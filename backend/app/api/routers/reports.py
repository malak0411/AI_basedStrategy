from fastapi import APIRouter, Depends, HTTPException, Query
from sqlalchemy.orm import Session
from sqlalchemy import func, desc, or_
from typing import Optional


from app.models import (
    StrategicPillar, StrategicGoal, Program, Initiative, MajorTask, OperationalTask,
    Risk, RiskMitigation, BudgetLine, BudgetTransaction, Employee, Department, 
    DictStatus, DictRiskLevel, DictTransactionType, KPI, KPIMeasurement
)
from app.core.dependencies import get_current_user
from app.database import get_db


router = APIRouter(prefix="/api/reports", tags=["Reports"])


@router.get("/dashboard")
async def get_dashboard_data(
    employee_id: int = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    # 1. الحسابات المالية (البرامج فقط لتجنب الازدواجية)
    budget_res = db.query(
        func.sum(BudgetLine.allocated_amount).label('total_alloc'),
        func.sum(BudgetLine.spent_amount).label('total_spent')
    ).filter(BudgetLine.budgetable_type == 'program').first()
    
    total_allocated = float(budget_res.total_alloc or 0)
    total_spent = float(budget_res.total_spent or 0)
    
    return {
        "counts": {
            "pillars": db.query(StrategicPillar).count(),
            "goals": db.query(StrategicGoal).count(),
            "programs": db.query(Program).count(),
            "initiatives": db.query(Initiative).count(),
            "major_tasks": db.query(MajorTask).count(),
            "op_tasks": db.query(OperationalTask).count(),
            "employees": db.query(Employee).filter(Employee.is_active == True).count(),
            "active_risks": db.query(Risk).filter(Risk.status_id.in_([9, 41, 42, 43])).count()
        },
        "financials": {
            "total_allocated": total_allocated,
            "total_spent": total_spent,
            "total_remaining": total_allocated - total_spent,
            "execution_pct": round((total_spent / total_allocated * 100), 2) if total_allocated > 0 else 0
        }
    }


@router.get("/strategic")
async def get_strategic_report(
    employee_id: int = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    # جلب البيانات دفعة واحدة لتجميعها في الذاكرة (أسرع من الاستعلام المتداخل)
    pillars = db.query(StrategicPillar).all()
    goals = db.query(StrategicGoal).all()
    programs = db.query(Program).all()
    initiatives = db.query(Initiative).all()
    major_tasks = db.query(MajorTask).all()
    op_tasks = db.query(OperationalTask).all()
    
    statuses = {s.status_id: s.name_ar for s in db.query(DictStatus).all()}
    
    # ربط المهام التشغيلية بالمهام الرئيسية
    ot_by_mt = {}
    for ot in op_tasks:
        ot_by_mt.setdefault(ot.major_task_id, []).append({
            "id": ot.task_id, "name": ot.title, "status": statuses.get(ot.status_id, "غير محدد")
        })
        
    mt_by_init = {}
    for mt in major_tasks:
        mt_by_init.setdefault(mt.initiative_id, []).append({
            "id": mt.major_task_id, "name": mt.name,
            "operational_tasks": ot_by_mt.get(mt.major_task_id, [])
        })
        
    init_by_prog = {}
    for init in initiatives:
        init_by_prog.setdefault(init.program_id, []).append({
            "id": init.initiative_id, "name": init.name, "status": statuses.get(init.status_id, "غير محدد"),
            "major_tasks": mt_by_init.get(init.initiative_id, [])
        })
        
    prog_by_goal = {}
    for prog in programs:
        prog_by_goal.setdefault(prog.goal_id, []).append({
            "id": prog.program_id, "name": prog.name, "status": statuses.get(prog.status_id, "غير محدد"),
            "initiatives": init_by_prog.get(prog.program_id, [])
        })


    tree = []
    for p in pillars:
        p_goals = []
        for g in [x for x in goals if x.pillar_id == p.pillar_id]:
            p_goals.append({
                "id": g.goal_id, "name": g.title,
                "programs": prog_by_goal.get(g.goal_id, [])
            })
        tree.append({
            "id": p.pillar_id, "name": p.name, "goals": p_goals
        })
        
    return {"success": True, "data": tree}


@router.get("/budget")
async def get_budget_report(
    employee_id: int = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    # تقرير الميزانية مقسم حسب النوع
    budgets = db.query(BudgetLine).all()
    result = {"program": [], "initiative": [], "major_task": [], "operational_task": []}
    
    for b in budgets:
        if b.budgetable_type in result:
            result[b.budgetable_type].append({
                "id": b.budgetable_id,
                "allocated": float(b.allocated_amount),
                "spent": float(b.spent_amount),
                "remaining": float(b.allocated_amount) - float(b.spent_amount)
            })
            
    return {"success": True, "data": result}


@router.get("/risks")
async def get_risks_report(
    employee_id: int = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    risks = db.query(Risk).all()
    levels = {l.risk_level_id: l.name_ar for l in db.query(DictRiskLevel).all()}
    statuses = {s.status_id: s.name_ar for s in db.query(DictStatus).all()}
    
    result = []
    for r in risks:
        task = db.query(OperationalTask).filter(OperationalTask.task_id == r.task_id).first()
        dept = db.query(Department).filter(Department.department_id == task.department_id).first() if task else None
        
        result.append({
            "id": r.risk_id,
            "name": r.name,
            "level": levels.get(r.risk_level_id, "غير محدد"),
            "status": statuses.get(r.status_id, "غير محدد"),
            "probability": r.probability,
            "impact": r.impact,
            "department": dept.name if dept else "غير مرتبط",
            "task": task.title if task else "غير مرتبط"
        })
        
    return {"success": True, "data": result}


@router.get("/kpis")
async def get_kpis_report(
    employee_id: int = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    kpis = db.query(KPI).filter(KPI.is_active == True).all()
    result = []
    
    for k in kpis:
        # جلب آخر قياس
        last_measure = db.query(KPIMeasurement).filter(
            KPIMeasurement.kpi_id == k.kpi_id
        ).order_by(desc(KPIMeasurement.measured_at)).first()
        
        actual = float(last_measure.value) if last_measure else None
        target = float(k.target_max) if k.target_max else 0
        
        achievement = 0
        if actual is not None and target > 0:
            achievement = round((actual / target) * 100, 2)
            
        result.append({
            "id": k.kpi_id,
            "name": k.name,
            "unit": k.unit,
            "target": target,
            "actual": actual,
            "achievement": achievement,
            "last_measured": last_measure.measured_at.isoformat() if last_measure else None
        })
        
    return {"success": True, "data": result}


@router.get("/departments")
async def get_departments_report(
    employee_id: int = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    depts = db.query(Department).all()
    result = []
    
    for d in depts:
        # عدد الموظفين
        emp_count = db.query(Employee).filter(Employee.department_id == d.department_id, Employee.is_active == True).count()
        # المهام التشغيلية
        tasks = db.query(OperationalTask).filter(OperationalTask.department_id == d.department_id).all()
        
        result.append({
            "id": d.department_id,
            "name": d.name,
            "employees": emp_count,
            "tasks": len(tasks),
            "completed_tasks": len([t for t in tasks if t.status_id == 19]),
            "in_progress_tasks": len([t for t in tasks if t.status_id == 6])
        })
        
    return {"success": True, "data": result}


@router.get("/employees")
async def get_employees_report(
    employee_id: int = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    emps = db.query(Employee).filter(Employee.is_active == True).all()
    result = []
    
    for e in emps:
        # المهام المسندة (من جدول task_assignments)
        assigned = db.query(TaskAssignment).filter(
            TaskAssignment.employee_id == e.employee_id,
            TaskAssignment.is_active == True
        ).count()
        
        # المهام المكتملة (بناءً على الجدول أعلاه أو جدول المهام)
        # نعتمد على task_assignments للربط
        result.append({
            "id": e.employee_id,
            "name": e.full_name,
            "job_title": e.job_title,
            "assigned_tasks": assigned
        })
        
    return {"success": True, "data": result}

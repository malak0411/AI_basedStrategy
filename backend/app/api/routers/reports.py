from fastapi import APIRouter, Depends, HTTPException, Query
from sqlalchemy.orm import Session
from sqlalchemy import func, desc, or_, and_
from typing import Optional
from datetime import datetime, date

from app.models import (
    StrategicPillar, StrategicGoal, Program, Initiative, MajorTask, OperationalTask,
    Risk, RiskMitigation, BudgetLine, Employee, Department, DictStatus, DictRiskLevel,
    KPI, KPIMeasurement, TaskAssignment, TaskProgressLog, DictTransactionType, GoalKPI
)
from app.core.dependencies import get_current_user
from app.database import get_db

router = APIRouter(prefix="/api/reports", tags=["Reports"])

@router.get("/dashboard")
async def get_dashboard_data(
    employee_id: int = Depends(get_current_user),
    db: Session = Depends(get_db)
):
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

@router.get("/strategic/general")
async def get_strategic_general_summary(
    db: Session = Depends(get_db),
    employee_id: int = Depends(get_current_user)
):
    return {
        "pillars": db.query(StrategicPillar).count(),
        "goals": db.query(StrategicGoal).count(),
        "programs": db.query(Program).count(),
        "initiatives": db.query(Initiative).count(),
        "major_tasks": db.query(MajorTask).count(),
        "op_tasks": db.query(OperationalTask).count(),
        "completed_tasks": db.query(OperationalTask).filter(OperationalTask.status_id == 19).count(),
        "in_progress_tasks": db.query(OperationalTask).filter(OperationalTask.status_id == 6).count()
    }

@router.get("/strategic")
async def get_strategic_report(
    pillar_id: Optional[int] = None,
    goal_id: Optional[int] = None,
    program_id: Optional[int] = None,
    initiative_id: Optional[int] = None,
    db: Session = Depends(get_db),
    employee_id: int = Depends(get_current_user)
):
    q_pillars = db.query(StrategicPillar)
    if pillar_id: q_pillars = q_pillars.filter(StrategicPillar.pillar_id == pillar_id)
    pillars = q_pillars.all()
    
    statuses = {s.status_id: s.name_ar for s in db.query(DictStatus).all()}
    budgets = {(b.budgetable_type, b.budgetable_id): b for b in db.query(BudgetLine).all()}

    tree = []
    for p in pillars:
        p_goals = []
        q_goals = db.query(StrategicGoal).filter(StrategicGoal.pillar_id == p.pillar_id)
        if goal_id: q_goals = q_goals.filter(StrategicGoal.goal_id == goal_id)
        
        for g in q_goals.all():
            g_progs = []
            q_progs = db.query(Program).filter(Program.goal_id == g.goal_id)
            if program_id: q_progs = q_progs.filter(Program.program_id == program_id)
            
            for prog in q_progs.all():
                prog_b = budgets.get(('program', prog.program_id))
                p_inits = []
                
                q_inits = db.query(Initiative).filter(Initiative.program_id == prog.program_id)
                if initiative_id: q_inits = q_inits.filter(Initiative.initiative_id == initiative_id)
                
                for init in q_inits.all():
                    init_b = budgets.get(('initiative', init.initiative_id))
                    i_mts = []
                    for mt in db.query(MajorTask).filter(MajorTask.initiative_id == init.initiative_id).all():
                        m_ots = []
                        tasks = db.query(OperationalTask).filter(OperationalTask.major_task_id == mt.major_task_id).all()
                        for ot in tasks:
                            m_ots.append({
                                "id": ot.task_id, "title": ot.title, "status": statuses.get(ot.status_id),
                                "start_date": ot.start_date.isoformat() if ot.start_date else None,
                                "end_date": ot.end_date.isoformat() if ot.end_date else None
                            })
                        i_mts.append({
                            "id": mt.major_task_id, "name": mt.name, 
                            "total_tasks": len(tasks),
                            "completed_tasks": len([t for t in tasks if t.status_id == 19]),
                            "op_tasks": m_ots
                        })
                    
                    p_inits.append({
                        "id": init.initiative_id, "name": init.name, "status": statuses.get(init.status_id),
                        "allocated": float(init_b.allocated_amount) if init_b else 0,
                        "spent": float(init_b.spent_amount) if init_b else 0,
                        "major_tasks": i_mts
                    })
                
                g_progs.append({
                    "id": prog.program_id, "name": prog.name, "status": statuses.get(prog.status_id),
                    "allocated": float(prog_b.allocated_amount) if prog_b else 0,
                    "spent": float(prog_b.spent_amount) if prog_b else 0,
                    "initiatives": p_inits
                })
            
            p_goals.append({"id": g.goal_id, "name": g.title, "programs": g_progs})
        tree.append({"id": p.pillar_id, "name": p.name, "goals": p_goals})
        
    return {"success": True, "data": tree}

@router.get("/budget")
async def get_budget_report(
    budgetable_type: Optional[str] = None,
    db: Session = Depends(get_db),
    employee_id: int = Depends(get_current_user)
):
    q = db.query(BudgetLine)
    if budgetable_type: q = q.filter(BudgetLine.budgetable_type == budgetable_type)
    budgets = q.all()
    
    prog_names = {p.program_id: p.name for p in db.query(Program).all()}
    init_names = {i.initiative_id: i.name for i in db.query(Initiative).all()}
    mt_names = {m.major_task_id: m.name for m in db.query(MajorTask).all()}
    ot_names = {o.task_id: o.title for o in db.query(OperationalTask).all()}
    
    parent_ids = [b.parent_budget_id for b in budgets if b.parent_budget_id]
    parents = {p.budget_id: p for p in db.query(BudgetLine).filter(BudgetLine.budget_id.in_(parent_ids)).all()} if parent_ids else {}
    
    result = []
    for b in budgets:
        name = "غير معروف"
        if b.budgetable_type == 'program': name = prog_names.get(b.budgetable_id, name)
        elif b.budgetable_type == 'initiative': name = init_names.get(b.budgetable_id, name)
        elif b.budgetable_type == 'major_task': name = mt_names.get(b.budgetable_id, name)
        elif b.budgetable_type == 'operational_task': name = ot_names.get(b.budgetable_id, name)
        
        parent_name = "لا يوجد"
        if b.parent_budget_id and b.parent_budget_id in parents:
            pb = parents[b.parent_budget_id]
            if pb.budgetable_type == 'program': parent_name = prog_names.get(pb.budgetable_id, "غير معروف")
            elif pb.budgetable_type == 'initiative': parent_name = init_names.get(pb.budgetable_id, "غير معروف")
            elif pb.budgetable_type == 'major_task': parent_name = mt_names.get(pb.budgetable_id, "غير معروف")
            
        result.append({
            "id": b.budget_id, "type": b.budgetable_type, "name": name, "parent_name": parent_name,
            "fiscal_year": b.fiscal_year, "allocated": float(b.allocated_amount), 
            "spent": float(b.spent_amount), "remaining": float(b.allocated_amount) - float(b.spent_amount)
        })
    return {"success": True, "data": result}

@router.get("/risks")
async def get_risks_report(
    initiative_id: Optional[int] = None,
    department_id: Optional[int] = None,
    db: Session = Depends(get_db),
    employee_id: int = Depends(get_current_user)
):
    q = db.query(Risk, OperationalTask, MajorTask, Initiative, Department).\
        outerjoin(OperationalTask, Risk.task_id == OperationalTask.task_id).\
        outerjoin(MajorTask, OperationalTask.major_task_id == MajorTask.major_task_id).\
        outerjoin(Initiative, MajorTask.initiative_id == Initiative.initiative_id).\
        outerjoin(Department, OperationalTask.department_id == Department.department_id)
        
    if initiative_id: q = q.filter(Initiative.initiative_id == initiative_id)
    if department_id: q = q.filter(Department.department_id == department_id)
    
    records = q.all()
    levels = {l.risk_level_id: l.name_ar for l in db.query(DictRiskLevel).all()}
    statuses = {s.status_id: s.name_ar for s in db.query(DictStatus).all()}
    mitigations = dict(db.query(RiskMitigation.risk_id, func.count(RiskMitigation.mitigation_id)).group_by(RiskMitigation.risk_id).all())
    
    result = []
    for risk, ot, mt, init, dept in records:
        result.append({
            "id": risk.risk_id, "name": risk.name,
            "initiative": init.name if init else "غير مرتبط",
            "task": ot.title if ot else "غير مرتبط",
            "department": dept.name if dept else "غير مرتبط",
            "level": levels.get(risk.risk_level_id, "غير محدد"),
            "status": statuses.get(risk.status_id, "غير محدد"),
            "mitigations_count": mitigations.get(risk.risk_id, 0),
            "target_date": risk.target_date.isoformat() if risk.target_date else None
        })
    return {"success": True, "data": result}

@router.get("/kpis")
async def get_kpis_report(
    category: Optional[str] = None,
    goal_id: Optional[int] = None,
    db: Session = Depends(get_db),
    employee_id: int = Depends(get_current_user)
):
    q = db.query(KPI).filter(KPI.is_active == True)
    if category: 
        q = q.filter(KPI.category == category)
    kpis = q.all()
    
    goal_kpis = db.query(GoalKPI, StrategicGoal).\
        join(StrategicGoal, GoalKPI.goal_id == StrategicGoal.goal_id).all()
    
    kpi_goals = {}
    for gk, g in goal_kpis:
        kpi_goals.setdefault(gk.kpi_id, []).append(g.title)
        
    result = []
    for k in kpis:
        if goal_id:
            linked_goal_ids = [gk.goal_id for gk in goal_kpis if gk.kpi_id == k.kpi_id]
            if goal_id not in linked_goal_ids:
                continue

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
            "description": k.description or "لا يوجد وصف",
            "category": k.category or "غير مصنف",
            "unit": k.unit or "-",
            "target_min": float(k.target_min) if k.target_min else 0,
            "target_max": target,
            "actual": actual,
            "achievement": achievement,
            "last_measured": last_measure.measured_at.isoformat() if last_measure else None,
            "goals": kpi_goals.get(k.kpi_id, [])
        })
        
    return {"success": True, "data": result}

@router.get("/departments")
async def get_departments_report(
    department_id: Optional[int] = None,
    db: Session = Depends(get_db),
    employee_id: int = Depends(get_current_user)
):
    q = db.query(Department)
    if department_id: q = q.filter(Department.department_id == department_id)
    depts = q.all()
    
    result = []
    for d in depts:
        emp_count = db.query(Employee).filter(Employee.department_id == d.department_id, Employee.is_active == True).count()
        op_tasks = db.query(OperationalTask).filter(OperationalTask.department_id == d.department_id).all()
        
        major_task_ids = set([t.major_task_id for t in op_tasks if t.major_task_id])
        major_tasks_completed = 0
        for mt_id in major_task_ids:
            mt = db.query(MajorTask).filter(MajorTask.major_task_id == mt_id).first()
            if mt:
                init = db.query(Initiative).filter(Initiative.initiative_id == mt.initiative_id).first()
                if init and init.status_id == 3: 
                    major_tasks_completed += 1
                    
        total_completed_by_dept = len([t for t in op_tasks if t.status_id == 19])
        total_tasks_by_dept = len(op_tasks)
        
        result.append({
            "id": d.department_id, "name": d.name, "employees_count": emp_count,
            "major_tasks_count": len(major_task_ids),
            "major_tasks_completed": major_tasks_completed,
            "op_tasks_count": total_tasks_by_dept,
            "op_tasks_completed": total_completed_by_dept,
            "op_tasks_in_progress": len([t for t in op_tasks if t.status_id == 6]),
            "op_tasks_delayed": len([t for t in op_tasks if t.end_date and t.end_date < datetime.now().date() and t.status_id != 19]),
            "completion_rate": round((total_completed_by_dept / total_tasks_by_dept * 100), 1) if total_tasks_by_dept > 0 else 0
        })
    return {"success": True, "data": result}

@router.get("/employees")
async def get_employees_report(
    department_id: Optional[int] = None,
    employee_id_filter: Optional[int] = None,
    db: Session = Depends(get_db),
    employee_id: int = Depends(get_current_user)
):
    q = db.query(Employee).filter(Employee.is_active == True)
    if department_id: q = q.filter(Employee.department_id == department_id)
    if employee_id_filter: q = q.filter(Employee.employee_id == employee_id_filter)
    emps = q.all()
    
    result = []
    for e in emps:
        assignments = db.query(TaskAssignment).filter(TaskAssignment.employee_id == e.employee_id).all()
        task_ids = [a.task_id for a in assignments]
        tasks = db.query(OperationalTask).filter(OperationalTask.task_id.in_(task_ids)).all() if task_ids else []
        
        result.append({
            "id": e.employee_id, "name": e.full_name, "job_title": e.job_title,
            "department": e.department.name if e.department else "غير محدد",
            "assigned_tasks": len(assignments),
            "completed_tasks": len([t for t in tasks if t.status_id == 19]),
            "in_progress_tasks": len([t for t in tasks if t.status_id == 6]),
            "delayed_tasks": len([t for t in tasks if t.end_date and t.end_date < datetime.now().date() and t.status_id != 19]),
            "rejected_tasks": len([a for a in assignments if a.acceptance_status == 'rejected']),
            "pending_tasks": len([a for a in assignments if a.acceptance_status == 'pending']),
            "mitigations_count": db.query(RiskMitigation).filter(RiskMitigation.assigned_to == e.employee_id).count()
        })
    return {"success": True, "data": result}

@router.get("/tasks")
async def get_tasks_report(
    department_id: Optional[int] = None,
    initiative_id: Optional[int] = None,
    db: Session = Depends(get_db),
    employee_id: int = Depends(get_current_user)
):
    q = db.query(OperationalTask)
    if department_id: q = q.filter(OperationalTask.department_id == department_id)
    
    actual_start_subq = db.query(
        TaskProgressLog.task_id, func.min(TaskProgressLog.log_time).label('actual_start')
    ).group_by(TaskProgressLog.task_id).subquery()
    
    actual_end_subq = db.query(
        TaskProgressLog.task_id, func.min(TaskProgressLog.log_time).label('actual_end')
    ).filter(
        or_(TaskProgressLog.status_new == 19, TaskProgressLog.progress_percent == 100)
    ).group_by(TaskProgressLog.task_id).subquery()
    
    q = db.query(
        OperationalTask,
        actual_start_subq.c.actual_start,
        actual_end_subq.c.actual_end
    ).outerjoin(
        actual_start_subq, OperationalTask.task_id == actual_start_subq.c.task_id
    ).outerjoin(
        actual_end_subq, OperationalTask.task_id == actual_end_subq.c.task_id
    )
         
    tasks = q.all()
    statuses = {s.status_id: s.name_ar for s in db.query(DictStatus).all()}
    result = []
    
    for task, actual_start, actual_end in tasks:
        mt = db.query(MajorTask).filter(MajorTask.major_task_id == task.major_task_id).first()
        if initiative_id and mt and mt.initiative_id != initiative_id:
            continue
            
        result.append({
            "id": task.task_id, "title": task.title, "status": statuses.get(task.status_id, "غير محدد"),
            "planned_start": task.start_date.isoformat() if task.start_date else None,
            "planned_end": task.end_date.isoformat() if task.end_date else None,
            "actual_start": actual_start.isoformat() if actual_start else None,
            "actual_end": actual_end.isoformat() if actual_end else None,
            "estimated_hours": task.estimated_hours, "actual_hours": task.actual_hours
        })
    return {"success": True, "data": result}

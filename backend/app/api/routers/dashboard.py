from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy.orm import Session
from sqlalchemy import func
from typing import List, Optional
from datetime import datetime, date

from ...database import get_db
from ...models import (
    Employee, Department, OperationalTask, Initiative,
    StrategicGoal, Program,
    TaskAssignment, TaskProgressLog,
    Risk, BudgetLine, BudgetTransaction,
    DictStatus, DictRiskLevel, DictTransactionType,
    KPI
)
from ...schemas import (
    MinisterDashboardResponse, DepartmentPerformance,
    EmployeeDashboardResponse
)
from ...core.dependencies import get_current_user, has_role

router = APIRouter(prefix="/api/dashboard", tags=["لوحات التحكم"])

# ================================================================
# 1. لوحة تحكم الوزير (Minister Dashboard)
# ================================================================

@router.get("/minister", response_model=MinisterDashboardResponse)
async def get_minister_dashboard(
    db: Session = Depends(get_db),
    current_user: Employee = Depends(has_role(["وزير / قيادة العليا", "وكيل وزارة", "Super Admin"]))
):
    try:
        # ============================================================
        # 1.1 إحصائيات عامة
        # ============================================================
        total_goals = db.query(StrategicGoal).filter(StrategicGoal.is_active == True).count()
        total_initiatives = db.query(Initiative).filter(Initiative.is_active == True).count()
        total_tasks = db.query(OperationalTask).filter(OperationalTask.is_active == True).count()
        
        completed_status = db.query(DictStatus).filter(DictStatus.code == "completed").first()
        completed_tasks = 0
        if completed_status:
            completed_tasks = db.query(OperationalTask).filter(
                OperationalTask.status_id == completed_status.status_id,
                OperationalTask.is_active == True
            ).count()
        
        overall_completion = round((completed_tasks / total_tasks * 100), 2) if total_tasks > 0 else 0
        
        # ============================================================
        # 1.2 أداء الإدارات
        # ============================================================
        departments = db.query(Department).filter(Department.is_active == True).all()
        departments_performance = []
        
        for dept in departments:
            tasks = db.query(OperationalTask).filter(
                OperationalTask.department_id == dept.department_id,
                OperationalTask.is_active == True
            ).all()
            
            total = len(tasks)
            completed = 0
            if completed_status:
                completed = len([t for t in tasks if t.status_id == completed_status.status_id])
            delayed = len([t for t in tasks if t.end_date and date.today() > t.end_date and (not completed_status or t.status_id != completed_status.status_id)])
            
            avg_progress = 0
            if total > 0:
                progresses = []
                for task in tasks:
                    latest_log = db.query(TaskProgressLog).filter(
                        TaskProgressLog.task_id == task.task_id
                    ).order_by(TaskProgressLog.log_time.desc()).first()
                    if latest_log:
                        progresses.append(latest_log.progress_percent)
                avg_progress = round(sum(progresses) / len(progresses), 2) if progresses else 0
            
            departments_performance.append(DepartmentPerformance(
                department_id=dept.department_id,
                name=dept.name,
                total_tasks=total,
                completed_tasks=completed,
                completion_rate=round((completed / total * 100), 2) if total > 0 else 0,
                delayed_tasks=delayed,
                average_progress=avg_progress
            ))
        
        departments_performance.sort(key=lambda x: x.completion_rate, reverse=True)
        
        # ============================================================
        # 1.3 ملخص مالي (آمن)
        # ============================================================
        total_allocated = db.query(func.sum(BudgetLine.allocated_amount)).scalar() or 0
        
        spent_type = db.query(DictTransactionType).filter(DictTransactionType.code == "spent").first()
        spent_type_id = spent_type.trans_type_id if spent_type else None
        total_spent = 0
        if spent_type_id:
            total_spent = db.query(func.sum(BudgetTransaction.amount)).filter(
                BudgetTransaction.transaction_type_id == spent_type_id
            ).scalar() or 0
        
        financial_summary = {
            "total_allocated": float(total_allocated),
            "total_spent": float(total_spent),
            "remaining": float(total_allocated - total_spent),
            "spent_percentage": round((total_spent / total_allocated * 100), 2) if total_allocated > 0 else 0
        }
        
        # ============================================================
        # 1.4 المخاطر الحرجة
        # ============================================================
        resolved_status = db.query(DictStatus).filter(DictStatus.code == "resolved").first()
        resolved_id = resolved_status.status_id if resolved_status else None
        
        query = db.query(Risk)
        if resolved_id:
            query = query.filter(Risk.status_id != resolved_id)
        critical_risks = query.order_by(Risk.risk_score.desc()).limit(5).all()
        
        critical_risks_list = []
        for risk in critical_risks:
            task = db.query(OperationalTask).filter(OperationalTask.task_id == risk.task_id).first()
            risk_level = db.query(DictRiskLevel).filter(DictRiskLevel.risk_level_id == risk.risk_level_id).first()
            critical_risks_list.append({
                "risk_id": risk.risk_id,
                "name": risk.name,
                "severity": risk_level.name_ar if risk_level else "غير محدد",
                "task_title": task.title if task else "غير محدد",
                "risk_score": risk.risk_score
            })
        
        # ============================================================
        # 1.5 تقرير AI موجز
        # ============================================================
        delayed_tasks_count = sum([d.delayed_tasks for d in departments_performance])
        high_risk_count = len([r for r in critical_risks_list if r.get("risk_score", 0) >= 70])
        
        ai_briefing = f"""
📊 **التقرير التنفيذي اليومي** ({datetime.now().strftime('%Y-%m-%d')})

• **نسبة الإنجاز الإجمالية:** {overall_completion}%
• **المهام المكتملة:** {completed_tasks} من {total_tasks}
• **المهام المتأخرة:** {delayed_tasks_count}
• **المخاطر الحرجة:** {high_risk_count}
• **الميزانية:** تم صرف {financial_summary['spent_percentage']}% من الميزانية المخصصة

**التوصيات:**
{ '🔴 مراجعة الإدارات المتأخرة' if delayed_tasks_count > 0 else '✅ جميع الإدارات في المسار الصحيح' }
{ '⚠️ تدقيق الصرف المالي' if financial_summary['spent_percentage'] > 80 else '✅ الميزانية تحت السيطرة' }
{ '🚨 مراجعة المخاطر الحرجة فوراً' if high_risk_count > 0 else '✅ مستوى المخاطر منخفض' }
"""
        
        # ============================================================
        # 1.6 إرجاع الاستجابة
        # ============================================================
        return MinisterDashboardResponse(
            total_goals=total_goals,
            total_initiatives=total_initiatives,
            total_tasks=total_tasks,
            overall_completion=overall_completion,
            departments_performance=departments_performance,
            financial_summary=financial_summary,
            critical_risks=critical_risks_list,
            ai_briefing=ai_briefing
        )
    
    except Exception as e:
        # تسجيل الخطأ وإعادته للمطور
        print(f"Error in minister dashboard: {e}")
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail=f"حدث خطأ داخلي: {str(e)}"
        )


# ================================================================
# 2. لوحة تحكم مدير الإدارة (Manager Dashboard)
# ================================================================

@router.get("/manager")
async def get_manager_dashboard(
    db: Session = Depends(get_db),
    current_user: Employee = Depends(has_role(["مدير إدارة", "مدير عام", "وكيل وزارة", "وزير / قيادة العليا", "Super Admin"]))
):
    try:
        department_id = current_user.department_id
        completed_status = db.query(DictStatus).filter(DictStatus.code == "completed").first()
        
        tasks = db.query(OperationalTask).filter(
            OperationalTask.department_id == department_id,
            OperationalTask.is_active == True
        ).all()
        
        total_tasks = len(tasks)
        completed_tasks = 0
        if completed_status:
            completed_tasks = len([t for t in tasks if t.status_id == completed_status.status_id])
        
        in_progress_status = db.query(DictStatus).filter(DictStatus.code == "in_progress").first()
        in_progress_tasks = 0
        if in_progress_status:
            in_progress_tasks = len([t for t in tasks if t.status_id == in_progress_status.status_id])
        
        pending_status = db.query(DictStatus).filter(DictStatus.code == "pending").first()
        pending_tasks = 0
        if pending_status:
            pending_tasks = len([t for t in tasks if t.status_id == pending_status.status_id])
        
        delayed_tasks = len([t for t in tasks if t.end_date and date.today() > t.end_date and (not completed_status or t.status_id != completed_status.status_id)])
        
        progresses = []
        for task in tasks:
            latest_log = db.query(TaskProgressLog).filter(
                TaskProgressLog.task_id == task.task_id
            ).order_by(TaskProgressLog.log_time.desc()).first()
            if latest_log:
                progresses.append(latest_log.progress_percent)
        avg_progress = round(sum(progresses) / len(progresses), 2) if progresses else 0
        
        # أداء الموظفين
        employees = db.query(Employee).filter(
            Employee.department_id == department_id,
            Employee.is_active == True
        ).all()
        
        employees_performance = []
        for emp in employees:
            assigned_task_ids = db.query(TaskAssignment.task_id).filter(
                TaskAssignment.employee_id == emp.employee_id,
                TaskAssignment.is_active == True
            )
            emp_tasks = db.query(OperationalTask).filter(
                OperationalTask.task_id.in_(assigned_task_ids),
                OperationalTask.is_active == True
            ).all()
            
            total = len(emp_tasks)
            completed = 0
            if completed_status:
                completed = len([t for t in emp_tasks if t.status_id == completed_status.status_id])
            
            emp_progress = []
            for task in emp_tasks:
                latest_log = db.query(TaskProgressLog).filter(
                    TaskProgressLog.task_id == task.task_id
                ).order_by(TaskProgressLog.log_time.desc()).first()
                if latest_log:
                    emp_progress.append(latest_log.progress_percent)
            emp_avg = round(sum(emp_progress) / len(emp_progress), 2) if emp_progress else 0
            
            employees_performance.append({
                "employee_id": emp.employee_id,
                "full_name": emp.full_name,
                "total_tasks": total,
                "completed_tasks": completed,
                "completion_rate": round((completed / total * 100), 2) if total > 0 else 0,
                "avg_progress": emp_avg
            })
        
        employees_performance.sort(key=lambda x: x["completion_rate"], reverse=True)
        
        # المهام المتأخرة
        delayed_tasks_list = []
        for task in tasks:
            if task.end_date and date.today() > task.end_date and (not completed_status or task.status_id != completed_status.status_id):
                assignments = db.query(TaskAssignment).filter(
                    TaskAssignment.task_id == task.task_id,
                    TaskAssignment.is_active == True
                ).all()
                assigned_employees = []
                for a in assignments:
                    emp = db.query(Employee).filter(Employee.employee_id == a.employee_id).first()
                    if emp:
                        assigned_employees.append(emp.full_name)
                delayed_tasks_list.append({
                    "task_id": task.task_id,
                    "title": task.title,
                    "end_date": task.end_date,
                    "days_delayed": (date.today() - task.end_date).days,
                    "assigned_to": ", ".join(assigned_employees) if assigned_employees else "غير معين"
                })
        
        # المخاطر
        risks = db.query(Risk).join(
            OperationalTask, Risk.task_id == OperationalTask.task_id
        ).filter(
            OperationalTask.department_id == department_id
        )
        if resolved_status:
            risks = risks.filter(Risk.status_id != resolved_status.status_id)
        risks_list = []
        for risk in risks.all():
            task = db.query(OperationalTask).filter(OperationalTask.task_id == risk.task_id).first()
            risk_level = db.query(DictRiskLevel).filter(DictRiskLevel.risk_level_id == risk.risk_level_id).first()
            risks_list.append({
                "risk_id": risk.risk_id,
                "name": risk.name,
                "severity": risk_level.name_ar if risk_level else "غير محدد",
                "task_title": task.title if task else "غير محدد",
                "risk_score": risk.risk_score
            })
        
        return {
            "department_id": department_id,
            "department_name": current_user.department.name if current_user.department else "غير محدد",
            "summary": {
                "total_tasks": total_tasks,
                "completed_tasks": completed_tasks,
                "in_progress_tasks": in_progress_tasks,
                "pending_tasks": pending_tasks,
                "delayed_tasks": delayed_tasks,
                "average_progress": avg_progress,
                "completion_rate": round((completed_tasks / total_tasks * 100), 2) if total_tasks > 0 else 0
            },
            "employees_performance": employees_performance[:10],
            "delayed_tasks": delayed_tasks_list[:10],
            "risks": risks_list
        }
    except Exception as e:
        print(f"Error in manager dashboard: {e}")
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail=f"حدث خطأ داخلي: {str(e)}"
        )


# ================================================================
# 3. لوحة تحكم الموظف (Employee Dashboard)
# ================================================================

@router.get("/employee", response_model=EmployeeDashboardResponse)
async def get_employee_dashboard(
    db: Session = Depends(get_db),
    current_user: Employee = Depends(get_current_user)
):
    try:
        assigned_task_ids = db.query(TaskAssignment.task_id).filter(
            TaskAssignment.employee_id == current_user.employee_id,
            TaskAssignment.is_active == True
        )
        tasks = db.query(OperationalTask).filter(
            OperationalTask.task_id.in_(assigned_task_ids),
            OperationalTask.is_active == True
        ).all()
        
        total = len(tasks)
        completed_status = db.query(DictStatus).filter(DictStatus.code == "completed").first()
        completed = 0
        if completed_status:
            completed = len([t for t in tasks if t.status_id == completed_status.status_id])
        
        in_progress_status = db.query(DictStatus).filter(DictStatus.code == "in_progress").first()
        in_progress = 0
        if in_progress_status:
            in_progress = len([t for t in tasks if t.status_id == in_progress_status.status_id])
        
        pending_status = db.query(DictStatus).filter(DictStatus.code == "pending").first()
        pending = 0
        if pending_status:
            pending = len([t for t in tasks if t.status_id == pending_status.status_id])
        
        delayed = len([t for t in tasks if t.end_date and date.today() > t.end_date and (not completed_status or t.status_id != completed_status.status_id)])
        
        total_progress = 0
        for task in tasks:
            latest_log = db.query(TaskProgressLog).filter(
                TaskProgressLog.task_id == task.task_id
            ).order_by(TaskProgressLog.log_time.desc()).first()
            if latest_log:
                total_progress += latest_log.progress_percent
        avg_progress = round(total_progress / total, 2) if total > 0 else 0
        
        # آخر النشاطات
        recent_logs = db.query(TaskProgressLog).join(
            OperationalTask, TaskProgressLog.task_id == OperationalTask.task_id
        ).join(
            TaskAssignment, OperationalTask.task_id == TaskAssignment.task_id
        ).filter(
            TaskAssignment.employee_id == current_user.employee_id,
            TaskAssignment.is_active == True
        ).order_by(TaskProgressLog.log_time.desc()).limit(5).all()
        
        recent_activities = []
        for log in recent_logs:
            task = db.query(OperationalTask).filter(OperationalTask.task_id == log.task_id).first()
            recent_activities.append({
                "task_title": task.title if task else "غير محدد",
                "progress": log.progress_percent,
                "time": log.log_time.strftime("%Y-%m-%d %H:%M")
            })
        
        return EmployeeDashboardResponse(
            employee_name=current_user.full_name,
            job_title=current_user.job_title,
            department=current_user.department.name if current_user.department else None,
            tasks_summary={
                "total": total,
                "completed": completed,
                "in_progress": in_progress,
                "pending": pending,
                "delayed": delayed,
                "average_progress": avg_progress,
                "completion_rate": round((completed / total * 100), 2) if total > 0 else 0
            },
            recent_activities=recent_activities
        )
    except Exception as e:
        print(f"Error in employee dashboard: {e}")
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail=f"حدث خطأ داخلي: {str(e)}"
        )


# ================================================================
# 4. مؤشرات الأداء الرئيسية (KPIs Dashboard)
# ================================================================

@router.get("/kpis")
async def get_kpis_dashboard(
    db: Session = Depends(get_db),
    current_user: Employee = Depends(get_current_user)
):
    try:
        kpis = db.query(KPI).filter(KPI.is_active == True).all()
        result = []
        for kpi in kpis:
            latest_measurement = db.query(KPIMeasurement).filter(
                KPIMeasurement.kpi_id == kpi.kpi_id
            ).order_by(KPIMeasurement.measured_at.desc()).first()
            
            result.append({
                "kpi_id": kpi.kpi_id,
                "name": kpi.name,
                "description": kpi.description,
                "category": kpi.category,
                "unit": kpi.unit,
                "target_min": float(kpi.target_min) if kpi.target_min else None,
                "target_max": float(kpi.target_max) if kpi.target_max else None,
                "current_value": float(latest_measurement.value) if latest_measurement else None,
                "last_measured_at": latest_measurement.measured_at if latest_measurement else None,
                "status": "✅ ضمن الهدف" if latest_measurement and kpi.target_min and kpi.target_max and kpi.target_min <= latest_measurement.value <= kpi.target_max else "⚠️ خارج الهدف" if latest_measurement else "❌ لا يوجد قياس"
            })
        return result
    except Exception as e:
        print(f"Error in KPIs dashboard: {e}")
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail=f"حدث خطأ داخلي: {str(e)}"
        )

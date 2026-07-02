from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session
from sqlalchemy import func
from app.database import get_db
from app.models import Employee, Department, OperationalTask, TaskAssignment

router = APIRouter(prefix="/api/dashboard", tags=["Dashboard"])

@router.get("/employee")
async def employee_dashboard(db: Session = Depends(get_db)):
    try:
        employee_id = 6  # مؤقتاً
        
        employee = db.query(Employee).filter(Employee.employee_id == employee_id).first()
        if not employee:
            raise HTTPException(status_code=404, detail="الموظف غير موجود")

        total_tasks = db.query(func.count(OperationalTask.task_id)).join(
            TaskAssignment, OperationalTask.task_id == TaskAssignment.task_id
        ).filter(TaskAssignment.employee_id == employee_id).scalar() or 0

        completed_tasks = db.query(func.count(OperationalTask.task_id)).join(
            TaskAssignment, OperationalTask.task_id == TaskAssignment.task_id
        ).filter(
            TaskAssignment.employee_id == employee_id,
            OperationalTask.status_id == 4  # completed
        ).scalar() or 0

        in_progress = db.query(func.count(OperationalTask.task_id)).join(
            TaskAssignment, OperationalTask.task_id == TaskAssignment.task_id
        ).filter(
            TaskAssignment.employee_id == employee_id,
            OperationalTask.status_id == 2  # in_progress
        ).scalar() or 0

        delayed = db.query(func.count(OperationalTask.task_id)).join(
            TaskAssignment, OperationalTask.task_id == TaskAssignment.task_id
        ).filter(
            TaskAssignment.employee_id == employee_id,
            OperationalTask.status_id == 3  # delayed
        ).scalar() or 0

        completion_rate = round((completed_tasks / total_tasks * 100) if total_tasks > 0 else 0, 1)

        return {
            "success": True,
            "data": {
                "employee_name": employee.full_name,
                "department_name": employee.department.name if employee.department else "",
                "total_tasks": total_tasks,
                "completed_tasks": completed_tasks,
                "in_progress_tasks": in_progress,
                "delayed_tasks": delayed,
                "completion_rate": completion_rate
            }
        }
    except HTTPException:
        raise
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")


@router.get("/manager")
async def manager_dashboard(db: Session = Depends(get_db)):
    try:
        department_id = 6

        department = db.query(Department).filter(Department.department_id == department_id).first()

        total_employees = db.query(func.count(Employee.employee_id)).filter(
            Employee.department_id == department_id
        ).scalar() or 0

        dept_tasks = db.query(OperationalTask).filter(
            OperationalTask.department_id == department_id
        ).all()

        total_tasks = len(dept_tasks)
        active_tasks = sum(1 for t in dept_tasks if t.status_id == 2)
        completed = sum(1 for t in dept_tasks if t.status_id == 4)
        delayed = sum(1 for t in dept_tasks if t.status_id == 3)
        completion_rate = round((completed / total_tasks * 100) if total_tasks > 0 else 0, 1)

        return {
            "success": True,
            "data": {
                "department_name": department.name if department else "",
                "total_employees": total_employees,
                "total_tasks": total_tasks,
                "active_tasks": active_tasks,
                "completed_tasks": completed,
                "delayed_tasks": delayed,
                "completion_rate": completion_rate,
                "pending_tasks": total_tasks - completed - active_tasks,
                "tasks_this_month": total_tasks,
                "average_performance": completion_rate
            }
        }
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")


@router.get("/minister")
async def minister_dashboard(db: Session = Depends(get_db)):
    try:
        total_employees = db.query(func.count(Employee.employee_id)).scalar() or 0
        total_departments = db.query(func.count(Department.department_id)).scalar() or 0
        total_tasks = db.query(func.count(OperationalTask.task_id)).scalar() or 0
        delayed = db.query(func.count(OperationalTask.task_id)).filter(
            OperationalTask.status_id == 3
        ).scalar() or 0
        completed = db.query(func.count(OperationalTask.task_id)).filter(
            OperationalTask.status_id == 4
        ).scalar() or 0
        completion_rate = round((completed / total_tasks * 100) if total_tasks > 0 else 0, 1)

        return {
            "success": True,
            "data": {
                "total_employees": total_employees,
                "total_departments": total_departments,
                "active_projects": total_tasks,
                "budget_utilization": 0,
                "completion_rate": completion_rate,
                "delayed_tasks": delayed,
                "total_tasks": total_tasks
            }
        }
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")


@router.get("/departments-performance")
async def departments_performance(db: Session = Depends(get_db)):
    try:
        departments = db.query(Department).all()
        result = []
        for dept in departments:
            tasks = db.query(OperationalTask).filter(
                OperationalTask.department_id == dept.department_id
            ).all()
            total = len(tasks)
            completed = sum(1 for t in tasks if t.status_id == 4)
            result.append({
                "name": dept.name,
                "completed_tasks": completed,
                "total_tasks": total,
                "completion_rate": round((completed / total * 100) if total > 0 else 0, 1)
            })
        return {"success": True, "data": result}
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")

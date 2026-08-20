from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy import func
from sqlalchemy.orm import Session
from app.database import get_db
from app.models import Employee
from pydantic import BaseModel
from typing import Optional
from app.models import TaskAssignment, OperationalTask, DictStatus
from app.core.dependencies import get_current_employee

router = APIRouter(prefix="/api/employees", tags=["Employees"])

class EmployeeUpdate(BaseModel):
    full_name: Optional[str] = None
    email: Optional[str] = None
    phone_number: Optional[str] = None
    job_title: Optional[str] = None
    department_id: Optional[int] = None
    is_active: Optional[bool] = None

@router.get("/")
async def get_employees(db: Session = Depends(get_db)):
    try:
        employees = db.query(Employee).all()
        result = [{
            "employee_id": e.employee_id,
            "full_name": e.full_name,
            "email": e.email,
            "phone_number": e.phone_number,
            "job_title": e.job_title,
            "department_name": e.department.name if e.department else None,
            "department_id": e.department_id,
            "is_active": e.is_active,
            "employee_number": e.employee_number,
            "hire_date": e.hire_date.isoformat() if e.hire_date else None,
            "last_login": e.last_login.isoformat() if e.last_login else None
        } for e in employees]
        
        db.commit()
        return {"success": True, "data": result}
    except Exception as e:
        db.rollback()
        raise HTTPException(status_code=500, detail=str(e))

@router.get("/department")
async def get_department_employees(db: Session = Depends(get_db)):
    try:
        department_id = 6
        employees = db.query(Employee).filter(Employee.department_id == department_id).all()
        result = [{
            "employee_id": e.employee_id,
            "full_name": e.full_name,
            "job_title": e.job_title,
            "is_active": e.is_active
        } for e in employees]
        
        db.commit()
        return {"success": True, "data": result}
    except Exception as e:
        db.rollback()
        raise HTTPException(status_code=500, detail=str(e))

@router.get("/{employee_id}")
async def get_employee(employee_id: int, db: Session = Depends(get_db)):
    try:
        e = db.query(Employee).filter(Employee.employee_id == employee_id).first()
        if not e:
            raise HTTPException(status_code=404, detail="الموظف غير موجود")
        
        result = {
            "employee_id": e.employee_id,
            "full_name": e.full_name,
            "email": e.email,
            "phone_number": e.phone_number,
            "job_title": e.job_title,
            "department_name": e.department.name if e.department else None,
            "department_id": e.department_id,
            "is_active": e.is_active,
            "hire_date": e.hire_date.isoformat() if e.hire_date else None,
            "employee_number": e.employee_number,
            "last_login": e.last_login.isoformat() if e.last_login else None
        }
        
        db.commit()
        return {"success": True, "data": result}
    except HTTPException:
        raise
    except Exception as e:
        db.rollback()
        raise HTTPException(status_code=500, detail=str(e))

@router.put("/{employee_id}")
async def update_employee(employee_id: int, data: EmployeeUpdate, db: Session = Depends(get_db)):
  
    try:
        e = db.query(Employee).filter(Employee.employee_id == employee_id).first()
        if not e:
            raise HTTPException(status_code=404, detail="الموظف غير موجود")

        if data.full_name is not None:
            e.full_name = data.full_name
        if data.email is not None:
            e.email = data.email
        if data.phone_number is not None:
            e.phone_number = data.phone_number
        if data.job_title is not None:
            e.job_title = data.job_title
        if data.department_id is not None:
            e.department_id = data.department_id
        if data.is_active is not None:
            e.is_active = data.is_active

        db.commit()
        return {"success": True, "message": "تم تحديث بيانات الموظف بنجاح"}
    except HTTPException:
        raise
    except Exception as e:
        db.rollback()
        raise HTTPException(status_code=500, detail=f"خطأ في تحديث الموظف: {str(e)}")

@router.get("/department/{department_id}")
async def get_employees_by_department(
    department_id: int,
    current_user: Employee = Depends(get_current_employee),
    db: Session = Depends(get_db)
):
    employees = db.query(Employee).filter(
        Employee.department_id == department_id,
        Employee.is_active == True
    ).all()
    
    result = []
    for e in employees:
        result.append({
            "employee_id": e.employee_id,
            "full_name": e.full_name,
            "job_title": e.job_title or "",
            "department_id": e.department_id
        })
    
    db.commit()
    return {"success": True, "data": result}

@router.get("/{employee_id}/task-stats")
async def get_employee_task_stats(
    employee_id: int,
    current_user: Employee = Depends(get_current_employee),
    db: Session = Depends(get_db)
):
    employee = db.query(Employee).filter(
        Employee.employee_id == employee_id,
        Employee.is_active == True
    ).first()
    
    if not employee:
        return {"success": False, "data": None, "message": "الموظف غير موجود"}
    
    total_tasks = db.query(func.count(TaskAssignment.task_id)).filter(
        TaskAssignment.employee_id == employee_id,
        TaskAssignment.is_active == True
    ).scalar() or 0
    
    total_hours = db.query(func.sum(OperationalTask.estimated_hours)).join(
        TaskAssignment, OperationalTask.task_id == TaskAssignment.task_id
    ).filter(
        TaskAssignment.employee_id == employee_id,
        TaskAssignment.is_active == True
    ).scalar() or 0
    
    current_tasks = db.query(func.count(TaskAssignment.task_id)).join(
        OperationalTask, TaskAssignment.task_id == OperationalTask.task_id
    ).filter(
        TaskAssignment.employee_id == employee_id,
        TaskAssignment.is_active == True,
        OperationalTask.status_id.notin_([4, 10])
    ).scalar() or 0
    
    current_hours = db.query(func.sum(OperationalTask.estimated_hours)).join(
        TaskAssignment, OperationalTask.task_id == TaskAssignment.task_id
    ).filter(
        TaskAssignment.employee_id == employee_id,
        TaskAssignment.is_active == True,
        OperationalTask.status_id.notin_([4, 10])
    ).scalar() or 0
    
    db.commit()
    
    return {
        "success": True,
        "data": {
            "total_tasks": total_tasks,
            "total_hours": float(total_hours) if total_hours else 0,
            "current_tasks": current_tasks,
            "current_hours": float(current_hours) if current_hours else 0
        }
    }

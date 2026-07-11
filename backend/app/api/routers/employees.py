from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session
from app.database import get_db
from app.models import Employee
from pydantic import BaseModel
from typing import Optional

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
            "employee_id": e.employee_id, "full_name": e.full_name,
            "email": e.email, "phone_number": e.phone_number,
            "job_title": e.job_title, "department_name": e.department.name if e.department else None,
            "is_active": e.is_active, "hire_date": e.hire_date.isoformat() if e.hire_date else None
        } for e in employees]
        return {"success": True, "data": result}
    except Exception as e: raise HTTPException(status_code=500, detail=str(e))

@router.get("/department")
async def get_department_employees(db: Session = Depends(get_db)):
    try:
        employees = db.query(Employee).filter(Employee.department_id == 6).all()
        result = [{"employee_id": e.employee_id, "full_name": e.full_name, "job_title": e.job_title, "is_active": e.is_active} for e in employees]
        return {"success": True, "data": result}
    except Exception as e: raise HTTPException(status_code=500, detail=str(e))

@router.get("/{employee_id}")
async def get_employee(employee_id: int, db: Session = Depends(get_db)):
    try:
        e = db.query(Employee).filter(Employee.employee_id == employee_id).first()
        if not e: raise HTTPException(status_code=404, detail="غير موجود")
        return {"success": True, "data": {
            "employee_id": e.employee_id, "full_name": e.full_name,
            "email": e.email, "phone_number": e.phone_number,
            "job_title": e.job_title, "department_name": e.department.name if e.department else None,
            "is_active": e.is_active, "hire_date": e.hire_date.isoformat() if e.hire_date else None,
            "employee_number": e.employee_number, "last_login": e.last_login.isoformat() if e.last_login else None
        }}
    except HTTPException: raise
    except Exception as e: raise HTTPException(status_code=500, detail=str(e))

@router.put("/{employee_id}")
async def update_employee(employee_id: int, data: EmployeeUpdate, db: Session = Depends(get_db)):
    try:
        e = db.query(Employee).filter(Employee.employee_id == employee_id).first()
        if not e: raise HTTPException(status_code=404, detail="غير موجود")
        if data.full_name is not None: e.full_name = data.full_name
        if data.email is not None: e.email = data.email
        if data.phone_number is not None: e.phone_number = data.phone_number
        if data.job_title is not None: e.job_title = data.job_title
        if data.department_id is not None: e.department_id = data.department_id
        if data.is_active is not None: e.is_active = data.is_active
        db.commit()
        return {"success": True, "message": "تم تحديث الموظف"}
    except HTTPException: raise
    except Exception as e:
        db.rollback()
        raise HTTPException(status_code=500, detail=str(e))

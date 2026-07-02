from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session
from app.database import get_db
from app.models import Employee, Department

router = APIRouter(prefix="/api/employees", tags=["Employees"])

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
            "is_active": e.is_active,
            "hire_date": e.hire_date.isoformat() if e.hire_date else None
        } for e in employees]
        return {"success": True, "data": result}
    except Exception as e:
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
        return {"success": True, "data": result}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@router.get("/{employee_id}")
async def get_employee(employee_id: int, db: Session = Depends(get_db)):
    try:
        e = db.query(Employee).filter(Employee.employee_id == employee_id).first()
        if not e:
            raise HTTPException(status_code=404, detail="الموظف غير موجود")
        return {
            "success": True,
            "data": {
                "employee_id": e.employee_id,
                "full_name": e.full_name,
                "email": e.email,
                "phone_number": e.phone_number,
                "job_title": e.job_title,
                "department_name": e.department.name if e.department else None,
                "is_active": e.is_active,
                "hire_date": e.hire_date.isoformat() if e.hire_date else None,
                "employee_number": e.employee_number,
                "last_login": e.last_login.isoformat() if e.last_login else None
            }
        }
    except HTTPException:
        raise
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

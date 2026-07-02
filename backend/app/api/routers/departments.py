from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session
from app.database import get_db
from app.models import Department, Employee

router = APIRouter(prefix="/api/departments", tags=["Departments"])

@router.get("/")
async def get_departments(db: Session = Depends(get_db)):
    try:
        depts = db.query(Department).all()
        result = [{
            "department_id": d.department_id,
            "name": d.name,
            "code": d.code,
            "description": d.description,
            "level": d.level,
            "manager_name": d.manager.full_name if d.manager else None
        } for d in depts]
        return {"success": True, "data": result}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@router.get("/{department_id}")
async def get_department(department_id: int, db: Session = Depends(get_db)):
    try:
        d = db.query(Department).filter(Department.department_id == department_id).first()
        if not d:
            raise HTTPException(status_code=404, detail="الإدارة غير موجودة")
        
        employees = db.query(Employee).filter(Employee.department_id == department_id).all()
        
        return {
            "success": True,
            "data": {
                "department_id": d.department_id,
                "name": d.name,
                "code": d.code,
                "description": d.description,
                "level": d.level,
                "manager_name": d.manager.full_name if d.manager else None,
                "parent_name": d.parent.name if d.parent else None,
                "employees": [{
                    "full_name": e.full_name,
                    "job_title": e.job_title,
                    "is_active": e.is_active
                } for e in employees]
            }
        }
    except HTTPException:
        raise
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

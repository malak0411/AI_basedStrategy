from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session
from app.database import get_db
from app.models import Department, Employee
from pydantic import BaseModel
from typing import Optional

router = APIRouter(prefix="/api/departments", tags=["Departments"])

class DepartmentCreate(BaseModel):
    name: str
    code: Optional[str] = None
    description: Optional[str] = ""
    parent_department_id: Optional[int] = None
    manager_employee_id: Optional[int] = None
    level: Optional[int] = 1

class DepartmentUpdate(BaseModel):
    name: Optional[str] = None
    code: Optional[str] = None
    description: Optional[str] = None
    parent_department_id: Optional[int] = None
    manager_employee_id: Optional[int] = None
    is_active: Optional[bool] = None

@router.get("/")
async def get_departments(db: Session = Depends(get_db)):
    try:
        depts = db.query(Department).all()
        result = [{"department_id": d.department_id, "name": d.name, "code": d.code, "description": d.description, "level": d.level, "manager_name": d.manager.full_name if d.manager else None} for d in depts]
        return {"success": True, "data": result}
    except Exception as e: raise HTTPException(status_code=500, detail=str(e))

@router.get("/{department_id}")
async def get_department(department_id: int, db: Session = Depends(get_db)):
    try:
        d = db.query(Department).filter(Department.department_id == department_id).first()
        if not d: raise HTTPException(status_code=404, detail="غير موجودة")
        employees = db.query(Employee).filter(Employee.department_id == department_id).all()
        return {"success": True, "data": {
            "department_id": d.department_id, "name": d.name, "code": d.code,
            "description": d.description, "level": d.level,
            "manager_name": d.manager.full_name if d.manager else None,
            "parent_name": d.parent.name if d.parent else None,
            "employees": [{"full_name": e.full_name, "job_title": e.job_title, "is_active": e.is_active} for e in employees]
        }}
    except HTTPException: raise
    except Exception as e: raise HTTPException(status_code=500, detail=str(e))

@router.post("/")
async def create_department(data: DepartmentCreate, db: Session = Depends(get_db)):
    try:
        dept = Department(name=data.name, code=data.code, description=data.description, parent_department_id=data.parent_department_id, manager_employee_id=data.manager_employee_id, level=data.level, is_active=True)
        db.add(dept)
        db.commit()
        db.refresh(dept)
        return {"success": True, "data": {"id": dept.department_id, "name": dept.name}}
    except Exception as e:
        db.rollback()
        raise HTTPException(status_code=500, detail=str(e))

@router.put("/{department_id}")
async def update_department(department_id: int, data: DepartmentUpdate, db: Session = Depends(get_db)):
    try:
        d = db.query(Department).filter(Department.department_id == department_id).first()
        if not d: raise HTTPException(status_code=404, detail="غير موجودة")
        if data.name is not None: d.name = data.name
        if data.code is not None: d.code = data.code
        if data.description is not None: d.description = data.description
        if data.parent_department_id is not None: d.parent_department_id = data.parent_department_id
        if data.manager_employee_id is not None: d.manager_employee_id = data.manager_employee_id
        if data.is_active is not None: d.is_active = data.is_active
        db.commit()
        return {"success": True, "message": "تم التحديث"}
    except HTTPException: raise
    except Exception as e:
        db.rollback()
        raise HTTPException(status_code=500, detail=str(e))

@router.delete("/{department_id}")
async def delete_department(department_id: int, db: Session = Depends(get_db)):
    try:
        d = db.query(Department).filter(Department.department_id == department_id).first()
        if not d: raise HTTPException(status_code=404, detail="غير موجودة")
        db.delete(d)
        db.commit()
        return {"success": True, "message": "تم الحذف"}
    except HTTPException: raise
    except Exception as e:
        db.rollback()
        raise HTTPException(status_code=500, detail=str(e))

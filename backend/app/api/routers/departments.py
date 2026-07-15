from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session
from app.database import get_db
from app.models import Department, Employee
from pydantic import BaseModel
from typing import Optional

router = APIRouter(prefix="/api/departments", tags=["Departments"])

# ============================================================
# Schemas
# ============================================================
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
    level: Optional[int] = None
    is_active: Optional[bool] = None

# ============================================================
# دالة مساعدة
# ============================================================
def format_department(d):
    """تنسيق بيانات الإدارة بشكل آمن"""
    manager_name = None
    try:
        if d.manager:
            manager_name = d.manager.full_name
    except:
        pass

    parent_name = None
    try:
        if d.parent:
            parent_name = d.parent.name
    except:
        pass

    return {
        "department_id": d.department_id,
        "name": d.name or "",
        "code": d.code or "",
        "description": d.description or "",
        "level": d.level or 1,
        "is_active": bool(d.is_active) if d.is_active is not None else True,
        "manager_name": manager_name,
        "manager_employee_id": d.manager_employee_id,
        "parent_department_id": d.parent_department_id,
        "parent_name": parent_name
    }

# ============================================================
# GET - جميع الإدارات
# ============================================================
@router.get("")
async def get_departments(db: Session = Depends(get_db)):
    """جميع الإدارات"""
    try:
        depts = db.query(Department).all()
        result = [format_department(d) for d in depts]
        return {"success": True, "data": result}
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")

# ============================================================
# GET - تفاصيل إدارة
# ============================================================
@router.get("/{department_id}")
async def get_department(department_id: int, db: Session = Depends(get_db)):
    """تفاصيل إدارة مع موظفيها"""
    try:
        d = db.query(Department).filter(Department.department_id == department_id).first()
        if not d:
            raise HTTPException(status_code=404, detail="الإدارة غير موجودة")

        employees = db.query(Employee).filter(Employee.department_id == department_id).all()
        emp_list = [{
            "employee_id": e.employee_id,
            "full_name": e.full_name or "",
            "job_title": e.job_title or "",
            "is_active": bool(e.is_active) if e.is_active is not None else True
        } for e in employees]

        result = format_department(d)
        result["employees"] = emp_list
        return {"success": True, "data": result}
    except HTTPException:
        raise
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")

# ============================================================
# POST - إنشاء إدارة
# ============================================================
@router.post("")
async def create_department(data: DepartmentCreate, db: Session = Depends(get_db)):
    """إنشاء إدارة جديدة"""
    try:
        dept = Department(
            name=data.name,
            code=data.code,
            description=data.description or "",
            parent_department_id=data.parent_department_id,
            manager_employee_id=data.manager_employee_id,
            level=data.level or 1,
            is_active=True
        )
        db.add(dept)
        db.commit()
        db.refresh(dept)
        return {
            "success": True,
            "data": {
                "id": dept.department_id,
                "name": dept.name,
                "message": "تم إنشاء الإدارة بنجاح"
            }
        }
    except Exception as e:
        db.rollback()
        raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")

# ============================================================
# PUT - تحديث إدارة
# ============================================================
@router.put("/{department_id}")
async def update_department(department_id: int, data: DepartmentUpdate, db: Session = Depends(get_db)):
    """تحديث إدارة"""
    try:
        d = db.query(Department).filter(Department.department_id == department_id).first()
        if not d:
            raise HTTPException(status_code=404, detail="الإدارة غير موجودة")

        if data.name is not None:
            d.name = data.name
        if data.code is not None:
            d.code = data.code
        if data.description is not None:
            d.description = data.description
        if data.parent_department_id is not None:
            d.parent_department_id = data.parent_department_id
        if data.manager_employee_id is not None:
            d.manager_employee_id = data.manager_employee_id
        if data.level is not None:
            d.level = data.level
        if data.is_active is not None:
            d.is_active = data.is_active

        db.commit()
        return {"success": True, "message": "تم تحديث الإدارة بنجاح"}
    except HTTPException:
        raise
    except Exception as e:
        db.rollback()
        raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")

# ============================================================
# DELETE - حذف إدارة
# ============================================================
@router.delete("/{department_id}")
async def delete_department(department_id: int, db: Session = Depends(get_db)):
    """حذف إدارة"""
    try:
        d = db.query(Department).filter(Department.department_id == department_id).first()
        if not d:
            raise HTTPException(status_code=404, detail="الإدارة غير موجودة")

        # التحقق من عدم وجود موظفين
        emp_count = db.query(Employee).filter(Employee.department_id == department_id).count()
        if emp_count > 0:
            raise HTTPException(
                status_code=400,
                detail=f"لا يمكن حذف الإدارة - يوجد {emp_count} موظفين مرتبطين بها"
            )

        db.delete(d)
        db.commit()
        return {"success": True, "message": "تم حذف الإدارة بنجاح"}
    except HTTPException:
        raise
    except Exception as e:
        db.rollback()
        raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")

from fastapi import APIRouter, Depends, HTTPException, Query
from sqlalchemy.orm import Session
from sqlalchemy import func
from app.database import get_db
from app.models import OperationalTask, TaskAssignment, Employee, MajorTask
from pydantic import BaseModel
from datetime import date as date_type, datetime
from typing import Optional

router = APIRouter(prefix="/api/tasks", tags=["Tasks"])

STATUS_MAP = {1: "معلق", 2: "قيد التنفيذ", 3: "متأخر", 4: "مكتمل", 5: "ملغي"}
PRIORITY_MAP = {1: "منخفضة", 2: "متوسطة", 3: "عالية", 4: "حرجة"}

def format_task(task, assigned_name=None, department_name=None):
    return {
        "id": task.task_id,
        "task_name": task.title,
        "title": task.title,
        "description": task.description or "",
        "status": task.status_id,
        "status_name": STATUS_MAP.get(task.status_id, "غير معروف"),
        "priority": task.priority_id,
        "priority_name": PRIORITY_MAP.get(task.priority_id, "غير معروف"),
        "progress": getattr(task, 'progress', 0) or 0,
        "due_date": task.end_date.isoformat() if task.end_date else None,
        "end_date": task.end_date.isoformat() if task.end_date else None,
        "start_date": task.start_date.isoformat() if task.start_date else None,
        "assigned_to_name": assigned_name,
        "department_name": department_name
    }

# ============================================================
# Schemas
# ============================================================
class TaskCreate(BaseModel):
    major_task_id: int
    department_id: int
    title: str
    description: Optional[str] = ""
    priority_id: Optional[int] = 2
    start_date: Optional[date_type] = None
    end_date: Optional[date_type] = None
    estimated_hours: Optional[float] = 0
    is_cross_functional: Optional[bool] = False
    assigned_employees: Optional[list[int]] = []

class TaskUpdate(BaseModel):
    major_task_id: Optional[int] = None
    department_id: Optional[int] = None
    title: Optional[str] = None
    description: Optional[str] = None
    status_id: Optional[int] = None
    priority_id: Optional[int] = None
    start_date: Optional[date_type] = None
    end_date: Optional[date_type] = None
    estimated_hours: Optional[float] = None
    actual_hours: Optional[float] = None
    is_cross_functional: Optional[bool] = None

# ============================================================
# GET - جلب المهام
# ============================================================

@router.get("/my-tasks")
async def my_tasks(
    limit: int = Query(20, ge=1, le=100),
    offset: int = Query(0, ge=0),
    db: Session = Depends(get_db)
):
    """مهام المستخدم الحالي"""
    try:
        employee_id = 6  # سيتم استبداله بالتوكن
        tasks = db.query(OperationalTask).join(
            TaskAssignment, OperationalTask.task_id == TaskAssignment.task_id
        ).filter(
            TaskAssignment.employee_id == employee_id
        ).order_by(OperationalTask.end_date.asc()).offset(offset).limit(limit).all()
        result = [format_task(task) for task in tasks]
        return {"success": True, "data": result}
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")

@router.get("/department")
async def department_tasks(
    limit: int = Query(20, ge=1, le=100),
    db: Session = Depends(get_db)
):
    """مهام الإدارة"""
    try:
        department_id = 6
        tasks = db.query(OperationalTask).filter(
            OperationalTask.department_id == department_id
        ).order_by(OperationalTask.end_date.asc()).limit(limit).all()
        result = []
        for task in tasks:
            assignment = db.query(TaskAssignment).filter(TaskAssignment.task_id == task.task_id).first()
            employee_name = None
            if assignment:
                emp = db.query(Employee).filter(Employee.employee_id == assignment.employee_id).first()
                employee_name = emp.full_name if emp else None
            result.append(format_task(task, assigned_name=employee_name))
        return {"success": True, "data": result}
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")

@router.get("/all")
async def all_tasks(limit: int = Query(20, ge=1, le=100), db: Session = Depends(get_db)):
    """جميع المهام"""
    try:
        tasks = db.query(OperationalTask).order_by(OperationalTask.end_date.asc()).limit(limit).all()
        result = []
        for task in tasks:
            dept_name = task.department.name if task.department else None
            result.append(format_task(task, department_name=dept_name))
        return {"success": True, "data": result}
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")

@router.get("/delayed")
async def delayed_tasks(db: Session = Depends(get_db)):
    """المهام المتأخرة"""
    try:
        tasks = db.query(OperationalTask).filter(OperationalTask.status_id == 7).limit(20).all()
        result = [{
            "id": t.task_id, "task_name": t.title,
            "due_date": t.end_date.isoformat() if t.end_date else None,
            "department_name": t.department.name if t.department else None
        } for t in tasks]
        return {"success": True, "data": result}
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")

@router.get("/{task_id}")
async def task_detail(task_id: int, db: Session = Depends(get_db)):
    """تفاصيل مهمة"""
    try:
        task = db.query(OperationalTask).filter(OperationalTask.task_id == task_id).first()
        if not task:
            raise HTTPException(status_code=404, detail="المهمة غير موجودة")
        assignment = db.query(TaskAssignment).filter(TaskAssignment.task_id == task_id).first()
        assigned_name = None
        if assignment:
            emp = db.query(Employee).filter(Employee.employee_id == assignment.employee_id).first()
            assigned_name = emp.full_name if emp else None
        dept_name = task.department.name if task.department else None
        result = format_task(task, assigned_name=assigned_name, department_name=dept_name)
        result["comments"] = []
        return {"success": True, "data": result}
    except HTTPException:
        raise
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")

# ============================================================
# POST - إنشاء مهمة جديدة
# ============================================================

@router.post("/")
async def create_task(data: TaskCreate, db: Session = Depends(get_db)):
    """إنشاء مهمة تشغيلية جديدة"""
    try:
        # إنشاء المهمة
        task = OperationalTask(
            major_task_id=data.major_task_id,
            department_id=data.department_id,
            title=data.title,
            description=data.description,
            priority_id=data.priority_id,
            start_date=data.start_date,
            end_date=data.end_date,
            estimated_hours=data.estimated_hours,
            is_cross_functional=data.is_cross_functional,
            status_id=5,  # معلق افتراضياً
            is_active=True
        )
        db.add(task)
        db.flush()  # للحصول على task_id قبل commit
        
        # تعيين الموظفين
        for emp_id in data.assigned_employees:
            assignment = TaskAssignment(
                task_id=task.task_id,
                employee_id=emp_id,
                assigned_by=1  # يمكن تغييره لاحقاً
            )
            db.add(assignment)
        
        db.commit()
        db.refresh(task)
        
        return {
            "success": True,
            "data": {
                "id": task.task_id,
                "task_name": task.title,
                "message": "تم إنشاء المهمة بنجاح"
            }
        }
    except Exception as e:
        db.rollback()
        raise HTTPException(status_code=500, detail=f"خطأ في إنشاء المهمة: {str(e)}")

# ============================================================
# PUT - تحديث مهمة
# ============================================================

@router.put("/{task_id}")
async def update_task(task_id: int, data: TaskUpdate, db: Session = Depends(get_db)):
    """تحديث مهمة"""
    try:
        task = db.query(OperationalTask).filter(OperationalTask.task_id == task_id).first()
        if not task:
            raise HTTPException(status_code=404, detail="المهمة غير موجودة")
        
        # تحديث الحقول المتوفرة فقط
        if data.major_task_id is not None:
            task.major_task_id = data.major_task_id
        if data.department_id is not None:
            task.department_id = data.department_id
        if data.title is not None:
            task.title = data.title
        if data.description is not None:
            task.description = data.description
        if data.status_id is not None:
            task.status_id = data.status_id
        if data.priority_id is not None:
            task.priority_id = data.priority_id
        if data.start_date is not None:
            task.start_date = data.start_date
        if data.end_date is not None:
            task.end_date = data.end_date
        if data.estimated_hours is not None:
            task.estimated_hours = data.estimated_hours
        if data.actual_hours is not None:
            task.actual_hours = data.actual_hours
        if data.is_cross_functional is not None:
            task.is_cross_functional = data.is_cross_functional
        
        task.updated_at = datetime.now()
        db.commit()
        
        return {"success": True, "message": "تم تحديث المهمة بنجاح"}
    except HTTPException:
        raise
    except Exception as e:
        db.rollback()
        raise HTTPException(status_code=500, detail=f"خطأ في تحديث المهمة: {str(e)}")

# ============================================================
# DELETE - حذف مهمة
# ============================================================

@router.delete("/{task_id}")
async def delete_task(task_id: int, db: Session = Depends(get_db)):
    """حذف مهمة"""
    try:
        task = db.query(OperationalTask).filter(OperationalTask.task_id == task_id).first()
        if not task:
            raise HTTPException(status_code=404, detail="المهمة غير موجودة")
        
        # حذف التعيينات المرتبطة
        db.query(TaskAssignment).filter(TaskAssignment.task_id == task_id).delete()
        # حذف المهمة
        db.delete(task)
        db.commit()
        
        return {"success": True, "message": "تم حذف المهمة بنجاح"}
    except HTTPException:
        raise
    except Exception as e:
        db.rollback()
        raise HTTPException(status_code=500, detail=f"خطأ في حذف المهمة: {str(e)}")

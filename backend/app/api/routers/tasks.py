from fastapi import APIRouter, Depends, HTTPException, Query
from sqlalchemy.orm import Session
from app.database import get_db
from app.models import OperationalTask, TaskAssignment, Employee
from app.core.dependencies import get_current_employee

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


@router.get("/my-tasks")
async def my_tasks(
    limit: int = Query(20, ge=1, le=100),
    offset: int = Query(0, ge=0),
    db: Session = Depends(get_db),
    employee_id: int = Depends(get_current_employee)
):
    """مهام المستخدم الحالي"""
    try:
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
    db: Session = Depends(get_db),
    employee_id: int = Depends(get_current_employee)
):
    """مهام الإدارة"""
    try:
        employee = db.query(Employee).filter(Employee.employee_id == employee_id).first()
        department_id = employee.department_id if employee else 6

        tasks = db.query(OperationalTask).filter(
            OperationalTask.department_id == department_id
        ).order_by(OperationalTask.end_date.asc()).limit(limit).all()

        result = []
        for task in tasks:
            assignment = db.query(TaskAssignment).filter(
                TaskAssignment.task_id == task.task_id
            ).first()
            employee_name = None
            if assignment:
                emp = db.query(Employee).filter(Employee.employee_id == assignment.employee_id).first()
                employee_name = emp.full_name if emp else None
            result.append(format_task(task, assigned_name=employee_name))

        return {"success": True, "data": result}
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")


@router.get("/all")
async def all_tasks(
    limit: int = Query(20, ge=1, le=100),
    db: Session = Depends(get_db)
):
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
        tasks = db.query(OperationalTask).filter(OperationalTask.status_id == 3).limit(20).all()
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

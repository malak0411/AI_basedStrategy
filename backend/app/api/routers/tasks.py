from fastapi import APIRouter, Depends, HTTPException, Query
from sqlalchemy.orm import Session
from sqlalchemy import func
from app.database import get_db
from app.models import DictRoleType, OperationalTask, TaskAssignment, Employee, MajorTask, MajorTaskDepartment, Department, DictStatus, DictPriority
from pydantic import BaseModel
from datetime import date as date_type, datetime
from typing import Optional
from app.core.dependencies import get_current_employee


router = APIRouter(prefix="/api/tasks", tags=["Tasks"])

def get_status_map(db: Session) -> dict:
    statuses = db.query(DictStatus).filter(DictStatus.category == 'task').all()
    return {s.status_id: s.name_ar for s in statuses}

def get_priority_map(db: Session) -> dict:
    priorities = db.query(DictPriority).all()
    return {p.priority_id: p.name_ar for p in priorities}

def format_task(task, db: Session, assigned_name=None, department_name=None):
    status_map = get_status_map(db)
    priority_map = get_priority_map(db)
    return {
        "id": task.task_id,
        "task_name": task.title,
        "title": task.title,
        "description": task.description or "",
        "status": task.status_id,
        "status_name": status_map.get(task.status_id, str(task.status_id)),
        "priority": task.priority_id,
        "priority_name": priority_map.get(task.priority_id, str(task.priority_id)),
        "progress": getattr(task, 'progress', 0) or 0,
        "due_date": task.end_date.isoformat() if task.end_date else None,
        "end_date": task.end_date.isoformat() if task.end_date else None,
        "start_date": task.start_date.isoformat() if task.start_date else None,
        "assigned_to_name": assigned_name,
        "department_name": department_name
    }

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

@router.get("/my-tasks")
async def my_tasks(limit: int = Query(20, ge=1, le=100), offset: int = Query(0, ge=0), db: Session = Depends(get_db)):
    try:
        employee_id = 6
        tasks = db.query(OperationalTask).join(TaskAssignment, OperationalTask.task_id == TaskAssignment.task_id).filter(TaskAssignment.employee_id == employee_id).order_by(OperationalTask.end_date.asc()).offset(offset).limit(limit).all()
        result = [format_task(task, db) for task in tasks]
        return {"success": True, "data": result}
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Error: {str(e)}")

@router.get("/department")
async def department_tasks(limit: int = Query(20, ge=1, le=100), db: Session = Depends(get_db)):
    try:
        department_id = 6
        tasks = db.query(OperationalTask).filter(OperationalTask.department_id == department_id).order_by(OperationalTask.end_date.asc()).limit(limit).all()
        result = []
        for task in tasks:
            assignment = db.query(TaskAssignment).filter(TaskAssignment.task_id == task.task_id).first()
            employee_name = None
            if assignment:
                emp = db.query(Employee).filter(Employee.employee_id == assignment.employee_id).first()
                employee_name = emp.full_name if emp else None
            result.append(format_task(task, db, assigned_name=employee_name))
        return {"success": True, "data": result}
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Error: {str(e)}")

@router.get("/all")
async def all_tasks(limit: int = Query(20, ge=1, le=100), db: Session = Depends(get_db)):
    try:
        tasks = db.query(OperationalTask).order_by(OperationalTask.end_date.asc()).limit(limit).all()
        result = []
        for task in tasks:
            dept_name = task.department.name if task.department else None
            result.append(format_task(task, db, department_name=dept_name))
        return {"success": True, "data": result}
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Error: {str(e)}")

@router.get("/delayed")
async def delayed_tasks(db: Session = Depends(get_db)):
    try:
        delayed_status = db.query(DictStatus).filter(DictStatus.name_ar.like('%متأخر%'), DictStatus.category == 'task').first()
        delayed_id = delayed_status.status_id if delayed_status else 7
        tasks = db.query(OperationalTask).filter(OperationalTask.status_id == delayed_id).limit(20).all()
        result = [{"id": t.task_id, "task_name": t.title, "due_date": t.end_date.isoformat() if t.end_date else None, "department_name": t.department.name if t.department else None} for t in tasks]
        return {"success": True, "data": result}
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Error: {str(e)}")

@router.get("/by-major-task/{major_task_id}")
async def tasks_by_major_task(major_task_id: int, db: Session = Depends(get_db)):
    try:
        tasks = db.query(OperationalTask).filter(OperationalTask.major_task_id == major_task_id).all()
        result = []
        for task in tasks:
            assignment = db.query(TaskAssignment).filter(TaskAssignment.task_id == task.task_id).first()
            assigned_name = None
            if assignment:
                emp = db.query(Employee).filter(Employee.employee_id == assignment.employee_id).first()
                assigned_name = emp.full_name if emp else None
            result.append({
                "id": task.task_id,
                "task_name": task.title,
                "title": task.title,
                "description": task.description or "",
                "status": task.status_id,
                "status_name": get_status_map(db).get(task.status_id, str(task.status_id)),
                "priority": task.priority_id,
                "priority_name": get_priority_map(db).get(task.priority_id, str(task.priority_id)),
                "due_date": task.end_date.isoformat() if task.end_date else None,
                "end_date": task.end_date.isoformat() if task.end_date else None,
                "assigned_to_name": assigned_name
            })
        return {"success": True, "data": result}
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Error: {str(e)}")

@router.get("/department-major-tasks")
async def department_major_tasks(db: Session = Depends(get_db)):
    try:
        tasks = db.query(MajorTask).filter(MajorTask.is_active == True).all()
        result = [{
            "id": t.major_task_id,
            "name": t.name,
            "description": t.description or "",
            "priority_id": t.priority_id,
            "estimated_duration_days": t.estimated_duration_days,
            "initiative_name": t.initiative.name if t.initiative else ""
        } for t in tasks]
        return {"success": True, "data": result}
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Error: {str(e)}")

@router.get("/major-tasks/by-department")
async def major_tasks_by_department(
    db: Session = Depends(get_db),
    employee_id: int = Depends(get_current_employee)
):
    try:
        employee = db.query(Employee).filter(Employee.employee_id == employee_id).first()
        if not employee:
            raise HTTPException(status_code=404, detail="Employee not found")
        
        department_id = employee.department_id
        
        dept_tasks = db.query(MajorTaskDepartment).filter(
            MajorTaskDepartment.department_id == department_id
        ).all()
        
        major_task_ids = list(set(d.major_task_id for d in dept_tasks))
        
        if not major_task_ids:
            return {"success": True, "data": []}
        
        tasks = db.query(MajorTask).filter(
            MajorTask.major_task_id.in_(major_task_ids)
        ).order_by(MajorTask.is_active.desc(), MajorTask.created_at.desc()).all()
        
        result = []
        for t in tasks:
            result.append({
                "id": t.major_task_id,
                "name": t.name,
                "description": t.description or "",
                "priority_id": t.priority_id,
                "estimated_duration_days": t.estimated_duration_days,
                "is_cross_department": t.is_cross_department,
                "is_active": t.is_active,
                "initiative_name": t.initiative.name if t.initiative else ""
            })
        return {"success": True, "data": result}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@router.get("/major-tasks/{task_id}/details")
async def major_task_details(task_id: int, db: Session = Depends(get_db)):
    try:
        t = db.query(MajorTask).filter(MajorTask.major_task_id == task_id).first()
        if not t:
            raise HTTPException(status_code=404, detail="Task not found")
        depts = db.query(MajorTaskDepartment).filter(MajorTaskDepartment.major_task_id == task_id).all()
        dept_list = []
        for d in depts:
            dept = db.query(Department).filter(Department.department_id == d.department_id).first()
            dept_list.append({
                "department_id": d.department_id,
                "department_name": dept.name if dept else "",
                "responsibility_type": d.responsibility_type,
                "notes": d.notes or ""
            })
        return {
            "success": True,
            "data": {
                "id": t.major_task_id,
                "name": t.name,
                "description": t.description or "",
                "priority_id": t.priority_id,
                "estimated_duration_days": t.estimated_duration_days,
                "is_cross_department": t.is_cross_department,
                "is_active": t.is_active,
                "initiative_name": t.initiative.name if t.initiative else "",
                "departments": dept_list
            }
        }
    except HTTPException:
        raise
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@router.get("/{task_id}")
async def task_detail(task_id: int, db: Session = Depends(get_db)):
    try:
        task = db.query(OperationalTask).filter(OperationalTask.task_id == task_id).first()
        if not task:
            raise HTTPException(status_code=404, detail="Task not found")
        assignment = db.query(TaskAssignment).filter(TaskAssignment.task_id == task_id).first()
        assigned_name = None
        if assignment:
            emp = db.query(Employee).filter(Employee.employee_id == assignment.employee_id).first()
            assigned_name = emp.full_name if emp else None
        dept_name = task.department.name if task.department else None
        result = format_task(task, db, assigned_name=assigned_name, department_name=dept_name)
        result["comments"] = []
        return {"success": True, "data": result}
    except HTTPException:
        raise
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Error: {str(e)}")

@router.post("/")
async def create_task(data: TaskCreate, db: Session = Depends(get_db)):
    try:
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
            status_id=16,
            is_active=True
        )
        db.add(task)
        db.flush()
        for emp_id in data.assigned_employees:
            db.add(TaskAssignment(task_id=task.task_id, employee_id=emp_id, assigned_by=1))
        db.commit()
        db.refresh(task)
        return {"success": True, "data": {"id": task.task_id, "task_name": task.title}}
    except Exception as e:
        db.rollback()
        raise HTTPException(status_code=500, detail=f"Error: {str(e)}")


@router.put("/{task_id}")
async def update_task(task_id: int, data: TaskUpdate, db: Session = Depends(get_db)):
    try:
        task = db.query(OperationalTask).filter(OperationalTask.task_id == task_id).first()
        if not task:
            raise HTTPException(status_code=404, detail="Task not found")
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
        return {"success": True, "message": "Task updated"}
    except HTTPException:
        raise
    except Exception as e:
        db.rollback()
        raise HTTPException(status_code=500, detail=f"Error: {str(e)}")

@router.delete("/{task_id}")
async def delete_task(task_id: int, db: Session = Depends(get_db)):
    try:
        task = db.query(OperationalTask).filter(OperationalTask.task_id == task_id).first()
        if not task:
            raise HTTPException(status_code=404, detail="Task not found")
        db.query(TaskAssignment).filter(TaskAssignment.task_id == task_id).delete()
        db.delete(task)
        db.commit()
        return {"success": True, "message": "Task deleted"}
    except HTTPException:
        raise
    except Exception as e:
        db.rollback()
        raise HTTPException(status_code=500, detail=f"Error: {str(e)}")


@router.get("/role-types")
async def get_role_types(db: Session = Depends(get_db)):
    try:
        types = db.query(DictRoleType).all()
        result = [{"role_type_id": t.role_type_id, "name_ar": t.name_ar, "name_en": t.name_en} for t in types]
        return {"success": True, "data": result}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

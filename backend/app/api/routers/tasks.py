from fastapi import APIRouter, Depends, HTTPException, Query
from sqlalchemy.orm import Session
from sqlalchemy import func
from app.database import get_db
from app.models import DictRoleType, OperationalTask, TaskAssignment, Employee, MajorTask, MajorTaskDepartment, Department, DictStatus, DictPriority, TaskComment, TaskDependency , TaskProgressLog 
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

def check_dependency_rules(task, new_status, db):
    dependencies = db.query(TaskDependency).filter(
        TaskDependency.task_id == task.task_id
    ).all()
    
    if not dependencies:
        return None
    
    dep_task_ids = [dep.depends_on_task_id for dep in dependencies]
    dep_tasks = db.query(OperationalTask).filter(
        OperationalTask.task_id.in_(dep_task_ids),
        OperationalTask.is_active == True
    ).all()
    dep_status_map = {t.task_id: t.status_id for t in dep_tasks}
    dep_title_map = {t.task_id: t.title for t in dep_tasks}
    
    for dep in dependencies:
        dep_status = dep_status_map.get(dep.depends_on_task_id)
        dep_title = dep_title_map.get(dep.depends_on_task_id, 'مهمة غير موجودة')
        
        if dep_status is None:
            return f"المهمة المعتمد عليها '{dep_title}' غير موجودة أو غير نشطة"
        
        dep_type = dep.dependency_type
        
        if dep_type == "FS":
            if new_status in [6, 8] and dep_status != 19:
                return f"لا يمكن بدء المهمة لأنها تعتمد على انتهاء مهمة '{dep_title}'"
        elif dep_type == "SS":
            if new_status in [6, 8] and dep_status not in [6, 8, 19]:
                return f"لا يمكن بدء المهمة لأن المهمة المعتمد عليها '{dep_title}' لم تبدأ بعد"
        elif dep_type == "FF":
            if new_status == 19 and dep_status != 19:
                return f"لا يمكن إنهاء المهمة لأن المهمة المعتمد عليها '{dep_title}' لم تنته بعد"
        elif dep_type == "SF":
            if new_status == 19 and dep_status not in [6, 8, 19]:
                return f"لا يمكن إنهاء المهمة لأن المهمة المعتمد عليها '{dep_title}' لم تبدأ بعد"
    
    return None

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
        tasks = db.query(OperationalTask).filter(
            OperationalTask.major_task_id == major_task_id,
            OperationalTask.is_active == True
        ).all()
        result = []
        for task in tasks:
            assignment = db.query(TaskAssignment).filter(TaskAssignment.task_id == task.task_id).first()
            assigned_name = None
            if assignment:
                emp = db.query(Employee).filter(Employee.employee_id == assignment.employee_id).first()
                assigned_name = emp.full_name if emp else None
            result.append({
                "id": task.task_id,
                "task_id": task.task_id,
                "task_name": task.title,
                "title": task.title,
                "description": task.description or "",
                "department_id": task.department_id,   # 🔥 تمت إضافة هذا السطر
                "status": task.status_id,
                "status_id": task.status_id,
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
        
        initiative_name = ""
        initiative_start_date = None
        initiative_end_date = None
        
        if t.initiative:
            initiative_name = t.initiative.name
            initiative_start_date = t.initiative.start_date.isoformat() if t.initiative.start_date else None
            initiative_end_date = t.initiative.end_date.isoformat() if t.initiative.end_date else None
        
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
                "initiative_id": t.initiative_id,
                "initiative_name": initiative_name,
                "initiative_start_date": initiative_start_date,
                "initiative_end_date": initiative_end_date,
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

class StatusUpdateRequest(BaseModel):
    status_id: int
    comment: Optional[str] = ""

@router.get("/by-department/{department_id}")
async def tasks_by_department(department_id: int, db: Session = Depends(get_db)):
    try:
        tasks = db.query(OperationalTask).filter(
            OperationalTask.department_id == department_id,
            OperationalTask.is_active == True
        ).order_by(OperationalTask.created_at.desc()).all()
        
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
                "due_date": task.end_date.isoformat() if task.end_date else None,
                "end_date": task.end_date.isoformat() if task.end_date else None,
                "start_date": task.start_date.isoformat() if task.start_date else None,
                "assigned_to_name": assigned_name,
                "department_id": task.department_id
            })
        
        return {"success": True, "data": result}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@router.get("/{task_id}/progress-logs")
async def task_progress_logs(task_id: int, db: Session = Depends(get_db)):
    try:
        logs = db.query(TaskProgressLog).filter(TaskProgressLog.task_id == task_id).order_by(TaskProgressLog.log_time.desc()).all()
        result = []
        for log in logs:
            emp = db.query(Employee).filter(Employee.employee_id == log.employee_id).first()
            result.append({
                "id": log.log_id,
                "employee_name": emp.full_name if emp else "غير معروف",
                "progress_percent": log.progress_percent,
                "status_old": log.status_old,
                "status_new": log.status_new,
                "notes": log.notes or "",
                "log_time": log.log_time.isoformat() if log.log_time else None
            })
        return {"success": True, "data": result}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))


@router.get("/major-tasks/by-department/{department_id}")
async def major_tasks_by_department_id(department_id: int, db: Session = Depends(get_db)):
    try:
        dept_tasks = db.query(MajorTaskDepartment).filter(
            MajorTaskDepartment.department_id == department_id
        ).all()
        
        major_task_ids = list(set(d.major_task_id for d in dept_tasks))
        if not major_task_ids:
            return {"success": True, "data": []}
        
        tasks = db.query(MajorTask).filter(MajorTask.major_task_id.in_(major_task_ids)).all()
        
        result = []
        for t in tasks:
            dept_role = next((d.responsibility_type for d in dept_tasks if d.major_task_id == t.major_task_id), 'SUPPORT')
            result.append({
                "id": t.major_task_id,
                "name": t.name,
                "description": t.description or "",
                "priority_id": t.priority_id,
                "estimated_duration_days": t.estimated_duration_days,
                "is_cross_department": t.is_cross_department,
                "is_active": t.is_active,
                "responsibility_type": dept_role,
            })
        
        return {"success": True, "data": result}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@router.get("/{task_id}")
async def get_task(
    task_id: int,
    current_user: Employee = Depends(get_current_employee),
    db: Session = Depends(get_db)
):
    task = db.query(OperationalTask).filter(
        OperationalTask.task_id == task_id,
        OperationalTask.is_active == True
    ).first()
    
    if not task:
        raise HTTPException(404, "المهمة غير موجودة")
    
    status = db.query(DictStatus).filter(
        DictStatus.status_id == task.status_id
    ).first()
    
    priority = db.query(DictPriority).filter(
        DictPriority.priority_id == task.priority_id
    ).first()
    
    result = {
        "task_id": task.task_id,
        "major_task_id": task.major_task_id,
        "department_id": task.department_id,
        "title": task.title,
        "description": task.description,
        "status_id": task.status_id,
        "status_name": status.name_ar if status else None,
        "priority_id": task.priority_id,
        "priority_name": priority.name_ar if priority else None,
        "start_date": task.start_date.isoformat() if task.start_date else None,
        "end_date": task.end_date.isoformat() if task.end_date else None,
        "estimated_hours": task.estimated_hours,
        "actual_hours": task.actual_hours,
        "is_cross_functional": task.is_cross_functional,
        "created_at": task.created_at.isoformat() if task.created_at else None
    }
    
    db.commit()
    return {"success": True, "data": result}

@router.get("/{task_id}/assignments")
async def get_task_assignments(
    task_id: int,
    current_user: Employee = Depends(get_current_employee),
    db: Session = Depends(get_db)
):
    assignments = db.query(TaskAssignment).filter(
        TaskAssignment.task_id == task_id,
        TaskAssignment.is_active == True
    ).all()
    
    result = []
    for a in assignments:
        employee = db.query(Employee).filter(
            Employee.employee_id == a.employee_id
        ).first()
        
        role_type = db.query(DictRoleType).filter(
            DictRoleType.role_type_id == a.role_type_id
        ).first()
        
        result.append({
            "assignment_id": a.assignment_id,
            "task_id": a.task_id,
            "employee_id": a.employee_id,
            "role_type_id": a.role_type_id,
            "role_type_name": role_type.name_ar if role_type else None,
            "acceptance_status": a.acceptance_status,
            "rejection_reason": a.rejection_reason,
            "estimated_hours": a.estimated_hours,
            "assigned_by": a.assigned_by,
            "assigned_at": a.assigned_at.isoformat() if a.assigned_at else None,
            "is_active": a.is_active,
            "employee": {
                "employee_id": employee.employee_id,
                "full_name": employee.full_name,
                "job_title": employee.job_title
            } if employee else None
        })
    
    db.commit()
    return {"success": True, "data": result}

@router.post("/{task_id}/assign")
async def assign_employee_to_task(
    task_id: int,
    data: dict,
    current_user: Employee = Depends(get_current_employee),
    db: Session = Depends(get_db)
):
    employee_id = data.get("employee_id")
    role_type_id = data.get("role_type_id", 1)
    estimated_hours = data.get("estimated_hours", 40)
    
    if not employee_id:
        raise HTTPException(400, "معرف الموظف مطلوب")
    
    task = db.query(OperationalTask).filter(
        OperationalTask.task_id == task_id,
        OperationalTask.is_active == True
    ).first()
    
    if not task:
        raise HTTPException(404, "المهمة غير موجودة")
    
    employee = db.query(Employee).filter(
        Employee.employee_id == employee_id,
        Employee.is_active == True
    ).first()
    
    if not employee:
        raise HTTPException(404, "الموظف غير موجود")
    
    # منع وجود أكثر من مسؤول نهائي
    if role_type_id == 2:
        existing_responsible = db.query(TaskAssignment).filter(
            TaskAssignment.task_id == task_id,
            TaskAssignment.role_type_id == 2,
            TaskAssignment.is_active == True
        ).first()
        if existing_responsible:
            raise HTTPException(400, "يوجد مسؤول نهائي لهذه المهمة بالفعل")
    
    # التحقق من وجود توزيع نشط لنفس الموظف والمهمة
    existing = db.query(TaskAssignment).filter(
        TaskAssignment.task_id == task_id,
        TaskAssignment.employee_id == employee_id,
        TaskAssignment.is_active == True
    ).first()
    
    if existing:
        existing.role_type_id = role_type_id
        existing.estimated_hours = estimated_hours
        existing.assigned_by = current_user
        existing.assigned_at = datetime.now()
        existing.acceptance_status = "pending"
        existing.rejection_reason = None
        db.commit()
        db.refresh(existing)
        return {
            "success": True,
            "message": "تم تحديث توزيع الموظف",
            "data": {"assignment_id": existing.assignment_id}
        }
    
    # التحقق من وجود توزيع غير نشط لنفس الموظف والمهمة (إعادة تفعيل)
    inactive = db.query(TaskAssignment).filter(
        TaskAssignment.task_id == task_id,
        TaskAssignment.employee_id == employee_id,
        TaskAssignment.is_active == False
    ).first()
    
    if inactive:
        inactive.is_active = True
        inactive.role_type_id = role_type_id
        inactive.estimated_hours = estimated_hours
        inactive.assigned_by = current_user
        inactive.assigned_at = datetime.now()
        inactive.acceptance_status = "pending"
        inactive.rejection_reason = None
        db.commit()
        db.refresh(inactive)
        return {
            "success": True,
            "message": "تم إعادة تفعيل توزيع الموظف",
            "data": {"assignment_id": inactive.assignment_id}
        }
    
    # إنشاء توزيع جديد
    assignment = TaskAssignment(
        task_id=task_id,
        employee_id=employee_id,
        role_type_id=role_type_id,
        estimated_hours=estimated_hours,
        acceptance_status="pending",
        assigned_by=current_user,
        assigned_at=datetime.now(),
        is_active=True
    )
    
    db.add(assignment)
    db.commit()
    db.refresh(assignment)
    
    return {
        "success": True,
        "message": "تم التوزيع بنجاح",
        "data": {"assignment_id": assignment.assignment_id}
    }

@router.delete("/assignments/{assignment_id}")
async def remove_task_assignment(
    assignment_id: int,
    current_user: Employee = Depends(get_current_employee),
    db: Session = Depends(get_db)
):
    assignment = db.query(TaskAssignment).filter(
        TaskAssignment.assignment_id == assignment_id
    ).first()
    
    if not assignment:
        raise HTTPException(404, "التوزيع غير موجود")
    
    assignment.is_active = False
    db.commit()
    
    return {"success": True, "message": "تم إلغاء التوزيع"}


@router.put("/assignments/{assignment_id}/hours")
async def update_assignment_hours(
    assignment_id: int,
    data: dict,
    current_user: Employee = Depends(get_current_employee),
    db: Session = Depends(get_db)
):
    estimated_hours = data.get("estimated_hours")
    
    if not estimated_hours:
        raise HTTPException(400, "الساعات المقدرة مطلوبة")
    
    assignment = db.query(TaskAssignment).filter(
        TaskAssignment.assignment_id == assignment_id,
        TaskAssignment.is_active == True
    ).first()
    
    if not assignment:
        raise HTTPException(404, "التوزيع غير موجود")
    
    assignment.estimated_hours = estimated_hours
    db.commit()
    
    return {"success": True, "message": "تم تحديث الساعات المقدرة"}

@router.put("/{task_id}/update-status")
async def update_task_status(
    task_id: int,
    data: dict,
    current_user: Employee = Depends(get_current_employee),
    db: Session = Depends(get_db)
):
    status_id = data.get("status_id")
    comment = data.get("comment", "")
    
    if not status_id:
        raise HTTPException(400, "الحالة مطلوبة")
    
    task = db.query(OperationalTask).filter(
        OperationalTask.task_id == task_id,
        OperationalTask.is_active == True
    ).first()
    
    if not task:
        raise HTTPException(404, "المهمة غير موجودة")
    
    old_status = task.status_id
    
    can_move, error = can_move_task(task.status_id, status_id)
    if not can_move:
        raise HTTPException(400, error)
    
    dep_error = check_dependency_rules(task, status_id, db)
    if dep_error:
        raise HTTPException(400, dep_error)
    
    if status_id in [5, 8, 19, 20] and not comment:
        raise HTTPException(400, "التعليق مطلوب لهذه الحالة")
    
    if old_status == 5 and status_id == 6 and not comment:
        raise HTTPException(400, "التعليق مطلوب لإعادة المهمة للعمل")
    
    task.status_id = status_id
    
    existing_log = db.query(TaskProgressLog).filter(
        TaskProgressLog.task_id == task.task_id,
        TaskProgressLog.status_old == old_status,
        TaskProgressLog.status_new == status_id
    ).first()
    
    if not existing_log:
        progress_log = TaskProgressLog(
            task_id=task.task_id,
            employee_id=current_user.employee_id,
            progress_percent=0,
            status_old=old_status,
            status_new=status_id,
            notes=comment
        )
        db.add(progress_log)
    
    db.commit()
    
    return {
        "success": True,
        "message": "تم تحديث حالة المهمة بنجاح",
        "data": {
            "task_id": task.task_id,
            "old_status": old_status,
            "new_status": status_id
        }
    }



def can_move_task(from_status, to_status):
    if from_status == 16:
        return False, "المهام غير الموزعة لا يمكن نقلها"
    
    if from_status in [19, 20]:
        return False, "لا يمكن نقل المهام المقبولة أو المرفوضة"
    
    rules = {
        26: [6],
        6: [5, 8],
        5: [6],
        8: [6, 19, 20],
        19: [],
        20: []
    }
    
    allowed = rules.get(from_status, [])
    if to_status not in allowed:
        return False, "لا يمكن نقل المهمة إلى هذه الحالة"
    
    return True, None




@router.post("/{task_id}/finalize")
async def finalize_task_assignments(
    task_id: int,
    current_user: Employee = Depends(get_current_employee),
    db: Session = Depends(get_db)
):
    task = db.query(OperationalTask).filter(
        OperationalTask.task_id == task_id,
        OperationalTask.is_active == True
    ).first()
    
    if not task:
        raise HTTPException(404, "المهمة غير موجودة")
    
    pending_assignments = db.query(TaskAssignment).filter(
        TaskAssignment.task_id == task_id,
        TaskAssignment.is_active == True,
        TaskAssignment.acceptance_status == "pending"
    ).count()
    
    if pending_assignments > 0:
        raise HTTPException(400, f"يوجد {pending_assignments} توزيع(ات) في حالة انتظار")
    
    responsible_count = db.query(TaskAssignment).filter(
        TaskAssignment.task_id == task_id,
        TaskAssignment.is_active == True,
        TaskAssignment.role_type_id == 2
    ).count()
    
    if responsible_count == 0:
        raise HTTPException(400, "يجب تعيين مسؤول نهائي للمهمة قبل إنهاء التوزيع")
    
    old_status = task.status_id
    new_status = 26
    task.status_id = new_status
    
    existing_log = db.query(TaskProgressLog).filter(
        TaskProgressLog.task_id == task.task_id,
        TaskProgressLog.status_old == old_status,
        TaskProgressLog.status_new == new_status
    ).first()
    
    if not existing_log:
        progress_log = TaskProgressLog(
            task_id=task.task_id,
            employee_id=current_user,
            progress_percent=100,
            status_old=old_status,
            status_new=new_status,
            notes="تم إنهاء توزيع المهمة وجاهزة للتنفيذ"
        )
        db.add(progress_log)
    
    db.commit()
    
    return {"success": True, "message": "تم إنهاء توزيع المهمة بنجاح"}

@router.get("/by-major-task/{major_task_id}")
async def get_tasks_by_major_task(
    major_task_id: int,
    current_user: Employee = Depends(get_current_employee),
    db: Session = Depends(get_db)
):
    tasks = db.query(OperationalTask).filter(
        OperationalTask.major_task_id == major_task_id,
        OperationalTask.is_active == True
    ).all()
    
    result = []
    for task in tasks:
        status = db.query(DictStatus).filter(
            DictStatus.status_id == task.status_id
        ).first()
        
        priority = db.query(DictPriority).filter(
            DictPriority.priority_id == task.priority_id
        ).first()
        
        assigned_to_name = None
        if task.assigned_to:
            employee = db.query(Employee).filter(
                Employee.employee_id == task.assigned_to
            ).first()
            if employee:
                assigned_to_name = employee.full_name
        
        result.append({
            "task_id": task.task_id,
            "id": task.task_id,
            "major_task_id": task.major_task_id,
            "title": task.title,
            "description": task.description,
            "status_id": task.status_id,
            "status_name": status.name_ar if status else None,
            "priority": priority.code if priority else "medium",
            "priority_name": priority.name_ar if priority else None,
            "start_date": task.start_date.isoformat() if task.start_date else None,
            "end_date": task.end_date.isoformat() if task.end_date else None,
            "estimated_hours": task.estimated_hours,
            "assigned_to_name": assigned_to_name
        })
    
    db.commit()
    return {"success": True, "data": result}

class DependencyCreate(BaseModel):
    task_id: int
    depends_on_task_id: int
    dependency_type: str = "FS"
    lag_days: int = 0

class DependencyUpdate(BaseModel):
    dependency_type: Optional[str] = None
    lag_days: Optional[int] = None

@router.get("/major/{major_task_id}/dependencies")
async def get_major_task_dependencies(
    major_task_id: int,
    current_user: Employee = Depends(get_current_employee),
    db: Session = Depends(get_db)
):
    tasks = db.query(OperationalTask).filter(
        OperationalTask.major_task_id == major_task_id,
        OperationalTask.is_active == True
    ).all()

    if not tasks:
        return {"success": True, "data": []}

    task_ids = [t.task_id for t in tasks]

    dependencies = db.query(TaskDependency).filter(
        TaskDependency.task_id.in_(task_ids)
    ).all()

    dependency_map = {}
    reverse_dependency_map = {}

    for dep in dependencies:
        depends_on_task = db.query(OperationalTask).filter(
            OperationalTask.task_id == dep.depends_on_task_id
        ).first()

        if dep.task_id not in dependency_map:
            dependency_map[dep.task_id] = []
        dependency_map[dep.task_id].append({
            "dependency_id": dep.dependency_id,
            "depends_on_task_id": dep.depends_on_task_id,
            "depends_on_task_title": depends_on_task.title if depends_on_task else None,
            "dependency_type": dep.dependency_type,
            "lag_days": dep.lag_days,
            "created_at": dep.created_at.isoformat() if dep.created_at else None
        })

        if dep.depends_on_task_id not in reverse_dependency_map:
            reverse_dependency_map[dep.depends_on_task_id] = []
        reverse_dependency_map[dep.depends_on_task_id].append({
            "dependency_id": dep.dependency_id,
            "task_id": dep.task_id,
            "dependency_type": dep.dependency_type,
            "lag_days": dep.lag_days
        })

    result = []
    for task in tasks:
        status = db.query(DictStatus).filter(
            DictStatus.status_id == task.status_id
        ).first()

        priority = db.query(DictPriority).filter(
            DictPriority.priority_id == task.priority_id
        ).first()

        # 🔥 جلب الموظف المسند من جدول task_assignments
        assigned_to_name = None
        assignment = db.query(TaskAssignment).filter(
            TaskAssignment.task_id == task.task_id,
            TaskAssignment.is_active == True
        ).first()
        
        if assignment:
            emp = db.query(Employee).filter(
                Employee.employee_id == assignment.employee_id
            ).first()
            if emp:
                assigned_to_name = emp.full_name

        result.append({
            "task_id": task.task_id,
            "title": task.title,
            "description": task.description or "",
            "status_id": task.status_id,
            "status_name": status.name_ar if status else None,
            "priority_id": task.priority_id,
            "priority_name": priority.name_ar if priority else None,
            "assigned_to_name": assigned_to_name,
            "department_id": task.department_id,
            "end_date": task.end_date.isoformat() if task.end_date else None,
            "dependencies": dependency_map.get(task.task_id, []),
            "depended_by": reverse_dependency_map.get(task.task_id, [])
        })

    db.commit()
    return {"success": True, "data": result}

@router.post("/dependencies")
async def create_dependency(
    data: DependencyCreate,
    current_user: Employee = Depends(get_current_employee),
    db: Session = Depends(get_db)
):
    if data.task_id == data.depends_on_task_id:
        raise HTTPException(400, "لا يمكن للمهمة أن تعتمد على نفسها")

    task = db.query(OperationalTask).filter(
        OperationalTask.task_id == data.task_id,
        OperationalTask.is_active == True
    ).first()
    if not task:
        raise HTTPException(404, "المهمة غير موجودة")

    depends_on = db.query(OperationalTask).filter(
        OperationalTask.task_id == data.depends_on_task_id,
        OperationalTask.is_active == True
    ).first()
    if not depends_on:
        raise HTTPException(404, "المهمة المعتمد عليها غير موجودة")

    existing = db.query(TaskDependency).filter(
        TaskDependency.task_id == data.task_id,
        TaskDependency.depends_on_task_id == data.depends_on_task_id
    ).first()
    if existing:
        raise HTTPException(400, "هذه التبعية موجودة بالفعل")

    if _would_create_cycle(data.task_id, data.depends_on_task_id, db):
        raise HTTPException(400, "هذه التبعية ستؤدي إلى دورة مغلقة (loop)")

    dependency = TaskDependency(
        task_id=data.task_id,
        depends_on_task_id=data.depends_on_task_id,
        dependency_type=data.dependency_type,
        lag_days=data.lag_days or 0,
        created_at=datetime.now()
    )

    db.add(dependency)
    db.commit()
    db.refresh(dependency)

    return {
        "success": True,
        "message": "تم إضافة التبعية بنجاح",
        "data": {
            "dependency_id": dependency.dependency_id,
            "task_id": dependency.task_id,
            "depends_on_task_id": dependency.depends_on_task_id,
            "dependency_type": dependency.dependency_type,
            "lag_days": dependency.lag_days
        }
    }

@router.put("/dependencies/{dependency_id}")
async def update_dependency(
    dependency_id: int,
    data: DependencyUpdate,
    current_user: Employee = Depends(get_current_employee),
    db: Session = Depends(get_db)
):
    dependency = db.query(TaskDependency).filter(
        TaskDependency.dependency_id == dependency_id
    ).first()

    if not dependency:
        raise HTTPException(404, "التبعية غير موجودة")

    if data.dependency_type is not None:
        dependency.dependency_type = data.dependency_type
    if data.lag_days is not None:
        dependency.lag_days = data.lag_days

    db.commit()
    db.refresh(dependency)

    return {
        "success": True,
        "message": "تم تحديث التبعية بنجاح",
        "data": {
            "dependency_id": dependency.dependency_id,
            "task_id": dependency.task_id,
            "depends_on_task_id": dependency.depends_on_task_id,
            "dependency_type": dependency.dependency_type,
            "lag_days": dependency.lag_days
        }
    }

@router.delete("/dependencies/{dependency_id}")
async def delete_dependency(
    dependency_id: int,
    current_user: Employee = Depends(get_current_employee),
    db: Session = Depends(get_db)
):
    dependency = db.query(TaskDependency).filter(
        TaskDependency.dependency_id == dependency_id
    ).first()

    if not dependency:
        raise HTTPException(404, "التبعية غير موجودة")

    db.delete(dependency)
    db.commit()

    return {"success": True, "message": "تم حذف التبعية بنجاح"}

@router.post("/dependencies/check-cycle")
async def check_cycle(
    data: dict,
    current_user: Employee = Depends(get_current_employee),
    db: Session = Depends(get_db)
):
    task_id = data.get("task_id")
    depends_on_task_id = data.get("depends_on_task_id")

    if not task_id or not depends_on_task_id:
        raise HTTPException(400, "معرفا المهمة مطلوبان")

    if task_id == depends_on_task_id:
        return {"success": False, "message": "لا يمكن للمهمة أن تعتمد على نفسها"}

    would_cycle = _would_create_cycle(task_id, depends_on_task_id, db)

    return {
        "success": True,
        "would_create_cycle": would_cycle,
        "message": "سيتم إنشاء دورة مغلقة" if would_cycle else "لا يوجد دورة مغلقة"
    }

def _would_create_cycle(task_id, depends_on_task_id, db):
    visited = set()
    stack = [depends_on_task_id]

    while stack:
        current = stack.pop()
        if current in visited:
            continue
        visited.add(current)

        if current == task_id:
            return True

        deps = db.query(TaskDependency).filter(
            TaskDependency.task_id == current
        ).all()

        for dep in deps:
            if dep.depends_on_task_id not in visited:
                stack.append(dep.depends_on_task_id)

    return False

@router.get("/{task_id}/dependencies")
async def get_task_dependencies(
    task_id: int,
    current_user: Employee = Depends(get_current_employee),
    db: Session = Depends(get_db)
):
    deps = db.query(TaskDependency).filter(
        TaskDependency.task_id == task_id
    ).all()

    result = []
    for dep in deps:
        task = db.query(OperationalTask).filter(
            OperationalTask.task_id == dep.depends_on_task_id
        ).first()
        result.append({
            "dependency_id": dep.dependency_id,
            "depends_on_task_id": dep.depends_on_task_id,
            "depends_on_title": task.title if task else None,
            "dependency_type": dep.dependency_type,
            "lag_days": dep.lag_days
        })

    db.commit()
    return {"success": True, "data": result}

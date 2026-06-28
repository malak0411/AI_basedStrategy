from fastapi import APIRouter, Depends, HTTPException, status, Query
from sqlalchemy.orm import Session, joinedload
from sqlalchemy import func, and_, or_
from typing import List, Optional
from datetime import datetime, date
from decimal import Decimal

from ...database import get_db
from ...models import (
    Employee, Department, OperationalTask, MajorTask, Initiative,
    TaskAssignment, TaskProgressLog, TaskDependency, LocationLog,
    DictStatus, DictPriority, DictRoleType
)
from ...schemas import (
    OperationalTaskCreate, OperationalTaskUpdate, OperationalTaskResponse,
    MajorTaskCreate, MajorTaskUpdate, MajorTaskResponse,
    TaskAssignmentCreate, TaskAssignmentUpdate, TaskAssignmentResponse,
    TaskProgressUpdate, TaskProgressLogResponse
)
from ...core.dependencies import get_current_user, has_role

router = APIRouter(prefix="/api/tasks", tags=["المهام"])

# ================================================================
# 1. Major Tasks APIs (المهام الكبرى)
# ================================================================

@router.get("/major", response_model=List[MajorTaskResponse])
async def get_major_tasks(
    initiative_id: Optional[int] = None,
    search: Optional[str] = None,
    skip: int = 0,
    limit: int = 100,
    db: Session = Depends(get_db),
    current_user: Employee = Depends(get_current_user)
):
    """جلب جميع المهام الكبرى"""
    query = db.query(MajorTask).filter(MajorTask.is_active == True)
    
    if initiative_id:
        query = query.filter(MajorTask.initiative_id == initiative_id)
    if search:
        query = query.filter(MajorTask.name.ilike(f"%{search}%"))
    
    tasks = query.offset(skip).limit(limit).all()
    
    result = []
    for task in tasks:
        initiative = db.query(Initiative).filter(Initiative.initiative_id == task.initiative_id).first()
        priority = db.query(DictPriority).filter(DictPriority.priority_id == task.priority_id).first()
        operational_count = db.query(OperationalTask).filter(
            OperationalTask.major_task_id == task.major_task_id,
            OperationalTask.is_active == True
        ).count()
        
        result.append(MajorTaskResponse(
            major_task_id=task.major_task_id,
            initiative_id=task.initiative_id,
            initiative_name=initiative.name if initiative else None,
            name=task.name,
            description=task.description,
            priority_id=task.priority_id,
            priority_name=priority.name_ar if priority else None,
            estimated_duration_days=task.estimated_duration_days,
            is_cross_department=task.is_cross_department or False,
            is_active=task.is_active,
            created_at=task.created_at,
            updated_at=task.updated_at,
            operational_tasks_count=operational_count
        ))
    return result

@router.post("/major", response_model=MajorTaskResponse, status_code=status.HTTP_201_CREATED)
async def create_major_task(
    task_data: MajorTaskCreate,
    db: Session = Depends(get_db),
    current_user: Employee = Depends(has_role(["وزير / قيادة العليا", "وكيل وزارة", "مدير عام", "Super Admin"]))
):
    """إنشاء مهمة كبرى جديدة"""
    initiative = db.query(Initiative).filter(Initiative.initiative_id == task_data.initiative_id).first()
    if not initiative:
        raise HTTPException(status_code=404, detail="المبادرة غير موجودة")
    
    new_task = MajorTask(
        initiative_id=task_data.initiative_id,
        name=task_data.name,
        description=task_data.description,
        priority_id=task_data.priority_id,
        estimated_duration_days=task_data.estimated_duration_days,
        is_cross_department=task_data.is_cross_department or False,
        created_by=current_user.employee_id,
        is_active=True
    )
    db.add(new_task)
    db.commit()
    db.refresh(new_task)
    
    return MajorTaskResponse(
        major_task_id=new_task.major_task_id,
        initiative_id=new_task.initiative_id,
        initiative_name=initiative.name,
        name=new_task.name,
        description=new_task.description,
        priority_id=new_task.priority_id,
        estimated_duration_days=new_task.estimated_duration_days,
        is_cross_department=new_task.is_cross_department or False,
        is_active=new_task.is_active,
        created_at=new_task.created_at,
        updated_at=new_task.updated_at,
        operational_tasks_count=0
    )

@router.put("/major/{task_id}", response_model=MajorTaskResponse)
async def update_major_task(
    task_id: int,
    task_data: MajorTaskUpdate,
    db: Session = Depends(get_db),
    current_user: Employee = Depends(has_role(["وزير / قيادة العليا", "وكيل وزارة", "مدير عام", "Super Admin"]))
):
    """تحديث مهمة كبرى"""
    task = db.query(MajorTask).filter(MajorTask.major_task_id == task_id).first()
    if not task:
        raise HTTPException(status_code=404, detail="المهمة غير موجودة")
    
    if task_data.initiative_id is not None:
        initiative = db.query(Initiative).filter(Initiative.initiative_id == task_data.initiative_id).first()
        if not initiative:
            raise HTTPException(status_code=404, detail="المبادرة غير موجودة")
        task.initiative_id = task_data.initiative_id
    
    if task_data.name is not None:
        task.name = task_data.name
    if task_data.description is not None:
        task.description = task_data.description
    if task_data.priority_id is not None:
        task.priority_id = task_data.priority_id
    if task_data.estimated_duration_days is not None:
        task.estimated_duration_days = task_data.estimated_duration_days
    if task_data.is_cross_department is not None:
        task.is_cross_department = task_data.is_cross_department
    if task_data.is_active is not None:
        task.is_active = task_data.is_active
    
    task.updated_at = datetime.now()
    db.commit()
    db.refresh(task)
    
    initiative = db.query(Initiative).filter(Initiative.initiative_id == task.initiative_id).first()
    priority = db.query(DictPriority).filter(DictPriority.priority_id == task.priority_id).first()
    operational_count = db.query(OperationalTask).filter(
        OperationalTask.major_task_id == task.major_task_id,
        OperationalTask.is_active == True
    ).count()
    
    return MajorTaskResponse(
        major_task_id=task.major_task_id,
        initiative_id=task.initiative_id,
        initiative_name=initiative.name if initiative else None,
        name=task.name,
        description=task.description,
        priority_id=task.priority_id,
        priority_name=priority.name_ar if priority else None,
        estimated_duration_days=task.estimated_duration_days,
        is_cross_department=task.is_cross_department or False,
        is_active=task.is_active,
        created_at=task.created_at,
        updated_at=task.updated_at,
        operational_tasks_count=operational_count
    )

@router.delete("/major/{task_id}")
async def delete_major_task(
    task_id: int,
    db: Session = Depends(get_db),
    current_user: Employee = Depends(has_role(["وزير / قيادة العليا", "Super Admin"]))
):
    """حذف مهمة كبرى (إذا لم تكن مرتبطة بمهام تشغيلية)"""
    task = db.query(MajorTask).filter(MajorTask.major_task_id == task_id).first()
    if not task:
        raise HTTPException(status_code=404, detail="المهمة غير موجودة")
    
    operational_count = db.query(OperationalTask).filter(OperationalTask.major_task_id == task_id).count()
    if operational_count > 0:
        raise HTTPException(status_code=400, detail=f"لا يمكن الحذف: مرتبطة بـ {operational_count} مهام تشغيلية")
    
    db.delete(task)
    db.commit()
    return {"message": "تم حذف المهمة الكبرى بنجاح"}

# ================================================================
# 2. Operational Tasks APIs (المهام التشغيلية)
# ================================================================

@router.get("/", response_model=List[OperationalTaskResponse])
async def get_operational_tasks(
    major_task_id: Optional[int] = None,
    department_id: Optional[int] = None,
    status_id: Optional[int] = None,
    assigned_to_me: Optional[bool] = False,
    search: Optional[str] = None,
    skip: int = 0,
    limit: int = 100,
    db: Session = Depends(get_db),
    current_user: Employee = Depends(get_current_user)
):
    """جلب المهام التشغيلية مع إمكانية التصفية"""
    query = db.query(OperationalTask).filter(OperationalTask.is_active == True)
    
    # تصفية حسب الصلاحيات
    user_roles = [role.name for role in current_user.roles]
    is_manager = any(role in user_roles for role in ["وزير / قيادة العليا", "وكيل وزارة", "مدير عام", "مدير إدارة", "Super Admin"])
    
    if not is_manager:
        # موظف عادي: يرى مهامه فقط
        assigned_task_ids = db.query(TaskAssignment.task_id).filter(
            TaskAssignment.employee_id == current_user.employee_id,
            TaskAssignment.is_active == True
        )
        query = query.filter(OperationalTask.task_id.in_(assigned_task_ids))
    
    # تصفية إضافية
    if major_task_id:
        query = query.filter(OperationalTask.major_task_id == major_task_id)
    if department_id:
        query = query.filter(OperationalTask.department_id == department_id)
    if status_id:
        query = query.filter(OperationalTask.status_id == status_id)
    if assigned_to_me and is_manager:
        # مدير يرى المهام المعينة لموظفيه (يمكن تنفيذها لاحقاً)
        pass
    if search:
        query = query.filter(OperationalTask.title.ilike(f"%{search}%"))
    
    tasks = query.offset(skip).limit(limit).all()
    
    result = []
    for task in tasks:
        major_task = db.query(MajorTask).filter(MajorTask.major_task_id == task.major_task_id).first()
        department = db.query(Department).filter(Department.department_id == task.department_id).first()
        status = db.query(DictStatus).filter(DictStatus.status_id == task.status_id).first()
        priority = db.query(DictPriority).filter(DictPriority.priority_id == task.priority_id).first()
        
        # حساب التقدم الحالي
        latest_log = db.query(TaskProgressLog).filter(
            TaskProgressLog.task_id == task.task_id
        ).order_by(TaskProgressLog.log_time.desc()).first()
        
        current_progress = latest_log.progress_percent if latest_log else 0
        
        # حساب التأخير
        is_delayed = False
        remaining_days = None
        if task.end_date and current_progress < 100:
            today = date.today()
            if today > task.end_date:
                is_delayed = True
            remaining_days = (task.end_date - today).days if task.end_date >= today else 0
        
        result.append(OperationalTaskResponse(
            task_id=task.task_id,
            major_task_id=task.major_task_id,
            major_task_name=major_task.name if major_task else None,
            department_id=task.department_id,
            department_name=department.name if department else None,
            title=task.title,
            description=task.description,
            status_id=task.status_id,
            status_name=status.name_ar if status else None,
            priority_id=task.priority_id,
            priority_name=priority.name_ar if priority else None,
            start_date=task.start_date,
            end_date=task.end_date,
            estimated_hours=task.estimated_hours,
            actual_hours=task.actual_hours,
            is_cross_functional=task.is_cross_functional or False,
            is_active=task.is_active,
            created_at=task.created_at,
            updated_at=task.updated_at,
            current_progress=current_progress,
            is_delayed=is_delayed,
            remaining_days=remaining_days
        ))
    return result

@router.get("/{task_id}", response_model=OperationalTaskResponse)
async def get_operational_task(
    task_id: int,
    db: Session = Depends(get_db),
    current_user: Employee = Depends(get_current_user)
):
    """جلب تفاصيل مهمة تشغيلية محددة"""
    task = db.query(OperationalTask).filter(OperationalTask.task_id == task_id).first()
    if not task:
        raise HTTPException(status_code=404, detail="المهمة غير موجودة")
    
    # التحقق من الصلاحية
    user_roles = [role.name for role in current_user.roles]
    is_manager = any(role in user_roles for role in ["وزير / قيادة العليا", "وكيل وزارة", "مدير عام", "مدير إدارة", "Super Admin"])
    
    if not is_manager:
        is_assigned = db.query(TaskAssignment).filter(
            TaskAssignment.task_id == task_id,
            TaskAssignment.employee_id == current_user.employee_id,
            TaskAssignment.is_active == True
        ).first()
        if not is_assigned:
            raise HTTPException(status_code=403, detail="غير مصرح لك بمشاهدة هذه المهمة")
    
    major_task = db.query(MajorTask).filter(MajorTask.major_task_id == task.major_task_id).first()
    department = db.query(Department).filter(Department.department_id == task.department_id).first()
    status = db.query(DictStatus).filter(DictStatus.status_id == task.status_id).first()
    priority = db.query(DictPriority).filter(DictPriority.priority_id == task.priority_id).first()
    
    latest_log = db.query(TaskProgressLog).filter(
        TaskProgressLog.task_id == task_id
    ).order_by(TaskProgressLog.log_time.desc()).first()
    
    current_progress = latest_log.progress_percent if latest_log else 0
    
    is_delayed = False
    remaining_days = None
    if task.end_date and current_progress < 100:
        today = date.today()
        if today > task.end_date:
            is_delayed = True
        remaining_days = (task.end_date - today).days if task.end_date >= today else 0
    
    return OperationalTaskResponse(
        task_id=task.task_id,
        major_task_id=task.major_task_id,
        major_task_name=major_task.name if major_task else None,
        department_id=task.department_id,
        department_name=department.name if department else None,
        title=task.title,
        description=task.description,
        status_id=task.status_id,
        status_name=status.name_ar if status else None,
        priority_id=task.priority_id,
        priority_name=priority.name_ar if priority else None,
        start_date=task.start_date,
        end_date=task.end_date,
        estimated_hours=task.estimated_hours,
        actual_hours=task.actual_hours,
        is_cross_functional=task.is_cross_functional or False,
        is_active=task.is_active,
        created_at=task.created_at,
        updated_at=task.updated_at,
        current_progress=current_progress,
        is_delayed=is_delayed,
        remaining_days=remaining_days
    )

@router.post("/", response_model=OperationalTaskResponse, status_code=status.HTTP_201_CREATED)
async def create_operational_task(
    task_data: OperationalTaskCreate,
    db: Session = Depends(get_db),
    current_user: Employee = Depends(has_role(["وزير / قيادة العليا", "وكيل وزارة", "مدير عام", "مدير إدارة", "Super Admin"]))
):
    """إنشاء مهمة تشغيلية جديدة"""
    major_task = db.query(MajorTask).filter(MajorTask.major_task_id == task_data.major_task_id).first()
    if not major_task:
        raise HTTPException(status_code=404, detail="المهمة الكبرى غير موجودة")
    
    department = db.query(Department).filter(Department.department_id == task_data.department_id).first()
    if not department:
        raise HTTPException(status_code=404, detail="الإدارة غير موجودة")
    
    new_task = OperationalTask(
        major_task_id=task_data.major_task_id,
        department_id=task_data.department_id,
        title=task_data.title,
        description=task_data.description,
        status_id=task_data.status_id,
        priority_id=task_data.priority_id,
        start_date=task_data.start_date,
        end_date=task_data.end_date,
        estimated_hours=task_data.estimated_hours,
        is_cross_functional=task_data.is_cross_functional or False,
        created_by=current_user.employee_id,
        is_active=True
    )
    db.add(new_task)
    db.commit()
    db.refresh(new_task)
    
    return OperationalTaskResponse(
        task_id=new_task.task_id,
        major_task_id=new_task.major_task_id,
        major_task_name=major_task.name,
        department_id=new_task.department_id,
        department_name=department.name,
        title=new_task.title,
        description=new_task.description,
        status_id=new_task.status_id,
        priority_id=new_task.priority_id,
        start_date=new_task.start_date,
        end_date=new_task.end_date,
        estimated_hours=new_task.estimated_hours,
        is_cross_functional=new_task.is_cross_functional or False,
        is_active=new_task.is_active,
        created_at=new_task.created_at,
        updated_at=new_task.updated_at,
        current_progress=0
    )

@router.put("/{task_id}", response_model=OperationalTaskResponse)
async def update_operational_task(
    task_id: int,
    task_data: OperationalTaskUpdate,
    db: Session = Depends(get_db),
    current_user: Employee = Depends(has_role(["وزير / قيادة العليا", "وكيل وزارة", "مدير عام", "مدير إدارة", "Super Admin"]))
):
    """تحديث مهمة تشغيلية"""
    task = db.query(OperationalTask).filter(OperationalTask.task_id == task_id).first()
    if not task:
        raise HTTPException(status_code=404, detail="المهمة غير موجودة")
    
    if task_data.major_task_id is not None:
        major_task = db.query(MajorTask).filter(MajorTask.major_task_id == task_data.major_task_id).first()
        if not major_task:
            raise HTTPException(status_code=404, detail="المهمة الكبرى غير موجودة")
        task.major_task_id = task_data.major_task_id
    
    if task_data.department_id is not None:
        department = db.query(Department).filter(Department.department_id == task_data.department_id).first()
        if not department:
            raise HTTPException(status_code=404, detail="الإدارة غير موجودة")
        task.department_id = task_data.department_id
    
    if task_data.title is not None:
        task.title = task_data.title
    if task_data.description is not None:
        task.description = task_data.description
    if task_data.status_id is not None:
        task.status_id = task_data.status_id
    if task_data.priority_id is not None:
        task.priority_id = task_data.priority_id
    if task_data.start_date is not None:
        task.start_date = task_data.start_date
    if task_data.end_date is not None:
        task.end_date = task_data.end_date
    if task_data.estimated_hours is not None:
        task.estimated_hours = task_data.estimated_hours
    if task_data.actual_hours is not None:
        task.actual_hours = task_data.actual_hours
    if task_data.is_cross_functional is not None:
        task.is_cross_functional = task_data.is_cross_functional
    if task_data.is_active is not None:
        task.is_active = task_data.is_active
    
    task.updated_at = datetime.now()
    db.commit()
    db.refresh(task)
    
    major_task = db.query(MajorTask).filter(MajorTask.major_task_id == task.major_task_id).first()
    department = db.query(Department).filter(Department.department_id == task.department_id).first()
    status = db.query(DictStatus).filter(DictStatus.status_id == task.status_id).first()
    priority = db.query(DictPriority).filter(DictPriority.priority_id == task.priority_id).first()
    
    latest_log = db.query(TaskProgressLog).filter(
        TaskProgressLog.task_id == task_id
    ).order_by(TaskProgressLog.log_time.desc()).first()
    
    current_progress = latest_log.progress_percent if latest_log else 0
    
    is_delayed = False
    remaining_days = None
    if task.end_date and current_progress < 100:
        today = date.today()
        if today > task.end_date:
            is_delayed = True
        remaining_days = (task.end_date - today).days if task.end_date >= today else 0
    
    return OperationalTaskResponse(
        task_id=task.task_id,
        major_task_id=task.major_task_id,
        major_task_name=major_task.name if major_task else None,
        department_id=task.department_id,
        department_name=department.name if department else None,
        title=task.title,
        description=task.description,
        status_id=task.status_id,
        status_name=status.name_ar if status else None,
        priority_id=task.priority_id,
        priority_name=priority.name_ar if priority else None,
        start_date=task.start_date,
        end_date=task.end_date,
        estimated_hours=task.estimated_hours,
        actual_hours=task.actual_hours,
        is_cross_functional=task.is_cross_functional or False,
        is_active=task.is_active,
        created_at=task.created_at,
        updated_at=task.updated_at,
        current_progress=current_progress,
        is_delayed=is_delayed,
        remaining_days=remaining_days
    )

@router.delete("/{task_id}")
async def delete_operational_task(
    task_id: int,
    db: Session = Depends(get_db),
    current_user: Employee = Depends(has_role(["وزير / قيادة العليا", "Super Admin"]))
):
    """حذف مهمة تشغيلية (إذا لم يكن لها تقدم أو توزيع)"""
    task = db.query(OperationalTask).filter(OperationalTask.task_id == task_id).first()
    if not task:
        raise HTTPException(status_code=404, detail="المهمة غير موجودة")
    
    # التحقق من وجود تقدم
    progress_count = db.query(TaskProgressLog).filter(TaskProgressLog.task_id == task_id).count()
    if progress_count > 0:
        raise HTTPException(status_code=400, detail="لا يمكن الحذف: يوجد سجل تقدم للمهمة")
    
    # التحقق من وجود توزيع
    assignment_count = db.query(TaskAssignment).filter(TaskAssignment.task_id == task_id).count()
    if assignment_count > 0:
        raise HTTPException(status_code=400, detail="لا يمكن الحذف: المهمة موزعة على موظفين")
    
    db.delete(task)
    db.commit()
    return {"message": "تم حذف المهمة التشغيلية بنجاح"}

# ================================================================
# 3. Task Assignments APIs (توزيع المهام)
# ================================================================

@router.get("/{task_id}/assignments", response_model=List[TaskAssignmentResponse])
async def get_task_assignments(
    task_id: int,
    db: Session = Depends(get_db),
    current_user: Employee = Depends(get_current_user)
):
    """جلب جميع توزيعات مهمة محددة"""
    task = db.query(OperationalTask).filter(OperationalTask.task_id == task_id).first()
    if not task:
        raise HTTPException(status_code=404, detail="المهمة غير موجودة")
    
    assignments = db.query(TaskAssignment).filter(
        TaskAssignment.task_id == task_id,
        TaskAssignment.is_active == True
    ).all()
    
    result = []
    for assignment in assignments:
        employee = db.query(Employee).filter(Employee.employee_id == assignment.employee_id).first()
        role_type = db.query(DictRoleType).filter(DictRoleType.role_type_id == assignment.role_type_id).first()
        assigned_by = db.query(Employee).filter(Employee.employee_id == assignment.assigned_by).first()
        
        result.append(TaskAssignmentResponse(
            assignment_id=assignment.assignment_id,
            task_id=assignment.task_id,
            task_title=task.title,
            employee_id=assignment.employee_id,
            employee_name=employee.full_name if employee else None,
            role_type_id=assignment.role_type_id,
            role_type_name=role_type.name_ar if role_type else None,
            acceptance_status=assignment.acceptance_status,
            rejection_reason=assignment.rejection_reason,
            accepted_at=assignment.accepted_at,
            estimated_hours=assignment.estimated_hours,
            assigned_by=assignment.assigned_by,
            assigned_by_name=assigned_by.full_name if assigned_by else None,
            assigned_at=assignment.assigned_at,
            is_active=assignment.is_active
        ))
    return result

@router.post("/{task_id}/assign", response_model=TaskAssignmentResponse, status_code=status.HTTP_201_CREATED)
async def assign_task(
    task_id: int,
    assignment_data: TaskAssignmentCreate,
    db: Session = Depends(get_db),
    current_user: Employee = Depends(has_role(["وزير / قيادة العليا", "وكيل وزارة", "مدير عام", "مدير إدارة", "Super Admin"]))
):
    """تعيين موظف لمهمة"""
    task = db.query(OperationalTask).filter(OperationalTask.task_id == task_id).first()
    if not task:
        raise HTTPException(status_code=404, detail="المهمة غير موجودة")
    
    employee = db.query(Employee).filter(Employee.employee_id == assignment_data.employee_id).first()
    if not employee:
        raise HTTPException(status_code=404, detail="الموظف غير موجود")
    
    # التحقق من عدم وجود تعيين مسبق
    existing = db.query(TaskAssignment).filter(
        TaskAssignment.task_id == task_id,
        TaskAssignment.employee_id == assignment_data.employee_id,
        TaskAssignment.is_active == True
    ).first()
    if existing:
        raise HTTPException(status_code=400, detail="الموظف معين بالفعل لهذه المهمة")
    
    new_assignment = TaskAssignment(
        task_id=task_id,
        employee_id=assignment_data.employee_id,
        role_type_id=assignment_data.role_type_id,
        estimated_hours=assignment_data.estimated_hours,
        assigned_by=current_user.employee_id,
        is_active=True
    )
    db.add(new_assignment)
    db.commit()
    db.refresh(new_assignment)
    
    role_type = db.query(DictRoleType).filter(DictRoleType.role_type_id == new_assignment.role_type_id).first()
    
    return TaskAssignmentResponse(
        assignment_id=new_assignment.assignment_id,
        task_id=new_assignment.task_id,
        task_title=task.title,
        employee_id=new_assignment.employee_id,
        employee_name=employee.full_name,
        role_type_id=new_assignment.role_type_id,
        role_type_name=role_type.name_ar if role_type else None,
        acceptance_status=new_assignment.acceptance_status,
        rejection_reason=new_assignment.rejection_reason,
        accepted_at=new_assignment.accepted_at,
        estimated_hours=new_assignment.estimated_hours,
        assigned_by=new_assignment.assigned_by,
        assigned_by_name=current_user.full_name,
        assigned_at=new_assignment.assigned_at,
        is_active=new_assignment.is_active
    )

@router.put("/assignments/{assignment_id}", response_model=TaskAssignmentResponse)
async def update_assignment(
    assignment_id: int,
    assignment_data: TaskAssignmentUpdate,
    db: Session = Depends(get_db),
    current_user: Employee = Depends(has_role(["وزير / قيادة العليا", "وكيل وزارة", "مدير عام", "مدير إدارة", "Super Admin"]))
):
    """تحديث توزيع مهمة"""
    assignment = db.query(TaskAssignment).filter(TaskAssignment.assignment_id == assignment_id).first()
    if not assignment:
        raise HTTPException(status_code=404, detail="التوزيع غير موجود")
    
    if assignment_data.role_type_id is not None:
        assignment.role_type_id = assignment_data.role_type_id
    if assignment_data.acceptance_status is not None:
        assignment.acceptance_status = assignment_data.acceptance_status
    if assignment_data.rejection_reason is not None:
        assignment.rejection_reason = assignment_data.rejection_reason
    if assignment_data.estimated_hours is not None:
        assignment.estimated_hours = assignment_data.estimated_hours
    if assignment_data.is_active is not None:
        assignment.is_active = assignment_data.is_active
    
    if assignment_data.acceptance_status == "accepted":
        assignment.accepted_at = datetime.now()
    
    db.commit()
    db.refresh(assignment)
    
    task = db.query(OperationalTask).filter(OperationalTask.task_id == assignment.task_id).first()
    employee = db.query(Employee).filter(Employee.employee_id == assignment.employee_id).first()
    role_type = db.query(DictRoleType).filter(DictRoleType.role_type_id == assignment.role_type_id).first()
    assigned_by = db.query(Employee).filter(Employee.employee_id == assignment.assigned_by).first()
    
    return TaskAssignmentResponse(
        assignment_id=assignment.assignment_id,
        task_id=assignment.task_id,
        task_title=task.title if task else None,
        employee_id=assignment.employee_id,
        employee_name=employee.full_name if employee else None,
        role_type_id=assignment.role_type_id,
        role_type_name=role_type.name_ar if role_type else None,
        acceptance_status=assignment.acceptance_status,
        rejection_reason=assignment.rejection_reason,
        accepted_at=assignment.accepted_at,
        estimated_hours=assignment.estimated_hours,
        assigned_by=assignment.assigned_by,
        assigned_by_name=assigned_by.full_name if assigned_by else None,
        assigned_at=assignment.assigned_at,
        is_active=assignment.is_active
    )

@router.delete("/assignments/{assignment_id}")
async def remove_assignment(
    assignment_id: int,
    db: Session = Depends(get_db),
    current_user: Employee = Depends(has_role(["وزير / قيادة العليا", "Super Admin"]))
):
    """حذف توزيع مهمة"""
    assignment = db.query(TaskAssignment).filter(TaskAssignment.assignment_id == assignment_id).first()
    if not assignment:
        raise HTTPException(status_code=404, detail="التوزيع غير موجود")
    
    db.delete(assignment)
    db.commit()
    return {"message": "تم إلغاء توزيع المهمة بنجاح"}

# ================================================================
# 4. Task Progress APIs (تحديث التقدم)
# ================================================================

@router.post("/{task_id}/progress", response_model=TaskProgressLogResponse)
async def update_task_progress(
    task_id: int,
    progress_data: TaskProgressUpdate,
    db: Session = Depends(get_db),
    current_user: Employee = Depends(get_current_user)
):
    """تحديث نسبة إنجاز المهمة مع تسجيل الموقع"""
    task = db.query(OperationalTask).filter(OperationalTask.task_id == task_id).first()
    if not task:
        raise HTTPException(status_code=404, detail="المهمة غير موجودة")
    
    # التحقق من أن المستخدم معين للمهمة
    assignment = db.query(TaskAssignment).filter(
        TaskAssignment.task_id == task_id,
        TaskAssignment.employee_id == current_user.employee_id,
        TaskAssignment.is_active == True
    ).first()
    
    if not assignment:
        # التحقق من أن المستخدم مدير أو وزير
        user_roles = [role.name for role in current_user.roles]
        is_manager = any(role in user_roles for role in ["وزير / قيادة العليا", "وكيل وزارة", "مدير عام", "مدير إدارة", "Super Admin"])
        if not is_manager:
            raise HTTPException(status_code=403, detail="غير مصرح لك بتحديث هذه المهمة")
    
    # الحالة القديمة
    old_status = task.status_id
    
    # إنشاء سجل التقدم
    progress_log = TaskProgressLog(
        task_id=task_id,
        employee_id=current_user.employee_id,
        progress_percent=progress_data.progress_percent,
        status_old=old_status,
        notes=progress_data.notes,
        log_time=datetime.now()
    )
    db.add(progress_log)
    
    # تحديث حالة المهمة إذا اكتملت
    if progress_data.progress_percent >= 100:
        completed_status = db.query(DictStatus).filter(DictStatus.code == "completed").first()
        if completed_status:
            task.status_id = completed_status.status_id
        # أيضاً تحديث الحالة الجديدة في سجل التقدم
        progress_log.status_new = task.status_id
    else:
        # تحديث الحالة إلى "قيد التنفيذ" إذا كانت البداية
        if progress_data.progress_percent > 0 and old_status:
            in_progress_status = db.query(DictStatus).filter(DictStatus.code == "in_progress").first()
            if in_progress_status:
                task.status_id = in_progress_status.status_id
                progress_log.status_new = task.status_id
    
    # تسجيل الموقع إذا تم إرساله
    if progress_data.latitude is not None and progress_data.longitude is not None:
        location_log = LocationLog(
            employee_id=current_user.employee_id,
            task_id=task_id,
            latitude=progress_data.latitude,
            longitude=progress_data.longitude,
            recorded_at=datetime.now()
        )
        db.add(location_log)
    
    task.updated_at = datetime.now()
    db.commit()
    db.refresh(progress_log)
    
    return TaskProgressLogResponse(
        log_id=progress_log.log_id,
        task_id=progress_log.task_id,
        employee_id=progress_log.employee_id,
        employee_name=current_user.full_name,
        progress_percent=progress_log.progress_percent,
        status_old=progress_log.status_old,
        status_new=progress_log.status_new,
        notes=progress_log.notes,
        log_time=progress_log.log_time
    )

@router.get("/{task_id}/progress", response_model=List[TaskProgressLogResponse])
async def get_task_progress_logs(
    task_id: int,
    skip: int = 0,
    limit: int = 50,
    db: Session = Depends(get_db),
    current_user: Employee = Depends(get_current_user)
):
    """جلب سجل تقدم مهمة محددة"""
    task = db.query(OperationalTask).filter(OperationalTask.task_id == task_id).first()
    if not task:
        raise HTTPException(status_code=404, detail="المهمة غير موجودة")
    
    logs = db.query(TaskProgressLog).filter(
        TaskProgressLog.task_id == task_id
    ).order_by(TaskProgressLog.log_time.desc()).offset(skip).limit(limit).all()
    
    result = []
    for log in logs:
        employee = db.query(Employee).filter(Employee.employee_id == log.employee_id).first()
        result.append(TaskProgressLogResponse(
            log_id=log.log_id,
            task_id=log.task_id,
            employee_id=log.employee_id,
            employee_name=employee.full_name if employee else None,
            progress_percent=log.progress_percent,
            status_old=log.status_old,
            status_new=log.status_new,
            notes=log.notes,
            log_time=log.log_time
        ))
    return result

# ================================================================
# 5. Task Dependencies APIs (الاعتماديات)
# ================================================================

@router.get("/{task_id}/dependencies")
async def get_task_dependencies(
    task_id: int,
    db: Session = Depends(get_db),
    current_user: Employee = Depends(get_current_user)
):
    """جلب جميع الاعتماديات لمهمة محددة"""
    task = db.query(OperationalTask).filter(OperationalTask.task_id == task_id).first()
    if not task:
        raise HTTPException(status_code=404, detail="المهمة غير موجودة")
    
    dependencies = db.query(TaskDependency).filter(
        or_(
            TaskDependency.task_id == task_id,
            TaskDependency.depends_on_task_id == task_id
        )
    ).all()
    
    result = []
    for dep in dependencies:
        dependent_task = db.query(OperationalTask).filter(OperationalTask.task_id == dep.task_id).first()
        depends_on_task = db.query(OperationalTask).filter(OperationalTask.task_id == dep.depends_on_task_id).first()
        
        result.append({
            "dependency_id": dep.dependency_id,
            "task_id": dep.task_id,
            "task_title": dependent_task.title if dependent_task else None,
            "depends_on_task_id": dep.depends_on_task_id,
            "depends_on_task_title": depends_on_task.title if depends_on_task else None,
            "dependency_type": dep.dependency_type,
            "lag_days": dep.lag_days,
            "created_at": dep.created_at
        })
    return result

@router.post("/dependencies")
async def create_task_dependency(
    task_id: int = Query(...),
    depends_on_task_id: int = Query(...),
    dependency_type: str = Query("FS"),
    lag_days: int = Query(0),
    db: Session = Depends(get_db),
    current_user: Employee = Depends(has_role(["وزير / قيادة العليا", "وكيل وزارة", "مدير عام", "مدير إدارة", "Super Admin"]))
):
    """إنشاء اعتمادية بين مهمتين"""
    if task_id == depends_on_task_id:
        raise HTTPException(status_code=400, detail="لا يمكن للمهمة أن تعتمد على نفسها")
    
    task = db.query(OperationalTask).filter(OperationalTask.task_id == task_id).first()
    if not task:
        raise HTTPException(status_code=404, detail="المهمة غير موجودة")
    
    depends_on = db.query(OperationalTask).filter(OperationalTask.task_id == depends_on_task_id).first()
    if not depends_on:
        raise HTTPException(status_code=404, detail="المهمة المعتمد عليها غير موجودة")
    
    # التحقق من عدم وجود اعتمادية مكررة
    existing = db.query(TaskDependency).filter(
        TaskDependency.task_id == task_id,
        TaskDependency.depends_on_task_id == depends_on_task_id
    ).first()
    if existing:
        raise HTTPException(status_code=400, detail="الاعتمادية موجودة مسبقاً")
    
    new_dependency = TaskDependency(
        task_id=task_id,
        depends_on_task_id=depends_on_task_id,
        dependency_type=dependency_type,
        lag_days=lag_days
    )
    db.add(new_dependency)
    db.commit()
    db.refresh(new_dependency)
    
    return {
        "dependency_id": new_dependency.dependency_id,
        "task_id": new_dependency.task_id,
        "depends_on_task_id": new_dependency.depends_on_task_id,
        "dependency_type": new_dependency.dependency_type,
        "lag_days": new_dependency.lag_days,
        "created_at": new_dependency.created_at,
        "message": "تم إنشاء الاعتمادية بنجاح"
    }

@router.delete("/dependencies/{dependency_id}")
async def delete_task_dependency(
    dependency_id: int,
    db: Session = Depends(get_db),
    current_user: Employee = Depends(has_role(["وزير / قيادة العليا", "Super Admin"]))
):
    """حذف اعتمادية"""
    dependency = db.query(TaskDependency).filter(TaskDependency.dependency_id == dependency_id).first()
    if not dependency:
        raise HTTPException(status_code=404, detail="الاعتمادية غير موجودة")
    
    db.delete(dependency)
    db.commit()
    return {"message": "تم حذف الاعتمادية بنجاح"}

# ================================================================
# 6. Task Statistics APIs (إحصائيات المهام)
# ================================================================

@router.get("/stats/summary")
async def get_task_stats(
    db: Session = Depends(get_db),
    current_user: Employee = Depends(get_current_user)
):
    """إحصائيات سريعة للمهام حسب صلاحية المستخدم"""
    query = db.query(OperationalTask).filter(OperationalTask.is_active == True)
    
    user_roles = [role.name for role in current_user.roles]
    is_manager = any(role in user_roles for role in ["وزير / قيادة العليا", "وكيل وزارة", "مدير عام", "مدير إدارة", "Super Admin"])
    
    if not is_manager:
        # موظف عادي: إحصائيات مهامه فقط
        assigned_task_ids = db.query(TaskAssignment.task_id).filter(
            TaskAssignment.employee_id == current_user.employee_id,
            TaskAssignment.is_active == True
        )
        query = query.filter(OperationalTask.task_id.in_(assigned_task_ids))
    elif "مدير إدارة" in user_roles:
        # مدير إدارة: إحصائيات مهام إدارته
        query = query.filter(OperationalTask.department_id == current_user.department_id)
    
    total = query.count()
    completed = query.filter(OperationalTask.status_id == db.query(DictStatus).filter(DictStatus.code == "completed").first().status_id).count()
    
    # حالة "قيد التنفيذ"
    in_progress_status = db.query(DictStatus).filter(DictStatus.code == "in_progress").first()
    in_progress = query.filter(OperationalTask.status_id == in_progress_status.status_id).count() if in_progress_status else 0
    
    # حالة "معلق"
    pending_status = db.query(DictStatus).filter(DictStatus.code == "pending").first()
    pending = query.filter(OperationalTask.status_id == pending_status.status_id).count() if pending_status else 0
    
    # المتأخرة
    today = date.today()
    delayed = query.filter(
        OperationalTask.end_date < today,
        OperationalTask.status_id != db.query(DictStatus).filter(DictStatus.code == "completed").first().status_id
    ).count()
    
    # حساب التقدم المتوسط
    avg_progress = db.query(func.avg(TaskProgressLog.progress_percent)).join(
        OperationalTask, TaskProgressLog.task_id == OperationalTask.task_id
    ).filter(OperationalTask.task_id.in_(query.with_entities(OperationalTask.task_id).subquery())).scalar()
    
    return {
        "total_tasks": total,
        "completed": completed,
        "in_progress": in_progress,
        "pending": pending,
        "delayed": delayed,
        "completion_rate": round((completed / total * 100), 2) if total > 0 else 0,
        "average_progress": round(avg_progress or 0, 2)
    }

@router.get("/stats/my-tasks")
async def get_my_task_stats(
    db: Session = Depends(get_db),
    current_user: Employee = Depends(get_current_user)
):
    """إحصائيات مهام الموظف الحالي"""
    assigned_task_ids = db.query(TaskAssignment.task_id).filter(
        TaskAssignment.employee_id == current_user.employee_id,
        TaskAssignment.is_active == True
    )
    
    tasks = db.query(OperationalTask).filter(
        OperationalTask.task_id.in_(assigned_task_ids),
        OperationalTask.is_active == True
    ).all()
    
    total = len(tasks)
    completed_status = db.query(DictStatus).filter(DictStatus.code == "completed").first()
    completed = len([t for t in tasks if t.status_id == completed_status.status_id]) if completed_status else 0
    
    # حساب التقدم الإجمالي
    total_progress = 0
    for task in tasks:
        latest_log = db.query(TaskProgressLog).filter(
            TaskProgressLog.task_id == task.task_id
        ).order_by(TaskProgressLog.log_time.desc()).first()
        if latest_log:
            total_progress += latest_log.progress_percent
    
    avg_progress = round(total_progress / total, 2) if total > 0 else 0
    
    return {
        "total": total,
        "completed": completed,
        "in_progress": len([t for t in tasks if t.status_id != (completed_status.status_id if completed_status else 0)]),
        "average_progress": avg_progress,
        "completion_rate": round((completed / total * 100), 2) if total > 0 else 0
    }
 

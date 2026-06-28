 
from fastapi import APIRouter, Depends, HTTPException, status, Query
from sqlalchemy.orm import Session, joinedload
from typing import List, Optional
from datetime import datetime

from ...database import get_db
from ...models import (
    StrategicPillar, StrategicGoal, Program, Initiative,
    MajorTask, OperationalTask, DictStatus, DictPriority, Employee
)
from ...schemas import (
    StrategicPillarCreate, StrategicPillarUpdate, StrategicPillarResponse,
    StrategicGoalCreate, StrategicGoalUpdate, StrategicGoalResponse,
    ProgramCreate, ProgramUpdate, ProgramResponse,
    InitiativeCreate, InitiativeUpdate, InitiativeResponse,
    MajorTaskCreate, MajorTaskUpdate, MajorTaskResponse
)
from ...core.dependencies import get_current_user, has_role

router = APIRouter(prefix="/api/strategic", tags=["التخطيط الاستراتيجي"])

# ================================================================
# 1. Strategic Pillars APIs (الركائز الاستراتيجية)
# ================================================================

@router.get("/pillars", response_model=List[StrategicPillarResponse])
async def get_pillars(
    search: Optional[str] = None,
    skip: int = 0,
    limit: int = 100,
    db: Session = Depends(get_db),
    current_user: Employee = Depends(get_current_user)
):
    """جلب جميع الركائز الاستراتيجية مع إمكانية البحث"""
    query = db.query(StrategicPillar).filter(StrategicPillar.is_active == True)
    
    if search:
        query = query.filter(StrategicPillar.name.ilike(f"%{search}%"))
    
    pillars = query.offset(skip).limit(limit).all()
    
    result = []
    for pillar in pillars:
        goals_count = db.query(StrategicGoal).filter(
            StrategicGoal.pillar_id == pillar.pillar_id,
            StrategicGoal.is_active == True
        ).count()
        result.append(StrategicPillarResponse(
            pillar_id=pillar.pillar_id,
            name=pillar.name,
            description=pillar.description,
            order_index=pillar.order_index or 0,
            is_active=pillar.is_active,
            created_at=pillar.created_at,
            updated_at=pillar.updated_at,
            goals_count=goals_count
        ))
    return result

@router.post("/pillars", response_model=StrategicPillarResponse, status_code=status.HTTP_201_CREATED)
async def create_pillar(
    pillar_data: StrategicPillarCreate,
    db: Session = Depends(get_db),
    current_user: Employee = Depends(has_role(["وزير / قيادة العليا", "وكيل وزارة", "Super Admin"]))
):
    """إنشاء ركيزة استراتيجية جديدة"""
    existing = db.query(StrategicPillar).filter(StrategicPillar.name == pillar_data.name).first()
    if existing:
        raise HTTPException(status_code=400, detail="اسم الركيزة موجود مسبقاً")
    
    new_pillar = StrategicPillar(
        name=pillar_data.name,
        description=pillar_data.description,
        order_index=pillar_data.order_index or 0,
        created_by=current_user.employee_id,
        is_active=True
    )
    db.add(new_pillar)
    db.commit()
    db.refresh(new_pillar)
    
    return StrategicPillarResponse(
        pillar_id=new_pillar.pillar_id,
        name=new_pillar.name,
        description=new_pillar.description,
        order_index=new_pillar.order_index or 0,
        is_active=new_pillar.is_active,
        created_at=new_pillar.created_at,
        updated_at=new_pillar.updated_at,
        goals_count=0
    )

@router.put("/pillars/{pillar_id}", response_model=StrategicPillarResponse)
async def update_pillar(
    pillar_id: int,
    pillar_data: StrategicPillarUpdate,
    db: Session = Depends(get_db),
    current_user: Employee = Depends(has_role(["وزير / قيادة العليا", "وكيل وزارة", "Super Admin"]))
):
    """تحديث ركيزة استراتيجية"""
    pillar = db.query(StrategicPillar).filter(StrategicPillar.pillar_id == pillar_id).first()
    if not pillar:
        raise HTTPException(status_code=404, detail="الركيزة غير موجودة")
    
    if pillar_data.name is not None:
        existing = db.query(StrategicPillar).filter(
            StrategicPillar.name == pillar_data.name,
            StrategicPillar.pillar_id != pillar_id
        ).first()
        if existing:
            raise HTTPException(status_code=400, detail="اسم الركيزة موجود مسبقاً")
        pillar.name = pillar_data.name
    
    if pillar_data.description is not None:
        pillar.description = pillar_data.description
    if pillar_data.order_index is not None:
        pillar.order_index = pillar_data.order_index
    if pillar_data.is_active is not None:
        pillar.is_active = pillar_data.is_active
    
    pillar.updated_at = datetime.now()
    db.commit()
    db.refresh(pillar)
    
    goals_count = db.query(StrategicGoal).filter(
        StrategicGoal.pillar_id == pillar.pillar_id,
        StrategicGoal.is_active == True
    ).count()
    
    return StrategicPillarResponse(
        pillar_id=pillar.pillar_id,
        name=pillar.name,
        description=pillar.description,
        order_index=pillar.order_index or 0,
        is_active=pillar.is_active,
        created_at=pillar.created_at,
        updated_at=pillar.updated_at,
        goals_count=goals_count
    )

@router.delete("/pillars/{pillar_id}")
async def delete_pillar(
    pillar_id: int,
    db: Session = Depends(get_db),
    current_user: Employee = Depends(has_role(["وزير / قيادة العليا", "Super Admin"]))
):
    """حذف ركيزة استراتيجية (إذا لم تكن مرتبطة بأهداف)"""
    pillar = db.query(StrategicPillar).filter(StrategicPillar.pillar_id == pillar_id).first()
    if not pillar:
        raise HTTPException(status_code=404, detail="الركيزة غير موجودة")
    
    goals_count = db.query(StrategicGoal).filter(StrategicGoal.pillar_id == pillar_id).count()
    if goals_count > 0:
        raise HTTPException(status_code=400, detail=f"لا يمكن الحذف: مرتبطة بـ {goals_count} أهداف")
    
    db.delete(pillar)
    db.commit()
    return {"message": "تم حذف الركيزة بنجاح"}

# ================================================================
# 2. Strategic Goals APIs (الأهداف الاستراتيجية)
# ================================================================

@router.get("/goals", response_model=List[StrategicGoalResponse])
async def get_goals(
    pillar_id: Optional[int] = None,
    search: Optional[str] = None,
    skip: int = 0,
    limit: int = 100,
    db: Session = Depends(get_db),
    current_user: Employee = Depends(get_current_user)
):
    """جلب جميع الأهداف الاستراتيجية"""
    query = db.query(StrategicGoal).filter(StrategicGoal.is_active == True)
    
    if pillar_id:
        query = query.filter(StrategicGoal.pillar_id == pillar_id)
    if search:
        query = query.filter(StrategicGoal.title.ilike(f"%{search}%"))
    
    goals = query.offset(skip).limit(limit).all()
    
    result = []
    for goal in goals:
        pillar = db.query(StrategicPillar).filter(StrategicPillar.pillar_id == goal.pillar_id).first()
        programs_count = db.query(Program).filter(
            Program.goal_id == goal.goal_id,
            Program.is_active == True
        ).count()
        
        result.append(StrategicGoalResponse(
            goal_id=goal.goal_id,
            pillar_id=goal.pillar_id,
            pillar_name=pillar.name if pillar else None,
            title=goal.title,
            description=goal.description,
            target_date=goal.target_date,
            weight=goal.weight or 1,
            valid_from=goal.valid_from,
            valid_until=goal.valid_until,
            is_current=True if goal.valid_from and goal.valid_until and goal.valid_from <= datetime.now().date() <= goal.valid_until else False,
            is_active=goal.is_active,
            created_at=goal.created_at,
            updated_at=goal.updated_at,
            programs_count=programs_count
        ))
    return result

@router.post("/goals", response_model=StrategicGoalResponse, status_code=status.HTTP_201_CREATED)
async def create_goal(
    goal_data: StrategicGoalCreate,
    db: Session = Depends(get_db),
    current_user: Employee = Depends(has_role(["وزير / قيادة العليا", "وكيل وزارة", "Super Admin"]))
):
    """إنشاء هدف استراتيجي جديد"""
    pillar = db.query(StrategicPillar).filter(StrategicPillar.pillar_id == goal_data.pillar_id).first()
    if not pillar:
        raise HTTPException(status_code=404, detail="الركيزة غير موجودة")
    
    new_goal = StrategicGoal(
        pillar_id=goal_data.pillar_id,
        title=goal_data.title,
        description=goal_data.description,
        target_date=goal_data.target_date,
        weight=goal_data.weight or 1,
        valid_from=goal_data.valid_from,
        valid_until=goal_data.valid_until,
        created_by=current_user.employee_id,
        is_active=True
    )
    db.add(new_goal)
    db.commit()
    db.refresh(new_goal)
    
    return StrategicGoalResponse(
        goal_id=new_goal.goal_id,
        pillar_id=new_goal.pillar_id,
        pillar_name=pillar.name,
        title=new_goal.title,
        description=new_goal.description,
        target_date=new_goal.target_date,
        weight=new_goal.weight or 1,
        valid_from=new_goal.valid_from,
        valid_until=new_goal.valid_until,
        is_current=True if new_goal.valid_from and new_goal.valid_until and new_goal.valid_from <= datetime.now().date() <= new_goal.valid_until else False,
        is_active=new_goal.is_active,
        created_at=new_goal.created_at,
        updated_at=new_goal.updated_at,
        programs_count=0
    )

@router.put("/goals/{goal_id}", response_model=StrategicGoalResponse)
async def update_goal(
    goal_id: int,
    goal_data: StrategicGoalUpdate,
    db: Session = Depends(get_db),
    current_user: Employee = Depends(has_role(["وزير / قيادة العليا", "وكيل وزارة", "Super Admin"]))
):
    """تحديث هدف استراتيجي"""
    goal = db.query(StrategicGoal).filter(StrategicGoal.goal_id == goal_id).first()
    if not goal:
        raise HTTPException(status_code=404, detail="الهدف غير موجود")
    
    if goal_data.pillar_id is not None:
        pillar = db.query(StrategicPillar).filter(StrategicPillar.pillar_id == goal_data.pillar_id).first()
        if not pillar:
            raise HTTPException(status_code=404, detail="الركيزة غير موجودة")
        goal.pillar_id = goal_data.pillar_id
    
    if goal_data.title is not None:
        goal.title = goal_data.title
    if goal_data.description is not None:
        goal.description = goal_data.description
    if goal_data.target_date is not None:
        goal.target_date = goal_data.target_date
    if goal_data.weight is not None:
        goal.weight = goal_data.weight
    if goal_data.valid_from is not None:
        goal.valid_from = goal_data.valid_from
    if goal_data.valid_until is not None:
        goal.valid_until = goal_data.valid_until
    if goal_data.is_active is not None:
        goal.is_active = goal_data.is_active
    
    goal.updated_at = datetime.now()
    db.commit()
    db.refresh(goal)
    
    pillar = db.query(StrategicPillar).filter(StrategicPillar.pillar_id == goal.pillar_id).first()
    programs_count = db.query(Program).filter(
        Program.goal_id == goal.goal_id,
        Program.is_active == True
    ).count()
    
    return StrategicGoalResponse(
        goal_id=goal.goal_id,
        pillar_id=goal.pillar_id,
        pillar_name=pillar.name if pillar else None,
        title=goal.title,
        description=goal.description,
        target_date=goal.target_date,
        weight=goal.weight or 1,
        valid_from=goal.valid_from,
        valid_until=goal.valid_until,
        is_current=True if goal.valid_from and goal.valid_until and goal.valid_from <= datetime.now().date() <= goal.valid_until else False,
        is_active=goal.is_active,
        created_at=goal.created_at,
        updated_at=goal.updated_at,
        programs_count=programs_count
    )

@router.delete("/goals/{goal_id}")
async def delete_goal(
    goal_id: int,
    db: Session = Depends(get_db),
    current_user: Employee = Depends(has_role(["وزير / قيادة العليا", "Super Admin"]))
):
    """حذف هدف استراتيجي (إذا لم يكن مرتبطاً ببرامج)"""
    goal = db.query(StrategicGoal).filter(StrategicGoal.goal_id == goal_id).first()
    if not goal:
        raise HTTPException(status_code=404, detail="الهدف غير موجود")
    
    programs_count = db.query(Program).filter(Program.goal_id == goal_id).count()
    if programs_count > 0:
        raise HTTPException(status_code=400, detail=f"لا يمكن الحذف: مرتبط بـ {programs_count} برامج")
    
    db.delete(goal)
    db.commit()
    return {"message": "تم حذف الهدف بنجاح"}

# ================================================================
# 3. Programs APIs (البرامج)
# ================================================================

@router.get("/programs", response_model=List[ProgramResponse])
async def get_programs(
    goal_id: Optional[int] = None,
    search: Optional[str] = None,
    skip: int = 0,
    limit: int = 100,
    db: Session = Depends(get_db),
    current_user: Employee = Depends(get_current_user)
):
    """جلب جميع البرامج"""
    query = db.query(Program).filter(Program.is_active == True)
    
    if goal_id:
        query = query.filter(Program.goal_id == goal_id)
    if search:
        query = query.filter(Program.name.ilike(f"%{search}%"))
    
    programs = query.offset(skip).limit(limit).all()
    
    result = []
    for program in programs:
        goal = db.query(StrategicGoal).filter(StrategicGoal.goal_id == program.goal_id).first()
        status = db.query(DictStatus).filter(DictStatus.status_id == program.status_id).first()
        initiatives_count = db.query(Initiative).filter(
            Initiative.program_id == program.program_id,
            Initiative.is_active == True
        ).count()
        
        result.append(ProgramResponse(
            program_id=program.program_id,
            goal_id=program.goal_id,
            goal_name=goal.title if goal else None,
            name=program.name,
            description=program.description,
            budget_estimate=program.budget_estimate,
            start_date=program.start_date,
            end_date=program.end_date,
            status_id=program.status_id,
            status_name=status.name_ar if status else None,
            is_active=program.is_active,
            created_at=program.created_at,
            updated_at=program.updated_at,
            initiatives_count=initiatives_count
        ))
    return result

@router.post("/programs", response_model=ProgramResponse, status_code=status.HTTP_201_CREATED)
async def create_program(
    program_data: ProgramCreate,
    db: Session = Depends(get_db),
    current_user: Employee = Depends(has_role(["وزير / قيادة العليا", "وكيل وزارة", "Super Admin"]))
):
    """إنشاء برنامج جديد"""
    goal = db.query(StrategicGoal).filter(StrategicGoal.goal_id == program_data.goal_id).first()
    if not goal:
        raise HTTPException(status_code=404, detail="الهدف غير موجود")
    
    new_program = Program(
        goal_id=program_data.goal_id,
        name=program_data.name,
        description=program_data.description,
        budget_estimate=program_data.budget_estimate,
        start_date=program_data.start_date,
        end_date=program_data.end_date,
        status_id=program_data.status_id,
        created_by=current_user.employee_id,
        is_active=True
    )
    db.add(new_program)
    db.commit()
    db.refresh(new_program)
    
    return ProgramResponse(
        program_id=new_program.program_id,
        goal_id=new_program.goal_id,
        goal_name=goal.title,
        name=new_program.name,
        description=new_program.description,
        budget_estimate=new_program.budget_estimate,
        start_date=new_program.start_date,
        end_date=new_program.end_date,
        status_id=new_program.status_id,
        is_active=new_program.is_active,
        created_at=new_program.created_at,
        updated_at=new_program.updated_at,
        initiatives_count=0
    )

@router.put("/programs/{program_id}", response_model=ProgramResponse)
async def update_program(
    program_id: int,
    program_data: ProgramUpdate,
    db: Session = Depends(get_db),
    current_user: Employee = Depends(has_role(["وزير / قيادة العليا", "وكيل وزارة", "Super Admin"]))
):
    """تحديث برنامج"""
    program = db.query(Program).filter(Program.program_id == program_id).first()
    if not program:
        raise HTTPException(status_code=404, detail="البرنامج غير موجود")
    
    if program_data.goal_id is not None:
        goal = db.query(StrategicGoal).filter(StrategicGoal.goal_id == program_data.goal_id).first()
        if not goal:
            raise HTTPException(status_code=404, detail="الهدف غير موجود")
        program.goal_id = program_data.goal_id
    
    if program_data.name is not None:
        program.name = program_data.name
    if program_data.description is not None:
        program.description = program_data.description
    if program_data.budget_estimate is not None:
        program.budget_estimate = program_data.budget_estimate
    if program_data.start_date is not None:
        program.start_date = program_data.start_date
    if program_data.end_date is not None:
        program.end_date = program_data.end_date
    if program_data.status_id is not None:
        program.status_id = program_data.status_id
    if program_data.is_active is not None:
        program.is_active = program_data.is_active
    
    program.updated_at = datetime.now()
    db.commit()
    db.refresh(program)
    
    goal = db.query(StrategicGoal).filter(StrategicGoal.goal_id == program.goal_id).first()
    status = db.query(DictStatus).filter(DictStatus.status_id == program.status_id).first()
    initiatives_count = db.query(Initiative).filter(
        Initiative.program_id == program.program_id,
        Initiative.is_active == True
    ).count()
    
    return ProgramResponse(
        program_id=program.program_id,
        goal_id=program.goal_id,
        goal_name=goal.title if goal else None,
        name=program.name,
        description=program.description,
        budget_estimate=program.budget_estimate,
        start_date=program.start_date,
        end_date=program.end_date,
        status_id=program.status_id,
        status_name=status.name_ar if status else None,
        is_active=program.is_active,
        created_at=program.created_at,
        updated_at=program.updated_at,
        initiatives_count=initiatives_count
    )

@router.delete("/programs/{program_id}")
async def delete_program(
    program_id: int,
    db: Session = Depends(get_db),
    current_user: Employee = Depends(has_role(["وزير / قيادة العليا", "Super Admin"]))
):
    """حذف برنامج (إذا لم يكن مرتبطاً بمبادرات)"""
    program = db.query(Program).filter(Program.program_id == program_id).first()
    if not program:
        raise HTTPException(status_code=404, detail="البرنامج غير موجود")
    
    initiatives_count = db.query(Initiative).filter(Initiative.program_id == program_id).count()
    if initiatives_count > 0:
        raise HTTPException(status_code=400, detail=f"لا يمكن الحذف: مرتبط بـ {initiatives_count} مبادرات")
    
    db.delete(program)
    db.commit()
    return {"message": "تم حذف البرنامج بنجاح"}

# ================================================================
# 4. Initiatives APIs (المبادرات)
# ================================================================

@router.get("/initiatives", response_model=List[InitiativeResponse])
async def get_initiatives(
    program_id: Optional[int] = None,
    status_id: Optional[int] = None,
    search: Optional[str] = None,
    skip: int = 0,
    limit: int = 100,
    db: Session = Depends(get_db),
    current_user: Employee = Depends(get_current_user)
):
    """جلب جميع المبادرات"""
    query = db.query(Initiative).filter(Initiative.is_active == True)
    
    if program_id:
        query = query.filter(Initiative.program_id == program_id)
    if status_id:
        query = query.filter(Initiative.status_id == status_id)
    if search:
        query = query.filter(Initiative.name.ilike(f"%{search}%"))
    
    initiatives = query.offset(skip).limit(limit).all()
    
    result = []
    for initiative in initiatives:
        program = db.query(Program).filter(Program.program_id == initiative.program_id).first()
        status = db.query(DictStatus).filter(DictStatus.status_id == initiative.status_id).first()
        priority = db.query(DictPriority).filter(DictPriority.priority_id == initiative.priority_id).first()
        major_tasks_count = db.query(MajorTask).filter(
            MajorTask.initiative_id == initiative.initiative_id,
            MajorTask.is_active == True
        ).count()
        
        result.append(InitiativeResponse(
            initiative_id=initiative.initiative_id,
            program_id=initiative.program_id,
            program_name=program.name if program else None,
            name=initiative.name,
            description=initiative.description,
            status_id=initiative.status_id,
            status_name=status.name_ar if status else None,
            priority_id=initiative.priority_id,
            priority_name=priority.name_ar if priority else None,
            start_date=initiative.start_date,
            end_date=initiative.end_date,
            budget_estimate=initiative.budget_estimate,
            is_active=initiative.is_active,
            created_at=initiative.created_at,
            updated_at=initiative.updated_at,
            major_tasks_count=major_tasks_count
        ))
    return result

@router.post("/initiatives", response_model=InitiativeResponse, status_code=status.HTTP_201_CREATED)
async def create_initiative(
    initiative_data: InitiativeCreate,
    db: Session = Depends(get_db),
    current_user: Employee = Depends(has_role(["وزير / قيادة العليا", "وكيل وزارة", "Super Admin"]))
):
    """إنشاء مبادرة جديدة"""
    program = db.query(Program).filter(Program.program_id == initiative_data.program_id).first()
    if not program:
        raise HTTPException(status_code=404, detail="البرنامج غير موجود")
    
    new_initiative = Initiative(
        program_id=initiative_data.program_id,
        name=initiative_data.name,
        description=initiative_data.description,
        status_id=initiative_data.status_id,
        priority_id=initiative_data.priority_id,
        start_date=initiative_data.start_date,
        end_date=initiative_data.end_date,
        budget_estimate=initiative_data.budget_estimate,
        created_by=current_user.employee_id,
        is_active=True
    )
    db.add(new_initiative)
    db.commit()
    db.refresh(new_initiative)
    
    return InitiativeResponse(
        initiative_id=new_initiative.initiative_id,
        program_id=new_initiative.program_id,
        program_name=program.name,
        name=new_initiative.name,
        description=new_initiative.description,
        status_id=new_initiative.status_id,
        priority_id=new_initiative.priority_id,
        start_date=new_initiative.start_date,
        end_date=new_initiative.end_date,
        budget_estimate=new_initiative.budget_estimate,
        is_active=new_initiative.is_active,
        created_at=new_initiative.created_at,
        updated_at=new_initiative.updated_at,
        major_tasks_count=0
    )

@router.put("/initiatives/{initiative_id}", response_model=InitiativeResponse)
async def update_initiative(
    initiative_id: int,
    initiative_data: InitiativeUpdate,
    db: Session = Depends(get_db),
    current_user: Employee = Depends(has_role(["وزير / قيادة العليا", "وكيل وزارة", "Super Admin"]))
):
    """تحديث مبادرة"""
    initiative = db.query(Initiative).filter(Initiative.initiative_id == initiative_id).first()
    if not initiative:
        raise HTTPException(status_code=404, detail="المبادرة غير موجودة")
    
    if initiative_data.program_id is not None:
        program = db.query(Program).filter(Program.program_id == initiative_data.program_id).first()
        if not program:
            raise HTTPException(status_code=404, detail="البرنامج غير موجود")
        initiative.program_id = initiative_data.program_id
    
    if initiative_data.name is not None:
        initiative.name = initiative_data.name
    if initiative_data.description is not None:
        initiative.description = initiative_data.description
    if initiative_data.status_id is not None:
        initiative.status_id = initiative_data.status_id
    if initiative_data.priority_id is not None:
        initiative.priority_id = initiative_data.priority_id
    if initiative_data.start_date is not None:
        initiative.start_date = initiative_data.start_date
    if initiative_data.end_date is not None:
        initiative.end_date = initiative_data.end_date
    if initiative_data.budget_estimate is not None:
        initiative.budget_estimate = initiative_data.budget_estimate
    if initiative_data.is_active is not None:
        initiative.is_active = initiative_data.is_active
    
    initiative.updated_at = datetime.now()
    db.commit()
    db.refresh(initiative)
    
    program = db.query(Program).filter(Program.program_id == initiative.program_id).first()
    status = db.query(DictStatus).filter(DictStatus.status_id == initiative.status_id).first()
    priority = db.query(DictPriority).filter(DictPriority.priority_id == initiative.priority_id).first()
    major_tasks_count = db.query(MajorTask).filter(
        MajorTask.initiative_id == initiative.initiative_id,
        MajorTask.is_active == True
    ).count()
    
    return InitiativeResponse(
        initiative_id=initiative.initiative_id,
        program_id=initiative.program_id,
        program_name=program.name if program else None,
        name=initiative.name,
        description=initiative.description,
        status_id=initiative.status_id,
        status_name=status.name_ar if status else None,
        priority_id=initiative.priority_id,
        priority_name=priority.name_ar if priority else None,
        start_date=initiative.start_date,
        end_date=initiative.end_date,
        budget_estimate=initiative.budget_estimate,
        is_active=initiative.is_active,
        created_at=initiative.created_at,
        updated_at=initiative.updated_at,
        major_tasks_count=major_tasks_count
    )

@router.delete("/initiatives/{initiative_id}")
async def delete_initiative(
    initiative_id: int,
    db: Session = Depends(get_db),
    current_user: Employee = Depends(has_role(["وزير / قيادة العليا", "Super Admin"]))
):
    """حذف مبادرة (إذا لم تكن مرتبطة بمهام كبرى)"""
    initiative = db.query(Initiative).filter(Initiative.initiative_id == initiative_id).first()
    if not initiative:
        raise HTTPException(status_code=404, detail="المبادرة غير موجودة")
    
    major_tasks_count = db.query(MajorTask).filter(MajorTask.initiative_id == initiative_id).count()
    if major_tasks_count > 0:
        raise HTTPException(status_code=400, detail=f"لا يمكن الحذف: مرتبطة بـ {major_tasks_count} مهام كبرى")
    
    db.delete(initiative)
    db.commit()
    return {"message": "تم حذف المبادرة بنجاح"}

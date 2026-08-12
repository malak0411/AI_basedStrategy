from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session
from app.database import get_db
from app.models import StrategicPillar, StrategicGoal, Program, Initiative, StrategicVision, SWOTAnalysis, PESTELAnalysis, Department, MajorTask, MajorTaskDepartment
from pydantic import BaseModel, field_validator
from datetime import date as date_type
from typing import Optional


router = APIRouter(prefix="/api/strategic", tags=["Strategic"])

class PillarCreate(BaseModel):
    name: str
    description: Optional[str] = ""
    order_index: Optional[int] = 0
    is_active: Optional[bool] = True

class GoalCreate(BaseModel):
    pillar_id: int
    title: str
    description: Optional[str] = ""
    target_date: Optional[date_type] = None
    valid_from: Optional[date_type] = None
    valid_until: Optional[date_type] = None
    created_by: Optional[int] = None
    

class ProgramCreate(BaseModel):
    goal_id: int
    name: str
    description: Optional[str] = ""
    budget_estimate: Optional[float] = 0
    start_date: Optional[date_type] = None
    end_date: Optional[date_type] = None
    status_id: Optional[int] = None

class InitiativeCreate(BaseModel):
    program_id: int
    name: str
    description: Optional[str] = ""
    priority_id: Optional[int] = 2
    start_date: Optional[date_type] = None
    end_date: Optional[date_type] = None
    budget_estimate: Optional[float] = 0

# ============================================================
# PILLARS - GET/POST/PUT/DELETE
# ============================================================

@router.get("/pillars")
async def get_pillars(db: Session = Depends(get_db)):
    """جميع الركائز الاستراتيجية"""
    try:
        pillars = db.query(StrategicPillar).all()
        result = [{
            "id": p.pillar_id,
            "name": p.name,
            "title": p.name,
            "description": p.description or "",
            "order_index": p.order_index or 0,
            "is_active": p.is_active if p.is_active is not None else True
        } for p in pillars]
        return {"success": True, "data": result}
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")

@router.post("/pillars")
async def create_pillar(data: PillarCreate, db: Session = Depends(get_db)):
    """إنشاء ركيزة جديدة"""
    try:
        pillar = StrategicPillar(
            name=data.name,
            description=data.description,
            order_index=data.order_index,
            is_active=data.is_active
        )
        db.add(pillar)
        db.commit()
        db.refresh(pillar)
        return {"success": True, "data": {"id": pillar.pillar_id, "name": pillar.name}}
    except Exception as e:
        db.rollback()
        raise HTTPException(status_code=500, detail=str(e))

@router.put("/pillars/{pillar_id}")
async def update_pillar(pillar_id: int, data: PillarCreate, db: Session = Depends(get_db)):
    """تحديث ركيزة"""
    try:
        pillar = db.query(StrategicPillar).filter(StrategicPillar.pillar_id == pillar_id).first()
        if not pillar:
            raise HTTPException(status_code=404, detail="غير موجودة")
        pillar.name = data.name
        pillar.description = data.description
        pillar.order_index = data.order_index
        pillar.is_active = data.is_active
        db.commit()
        return {"success": True, "message": "تم تحديث الركيزة"}
    except HTTPException:
        raise
    except Exception as e:
        db.rollback()
        raise HTTPException(status_code=500, detail=str(e))



# ============================================================
# GOALS - GET/POST/PUT/DELETE
# ============================================================

@router.get("/goals")
async def get_goals(db: Session = Depends(get_db)):
    """جميع الأهداف الاستراتيجية"""
    try:
        goals = db.query(StrategicGoal).all()
        result = []
        for g in goals:
            pillar_name = None
            try:
                pillar_name = g.pillar.name if g.pillar else None
            except:
                pass
            result.append({
                "id": g.goal_id,
                "name": g.title,
                "title": g.title,
                "description": g.description or "",
                "pillar_name": pillar_name,
                "pillar_id": g.pillar_id,
                "progress": 0,
                "status": "active" if g.is_active else "inactive",
                "start_date": g.valid_from.isoformat() if g.valid_from else None,
                "end_date": g.valid_until.isoformat() if g.valid_until else None
            })
        return {"success": True, "data": result}
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")

@router.get("/goals/{goal_id}")
async def get_goal(goal_id: int, db: Session = Depends(get_db)):
    """تفاصيل هدف"""
    try:
        g = db.query(StrategicGoal).filter(StrategicGoal.goal_id == goal_id).first()
        if not g:
            raise HTTPException(status_code=404, detail="الهدف غير موجود")
        pillar_name = None
        try:
            pillar_name = g.pillar.name if g.pillar else None
        except:
            pass
        return {
            "success": True,
            "data": {
                "id": g.goal_id, "name": g.title, "title": g.title,
                "description": g.description or "",
                "pillar_name": pillar_name,
                "pillar_id": g.pillar_id,
                "progress": 0,
                "weight": g.weight if g.weight is not None else 0,
                "status": "active" if g.is_active else "inactive",
                "start_date": g.valid_from.isoformat() if g.valid_from else None,
                "end_date": g.valid_until.isoformat() if g.valid_until else None,
                "target_date": g.target_date.isoformat() if g.target_date else None,
            }
        }
    except HTTPException:
        raise
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")

@router.post("/goals")
async def create_goal(data: GoalCreate, db: Session = Depends(get_db)):
    """إنشاء هدف جديد"""
    try:
        goal = StrategicGoal(
            pillar_id=data.pillar_id, title=data.title,
            description=data.description, target_date=data.target_date,
            valid_from=data.valid_from, valid_until=data.valid_until, is_active=True
        )
        db.add(goal)
        db.commit()
        db.refresh(goal)
        return {"success": True, "data": {"id": goal.goal_id, "title": goal.title}}
    except Exception as e:
        db.rollback()
        raise HTTPException(status_code=500, detail=str(e))

@router.put("/goals/{goal_id}")
async def update_goal(goal_id: int, data: GoalCreate, db: Session = Depends(get_db)):
    """تحديث هدف"""
    try:
        goal = db.query(StrategicGoal).filter(StrategicGoal.goal_id == goal_id).first()
        if not goal:
            raise HTTPException(status_code=404, detail="غير موجود")
        goal.pillar_id = data.pillar_id
        goal.title = data.title
        goal.description = data.description
        goal.target_date = data.target_date
        goal.valid_from = data.valid_from
        goal.valid_until = data.valid_until
        db.commit()
        return {"success": True, "message": "تم تحديث الهدف"}
    except HTTPException:
        raise
    except Exception as e:
        db.rollback()
        raise HTTPException(status_code=500, detail=str(e))


# ============================================================
# PROGRAMS - GET/POST/PUT/DELETE
# ============================================================

@router.get("/programs")
async def get_programs(db: Session = Depends(get_db)):
    """جميع البرامج"""
    try:
        programs = db.query(Program).all()
        result = []
        for p in programs:
            goal_name = None
            try:
                goal_name = p.goal.title if p.goal else None
            except:
                pass
            result.append({
                "id": p.program_id, "name": p.name, "title": p.name,
                "description": p.description or "", "goal_name": goal_name,
                "goal_id": p.goal_id,
                "budget": float(p.budget_estimate) if p.budget_estimate else 0,
                "progress": 0,
                "start_date": p.start_date.isoformat() if p.start_date else None,
                "end_date": p.end_date.isoformat() if p.end_date else None
            })
        return {"success": True, "data": result}
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")

@router.get("/programs/{program_id}")
async def get_program(program_id: int, db: Session = Depends(get_db)):
    """تفاصيل برنامج"""
    try:
        p = db.query(Program).filter(Program.program_id == program_id).first()
        if not p:
            raise HTTPException(status_code=404, detail="غير موجود")
        goal_name = None
        try:
            goal_name = p.goal.title if p.goal else None
        except:
            pass
        return {
            "success": True,
            "data": {
                "id": p.program_id, "name": p.name, "title": p.name,
                "description": p.description or "", "goal_name": goal_name,
                "goal_id": p.goal_id,
                "budget": float(p.budget_estimate) if p.budget_estimate else 0,
                "progress": 0,
                "start_date": p.start_date.isoformat() if p.start_date else None,
                "end_date": p.end_date.isoformat() if p.end_date else None
            }
        }
    except HTTPException:
        raise
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")

@router.post("/programs")
async def create_program(data: ProgramCreate, db: Session = Depends(get_db)):
    """إنشاء برنامج"""
    try:
        program = Program(
            goal_id=data.goal_id, name=data.name, description=data.description,
            budget_estimate=data.budget_estimate, start_date=data.start_date,
            end_date=data.end_date, status_id=data.status_id, is_active=True
        )
        db.add(program)
        db.commit()
        db.refresh(program)
        return {"success": True, "data": {"id": program.program_id, "name": program.name}}
    except Exception as e:
        db.rollback()
        raise HTTPException(status_code=500, detail=str(e))

@router.put("/programs/{program_id}")
async def update_program(program_id: int, data: ProgramCreate, db: Session = Depends(get_db)):
    """تحديث برنامج"""
    try:
        program = db.query(Program).filter(Program.program_id == program_id).first()
        if not program:
            raise HTTPException(status_code=404, detail="غير موجود")
        program.goal_id = data.goal_id
        program.name = data.name
        program.description = data.description
        program.budget_estimate = data.budget_estimate
        program.start_date = data.start_date
        program.end_date = data.end_date
        program.status_id = data.status_id
        db.commit()
        return {"success": True, "message": "تم تحديث البرنامج"}
    except HTTPException:
        raise
    except Exception as e:
        db.rollback()
        raise HTTPException(status_code=500, detail=str(e))


# ============================================================
# INITIATIVES - GET/POST/PUT/DELETE
# ============================================================

@router.get("/initiatives")
async def get_initiatives(db: Session = Depends(get_db)):
    """جميع المبادرات"""
    try:
        initiatives = db.query(Initiative).all()
        result = []
        for i in initiatives:
            program_name = None
            try:
                program_name = i.program.name if i.program else None
            except:
                pass
            result.append({
                "id": i.initiative_id, "name": i.name, "title": i.name,
                "description": i.description or "", "program_name": program_name,
                "program_id": i.program_id, "status": i.status_id,
                "priority": i.priority_id, "progress": 0,
                "start_date": i.start_date.isoformat() if i.start_date else None,
                "end_date": i.end_date.isoformat() if i.end_date else None
            })
        return {"success": True, "data": result}
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")

@router.get("/initiatives/{initiative_id}")
async def get_initiative(initiative_id: int, db: Session = Depends(get_db)):
    """تفاصيل مبادرة"""
    try:
        i = db.query(Initiative).filter(Initiative.initiative_id == initiative_id).first()
        if not i:
            raise HTTPException(status_code=404, detail="غير موجودة")
        program_name = None
        try:
            program_name = i.program.name if i.program else None
        except:
            pass
        return {
            "success": True,
            "data": {
                "id": i.initiative_id, "name": i.name, "title": i.name,
                "description": i.description or "", "program_name": program_name,
                "program_id": i.program_id, "status": i.status_id,
                "priority": i.priority_id, "progress": 0,
                "start_date": i.start_date.isoformat() if i.start_date else None,
                "end_date": i.end_date.isoformat() if i.end_date else None
            }
        }
    except HTTPException:
        raise
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")

@router.post("/initiatives")
async def create_initiative(data: InitiativeCreate, db: Session = Depends(get_db)):
    """إنشاء مبادرة"""
    try:
        initiative = Initiative(
            program_id=data.program_id, name=data.name, description=data.description,
            priority_id=data.priority_id, start_date=data.start_date,
            end_date=data.end_date, budget_estimate=data.budget_estimate,
            status_id=5, is_active=True
        )
        db.add(initiative)
        db.commit()
        db.refresh(initiative)
        return {"success": True, "data": {"id": initiative.initiative_id, "name": initiative.name}}
    except Exception as e:
        db.rollback()
        raise HTTPException(status_code=500, detail=str(e))

@router.put("/initiatives/{initiative_id}")
async def update_initiative(initiative_id: int, data: InitiativeCreate, db: Session = Depends(get_db)):
    """تحديث مبادرة"""
    try:
        initiative = db.query(Initiative).filter(Initiative.initiative_id == initiative_id).first()
        if not initiative:
            raise HTTPException(status_code=404, detail="غير موجودة")
        initiative.program_id = data.program_id
        initiative.name = data.name
        initiative.description = data.description
        initiative.priority_id = data.priority_id
        initiative.start_date = data.start_date
        initiative.end_date = data.end_date
        initiative.budget_estimate = data.budget_estimate
        db.commit()
        return {"success": True, "message": "تم تحديث المبادرة"}
    except HTTPException:
        raise
    except Exception as e:
        db.rollback()
        raise HTTPException(status_code=500, detail=str(e))


# ============================================================
# VISION - GET/PUT
# ============================================================

@router.get("/vision")
async def get_vision(db: Session = Depends(get_db)):
    """الرؤية الحالية"""
    try:
        vision = db.query(StrategicVision).filter(StrategicVision.is_current == True).first()
        if not vision:
            return {"success": True, "data": {"text": "", "description": "", "version": 1}}
        return {"success": True, "data": {"text": vision.text, "description": vision.description or "", "version": vision.version}}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@router.put("/vision")
async def update_vision(data: dict, db: Session = Depends(get_db)):
    """تحديث الرؤية"""
    try:
        vision = db.query(StrategicVision).filter(StrategicVision.is_current == True).first()
        if vision:
            vision.text = data.get('text', vision.text)
            vision.description = data.get('description', vision.description)
        else:
            vision = StrategicVision(text=data.get('text', ''), description=data.get('description', ''), is_current=True, effective_date=date_type.today())
            db.add(vision)
        db.commit()
        return {"success": True, "message": "تم تحديث الرؤية"}
    except Exception as e:
        db.rollback()
        raise HTTPException(status_code=500, detail=str(e))

# ============================================================
# SWOT - GET/PUT
# ============================================================

@router.get("/swot")
async def get_swot(db: Session = Depends(get_db)):
    """تحليل SWOT"""
    try:
        swot = db.query(SWOTAnalysis).first()
        if swot:
            return {"success": True, "data": {"strengths": swot.strengths or "", "weaknesses": swot.weaknesses or "", "opportunities": swot.opportunities or "", "threats": swot.threats or ""}}
        return {"success": True, "data": {"strengths": "", "weaknesses": "", "opportunities": "", "threats": ""}}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@router.put("/swot")
async def update_swot(data: dict, db: Session = Depends(get_db)):
    """تحديث SWOT"""
    try:
        swot = db.query(SWOTAnalysis).first()
        if swot:
            swot.strengths = data.get('strengths', swot.strengths)
            swot.weaknesses = data.get('weaknesses', swot.weaknesses)
            swot.opportunities = data.get('opportunities', swot.opportunities)
            swot.threats = data.get('threats', swot.threats)
        else:
            swot = SWOTAnalysis(strengths=data.get('strengths',''), weaknesses=data.get('weaknesses',''), opportunities=data.get('opportunities',''), threats=data.get('threats',''), analysis_date=date_type.today())
            db.add(swot)
        db.commit()
        return {"success": True, "message": "تم تحديث SWOT"}
    except Exception as e:
        db.rollback()
        raise HTTPException(status_code=500, detail=str(e))

# ============================================================
# PESTEL - GET/PUT
# ============================================================

@router.get("/pestel")
async def get_pestel(db: Session = Depends(get_db)):
    """تحليل PESTEL"""
    try:
        pestel = db.query(PESTELAnalysis).first()
        if pestel:
            return {"success": True, "data": {"political": pestel.political or "", "economic": pestel.economic or "", "social": pestel.social or "", "technological": pestel.technological or "", "environmental": pestel.environmental or "", "legal": pestel.legal or ""}}
        return {"success": True, "data": {"political": "", "economic": "", "social": "", "technological": "", "environmental": "", "legal": ""}}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@router.get("/initiatives/{initiative_id}/major-tasks")
async def get_initiative_major_tasks(initiative_id: int, db: Session = Depends(get_db)):
    try:
        tasks = db.query(MajorTask).filter(
            MajorTask.initiative_id == initiative_id,
            MajorTask.is_active == True
        ).all()
        
        result = []
        for task in tasks:
            depts = db.query(MajorTaskDepartment).filter(
                MajorTaskDepartment.major_task_id == task.major_task_id
            ).all()
            
            dept_list = []
            for d in depts:
                department = db.query(Department).filter(Department.department_id == d.department_id).first()
                dept_list.append({
                    "department_id": d.department_id,
                    "department_name": department.name if department else f"قسم #{d.department_id}",
                    "responsibility_type": d.responsibility_type or "SUPPORT",
                    "notes": d.notes or ""
                })
            
            result.append({
                "id": task.major_task_id,
                "name": task.name or "",
                "description": task.description or "",
                "priority_id": task.priority_id or 2,
                "estimated_duration_days": task.estimated_duration_days or 30,
                "is_cross_department": bool(task.is_cross_department),
                "departments": dept_list,
                "created_at": task.created_at.isoformat() if task.created_at else None
            })
        
        return {"success": True, "data": result}
    except Exception as e:
        import traceback
        traceback.print_exc()
        raise HTTPException(status_code=500, detail=str(e))



@router.post("/initiatives/{initiative_id}/major-tasks")
async def create_major_task_manual(initiative_id: int, data: dict, db: Session = Depends(get_db)):
    try:
        task = MajorTask(
            initiative_id=initiative_id,
            name=data.get("name"),
            description=data.get("description", ""),
            priority_id=data.get("priority_id", 2),
            estimated_duration_days=data.get("estimated_duration_days", 30),
            is_cross_department=data.get("is_cross_department", False),
            is_active=True
        )
        db.add(task)
        db.flush()
        
        for dept in data.get("departments", []):
            mtd = MajorTaskDepartment(
                major_task_id=task.major_task_id,
                department_id=dept.get("department_id"),
                responsibility_type=dept.get("responsibility_type", "SUPPORT"),
                notes=dept.get("notes", "")
            )
            db.add(mtd)
        
        db.commit()
        return {"success": True, "data": {"id": task.major_task_id, "name": task.name}}
    except Exception as e:
        db.rollback()
        raise HTTPException(status_code=500, detail=str(e))


@router.put("/major-tasks/{task_id}")
async def update_major_task(task_id: int, data: dict, db: Session = Depends(get_db)):
    try:
        task = db.query(MajorTask).filter(MajorTask.major_task_id == task_id).first()
        if not task:
            raise HTTPException(status_code=404, detail="Task not found")
        
        if "name" in data: task.name = data["name"]
        if "description" in data: task.description = data["description"]
        if "priority_id" in data: task.priority_id = data["priority_id"]
        if "estimated_duration_days" in data: task.estimated_duration_days = data["estimated_duration_days"]
        if "is_cross_department" in data: task.is_cross_department = data["is_cross_department"]
        
        db.commit()
        return {"success": True, "message": "Updated"}
    except Exception as e:
        db.rollback()
        raise HTTPException(status_code=500, detail=str(e))





@router.put("/pestel")
async def update_pestel(data: dict, db: Session = Depends(get_db)):
    """تحديث PESTEL"""
    try:
        pestel = db.query(PESTELAnalysis).first()
        if pestel:
            pestel.political = data.get('political', pestel.political)
            pestel.economic = data.get('economic', pestel.economic)
            pestel.social = data.get('social', pestel.social)
            pestel.technological = data.get('technological', pestel.technological)
            pestel.environmental = data.get('environmental', pestel.environmental)
            pestel.legal = data.get('legal', pestel.legal)
        else:
            pestel = PESTELAnalysis(political=data.get('political',''), economic=data.get('economic',''), social=data.get('social',''), technological=data.get('technological',''), environmental=data.get('environmental',''), legal=data.get('legal',''), analysis_date=date_type.today())
            db.add(pestel)
        db.commit()
        return {"success": True, "message": "تم تحديث PESTEL"}
    except Exception as e:
        db.rollback()
        raise HTTPException(status_code=500, detail=str(e))

@router.get("/pillars/{pillar_id}/details")
async def get_pillar_details(pillar_id: int, db: Session = Depends(get_db)):
    try:
        pillar = db.query(StrategicPillar).filter(StrategicPillar.pillar_id == pillar_id).first()
        if not pillar:
            raise HTTPException(status_code=404, detail="Pillar not found")
        
        goals = db.query(StrategicGoal).filter(
            StrategicGoal.pillar_id == pillar_id,
            StrategicGoal.is_active == True
        ).all()
        
        return {
            "success": True,
            "data": {
                "id": pillar.pillar_id,
                "name": pillar.name,
                "description": pillar.description or "",
                "order_index": pillar.order_index,
                "is_active": pillar.is_active,
                "goals": [{
                    "id": g.goal_id,
                    "title": g.title,
                    "description": g.description or "",
                    "target_date": g.target_date.isoformat() if g.target_date else None
                } for g in goals]
            }
        }
    except HTTPException: raise
    except Exception as e: raise HTTPException(status_code=500, detail=str(e))


@router.get("/goals/{goal_id}/details")
async def get_goal_details(goal_id: int, db: Session = Depends(get_db)):
    try:
        goal = db.query(StrategicGoal).filter(StrategicGoal.goal_id == goal_id).first()
        if not goal:
            raise HTTPException(status_code=404, detail="Goal not found")
        
        programs = db.query(Program).filter(
            Program.goal_id == goal_id,
            Program.is_active == True
        ).all()
        
        return {
            "success": True,
            "data": {
                "id": goal.goal_id,
                "title": goal.title,
                "description": goal.description or "",
                "pillar_name": goal.pillar.name if goal.pillar else "",
                "pillar_id": goal.pillar_id,
                "weight": goal.weight if goal.weight is not None else 0,
                "status": "active" if goal.is_active else "inactive",
                "start_date": goal.valid_from.isoformat() if goal.valid_from else None,
                "end_date": goal.valid_until.isoformat() if goal.valid_until else None,
                "target_date": goal.target_date.isoformat() if goal.target_date else None,
                "programs": [{
                    "id": p.program_id,
                    "name": p.name,
                    "description": p.description or "",
                    "budget_estimate": float(p.budget_estimate or 0),
                    "start_date": p.start_date.isoformat() if p.start_date else None,
                    "end_date": p.end_date.isoformat() if p.end_date else None
                } for p in programs]
            }
        }
    except HTTPException: raise
    except Exception as e: raise HTTPException(status_code=500, detail=str(e))


@router.get("/programs/{program_id}/details")
async def get_program_details(program_id: int, db: Session = Depends(get_db)):
    try:
        program = db.query(Program).filter(Program.program_id == program_id).first()
        if not program:
            raise HTTPException(status_code=404, detail="Program not found")
        
        initiatives = db.query(Initiative).filter(
            Initiative.program_id == program_id,
            Initiative.is_active == True
        ).all()
        
        return {
            "success": True,
            "data": {
                "id": program.program_id,
                "name": program.name,
                "description": program.description or "",
                "goal_id": program.goal_id,
                "goal_name": program.goal.title if program.goal else "",
                "budget_estimate": float(program.budget_estimate or 0),
                "start_date": program.start_date.isoformat() if program.start_date else None,
                "end_date": program.end_date.isoformat() if program.end_date else None,
                "initiatives": [{
                    "id": i.initiative_id,
                    "name": i.name,
                    "description": i.description or "",
                    "priority_id": i.priority_id,
                    "start_date": i.start_date.isoformat() if i.start_date else None,
                    "end_date": i.end_date.isoformat() if i.end_date else None,
                    "budget_estimate": float(i.budget_estimate or 0)
                } for i in initiatives]
            }
        }
    except HTTPException: raise
    except Exception as e: raise HTTPException(status_code=500, detail=str(e))

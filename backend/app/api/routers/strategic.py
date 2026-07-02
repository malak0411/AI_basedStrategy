from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session
from app.database import get_db
from app.models import StrategicPillar, StrategicGoal, Program, Initiative

router = APIRouter(prefix="/api/strategic", tags=["Strategic"])

@router.get("/pillars")
async def get_pillars(db: Session = Depends(get_db)):
    """الركائز الاستراتيجية"""
    try:
        pillars = db.query(StrategicPillar).all()
        result = [{
            "id": p.pillar_id,
            "name": p.name,
            "title": p.name,
            "description": p.description or ""
        } for p in pillars]
        return {"success": True, "data": result}
    except Exception as e:
        print(f"Error in pillars: {str(e)}")
        raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")

@router.get("/goals")
async def get_goals(db: Session = Depends(get_db)):
    """الأهداف الاستراتيجية"""
    try:
        goals = db.query(StrategicGoal).all()
        result = []
        for g in goals:
            try:
                pillar_name = g.pillar.name if g.pillar else None
            except:
                pillar_name = None
            
            result.append({
                "id": g.goal_id,
                "name": g.title,
                "title": g.title,
                "description": g.description or "",
                "pillar_name": pillar_name,
                "pillar_id": g.pillar_id,
                "progress": 0,  # لا يوجد عمود progress في الجدول
                "status": "active" if g.is_active else "inactive",
                "start_date": g.valid_from.isoformat() if g.valid_from else None,
                "end_date": g.valid_until.isoformat() if g.valid_until else None
            })
        return {"success": True, "data": result}
    except Exception as e:
        print(f"Error in goals: {str(e)}")
        raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")

@router.get("/goals/{goal_id}")
async def get_goal(goal_id: int, db: Session = Depends(get_db)):
    """تفاصيل هدف"""
    try:
        g = db.query(StrategicGoal).filter(StrategicGoal.goal_id == goal_id).first()
        if not g:
            raise HTTPException(status_code=404, detail="الهدف غير موجود")
        
        try:
            pillar_name = g.pillar.name if g.pillar else None
        except:
            pillar_name = None

        return {
            "success": True,
            "data": {
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
            }
        }
    except HTTPException:
        raise
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")

@router.get("/programs")
async def get_programs(db: Session = Depends(get_db)):
    """البرامج"""
    try:
        programs = db.query(Program).all()
        result = []
        for p in programs:
            try:
                goal_name = p.goal.title if p.goal else None
            except:
                goal_name = None

            result.append({
                "id": p.program_id,
                "name": p.name,
                "title": p.name,
                "description": p.description or "",
                "goal_name": goal_name,
                "goal_id": p.goal_id,
                "budget": float(p.budget_estimate) if p.budget_estimate else 0,
                "progress": 0,
                "start_date": p.start_date.isoformat() if p.start_date else None,
                "end_date": p.end_date.isoformat() if p.end_date else None
            })
        return {"success": True, "data": result}
    except Exception as e:
        print(f"Error in programs: {str(e)}")
        raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")

@router.get("/programs/{program_id}")
async def get_program(program_id: int, db: Session = Depends(get_db)):
    """تفاصيل برنامج"""
    try:
        p = db.query(Program).filter(Program.program_id == program_id).first()
        if not p:
            raise HTTPException(status_code=404, detail="البرنامج غير موجود")

        try:
            goal_name = p.goal.title if p.goal else None
        except:
            goal_name = None

        return {
            "success": True,
            "data": {
                "id": p.program_id,
                "name": p.name,
                "title": p.name,
                "description": p.description or "",
                "goal_name": goal_name,
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

@router.get("/initiatives")
async def get_initiatives(db: Session = Depends(get_db)):
    """المبادرات"""
    try:
        initiatives = db.query(Initiative).all()
        result = []
        for i in initiatives:
            try:
                program_name = i.program.name if i.program else None
            except:
                program_name = None

            result.append({
                "id": i.initiative_id,
                "name": i.name,
                "title": i.name,
                "description": i.description or "",
                "program_name": program_name,
                "program_id": i.program_id,
                "status": i.status_id,
                "priority": i.priority_id,
                "progress": 0,
                "start_date": i.start_date.isoformat() if i.start_date else None,
                "end_date": i.end_date.isoformat() if i.end_date else None
            })
        return {"success": True, "data": result}
    except Exception as e:
        print(f"Error in initiatives: {str(e)}")
        raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")

@router.get("/initiatives/{initiative_id}")
async def get_initiative(initiative_id: int, db: Session = Depends(get_db)):
    """تفاصيل مبادرة"""
    try:
        i = db.query(Initiative).filter(Initiative.initiative_id == initiative_id).first()
        if not i:
            raise HTTPException(status_code=404, detail="المبادرة غير موجودة")

        try:
            program_name = i.program.name if i.program else None
        except:
            program_name = None

        return {
            "success": True,
            "data": {
                "id": i.initiative_id,
                "name": i.name,
                "title": i.name,
                "description": i.description or "",
                "program_name": program_name,
                "program_id": i.program_id,
                "status": i.status_id,
                "priority": i.priority_id,
                "progress": 0,
                "start_date": i.start_date.isoformat() if i.start_date else None,
                "end_date": i.end_date.isoformat() if i.end_date else None
            }
        }
    except HTTPException:
        raise
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")

@router.get("/swot")
async def get_swot():
    return {"success": True, "data": {"strengths": [], "weaknesses": [], "opportunities": [], "threats": []}}

@router.get("/pestel")
async def get_pestel():
    return {"success": True, "data": {"political": [], "economic": [], "social": [], "technological": [], "environmental": [], "legal": []}}

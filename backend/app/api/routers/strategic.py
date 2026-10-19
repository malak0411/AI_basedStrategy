from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session
from app.database import get_db
from app.models import KPI, BudgetLine, BudgetTransaction, DictStatus, DictTransactionType, Employee, GoalKPI, GoalKPI, KPIMeasurement, OperationalTask, StrategicPillar, StrategicGoal, Program, Initiative, StrategicVision, SWOTAnalysis, PESTELAnalysis, Department, MajorTask, MajorTaskDepartment
from pydantic import BaseModel, field_validator
from datetime import date as date_type ,datetime
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

INIT_STATUS_DRAFT = 1
INIT_STATUS_IN_PROGRESS = 2
INIT_STATUS_COMPLETED = 3
INIT_STATUS_CANCELLED = 4
INIT_STATUS_APPROVED = 17
INIT_STATUS_PENDING = 45


PROG_STATUS_PENDING = 45
PROG_STATUS_DRAFT = 46
PROG_STATUS_IN_PROGRESS = 47
PROG_STATUS_COMPLETED = 48
PROG_STATUS_CANCELLED = 49


TASK_STATUS_COMPLETED = 3


def _serialize_pillar(p: StrategicPillar, db: Session, full: bool = False):
    goals = db.query(StrategicGoal).filter(
        StrategicGoal.pillar_id == p.pillar_id,
        StrategicGoal.is_active == True
    ).order_by(StrategicGoal.goal_id).all()


    goals_data = []
    total_goals_progress = 0.0
    total_kpi_progress = 0.0
    total_programs = 0
    total_initiatives = 0
    total_major_tasks = 0
    total_ops = 0
    total_ops_completed = 0
    total_kpis = 0
    total_kpis_achieved = 0
    total_kpis_at_risk = 0
    weighted_goal_sum = 0.0
    weight_sum = 0.0


    for g in goals:
        g_data = _serialize_goal(g, db, full=False)


        total_goals_progress += g_data["progress"]
        total_kpi_progress += g_data["kpi_progress"]
        total_programs += g_data["programs_count"]
        total_initiatives += g_data["initiatives_count"]
        total_major_tasks += g_data["major_tasks_count"]
        total_ops += g_data["total_operational_tasks"]
        total_ops_completed += g_data["total_operational_completed"]
        total_kpis += g_data["kpis_count"]
        total_kpis_achieved += g_data["kpis_achieved"]
        total_kpis_at_risk += g_data["kpis_at_risk"]


        w = g_data.get("weight") or 1
        weighted_goal_sum += g_data["progress"] * w
        weight_sum += w


        if full:
            goals_data.append(g_data)


    goals_count = len(goals)
    pillar_progress = round(total_goals_progress / goals_count, 2) if goals_count > 0 else 0
    pillar_kpi_progress = round(total_kpi_progress / goals_count, 2) if goals_count > 0 else 0
    weighted_progress = round(weighted_goal_sum / weight_sum, 2) if weight_sum > 0 else 0


    data = {
        "id": p.pillar_id,
        "name": p.name,
        "title": p.name,
        "description": p.description or "",
        "order_index": p.order_index or 0,
        "is_active": bool(p.is_active),
        "progress": pillar_progress,
        "weighted_progress": weighted_progress,
        "kpi_progress": pillar_kpi_progress,
        "goals_count": goals_count,
        "programs_count": total_programs,
        "initiatives_count": total_initiatives,
        "major_tasks_count": total_major_tasks,
        "total_operational_tasks": total_ops,
        "total_operational_completed": total_ops_completed,
        "kpis_count": total_kpis,
        "kpis_achieved": total_kpis_achieved,
        "kpis_at_risk": total_kpis_at_risk,
    }


    if full:
        data["goals"] = goals_data


    return data




@router.get("/pillars")
async def get_pillars(db: Session = Depends(get_db)):
    try:
        pillars = db.query(StrategicPillar).filter(
            StrategicPillar.is_active == True
        ).order_by(StrategicPillar.order_index).all()
        result = [_serialize_pillar(p, db, full=False) for p in pillars]
        return {"success": True, "data": result}
    except Exception as e:
        import traceback
        traceback.print_exc()
        raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")




@router.get("/pillars/{pillar_id}")
async def get_pillar(pillar_id: int, db: Session = Depends(get_db)):
    try:
        p = db.query(StrategicPillar).filter(StrategicPillar.pillar_id == pillar_id).first()
        if not p:
            raise HTTPException(status_code=404, detail="الركيزة غير موجودة")
        return {"success": True, "data": _serialize_pillar(p, db, full=True)}
    except HTTPException:
        raise
    except Exception as e:
        import traceback
        traceback.print_exc()
        raise HTTPException(status_code=500, detail=str(e))




@router.post("/pillars")
async def create_pillar(data: PillarCreate, db: Session = Depends(get_db)):
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


def _serialize_kpi(goal_kpi: GoalKPI, kpi: KPI, db: Session):
    measurements = db.query(KPIMeasurement).filter(
        KPIMeasurement.kpi_id == kpi.kpi_id
    ).order_by(KPIMeasurement.measured_at.desc()).all()


    latest_value = None
    last_update = None
    if measurements:
        latest_value = float(measurements[0].value) if measurements[0].value is not None else None
        last_update = measurements[0].measured_at.isoformat() if measurements[0].measured_at else None


    target = float(goal_kpi.target_value) if goal_kpi.target_value is not None else None
    baseline = float(goal_kpi.baseline_value) if goal_kpi.baseline_value is not None else 0.0


    achievement = None
    if target is not None and latest_value is not None:
        if target != baseline:
            achievement = round(((latest_value - baseline) / (target - baseline)) * 100, 2)
            achievement = max(0, min(achievement, 150))
        elif latest_value >= target:
            achievement = 100.0
        else:
            achievement = 0.0


    trend = "stable"
    if len(measurements) >= 2:
        current = float(measurements[0].value or 0)
        previous = float(measurements[1].value or 0)
        if current > previous:
            trend = "up"
        elif current < previous:
            trend = "down"


    status = "on_track"
    if achievement is None:
        status = "no_data"
    elif achievement >= 100:
        status = "achieved"
    elif achievement >= 70:
        status = "on_track"
    elif achievement >= 40:
        status = "at_risk"
    else:
        status = "off_track"


    return {
        "kpi_id": kpi.kpi_id,
        "name": kpi.name,
        "description": kpi.description or "",
        "category": kpi.category or "",
        "unit": kpi.unit or "",
        "target_min": float(kpi.target_min) if kpi.target_min is not None else None,
        "target_max": float(kpi.target_max) if kpi.target_max is not None else None,
        "calculation_method": kpi.calculation_method or "",
        "target_value": target,
        "baseline_value": baseline,
        "weight": float(goal_kpi.weight) if goal_kpi.weight is not None else 1.0,
        "current_value": latest_value,
        "achievement": achievement,
        "trend": trend,
        "status": status,
        "last_update": last_update,
        "measurements_count": len(measurements),
    }




def _serialize_goal(g: StrategicGoal, db: Session, full: bool = False):
    pillar_name = None
    pillar_id = None
    try:
        if g.pillar:
            pillar_name = g.pillar.name
            pillar_id = g.pillar.pillar_id
    except Exception:
        pass


    programs = db.query(Program).filter(
        Program.goal_id == g.goal_id,
        Program.is_active == True
    ).all()


    programs_data = []
    total_progress = 0.0
    total_ops = 0
    total_ops_completed = 0
    total_initiatives = 0
    total_major_tasks = 0


    for p in programs:
        initiatives = db.query(Initiative).filter(
            Initiative.program_id == p.program_id,
            Initiative.is_active == True
        ).all()
        total_initiatives += len(initiatives)


        p_ops = 0
        p_ops_completed = 0
        p_progress_sum = 0.0
        p_initiatives_count = 0


        for init in initiatives:
            major_tasks = db.query(MajorTask).filter(
                MajorTask.initiative_id == init.initiative_id,
                MajorTask.is_active == True
            ).all()
            total_major_tasks += len(major_tasks)


            init_ops = 0
            init_progress_sum = 0.0
            for mt in major_tasks:
                mt_progress = _calc_major_task_progress(mt)
                init_ops += mt_progress["total"]
                p_ops_completed += mt_progress["completed"]
                init_progress_sum += mt_progress["progress"] * mt_progress["total"]


            init_progress = round(init_progress_sum / init_ops, 2) if init_ops > 0 else 0
            p_ops += init_ops
            p_progress_sum += init_progress
            p_initiatives_count += 1


        p_progress = round(p_progress_sum / p_initiatives_count, 2) if p_initiatives_count > 0 else 0
        total_ops += p_ops
        total_ops_completed += p_ops_completed
        total_progress += p_progress


        p_status = _get_status_info(db, p.status_id)


        programs_data.append({
            "id": p.program_id,
            "name": p.name,
            "description": p.description or "",
            "status_id": p.status_id,
            "status_name": p_status["name"],
            "status_color": p_status["color"],
            "progress": p_progress,
            "budget_estimate": float(p.budget_estimate or 0),
            "start_date": p.start_date.isoformat() if p.start_date else None,
            "end_date": p.end_date.isoformat() if p.end_date else None,
            "initiatives_count": p_initiatives_count,
            "operational_count": p_ops,
            "operational_completed": p_ops_completed,
        })


    goal_progress = round(total_progress / len(programs), 2) if len(programs) > 0 else 0


    goal_kpis = db.query(GoalKPI).filter(GoalKPI.goal_id == g.goal_id).all()
    kpis_data = []
    kpi_achievement_sum = 0.0
    kpi_weight_sum = 0.0
    kpi_at_risk = 0
    kpi_achieved = 0


    for gk in goal_kpis:
        kpi = db.query(KPI).filter(KPI.kpi_id == gk.kpi_id).first()
        if not kpi:
            continue
        kpi_data = _serialize_kpi(gk, kpi, db)
        kpis_data.append(kpi_data)


        if kpi_data["achievement"] is not None:
            kpi_achievement_sum += kpi_data["achievement"] * kpi_data["weight"]
            kpi_weight_sum += kpi_data["weight"]


        if kpi_data["status"] == "achieved":
            kpi_achieved += 1
        elif kpi_data["status"] in ("at_risk", "off_track"):
            kpi_at_risk += 1


    kpi_overall = round(kpi_achievement_sum / kpi_weight_sum, 2) if kpi_weight_sum > 0 else 0


    days_remaining = None
    if g.target_date:
        try:
            days_remaining = (g.target_date - date_type.today()).days
        except Exception:
            days_remaining = None


    data = {
        "id": g.goal_id,
        "name": g.title,
        "title": g.title,
        "description": g.description or "",
        "pillar_id": pillar_id,
        "pillar_name": pillar_name,
        "weight": g.weight or 0,
        "target_date": g.target_date.isoformat() if g.target_date else None,
        "valid_from": g.valid_from.isoformat() if g.valid_from else None,
        "valid_until": g.valid_until.isoformat() if g.valid_until else None,
        "days_remaining": days_remaining,
        "is_active": bool(g.is_active),
        "is_current": bool(g.is_current),
        "progress": goal_progress,
        "kpi_progress": kpi_overall,
        "programs_count": len(programs),
        "initiatives_count": total_initiatives,
        "major_tasks_count": total_major_tasks,
        "total_operational_tasks": total_ops,
        "total_operational_completed": total_ops_completed,
        "kpis_count": len(kpis_data),
        "kpis_achieved": kpi_achieved,
        "kpis_at_risk": kpi_at_risk,
    }


    if full:
        data["programs"] = programs_data
        data["kpis"] = kpis_data


    return data




@router.get("/goals")
async def get_goals(db: Session = Depends(get_db)):
    try:
        goals = db.query(StrategicGoal).filter(StrategicGoal.is_active == True).all()
        result = [_serialize_goal(g, db, full=False) for g in goals]
        return {"success": True, "data": result}
    except Exception as e:
        import traceback
        traceback.print_exc()
        raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")




@router.get("/goals/{goal_id}")
async def get_goal(goal_id: int, db: Session = Depends(get_db)):
    try:
        g = db.query(StrategicGoal).filter(StrategicGoal.goal_id == goal_id).first()
        if not g:
            raise HTTPException(status_code=404, detail="الهدف غير موجود")
        return {"success": True, "data": _serialize_goal(g, db, full=True)}
    except HTTPException:
        raise
    except Exception as e:
        import traceback
        traceback.print_exc()
        raise HTTPException(status_code=500, detail=str(e))




@router.post("/goals")
async def create_goal(data: GoalCreate, db: Session = Depends(get_db)):
    try:
        goal = StrategicGoal(
            pillar_id=data.pillar_id,
            title=data.title,
            description=data.description,
            target_date=data.target_date,
            valid_from=data.valid_from,
            valid_until=data.valid_until,
            is_active=True,
            is_current=True
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
def _calc_program_budget(program_id: int, db: Session):
    program_lines = db.query(BudgetLine).filter(
        BudgetLine.budgetable_type == "program",
        BudgetLine.budgetable_id == program_id
    ).all()


    initiatives = db.query(Initiative).filter(
        Initiative.program_id == program_id,
        Initiative.is_active == True
    ).all()
    initiative_ids = [i.initiative_id for i in initiatives]


    initiative_lines = []
    if initiative_ids:
        initiative_lines = db.query(BudgetLine).filter(
            BudgetLine.budgetable_type == "initiative",
            BudgetLine.budgetable_id.in_(initiative_ids)
        ).all()


    major_tasks = []
    for init in initiatives:
        mts = db.query(MajorTask).filter(
            MajorTask.initiative_id == init.initiative_id,
            MajorTask.is_active == True
        ).all()
        major_tasks.extend(mts)
    major_task_ids = [mt.major_task_id for mt in major_tasks]


    major_task_lines = []
    if major_task_ids:
        major_task_lines = db.query(BudgetLine).filter(
            BudgetLine.budgetable_type == "major_task",
            BudgetLine.budgetable_id.in_(major_task_ids)
        ).all()


    operational_tasks = []
    for mt in major_tasks:
        ops = db.query(OperationalTask).filter(
            OperationalTask.major_task_id == mt.major_task_id,
            OperationalTask.is_active == True
        ).all()
        operational_tasks.extend(ops)
    op_task_ids = [op.task_id for op in operational_tasks]


    op_lines = []
    if op_task_ids:
        op_lines = db.query(BudgetLine).filter(
            BudgetLine.budgetable_type == "operational_task",
            BudgetLine.budgetable_id.in_(op_task_ids)
        ).all()


    all_lines = program_lines + initiative_lines + major_task_lines + op_lines
    all_line_ids = [b.budget_id for b in all_lines]


    transactions = []
    if all_line_ids:
        transactions = db.query(BudgetTransaction).filter(
            BudgetTransaction.budget_id.in_(all_line_ids)
        ).order_by(BudgetTransaction.transaction_date.desc()).all()


    tx_list = []
    for t in transactions:
        bl = db.query(BudgetLine).filter(BudgetLine.budget_id == t.budget_id).first()
        budgetable_name = ""
        budgetable_type = ""
        if bl:
            budgetable_type = bl.budgetable_type or ""
            if bl.budgetable_type == "program":
                obj = db.query(Program).filter(Program.program_id == bl.budgetable_id).first()
                budgetable_name = obj.name if obj else ""
            elif bl.budgetable_type == "initiative":
                obj = db.query(Initiative).filter(Initiative.initiative_id == bl.budgetable_id).first()
                budgetable_name = obj.name if obj else ""
            elif bl.budgetable_type == "major_task":
                obj = db.query(MajorTask).filter(MajorTask.major_task_id == bl.budgetable_id).first()
                budgetable_name = obj.name if obj else ""
            elif bl.budgetable_type == "operational_task":
                obj = db.query(OperationalTask).filter(OperationalTask.task_id == bl.budgetable_id).first()
                budgetable_name = obj.title if obj else ""


        ttype = None
        if t.transaction_type_id:
            ttype = db.query(DictTransactionType).filter(
                DictTransactionType.trans_type_id == t.transaction_type_id
            ).first()


        tx_list.append({
            "id": t.transaction_id,
            "amount": float(t.amount or 0),
            "description": t.description or "",
            "date": t.transaction_date.isoformat() if t.transaction_date else None,
            "type_name": ttype.name_ar if ttype else "",
            "type_sign": ttype.sign if ttype else 1,
            "budgetable_type": budgetable_type,
            "budgetable_name": budgetable_name,
        })


    program_allocated = float(sum(b.allocated_amount or 0 for b in program_lines))
    program_spent = float(sum(b.spent_amount or 0 for b in program_lines))
    initiatives_allocated = float(sum(b.allocated_amount or 0 for b in initiative_lines))
    initiatives_spent = float(sum(b.spent_amount or 0 for b in initiative_lines))
    major_tasks_allocated = float(sum(b.allocated_amount or 0 for b in major_task_lines))
    major_tasks_spent = float(sum(b.spent_amount or 0 for b in major_task_lines))
    operations_allocated = float(sum(b.allocated_amount or 0 for b in op_lines))
    operations_spent = float(sum(b.spent_amount or 0 for b in op_lines))


    total_allocated = program_allocated + initiatives_allocated + major_tasks_allocated + operations_allocated
    total_spent = program_spent + initiatives_spent + major_tasks_spent + operations_spent


    return {
        "program": {
            "allocated": program_allocated,
            "spent": program_spent,
            "remaining": program_allocated - program_spent,
        },
        "initiatives": {
            "allocated": initiatives_allocated,
            "spent": initiatives_spent,
            "remaining": initiatives_allocated - initiatives_spent,
            "count": len(initiative_lines),
        },
        "major_tasks": {
            "allocated": major_tasks_allocated,
            "spent": major_tasks_spent,
            "remaining": major_tasks_allocated - major_tasks_spent,
            "count": len(major_task_lines),
        },
        "operations": {
            "allocated": operations_allocated,
            "spent": operations_spent,
            "remaining": operations_allocated - operations_spent,
            "count": len(op_lines),
        },
        "total": {
            "allocated": total_allocated,
            "spent": total_spent,
            "remaining": total_allocated - total_spent,
        },
        "transactions": tx_list,
    }




def _serialize_program(p: Program, db: Session, full: bool = False):
    goal_name = pillar_name = None
    goal_id = pillar_id = None
    goal_description = ""
    goal_target_date = None
    try:
        if p.goal:
            goal_name = p.goal.title
            goal_id = p.goal.goal_id
            goal_description = p.goal.description or ""
            goal_target_date = p.goal.target_date.isoformat() if p.goal.target_date else None
            if p.goal.pillar:
                pillar_name = p.goal.pillar.name
                pillar_id = p.goal.pillar.pillar_id
    except Exception:
        pass


    initiatives = db.query(Initiative).filter(
        Initiative.program_id == p.program_id,
        Initiative.is_active == True
    ).all()


    initiatives_data = []
    total_progress = 0.0
    total_ops = 0
    total_ops_completed = 0
    total_major_tasks = 0


    statuses_count = {
        "draft": 0, "pending": 0, "approved": 0,
        "in_progress": 0, "completed": 0, "cancelled": 0,
    }


    for init in initiatives:
        major_tasks = db.query(MajorTask).filter(
            MajorTask.initiative_id == init.initiative_id,
            MajorTask.is_active == True
        ).all()
        total_major_tasks += len(major_tasks)


        init_total_ops = 0
        init_ops_completed = 0
        init_progress_sum = 0.0


        for mt in major_tasks:
            mt_progress = _calc_major_task_progress(mt)
            init_total_ops += mt_progress["total"]
            init_ops_completed += mt_progress["completed"]
            init_progress_sum += mt_progress["progress"] * mt_progress["total"]


        init_progress = round(init_progress_sum / init_total_ops, 2) if init_total_ops > 0 else 0
        total_ops += init_total_ops
        total_ops_completed += init_ops_completed
        total_progress += init_progress


        if init.status_id == INIT_STATUS_DRAFT:
            statuses_count["draft"] += 1
        elif init.status_id == INIT_STATUS_PENDING:
            statuses_count["pending"] += 1
        elif init.status_id == INIT_STATUS_APPROVED:
            statuses_count["approved"] += 1
        elif init.status_id == INIT_STATUS_IN_PROGRESS:
            statuses_count["in_progress"] += 1
        elif init.status_id == INIT_STATUS_COMPLETED:
            statuses_count["completed"] += 1
        elif init.status_id == INIT_STATUS_CANCELLED:
            statuses_count["cancelled"] += 1


        status_info = _get_status_info(db, init.status_id)


        initiatives_data.append({
            "id": init.initiative_id,
            "name": init.name,
            "description": init.description or "",
            "status_id": init.status_id,
            "status_name": status_info["name"],
            "status_color": status_info["color"],
            "priority_id": init.priority_id,
            "progress": init_progress,
            "budget_estimate": float(init.budget_estimate or 0),
            "start_date": init.start_date.isoformat() if init.start_date else None,
            "end_date": init.end_date.isoformat() if init.end_date else None,
            "major_tasks_count": len(major_tasks),
            "operational_count": init_total_ops,
            "operational_completed": init_ops_completed,
        })


    program_progress = round(total_progress / len(initiatives), 2) if len(initiatives) > 0 else 0
    status_info = _get_status_info(db, p.status_id)


    data = {
        "id": p.program_id,
        "name": p.name,
        "title": p.name,
        "description": p.description or "",
        "goal_id": goal_id,
        "goal_name": goal_name,
        "goal_description": goal_description,
        "goal_target_date": goal_target_date,
        "pillar_id": pillar_id,
        "pillar_name": pillar_name,
        "status_id": p.status_id,
        "status_name": status_info["name"],
        "status_color": status_info["color"],
        "progress": program_progress,
        "budget": float(p.budget_estimate or 0),
        "budget_estimate": float(p.budget_estimate or 0),
        "start_date": p.start_date.isoformat() if p.start_date else None,
        "end_date": p.end_date.isoformat() if p.end_date else None,
        "initiatives_count": len(initiatives),
        "major_tasks_count": total_major_tasks,
        "total_operational_tasks": total_ops,
        "total_operational_completed": total_ops_completed,
        "statuses_count": statuses_count,
    }


    if full:
        data["initiatives"] = initiatives_data
        data["budget_details"] = _calc_program_budget(p.program_id, db)


    return data



@router.get("/programs")
async def get_programs(db: Session = Depends(get_db)):
    try:
        programs = db.query(Program).filter(Program.is_active == True).all()
        result = [_serialize_program(p, db, full=False) for p in programs]
        return {"success": True, "data": result}
    except Exception as e:
        import traceback
        traceback.print_exc()
        raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")




@router.get("/programs/{program_id}")
async def get_program(program_id: int, db: Session = Depends(get_db)):
    try:
        p = db.query(Program).filter(Program.program_id == program_id).first()
        if not p:
            raise HTTPException(status_code=404, detail="غير موجود")
        return {"success": True, "data": _serialize_program(p, db, full=True)}
    except HTTPException:
        raise
    except Exception as e:
        import traceback
        traceback.print_exc()
        raise HTTPException(status_code=500, detail=str(e))




@router.post("/programs")
async def create_program(data: ProgramCreate, db: Session = Depends(get_db)):
    try:
        program = Program(
            goal_id=data.goal_id,
            name=data.name,
            description=data.description,
            budget_estimate=data.budget_estimate,
            start_date=data.start_date,
            end_date=data.end_date,
            status_id=PROG_STATUS_DRAFT,
            is_active=True
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
    try:
        program = db.query(Program).filter(Program.program_id == program_id).first()
        if not program:
            raise HTTPException(status_code=404, detail="غير موجود")


        if program.status_id in (PROG_STATUS_IN_PROGRESS, PROG_STATUS_COMPLETED):
            program.status_id = PROG_STATUS_PENDING


        program.goal_id = data.goal_id
        program.name = data.name
        program.description = data.description
        program.budget_estimate = data.budget_estimate
        program.start_date = data.start_date
        program.end_date = data.end_date


        db.commit()
        status_info = _get_status_info(db, program.status_id)
        return {
            "success": True,
            "message": "تم تحديث البرنامج",
            "new_status": program.status_id,
            "new_status_name": status_info["name"],
        }
    except HTTPException:
        raise
    except Exception as e:
        db.rollback()
        raise HTTPException(status_code=500, detail=str(e))



@router.post("/programs/{program_id}/change-status")
async def change_program_status(program_id: int, data: dict, db: Session = Depends(get_db)):
    try:
        new_status = int(data.get("status_id") or 0)
        user_role = (data.get("user_role") or "").lower()


        valid_statuses = (
            PROG_STATUS_PENDING, PROG_STATUS_DRAFT, PROG_STATUS_IN_PROGRESS,
            PROG_STATUS_COMPLETED, PROG_STATUS_CANCELLED
        )
        if new_status not in valid_statuses:
            raise HTTPException(status_code=400, detail="حالة غير صحيحة")


        program = db.query(Program).filter(Program.program_id == program_id).first()
        if not program:
            raise HTTPException(status_code=404, detail="غير موجود")


        if new_status == PROG_STATUS_CANCELLED:
            if user_role not in ("minister", "وزير"):
                raise HTTPException(status_code=403, detail="هذا الإجراء متاح للوزير فقط")


        if new_status in (PROG_STATUS_IN_PROGRESS, PROG_STATUS_COMPLETED):
            if user_role not in ("manager", "مدير"):
                raise HTTPException(status_code=403, detail="هذا الإجراء متاح للمدير فقط")


        program.status_id = new_status
        db.commit()


        status_info = _get_status_info(db, new_status)
        return {
            "success": True,
            "message": "تم تحديث الحالة",
            "new_status": new_status,
            "new_status_name": status_info["name"],
        }
    except HTTPException:
        raise
    except Exception as e:
        db.rollback()
        raise HTTPException(status_code=500, detail=str(e))




def _get_status_info(db: Session, status_id: int):
    if not status_id:
        return {"name": "غير محدد", "color": "#6c757d", "code": ""}
    status = db.query(DictStatus).filter(DictStatus.status_id == status_id).first()
    if not status:
        return {"name": "غير محدد", "color": "#6c757d", "code": ""}
    return {
        "name": status.name_ar or "",
        "color": status.color_hex or "#6c757d",
        "code": status.code or "",
    }




def _calc_major_task_progress(mt: MajorTask) -> dict:
    ops = list(mt.operational_tasks or [])
    total = len(ops)
    if total == 0:
        return {"total": 0, "completed": 0, "progress": 0}


    completed = 0
    progress_sum = 0.0


    for op in ops:
        if op.status_id == TASK_STATUS_COMPLETED:
            completed += 1
            progress_sum += 100
        else:
            latest_log = None
            if op.progress_logs:
                latest_log = sorted(
                    op.progress_logs,
                    key=lambda x: x.log_time or datetime.min,
                    reverse=True
                )[0]
            if latest_log and latest_log.progress_percent is not None:
                progress_sum += float(latest_log.progress_percent)


    avg_progress = round(progress_sum / total, 2) if total > 0 else 0
    return {"total": total, "completed": completed, "progress": avg_progress}



def _serialize_initiative(i: Initiative, db: Session, full: bool = False):
    program_name = goal_name = pillar_name = None
    goal_id = pillar_id = None
    try:
        if i.program:
            program_name = i.program.name
            if i.program.goal:
                goal_name = i.program.goal.title
                goal_id = i.program.goal.goal_id
                if i.program.goal.pillar:
                    pillar_name = i.program.goal.pillar.name
                    pillar_id = i.program.goal.pillar.pillar_id
    except Exception:
        pass


    major_tasks = db.query(MajorTask).filter(
        MajorTask.initiative_id == i.initiative_id,
        MajorTask.is_active == True
    ).all()


    total_ops = 0
    progress_sum = 0.0
    tasks_data = []


    for mt in major_tasks:
        mt_progress = _calc_major_task_progress(mt)
        total_ops += mt_progress["total"]
        progress_sum += mt_progress["progress"] * mt_progress["total"]


        if full:
            depts = db.query(MajorTaskDepartment).filter(
                MajorTaskDepartment.major_task_id == mt.major_task_id
            ).all()


            responsible = []
            supporting = []
            for d in depts:
                dept = db.query(Department).filter(Department.department_id == d.department_id).first()
                item = {
                    "department_id": d.department_id,
                    "department_name": dept.name if dept else f"قسم #{d.department_id}",
                    "notes": d.notes or "",
                }
                if (d.responsibility_type or "").upper() == "LEAD":
                    responsible.append(item)
                else:
                    supporting.append(item)


            tasks_data.append({
                "id": mt.major_task_id,
                "name": mt.name or "",
                "description": mt.description or "",
                "priority_id": mt.priority_id or 2,
                "estimated_duration_days": mt.estimated_duration_days or 30,
                "is_cross_department": bool(mt.is_cross_department) or (len(responsible) + len(supporting)) > 1,
                "responsible_departments": responsible,
                "supporting_departments": supporting,
                "departments": responsible + supporting,
                "operational_count": mt_progress["total"],
                "operational_completed": mt_progress["completed"],
                "progress": mt_progress["progress"],
            })


    initiative_progress = round(progress_sum / total_ops, 2) if total_ops > 0 else 0
    status_info = _get_status_info(db, i.status_id)


    data = {
        "id": i.initiative_id,
        "name": i.name,
        "title": i.name,
        "description": i.description or "",
        "program_id": i.program_id,
        "program_name": program_name,
        "goal_id": goal_id,
        "goal_name": goal_name,
        "pillar_id": pillar_id,
        "pillar_name": pillar_name,
        "priority_id": i.priority_id,
        "status_id": i.status_id,
        "status_name": status_info["name"],
        "status_color": status_info["color"],
        "progress": initiative_progress,
        "budget_estimate": float(i.budget_estimate or 0),
        "start_date": i.start_date.isoformat() if i.start_date else None,
        "end_date": i.end_date.isoformat() if i.end_date else None,
        "major_tasks_count": len(major_tasks),
        "total_operational_tasks": total_ops,
    }


    if full:
        data["major_tasks"] = tasks_data
        try:
            budget_lines = db.query(BudgetLine).filter(
                BudgetLine.budgetable_type == "initiative",
                BudgetLine.budgetable_id == i.initiative_id
            ).all()
            budget_ids = [b.budget_id for b in budget_lines]
            txs = []
            if budget_ids:
                txs = db.query(BudgetTransaction).filter(
                    BudgetTransaction.budget_id.in_(budget_ids)
                ).order_by(BudgetTransaction.transaction_date.desc()).all()
            data["budget_history"] = [{
                "id": t.transaction_id,
                "amount": float(t.amount or 0),
                "description": t.description or "",
                "date": t.transaction_date.isoformat() if t.transaction_date else None,
            } for t in txs]
            data["budget_allocated"] = float(sum(b.allocated_amount or 0 for b in budget_lines))
            data["budget_spent"] = float(sum(b.spent_amount or 0 for b in budget_lines))
        except Exception:
            data["budget_history"] = []
            data["budget_allocated"] = 0
            data["budget_spent"] = 0


    return data




@router.get("/initiatives")
async def get_initiatives(db: Session = Depends(get_db)):
    try:
        initiatives = db.query(Initiative).filter(Initiative.is_active == True).all()
        result = [_serialize_initiative(i, db, full=False) for i in initiatives]
        return {"success": True, "data": result}
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")




@router.get("/initiatives/{initiative_id}")
async def get_initiative(initiative_id: int, db: Session = Depends(get_db)):
    try:
        i = db.query(Initiative).filter(Initiative.initiative_id == initiative_id).first()
        if not i:
            raise HTTPException(status_code=404, detail="غير موجودة")
        return {"success": True, "data": _serialize_initiative(i, db, full=True)}
    except HTTPException:
        raise
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))




@router.post("/initiatives")
async def create_initiative(data: InitiativeCreate, db: Session = Depends(get_db)):
    try:
        initiative = Initiative(
            program_id=data.program_id,
            name=data.name,
            description=data.description,
            priority_id=data.priority_id,
            start_date=data.start_date,
            end_date=data.end_date,
            budget_estimate=data.budget_estimate,
            status_id=INIT_STATUS_DRAFT,
            is_active=True
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
    try:
        initiative = db.query(Initiative).filter(Initiative.initiative_id == initiative_id).first()
        if not initiative:
            raise HTTPException(status_code=404, detail="غير موجودة")


        if initiative.status_id in (INIT_STATUS_APPROVED, INIT_STATUS_IN_PROGRESS, INIT_STATUS_COMPLETED):
            initiative.status_id = INIT_STATUS_PENDING


        initiative.program_id = data.program_id
        initiative.name = data.name
        initiative.description = data.description
        initiative.priority_id = data.priority_id
        initiative.start_date = data.start_date
        initiative.end_date = data.end_date
        initiative.budget_estimate = data.budget_estimate


        db.commit()
        status_info = _get_status_info(db, initiative.status_id)
        return {
            "success": True,
            "message": "تم تحديث المبادرة",
            "new_status": initiative.status_id,
            "new_status_name": status_info["name"],
        }
    except HTTPException:
        raise
    except Exception as e:
        db.rollback()
        raise HTTPException(status_code=500, detail=str(e))



@router.post("/initiatives/{initiative_id}/change-status")
async def change_initiative_status(initiative_id: int, data: dict, db: Session = Depends(get_db)):
    try:
        new_status = int(data.get("status_id") or 0)
        user_role = (data.get("user_role") or "").lower()


        valid_statuses = (
            INIT_STATUS_DRAFT, INIT_STATUS_IN_PROGRESS, INIT_STATUS_COMPLETED,
            INIT_STATUS_CANCELLED, INIT_STATUS_APPROVED, INIT_STATUS_PENDING
        )
        if new_status not in valid_statuses:
            raise HTTPException(status_code=400, detail="حالة غير صحيحة")


        initiative = db.query(Initiative).filter(Initiative.initiative_id == initiative_id).first()
        if not initiative:
            raise HTTPException(status_code=404, detail="غير موجودة")


        if new_status in (INIT_STATUS_APPROVED, INIT_STATUS_CANCELLED):
            if user_role not in ("minister", "وزير"):
                raise HTTPException(status_code=403, detail="هذا الإجراء متاح للوزير فقط")


        if new_status in (INIT_STATUS_IN_PROGRESS, INIT_STATUS_COMPLETED):
            if user_role not in ("manager", "مدير"):
                raise HTTPException(status_code=403, detail="هذا الإجراء متاح للمدير فقط")


        initiative.status_id = new_status
        db.commit()


        status_info = _get_status_info(db, new_status)
        return {
            "success": True,
            "message": "تم تحديث الحالة",
            "new_status": new_status,
            "new_status_name": status_info["name"],
        }
    except HTTPException:
        raise
    except Exception as e:
        db.rollback()
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


            mt_progress = _calc_major_task_progress(task)


            result.append({
                "id": task.major_task_id,
                "name": task.name or "",
                "description": task.description or "",
                "priority_id": task.priority_id or 2,
                "estimated_duration_days": task.estimated_duration_days or 30,
                "is_cross_department": bool(task.is_cross_department),
                "departments": dept_list,
                "operational_count": mt_progress["total"],
                "progress": mt_progress["progress"],
                "created_at": task.created_at.isoformat() if task.created_at else None
            })


        return {"success": True, "data": result}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))




@router.post("/initiatives/{initiative_id}/major-tasks")
async def create_major_task_manual(initiative_id: int, data: dict, db: Session = Depends(get_db)):
    try:
        initiative = db.query(Initiative).filter(Initiative.initiative_id == initiative_id).first()
        if not initiative:
            raise HTTPException(status_code=404, detail="المبادرة غير موجودة")


        if initiative.status_id not in (INIT_STATUS_APPROVED, INIT_STATUS_IN_PROGRESS):
            raise HTTPException(status_code=400, detail="لا يمكن إنشاء مهام رئيسية قبل اعتماد المبادرة")


        departments = data.get("departments", [])
        responsible_count = sum(
            1 for d in departments
            if (d.get("responsibility_type") or "").upper() in ("RESPONSIBLE", "LEAD")
        )
        if responsible_count == 0:
            raise HTTPException(status_code=400, detail="يجب تحديد إدارة مسؤولة واحدة على الأقل")


        is_cross = len(departments) > 1


        task = MajorTask(
            initiative_id=initiative_id,
            name=data.get("name"),
            description=data.get("description", ""),
            priority_id=data.get("priority_id", 2),
            estimated_duration_days=data.get("estimated_duration_days", 30),
            is_cross_department=is_cross,
            is_active=True
        )
        db.add(task)
        db.flush()


        for dept in departments:
            mtd = MajorTaskDepartment(
                major_task_id=task.major_task_id,
                department_id=dept.get("department_id"),
                responsibility_type=(dept.get("responsibility_type") or "SUPPORT").upper(),
                notes=dept.get("notes", "")
            )
            db.add(mtd)


        if initiative.status_id == INIT_STATUS_APPROVED:
            initiative.status_id = INIT_STATUS_IN_PROGRESS


        db.commit()
        return {
            "success": True,
            "data": {
                "id": task.major_task_id,
                "name": task.name,
                "is_cross_department": is_cross,
            }
        }
    except HTTPException:
        raise
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
    except HTTPException:
        raise
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

@router.get("/vision")
async def get_vision(db: Session = Depends(get_db)):
    try:
        vision = db.query(StrategicVision).filter(
            StrategicVision.is_current == True
        ).order_by(StrategicVision.version.desc()).first()


        if not vision:
            return {"success": True, "data": {
                "id": None, "text": "", "description": "",
                "version": 1, "effective_date": None,
                "approved_at": None, "created_at": None,
                "approved_by_name": None, "created_by_name": None,
            }}


        approved_by_name = None
        if vision.approved_by:
            emp = db.query(Employee).filter(Employee.employee_id == vision.approved_by).first()
            approved_by_name = emp.full_name if emp else None


        created_by_name = None
        if vision.created_by:
            emp = db.query(Employee).filter(Employee.employee_id == vision.created_by).first()
            created_by_name = emp.full_name if emp else None


        return {"success": True, "data": {
            "id": vision.vision_id,
            "text": vision.text or "",
            "description": vision.description or "",
            "version": vision.version or 1,
            "effective_date": vision.effective_date.isoformat() if vision.effective_date else None,
            "approved_at": vision.approved_at.isoformat() if vision.approved_at else None,
            "created_at": vision.created_at.isoformat() if vision.created_at else None,
            "approved_by_name": approved_by_name,
            "created_by_name": created_by_name,
        }}
    except Exception as e:
        import traceback
        traceback.print_exc()
        raise HTTPException(status_code=500, detail=str(e))




@router.put("/vision")
async def update_vision(data: dict, db: Session = Depends(get_db)):
    try:
        vision = db.query(StrategicVision).filter(
            StrategicVision.is_current == True
        ).order_by(StrategicVision.version.desc()).first()


        if vision:
            vision.text = data.get('text', vision.text)
            vision.description = data.get('description', vision.description or '')
            if vision.version is None:
                vision.version = 1
            vision.version = (vision.version or 1) + 1
        else:
            vision = StrategicVision(
                text=data.get('text', ''),
                description=data.get('description', ''),
                version=1,
                is_current=True,
                effective_date=date_type.today()
            )
            db.add(vision)


        db.commit()
        return {"success": True, "message": "تم تحديث الرؤية"}
    except Exception as e:
        db.rollback()
        raise HTTPException(status_code=500, detail=str(e))




@router.get("/swot")
async def get_swot(db: Session = Depends(get_db)):
    try:
        swot = db.query(SWOTAnalysis).order_by(SWOTAnalysis.id.desc()).first()


        if not swot:
            return {"success": True, "data": {
                "id": None,
                "strengths": "", "weaknesses": "",
                "opportunities": "", "threats": "",
                "analysis_date": None, "updated_at": None,
                "department_name": None,
            }}


        dept_name = None
        if swot.department_id:
            dept = db.query(Department).filter(Department.department_id == swot.department_id).first()
            dept_name = dept.name if dept else None


        return {"success": True, "data": {
            "id": swot.id,
            "strengths": swot.strengths or "",
            "weaknesses": swot.weaknesses or "",
            "opportunities": swot.opportunities or "",
            "threats": swot.threats or "",
            "analysis_date": swot.analysis_date.isoformat() if swot.analysis_date else None,
            "updated_at": swot.updated_at.isoformat() if swot.updated_at else None,
            "department_name": dept_name,
        }}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))




@router.put("/swot")
async def update_swot(data: dict, db: Session = Depends(get_db)):
    try:
        swot = db.query(SWOTAnalysis).order_by(SWOTAnalysis.id.desc()).first()


        if swot:
            swot.strengths = data.get('strengths', swot.strengths or '')
            swot.weaknesses = data.get('weaknesses', swot.weaknesses or '')
            swot.opportunities = data.get('opportunities', swot.opportunities or '')
            swot.threats = data.get('threats', swot.threats or '')
        else:
            swot = SWOTAnalysis(
                strengths=data.get('strengths', ''),
                weaknesses=data.get('weaknesses', ''),
                opportunities=data.get('opportunities', ''),
                threats=data.get('threats', ''),
                analysis_date=date_type.today()
            )
            db.add(swot)


        db.commit()
        return {"success": True, "message": "تم تحديث تحليل SWOT"}
    except Exception as e:
        db.rollback()
        raise HTTPException(status_code=500, detail=str(e))




@router.get("/pestel")
async def get_pestel(db: Session = Depends(get_db)):
    try:
        pestel = db.query(PESTELAnalysis).order_by(PESTELAnalysis.id.desc()).first()


        if not pestel:
            return {"success": True, "data": {
                "id": None,
                "political": "", "economic": "", "social": "",
                "technological": "", "environmental": "", "legal": "",
                "analysis_date": None, "updated_at": None,
                "department_name": None,
            }}


        dept_name = None
        if pestel.department_id:
            dept = db.query(Department).filter(Department.department_id == pestel.department_id).first()
            dept_name = dept.name if dept else None


        return {"success": True, "data": {
            "id": pestel.id,
            "political": pestel.political or "",
            "economic": pestel.economic or "",
            "social": pestel.social or "",
            "technological": pestel.technological or "",
            "environmental": pestel.environmental or "",
            "legal": pestel.legal or "",
            "analysis_date": pestel.analysis_date.isoformat() if pestel.analysis_date else None,
            "updated_at": pestel.updated_at.isoformat() if pestel.updated_at else None,
            "department_name": dept_name,
        }}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))




@router.put("/pestel")
async def update_pestel(data: dict, db: Session = Depends(get_db)):
    try:
        pestel = db.query(PESTELAnalysis).order_by(PESTELAnalysis.id.desc()).first()


        if pestel:
            pestel.political = data.get('political', pestel.political or '')
            pestel.economic = data.get('economic', pestel.economic or '')
            pestel.social = data.get('social', pestel.social or '')
            pestel.technological = data.get('technological', pestel.technological or '')
            pestel.environmental = data.get('environmental', pestel.environmental or '')
            pestel.legal = data.get('legal', pestel.legal or '')
        else:
            pestel = PESTELAnalysis(
                political=data.get('political', ''),
                economic=data.get('economic', ''),
                social=data.get('social', ''),
                technological=data.get('technological', ''),
                environmental=data.get('environmental', ''),
                legal=data.get('legal', ''),
                analysis_date=date_type.today()
            )
            db.add(pestel)


        db.commit()
        return {"success": True, "message": "تم تحديث تحليل PESTEL"}
    except Exception as e:
        db.rollback()
        raise HTTPException(status_code=500, detail=str(e))

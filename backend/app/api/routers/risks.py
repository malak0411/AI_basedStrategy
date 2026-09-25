from fastapi import APIRouter, Depends, HTTPException, Query
from sqlalchemy.orm import Session
from sqlalchemy import func, desc, and_
from typing import Optional
from datetime import datetime, date
import json


from app.models import (
    Risk, RiskMitigation, OperationalTask, Employee,
    DictStatus, DictRiskLevel, Department, AIRecommendation, DictPriority
)
from app.models import (
    RiskCreate, RiskUpdate, RiskMitigationCreate, RiskMitigationUpdate,
    RiskResponse, RiskMitigationResponse
)
from app.core.dependencies import get_current_user
from app.database import get_db


router = APIRouter(prefix="/api/risks", tags=["Risks"])


PROBABILITY_MIN = 1
PROBABILITY_MAX = 10
IMPACT_MIN = 1
IMPACT_MAX = 10


MITIGATION_COMPLETED_CODES = ['completed', 'resolved', 'done', 'closed']




@router.get("/")
async def get_risks(
    risk_level_id: Optional[int] = Query(None),
    status_id: Optional[int] = Query(None),
    task_id: Optional[int] = Query(None),
    search: Optional[str] = Query(None),
    overdue: Optional[bool] = Query(None),
    page: int = Query(1, ge=1),
    per_page: int = Query(10, ge=1, le=100),
    current_user: Employee = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    query = db.query(Risk)


    if risk_level_id:
        query = query.filter(Risk.risk_level_id == risk_level_id)


    if status_id:
        query = query.filter(Risk.status_id == status_id)


    if task_id:
        query = query.filter(Risk.task_id == task_id)


    if search:
        query = query.filter(
            Risk.name.contains(search) | Risk.description.contains(search)
        )


    if overdue:
        today = date.today()
        query = query.filter(Risk.target_date < today)


    total = query.count()
    risks = query.offset((page - 1) * per_page).limit(per_page).all()


    result = []
    for risk in risks:
        task = db.query(OperationalTask).filter(
            OperationalTask.task_id == risk.task_id
        ).first() if risk.task_id else None


        level = db.query(DictRiskLevel).filter(
            DictRiskLevel.risk_level_id == risk.risk_level_id
        ).first() if risk.risk_level_id else None


        status = db.query(DictStatus).filter(
            DictStatus.status_id == risk.status_id
        ).first() if risk.status_id else None


        employee = db.query(Employee).filter(
            Employee.employee_id == risk.identified_by
        ).first() if risk.identified_by else None


        mitigations_count = db.query(RiskMitigation).filter(
            RiskMitigation.risk_id == risk.risk_id
        ).count()


        result.append({
            "risk_id": risk.risk_id,
            "name": risk.name,
            "description": risk.description,
            "task_id": risk.task_id,
            "task": {
                "task_id": task.task_id if task else None,
                "title": task.title if task else None
            },
            "probability": risk.probability,
            "impact": risk.impact,
            "risk_score": risk.risk_score,
            "risk_level": {
                "risk_level_id": level.risk_level_id if level else None,
                "code": level.code if level else None,
                "name_ar": level.name_ar if level else None,
                "name_en": level.name_en if level else None,
                "color_hex": level.color_hex if level else "#6c757d"
            },
            "status": {
                "status_id": status.status_id if status else None,
                "code": status.code if status else None,
                "name_ar": status.name_ar if status else None,
                "name_en": status.name_en if status else None,
                "color_hex": status.color_hex if status else "#6c757d"
            },
            "identified_by": {
                "employee_id": employee.employee_id if employee else None,
                "full_name": employee.full_name if employee else None
            },
            "identified_at": risk.identified_at.isoformat() if risk.identified_at else None,
            "target_date": risk.target_date.isoformat() if risk.target_date else None,
            "mitigations_count": mitigations_count,
            "updated_at": risk.updated_at.isoformat() if risk.updated_at else None
        })


    return {
        "data": result,
        "total": total,
        "page": page,
        "per_page": per_page,
        "total_pages": (total + per_page - 1) // per_page
    }




@router.get("/options")
async def get_risk_options(
    current_user: Employee = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    risk_levels = db.query(DictRiskLevel).all()
    risk_levels_data = [
        {
            "risk_level_id": rl.risk_level_id,
            "code": rl.code,
            "name_ar": rl.name_ar,
            "name_en": rl.name_en,
            "color_hex": rl.color_hex,
            "min_score": rl.min_score,
            "max_score": rl.max_score
        }
        for rl in risk_levels
    ]


    statuses = db.query(DictStatus).filter(
        DictStatus.category == "risk"
    ).all()
    statuses_data = [
        {
            "status_id": s.status_id,
            "code": s.code,
            "name_ar": s.name_ar,
            "name_en": s.name_en,
            "color_hex": s.color_hex
        }
        for s in statuses
    ]


    mitigation_statuses = db.query(DictStatus).filter(
        DictStatus.category.in_(["task", "risk"])
    ).all()
    mitigation_statuses_data = [
        {
            "status_id": s.status_id,
            "code": s.code,
            "name_ar": s.name_ar,
            "name_en": s.name_en,
            "category": s.category,
            "color_hex": s.color_hex
        }
        for s in mitigation_statuses
    ]


    tasks = db.query(OperationalTask).filter(
        OperationalTask.is_active == True
    ).all()
    tasks_data = [
        {
            "task_id": t.task_id,
            "title": t.title
        }
        for t in tasks
    ]


    employees = db.query(Employee).filter(
        Employee.is_active == True
    ).all()
    employees_data = [
        {
            "employee_id": e.employee_id,
            "full_name": e.full_name
        }
        for e in employees
    ]


    return {
        "risk_levels": risk_levels_data,
        "statuses": statuses_data,
        "mitigation_statuses": mitigation_statuses_data,
        "tasks": tasks_data,
        "employees": employees_data,
        "probability_range": {"min": PROBABILITY_MIN, "max": PROBABILITY_MAX},
        "impact_range": {"min": IMPACT_MIN, "max": IMPACT_MAX}
    }




@router.get("/{risk_id}")
async def get_risk_detail(
    risk_id: int,
    current_user: Employee = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    risk = db.query(Risk).filter(Risk.risk_id == risk_id).first()


    if not risk:
        raise HTTPException(status_code=404, detail="الخطر غير موجود")


    task = db.query(OperationalTask).filter(
        OperationalTask.task_id == risk.task_id
    ).first() if risk.task_id else None


    level = db.query(DictRiskLevel).filter(
        DictRiskLevel.risk_level_id == risk.risk_level_id
    ).first() if risk.risk_level_id else None


    status = db.query(DictStatus).filter(
        DictStatus.status_id == risk.status_id
    ).first() if risk.status_id else None


    employee = db.query(Employee).filter(
        Employee.employee_id == risk.identified_by
    ).first() if risk.identified_by else None


    mitigations = db.query(RiskMitigation).filter(
        RiskMitigation.risk_id == risk_id
    ).all()


    mitigations_data = []
    for m in mitigations:
        m_status = db.query(DictStatus).filter(
            DictStatus.status_id == m.status_id
        ).first() if m.status_id else None


        m_assigned = db.query(Employee).filter(
            Employee.employee_id == m.assigned_to
        ).first() if m.assigned_to else None


        m_task = db.query(OperationalTask).filter(
            OperationalTask.task_id == m.task_id
        ).first() if m.task_id else None


        mitigations_data.append({
            "mitigation_id": m.mitigation_id,
            "action": m.action,
            "task": {
                "task_id": m_task.task_id if m_task else None,
                "title": m_task.title if m_task else None
            },
            "assigned_to": {
                "employee_id": m_assigned.employee_id if m_assigned else None,
                "full_name": m_assigned.full_name if m_assigned else None
            },
            "status": {
                "status_id": m_status.status_id if m_status else None,
                "code": m_status.code if m_status else None,
                "name_ar": m_status.name_ar if m_status else None,
                "name_en": m_status.name_en if m_status else None,
                "color_hex": m_status.color_hex if m_status else "#6c757d"
            },
            "due_date": m.due_date.isoformat() if m.due_date else None,
            "completed_at": m.completed_at.isoformat() if m.completed_at else None,
            "notes": m.notes,
            "created_at": m.created_at.isoformat() if m.created_at else None,
            "updated_at": m.updated_at.isoformat() if m.updated_at else None
        })


    return {
        "risk_id": risk.risk_id,
        "name": risk.name,
        "description": risk.description,
        "task_id": risk.task_id,
        "task": {
            "task_id": task.task_id if task else None,
            "title": task.title if task else None
        },
        "probability": risk.probability,
        "impact": risk.impact,
        "risk_score": risk.risk_score,
        "risk_level": {
            "risk_level_id": level.risk_level_id if level else None,
            "code": level.code if level else None,
            "name_ar": level.name_ar if level else None,
            "name_en": level.name_en if level else None,
            "color_hex": level.color_hex if level else "#6c757d"
        },
        "status": {
            "status_id": status.status_id if status else None,
            "code": status.code if status else None,
            "name_ar": status.name_ar if status else None,
            "name_en": status.name_en if status else None,
            "color_hex": status.color_hex if status else "#6c757d"
        },
        "identified_by": {
            "employee_id": employee.employee_id if employee else None,
            "full_name": employee.full_name if employee else None
        },
        "identified_at": risk.identified_at.isoformat() if risk.identified_at else None,
        "target_date": risk.target_date.isoformat() if risk.target_date else None,
        "updated_at": risk.updated_at.isoformat() if risk.updated_at else None,
        "mitigations": mitigations_data
    }




@router.get("/{risk_id}/mitigations")
async def get_risk_mitigations(
    risk_id: int,
    status_id: Optional[int] = Query(None),
    assigned_to: Optional[int] = Query(None),
    page: int = Query(1, ge=1),
    per_page: int = Query(10, ge=1, le=100),
    current_user: Employee = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    risk = db.query(Risk).filter(Risk.risk_id == risk_id).first()


    if not risk:
        raise HTTPException(status_code=404, detail="الخطر غير موجود")


    query = db.query(RiskMitigation).filter(
        RiskMitigation.risk_id == risk_id
    )


    if status_id:
        query = query.filter(RiskMitigation.status_id == status_id)


    if assigned_to:
        query = query.filter(RiskMitigation.assigned_to == assigned_to)


    total = query.count()
    mitigations = query.offset((page - 1) * per_page).limit(per_page).all()


    result = []
    for m in mitigations:
        m_status = db.query(DictStatus).filter(
            DictStatus.status_id == m.status_id
        ).first() if m.status_id else None


        m_assigned = db.query(Employee).filter(
            Employee.employee_id == m.assigned_to
        ).first() if m.assigned_to else None


        m_task = db.query(OperationalTask).filter(
            OperationalTask.task_id == m.task_id
        ).first() if m.task_id else None


        result.append({
            "mitigation_id": m.mitigation_id,
            "action": m.action,
            "task": {
                "task_id": m_task.task_id if m_task else None,
                "title": m_task.title if m_task else None
            },
            "assigned_to": {
                "employee_id": m_assigned.employee_id if m_assigned else None,
                "full_name": m_assigned.full_name if m_assigned else None
            },
            "status": {
                "status_id": m_status.status_id if m_status else None,
                "code": m_status.code if m_status else None,
                "name_ar": m_status.name_ar if m_status else None,
                "name_en": m_status.name_en if m_status else None,
                "color_hex": m_status.color_hex if m_status else "#6c757d"
            },
            "due_date": m.due_date.isoformat() if m.due_date else None,
            "completed_at": m.completed_at.isoformat() if m.completed_at else None,
            "notes": m.notes,
            "created_at": m.created_at.isoformat() if m.created_at else None,
            "updated_at": m.updated_at.isoformat() if m.updated_at else None
        })


    return {
        "data": result,
        "total": total,
        "page": page,
        "per_page": per_page,
        "total_pages": (total + per_page - 1) // per_page
    }




@router.post("/")
async def create_risk(
    data: dict,
    current_user: Employee = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    name = data.get("name")
    description = data.get("description")
    task_id = data.get("task_id")
    probability = data.get("probability")
    impact = data.get("impact")
    target_date = data.get("target_date")
    status_id = data.get("status_id")


    if not name:
        raise HTTPException(status_code=400, detail="اسم الخطر مطلوب")


    if task_id:
        task = db.query(OperationalTask).filter(
            OperationalTask.task_id == task_id
        ).first()
        if not task:
            raise HTTPException(status_code=404, detail="المهمة التشغيلية غير موجودة")


    if probability is None:
        raise HTTPException(status_code=400, detail="نسبة الاحتمال مطلوبة")
    if probability < PROBABILITY_MIN or probability > PROBABILITY_MAX:
        raise HTTPException(
            status_code=400,
            detail=f"نسبة الاحتمال يجب أن تكون بين {PROBABILITY_MIN} و {PROBABILITY_MAX}"
        )


    if impact is None:
        raise HTTPException(status_code=400, detail="نسبة التأثير مطلوبة")
    if impact < IMPACT_MIN or impact > IMPACT_MAX:
        raise HTTPException(
            status_code=400,
            detail=f"نسبة التأثير يجب أن تكون بين {IMPACT_MIN} و {IMPACT_MAX}"
        )


    risk_score = probability * impact


    risk_level = db.query(DictRiskLevel).filter(
        DictRiskLevel.min_score <= risk_score,
        DictRiskLevel.max_score >= risk_score
    ).first()


    if not risk_level:
        raise HTTPException(status_code=400, detail="لا يوجد مستوى خطر مناسب للنتيجة المحسوبة")


    if status_id:
        status = db.query(DictStatus).filter(
            DictStatus.status_id == status_id,
            DictStatus.category == "risk"
        ).first()
        if not status:
            raise HTTPException(status_code=404, detail="الحالة غير موجودة")


    new_risk = Risk(
        name=name,
        description=description,
        task_id=task_id,
        probability=probability,
        impact=impact,
        risk_score=risk_score,
        risk_level_id=risk_level.risk_level_id,
        identified_by=current_user,
        identified_at=datetime.now(),
        target_date=target_date,
        status_id=status_id
    )


    db.add(new_risk)
    db.commit()
    db.refresh(new_risk)


    return {
        "success": True,
        "message": "تم إنشاء الخطر بنجاح",
        "risk_id": new_risk.risk_id,
        "risk_score": risk_score,
        "risk_level_id": risk_level.risk_level_id
    }




@router.put("/{risk_id}")
async def update_risk(
    risk_id: int,
    data: dict,
    current_user: Employee = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    risk = db.query(Risk).filter(Risk.risk_id == risk_id).first()


    if not risk:
        raise HTTPException(status_code=404, detail="الخطر غير موجود")


    if "name" in data:
        risk.name = data["name"]


    if "description" in data:
        risk.description = data["description"]


    if "task_id" in data:
        if data["task_id"]:
            task = db.query(OperationalTask).filter(
                OperationalTask.task_id == data["task_id"]
            ).first()
            if not task:
                raise HTTPException(status_code=404, detail="المهمة التشغيلية غير موجودة")
        risk.task_id = data["task_id"]


    if "probability" in data:
        prob = data["probability"]
        if prob < PROBABILITY_MIN or prob > PROBABILITY_MAX:
            raise HTTPException(
                status_code=400,
                detail=f"نسبة الاحتمال يجب أن تكون بين {PROBABILITY_MIN} و {PROBABILITY_MAX}"
            )
        risk.probability = prob


    if "impact" in data:
        imp = data["impact"]
        if imp < IMPACT_MIN or imp > IMPACT_MAX:
            raise HTTPException(
                status_code=400,
                detail=f"نسبة التأثير يجب أن تكون بين {IMPACT_MIN} و {IMPACT_MAX}"
            )
        risk.impact = imp


    if "target_date" in data:
        risk.target_date = data["target_date"]


    if "status_id" in data:
        if data["status_id"]:
            status = db.query(DictStatus).filter(
                DictStatus.status_id == data["status_id"],
                DictStatus.category == "risk"
            ).first()
            if not status:
                raise HTTPException(status_code=404, detail="الحالة غير موجودة")
        risk.status_id = data["status_id"]


    risk.risk_score = risk.probability * risk.impact


    risk_level = db.query(DictRiskLevel).filter(
        DictRiskLevel.min_score <= risk.risk_score,
        DictRiskLevel.max_score >= risk.risk_score
    ).first()


    if risk_level:
        risk.risk_level_id = risk_level.risk_level_id


    risk.updated_at = datetime.now()


    db.commit()


    return {
        "success": True,
        "message": "تم تحديث الخطر بنجاح"
    }




@router.delete("/{risk_id}")
async def delete_risk(
    risk_id: int,
    current_user: Employee = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    risk = db.query(Risk).filter(Risk.risk_id == risk_id).first()


    if not risk:
        raise HTTPException(status_code=404, detail="الخطر غير موجود")


    mitigations = db.query(RiskMitigation).filter(
        RiskMitigation.risk_id == risk_id
    ).count()


    if mitigations > 0:
        raise HTTPException(
            status_code=400,
            detail="لا يمكن حذف الخطر لوجود إجراءات معالجة مرتبطة به"
        )


    db.delete(risk)
    db.commit()


    return {
        "success": True,
        "message": "تم حذف الخطر بنجاح"
    }




@router.post("/{risk_id}/mitigations")
async def create_mitigation(
    risk_id: int,
    data: dict,
    current_user: Employee = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    risk = db.query(Risk).filter(Risk.risk_id == risk_id).first()


    if not risk:
        raise HTTPException(status_code=404, detail="الخطر غير موجود")


    action = data.get("action")
    task_id = data.get("task_id")
    assigned_to = data.get("assigned_to")
    due_date = data.get("due_date")
    status_id = data.get("status_id")
    notes = data.get("notes")


    if not action:
        raise HTTPException(status_code=400, detail="إجراء المعالجة مطلوب")


    if task_id:
        task = db.query(OperationalTask).filter(
            OperationalTask.task_id == task_id
        ).first()
        if not task:
            raise HTTPException(status_code=404, detail="المهمة التشغيلية غير موجودة")


    if assigned_to:
        employee = db.query(Employee).filter(
            Employee.employee_id == assigned_to
        ).first()
        if not employee:
            raise HTTPException(status_code=404, detail="الموظف غير موجود")


    if status_id:
        status = db.query(DictStatus).filter(
            DictStatus.status_id == status_id
        ).first()
        if not status:
            raise HTTPException(status_code=404, detail="الحالة غير موجودة")


    completed_at = None
    if status_id:
        current_status = db.query(DictStatus).filter(
            DictStatus.status_id == status_id
        ).first()
        if current_status and current_status.code in MITIGATION_COMPLETED_CODES:
            completed_at = datetime.now()


    new_mitigation = RiskMitigation(
        risk_id=risk_id,
        task_id=task_id,
        action=action,
        assigned_to=assigned_to,
        due_date=due_date,
        status_id=status_id,
        completed_at=completed_at,
        notes=notes
    )


    db.add(new_mitigation)
    db.commit()
    db.refresh(new_mitigation)


    return {
        "success": True,
        "message": "تم إنشاء إجراء المعالجة بنجاح",
        "mitigation_id": new_mitigation.mitigation_id
    }




@router.put("/mitigations/{mitigation_id}")
async def update_mitigation(
    mitigation_id: int,
    data: dict,
    current_user: Employee = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    mitigation = db.query(RiskMitigation).filter(
        RiskMitigation.mitigation_id == mitigation_id
    ).first()


    if not mitigation:
        raise HTTPException(status_code=404, detail="إجراء المعالجة غير موجود")


    if "action" in data:
        mitigation.action = data["action"]


    if "task_id" in data:
        if data["task_id"]:
            task = db.query(OperationalTask).filter(
                OperationalTask.task_id == data["task_id"]
            ).first()
            if not task:
                raise HTTPException(status_code=404, detail="المهمة التشغيلية غير موجودة")
        mitigation.task_id = data["task_id"]


    if "assigned_to" in data:
        if data["assigned_to"]:
            employee = db.query(Employee).filter(
                Employee.employee_id == data["assigned_to"]
            ).first()
            if not employee:
                raise HTTPException(status_code=404, detail="الموظف غير موجود")
        mitigation.assigned_to = data["assigned_to"]


    if "due_date" in data:
        mitigation.due_date = data["due_date"]


    if "status_id" in data:
        if data["status_id"]:
            status = db.query(DictStatus).filter(
                DictStatus.status_id == data["status_id"]
            ).first()
            if not status:
                raise HTTPException(status_code=404, detail="الحالة غير موجودة")
        mitigation.status_id = data["status_id"]


        if mitigation.status_id:
            new_status = db.query(DictStatus).filter(
                DictStatus.status_id == mitigation.status_id
            ).first()
            if new_status and new_status.code in MITIGATION_COMPLETED_CODES:
                if not mitigation.completed_at:
                    mitigation.completed_at = datetime.now()
            else:
                mitigation.completed_at = None


    if "notes" in data:
        mitigation.notes = data["notes"]


    mitigation.updated_at = datetime.now()


    db.commit()


    return {
        "success": True,
        "message": "تم تحديث إجراء المعالجة بنجاح"
    }




@router.delete("/mitigations/{mitigation_id}")
async def delete_mitigation(
    mitigation_id: int,
    current_user: Employee = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    mitigation = db.query(RiskMitigation).filter(
        RiskMitigation.mitigation_id == mitigation_id
    ).first()


    if not mitigation:
        raise HTTPException(status_code=404, detail="إجراء المعالجة غير موجود")


    db.delete(mitigation)
    db.commit()


    return {
        "success": True,
        "message": "تم حذف إجراء المعالجة بنجاح"
    }




@router.post("/auto-detect/{task_id}")
async def auto_detect_risk(
    task_id: int,
    current_user: Employee = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    try:
        from app.ai.services.prediction_service import prediction_service
        result = prediction_service.predict_single(
            task_id, save=True, detect_risk=True
        )
        return {"success": True, "data": result}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))




@router.post("/{risk_id}/recommendations")
async def generate_risk_recommendations(
    risk_id: int,
    current_user: Employee = Depends(get_current_user)
):
    try:
        from app.ai.services.risk_recommendation_service import RiskRecommendationService
        service = RiskRecommendationService()
        try:
            result = service.generate_for_risk(
                risk_id, created_by=current_user
            )
            return {"success": True, "data": result}
        finally:
            service.close()
    except ValueError as e:
        raise HTTPException(status_code=404, detail=str(e))
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))




@router.post("/{risk_id}/reassess")
async def reassess_risk(
    risk_id: int,
    current_user: Employee = Depends(get_current_user)
):
    try:
        from app.ai.services.risk_reassessment_service import RiskReassessmentService
        service = RiskReassessmentService()
        try:
            result = service.reassess(
                risk_id, created_by=current_user
            )
            return {"success": True, "data": result}
        finally:
            service.close()
    except ValueError as e:
        raise HTTPException(status_code=404, detail=str(e))
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))


@router.get("/by-major-task/{major_task_id}")
async def get_risks_by_major_task(
    major_task_id: int,
    current_user: Employee = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    task_ids_rows = db.query(OperationalTask.task_id).filter(
        OperationalTask.major_task_id == major_task_id
    ).all()
    task_ids = [t[0] for t in task_ids_rows]


    if not task_ids:
        return {"data": [], "total": 0}


    risks = db.query(Risk).filter(
        Risk.task_id.in_(task_ids)
    ).order_by(desc(Risk.identified_at)).all()


    result = []
    for risk in risks:
        task = db.query(OperationalTask).filter(
            OperationalTask.task_id == risk.task_id
        ).first()


        level = db.query(DictRiskLevel).filter(
            DictRiskLevel.risk_level_id == risk.risk_level_id
        ).first() if risk.risk_level_id else None


        status = db.query(DictStatus).filter(
            DictStatus.status_id == risk.status_id
        ).first() if risk.status_id else None


        mitigations = db.query(RiskMitigation).filter(
            RiskMitigation.risk_id == risk.risk_id
        ).all()


        mitigations_data = []
        for m in mitigations:
            m_status = db.query(DictStatus).filter(
                DictStatus.status_id == m.status_id
            ).first() if m.status_id else None


            m_assigned = db.query(Employee).filter(
                Employee.employee_id == m.assigned_to
            ).first() if m.assigned_to else None


            mitigations_data.append({
                "mitigation_id": m.mitigation_id,
                "action": m.action,
                "assigned_to": {
                    "employee_id": m_assigned.employee_id if m_assigned else None,
                    "full_name": m_assigned.full_name if m_assigned else None
                },
                "status": {
                    "status_id": m_status.status_id if m_status else None,
                    "code": m_status.code if m_status else None,
                    "name_ar": m_status.name_ar if m_status else None,
                    "color_hex": m_status.color_hex if m_status else "#6c757d"
                },
                "due_date": m.due_date.isoformat() if m.due_date else None,
                "completed_at": m.completed_at.isoformat() if m.completed_at else None,
                "notes": m.notes
            })


        ai_recs = db.query(AIRecommendation).filter(
            AIRecommendation.task_id == risk.task_id
        ).order_by(desc(AIRecommendation.created_at)).limit(10).all()


        ai_recs_data = []
        for r in ai_recs:
            parsed_reasoning = None
            reasoning_risk_id = None
            try:
                parsed_reasoning = json.loads(r.reasoning) if r.reasoning else None
                if parsed_reasoning:
                    reasoning_risk_id = parsed_reasoning.get('risk_id')
            except Exception:
                parsed_reasoning = None


            if reasoning_risk_id is not None and reasoning_risk_id != risk.risk_id:
                continue


            priority = None
            if r.priority_id:
                p = db.query(DictPriority).filter(
                    DictPriority.priority_id == r.priority_id
                ).first()
                if p:
                    priority = {"code": p.code, "name_ar": p.name_ar}


            reasoning_text = r.reasoning
            if parsed_reasoning:
                reasoning_text = parsed_reasoning.get('text', r.reasoning)


            ai_recs_data.append({
                "recommendation_id": r.recommendation_id,
                "text": r.text,
                "reasoning": reasoning_text,
                "priority": priority,
                "created_at": r.created_at.isoformat() if r.created_at else None
            })


        result.append({
            "risk_id": risk.risk_id,
            "name": risk.name,
            "description": risk.description,
            "task_id": risk.task_id,
            "task": {
                "task_id": task.task_id if task else None,
                "title": task.title if task else None
            },
            "probability": risk.probability,
            "impact": risk.impact,
            "risk_score": risk.risk_score,
            "risk_level": {
                "risk_level_id": level.risk_level_id if level else None,
                "code": level.code if level else None,
                "name_ar": level.name_ar if level else None,
                "color_hex": level.color_hex if level else "#6c757d"
            },
            "status": {
                "status_id": status.status_id if status else None,
                "code": status.code if status else None,
                "name_ar": status.name_ar if status else None,
                "color_hex": status.color_hex if status else "#6c757d"
            },
            "identified_at": risk.identified_at.isoformat() if risk.identified_at else None,
            "target_date": risk.target_date.isoformat() if risk.target_date else None,
            "mitigations": mitigations_data,
            "ai_recommendations": ai_recs_data
        })


    return {"data": result, "total": len(result)}


@router.get("/{risk_id}/ai-recommendations")
async def get_risk_ai_recommendations(
    risk_id: int,
    current_user: Employee = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    risk = db.query(Risk).filter(Risk.risk_id == risk_id).first()
    if not risk:
        raise HTTPException(status_code=404, detail="الخطر غير موجود")


    if not risk.task_id:
        return {"success": True, "data": []}


    recommendations = db.query(AIRecommendation).filter(
        AIRecommendation.task_id == risk.task_id
    ).order_by(AIRecommendation.created_at.desc()).limit(20).all()


    result = []
    for r in recommendations:
        parsed_reasoning = None
        reasoning_risk_id = None
        try:
            parsed_reasoning = json.loads(r.reasoning) if r.reasoning else None
            if parsed_reasoning:
                reasoning_risk_id = parsed_reasoning.get('risk_id')
        except Exception:
            parsed_reasoning = None


        if reasoning_risk_id is not None and reasoning_risk_id != risk_id:
            continue


        priority = None
        if r.priority_id:
            p = db.query(DictPriority).filter(
                DictPriority.priority_id == r.priority_id
            ).first()
            if p:
                priority = {"code": p.code, "name_ar": p.name_ar}


        reasoning_text = r.reasoning
        if parsed_reasoning:
            reasoning_text = parsed_reasoning.get('text', r.reasoning)


        result.append({
            "recommendation_id": r.recommendation_id,
            "text": r.text,
            "reasoning": reasoning_text,
            "priority": priority,
            "status_id": r.status_id,
            "implemented_at": r.implemented_at.isoformat() if r.implemented_at else None,
            "created_at": r.created_at.isoformat() if r.created_at else None
        })


    return {"success": True, "data": result}

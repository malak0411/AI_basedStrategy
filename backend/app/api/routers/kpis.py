from fastapi import APIRouter, Depends, HTTPException, Query
from sqlalchemy.orm import Session, joinedload
from sqlalchemy import func, desc, asc
from typing import List, Optional
from datetime import datetime
from decimal import Decimal

from app.models import KPI, GoalKPI, KPIMeasurement, StrategicGoal, Employee
from app.models import KPICreate, KPIUpdate, KPIMeasurementCreate, KPIResponse, KPIDetailResponse
from app.core.dependencies import  get_current_user
from app.database import get_db
router = APIRouter(prefix="/api/kpis", tags=["KPIs"])

@router.get("/")
async def get_kpis(
    search: Optional[str] = Query(None),
    category: Optional[str] = Query(None),
    status: Optional[str] = Query(None),
    goal_id: Optional[int] = Query(None),
    page: int = Query(1, ge=1),
    per_page: int = Query(10, ge=1, le=100),
    current_user: Employee = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    query = db.query(KPI).filter(KPI.is_active == True)

    if search:
        query = query.filter(KPI.name.contains(search) | KPI.description.contains(search))

    if category:
        query = query.filter(KPI.category == category)

    if goal_id:
        query = query.join(GoalKPI).filter(GoalKPI.goal_id == goal_id)

    total = query.count()
    kpis = query.offset((page - 1) * per_page).limit(per_page).all()

    result = []
    for kpi in kpis:
        goal_kpi = db.query(GoalKPI).filter(GoalKPI.kpi_id == kpi.kpi_id).first()
        goal = db.query(StrategicGoal).filter(StrategicGoal.goal_id == goal_kpi.goal_id).first() if goal_kpi else None

        latest_measurement = db.query(KPIMeasurement).filter(
            KPIMeasurement.kpi_id == kpi.kpi_id
        ).order_by(desc(KPIMeasurement.measured_at)).first()

        prev_measurement = db.query(KPIMeasurement).filter(
            KPIMeasurement.kpi_id == kpi.kpi_id
        ).order_by(desc(KPIMeasurement.measured_at)).offset(1).first()

        current_value = latest_measurement.value if latest_measurement else None
        target_value = goal_kpi.target_value if goal_kpi else None

        achievement = None
        status_text = "لا يوجد بيانات"
        trend = "stable"

        if current_value is not None and target_value is not None and target_value != 0:
            achievement = (current_value / target_value) * 100

            if achievement >= 100:
                status_text = "محقق"
            elif achievement >= 80:
                status_text = "قريب من الهدف"
            elif achievement >= 60:
                status_text = "يحتاج متابعة"
            else:
                status_text = "متأخر"

            if prev_measurement:
                if current_value > prev_measurement.value:
                    trend = "up"
                elif current_value < prev_measurement.value:
                    trend = "down"
                else:
                    trend = "stable"

        result.append({
            "kpi_id": kpi.kpi_id,
            "name": kpi.name,
            "description": kpi.description,
            "category": kpi.category,
            "unit": kpi.unit,
            "goal_title": goal.title if goal else "غير مرتبط",
            "goal_id": goal.goal_id if goal else None,
            "current_value": float(current_value) if current_value else None,
            "target_value": float(target_value) if target_value else None,
            "achievement_percentage": float(achievement) if achievement else None,
            "trend": trend,
            "status": status_text,
            "last_updated": latest_measurement.measured_at.isoformat() if latest_measurement and latest_measurement.measured_at else None
        })

    return {
        "data": result,
        "total": total,
        "page": page,
        "per_page": per_page,
        "total_pages": (total + per_page - 1) // per_page
    }

@router.get("/goals")
async def get_kpi_goals(
    current_user: Employee = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    goals = db.query(StrategicGoal).filter(
        StrategicGoal.is_active == True
    ).all()

    return {
        "data": [
            {
                "goal_id": goal.goal_id,
                "title": goal.title,
                "description": goal.description
            }
            for goal in goals
        ]
    }

@router.get("/{kpi_id}")
async def get_kpi_detail(
    kpi_id: int,
    current_user: Employee = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    kpi = db.query(KPI).filter(KPI.kpi_id == kpi_id, KPI.is_active == True).first()

    if not kpi:
        raise HTTPException(status_code=404, detail="المؤشر غير موجود")

    goal_kpi = db.query(GoalKPI).filter(GoalKPI.kpi_id == kpi_id).first()
    goal = db.query(StrategicGoal).filter(StrategicGoal.goal_id == goal_kpi.goal_id).first() if goal_kpi else None

    measurements = db.query(KPIMeasurement).filter(
        KPIMeasurement.kpi_id == kpi_id
    ).order_by(asc(KPIMeasurement.measured_at)).all()

    latest_measurement = measurements[-1] if measurements else None
    prev_measurement = measurements[-2] if len(measurements) >= 2 else None

    current_value = latest_measurement.value if latest_measurement else None
    target_value = goal_kpi.target_value if goal_kpi else None
    baseline_value = goal_kpi.baseline_value if goal_kpi else None

    achievement = None
    status_text = "لا يوجد بيانات"
    trend = "stable"

    if current_value is not None and target_value is not None and target_value != 0:
        achievement = (current_value / target_value) * 100

        if achievement >= 100:
            status_text = "محقق"
        elif achievement >= 80:
            status_text = "قريب من الهدف"
        elif achievement >= 60:
            status_text = "يحتاج متابعة"
        else:
            status_text = "متأخر"

        if prev_measurement:
            if current_value > prev_measurement.value:
                trend = "up"
            elif current_value < prev_measurement.value:
                trend = "down"
            else:
                trend = "stable"

    measurements_data = []
    for m in measurements:
        measurements_data.append({
            "measurement_id": m.measurement_id,
            "value": float(m.value),
            "measured_at": m.measured_at.isoformat() if m.measured_at else None,
            "source_type": m.source_type,
            "notes": m.notes,
            "recorded_by": m.recorded_by
        })

    return {
        "kpi_id": kpi.kpi_id,
        "name": kpi.name,
        "description": kpi.description,
        "category": kpi.category,
        "unit": kpi.unit,
        "target_min": float(kpi.target_min) if kpi.target_min else None,
        "target_max": float(kpi.target_max) if kpi.target_max else None,
        "calculation_method": kpi.calculation_method,
        "is_active": kpi.is_active,
        "created_at": kpi.created_at.isoformat() if kpi.created_at else None,
        "updated_at": kpi.updated_at.isoformat() if kpi.updated_at else None,
        "goal": {
            "goal_id": goal.goal_id if goal else None,
            "title": goal.title if goal else None,
            "description": goal.description if goal else None
        },
        "goal_kpi": {
            "target_value": float(target_value) if target_value else None,
            "baseline_value": float(baseline_value) if baseline_value else None,
            "weight": float(goal_kpi.weight) if goal_kpi and goal_kpi.weight else None
        },
        "current_value": float(current_value) if current_value else None,
        "achievement_percentage": float(achievement) if achievement else None,
        "trend": trend,
        "status": status_text,
        "measurements": measurements_data
    }

@router.get("/{kpi_id}/measurements")
async def get_kpi_measurements(
    kpi_id: int,
    current_user: Employee = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    kpi = db.query(KPI).filter(KPI.kpi_id == kpi_id, KPI.is_active == True).first()

    if not kpi:
        raise HTTPException(status_code=404, detail="المؤشر غير موجود")

    measurements = db.query(KPIMeasurement).filter(
        KPIMeasurement.kpi_id == kpi_id
    ).order_by(desc(KPIMeasurement.measured_at)).all()

    goal_kpi = db.query(GoalKPI).filter(GoalKPI.kpi_id == kpi_id).first()
    target_value = goal_kpi.target_value if goal_kpi else None

    result = []
    for m in measurements:
        employee = db.query(Employee).filter(Employee.employee_id == m.recorded_by).first()

        achievement = None
        variance = None

        if target_value and target_value != 0 and m.value is not None:
            achievement = (m.value / target_value) * 100
            variance = m.value - target_value

        result.append({
            "measurement_id": m.measurement_id,
            "value": float(m.value),
            "target_value": float(target_value) if target_value else None,
            "achievement_percentage": float(achievement) if achievement else None,
            "variance": float(variance) if variance else None,
            "measured_at": m.measured_at.isoformat() if m.measured_at else None,
            "source_type": m.source_type,
            "notes": m.notes,
            "recorded_by": employee.full_name if employee else None,
            "recorded_by_id": m.recorded_by
        })

    return {"data": result}

@router.get("/{kpi_id}/measurements/chart")
async def get_kpi_chart_data(
    kpi_id: int,
    current_user: Employee = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    kpi = db.query(KPI).filter(KPI.kpi_id == kpi_id, KPI.is_active == True).first()

    if not kpi:
        raise HTTPException(status_code=404, detail="المؤشر غير موجود")

    measurements = db.query(KPIMeasurement).filter(
        KPIMeasurement.kpi_id == kpi_id
    ).order_by(asc(KPIMeasurement.measured_at)).all()

    goal_kpi = db.query(GoalKPI).filter(GoalKPI.kpi_id == kpi_id).first()
    target_value = float(goal_kpi.target_value) if goal_kpi and goal_kpi.target_value else None

    chart_data = {
        "labels": [m.measured_at.strftime("%Y-%m-%d") if m.measured_at else "" for m in measurements],
        "actual": [float(m.value) for m in measurements],
        "target": [target_value for _ in measurements] if target_value else []
    }

    return chart_data

@router.post("/")
async def create_kpi(
    data: dict,
    current_user: Employee = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    name = data.get("name")
    description = data.get("description")
    category = data.get("category")
    unit = data.get("unit")
    target_min = data.get("target_min")
    target_max = data.get("target_max")
    calculation_method = data.get("calculation_method")
    goal_id = data.get("goal_id")
    target_value = data.get("target_value")
    baseline_value = data.get("baseline_value")
    weight = data.get("weight", 1.0)

    if not name:
        raise HTTPException(status_code=400, detail="اسم المؤشر مطلوب")

    if not goal_id or not target_value:
        raise HTTPException(status_code=400, detail="الهدف الاستراتيجي والقيمة المستهدفة مطلوبة")

    existing = db.query(KPI).filter(KPI.name == name, KPI.is_active == True).first()
    if existing:
        raise HTTPException(status_code=400, detail="مؤشر بهذا الاسم موجود بالفعل")

    existing_goal_kpi = db.query(GoalKPI).filter(
        GoalKPI.goal_id == goal_id,
        GoalKPI.kpi_id == KPI.kpi_id,
        KPI.is_active == True
    ).first()

    if existing_goal_kpi:
        raise HTTPException(status_code=400, detail="هذا المؤشر مرتبط بالفعل بهذا الهدف")

    new_kpi = KPI(
        name=name,
        description=description,
        category=category,
        unit=unit,
        target_min=target_min,
        target_max=target_max,
        calculation_method=calculation_method,
        is_active=True
    )

    db.add(new_kpi)
    db.flush()

    new_goal_kpi = GoalKPI(
        goal_id=goal_id,
        kpi_id=new_kpi.kpi_id,
        target_value=target_value,
        baseline_value=baseline_value,
        weight=weight
    )

    db.add(new_goal_kpi)
    db.commit()
    db.refresh(new_kpi)

    return {
        "success": True,
        "message": "تم إنشاء المؤشر بنجاح",
        "kpi_id": new_kpi.kpi_id
    }

@router.put("/{kpi_id}")
async def update_kpi(
    kpi_id: int,
    data: dict,
    current_user: Employee = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    kpi = db.query(KPI).filter(KPI.kpi_id == kpi_id, KPI.is_active == True).first()

    if not kpi:
        raise HTTPException(status_code=404, detail="المؤشر غير موجود")

    if "name" in data and data["name"]:
        existing = db.query(KPI).filter(
            KPI.name == data["name"],
            KPI.kpi_id != kpi_id,
            KPI.is_active == True
        ).first()
        if existing:
            raise HTTPException(status_code=400, detail="مؤشر بهذا الاسم موجود بالفعل")
        kpi.name = data["name"]

    if "description" in data:
        kpi.description = data["description"]

    if "category" in data:
        kpi.category = data["category"]

    if "unit" in data:
        kpi.unit = data["unit"]

    if "target_min" in data:
        kpi.target_min = data["target_min"]

    if "target_max" in data:
        kpi.target_max = data["target_max"]

    if "calculation_method" in data:
        kpi.calculation_method = data["calculation_method"]

    if "goal_id" in data and "target_value" in data:
        goal_kpi = db.query(GoalKPI).filter(GoalKPI.kpi_id == kpi_id).first()

        if not goal_kpi:
            new_goal_kpi = GoalKPI(
                goal_id=data["goal_id"],
                kpi_id=kpi_id,
                target_value=data["target_value"],
                baseline_value=data.get("baseline_value"),
                weight=data.get("weight", 1.0)
            )
            db.add(new_goal_kpi)
        else:
            goal_kpi.goal_id = data["goal_id"]
            goal_kpi.target_value = data["target_value"]

            if "baseline_value" in data:
                goal_kpi.baseline_value = data["baseline_value"]

            if "weight" in data:
                goal_kpi.weight = data["weight"]

    db.commit()

    return {
        "success": True,
        "message": "تم تحديث المؤشر بنجاح"
    }

@router.delete("/{kpi_id}")
async def delete_kpi(
    kpi_id: int,
    current_user: Employee = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    kpi = db.query(KPI).filter(KPI.kpi_id == kpi_id, KPI.is_active == True).first()

    if not kpi:
        raise HTTPException(status_code=404, detail="المؤشر غير موجود")

    measurements = db.query(KPIMeasurement).filter(KPIMeasurement.kpi_id == kpi_id).count()

    if measurements > 0:
        kpi.is_active = False
        db.commit()
        return {
            "success": True,
            "message": "تم تعطيل المؤشر لوجود قياسات مرتبطة به"
        }

    goal_kpi = db.query(GoalKPI).filter(GoalKPI.kpi_id == kpi_id).first()

    if goal_kpi:
        db.delete(goal_kpi)

    db.delete(kpi)
    db.commit()

    return {
        "success": True,
        "message": "تم حذف المؤشر بنجاح"
    }

@router.post("/{kpi_id}/measurements")
async def create_measurement(
    kpi_id: int,
    data: dict,
    current_user: Employee = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    kpi = db.query(KPI).filter(KPI.kpi_id == kpi_id, KPI.is_active == True).first()

    if not kpi:
        raise HTTPException(status_code=404, detail="المؤشر غير موجود")

    value = data.get("value")
    measured_at = data.get("measured_at")
    notes = data.get("notes")

    if value is None:
        raise HTTPException(status_code=400, detail="القيمة مطلوبة")

    new_measurement = KPIMeasurement(
        kpi_id=kpi_id,
        value=value,
        source_type="manual",
        source_id=None,
        measured_at=measured_at if measured_at else datetime.now(),
        recorded_by=current_user.employee_id,
        notes=notes
    )

    db.add(new_measurement)
    db.commit()
    db.refresh(new_measurement)

    return {
        "success": True,
        "message": "تم تسجيل القياس بنجاح",
        "measurement_id": new_measurement.measurement_id
    }

@router.delete("/measurements/{measurement_id}")
async def delete_measurement(
    measurement_id: int,
    current_user: Employee = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    measurement = db.query(KPIMeasurement).filter(
        KPIMeasurement.measurement_id == measurement_id
    ).first()

    if not measurement:
        raise HTTPException(status_code=404, detail="القياس غير موجود")

    if measurement.recorded_by != current_user.employee_id:
        raise HTTPException(status_code=403, detail="لا يمكنك حذف قياس ليس لك")

    db.delete(measurement)
    db.commit()

    return {
        "success": True,
        "message": "تم حذف القياس بنجاح"
    }

from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session
from app.database import get_db
from app.models import KPI, KPIMeasurement
from pydantic import BaseModel
from typing import Optional
from datetime import datetime

router = APIRouter(prefix="/api/kpis", tags=["KPIs"])

# ============================================================
# Schemas
# ============================================================
class KPICreate(BaseModel):
    name: str
    description: Optional[str] = ""
    category: Optional[str] = ""
    unit: Optional[str] = "%"
    target_min: Optional[float] = 0
    target_max: Optional[float] = 100
    calculation_method: Optional[str] = ""

class KPIUpdate(BaseModel):
    name: Optional[str] = None
    description: Optional[str] = None
    category: Optional[str] = None
    unit: Optional[str] = None
    target_min: Optional[float] = None
    target_max: Optional[float] = None

class MeasurementCreate(BaseModel):
    value: float
    measured_at: Optional[str] = None
    notes: Optional[str] = ""
    recorded_by: Optional[int] = None

# ============================================================
# GET
# ============================================================

@router.get("/")
async def get_kpis(db: Session = Depends(get_db)):
    try:
        kpis = db.query(KPI).all()
        result = []
        for k in kpis:
            last = db.query(KPIMeasurement).filter(KPIMeasurement.kpi_id == k.kpi_id).order_by(KPIMeasurement.measured_at.desc()).first()
            result.append({
                "id": k.kpi_id, "name": k.name, "title": k.name,
                "description": k.description or "", "category": k.category or "",
                "unit": k.unit or "%", "type": "percentage",
                "target_value": float(k.target_max) if k.target_max else 100,
                "current_value": float(last.value) if last else 0,
                "target_min": float(k.target_min) if k.target_min else 0,
                "target_max": float(k.target_max) if k.target_max else 100
            })
        return {"success": True, "data": result}
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")

@router.get("/department")
async def department_kpis(db: Session = Depends(get_db)):
    try:
        kpis = db.query(KPI).limit(4).all()
        result = [{"kpi_name": k.name, "current_value": float(k.target_max or 80), "target_value": float(k.target_max or 100)} for k in kpis]
        return {"success": True, "data": result}
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")

@router.get("/{kpi_id}")
async def get_kpi(kpi_id: int, db: Session = Depends(get_db)):
    try:
        k = db.query(KPI).filter(KPI.kpi_id == kpi_id).first()
        if not k: raise HTTPException(status_code=404, detail="غير موجود")
        last = db.query(KPIMeasurement).filter(KPIMeasurement.kpi_id == kpi_id).order_by(KPIMeasurement.measured_at.desc()).first()
        return {"success": True, "data": {
            "id": k.kpi_id, "name": k.name, "title": k.name,
            "description": k.description or "", "type": "percentage", "unit": k.unit or "%",
            "target_value": float(k.target_max) if k.target_max else 100,
            "current_value": float(last.value) if last else 0,
            "target_min": float(k.target_min) if k.target_min else 0,
            "target_max": float(k.target_max) if k.target_max else 100
        }}
    except HTTPException: raise
    except Exception as e: raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")

@router.get("/{kpi_id}/measurements")
async def get_measurements(kpi_id: int, db: Session = Depends(get_db)):
    try:
        kpi = db.query(KPI).filter(KPI.kpi_id == kpi_id).first()
        measurements = db.query(KPIMeasurement).filter(KPIMeasurement.kpi_id == kpi_id).order_by(KPIMeasurement.measured_at.desc()).limit(50).all()
        result = [{"id": m.measurement_id, "value": float(m.value), "measurement_date": m.measured_at.isoformat() if m.measured_at else None, "created_at": m.measured_at.isoformat() if m.measured_at else None, "notes": m.notes or ""} for m in measurements]
        return {"success": True, "data": result, "kpi": {"id": kpi.kpi_id, "name": kpi.name} if kpi else {}}
    except Exception as e: raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")

# ============================================================
# POST/PUT/DELETE - KPIs
# ============================================================

@router.post("/")
async def create_kpi(data: KPICreate, db: Session = Depends(get_db)):
    try:
        kpi = KPI(name=data.name, description=data.description, category=data.category, unit=data.unit, target_min=data.target_min, target_max=data.target_max, calculation_method=data.calculation_method, is_active=True)
        db.add(kpi)
        db.commit()
        db.refresh(kpi)
        return {"success": True, "data": {"id": kpi.kpi_id, "name": kpi.name}}
    except Exception as e:
        db.rollback()
        raise HTTPException(status_code=500, detail=str(e))

@router.put("/{kpi_id}")
async def update_kpi(kpi_id: int, data: KPIUpdate, db: Session = Depends(get_db)):
    try:
        k = db.query(KPI).filter(KPI.kpi_id == kpi_id).first()
        if not k: raise HTTPException(status_code=404, detail="غير موجود")
        if data.name is not None: k.name = data.name
        if data.description is not None: k.description = data.description
        if data.category is not None: k.category = data.category
        if data.unit is not None: k.unit = data.unit
        if data.target_min is not None: k.target_min = data.target_min
        if data.target_max is not None: k.target_max = data.target_max
        k.updated_at = datetime.now()
        db.commit()
        return {"success": True, "message": "تم التحديث"}
    except HTTPException: raise
    except Exception as e:
        db.rollback()
        raise HTTPException(status_code=500, detail=str(e))

@router.delete("/{kpi_id}")
async def delete_kpi(kpi_id: int, db: Session = Depends(get_db)):
    try:
        k = db.query(KPI).filter(KPI.kpi_id == kpi_id).first()
        if not k: raise HTTPException(status_code=404, detail="غير موجود")
        db.delete(k)
        db.commit()
        return {"success": True, "message": "تم الحذف"}
    except HTTPException: raise
    except Exception as e:
        db.rollback()
        raise HTTPException(status_code=500, detail=str(e))

# ============================================================
# POST - قياسات KPIs
# ============================================================

@router.post("/{kpi_id}/measurements")
async def create_measurement(kpi_id: int, data: MeasurementCreate, db: Session = Depends(get_db)):
    try:
        m = KPIMeasurement(
            kpi_id=kpi_id, value=data.value,
            measured_at=datetime.strptime(data.measured_at, "%Y-%m-%d") if data.measured_at else datetime.now(),
            notes=data.notes, recorded_by=data.recorded_by
        )
        db.add(m)
        db.commit()
        db.refresh(m)
        return {"success": True, "data": {"id": m.measurement_id, "value": float(m.value)}}
    except Exception as e:
        db.rollback()
        raise HTTPException(status_code=500, detail=str(e))

@router.delete("/measurements/{measurement_id}")
async def delete_measurement(measurement_id: int, db: Session = Depends(get_db)):
    try:
        m = db.query(KPIMeasurement).filter(KPIMeasurement.measurement_id == measurement_id).first()
        if not m: raise HTTPException(status_code=404, detail="غير موجود")
        db.delete(m)
        db.commit()
        return {"success": True, "message": "تم الحذف"}
    except HTTPException: raise
    except Exception as e:
        db.rollback()
        raise HTTPException(status_code=500, detail=str(e))

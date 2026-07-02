from fastapi import APIRouter, Depends, HTTPException, Query
from sqlalchemy.orm import Session
from app.database import get_db
from app.models import KPI, KPIMeasurement

router = APIRouter(prefix="/api/kpis", tags=["KPIs"])

@router.get("/")
async def get_kpis(db: Session = Depends(get_db)):
    try:
        kpis = db.query(KPI).all()
        result = []
        for k in kpis:
            last_measurement = db.query(KPIMeasurement).filter(
                KPIMeasurement.kpi_id == k.kpi_id
            ).order_by(KPIMeasurement.measured_at.desc()).first()

            result.append({
                "id": k.kpi_id,
                "name": k.name,
                "title": k.name,
                "description": k.description or "",
                "category": k.category or "",
                "unit": k.unit or "%",
                "type": "percentage",
                "target_value": float(k.target_max) if k.target_max else 100,
                "current_value": float(last_measurement.value) if last_measurement else 0,
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
        if not k:
            raise HTTPException(status_code=404, detail="المؤشر غير موجود")
        last_measurement = db.query(KPIMeasurement).filter(KPIMeasurement.kpi_id == kpi_id).order_by(KPIMeasurement.measured_at.desc()).first()
        return {"success": True, "data": {
            "id": k.kpi_id, "name": k.name, "title": k.name,
            "description": k.description or "", "type": "percentage", "unit": k.unit or "%",
            "target_value": float(k.target_max) if k.target_max else 100,
            "current_value": float(last_measurement.value) if last_measurement else 0,
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

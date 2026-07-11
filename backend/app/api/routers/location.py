from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session
from app.database import get_db
from app.models import LocationLog
from pydantic import BaseModel
from typing import Optional
from datetime import datetime

router = APIRouter(prefix="/api/location-logs", tags=["Location"])

class LocationCreate(BaseModel):
    employee_id: int
    task_id: Optional[int] = None
    latitude: float
    longitude: float
    accuracy: Optional[float] = None
    source: Optional[str] = "manual"

@router.get("/")
async def location_logs(db: Session = Depends(get_db)):
    try:
        logs = db.query(LocationLog).order_by(LocationLog.recorded_at.desc()).limit(50).all()
        result = [{"employee_name": f"موظف {l.employee_id}", "employee_id": l.employee_id, "latitude": float(l.latitude) if l.latitude else 0, "longitude": float(l.longitude) if l.longitude else 0, "created_at": l.recorded_at.isoformat() if l.recorded_at else None} for l in logs]
        return {"success": True, "data": result}
    except Exception as e: raise HTTPException(status_code=500, detail=str(e))

@router.get("/employee/{employee_id}")
async def employee_locations(employee_id: int, db: Session = Depends(get_db)):
    try:
        logs = db.query(LocationLog).filter(LocationLog.employee_id == employee_id).order_by(LocationLog.recorded_at.desc()).limit(50).all()
        result = [{"latitude": float(l.latitude) if l.latitude else 0, "longitude": float(l.longitude) if l.longitude else 0, "created_at": l.recorded_at.isoformat() if l.recorded_at else None} for l in logs]
        return {"success": True, "data": result}
    except Exception as e: raise HTTPException(status_code=500, detail=str(e))

@router.get("/history")
async def location_history(db: Session = Depends(get_db)):
    try:
        logs = db.query(LocationLog).order_by(LocationLog.recorded_at.desc()).limit(100).all()
        from collections import Counter
        emp_counts = Counter(l.employee_id for l in logs)
        result = [{"employee_name": f"موظف {eid}", "total_logs": count} for eid, count in emp_counts.most_common(20)]
        return {"success": True, "data": result}
    except Exception as e: raise HTTPException(status_code=500, detail=str(e))

@router.post("/")
async def create_location(data: LocationCreate, db: Session = Depends(get_db)):
    try:
        loc = LocationLog(employee_id=data.employee_id, task_id=data.task_id, latitude=data.latitude, longitude=data.longitude, accuracy=data.accuracy, source=data.source, recorded_at=datetime.now())
        db.add(loc)
        db.commit()
        db.refresh(loc)
        return {"success": True, "data": {"id": loc.location_id, "message": "تم تسجيل الموقع"}}
    except Exception as e:
        db.rollback()
        raise HTTPException(status_code=500, detail=str(e))

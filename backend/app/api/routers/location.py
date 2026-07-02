from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session
from app.database import get_db
from app.models import LocationLog

router = APIRouter(prefix="/api/location-logs", tags=["Location"])

@router.get("/")
async def location_logs(db: Session = Depends(get_db)):
    try:
        logs = db.query(LocationLog).order_by(LocationLog.recorded_at.desc()).limit(50).all()
        return {"success": True, "data": [{"employee_name": f"موظف {l.employee_id}", "employee_id": l.employee_id, "latitude": float(l.latitude) if l.latitude else 0, "longitude": float(l.longitude) if l.longitude else 0, "created_at": l.recorded_at.isoformat() if l.recorded_at else None} for l in logs]}
    except Exception as e: raise HTTPException(status_code=500, detail=str(e))

@router.get("/employee/{employee_id}")
async def employee_locations(employee_id: int, db: Session = Depends(get_db)):
    try:
        logs = db.query(LocationLog).filter(LocationLog.employee_id == employee_id).order_by(LocationLog.recorded_at.desc()).limit(50).all()
        return {"success": True, "data": [{"latitude": float(l.latitude) if l.latitude else 0, "longitude": float(l.longitude) if l.longitude else 0, "created_at": l.recorded_at.isoformat() if l.recorded_at else None} for l in logs]}
    except Exception as e: raise HTTPException(status_code=500, detail=str(e))

from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session
from app.database import get_db
from app.models import Risk, RiskMitigation

router = APIRouter(prefix="/api/risks", tags=["Risks"])
RISK_LEVEL_MAP = {1: "منخفض", 2: "متوسط", 3: "عالي", 4: "حرج"}

@router.get("/")
async def get_risks(db: Session = Depends(get_db)):
    try:
        risks = db.query(Risk).all()
        result = [{"id": r.risk_id, "name": r.name, "title": r.name, "description": r.description or "", "level": RISK_LEVEL_MAP.get(r.risk_level_id, "غير محدد"), "probability": r.probability or "", "impact": r.impact or "", "status": r.status_id or "نشط"} for r in risks]
        return {"success": True, "data": result}
    except Exception as e: raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")

@router.get("/all")
async def all_risks(db: Session = Depends(get_db)):
    try:
        risks = db.query(Risk).order_by(Risk.risk_level_id.desc()).limit(20).all()
        return {"success": True, "data": [{"id": r.risk_id, "name": r.name, "title": r.name, "level": RISK_LEVEL_MAP.get(r.risk_level_id, "غير محدد"), "status": r.status_id or "نشط"} for r in risks]}
    except Exception as e: raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")

@router.get("/department")
async def department_risks(db: Session = Depends(get_db)):
    try:
        risks = db.query(Risk).limit(10).all()
        return {"success": True, "data": [{"id": r.risk_id, "name": r.name, "level": RISK_LEVEL_MAP.get(r.risk_level_id, "غير محدد"), "status": r.status_id or "نشط"} for r in risks]}
    except Exception as e: raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")

@router.get("/{risk_id}")
async def risk_detail(risk_id: int, db: Session = Depends(get_db)):
    try:
        r = db.query(Risk).filter(Risk.risk_id == risk_id).first()
        if not r: raise HTTPException(status_code=404, detail="الخطر غير موجود")
        return {"success": True, "data": {"id": r.risk_id, "name": r.name, "title": r.name, "description": r.description or "", "level": RISK_LEVEL_MAP.get(r.risk_level_id, "غير محدد"), "probability": r.probability or "", "impact": r.impact or "", "status": r.status_id or "نشط"}}
    except HTTPException: raise
    except Exception as e: raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")

@router.get("/{risk_id}/mitigations")
async def risk_mitigations(risk_id: int, db: Session = Depends(get_db)):
    try:
        mitigations = db.query(RiskMitigation).filter(RiskMitigation.risk_id == risk_id).all()
        return {"success": True, "data": [{"id": m.mitigation_id, "name": m.action, "title": m.action, "description": m.action, "status": m.status_id or "نشط"} for m in mitigations]}
    except Exception as e: raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")

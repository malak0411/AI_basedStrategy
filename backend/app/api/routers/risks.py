from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session
from app.database import get_db
from app.models import Risk, RiskMitigation
from pydantic import BaseModel
from typing import Optional

router = APIRouter(prefix="/api/risks", tags=["Risks"])

RISK_LEVEL_MAP = {1: "منخفض", 2: "متوسط", 3: "عالي", 4: "حرج"}

# ============================================================
# Schemas
# ============================================================
class RiskCreate(BaseModel):
    task_id: Optional[int] = None
    name: str
    description: Optional[str] = ""
    risk_level_id: Optional[int] = 2
    probability: Optional[int] = 50
    impact: Optional[int] = 50
    status_id: Optional[int] = 1

class RiskUpdate(BaseModel):
    name: Optional[str] = None
    description: Optional[str] = None
    risk_level_id: Optional[int] = None
    probability: Optional[int] = None
    impact: Optional[int] = None
    status_id: Optional[int] = None

class MitigationCreate(BaseModel):
    action: str
    assigned_to: Optional[int] = None
    due_date: Optional[str] = None
    notes: Optional[str] = ""

# ============================================================
# GET
# ============================================================

@router.get("/")
async def get_risks(db: Session = Depends(get_db)):
    """قائمة المخاطر"""
    try:
        risks = db.query(Risk).all()
        result = [{
            "id": r.risk_id, "name": r.name, "title": r.name,
            "description": r.description or "",
            "level": RISK_LEVEL_MAP.get(r.risk_level_id, "غير محدد"),
            "probability": r.probability or "", "impact": r.impact or "",
            "status": r.status_id or "نشط"
        } for r in risks]
        return {"success": True, "data": result}
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")

@router.get("/all")
async def all_risks(db: Session = Depends(get_db)):
    """جميع المخاطر"""
    try:
        risks = db.query(Risk).order_by(Risk.risk_level_id.desc()).limit(20).all()
        result = [{
            "id": r.risk_id, "name": r.name, "title": r.name,
            "level": RISK_LEVEL_MAP.get(r.risk_level_id, "غير محدد"),
            "status": r.status_id or "نشط"
        } for r in risks]
        return {"success": True, "data": result}
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")

@router.get("/department")
async def department_risks(db: Session = Depends(get_db)):
    """مخاطر الإدارة"""
    try:
        risks = db.query(Risk).limit(10).all()
        result = [{
            "id": r.risk_id, "name": r.name,
            "level": RISK_LEVEL_MAP.get(r.risk_level_id, "غير محدد"),
            "status": r.status_id or "نشط"
        } for r in risks]
        return {"success": True, "data": result}
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")

@router.get("/{risk_id}")
async def risk_detail(risk_id: int, db: Session = Depends(get_db)):
    """تفاصيل خطر"""
    try:
        r = db.query(Risk).filter(Risk.risk_id == risk_id).first()
        if not r:
            raise HTTPException(status_code=404, detail="الخطر غير موجود")
        return {
            "success": True,
            "data": {
                "id": r.risk_id, "name": r.name, "title": r.name,
                "description": r.description or "",
                "level": RISK_LEVEL_MAP.get(r.risk_level_id, "غير محدد"),
                "probability": r.probability or "", "impact": r.impact or "",
                "status": r.status_id or "نشط"
            }
        }
    except HTTPException:
        raise
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")

@router.get("/{risk_id}/mitigations")
async def risk_mitigations(risk_id: int, db: Session = Depends(get_db)):
    """خطط تخفيف الخطر"""
    try:
        mitigations = db.query(RiskMitigation).filter(RiskMitigation.risk_id == risk_id).all()
        result = [{
            "id": m.mitigation_id, "name": m.action, "title": m.action,
            "description": m.action, "status": m.status_id or "نشط"
        } for m in mitigations]
        return {"success": True, "data": result}
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")

# ============================================================
# POST/PUT/DELETE - المخاطر
# ============================================================

@router.post("/")
async def create_risk(data: RiskCreate, db: Session = Depends(get_db)):
    """تسجيل خطر جديد"""
    try:
        risk = Risk(
            task_id=data.task_id,
            name=data.name,
            description=data.description,
            risk_level_id=data.risk_level_id,
            probability=data.probability,
            impact=data.impact,
            status_id=data.status_id
        )
        db.add(risk)
        db.commit()
        db.refresh(risk)
        return {"success": True, "data": {"id": risk.risk_id, "name": risk.name}}
    except Exception as e:
        db.rollback()
        raise HTTPException(status_code=500, detail=str(e))

@router.put("/{risk_id}")
async def update_risk(risk_id: int, data: RiskUpdate, db: Session = Depends(get_db)):
    """تحديث خطر"""
    try:
        r = db.query(Risk).filter(Risk.risk_id == risk_id).first()
        if not r:
            raise HTTPException(status_code=404, detail="غير موجود")
        if data.name is not None: r.name = data.name
        if data.description is not None: r.description = data.description
        if data.risk_level_id is not None: r.risk_level_id = data.risk_level_id
        if data.probability is not None: r.probability = data.probability
        if data.impact is not None: r.impact = data.impact
        if data.status_id is not None: r.status_id = data.status_id
        db.commit()
        return {"success": True, "message": "تم التحديث"}
    except HTTPException:
        raise
    except Exception as e:
        db.rollback()
        raise HTTPException(status_code=500, detail=str(e))

@router.delete("/{risk_id}")
async def delete_risk(risk_id: int, db: Session = Depends(get_db)):
    """حذف خطر"""
    try:
        r = db.query(Risk).filter(Risk.risk_id == risk_id).first()
        if not r:
            raise HTTPException(status_code=404, detail="غير موجود")
        db.delete(r)
        db.commit()
        return {"success": True, "message": "تم الحذف"}
    except HTTPException:
        raise
    except Exception as e:
        db.rollback()
        raise HTTPException(status_code=500, detail=str(e))

# ============================================================
# POST - خطط التخفيف
# ============================================================

@router.post("/{risk_id}/mitigations")
async def create_mitigation(risk_id: int, data: MitigationCreate, db: Session = Depends(get_db)):
    """إضافة خطة تخفيف"""
    try:
        from datetime import datetime as dt
        mitigation = RiskMitigation(
            risk_id=risk_id,
            action=data.action,
            assigned_to=data.assigned_to,
            due_date=dt.strptime(data.due_date, "%Y-%m-%d") if data.due_date else None,
            notes=data.notes,
            status_id=1
        )
        db.add(mitigation)
        db.commit()
        db.refresh(mitigation)
        return {"success": True, "data": {"id": mitigation.mitigation_id, "message": "تمت الإضافة"}}
    except Exception as e:
        db.rollback()
        raise HTTPException(status_code=500, detail=str(e))

@router.delete("/mitigations/{mitigation_id}")
async def delete_mitigation(mitigation_id: int, db: Session = Depends(get_db)):
    """حذف خطة تخفيف"""
    try:
        m = db.query(RiskMitigation).filter(RiskMitigation.mitigation_id == mitigation_id).first()
        if not m:
            raise HTTPException(status_code=404, detail="غير موجودة")
        db.delete(m)
        db.commit()
        return {"success": True, "message": "تم الحذف"}
    except HTTPException:
        raise
    except Exception as e:
        db.rollback()
        raise HTTPException(status_code=500, detail=str(e))

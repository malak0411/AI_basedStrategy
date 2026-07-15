from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session
from datetime import datetime
from app.database import get_db
from app.models import SystemConfig
from pydantic import BaseModel
from typing import Optional

router = APIRouter(prefix="/api/system-config", tags=["System Config"])

class ConfigCreate(BaseModel):
    config_key: str
    config_value: str
    description: Optional[str] = ""

class ConfigUpdate(BaseModel):
    config_value: str
    description: Optional[str] = None

# ============================================================
# GET - جميع الإعدادات (قائمة واحدة)
# ============================================================
@router.get("")
async def get_all_configs(db: Session = Depends(get_db)):
    """جميع إعدادات النظام في قائمة واحدة"""
    try:
        configs = db.query(SystemConfig).order_by(SystemConfig.config_id).all()
        result = [{
            "config_id": c.config_id,
            "config_key": c.config_key,
            "config_value": c.config_value,
            "description": c.description or "",
            "updated_at": c.updated_at.isoformat() if c.updated_at else None
        } for c in configs]
        return {"success": True, "data": result}
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")

# ============================================================
# POST - إضافة إعداد جديد
# ============================================================
@router.post("")
async def create_config(data: ConfigCreate, db: Session = Depends(get_db)):
    """إضافة إعداد جديد"""
    try:
        existing = db.query(SystemConfig).filter(
            SystemConfig.config_key == data.config_key
        ).first()
        if existing:
            raise HTTPException(status_code=400, detail="الإعداد موجود بالفعل")

        config = SystemConfig(
            config_key=data.config_key,
            config_value=data.config_value,
            description=data.description or ""
        )
        db.add(config)
        db.commit()
        db.refresh(config)
        return {
            "success": True,
            "data": {
                "id": config.config_id,
                "key": config.config_key,
                "message": "تم إضافة الإعداد بنجاح"
            }
        }
    except HTTPException:
        raise
    except Exception as e:
        db.rollback()
        raise HTTPException(status_code=500, detail=str(e))

# ============================================================
# PUT - تحديث إعداد
# ============================================================
@router.put("/{config_key}")
async def update_config(config_key: str, data: ConfigUpdate, db: Session = Depends(get_db)):
    """تحديث قيمة إعداد"""
    try:
        config = db.query(SystemConfig).filter(SystemConfig.config_key == config_key).first()
        if not config:
            raise HTTPException(status_code=404, detail="الإعداد غير موجود")

        config.config_value = data.config_value
        if data.description is not None:
            config.description = data.description
        config.updated_at = datetime.now()
        db.commit()

        return {
            "success": True,
            "message": f"تم تحديث {config_key}",
            "data": {
                "config_key": config.config_key,
                "config_value": config.config_value,
                "updated_at": config.updated_at.isoformat()
            }
        }
    except HTTPException:
        raise
    except Exception as e:
        db.rollback()
        raise HTTPException(status_code=500, detail=str(e))

# ============================================================
# DELETE - حذف إعداد
# ============================================================
@router.delete("/{config_key}")
async def delete_config(config_key: str, db: Session = Depends(get_db)):
    """حذف إعداد"""
    try:
        config = db.query(SystemConfig).filter(SystemConfig.config_key == config_key).first()
        if not config:
            raise HTTPException(status_code=404, detail="الإعداد غير موجود")
        db.delete(config)
        db.commit()
        return {"success": True, "message": f"تم حذف {config_key}"}
    except HTTPException:
        raise
    except Exception as e:
        db.rollback()
        raise HTTPException(status_code=500, detail=str(e))

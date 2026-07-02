from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session
from datetime import datetime
from app.database import get_db
from app.models import SystemConfig
from pydantic import BaseModel

router = APIRouter(prefix="/api/system-config", tags=["System Config"])

class UpdateConfigSchema(BaseModel):
    config_value: str

@router.get("/")
async def get_all_configs(db: Session = Depends(get_db)):
    try:
        configs = db.query(SystemConfig).all()
        return {"success": True, "data": [{"config_id": c.config_id, "config_key": c.config_key, "config_value": c.config_value, "description": c.description or "", "updated_at": c.updated_at.isoformat() if c.updated_at else None} for c in configs]}
    except Exception as e: raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")

@router.get("/grouped/all")
async def get_grouped_configs(db: Session = Depends(get_db)):
    try:
        configs = db.query(SystemConfig).all()
        result = {"general": {}, "financial": {}, "operational": {}}
        for c in configs:
            data = {"config_id": c.config_id, "config_value": c.config_value, "description": c.description or ""}
            if c.config_key in ["sector_type", "ministry_name", "timezone"]: result["general"][c.config_key] = data
            elif c.config_key in ["currency", "fiscal_year_start"]: result["financial"][c.config_key] = data
            else: result["operational"][c.config_key] = data
        return {"success": True, "data": result}
    except Exception as e: raise HTTPException(status_code=500, detail=str(e))

@router.put("/{config_key}")
async def update_config(config_key: str, data: UpdateConfigSchema, db: Session = Depends(get_db)):
    try:
        config = db.query(SystemConfig).filter(SystemConfig.config_key == config_key).first()
        if not config: raise HTTPException(status_code=404, detail="الإعداد غير موجود")
        config.config_value = data.config_value
        config.updated_at = datetime.now()
        db.commit()
        return {"success": True, "message": f"تم تحديث {config_key}", "data": {"config_key": config.config_key, "config_value": config.config_value, "updated_at": config.updated_at.isoformat()}}
    except HTTPException: raise
    except Exception as e:
        db.rollback()
        raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")

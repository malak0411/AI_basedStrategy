from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session
from app.database import get_db
from app.models import DictStatus, DictPriority, DictRiskLevel, DictRoleType, DictTransactionType
from pydantic import BaseModel
from typing import Optional

router = APIRouter(prefix="/api/dict", tags=["Dictionaries"])

class StatusCreate(BaseModel):
    code: str
    name_ar: str
    name_en: str
    category: str
    color_hex: Optional[str] = "#6c757d"

class PriorityCreate(BaseModel):
    code: str
    name_ar: str
    name_en: str
    level: int
    color_hex: Optional[str] = "#6c757d"

class RiskLevelCreate(BaseModel):
    code: str
    name_ar: str
    name_en: str
    min_score: int
    max_score: int
    color_hex: Optional[str] = "#6c757d"

class RoleTypeCreate(BaseModel):
    code: str
    name_ar: str
    name_en: str
    description: Optional[str] = ""

class TransactionTypeCreate(BaseModel):
    code: str
    name_ar: str
    name_en: str
    sign: int

@router.get("/statuses")
async def get_statuses(db: Session = Depends(get_db)):
    items = db.query(DictStatus).all()
    return {"success": True, "data": [{c.name: getattr(i, c.name) for c in i.__table__.columns} for i in items]}

@router.post("/statuses")
async def create_status(data: StatusCreate, db: Session = Depends(get_db)):
    item = DictStatus(**data.model_dump())
    db.add(item)
    db.commit()
    return {"success": True}

@router.put("/statuses/{id}")
async def update_status(id: int, data: StatusCreate, db: Session = Depends(get_db)):
    item = db.query(DictStatus).filter(DictStatus.status_id == id).first()
    if not item: raise HTTPException(404)
    for k, v in data.model_dump().items(): setattr(item, k, v)
    db.commit()
    return {"success": True}

@router.delete("/statuses/{id}")
async def delete_status(id: int, db: Session = Depends(get_db)):
    item = db.query(DictStatus).filter(DictStatus.status_id == id).first()
    if not item: raise HTTPException(404)
    db.delete(item)
    db.commit()
    return {"success": True}

@router.get("/priorities")
async def get_priorities(db: Session = Depends(get_db)):
    items = db.query(DictPriority).all()
    return {"success": True, "data": [{c.name: getattr(i, c.name) for c in i.__table__.columns} for i in items]}

@router.post("/priorities")
async def create_priority(data: PriorityCreate, db: Session = Depends(get_db)):
    item = DictPriority(**data.model_dump())
    db.add(item)
    db.commit()
    return {"success": True}

@router.put("/priorities/{id}")
async def update_priority(id: int, data: PriorityCreate, db: Session = Depends(get_db)):
    item = db.query(DictPriority).filter(DictPriority.priority_id == id).first()
    if not item: raise HTTPException(404)
    for k, v in data.model_dump().items(): setattr(item, k, v)
    db.commit()
    return {"success": True}

@router.delete("/priorities/{id}")
async def delete_priority(id: int, db: Session = Depends(get_db)):
    item = db.query(DictPriority).filter(DictPriority.priority_id == id).first()
    if not item: raise HTTPException(404)
    db.delete(item)
    db.commit()
    return {"success": True}

@router.get("/risk-levels")
async def get_risk_levels(db: Session = Depends(get_db)):
    items = db.query(DictRiskLevel).all()
    return {"success": True, "data": [{c.name: getattr(i, c.name) for c in i.__table__.columns} for i in items]}

@router.post("/risk-levels")
async def create_risk_level(data: RiskLevelCreate, db: Session = Depends(get_db)):
    item = DictRiskLevel(**data.model_dump())
    db.add(item)
    db.commit()
    return {"success": True}

@router.put("/risk-levels/{id}")
async def update_risk_level(id: int, data: RiskLevelCreate, db: Session = Depends(get_db)):
    item = db.query(DictRiskLevel).filter(DictRiskLevel.risk_level_id == id).first()
    if not item: raise HTTPException(404)
    for k, v in data.model_dump().items(): setattr(item, k, v)
    db.commit()
    return {"success": True}

@router.delete("/risk-levels/{id}")
async def delete_risk_level(id: int, db: Session = Depends(get_db)):
    item = db.query(DictRiskLevel).filter(DictRiskLevel.risk_level_id == id).first()
    if not item: raise HTTPException(404)
    db.delete(item)
    db.commit()
    return {"success": True}

@router.get("/role-types")
async def get_role_types(db: Session = Depends(get_db)):
    items = db.query(DictRoleType).all()
    return {"success": True, "data": [{c.name: getattr(i, c.name) for c in i.__table__.columns} for i in items]}

@router.post("/role-types")
async def create_role_type(data: RoleTypeCreate, db: Session = Depends(get_db)):
    item = DictRoleType(**data.model_dump())
    db.add(item)
    db.commit()
    return {"success": True}

@router.put("/role-types/{id}")
async def update_role_type(id: int, data: RoleTypeCreate, db: Session = Depends(get_db)):
    item = db.query(DictRoleType).filter(DictRoleType.role_type_id == id).first()
    if not item: raise HTTPException(404)
    for k, v in data.model_dump().items(): setattr(item, k, v)
    db.commit()
    return {"success": True}

@router.delete("/role-types/{id}")
async def delete_role_type(id: int, db: Session = Depends(get_db)):
    item = db.query(DictRoleType).filter(DictRoleType.role_type_id == id).first()
    if not item: raise HTTPException(404)
    db.delete(item)
    db.commit()
    return {"success": True}

@router.get("/transaction-types")
async def get_transaction_types(db: Session = Depends(get_db)):
    items = db.query(DictTransactionType).all()
    return {"success": True, "data": [{c.name: getattr(i, c.name) for c in i.__table__.columns} for i in items]}

@router.post("/transaction-types")
async def create_transaction_type(data: TransactionTypeCreate, db: Session = Depends(get_db)):
    item = DictTransactionType(**data.model_dump())
    db.add(item)
    db.commit()
    return {"success": True}

@router.put("/transaction-types/{id}")
async def update_transaction_type(id: int, data: TransactionTypeCreate, db: Session = Depends(get_db)):
    item = db.query(DictTransactionType).filter(DictTransactionType.trans_type_id == id).first()
    if not item: raise HTTPException(404)
    for k, v in data.model_dump().items(): setattr(item, k, v)
    db.commit()
    return {"success": True}

@router.delete("/transaction-types/{id}")
async def delete_transaction_type(id: int, db: Session = Depends(get_db)):
    item = db.query(DictTransactionType).filter(DictTransactionType.trans_type_id == id).first()
    if not item: raise HTTPException(404)
    db.delete(item)
    db.commit()
    return {"success": True}

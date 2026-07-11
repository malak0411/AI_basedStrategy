from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session
from sqlalchemy import func
from datetime import datetime
from app.database import get_db
from app.models import BudgetLine, BudgetTransaction
from pydantic import BaseModel
from typing import Optional

router = APIRouter(prefix="/api/budget", tags=["Budget"])

# ============================================================
# Schemas
# ============================================================
class BudgetLineCreate(BaseModel):
    budgetable_type: str  # 'program', 'initiative', 'major_task', 'operational_task'
    budgetable_id: int
    department_id: Optional[int] = None
    allocated_amount: float = 0
    fiscal_year: int = 2026

class BudgetLineUpdate(BaseModel):
    allocated_amount: Optional[float] = None
    spent_amount: Optional[float] = None
    fiscal_year: Optional[int] = None

class TransactionCreate(BaseModel):
    budget_id: int
    amount: float
    transaction_type_id: Optional[int] = 1
    description: Optional[str] = ""
    transaction_date: Optional[str] = None

# ============================================================
# GET - نظرة عامة
# ============================================================

@router.get("/overview")
async def budget_overview(db: Session = Depends(get_db)):
    """نظرة عامة على الميزانية"""
    try:
        total_allocated = db.query(func.sum(BudgetLine.allocated_amount)).scalar() or 0
        total_spent = db.query(func.sum(BudgetLine.spent_amount)).scalar() or 0
        return {
            "success": True,
            "data": {
                "total": float(total_allocated),
                "used": float(total_spent),
                "remaining": float(total_allocated - total_spent)
            }
        }
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")

@router.get("/")
async def budget_lines(db: Session = Depends(get_db)):
    """جميع بنود الميزانية"""
    try:
        lines = db.query(BudgetLine).all()
        result = [{
            "id": b.budget_id,
            "name": f"بند {b.budget_id}",
            "allocated_amount": float(b.allocated_amount or 0),
            "used_amount": float(b.spent_amount or 0),
            "description": f"ميزانية {b.fiscal_year or ''}",
            "budgetable_type": b.budgetable_type,
            "budgetable_id": b.budgetable_id,
            "fiscal_year": b.fiscal_year
        } for b in lines]
        return {"success": True, "data": result}
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")

@router.get("/{budget_id}")
async def budget_detail(budget_id: int, db: Session = Depends(get_db)):
    """تفاصيل بند ميزانية"""
    try:
        b = db.query(BudgetLine).filter(BudgetLine.budget_id == budget_id).first()
        if not b:
            raise HTTPException(status_code=404, detail="البند غير موجود")
        return {
            "success": True,
            "data": {
                "id": b.budget_id,
                "name": f"بند {b.budget_id}",
                "allocated_amount": float(b.allocated_amount or 0),
                "used_amount": float(b.spent_amount or 0),
                "description": f"ميزانية {b.fiscal_year or ''}",
                "budgetable_type": b.budgetable_type,
                "budgetable_id": b.budgetable_id,
                "fiscal_year": b.fiscal_year
            }
        }
    except HTTPException:
        raise
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")

# ============================================================
# POST/PUT/DELETE - بنود الميزانية
# ============================================================

@router.post("/")
async def create_budget_line(data: BudgetLineCreate, db: Session = Depends(get_db)):
    """إنشاء بند ميزانية جديد"""
    try:
        budget = BudgetLine(
            budgetable_type=data.budgetable_type,
            budgetable_id=data.budgetable_id,
            department_id=data.department_id,
            allocated_amount=data.allocated_amount,
            fiscal_year=data.fiscal_year
        )
        db.add(budget)
        db.commit()
        db.refresh(budget)
        return {"success": True, "data": {"id": budget.budget_id, "message": "تم إنشاء البند"}}
    except Exception as e:
        db.rollback()
        raise HTTPException(status_code=500, detail=str(e))

@router.put("/{budget_id}")
async def update_budget_line(budget_id: int, data: BudgetLineUpdate, db: Session = Depends(get_db)):
    """تحديث بند ميزانية"""
    try:
        b = db.query(BudgetLine).filter(BudgetLine.budget_id == budget_id).first()
        if not b:
            raise HTTPException(status_code=404, detail="غير موجود")
        if data.allocated_amount is not None:
            b.allocated_amount = data.allocated_amount
        if data.spent_amount is not None:
            b.spent_amount = data.spent_amount
        if data.fiscal_year is not None:
            b.fiscal_year = data.fiscal_year
        b.updated_at = datetime.now()
        db.commit()
        return {"success": True, "message": "تم التحديث"}
    except HTTPException:
        raise
    except Exception as e:
        db.rollback()
        raise HTTPException(status_code=500, detail=str(e))

@router.delete("/{budget_id}")
async def delete_budget_line(budget_id: int, db: Session = Depends(get_db)):
    """حذف بند ميزانية"""
    try:
        b = db.query(BudgetLine).filter(BudgetLine.budget_id == budget_id).first()
        if not b:
            raise HTTPException(status_code=404, detail="غير موجود")
        db.delete(b)
        db.commit()
        return {"success": True, "message": "تم الحذف"}
    except HTTPException:
        raise
    except Exception as e:
        db.rollback()
        raise HTTPException(status_code=500, detail=str(e))

# ============================================================
# المعاملات
# ============================================================

@router.get("/transactions")
async def budget_transactions(db: Session = Depends(get_db)):
    """معاملات الميزانية"""
    try:
        transactions = db.query(BudgetTransaction).order_by(
            BudgetTransaction.transaction_date.desc()
        ).limit(50).all()
        result = [{
            "id": t.transaction_id,
            "budget_line_name": f"بند {t.budget_id}",
            "budget_id": t.budget_id,
            "amount": float(t.amount or 0),
            "type": "expense" if (t.transaction_type_id or 1) == 1 else "deposit",
            "transaction_date": t.transaction_date.isoformat() if t.transaction_date else None,
            "description": t.description or ""
        } for t in transactions]
        return {"success": True, "data": result}
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")

@router.get("/transactions/{transaction_id}")
async def transaction_detail(transaction_id: int, db: Session = Depends(get_db)):
    """تفاصيل معاملة"""
    try:
        t = db.query(BudgetTransaction).filter(BudgetTransaction.transaction_id == transaction_id).first()
        if not t:
            raise HTTPException(status_code=404, detail="المعاملة غير موجودة")
        return {
            "success": True,
            "data": {
                "id": t.transaction_id,
                "amount": float(t.amount or 0),
                "type": "expense" if (t.transaction_type_id or 1) == 1 else "deposit",
                "transaction_date": t.transaction_date.isoformat() if t.transaction_date else None,
                "description": t.description or ""
            }
        }
    except HTTPException:
        raise
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")

@router.post("/transactions")
async def create_transaction(data: TransactionCreate, db: Session = Depends(get_db)):
    """تسجيل معاملة جديدة"""
    try:
        tx = BudgetTransaction(
            budget_id=data.budget_id,
            amount=data.amount,
            transaction_type_id=data.transaction_type_id,
            description=data.description,
            transaction_date=datetime.strptime(data.transaction_date, "%Y-%m-%d") if data.transaction_date else datetime.now()
        )
        db.add(tx)
        
        # تحديث المبلغ المنفق في البند
        budget = db.query(BudgetLine).filter(BudgetLine.budget_id == data.budget_id).first()
        if budget:
            budget.spent_amount = (budget.spent_amount or 0) + data.amount
            budget.updated_at = datetime.now()
        
        db.commit()
        db.refresh(tx)
        return {"success": True, "data": {"id": tx.transaction_id, "message": "تم تسجيل المعاملة"}}
    except Exception as e:
        db.rollback()
        raise HTTPException(status_code=500, detail=str(e))

@router.delete("/transactions/{transaction_id}")
async def delete_transaction(transaction_id: int, db: Session = Depends(get_db)):
    """حذف معاملة"""
    try:
        t = db.query(BudgetTransaction).filter(BudgetTransaction.transaction_id == transaction_id).first()
        if not t:
            raise HTTPException(status_code=404, detail="غير موجودة")
        db.delete(t)
        db.commit()
        return {"success": True, "message": "تم حذف المعاملة"}
    except HTTPException:
        raise
    except Exception as e:
        db.rollback()
        raise HTTPException(status_code=500, detail=str(e))

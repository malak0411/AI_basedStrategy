from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session
from sqlalchemy import func
from app.database import get_db
from app.models import BudgetLine, BudgetTransaction

router = APIRouter(prefix="/api/budget", tags=["Budget"])

@router.get("/overview")
async def budget_overview(db: Session = Depends(get_db)):
    try:
        total_allocated = db.query(func.sum(BudgetLine.allocated_amount)).scalar() or 0
        total_spent = db.query(func.sum(BudgetLine.spent_amount)).scalar() or 0
        return {"success": True, "data": {"total": float(total_allocated), "used": float(total_spent), "remaining": float(total_allocated - total_spent)}}
    except Exception as e: raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")

@router.get("/")
async def budget_lines(db: Session = Depends(get_db)):
    try:
        lines = db.query(BudgetLine).all()
        result = [{"id": b.budget_id, "name": f"بند {b.budget_id}", "allocated_amount": float(b.allocated_amount or 0), "used_amount": float(b.spent_amount or 0), "description": f"ميزانية {b.fiscal_year or ''}"} for b in lines]
        return {"success": True, "data": result}
    except Exception as e: raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")

@router.get("/{budget_id}")
async def budget_detail(budget_id: int, db: Session = Depends(get_db)):
    try:
        b = db.query(BudgetLine).filter(BudgetLine.budget_id == budget_id).first()
        if not b: raise HTTPException(status_code=404, detail="البند غير موجود")
        return {"success": True, "data": {"id": b.budget_id, "name": f"بند {b.budget_id}", "allocated_amount": float(b.allocated_amount or 0), "used_amount": float(b.spent_amount or 0), "description": f"ميزانية {b.fiscal_year or ''}"}}
    except HTTPException: raise
    except Exception as e: raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")

@router.get("/transactions")
async def budget_transactions(db: Session = Depends(get_db)):
    try:
        transactions = db.query(BudgetTransaction).order_by(BudgetTransaction.transaction_date.desc()).limit(50).all()
        result = [{"id": t.transaction_id, "budget_line_name": f"بند {t.budget_id}", "amount": float(t.amount or 0), "type": "expense" if (t.transaction_type_id or 1) == 1 else "deposit", "transaction_date": t.transaction_date.isoformat() if t.transaction_date else None, "description": t.description or ""} for t in transactions]
        return {"success": True, "data": result}
    except Exception as e: raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")

@router.get("/transactions/{transaction_id}")
async def transaction_detail(transaction_id: int, db: Session = Depends(get_db)):
    try:
        t = db.query(BudgetTransaction).filter(BudgetTransaction.transaction_id == transaction_id).first()
        if not t: raise HTTPException(status_code=404, detail="المعاملة غير موجودة")
        return {"success": True, "data": {"id": t.transaction_id, "amount": float(t.amount or 0), "type": "expense" if (t.transaction_type_id or 1) == 1 else "deposit", "transaction_date": t.transaction_date.isoformat() if t.transaction_date else None, "description": t.description or ""}}
    except HTTPException: raise
    except Exception as e: raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")

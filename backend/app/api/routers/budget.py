from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy.orm import Session
from typing import List, Optional
from datetime import datetime

from ...database import get_db
from ...models import BudgetLine, BudgetTransaction, OperationalTask, Department, DictTransactionType
from ...schemas import (
    BudgetLineCreate, BudgetLineUpdate, BudgetLineResponse,
    BudgetTransactionCreate, BudgetTransactionResponse
)
from ...core.dependencies import get_current_user, has_role

router = APIRouter(prefix="/api/budget", tags=["الميزانية"])

@router.get("/lines", response_model=List[BudgetLineResponse])
async def get_budget_lines(
    budgetable_type: Optional[str] = None,
    budgetable_id: Optional[int] = None,
    fiscal_year: Optional[int] = None,
    db: Session = Depends(get_db),
    current_user: Employee = Depends(get_current_user)
):
    query = db.query(BudgetLine)
    if budgetable_type:
        query = query.filter(BudgetLine.budgetable_type == budgetable_type)
    if budgetable_id:
        query = query.filter(BudgetLine.budgetable_id == budgetable_id)
    if fiscal_year:
        query = query.filter(BudgetLine.fiscal_year == fiscal_year)
    lines = query.all()
    result = []
    for line in lines:
        dept = db.query(Department).filter(Department.department_id == line.department_id).first()
        # Get budgetable name (simplified)
        budgetable_name = None
        if line.budgetable_type == 'operational_task':
            task = db.query(OperationalTask).filter(OperationalTask.task_id == line.budgetable_id).first()
            budgetable_name = task.title if task else None
        result.append(BudgetLineResponse(
            budget_id=line.budget_id,
            budgetable_type=line.budgetable_type,
            budgetable_id=line.budgetable_id,
            budgetable_name=budgetable_name,
            department_id=line.department_id,
            department_name=dept.name if dept else None,
            allocated_amount=line.allocated_amount,
            spent_amount=line.spent_amount,
            remaining_amount=line.allocated_amount - line.spent_amount,
            fiscal_year=line.fiscal_year,
            parent_budget_id=line.parent_budget_id,
            created_at=line.created_at,
            updated_at=line.updated_at
        ))
    return result

@router.post("/lines", response_model=BudgetLineResponse, status_code=status.HTTP_201_CREATED)
async def create_budget_line(
    line_data: BudgetLineCreate,
    db: Session = Depends(get_db),
    current_user: Employee = Depends(has_role(["Super Admin", "وزير / قيادة العليا"]))
):
    # التحقق من صحة النوع
    valid_types = ['program', 'initiative', 'major_task', 'operational_task']
    if line_data.budgetable_type not in valid_types:
        raise HTTPException(status_code=400, detail=f"نوع غير صحيح. الأنواع المسموحة: {valid_types}")
    new_line = BudgetLine(
        budgetable_type=line_data.budgetable_type,
        budgetable_id=line_data.budgetable_id,
        department_id=line_data.department_id,
        allocated_amount=line_data.allocated_amount,
        fiscal_year=line_data.fiscal_year,
        parent_budget_id=line_data.parent_budget_id
    )
    db.add(new_line)
    db.commit()
    db.refresh(new_line)
    return BudgetLineResponse(
        budget_id=new_line.budget_id,
        budgetable_type=new_line.budgetable_type,
        budgetable_id=new_line.budgetable_id,
        department_id=new_line.department_id,
        allocated_amount=new_line.allocated_amount,
        spent_amount=new_line.spent_amount,
        remaining_amount=new_line.allocated_amount - new_line.spent_amount,
        fiscal_year=new_line.fiscal_year,
        parent_budget_id=new_line.parent_budget_id,
        created_at=new_line.created_at,
        updated_at=new_line.updated_at
    )

@router.post("/transactions", response_model=BudgetTransactionResponse, status_code=status.HTTP_201_CREATED)
async def create_transaction(
    trans_data: BudgetTransactionCreate,
    db: Session = Depends(get_db),
    current_user: Employee = Depends(has_role(["Super Admin", "وزير / قيادة العليا"]))
):
    budget_line = db.query(BudgetLine).filter(BudgetLine.budget_id == trans_data.budget_id).first()
    if not budget_line:
        raise HTTPException(status_code=404, detail="بند الميزانية غير موجود")
    trans_type = db.query(DictTransactionType).filter(DictTransactionType.trans_type_id == trans_data.transaction_type_id).first()
    if not trans_type:
        raise HTTPException(status_code=404, detail="نوع المعاملة غير موجود")
    # تحديث المصروفات
    if trans_type.code == 'spent':
        budget_line.spent_amount += trans_data.amount
    elif trans_type.code == 'allocated':
        budget_line.allocated_amount += trans_data.amount
    elif trans_type.code == 'refunded':
        budget_line.spent_amount -= trans_data.amount
    new_trans = BudgetTransaction(
        budget_id=trans_data.budget_id,
        amount=trans_data.amount,
        transaction_type_id=trans_data.transaction_type_id,
        description=trans_data.description,
        created_by=current_user.employee_id
    )
    db.add(new_trans)
    db.commit()
    db.refresh(new_trans)
    return BudgetTransactionResponse(
        transaction_id=new_trans.transaction_id,
        budget_id=new_trans.budget_id,
        amount=new_trans.amount,
        transaction_type_id=new_trans.transaction_type_id,
        transaction_type_name=trans_type.name_ar if trans_type else None,
        description=new_trans.description,
        transaction_date=new_trans.transaction_date,
        created_by=new_trans.created_by,
        created_by_name=current_user.full_name,
        approved_by=new_trans.approved_by,
        approved_at=new_trans.approved_at
    )

 

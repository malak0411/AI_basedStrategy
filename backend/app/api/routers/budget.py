from fastapi import APIRouter, Depends, HTTPException, Query
from sqlalchemy.orm import Session, joinedload
from sqlalchemy import func, desc, asc, and_
from typing import List, Optional
from datetime import datetime
from decimal import Decimal

from app.models import (
    BudgetLine, BudgetTransaction, DictTransactionType,
    OperationalTask, MajorTask, Initiative, Program,
    Department, Employee
)
from app.models import (
    BudgetLineCreate, BudgetLineUpdate,
    BudgetTransactionCreate, BudgetTransactionUpdate,
    BudgetLineResponse, BudgetTransactionResponse
)
from app.core.dependencies import get_current_user
from app.database import get_db
router = APIRouter(prefix="/api/budget", tags=["Budget"])


@router.get("/")
async def get_budget_lines(
    fiscal_year: Optional[int] = Query(None),
    budgetable_type: Optional[str] = Query(None),
    department_id: Optional[int] = Query(None),
    status: Optional[str] = Query(None),
    search: Optional[str] = Query(None),
    page: int = Query(1, ge=1),
    per_page: int = Query(10, ge=1, le=100),
    current_user: Employee = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    query = db.query(BudgetLine)

    if fiscal_year:
        query = query.filter(BudgetLine.fiscal_year == fiscal_year)

    if budgetable_type:
        query = query.filter(BudgetLine.budgetable_type == budgetable_type)

    if department_id:
        query = query.filter(BudgetLine.department_id == department_id)

    if search:
        query = query.filter(
            BudgetLine.budgetable_name.contains(search)
        )

    total = query.count()
    budget_lines = query.offset((page - 1) * per_page).limit(per_page).all()

    result = []
    for bl in budget_lines:
        item_name = ""
        if bl.budgetable_type == "operational_task":
            task = db.query(OperationalTask).filter(
                OperationalTask.task_id == bl.budgetable_id
            ).first()
            item_name = task.title if task else "غير معروف"
        elif bl.budgetable_type == "major_task":
            task = db.query(MajorTask).filter(
                MajorTask.major_task_id == bl.budgetable_id
            ).first()
            item_name = task.name if task else "غير معروف"
        elif bl.budgetable_type == "initiative":
            initiative = db.query(Initiative).filter(
                Initiative.initiative_id == bl.budgetable_id
            ).first()
            item_name = initiative.name if initiative else "غير معروف"
        elif bl.budgetable_type == "program":
            program = db.query(Program).filter(
                Program.program_id == bl.budgetable_id
            ).first()
            item_name = program.name if program else "غير معروف"

        department = db.query(Department).filter(
            Department.department_id == bl.department_id
        ).first()

        allocated = float(bl.allocated_amount) if bl.allocated_amount else 0
        spent = float(bl.spent_amount) if bl.spent_amount else 0
        remaining = allocated - spent
        execution_percentage = (spent / allocated * 100) if allocated > 0 else 0

        if spent > allocated:
            status_text = "متجاوزة"
        elif spent == allocated and allocated > 0:
            status_text = "مكتملة"
        elif spent < allocated and allocated > 0:
            status_text = "ضمن الميزانية"
        else:
            status_text = "بدون ميزانية"

        result.append({
            "budget_id": bl.budget_id,
            "budgetable_type": bl.budgetable_type,
            "budgetable_id": bl.budgetable_id,
            "budgetable_name": item_name,
            "department": {
                "id": department.department_id if department else None,
                "name": department.name if department else "غير محدد"
            },
            "fiscal_year": bl.fiscal_year,
            "allocated_amount": allocated,
            "spent_amount": spent,
            "remaining_amount": remaining,
            "execution_percentage": round(execution_percentage, 2),
            "status": status_text,
            "created_at": bl.created_at.isoformat() if bl.created_at else None,
            "updated_at": bl.updated_at.isoformat() if bl.updated_at else None
        })

    return {
        "data": result,
        "total": total,
        "page": page,
        "per_page": per_page,
        "total_pages": (total + per_page - 1) // per_page
    }


@router.get("/{budget_id}")
async def get_budget_detail(
    budget_id: int,
    current_user: Employee = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    budget_line = db.query(BudgetLine).filter(
        BudgetLine.budget_id == budget_id
    ).first()

    if not budget_line:
        raise HTTPException(status_code=404, detail="الميزانية غير موجودة")

    # جلب اسم العنصر المرتبط
    item_name = ""
    if budget_line.budgetable_type == "operational_task":
        task = db.query(OperationalTask).filter(
            OperationalTask.task_id == budget_line.budgetable_id
        ).first()
        item_name = task.title if task else "غير معروف"
    elif budget_line.budgetable_type == "major_task":
        task = db.query(MajorTask).filter(
            MajorTask.major_task_id == budget_line.budgetable_id
        ).first()
        item_name = task.name if task else "غير معروف"
    elif budget_line.budgetable_type == "initiative":
        initiative = db.query(Initiative).filter(
            Initiative.initiative_id == budget_line.budgetable_id
        ).first()
        item_name = initiative.name if initiative else "غير معروف"
    elif budget_line.budgetable_type == "program":
        program = db.query(Program).filter(
            Program.program_id == budget_line.budgetable_id
        ).first()
        item_name = program.name if program else "غير معروف"

    # جلب القسم
    department = db.query(Department).filter(
        Department.department_id == budget_line.department_id
    ).first()

    # جلب العمليات المالية
    transactions = db.query(BudgetTransaction).filter(
        BudgetTransaction.budget_id == budget_line.budget_id
    ).order_by(desc(BudgetTransaction.transaction_date)).all()

    # جلب الميزانية الأب
    parent_budget = None
    if budget_line.parent_budget_id:
        parent = db.query(BudgetLine).filter(
            BudgetLine.budget_id == budget_line.parent_budget_id
        ).first()
        if parent:
            parent_budget = {
                "budget_id": parent.budget_id,
                "budgetable_type": parent.budgetable_type,
                "allocated_amount": float(parent.allocated_amount) if parent.allocated_amount else 0
            }

    # حساب المؤشرات المالية
    allocated = float(budget_line.allocated_amount) if budget_line.allocated_amount else 0
    spent = float(budget_line.spent_amount) if budget_line.spent_amount else 0
    remaining = allocated - spent
    execution_percentage = (spent / allocated * 100) if allocated > 0 else 0

    if spent > allocated:
        status_text = "متجاوزة"
    elif spent == allocated and allocated > 0:
        status_text = "مكتملة"
    elif spent < allocated and allocated > 0:
        status_text = "ضمن الميزانية"
    else:
        status_text = "بدون ميزانية"

    transactions_data = []
    for t in transactions:
        trans_type = db.query(DictTransactionType).filter(
            DictTransactionType.trans_type_id == t.transaction_type_id
        ).first()

        created_by = db.query(Employee).filter(
            Employee.employee_id == t.created_by
        ).first()

        approved_by = db.query(Employee).filter(
            Employee.employee_id == t.approved_by
        ).first() if t.approved_by else None

        transactions_data.append({
            "transaction_id": t.transaction_id,
            "amount": float(t.amount),
            "transaction_type": {
                "id": trans_type.trans_type_id if trans_type else None,
                "name": trans_type.name_ar if trans_type else "غير محدد"
            },
            "description": t.description,
            "transaction_date": t.transaction_date.isoformat() if t.transaction_date else None,
            "created_by": {
                "id": created_by.employee_id if created_by else None,
                "name": created_by.full_name if created_by else "غير معروف"
            },
            "approved_by": {
                "id": approved_by.employee_id if approved_by else None,
                "name": approved_by.full_name if approved_by else None
            } if approved_by else None,
            "approved_at": t.approved_at.isoformat() if t.approved_at else None
        })

    return {
        "budget_id": budget_line.budget_id,
        "budgetable_type": budget_line.budgetable_type,
        "budgetable_id": budget_line.budgetable_id,
        "budgetable_name": item_name,
        "department": {
            "id": department.department_id if department else None,
            "name": department.name if department else "غير محدد"
        },
        "fiscal_year": budget_line.fiscal_year,
        "allocated_amount": allocated,
        "spent_amount": spent,
        "remaining_amount": remaining,
        "execution_percentage": round(execution_percentage, 2),
        "status": status_text,
        "parent_budget": parent_budget,
        "transactions": transactions_data,
        "created_at": budget_line.created_at.isoformat() if budget_line.created_at else None,
        "updated_at": budget_line.updated_at.isoformat() if budget_line.updated_at else None
    }


@router.get("/{budget_id}/transactions")
async def get_budget_transactions(
    budget_id: int,
    page: int = Query(1, ge=1),
    per_page: int = Query(10, ge=1, le=100),
    current_user: Employee = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    budget_line = db.query(BudgetLine).filter(
        BudgetLine.budget_id == budget_id
    ).first()

    if not budget_line:
        raise HTTPException(status_code=404, detail="الميزانية غير موجودة")

    query = db.query(BudgetTransaction).filter(
        BudgetTransaction.budget_id == budget_id
    )

    total = query.count()
    transactions = query.order_by(desc(BudgetTransaction.transaction_date)).offset(
        (page - 1) * per_page
    ).limit(per_page).all()

    result = []
    for t in transactions:
        trans_type = db.query(DictTransactionType).filter(
            DictTransactionType.trans_type_id == t.transaction_type_id
        ).first()

        created_by = db.query(Employee).filter(
            Employee.employee_id == t.created_by
        ).first()

        approved_by = db.query(Employee).filter(
            Employee.employee_id == t.approved_by
        ).first() if t.approved_by else None

        result.append({
            "transaction_id": t.transaction_id,
            "amount": float(t.amount),
            "transaction_type": {
                "id": trans_type.trans_type_id if trans_type else None,
                "name": trans_type.name_ar if trans_type else "غير محدد"
            },
            "description": t.description,
            "transaction_date": t.transaction_date.isoformat() if t.transaction_date else None,
            "created_by": {
                "id": created_by.employee_id if created_by else None,
                "name": created_by.full_name if created_by else "غير معروف"
            },
            "approved_by": {
                "id": approved_by.employee_id if approved_by else None,
                "name": approved_by.full_name if approved_by else None
            } if approved_by else None,
            "approved_at": t.approved_at.isoformat() if t.approved_at else None,
            "is_approved": t.approved_by is not None
        })

    return {
        "data": result,
        "total": total,
        "page": page,
        "per_page": per_page,
        "total_pages": (total + per_page - 1) // per_page
    }


@router.get("/transactions/{transaction_id}")
async def get_transaction_detail(
    transaction_id: int,
    current_user: Employee = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    transaction = db.query(BudgetTransaction).filter(
        BudgetTransaction.transaction_id == transaction_id
    ).first()

    if not transaction:
        raise HTTPException(status_code=404, detail="العملية غير موجودة")

    trans_type = db.query(DictTransactionType).filter(
        DictTransactionType.trans_type_id == transaction.transaction_type_id
    ).first()

    created_by = db.query(Employee).filter(
        Employee.employee_id == transaction.created_by
    ).first()

    approved_by = db.query(Employee).filter(
        Employee.employee_id == transaction.approved_by
    ).first() if transaction.approved_by else None

    budget_line = db.query(BudgetLine).filter(
        BudgetLine.budget_id == transaction.budget_id
    ).first()

    return {
        "transaction_id": transaction.transaction_id,
        "budget_id": transaction.budget_id,
        "budgetable_type": budget_line.budgetable_type if budget_line else None,
        "budgetable_name": "الميزانية #" + str(transaction.budget_id),
        "amount": float(transaction.amount),
        "transaction_type": {
            "id": trans_type.trans_type_id if trans_type else None,
            "name": trans_type.name_ar if trans_type else "غير محدد"
        },
        "description": transaction.description,
        "transaction_date": transaction.transaction_date.isoformat() if transaction.transaction_date else None,
        "created_by": {
            "id": created_by.employee_id if created_by else None,
            "name": created_by.full_name if created_by else "غير معروف"
        },
        "approved_by": {
            "id": approved_by.employee_id if approved_by else None,
            "name": approved_by.full_name if approved_by else None
        } if approved_by else None,
        "approved_at": transaction.approved_at.isoformat() if transaction.approved_at else None
    }


@router.post("/")
async def create_budget_line(
    data: dict,
    current_user: Employee = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    budgetable_type = data.get("budgetable_type")
    budgetable_id = data.get("budgetable_id")
    department_id = data.get("department_id")
    fiscal_year = data.get("fiscal_year")
    allocated_amount = data.get("allocated_amount")
    parent_budget_id = data.get("parent_budget_id")

    if not budgetable_type or not budgetable_id or not fiscal_year:
        raise HTTPException(
            status_code=400,
            detail="نوع الميزانية والعنصر المرتبط والسنة المالية مطلوبة"
        )

    if allocated_amount is None or allocated_amount < 0:
        raise HTTPException(status_code=400, detail="المبلغ المخصص غير صحيح")

    # التحقق من صحة نوع الميزانية
    valid_types = ["program", "initiative", "major_task", "operational_task"]
    if budgetable_type not in valid_types:
        raise HTTPException(status_code=400, detail="نوع الميزانية غير صحيح")

    # التحقق من وجود العنصر المرتبط
    if budgetable_type == "program":
        exists = db.query(Program).filter(Program.program_id == budgetable_id).first()
    elif budgetable_type == "initiative":
        exists = db.query(Initiative).filter(Initiative.initiative_id == budgetable_id).first()
    elif budgetable_type == "major_task":
        exists = db.query(MajorTask).filter(MajorTask.major_task_id == budgetable_id).first()
    elif budgetable_type == "operational_task":
        exists = db.query(OperationalTask).filter(OperationalTask.task_id == budgetable_id).first()

    if not exists:
        raise HTTPException(status_code=404, detail="العنصر المرتبط غير موجود")

    # التحقق من القسم
    if department_id:
        dept = db.query(Department).filter(Department.department_id == department_id).first()
        if not dept:
            raise HTTPException(status_code=404, detail="القسم غير موجود")

    # التحقق من الميزانية الأب
    if parent_budget_id:
        parent = db.query(BudgetLine).filter(BudgetLine.budget_id == parent_budget_id).first()
        if not parent:
            raise HTTPException(status_code=404, detail="الميزانية الأب غير موجودة")

    new_budget = BudgetLine(
        budgetable_type=budgetable_type,
        budgetable_id=budgetable_id,
        department_id=department_id,
        fiscal_year=fiscal_year,
        allocated_amount=allocated_amount,
        spent_amount=0,
        parent_budget_id=parent_budget_id
    )

    db.add(new_budget)
    db.commit()
    db.refresh(new_budget)

    return {
        "success": True,
        "message": "تم إنشاء الميزانية بنجاح",
        "budget_id": new_budget.budget_id
    }


@router.put("/{budget_id}")
async def update_budget_line(
    budget_id: int,
    data: dict,
    current_user: Employee = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    budget_line = db.query(BudgetLine).filter(
        BudgetLine.budget_id == budget_id
    ).first()

    if not budget_line:
        raise HTTPException(status_code=404, detail="الميزانية غير موجودة")

    if "allocated_amount" in data:
        if data["allocated_amount"] < 0:
            raise HTTPException(status_code=400, detail="المبلغ المخصص غير صحيح")
        budget_line.allocated_amount = data["allocated_amount"]

    if "department_id" in data:
        if data["department_id"]:
            dept = db.query(Department).filter(
                Department.department_id == data["department_id"]
            ).first()
            if not dept:
                raise HTTPException(status_code=404, detail="القسم غير موجود")
        budget_line.department_id = data["department_id"]

    if "fiscal_year" in data:
        budget_line.fiscal_year = data["fiscal_year"]

    if "parent_budget_id" in data:
        if data["parent_budget_id"]:
            parent = db.query(BudgetLine).filter(
                BudgetLine.budget_id == data["parent_budget_id"]
            ).first()
            if not parent:
                raise HTTPException(status_code=404, detail="الميزانية الأب غير موجودة")
        budget_line.parent_budget_id = data["parent_budget_id"]

    db.commit()

    return {
        "success": True,
        "message": "تم تحديث الميزانية بنجاح"
    }


@router.delete("/{budget_id}")
async def delete_budget_line(
    budget_id: int,
    current_user: Employee = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    budget_line = db.query(BudgetLine).filter(
        BudgetLine.budget_id == budget_id
    ).first()

    if not budget_line:
        raise HTTPException(status_code=404, detail="الميزانية غير موجودة")

    # التحقق من وجود عمليات مالية
    transactions = db.query(BudgetTransaction).filter(
        BudgetTransaction.budget_id == budget_id
    ).count()

    if transactions > 0:
        raise HTTPException(
            status_code=400,
            detail="لا يمكن حذف الميزانية لوجود عمليات مالية مرتبطة بها"
        )

    # التحقق من وجود ميزانيات ابنة
    children = db.query(BudgetLine).filter(
        BudgetLine.parent_budget_id == budget_id
    ).count()

    if children > 0:
        raise HTTPException(
            status_code=400,
            detail="لا يمكن حذف الميزانية لوجود ميزانيات فرعية مرتبطة بها"
        )

    db.delete(budget_line)
    db.commit()

    return {
        "success": True,
        "message": "تم حذف الميزانية بنجاح"
    }


@router.post("/{budget_id}/transactions")
async def create_transaction(
    budget_id: int,
    data: dict,
    current_user: Employee = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    budget_line = db.query(BudgetLine).filter(
        BudgetLine.budget_id == budget_id
    ).first()

    if not budget_line:
        raise HTTPException(status_code=404, detail="الميزانية غير موجودة")

    amount = data.get("amount")
    transaction_type_id = data.get("transaction_type_id")
    description = data.get("description")
    transaction_date = data.get("transaction_date")

    if amount is None or amount <= 0:
        raise HTTPException(status_code=400, detail="المبلغ غير صحيح")

    if not transaction_type_id:
        raise HTTPException(status_code=400, detail="نوع العملية مطلوب")

    # التحقق من نوع العملية
    trans_type = db.query(DictTransactionType).filter(
        DictTransactionType.trans_type_id == transaction_type_id
    ).first()

    if not trans_type:
        raise HTTPException(status_code=404, detail="نوع العملية غير موجود")

    # إنشاء العملية
    new_transaction = BudgetTransaction(
        budget_id=budget_id,
        amount=amount,
        transaction_type_id=transaction_type_id,
        description=description,
        transaction_date=transaction_date if transaction_date else datetime.now(),
        created_by=current_user.employee_id
    )

    db.add(new_transaction)
    db.flush()

    # تحديث المبلغ المصروف في Budget Line
    current_spent = float(budget_line.spent_amount) if budget_line.spent_amount else 0
    new_spent = current_spent + float(amount)
    budget_line.spent_amount = new_spent

    db.commit()
    db.refresh(new_transaction)

    return {
        "success": True,
        "message": "تم تسجيل العملية المالية بنجاح",
        "transaction_id": new_transaction.transaction_id
    }


@router.delete("/transactions/{transaction_id}")
async def delete_transaction(
    transaction_id: int,
    current_user: Employee = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    transaction = db.query(BudgetTransaction).filter(
        BudgetTransaction.transaction_id == transaction_id
    ).first()

    if not transaction:
        raise HTTPException(status_code=404, detail="العملية غير موجودة")

    budget_line = db.query(BudgetLine).filter(
        BudgetLine.budget_id == transaction.budget_id
    ).first()

    if not budget_line:
        raise HTTPException(status_code=404, detail="الميزانية المرتبطة غير موجودة")

    # تحديث المبلغ المصروف في Budget Line
    current_spent = float(budget_line.spent_amount) if budget_line.spent_amount else 0
    new_spent = current_spent - float(transaction.amount)
    budget_line.spent_amount = max(new_spent, 0)

    db.delete(transaction)
    db.commit()

    return {
        "success": True,
        "message": "تم حذف العملية المالية بنجاح"
    }


@router.get("/options")
async def get_budget_options(
    current_user: Employee = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    # جلب أنواع الميزانية
    budget_types = ["program", "initiative", "major_task", "operational_task"]

    # جلب البرامج
    programs = db.query(Program).filter(Program.is_active == True).all()
    programs_data = [
        {"id": p.program_id, "name": p.name}
        for p in programs
    ]

    # جلب المبادرات
    initiatives = db.query(Initiative).filter(Initiative.is_active == True).all()
    initiatives_data = [
        {"id": i.initiative_id, "name": i.name}
        for i in initiatives
    ]

    # جلب المهام الرئيسية
    major_tasks = db.query(MajorTask).filter(MajorTask.is_active == True).all()
    major_tasks_data = [
        {"id": mt.major_task_id, "name": mt.name}
        for mt in major_tasks
    ]

    # جلب المهام التشغيلية
    operational_tasks = db.query(OperationalTask).filter(
        OperationalTask.is_active == True
    ).all()
    operational_tasks_data = [
        {"id": ot.task_id, "name": ot.title}
        for ot in operational_tasks
    ]

    # جلب الأقسام
    departments = db.query(Department).filter(Department.is_active == True).all()
    departments_data = [
        {"id": d.department_id, "name": d.name}
        for d in departments
    ]

    # جلب أنواع العمليات
    transaction_types = db.query(DictTransactionType).all()
    transaction_types_data = [
        {"id": tt.trans_type_id, "name": tt.name_ar}
        for tt in transaction_types
    ]

    # جلب السنوات المالية
    years = db.query(BudgetLine.fiscal_year).distinct().all()
    years_data = sorted([y[0] for y in years if y[0]])

    # جلب ميزانيات الأب (للـ Parent Budget)
    parent_budgets = db.query(BudgetLine).all()
    parent_budgets_data = [
        {
            "id": b.budget_id,
            "name": f"{b.budgetable_type} - #{b.budget_id}"
        }
        for b in parent_budgets
    ]

    return {
        "budget_types": budget_types,
        "programs": programs_data,
        "initiatives": initiatives_data,
        "major_tasks": major_tasks_data,
        "operational_tasks": operational_tasks_data,
        "departments": departments_data,
        "transaction_types": transaction_types_data,
        "fiscal_years": years_data,
        "parent_budgets": parent_budgets_data
    }

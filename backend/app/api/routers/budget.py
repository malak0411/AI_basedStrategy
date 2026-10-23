from fastapi import APIRouter, Depends, HTTPException, Query
from sqlalchemy.orm import Session
from sqlalchemy import func, desc
from typing import Optional
from datetime import datetime


from app.models import (
    BudgetLine, BudgetTransaction, DictTransactionType,
    OperationalTask, MajorTask, Initiative, Program, 
    StrategicGoal, StrategicPillar, Department, Employee, DictStatus
)
from app.core.dependencies import get_current_user
from app.database import get_db


router = APIRouter(prefix="/api/budget", tags=["Budget"])


STATUS_PROGRAM_ACTIVE = 47
STATUS_INITIATIVE_ACTIVE = 2
STATUS_OPTASK_ACTIVE = 6





@router.get("/options")
async def get_budget_options(
    employee_id: int = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    departments = db.query(Department).filter(Department.is_active == True).all()
    transaction_types = db.query(DictTransactionType).all()
    years = db.query(BudgetLine.fiscal_year).distinct().all()
    
    return {
        "departments": [{"id": d.department_id, "name": d.name} for d in departments],
        "transaction_types": [{"id": tt.trans_type_id, "name": tt.name_ar, "sign": tt.sign} for tt in transaction_types],
        "fiscal_years": sorted([y[0] for y in years if y[0]])
    }


@router.get("/summary")
async def get_budget_summary(
    fiscal_year: Optional[int] = Query(None),
    employee_id: int = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    q_budget = db.query(BudgetLine)
    if fiscal_year:
        q_budget = q_budget.filter(BudgetLine.fiscal_year == fiscal_year)
    
    budgets = q_budget.all()
    
    total_allocated = sum(float(b.allocated_amount) for b in budgets if b.budgetable_type == 'program')
    total_spent = sum(float(b.spent_amount) for b in budgets if b.budgetable_type == 'program')
    total_remaining = total_allocated - total_spent
    
    execution_pct = (total_spent / total_allocated * 100) if total_allocated > 0 else 0
    
    overspent_count = sum(1 for b in budgets if b.spent_amount > b.allocated_amount)
    transactions_count = db.query(BudgetTransaction).count()
    
    return {
        "total_programs": db.query(Program).count(),
        "total_allocated": total_allocated,
        "total_spent": total_spent,
        "total_remaining": total_remaining,
        "execution_percentage": round(execution_pct, 2),
        "overspent_count": overspent_count,
        "transactions_count": transactions_count
    }


@router.get("/hierarchy")
async def get_budget_hierarchy(
    fiscal_year: Optional[int] = Query(None),
    employee_id: int = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    q_budget = db.query(BudgetLine)
    if fiscal_year:
        q_budget = q_budget.filter(BudgetLine.fiscal_year == fiscal_year)
    budgets = q_budget.all()
    budget_map = {(b.budgetable_type, b.budgetable_id): b for b in budgets}


    programs = db.query(Program).all()
    initiatives = db.query(Initiative).all()
    major_tasks = db.query(MajorTask).all()
    op_tasks = db.query(OperationalTask).all()


    goals = {g.goal_id: g for g in db.query(StrategicGoal).all()}
    pillars = {p.pillar_id: p for p in db.query(StrategicPillar).all()}
    depts = {d.department_id: d for d in db.query(Department).all()}


    inits_by_prog = {}
    for i in initiatives: inits_by_prog.setdefault(i.program_id, []).append(i)
    mts_by_init = {}
    for mt in major_tasks: mts_by_init.setdefault(mt.initiative_id, []).append(mt)
    ots_by_mt = {}
    for ot in op_tasks: ots_by_mt.setdefault(ot.major_task_id, []).append(ot)


    tree = []
    for p in programs:
        p_b = budget_map.get(('program', p.program_id))
        p_direct_alloc = float(p_b.allocated_amount) if p_b else 0
        p_direct_spent = float(p_b.spent_amount) if p_b else 0
        
        p_inits = []
        for i in inits_by_prog.get(p.program_id, []):
            i_b = budget_map.get(('initiative', i.initiative_id))
            i_direct_alloc = float(i_b.allocated_amount) if i_b else 0
            i_direct_spent = float(i_b.spent_amount) if i_b else 0
            
            i_mts = []
            for mt in mts_by_init.get(i.initiative_id, []):
                mt_b = budget_map.get(('major_task', mt.major_task_id))
                mt_direct_alloc = float(mt_b.allocated_amount) if mt_b else 0
                mt_direct_spent = float(mt_b.spent_amount) if mt_b else 0
                
                mt_ots = []
                for ot in ots_by_mt.get(mt.major_task_id, []):
                    ot_b = budget_map.get(('operational_task', ot.task_id))
                    ot_direct_alloc = float(ot_b.allocated_amount) if ot_b else 0
                    ot_direct_spent = float(ot_b.spent_amount) if ot_b else 0
                    
                    mt_ots.append({
                        "id": ot.task_id,
                        "name": ot.title,
                        "status_id": ot.status_id,
                        "department_name": depts.get(ot.department_id).name if ot.department_id and depts.get(ot.department_id) else "غير محدد",
                        "direct_allocated": ot_direct_alloc,
                        "direct_spent": ot_direct_spent,
                        "budget_id": ot_b.budget_id if ot_b else None
                    })
                    
                i_mts.append({
                    "id": mt.major_task_id,
                    "name": mt.name,
                    "status_id": i.status_id,
                    "direct_allocated": mt_direct_alloc,
                    "direct_spent": mt_direct_spent,
                    "budget_id": mt_b.budget_id if mt_b else None,
                    "operational_tasks": mt_ots
                })
                
            p_inits.append({
                "id": i.initiative_id,
                "name": i.name,
                "status_id": i.status_id,
                "direct_allocated": i_direct_alloc,
                "direct_spent": i_direct_spent,
                "budget_id": i_b.budget_id if i_b else None,
                "major_tasks": i_mts
            })


        goal = goals.get(p.goal_id)
        pillar = pillars.get(goal.pillar_id) if goal else None
        
        tree.append({
            "id": p.program_id,
            "name": p.name,
            "status_id": p.status_id,
            "strategic_goal": goal.title if goal else "غير مرتبط بهدف",
            "strategic_pillar": pillar.name if pillar else "غير مرتبط بركيزة",
            "direct_allocated": p_direct_alloc,
            "direct_spent": p_direct_spent,
            "budget_id": p_b.budget_id if p_b else None,
            "initiatives": p_inits
        })


    return {"success": True, "data": tree}





@router.get("/{budget_id}")
async def get_budget_detail(
    budget_id: int,
    employee_id: int = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    bl = db.query(BudgetLine).filter(BudgetLine.budget_id == budget_id).first()
    if not bl:
        raise HTTPException(status_code=404, detail="الميزانية غير موجودة")
        
    item_name = "غير معروف"
    status_id = None
    hierarchy = ""
    
    if bl.budgetable_type == "operational_task":
        item = db.query(OperationalTask).filter(OperationalTask.task_id == bl.budgetable_id).first()
        if item:
            item_name = item.title
            status_id = item.status_id
            mt = db.query(MajorTask).filter(MajorTask.major_task_id == item.major_task_id).first()
            if mt:
                init = db.query(Initiative).filter(Initiative.initiative_id == mt.initiative_id).first()
                if init:
                    prog = db.query(Program).filter(Program.program_id == init.program_id).first()
                    hierarchy = f"{prog.name if prog else ''} / {init.name} / {mt.name}"
    elif bl.budgetable_type == "major_task":
        item = db.query(MajorTask).filter(MajorTask.major_task_id == bl.budgetable_id).first()
        if item:
            item_name = item.name
            init = db.query(Initiative).filter(Initiative.initiative_id == item.initiative_id).first()
            if init:
                status_id = init.status_id
                prog = db.query(Program).filter(Program.program_id == init.program_id).first()
                hierarchy = f"{prog.name if prog else ''} / {init.name}"
    elif bl.budgetable_type == "initiative":
        item = db.query(Initiative).filter(Initiative.initiative_id == bl.budgetable_id).first()
        if item:
            item_name = item.name
            status_id = item.status_id
            prog = db.query(Program).filter(Program.program_id == item.program_id).first()
            hierarchy = f"{prog.name if prog else ''}"
    elif bl.budgetable_type == "program":
        item = db.query(Program).filter(Program.program_id == bl.budgetable_id).first()
        if item:
            item_name = item.name
            status_id = item.status_id
            hierarchy = "برنامج استراتيجي"



    parent_warning = None
    if bl.parent_budget_id:
        parent = db.query(BudgetLine).filter(BudgetLine.budget_id == bl.parent_budget_id).first()
        if parent:
            parent_type_map = {"operational_task": "major_task", "major_task": "initiative", "initiative": "program"}
            if parent.budgetable_type != parent_type_map.get(bl.budgetable_type):
                parent_warning = "تحذير: الميزانية الأب المسجلة لا تتطابق منطقياً مع الهيكل التنظيمي لهذا البند."


    return {
        "budget_id": bl.budget_id,
        "budgetable_type": bl.budgetable_type,
        "budgetable_name": item_name,
        "status_id": status_id,
        "hierarchy": hierarchy,
        "fiscal_year": bl.fiscal_year,
        "allocated_amount": float(bl.allocated_amount),
        "spent_amount": float(bl.spent_amount),
        "remaining_amount": float(bl.allocated_amount) - float(bl.spent_amount),
        "parent_budget_id": bl.parent_budget_id,
        "parent_warning": parent_warning,
        "department_id": bl.department_id
    }


@router.post("/")
async def create_budget_line(
    data: dict,
    employee_id: int = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    budgetable_type = data.get("budgetable_type")
    budgetable_id = data.get("budgetable_id")
    fiscal_year = data.get("fiscal_year")
    allocated_amount = data.get("allocated_amount")


    if not budgetable_type or not budgetable_id or not fiscal_year:
        raise HTTPException(status_code=400, detail="البيانات المطلوبة ناقصة")
    if float(allocated_amount) < 0:
        raise HTTPException(status_code=400, detail="المبلغ المخصص لا يمكن أن يكون سالباً")


    if budgetable_type == "program":
        item = db.query(Program).filter(Program.program_id == budgetable_id).first()
        if not item or item.status_id != STATUS_PROGRAM_ACTIVE:
            raise HTTPException(status_code=403, detail="لا يمكن إضافة ميزانية إلا للبرامج قيد التنفيذ (47)")
    elif budgetable_type == "initiative":
        item = db.query(Initiative).filter(Initiative.initiative_id == budgetable_id).first()
        if not item or item.status_id != STATUS_INITIATIVE_ACTIVE:
            raise HTTPException(status_code=403, detail="لا يمكن إضافة ميزانية إلا للمبادرات قيد التنفيذ (2)")
    elif budgetable_type == "operational_task":
        item = db.query(OperationalTask).filter(OperationalTask.task_id == budgetable_id).first()
        if not item or item.status_id != STATUS_OPTASK_ACTIVE:
            raise HTTPException(status_code=403, detail="لا يمكن إضافة ميزانية إلا للمهام التشغيلية قيد التنفيذ (6)")



    existing = db.query(BudgetLine).filter(
        BudgetLine.budgetable_type == budgetable_type,
        BudgetLine.budgetable_id == budgetable_id,
        BudgetLine.fiscal_year == fiscal_year
    ).first()
    if existing:
        raise HTTPException(status_code=400, detail="توجد ميزانية مسجلة مسبقاً لهذا البند في نفس السنة المالية")


    new_budget = BudgetLine(
        budgetable_type=budgetable_type,
        budgetable_id=budgetable_id,
        department_id=data.get("department_id"),
        fiscal_year=fiscal_year,
        allocated_amount=float(allocated_amount),
        spent_amount=0
    )
    db.add(new_budget)
    db.commit()
    return {"success": True, "message": "تم إنشاء الميزانية بنجاح", "budget_id": new_budget.budget_id}


@router.put("/{budget_id}")
async def update_budget_line(
    budget_id: int,
    data: dict,
    employee_id: int = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    bl = db.query(BudgetLine).filter(BudgetLine.budget_id == budget_id).first()
    if not bl:
        raise HTTPException(status_code=404, detail="الميزانية غير موجودة")



    if bl.budgetable_type == "program":
        item = db.query(Program).filter(Program.program_id == bl.budgetable_id).first()
        if not item or item.status_id != STATUS_PROGRAM_ACTIVE:
            raise HTTPException(status_code=403, detail="لا يمكن تعديل ميزانية إلا للبرامج قيد التنفيذ")
    elif bl.budgetable_type == "initiative":
        item = db.query(Initiative).filter(Initiative.initiative_id == bl.budgetable_id).first()
        if not item or item.status_id != STATUS_INITIATIVE_ACTIVE:
            raise HTTPException(status_code=403, detail="لا يمكن تعديل ميزانية إلا للمبادرات قيد التنفيذ")
    elif bl.budgetable_type == "operational_task":
        item = db.query(OperationalTask).filter(OperationalTask.task_id == bl.budgetable_id).first()
        if not item or item.status_id != STATUS_OPTASK_ACTIVE:
            raise HTTPException(status_code=403, detail="لا يمكن تعديل ميزانية إلا للمهام التشغيلية قيد التنفيذ")


    if "allocated_amount" in data:
        bl.allocated_amount = float(data["allocated_amount"])
    if "department_id" in data:
        bl.department_id = data["department_id"]
    if "fiscal_year" in data:
        bl.fiscal_year = data["fiscal_year"]


    db.commit()
    return {"success": True, "message": "تم تحديث الميزانية بنجاح"}


@router.get("/{budget_id}/transactions")
async def get_budget_transactions(
    budget_id: int,
    page: int = Query(1, ge=1),
    per_page: int = Query(10, ge=1, le=100),
    employee_id: int = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    query = db.query(BudgetTransaction).filter(BudgetTransaction.budget_id == budget_id)
    total = query.count()
    transactions = query.order_by(desc(BudgetTransaction.transaction_date)).offset((page - 1) * per_page).limit(per_page).all()


    result = []
    for t in transactions:
        trans_type = db.query(DictTransactionType).filter(DictTransactionType.trans_type_id == t.transaction_type_id).first()
        created_by = db.query(Employee).filter(Employee.employee_id == t.created_by).first()
        approver = db.query(Employee).filter(Employee.employee_id == t.approved_by).first() if t.approved_by else None
        result.append({
            "transaction_id": t.transaction_id,
            "amount": float(t.amount),
            "transaction_type": trans_type.name_ar if trans_type else "غير محدد",
            "type_id": t.transaction_type_id,
            "description": t.description,
            "transaction_date": t.transaction_date.isoformat() if t.transaction_date else None,
            "created_by": created_by.full_name if created_by else "غير معروف",
            "approved_by": approver.full_name if approver else "معلق",
            "approved_at": t.approved_at.isoformat() if t.approved_at else None
        })


    return {"data": result, "total": total, "page": page, "per_page": per_page, "total_pages": (total + per_page - 1) // per_page}


@router.post("/{budget_id}/transactions")
async def create_transaction(
    budget_id: int,
    data: dict,
    employee_id: int = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    bl = db.query(BudgetLine).filter(BudgetLine.budget_id == budget_id).first()
    if not bl:
        raise HTTPException(status_code=404, detail="الميزانية غير موجودة")



    if bl.budgetable_type == "program":
        item = db.query(Program).filter(Program.program_id == bl.budgetable_id).first()
        if not item or item.status_id != STATUS_PROGRAM_ACTIVE:
            raise HTTPException(status_code=403, detail="لا يمكن إضافة عمليات إلا للبرامج قيد التنفيذ")
    elif bl.budgetable_type == "initiative":
        item = db.query(Initiative).filter(Initiative.initiative_id == bl.budgetable_id).first()
        if not item or item.status_id != STATUS_INITIATIVE_ACTIVE:
            raise HTTPException(status_code=403, detail="لا يمكن إضافة عمليات إلا للمبادرات قيد التنفيذ")
    elif bl.budgetable_type == "operational_task":
        item = db.query(OperationalTask).filter(OperationalTask.task_id == bl.budgetable_id).first()
        if not item or item.status_id != STATUS_OPTASK_ACTIVE:
            raise HTTPException(status_code=403, detail="لا يمكن إضافة عمليات إلا للمهام التشغيلية قيد التنفيذ")


    amount = float(data.get("amount", 0))
    if amount <= 0:
        raise HTTPException(status_code=400, detail="المبلغ يجب أن يكون أكبر من صفر")


    type_id = data.get("transaction_type_id")
    trans_type = db.query(DictTransactionType).filter(DictTransactionType.trans_type_id == type_id).first()
    if not trans_type:
        raise HTTPException(status_code=400, detail="نوع العملية غير صحيح")



    if type_id == 1: 
        bl.allocated_amount = float(bl.allocated_amount) + amount
    elif type_id == 2: 
        bl.spent_amount = float(bl.spent_amount) + amount
    elif type_id == 3: 
        bl.spent_amount = float(bl.spent_amount) - amount
    
    new_trans = BudgetTransaction(
        budget_id=budget_id,
        amount=amount,
        transaction_type_id=type_id,
        description=data.get("description"),
        transaction_date=data.get("transaction_date") or datetime.now(),
        created_by=employee_id
    )
    db.add(new_trans)
    db.commit()
    return {"success": True, "message": "تم تسجيل العملية بنجاح"}


@router.delete("/transactions/{transaction_id}")
async def delete_transaction(
    transaction_id: int,
    employee_id: int = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    t = db.query(BudgetTransaction).filter(BudgetTransaction.transaction_id == transaction_id).first()
    if not t:
        raise HTTPException(status_code=404, detail="العملية غير موجودة")
    
    bl = db.query(BudgetLine).filter(BudgetLine.budget_id == t.budget_id).first()
    if not bl:
        raise HTTPException(status_code=404, detail="الميزانية المرتبطة غير موجودة")


    amount = float(t.amount)
    if t.transaction_type_id == 1:
        bl.allocated_amount = float(bl.allocated_amount) - amount
    elif t.transaction_type_id == 2:
        bl.spent_amount = float(bl.spent_amount) - amount
    elif t.transaction_type_id == 3:
        bl.spent_amount = float(bl.spent_amount) + amount


    db.delete(t)
    db.commit()
    return {"success": True, "message": "تم التراجع عن العملية وتحديث الأرصدة"}

@router.get("/transactions/{transaction_id}")
async def get_transaction_detail(
    transaction_id: int,
    employee_id: int = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    t = db.query(BudgetTransaction).filter(BudgetTransaction.transaction_id == transaction_id).first()
    if not t:
        raise HTTPException(status_code=404, detail="العملية غير موجودة")
        
    trans_type = db.query(DictTransactionType).filter(DictTransactionType.trans_type_id == t.transaction_type_id).first()
    created_by = db.query(Employee).filter(Employee.employee_id == t.created_by).first()
    approver = db.query(Employee).filter(Employee.employee_id == t.approved_by).first() if t.approved_by else None
    
    return {
        "transaction_id": t.transaction_id,
        "budget_id": t.budget_id,
        "amount": float(t.amount),
        "transaction_type": trans_type.name_ar if trans_type else "غير محدد",
        "type_id": t.transaction_type_id,
        "description": t.description,
        "transaction_date": t.transaction_date.isoformat() if t.transaction_date else None,
        "created_by": created_by.full_name if created_by else "غير معروف",
        "approved_by": approver.full_name if approver else "معلق",
        "approved_at": t.approved_at.isoformat() if t.approved_at else None,
    }

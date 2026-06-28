from fastapi import APIRouter, Depends, HTTPException, status, Query
from sqlalchemy.orm import Session
from sqlalchemy import func, or_
from typing import List, Optional
from datetime import datetime

from ...database import get_db
from ...models import Employee, Department, Role, employee_roles, DictStatus
from ...schemas import (
    EmployeeCreate, EmployeeUpdate, EmployeeResponse,
    DepartmentCreate, DepartmentUpdate, DepartmentResponse
)
from ...core.dependencies import get_current_user, has_role
from ...core.security import get_password_hash

router = APIRouter(prefix="/api/admin", tags=["إدارة الموظفين والإدارات"])

# ================================================================
# 1. Departments APIs (الإدارات)
# ================================================================

@router.get("/departments", response_model=List[DepartmentResponse])
async def get_departments(
    search: Optional[str] = None,
    skip: int = 0,
    limit: int = 100,
    db: Session = Depends(get_db),
    current_user: Employee = Depends(get_current_user)
):
    query = db.query(Department).filter(Department.is_active == True)
    if search:
        query = query.filter(Department.name.ilike(f"%{search}%"))
    departments = query.offset(skip).limit(limit).all()
    result = []
    for dept in departments:
        parent = db.query(Department).filter(Department.department_id == dept.parent_department_id).first()
        manager = db.query(Employee).filter(Employee.employee_id == dept.manager_employee_id).first()
        employees_count = db.query(Employee).filter(Employee.department_id == dept.department_id, Employee.is_active == True).count()
        result.append(DepartmentResponse(
            department_id=dept.department_id,
            name=dept.name,
            code=dept.code,
            description=dept.description,
            parent_department_id=dept.parent_department_id,
            parent_name=parent.name if parent else None,
            manager_employee_id=dept.manager_employee_id,
            manager_name=manager.full_name if manager else None,
            level=dept.level or 1,
            is_active=dept.is_active,
            created_at=dept.created_at,
            updated_at=dept.updated_at,
            employees_count=employees_count
        ))
    return result

@router.post("/departments", response_model=DepartmentResponse, status_code=status.HTTP_201_CREATED)
async def create_department(
    dept_data: DepartmentCreate,
    db: Session = Depends(get_db),
    current_user: Employee = Depends(has_role(["Super Admin", "وزير / قيادة العليا"]))
):
    existing = db.query(Department).filter(Department.name == dept_data.name).first()
    if existing:
        raise HTTPException(status_code=400, detail="اسم الإدارة موجود مسبقاً")
    if dept_data.parent_department_id:
        parent = db.query(Department).filter(Department.department_id == dept_data.parent_department_id).first()
        if not parent:
            raise HTTPException(status_code=404, detail="الإدارة الأم غير موجودة")
    new_dept = Department(
        name=dept_data.name,
        code=dept_data.code,
        description=dept_data.description,
        parent_department_id=dept_data.parent_department_id,
        manager_employee_id=dept_data.manager_employee_id,
        level=dept_data.level or 1,
        is_active=True
    )
    db.add(new_dept)
    db.commit()
    db.refresh(new_dept)
    return DepartmentResponse(
        department_id=new_dept.department_id,
        name=new_dept.name,
        code=new_dept.code,
        description=new_dept.description,
        parent_department_id=new_dept.parent_department_id,
        level=new_dept.level or 1,
        is_active=new_dept.is_active,
        created_at=new_dept.created_at,
        updated_at=new_dept.updated_at
    )

@router.put("/departments/{dept_id}", response_model=DepartmentResponse)
async def update_department(
    dept_id: int,
    dept_data: DepartmentUpdate,
    db: Session = Depends(get_db),
    current_user: Employee = Depends(has_role(["Super Admin", "وزير / قيادة العليا"]))
):
    dept = db.query(Department).filter(Department.department_id == dept_id).first()
    if not dept:
        raise HTTPException(status_code=404, detail="الإدارة غير موجودة")
    if dept_data.name is not None:
        existing = db.query(Department).filter(Department.name == dept_data.name, Department.department_id != dept_id).first()
        if existing:
            raise HTTPException(status_code=400, detail="اسم الإدارة موجود مسبقاً")
        dept.name = dept_data.name
    if dept_data.code is not None:
        dept.code = dept_data.code
    if dept_data.description is not None:
        dept.description = dept_data.description
    if dept_data.parent_department_id is not None:
        if dept_data.parent_department_id:
            parent = db.query(Department).filter(Department.department_id == dept_data.parent_department_id).first()
            if not parent:
                raise HTTPException(status_code=404, detail="الإدارة الأم غير موجودة")
        dept.parent_department_id = dept_data.parent_department_id
    if dept_data.manager_employee_id is not None:
        dept.manager_employee_id = dept_data.manager_employee_id
    if dept_data.level is not None:
        dept.level = dept_data.level
    if dept_data.is_active is not None:
        dept.is_active = dept_data.is_active
    dept.updated_at = datetime.now()
    db.commit()
    db.refresh(dept)
    return DepartmentResponse(
        department_id=dept.department_id,
        name=dept.name,
        code=dept.code,
        description=dept.description,
        parent_department_id=dept.parent_department_id,
        level=dept.level or 1,
        is_active=dept.is_active,
        created_at=dept.created_at,
        updated_at=dept.updated_at
    )

@router.delete("/departments/{dept_id}")
async def delete_department(
    dept_id: int,
    db: Session = Depends(get_db),
    current_user: Employee = Depends(has_role(["Super Admin"]))
):
    dept = db.query(Department).filter(Department.department_id == dept_id).first()
    if not dept:
        raise HTTPException(status_code=404, detail="الإدارة غير موجودة")
    employees_count = db.query(Employee).filter(Employee.department_id == dept_id).count()
    if employees_count > 0:
        raise HTTPException(status_code=400, detail=f"لا يمكن الحذف: يوجد {employees_count} موظفين في هذه الإدارة")
    db.delete(dept)
    db.commit()
    return {"message": "تم حذف الإدارة بنجاح"}

# ================================================================
# 2. Employees APIs (الموظفون)
# ================================================================

@router.get("/employees", response_model=List[EmployeeResponse])
async def get_employees(
    department_id: Optional[int] = None,
    search: Optional[str] = None,
    skip: int = 0,
    limit: int = 100,
    db: Session = Depends(get_db),
    current_user: Employee = Depends(get_current_user)
):
    query = db.query(Employee).filter(Employee.is_active == True)
    if department_id:
        query = query.filter(Employee.department_id == department_id)
    if search:
        query = query.filter(
            or_(
                Employee.full_name.ilike(f"%{search}%"),
                Employee.email.ilike(f"%{search}%"),
                Employee.phone_number.ilike(f"%{search}%")
            )
        )
    employees = query.offset(skip).limit(limit).all()
    result = []
    for emp in employees:
        dept = db.query(Department).filter(Department.department_id == emp.department_id).first()
        status = db.query(DictStatus).filter(DictStatus.status_id == emp.employment_status_id).first()
        roles = [role.name for role in emp.roles]
        result.append(EmployeeResponse(
            employee_id=emp.employee_id,
            department_id=emp.department_id,
            department_name=dept.name if dept else None,
            employee_number=emp.employee_number,
            full_name=emp.full_name,
            email=emp.email,
            phone_number=emp.phone_number,
            job_title=emp.job_title,
            employment_status_id=emp.employment_status_id,
            employment_status_name=status.name_ar if status else None,
            hire_date=emp.hire_date,
            gps_enabled=emp.gps_enabled,
            is_active=emp.is_active,
            last_login=emp.last_login,
            created_at=emp.created_at,
            updated_at=emp.updated_at,
            roles=roles
        ))
    return result

@router.post("/employees", response_model=EmployeeResponse, status_code=status.HTTP_201_CREATED)
async def create_employee(
    emp_data: EmployeeCreate,
    db: Session = Depends(get_db),
    current_user: Employee = Depends(has_role(["Super Admin", "وزير / قيادة العليا"]))
):
    existing = db.query(Employee).filter(Employee.email == emp_data.email).first()
    if existing:
        raise HTTPException(status_code=400, detail="البريد الإلكتروني موجود مسبقاً")
    dept = db.query(Department).filter(Department.department_id == emp_data.department_id).first()
    if not dept:
        raise HTTPException(status_code=404, detail="الإدارة غير موجودة")
    hashed_pw = get_password_hash(emp_data.password)
    new_emp = Employee(
        department_id=emp_data.department_id,
        full_name=emp_data.full_name,
        email=emp_data.email,
        password=hashed_pw,
        phone_number=emp_data.phone_number,
        job_title=emp_data.job_title,
        employment_status_id=emp_data.employment_status_id or 11,
        hire_date=emp_data.hire_date,
        gps_enabled=emp_data.gps_enabled if emp_data.gps_enabled is not None else True,
        is_active=True
    )
    db.add(new_emp)
    db.flush()
    # إضافة الأدوار باستخدام employee_roles (Table)
    if emp_data.role_ids:
        for role_id in emp_data.role_ids:
            role = db.query(Role).filter(Role.role_id == role_id).first()
            if role:
                stmt = employee_roles.insert().values(employee_id=new_emp.employee_id, role_id=role_id)
                db.execute(stmt)
    db.commit()
    db.refresh(new_emp)
    roles = [role.name for role in new_emp.roles]
    return EmployeeResponse(
        employee_id=new_emp.employee_id,
        department_id=new_emp.department_id,
        department_name=dept.name,
        employee_number=new_emp.employee_number,
        full_name=new_emp.full_name,
        email=new_emp.email,
        phone_number=new_emp.phone_number,
        job_title=new_emp.job_title,
        employment_status_id=new_emp.employment_status_id,
        hire_date=new_emp.hire_date,
        gps_enabled=new_emp.gps_enabled,
        is_active=new_emp.is_active,
        created_at=new_emp.created_at,
        updated_at=new_emp.updated_at,
        roles=roles
    )

@router.put("/employees/{emp_id}", response_model=EmployeeResponse)
async def update_employee(
    emp_id: int,
    emp_data: EmployeeUpdate,
    db: Session = Depends(get_db),
    current_user: Employee = Depends(has_role(["Super Admin", "وزير / قيادة العليا"]))
):
    emp = db.query(Employee).filter(Employee.employee_id == emp_id).first()
    if not emp:
        raise HTTPException(status_code=404, detail="الموظف غير موجود")
    if emp_data.email is not None:
        existing = db.query(Employee).filter(Employee.email == emp_data.email, Employee.employee_id != emp_id).first()
        if existing:
            raise HTTPException(status_code=400, detail="البريد الإلكتروني موجود مسبقاً")
        emp.email = emp_data.email
    if emp_data.department_id is not None:
        dept = db.query(Department).filter(Department.department_id == emp_data.department_id).first()
        if not dept:
            raise HTTPException(status_code=404, detail="الإدارة غير موجودة")
        emp.department_id = emp_data.department_id
    if emp_data.full_name is not None:
        emp.full_name = emp_data.full_name
    if emp_data.phone_number is not None:
        emp.phone_number = emp_data.phone_number
    if emp_data.job_title is not None:
        emp.job_title = emp_data.job_title
    if emp_data.employment_status_id is not None:
        emp.employment_status_id = emp_data.employment_status_id
    if emp_data.hire_date is not None:
        emp.hire_date = emp_data.hire_date
    if emp_data.gps_enabled is not None:
        emp.gps_enabled = emp_data.gps_enabled
    if emp_data.is_active is not None:
        emp.is_active = emp_data.is_active
    # تحديث الأدوار: حذف القديمة وإضافة الجديدة
    if emp_data.role_ids is not None:
        # حذف الأدوار القديمة
        db.execute(employee_roles.delete().where(employee_roles.c.employee_id == emp_id))
        # إضافة الأدوار الجديدة
        for role_id in emp_data.role_ids:
            role = db.query(Role).filter(Role.role_id == role_id).first()
            if role:
                stmt = employee_roles.insert().values(employee_id=emp_id, role_id=role_id)
                db.execute(stmt)
    emp.updated_at = datetime.now()
    db.commit()
    db.refresh(emp)
    dept = db.query(Department).filter(Department.department_id == emp.department_id).first()
    roles = [role.name for role in emp.roles]
    return EmployeeResponse(
        employee_id=emp.employee_id,
        department_id=emp.department_id,
        department_name=dept.name if dept else None,
        employee_number=emp.employee_number,
        full_name=emp.full_name,
        email=emp.email,
        phone_number=emp.phone_number,
        job_title=emp.job_title,
        employment_status_id=emp.employment_status_id,
        hire_date=emp.hire_date,
        gps_enabled=emp.gps_enabled,
        is_active=emp.is_active,
        last_login=emp.last_login,
        created_at=emp.created_at,
        updated_at=emp.updated_at,
        roles=roles
    )

@router.delete("/employees/{emp_id}")
async def delete_employee(
    emp_id: int,
    db: Session = Depends(get_db),
    current_user: Employee = Depends(has_role(["Super Admin"]))
):
    emp = db.query(Employee).filter(Employee.employee_id == emp_id).first()
    if not emp:
        raise HTTPException(status_code=404, detail="الموظف غير موجود")
    # التحقق من المهام المعينة
    assignments = db.query(TaskAssignment).filter(TaskAssignment.employee_id == emp_id, TaskAssignment.is_active == True).count()
    if assignments > 0:
        raise HTTPException(status_code=400, detail=f"لا يمكن الحذف: الموظف معين في {assignments} مهام نشطة")
    # حذف أدوار الموظف
    db.execute(employee_roles.delete().where(employee_roles.c.employee_id == emp_id))
    db.delete(emp)
    db.commit()
    return {"message": "تم حذف الموظف بنجاح"}

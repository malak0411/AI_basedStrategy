from fastapi import APIRouter, Depends, HTTPException, Query
from sqlalchemy.orm import Session
from sqlalchemy import func, desc, and_
from typing import Optional
from datetime import datetime, date
from decimal import Decimal

from app.models import LocationLog, Employee, OperationalTask, Department
from app.core.dependencies import  get_current_user
from app.database import get_db
router = APIRouter(prefix="/api/locations", tags=["Locations"])

def get_employee_department(current_user_id: int, db: Session):
    employee = db.query(Employee).filter(Employee.employee_id == current_user_id).first()
    if not employee:
        raise HTTPException(status_code=404, detail="الموظف غير موجود")
    return employee

@router.get("/")
async def get_locations(
    employee_id: Optional[int] = Query(None),
    task_id: Optional[int] = Query(None),
    date_from: Optional[str] = Query(None),
    date_to: Optional[str] = Query(None),
    source: Optional[str] = Query(None),
    latest_only: bool = Query(False),
    page: int = Query(1, ge=1),
    per_page: int = Query(10, ge=1, le=100),
    current_user: int = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    user_employee = get_employee_department(current_user, db)
    department_id = user_employee.department_id

    query = db.query(LocationLog).join(
        Employee, Employee.employee_id == LocationLog.employee_id
    ).filter(Employee.department_id == department_id)

    if employee_id:
        query = query.filter(LocationLog.employee_id == employee_id)

    if task_id:
        query = query.filter(LocationLog.task_id == task_id)

    if date_from:
        query = query.filter(LocationLog.recorded_at >= date_from)

    if date_to:
        query = query.filter(LocationLog.recorded_at <= date_to)

    if source:
        query = query.filter(LocationLog.source == source)

    if latest_only:
        subquery = db.query(
            LocationLog.employee_id,
            func.max(LocationLog.recorded_at).label("max_recorded")
        ).join(
            Employee, Employee.employee_id == LocationLog.employee_id
        ).filter(
            Employee.department_id == department_id
        ).group_by(LocationLog.employee_id).subquery()

        query = query.join(
            subquery,
            and_(
                LocationLog.employee_id == subquery.c.employee_id,
                LocationLog.recorded_at == subquery.c.max_recorded
            )
        )

    total = query.count()
    locations = query.offset((page - 1) * per_page).limit(per_page).all()

    result = []
    for loc in locations:
        employee = db.query(Employee).filter(
            Employee.employee_id == loc.employee_id
        ).first()

        task = db.query(OperationalTask).filter(
            OperationalTask.task_id == loc.task_id
        ).first() if loc.task_id else None

        department = db.query(Department).filter(
            Department.department_id == employee.department_id
        ).first() if employee else None

        result.append({
            "location_id": loc.location_id,
            "employee_id": loc.employee_id,
            "employee": {
                "employee_id": employee.employee_id if employee else None,
                "employee_number": employee.employee_number if employee else None,
                "full_name": employee.full_name if employee else None,
                "job_title": employee.job_title if employee else None,
                "department": {
                    "department_id": department.department_id if department else None,
                    "name": department.name if department else None
                },
                "gps_enabled": employee.gps_enabled if employee else False,
                "is_active": employee.is_active if employee else False
            },
            "task_id": loc.task_id,
            "task": {
                "task_id": task.task_id if task else None,
                "title": task.title if task else None
            },
            "latitude": float(loc.latitude) if loc.latitude else None,
            "longitude": float(loc.longitude) if loc.longitude else None,
            "accuracy": float(loc.accuracy) if loc.accuracy else None,
            "recorded_at": loc.recorded_at.isoformat() if loc.recorded_at else None,
            "source": loc.source
        })

    return {
        "data": result,
        "total": total,
        "page": page,
        "per_page": per_page,
        "total_pages": (total + per_page - 1) // per_page
    }

@router.get("/summary")
async def get_location_summary(
    current_user: int = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    user_employee = get_employee_department(current_user, db)
    department_id = user_employee.department_id

    total_logs = db.query(LocationLog).join(
        Employee, Employee.employee_id == LocationLog.employee_id
    ).filter(
        Employee.department_id == department_id
    ).count()

    employees_with_gps = db.query(Employee).filter(
        Employee.department_id == department_id,
        Employee.is_active == True,
        Employee.gps_enabled == True
    ).count()

    today = date.today()
    today_start = datetime.combine(today, datetime.min.time())
    today_end = datetime.combine(today, datetime.max.time())

    locations_today = db.query(LocationLog).join(
        Employee, Employee.employee_id == LocationLog.employee_id
    ).filter(
        Employee.department_id == department_id,
        LocationLog.recorded_at >= today_start,
        LocationLog.recorded_at <= today_end
    ).count()

    active_employees = db.query(Employee).filter(
        Employee.department_id == department_id,
        Employee.is_active == True
    ).count()

    latest_timestamp = db.query(
        func.max(LocationLog.recorded_at)
    ).join(
        Employee, Employee.employee_id == LocationLog.employee_id
    ).filter(
        Employee.department_id == department_id
    ).scalar()

    return {
        "total_logs": total_logs,
        "employees_with_gps": employees_with_gps,
        "locations_today": locations_today,
        "active_employees": active_employees,
        "latest_recorded_at": latest_timestamp.isoformat() if latest_timestamp else None
    }

@router.get("/map")
async def get_map_locations(
    current_user: int = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    user_employee = get_employee_department(current_user, db)
    department_id = user_employee.department_id

    subquery = db.query(
        LocationLog.employee_id,
        func.max(LocationLog.recorded_at).label("max_recorded")
    ).join(
        Employee, Employee.employee_id == LocationLog.employee_id
    ).filter(
        Employee.department_id == department_id
    ).group_by(LocationLog.employee_id).subquery()

    locations = db.query(LocationLog).join(
        subquery,
        and_(
            LocationLog.employee_id == subquery.c.employee_id,
            LocationLog.recorded_at == subquery.c.max_recorded
        )
    ).all()

    result = []
    for loc in locations:
        employee = db.query(Employee).filter(
            Employee.employee_id == loc.employee_id,
            Employee.is_active == True,
            Employee.gps_enabled == True
        ).first()

        if not employee:
            continue

        task = db.query(OperationalTask).filter(
            OperationalTask.task_id == loc.task_id
        ).first() if loc.task_id else None

        result.append({
            "location_id": loc.location_id,
            "employee_id": loc.employee_id,
            "employee_name": employee.full_name,
            "employee_number": employee.employee_number,
            "job_title": employee.job_title,
            "task_id": loc.task_id,
            "task_title": task.title if task else None,
            "latitude": float(loc.latitude) if loc.latitude else None,
            "longitude": float(loc.longitude) if loc.longitude else None,
            "accuracy": float(loc.accuracy) if loc.accuracy else None,
            "recorded_at": loc.recorded_at.isoformat() if loc.recorded_at else None,
            "source": loc.source
        })

    return {"data": result}

@router.get("/{location_id}")
async def get_location_detail(
    location_id: int,
    current_user: int = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    user_employee = get_employee_department(current_user, db)
    department_id = user_employee.department_id

    location = db.query(LocationLog).filter(
        LocationLog.location_id == location_id
    ).first()

    if not location:
        raise HTTPException(status_code=404, detail="سجل الموقع غير موجود")

    employee = db.query(Employee).filter(
        Employee.employee_id == location.employee_id
    ).first()

    if employee.department_id != department_id:
        raise HTTPException(status_code=403, detail="غير مصرح بعرض هذا الموقع")

    task = db.query(OperationalTask).filter(
        OperationalTask.task_id == location.task_id
    ).first() if location.task_id else None

    department = db.query(Department).filter(
        Department.department_id == employee.department_id
    ).first() if employee else None

    return {
        "location_id": location.location_id,
        "employee_id": location.employee_id,
        "employee": {
            "employee_id": employee.employee_id if employee else None,
            "employee_number": employee.employee_number if employee else None,
            "full_name": employee.full_name if employee else None,
            "job_title": employee.job_title if employee else None,
            "department": {
                "department_id": department.department_id if department else None,
                "name": department.name if department else None
            },
            "gps_enabled": employee.gps_enabled if employee else False,
            "is_active": employee.is_active if employee else False
        },
        "task_id": location.task_id,
        "task": {
            "task_id": task.task_id if task else None,
            "title": task.title if task else None
        },
        "latitude": float(location.latitude) if location.latitude else None,
        "longitude": float(location.longitude) if location.longitude else None,
        "accuracy": float(location.accuracy) if location.accuracy else None,
        "recorded_at": location.recorded_at.isoformat() if location.recorded_at else None,
        "source": location.source
    }

@router.get("/employees/{employee_id}/latest")
async def get_employee_latest_location(
    employee_id: int,
    current_user: int = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    user_employee = get_employee_department(current_user, db)
    department_id = user_employee.department_id

    employee = db.query(Employee).filter(
        Employee.employee_id == employee_id
    ).first()

    if not employee:
        raise HTTPException(status_code=404, detail="الموظف غير موجود")

    if employee.department_id != department_id:
        raise HTTPException(status_code=403, detail="غير مصرح بعرض هذا الموظف")

    location = db.query(LocationLog).filter(
        LocationLog.employee_id == employee_id
    ).order_by(desc(LocationLog.recorded_at)).first()

    department = db.query(Department).filter(
        Department.department_id == employee.department_id
    ).first()

    if not location:
        return {
            "employee_id": employee_id,
            "employee": {
                "employee_id": employee.employee_id,
                "employee_number": employee.employee_number,
                "full_name": employee.full_name,
                "job_title": employee.job_title,
                "department": {
                    "department_id": department.department_id if department else None,
                    "name": department.name if department else None
                },
                "gps_enabled": employee.gps_enabled,
                "is_active": employee.is_active
            },
            "has_location": False
        }

    task = db.query(OperationalTask).filter(
        OperationalTask.task_id == location.task_id
    ).first() if location.task_id else None

    return {
        "employee_id": employee_id,
        "employee": {
            "employee_id": employee.employee_id,
            "employee_number": employee.employee_number,
            "full_name": employee.full_name,
            "job_title": employee.job_title,
            "department": {
                "department_id": department.department_id if department else None,
                "name": department.name if department else None
            },
            "gps_enabled": employee.gps_enabled,
            "is_active": employee.is_active
        },
        "has_location": True,
        "location": {
            "location_id": location.location_id,
            "task_id": location.task_id,
            "task": {
                "task_id": task.task_id if task else None,
                "title": task.title if task else None
            },
            "latitude": float(location.latitude) if location.latitude else None,
            "longitude": float(location.longitude) if location.longitude else None,
            "accuracy": float(location.accuracy) if location.accuracy else None,
            "recorded_at": location.recorded_at.isoformat() if location.recorded_at else None,
            "source": location.source
        }
    }

@router.get("/employees/{employee_id}/history")
async def get_employee_location_history(
    employee_id: int,
    date_from: Optional[str] = Query(None),
    date_to: Optional[str] = Query(None),
    page: int = Query(1, ge=1),
    per_page: int = Query(10, ge=1, le=100),
    current_user: int = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    user_employee = get_employee_department(current_user, db)
    department_id = user_employee.department_id

    employee = db.query(Employee).filter(
        Employee.employee_id == employee_id
    ).first()

    if not employee:
        raise HTTPException(status_code=404, detail="الموظف غير موجود")

    if employee.department_id != department_id:
        raise HTTPException(status_code=403, detail="غير مصرح بعرض هذا الموظف")

    query = db.query(LocationLog).filter(
        LocationLog.employee_id == employee_id
    )

    if date_from:
        query = query.filter(LocationLog.recorded_at >= date_from)

    if date_to:
        query = query.filter(LocationLog.recorded_at <= date_to)

    total = query.count()
    locations = query.order_by(desc(LocationLog.recorded_at)).offset(
        (page - 1) * per_page
    ).limit(per_page).all()

    result = []
    for loc in locations:
        task = db.query(OperationalTask).filter(
            OperationalTask.task_id == loc.task_id
        ).first() if loc.task_id else None

        result.append({
            "location_id": loc.location_id,
            "task_id": loc.task_id,
            "task": {
                "task_id": task.task_id if task else None,
                "title": task.title if task else None
            },
            "latitude": float(loc.latitude) if loc.latitude else None,
            "longitude": float(loc.longitude) if loc.longitude else None,
            "accuracy": float(loc.accuracy) if loc.accuracy else None,
            "recorded_at": loc.recorded_at.isoformat() if loc.recorded_at else None,
            "source": loc.source
        })

    department = db.query(Department).filter(
        Department.department_id == employee.department_id
    ).first()

    return {
        "employee": {
            "employee_id": employee.employee_id,
            "employee_number": employee.employee_number,
            "full_name": employee.full_name,
            "job_title": employee.job_title,
            "department": {
                "department_id": department.department_id if department else None,
                "name": department.name if department else None
            },
            "gps_enabled": employee.gps_enabled,
            "is_active": employee.is_active
        },
        "data": result,
        "total": total,
        "page": page,
        "per_page": per_page,
        "total_pages": (total + per_page - 1) // per_page
    }

@router.post("/")
async def create_location(
    data: dict,
    current_user: int = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    user_employee = get_employee_department(current_user, db)
    department_id = user_employee.department_id

    employee_id = data.get("employee_id")
    task_id = data.get("task_id")
    latitude = data.get("latitude")
    longitude = data.get("longitude")
    accuracy = data.get("accuracy")
    recorded_at = data.get("recorded_at")
    source = data.get("source")

    if not latitude or not longitude:
        raise HTTPException(status_code=400, detail="خط الطول والعرض مطلوبان")

    if latitude < -90 or latitude > 90:
        raise HTTPException(status_code=400, detail="خط العرض غير صحيح")

    if longitude < -180 or longitude > 180:
        raise HTTPException(status_code=400, detail="خط الطول غير صحيح")

    if accuracy is not None and accuracy < 0:
        raise HTTPException(status_code=400, detail="دقة الموقع غير صحيحة")

    employee = db.query(Employee).filter(
        Employee.employee_id == employee_id
    ).first()

    if not employee:
        raise HTTPException(status_code=404, detail="الموظف غير موجود")

    if employee.department_id != department_id:
        raise HTTPException(status_code=403, detail="لا يمكنك تسجيل موقع لموظف في قسم آخر")

    if not employee.gps_enabled:
        raise HTTPException(status_code=403, detail="نظام GPS معطل لهذا الموظف")

    if task_id:
        task = db.query(OperationalTask).filter(
            OperationalTask.task_id == task_id
        ).first()
        if not task:
            raise HTTPException(status_code=404, detail="المهمة التشغيلية غير موجودة")

    new_location = LocationLog(
        employee_id=employee_id,
        task_id=task_id,
        latitude=latitude,
        longitude=longitude,
        accuracy=accuracy,
        source=source or "manual"
    )

    if recorded_at:
        new_location.recorded_at = datetime.fromisoformat(recorded_at)

    db.add(new_location)
    db.commit()
    db.refresh(new_location)

    task = db.query(OperationalTask).filter(
        OperationalTask.task_id == new_location.task_id
    ).first() if new_location.task_id else None

    return {
        "success": True,
        "message": "تم تسجيل الموقع بنجاح",
        "location_id": new_location.location_id,
        "location": {
            "location_id": new_location.location_id,
            "employee_id": new_location.employee_id,
            "task_id": new_location.task_id,
            "task": {
                "task_id": task.task_id if task else None,
                "title": task.title if task else None
            },
            "latitude": float(new_location.latitude) if new_location.latitude else None,
            "longitude": float(new_location.longitude) if new_location.longitude else None,
            "accuracy": float(new_location.accuracy) if new_location.accuracy else None,
            "recorded_at": new_location.recorded_at.isoformat() if new_location.recorded_at else None,
            "source": new_location.source
        }
    }

@router.delete("/{location_id}")
async def delete_location(
    location_id: int,
    current_user: int = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    user_employee = get_employee_department(current_user, db)
    department_id = user_employee.department_id

    location = db.query(LocationLog).filter(
        LocationLog.location_id == location_id
    ).first()

    if not location:
        raise HTTPException(status_code=404, detail="سجل الموقع غير موجود")

    employee = db.query(Employee).filter(
        Employee.employee_id == location.employee_id
    ).first()

    if employee.department_id != department_id:
        raise HTTPException(status_code=403, detail="غير مصرح بحذف هذا الموقع")

    db.delete(location)
    db.commit()

    return {
        "success": True,
        "message": "تم حذف سجل الموقع بنجاح"
    }

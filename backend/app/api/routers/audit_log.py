from fastapi import APIRouter, Depends, Query, Request
from sqlalchemy.orm import Session, joinedload
from typing import Optional
from datetime import datetime

from app.database import get_db
from app.models import AuditLog
from app.core.dependencies import get_current_employee

router = APIRouter(prefix="/api/audit-logs", tags=["Audit Logs"])


def _serialize_log(log: AuditLog) -> dict:
    return {
        "log_id": log.log_id,
        "employee_id": log.employee_id,
        "employee_name": log.employee.full_name if log.employee else "غير معروف",
        "employee_email": log.employee.email if log.employee else None,
        "action": log.action,
        "table_name": log.table_name,
        "record_id": log.record_id,
        "old_data": log.old_data,
        "new_data": log.new_data,
        "ip_address": log.ip_address,
        "user_agent": log.user_agent,
        "created_at": log.created_at.isoformat() if log.created_at else None,
    }


@router.get("")
async def list_audit_logs(
    db: Session = Depends(get_db),
    employee_id_authenticated: int = Depends(get_current_employee),
    skip: int = Query(0, ge=0),
    limit: int = Query(50, ge=1, le=500),
    employee_id: Optional[int] = Query(None, description="تصفية بموظف معيّن"),
    employee_name: Optional[str] = Query(None, description="تصفية باسم الموظف (بحث جزئي)"),
    action: Optional[str] = Query(None),
    table_name: Optional[str] = Query(None),
    date_from: Optional[datetime] = Query(None),
    date_to: Optional[datetime] = Query(None),
):
    query = (
        db.query(AuditLog)
        .options(joinedload(AuditLog.employee))
    )

    if employee_id is not None:
        query = query.filter(AuditLog.employee_id == employee_id)
    if action:
        query = query.filter(AuditLog.action == action)
    if table_name:
        query = query.filter(AuditLog.table_name == table_name)
    if date_from:
        query = query.filter(AuditLog.created_at >= date_from)
    if date_to:
        query = query.filter(AuditLog.created_at <= date_to)

    if employee_name:
        from app.models import Employee
        query = query.join(Employee, AuditLog.employee_id == Employee.employee_id).filter(
            Employee.full_name.ilike(f"%{employee_name}%")
        )

    total = query.count()
    logs = (
        query
        .order_by(AuditLog.created_at.desc())
        .offset(skip)
        .limit(limit)
        .all()
    )

    return {
        "success": True,
        "total": total,
        "data": [_serialize_log(log) for log in logs],
    }


@router.get("/{log_id}")
async def get_audit_log(
    log_id: int,
    db: Session = Depends(get_db),
    employee_id_authenticated: int = Depends(get_current_employee),
):
    log = (
        db.query(AuditLog)
        .options(joinedload(AuditLog.employee))
        .filter(AuditLog.log_id == log_id)
        .one_or_none()
    )
    if not log:
        from fastapi import HTTPException
        raise HTTPException(status_code=404, detail="السجل غير موجود")
    return {"success": True, "data": _serialize_log(log)}

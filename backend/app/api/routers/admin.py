from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session
from app.database import get_db
from app.models import AuditLog, Role, Permission, employee_roles, role_permissions

router = APIRouter(prefix="/api", tags=["Admin"])

@router.get("/audit-logs")
async def audit_logs(db: Session = Depends(get_db)):
    try:
        logs = db.query(AuditLog).order_by(AuditLog.created_at.desc()).limit(100).all()
        return {"success": True, "data": [{"id": l.audit_id, "employee_name": f"موظف {l.employee_id}", "action": l.action, "table_name": l.table_name, "record_id": l.record_id, "created_at": l.created_at.isoformat() if l.created_at else None, "ip_address": l.ip_address or "", "old_values": l.old_data, "new_values": l.new_data} for l in logs]}
    except Exception as e: raise HTTPException(status_code=500, detail=str(e))

@router.get("/audit-logs/{log_id}")
async def audit_log_detail(log_id: int, db: Session = Depends(get_db)):
    try:
        l = db.query(AuditLog).filter(AuditLog.audit_id == log_id).first()
        if not l: raise HTTPException(status_code=404, detail="غير موجود")
        return {"success": True, "data": {"id": l.audit_id, "employee_name": f"موظف {l.employee_id}", "action": l.action, "table_name": l.table_name, "created_at": l.created_at.isoformat() if l.created_at else None}}
    except HTTPException: raise
    except Exception as e: raise HTTPException(status_code=500, detail=str(e))

@router.get("/roles")
async def roles(db: Session = Depends(get_db)):
    try:
        roles = db.query(Role).all()
        return {"success": True, "data": [{"id": r.role_id, "name": r.name, "description": r.description or "", "is_system": r.is_system, "created_at": r.created_at.isoformat() if r.created_at else None} for r in roles]}
    except Exception as e: raise HTTPException(status_code=500, detail=str(e))

@router.get("/permissions")
async def permissions(db: Session = Depends(get_db)):
    try:
        perms = db.query(Permission).all()
        return {"success": True, "data": [{"id": p.permission_id, "name": p.name, "description": p.description or ""} for p in perms]}
    except Exception as e: raise HTTPException(status_code=500, detail=str(e))

@router.get("/employee-roles")
async def get_employee_roles(db: Session = Depends(get_db)):
    try:
        # employee_roles هو Table وليس Class - نستخدم استعلام SQL مباشر
        result = db.execute(employee_roles.select()).fetchall()
        data = [{"employee_name": f"موظف {row.employee_id}", "role_name": f"دور {row.role_id}"} for row in result]
        return {"success": True, "data": data}
    except Exception as e: raise HTTPException(status_code=500, detail=str(e))

@router.get("/role-permissions")
async def get_role_permissions(db: Session = Depends(get_db)):
    try:
        # role_permissions هو Table وليس Class
        result = db.execute(role_permissions.select()).fetchall()
        data = [{"role_name": f"دور {row.role_id}", "permission_name": f"صلاحية {row.permission_id}"} for row in result]
        return {"success": True, "data": data}
    except Exception as e: raise HTTPException(status_code=500, detail=str(e))

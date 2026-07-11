from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session
from app.database import get_db
from app.models import AuditLog, Role, Permission, employee_roles, role_permissions
from pydantic import BaseModel
from typing import Optional

router = APIRouter(prefix="/api", tags=["Admin"])

# ============================================================
# Schemas
# ============================================================
class RoleCreate(BaseModel):
    name: str
    description: Optional[str] = ""
    is_system: Optional[bool] = False

class RoleUpdate(BaseModel):
    name: Optional[str] = None
    description: Optional[str] = None

class EmployeeRoleAssign(BaseModel):
    employee_id: int
    role_id: int

class RolePermissionAssign(BaseModel):
    role_id: int
    permission_id: int

# ============================================================
# AUDIT LOGS
# ============================================================

@router.get("/audit-logs")
async def audit_logs(db: Session = Depends(get_db)):
    try:
        logs = db.query(AuditLog).order_by(AuditLog.created_at.desc()).limit(100).all()
        result = [{
            "id": l.audit_id, "employee_name": f"موظف {l.employee_id}",
            "action": l.action, "table_name": l.table_name,
            "record_id": l.record_id, "created_at": l.created_at.isoformat() if l.created_at else None,
            "ip_address": l.ip_address or "", "old_values": l.old_data, "new_values": l.new_data
        } for l in logs]
        return {"success": True, "data": result}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@router.get("/audit-logs/{log_id}")
async def audit_log_detail(log_id: int, db: Session = Depends(get_db)):
    try:
        l = db.query(AuditLog).filter(AuditLog.audit_id == log_id).first()
        if not l: raise HTTPException(status_code=404, detail="غير موجود")
        return {"success": True, "data": {
            "id": l.audit_id, "employee_name": f"موظف {l.employee_id}",
            "action": l.action, "table_name": l.table_name,
            "created_at": l.created_at.isoformat() if l.created_at else None,
            "old_values": l.old_data, "new_values": l.new_data
        }}
    except HTTPException: raise
    except Exception as e: raise HTTPException(status_code=500, detail=str(e))

# ============================================================
# ROLES - GET/POST/PUT/DELETE
# ============================================================

@router.get("/roles")
async def roles(db: Session = Depends(get_db)):
    try:
        roles = db.query(Role).all()
        result = [{
            "id": r.role_id, "name": r.name, "description": r.description or "",
            "is_system": r.is_system, "created_at": r.created_at.isoformat() if r.created_at else None
        } for r in roles]
        return {"success": True, "data": result}
    except Exception as e: raise HTTPException(status_code=500, detail=str(e))

@router.get("/roles/{role_id}")
async def role_detail(role_id: int, db: Session = Depends(get_db)):
    try:
        r = db.query(Role).filter(Role.role_id == role_id).first()
        if not r: raise HTTPException(status_code=404, detail="غير موجود")
        return {"success": True, "data": {
            "id": r.role_id, "name": r.name, "description": r.description or "",
            "is_system": r.is_system
        }}
    except HTTPException: raise
    except Exception as e: raise HTTPException(status_code=500, detail=str(e))

@router.post("/roles")
async def create_role(data: RoleCreate, db: Session = Depends(get_db)):
    try:
        role = Role(name=data.name, description=data.description, is_system=data.is_system)
        db.add(role)
        db.commit()
        db.refresh(role)
        return {"success": True, "data": {"id": role.role_id, "name": role.name}}
    except Exception as e:
        db.rollback()
        raise HTTPException(status_code=500, detail=str(e))

@router.put("/roles/{role_id}")
async def update_role(role_id: int, data: RoleUpdate, db: Session = Depends(get_db)):
    try:
        r = db.query(Role).filter(Role.role_id == role_id).first()
        if not r: raise HTTPException(status_code=404, detail="غير موجود")
        if data.name is not None: r.name = data.name
        if data.description is not None: r.description = data.description
        db.commit()
        return {"success": True, "message": "تم التحديث"}
    except HTTPException: raise
    except Exception as e:
        db.rollback()
        raise HTTPException(status_code=500, detail=str(e))

@router.delete("/roles/{role_id}")
async def delete_role(role_id: int, db: Session = Depends(get_db)):
    try:
        r = db.query(Role).filter(Role.role_id == role_id).first()
        if not r: raise HTTPException(status_code=404, detail="غير موجود")
        db.delete(r)
        db.commit()
        return {"success": True, "message": "تم الحذف"}
    except HTTPException: raise
    except Exception as e:
        db.rollback()
        raise HTTPException(status_code=500, detail=str(e))

# ============================================================
# PERMISSIONS
# ============================================================

@router.get("/permissions")
async def permissions(db: Session = Depends(get_db)):
    try:
        perms = db.query(Permission).all()
        result = [{"id": p.permission_id, "name": p.name, "description": p.description or ""} for p in perms]
        return {"success": True, "data": result}
    except Exception as e: raise HTTPException(status_code=500, detail=str(e))

@router.get("/permissions/{permission_id}")
async def permission_detail(permission_id: int, db: Session = Depends(get_db)):
    try:
        p = db.query(Permission).filter(Permission.permission_id == permission_id).first()
        if not p: raise HTTPException(status_code=404, detail="غير موجودة")
        return {"success": True, "data": {"id": p.permission_id, "name": p.name, "description": p.description or ""}}
    except HTTPException: raise
    except Exception as e: raise HTTPException(status_code=500, detail=str(e))

# ============================================================
# EMPLOYEE ROLES - ربط الموظفين بالأدوار
# ============================================================

@router.get("/employee-roles")
async def get_employee_roles(db: Session = Depends(get_db)):
    try:
        result = db.execute(employee_roles.select()).fetchall()
        data = [{"employee_id": row.employee_id, "role_id": row.role_id, "employee_name": f"موظف {row.employee_id}", "role_name": f"دور {row.role_id}"} for row in result]
        return {"success": True, "data": data}
    except Exception as e: raise HTTPException(status_code=500, detail=str(e))

@router.post("/employee-roles")
async def assign_employee_role(data: EmployeeRoleAssign, db: Session = Depends(get_db)):
    try:
        db.execute(employee_roles.insert().values(employee_id=data.employee_id, role_id=data.role_id))
        db.commit()
        return {"success": True, "message": "تم ربط الموظف بالدور"}
    except Exception as e:
        db.rollback()
        raise HTTPException(status_code=500, detail=str(e))

@router.delete("/employee-roles")
async def remove_employee_role(data: EmployeeRoleAssign, db: Session = Depends(get_db)):
    try:
        db.execute(employee_roles.delete().where(
            (employee_roles.c.employee_id == data.employee_id) & 
            (employee_roles.c.role_id == data.role_id)
        ))
        db.commit()
        return {"success": True, "message": "تم إلغاء الربط"}
    except Exception as e:
        db.rollback()
        raise HTTPException(status_code=500, detail=str(e))

# ============================================================
# ROLE PERMISSIONS - ربط الأدوار بالصلاحيات
# ============================================================

@router.get("/role-permissions")
async def get_role_permissions(db: Session = Depends(get_db)):
    try:
        result = db.execute(role_permissions.select()).fetchall()
        data = [{"role_id": row.role_id, "permission_id": row.permission_id, "role_name": f"دور {row.role_id}", "permission_name": f"صلاحية {row.permission_id}"} for row in result]
        return {"success": True, "data": data}
    except Exception as e: raise HTTPException(status_code=500, detail=str(e))

@router.post("/role-permissions")
async def assign_role_permission(data: RolePermissionAssign, db: Session = Depends(get_db)):
    try:
        db.execute(role_permissions.insert().values(role_id=data.role_id, permission_id=data.permission_id))
        db.commit()
        return {"success": True, "message": "تم ربط الدور بالصلاحية"}
    except Exception as e:
        db.rollback()
        raise HTTPException(status_code=500, detail=str(e))

@router.delete("/role-permissions")
async def remove_role_permission(data: RolePermissionAssign, db: Session = Depends(get_db)):
    try:
        db.execute(role_permissions.delete().where(
            (role_permissions.c.role_id == data.role_id) & 
            (role_permissions.c.permission_id == data.permission_id)
        ))
        db.commit()
        return {"success": True, "message": "تم إلغاء الربط"}
    except Exception as e:
        db.rollback()
        raise HTTPException(status_code=500, detail=str(e))

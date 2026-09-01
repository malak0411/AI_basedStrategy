from fastapi import Depends, HTTPException, status, Request
from fastapi.security import HTTPBearer, HTTPAuthorizationCredentials
from sqlalchemy.orm import Session
from slowapi import Limiter
from slowapi.util import get_remote_address

from ..database import get_db
from ..models import Employee
from ..core.security import decode_access_token

limiter = Limiter(key_func=get_remote_address)

security = HTTPBearer()

def get_current_user(
    request: Request,
    credentials: HTTPAuthorizationCredentials = Depends(security),
    db: Session = Depends(get_db)
) -> Employee:
    token = credentials.credentials
    payload = decode_access_token(token)
    
    if payload is None:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="توكن غير صالح أو منتهي الصلاحية",
            headers={"WWW-Authenticate": "Bearer"},
        )
    
    employee_id = payload.get("sub")
    if employee_id is None:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="معرف المستخدم غير موجود في التوكن"
        )
    
    employee = db.query(Employee).filter(Employee.employee_id == int(employee_id)).first()
    if employee is None:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="المستخدم غير موجود"
        )
    
    if not employee.is_active:
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="الحساب غير نشط، يرجى مراجعة المسؤول"
        )
    
    return employee

def has_role(required_roles: list):
    def role_checker(current_user: Employee = Depends(get_current_user)):
        user_roles = [role.name for role in current_user.roles]
        for role in required_roles:
            if role in user_roles:
                return current_user
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail=f"غير مصرح. تحتاج إلى أحد الأدوار: {', '.join(required_roles)}"
        )
    return role_checker

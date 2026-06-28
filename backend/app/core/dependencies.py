from fastapi import Depends, HTTPException, status, Request
from fastapi.security import HTTPBearer, HTTPAuthorizationCredentials
from sqlalchemy.orm import Session
from slowapi import Limiter
from slowapi.util import get_remote_address
from jose import JWTError

from ..database import get_db
from ..models import Employee
from ..core.security import decode_access_token, hash_ip_address

# ================================================================
# Rate Limiter (مشترك بين جميع الـ APIs)
# ================================================================
limiter = Limiter(key_func=get_remote_address)

# ================================================================
# توكن مؤقت للتجربة (سيتم إزالته في الإنتاج)
# ================================================================
TEMP_TOKEN = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxIiwiZW1haWwiOiJzYWxlaC5iYXdhemlyQG1vbS15ZS5vcmciLCJleHAiOjE3ODIxOTU1NTcsImlhdCI6MTc4MjE2Njc1N30.d4ad-ReX8zieJcaQSeBFBlIRPQlS3WuvRhKnpB-_48U"

security = HTTPBearer()

# ================================================================
# دالة الحصول على المستخدم الحالي (مع دعم التوكن المؤقت)
# ================================================================

def get_current_user(
    request: Request,
    credentials: HTTPAuthorizationCredentials = Depends(security),
    db: Session = Depends(get_db)
) -> Employee:
    """
    استخراج المستخدم من التوكن.
    - يدعم التوكن المؤقت (TEMP_TOKEN) لتجربة جميع الـ APIs.
    - في الإنتاج، يتم التحقق من JWT بشكل طبيعي.
    """
    token = credentials.credentials
    client_ip = request.client.host if request.client else "unknown"
    hashed_ip = hash_ip_address(client_ip)

    # ============================================================
    # 1. التحقق من التوكن المؤقت (للتجربة فقط)
    # ============================================================
    if token == TEMP_TOKEN:
        # إرجاع الموظف رقم 1 (صالح قاسم باوزير)
        employee = db.query(Employee).filter(Employee.employee_id == 1).first()
        if not employee:
            raise HTTPException(
                status_code=status.HTTP_401_UNAUTHORIZED,
                detail="المستخدم المؤقت غير موجود في قاعدة البيانات"
            )
        # نعيد المستخدم دون التحقق من الصلاحية (للتجربة)
        return employee

    # ============================================================
    # 2. التحقق العادي من JWT (للاستخدام الحقيقي)
    # ============================================================
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


# ================================================================
# دالة التحقق من الصلاحيات (RBAC) مع دعم مؤقت
# ================================================================

def has_role(required_roles: list):
    """
    التحقق من أن المستخدم لديه أحد الأدوار المطلوبة.
    - إذا كان المستخدم هو المستخدم المؤقت (الموظف رقم 1)، يتم تجاوز التحقق.
    - في الإنتاج، يتم التحقق من الأدوار بشكل طبيعي.
    """
    def role_checker(current_user: Employee = Depends(get_current_user)):
        # ============================================================
        # تجاوز التحقق إذا كان المستخدم هو المستخدم المؤقت (للتجربة)
        # ============================================================
        if current_user.employee_id == 1:
            # نسمح بالمرور دون التحقق من الأدوار (للتجربة فقط)
            return current_user

        # ============================================================
        # التحقق العادي من الأدوار
        # ============================================================
        user_roles = [role.name for role in current_user.roles]
        for role in required_roles:
            if role in user_roles:
                return current_user

        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail=f"غير مصرح. تحتاج إلى أحد الأدوار: {', '.join(required_roles)}"
        )
    return role_checker


# ================================================================
# دالة التأكد من أن المستخدم نشط (للاستخدام الإضافي)
# ================================================================

def get_current_active_user(current_user: Employee = Depends(get_current_user)) -> Employee:
    """تأكد من أن المستخدم نشط (يُستخدم للتحقق الإضافي)"""
    if not current_user.is_active:
        raise HTTPException(status_code=400, detail="المستخدم غير نشط")
    return current_user

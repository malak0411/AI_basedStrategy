from fastapi import Depends, HTTPException, status
from fastapi.security import HTTPBearer, HTTPAuthorizationCredentials
from jose import JWTError, jwt
import os

security = HTTPBearer()

SECRET_KEY = os.getenv("SECRET_KEY", "your-secret-key")
ALGORITHM = os.getenv("ALGORITHM", "HS256")

def get_current_employee(credentials: HTTPAuthorizationCredentials = Depends(security)):
    """استخراج employee_id من JWT Token"""
    token = credentials.credentials
    try:
        payload = jwt.decode(token, SECRET_KEY, algorithms=[ALGORITHM])
        employee_id: str = payload.get("employee_id") or payload.get("sub")
        if employee_id is None:
            raise HTTPException(
                status_code=status.HTTP_401_UNAUTHORIZED,
                detail="Invalid token - no employee_id"
            )
        return int(employee_id)
    except JWTError as e:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail=f"Invalid token: {str(e)}"
        )

# اسم بديل للتوافق مع الملفات القديمة
def get_current_user(credentials: HTTPAuthorizationCredentials = Depends(security)):
    """استخراج employee_id من JWT Token - اسم بديل"""
    return get_current_employee(credentials)

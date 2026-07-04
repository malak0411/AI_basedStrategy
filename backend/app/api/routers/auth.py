from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy.orm import Session
from pydantic import BaseModel
from datetime import datetime, timedelta
from jose import jwt
import os
import random
import string
import smtplib
from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart
import bcrypt

from app.database import get_db
from app.models import Employee
from app.core.dependencies import get_current_employee

router = APIRouter(prefix="/api/auth", tags=["Authentication"])

# إعدادات
SECRET_KEY = os.getenv("SECRET_KEY", "your-secret-key")
ALGORITHM = os.getenv("ALGORITHM", "HS256")
ACCESS_TOKEN_EXPIRE_MINUTES = int(os.getenv("ACCESS_TOKEN_EXPIRE_MINUTES", "60"))

# Schemas
class LoginRequest(BaseModel):
    email: str
    password: str

class ForgotPasswordRequest(BaseModel):
    email: str
    phone_number: str

class ChangePasswordRequest(BaseModel):
    current_password: str
    new_password: str

# دوال مساعدة
def create_access_token(data: dict):
    to_encode = data.copy()
    expire = datetime.utcnow() + timedelta(minutes=ACCESS_TOKEN_EXPIRE_MINUTES)
    to_encode.update({"exp": expire, "iat": datetime.utcnow()})
    return jwt.encode(to_encode, SECRET_KEY, algorithm=ALGORITHM)

def verify_password(plain_password, hashed_password):
    """التحقق من كلمة المرور باستخدام bcrypt"""
    try:
        return bcrypt.checkpw(
            plain_password.encode('utf-8'),
            hashed_password.encode('utf-8') if isinstance(hashed_password, str) else hashed_password
        )
    except Exception:
        return False

def hash_password(password):
    """تشفير كلمة المرور باستخدام bcrypt"""
    return bcrypt.hashpw(password.encode('utf-8'), bcrypt.gensalt()).decode('utf-8')

def generate_random_password(length=10):
    return ''.join(random.choices(string.ascii_letters + string.digits, k=length))

# ============================================================
# تسجيل الدخول
# ============================================================
@router.post("/login")
async def login(request: LoginRequest, db: Session = Depends(get_db)):
    try:
        employee = db.query(Employee).filter(Employee.email == request.email).first()
        
        if not employee:
            raise HTTPException(status_code=401, detail="البريد الإلكتروني أو كلمة المرور غير صحيحة")
        
        # التحقق من كلمة المرور باستخدام bcrypt
        if not verify_password(request.password, employee.password):
            raise HTTPException(status_code=401, detail="البريد الإلكتروني أو كلمة المرور غير صحيحة")
        
        if not employee.is_active:
            raise HTTPException(status_code=403, detail="الحساب غير نشط")
        
        roles = [role.name for role in employee.roles] if employee.roles else []
        department_name = employee.department.name if employee.department else ""
        
        access_token = create_access_token(
            data={
                "sub": str(employee.employee_id),
                "email": employee.email,
                "employee_id": employee.employee_id
            }
        )
        
        employee.last_login = datetime.now()
        db.commit()
        
        return {
            "success": True,
            "data": {
                "access_token": access_token,
                "token_type": "bearer",
                "employee_id": employee.employee_id,
                "full_name": employee.full_name,
                "email": employee.email,
                "department_id": employee.department_id,
                "department_name": department_name,
                "roles": roles,
                "is_active": employee.is_active
            }
        }
    except HTTPException:
        raise
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")

# ============================================================
# معلومات المستخدم
# ============================================================
@router.get("/me")
async def get_current_user_info(
    db: Session = Depends(get_db),
    employee_id: int = Depends(get_current_employee)
):
    try:
        employee = db.query(Employee).filter(Employee.employee_id == employee_id).first()
        if not employee:
            raise HTTPException(status_code=404, detail="الموظف غير موجود")
        
        roles = [role.name for role in employee.roles] if employee.roles else []
        
        return {
            "success": True,
            "data": {
                "employee_id": employee.employee_id,
                "full_name": employee.full_name,
                "email": employee.email,
                "phone_number": employee.phone_number,
                "job_title": employee.job_title,
                "department_name": employee.department.name if employee.department else "",
                "roles": roles,
                "is_active": employee.is_active
            }
        }
    except HTTPException:
        raise
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")

# ============================================================
# نسيت كلمة المرور
# ============================================================
@router.post("/forgot-password")
async def forgot_password(request: ForgotPasswordRequest, db: Session = Depends(get_db)):
    try:
        employee = db.query(Employee).filter(
            Employee.email == request.email,
            Employee.phone_number == request.phone_number
        ).first()
        
        if not employee:
            return {"success": True, "message": "إذا كانت المعلومات صحيحة، ستتلقى كلمة المرور الجديدة"}
        
        new_password = generate_random_password()
        employee.password = hash_password(new_password)  # تشفير كلمة المرور
        db.commit()
        
        email_sent = False
        if all([os.getenv('SMTP_SERVER'), os.getenv('SMTP_USERNAME'), os.getenv('SMTP_PASSWORD')]):
            try:
                msg = MIMEMultipart('alternative')
                msg['Subject'] = "استعادة كلمة المرور"
                msg['From'] = os.getenv('FROM_EMAIL')
                msg['To'] = employee.email
                html_body = f"""
                <html><body dir="rtl">
                    <h2>استعادة كلمة المرور</h2>
                    <p>عزيزي/عزيزتي {employee.full_name}،</p>
                    <p>كلمة المرور الجديدة: <strong>{new_password}</strong></p>
                </body></html>
                """
                msg.attach(MIMEText(html_body, 'html', 'utf-8'))
                server = smtplib.SMTP(os.getenv('SMTP_SERVER'), int(os.getenv('SMTP_PORT', 587)), timeout=10)
                server.starttls()
                server.login(os.getenv('SMTP_USERNAME'), os.getenv('SMTP_PASSWORD'))
                server.sendmail(os.getenv('FROM_EMAIL'), employee.email, msg.as_string())
                server.quit()
                email_sent = True
            except Exception as e:
                print(f"❌ فشل إرسال البريد: {str(e)}")
        
        if email_sent:
            return {"success": True, "message": "تم إرسال كلمة المرور إلى بريدك الإلكتروني"}
        else:
            return {"success": True, "message": "تم إعادة تعيين كلمة المرور", "new_password": new_password}
            
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")

# ============================================================
# تغيير كلمة المرور
# ============================================================
@router.post("/change-password")
async def change_password(
    request: ChangePasswordRequest,
    db: Session = Depends(get_db),
    employee_id: int = Depends(get_current_employee)
):
    try:
        employee = db.query(Employee).filter(Employee.employee_id == employee_id).first()
        if not employee:
            raise HTTPException(status_code=404, detail="الموظف غير موجود")
        
        if not verify_password(request.current_password, employee.password):
            raise HTTPException(status_code=400, detail="كلمة المرور الحالية غير صحيحة")
        
        employee.password = hash_password(request.new_password)
        db.commit()
        
        return {"success": True, "message": "تم تغيير كلمة المرور بنجاح"}
        
    except HTTPException:
        raise
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")

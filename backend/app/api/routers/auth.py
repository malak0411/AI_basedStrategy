from fastapi import APIRouter, Depends, HTTPException, status, Request, BackgroundTasks
from fastapi.security import HTTPBearer
from sqlalchemy.orm import Session
from datetime import datetime, timedelta
from jose import JWTError, jwt
import bcrypt
import random
import string
import smtplib
from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart

from ...database import get_db
from ...models import Employee
from ...schemas import (
    LoginRequest, LoginResponse, UserInfoResponse,
    ForgotPasswordRequest, ForgotPasswordResponse,
    ChangePasswordRequest, ChangePasswordResponse
)
from ...core.security import verify_password, create_access_token
from ...core.dependencies import get_current_user
from ...config import settings

router = APIRouter(prefix="/api/auth", tags=["المصادقة"])
security = HTTPBearer()

# ================================================================
# Helper Functions
# ================================================================

def hash_password(password: str) -> str:
    """تشفير كلمة المرور باستخدام bcrypt"""
    return bcrypt.hashpw(password.encode('utf-8'), bcrypt.gensalt()).decode('utf-8')

def generate_random_password(length: int = 10) -> str:
    """توليد كلمة مرور عشوائية"""
    characters = string.ascii_letters + string.digits + "!@#$%^&*"
    return ''.join(random.choice(characters) for _ in range(length))

def send_password_reset_email(email: str, new_password: str):
    """
    إرسال كلمة المرور الجديدة عبر البريد الإلكتروني (SMTP)
    """
    try:
        smtp_host = settings.SMTP_HOST
        smtp_port = settings.SMTP_PORT
        sender_email = settings.SMTP_USER
        sender_password = settings.SMTP_PASSWORD
        sender_from = settings.SMTP_FROM

        if not sender_email or not sender_password:
            print(f"⚠️ SMTP غير مهيأ. كلمة المرور الجديدة: {new_password}")
            return

        # إنشاء الرسالة
        msg = MIMEMultipart()
        msg["From"] = sender_from
        msg["To"] = email
        msg["Subject"] = "استعادة كلمة المرور - نظام إدارة الاستراتيجية"

        body = f"""
        <html>
        <body style="font-family: Cairo, Arial, sans-serif; direction: rtl;">
            <div style="max-width: 600px; margin: auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 10px;">
                <h2 style="color: #0a2e5c;">وزارة النفط والمعادن</h2>
                <h3 style="color: #d4af37;">نظام إدارة الاستراتيجية</h3>
                <hr>
                <p>تم استعادة كلمة المرور الخاصة بحسابك بنجاح.</p>
                <p style="font-size: 18px; background: #f5f7fa; padding: 10px; border-radius: 5px;">
                    <strong>كلمة المرور الجديدة:</strong> 
                    <span style="color: #d4af37; font-size: 24px; font-weight: bold;">{new_password}</span>
                </p>
                <p style="color: #4a5568; font-size: 14px;">
                    يرجى تسجيل الدخول باستخدام كلمة المرور الجديدة وتغييرها فوراً لأسباب أمنية.
                </p>
                <hr>
                <p style="color: #a0aec0; font-size: 12px;">
                    هذا بريد آلي، يرجى عدم الرد عليه.
                </p>
                <p style="color: #a0aec0; font-size: 12px;">
                    © 2026 وزارة النفط والمعادن - جميع الحقوق محفوظة
                </p>
            </div>
        </body>
        </html>
        """

        msg.attach(MIMEText(body, "html"))

        # إرسال البريد
        server = smtplib.SMTP(smtp_host, smtp_port)
        server.starttls()
        server.login(sender_email, sender_password)
        server.sendmail(sender_from, email, msg.as_string())
        server.quit()

        print(f"✅ تم إرسال كلمة المرور الجديدة إلى: {email}")

    except Exception as e:
        print(f"❌ فشل إرسال البريد إلى {email}: {e}")
        print(f"🔑 كلمة المرور الجديدة (للرجوع): {new_password}")

# ================================================================
# 1. Login
# ================================================================

@router.post("/login", response_model=LoginResponse)
async def login(
    login_data: LoginRequest,
    db: Session = Depends(get_db)
):
    employee = db.query(Employee).filter(Employee.email == login_data.email).first()
    
    if not employee or not verify_password(login_data.password, employee.password):
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="البريد الإلكتروني أو كلمة المرور غير صحيحة"
        )
    
    if not employee.is_active:
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="الحساب غير نشط، يرجى مراجعة المسؤول"
        )
    
    access_token = create_access_token(data={"sub": str(employee.employee_id), "email": employee.email})
    roles = [role.name for role in employee.roles]
    
    return LoginResponse(
        access_token=access_token,
        employee_id=employee.employee_id,
        full_name=employee.full_name,
        email=employee.email,
        department_id=employee.department_id,
        department_name=employee.department.name if employee.department else "",
        roles=roles,
        is_active=employee.is_active
    )

# ================================================================
# 2. Get current user info
# ================================================================

@router.get("/me", response_model=UserInfoResponse)
async def get_me(
    current_user: Employee = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    roles = [role.name for role in current_user.roles]
    permissions = []
    for role in current_user.roles:
        for perm in role.permissions:
            permissions.append(perm.name)
    permissions = list(set(permissions))
    
    return UserInfoResponse(
        employee_id=current_user.employee_id,
        full_name=current_user.full_name,
        email=current_user.email,
        job_title=current_user.job_title or "",
        department_id=current_user.department_id,
        department_name=current_user.department.name if current_user.department else "",
        roles=roles,
        permissions=permissions
    )

# ================================================================
# 3. Logout
# ================================================================

@router.post("/logout")
async def logout(
    current_user: Employee = Depends(get_current_user)
):
    return {"message": "تم تسجيل الخروج بنجاح"}

# ================================================================
# 4. Forgot Password
# ================================================================

@router.post("/forgot-password", response_model=ForgotPasswordResponse)
async def forgot_password(
    request: ForgotPasswordRequest,
    background_tasks: BackgroundTasks,
    db: Session = Depends(get_db)
):
    try:
        employee = db.query(Employee).filter(
            Employee.email == request.email,
            Employee.phone_number == request.phone_number
        ).first()
        
        if not employee:
            raise HTTPException(
                status_code=status.HTTP_404_NOT_FOUND,
                detail="لا يوجد مستخدم بهذا البريد الإلكتروني ورقم الهاتف"
            )
        
        new_password = generate_random_password()
        hashed_password = hash_password(new_password)
        
        employee.password = hashed_password
        db.commit()
        
        background_tasks.add_task(send_password_reset_email, request.email, new_password)
        
        return ForgotPasswordResponse(
            message="تم إرسال كلمة المرور الجديدة إلى بريدك الإلكتروني",
            email_sent=True
        )
        
    except HTTPException:
        raise
    except Exception as e:
        print(f"❌ Error in forgot_password: {e}")
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail=f"حدث خطأ داخلي: {str(e)}"
        )

# ================================================================
# 5. Change Password
# ================================================================

@router.post("/change-password", response_model=ChangePasswordResponse)
async def change_password(
    request: ChangePasswordRequest,
    db: Session = Depends(get_db)
):
    try:
        employee = db.query(Employee).filter(Employee.email == request.email).first()
        
        if not employee:
            raise HTTPException(
                status_code=status.HTTP_404_NOT_FOUND,
                detail="المستخدم غير موجود"
            )
        
        if not verify_password(request.old_password, employee.password):
            raise HTTPException(
                status_code=status.HTTP_401_UNAUTHORIZED,
                detail="كلمة المرور القديمة غير صحيحة"
            )
        
        hashed_new = hash_password(request.new_password)
        employee.password = hashed_new
        db.commit()
        
        return ChangePasswordResponse(
            message="تم تغيير كلمة المرور بنجاح"
        )
        
    except HTTPException:
        raise
    except Exception as e:
        print(f"❌ Error in change_password: {e}")
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail=f"حدث خطأ داخلي: {str(e)}"
        )

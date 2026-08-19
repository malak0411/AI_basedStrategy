from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session
from pydantic import BaseModel
from datetime import datetime, timedelta
from jose import jwt
import os
import secrets
import string
import smtplib
from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart
import bcrypt
from dotenv import load_dotenv

from app.database import get_db
from app.models import Employee
from app.core.dependencies import get_current_employee
from app.core.audit import log_audit

load_dotenv()

router = APIRouter(prefix="/api/auth", tags=["Authentication"])

SECRET_KEY = os.getenv("SECRET_KEY", "your-secret-key")
ALGORITHM = os.getenv("ALGORITHM", "HS256")
ACCESS_TOKEN_EXPIRE_MINUTES = int(
    os.getenv("ACCESS_TOKEN_EXPIRE_MINUTES", "60")
)


class LoginRequest(BaseModel):
    email: str
    password: str


class ForgotPasswordRequest(BaseModel):
    email: str
    phone_number: str


class ChangePasswordRequest(BaseModel):
    current_password: str
    new_password: str


def create_access_token(data: dict):
    to_encode = data.copy()
    expire = datetime.utcnow() + timedelta(
        minutes=ACCESS_TOKEN_EXPIRE_MINUTES
    )
    to_encode.update({
        "exp": expire,
        "iat": datetime.utcnow()
    })
    return jwt.encode(
        to_encode,
        SECRET_KEY,
        algorithm=ALGORITHM
    )


def verify_password(plain_password, hashed_password):
    try:
        return bcrypt.checkpw(
            plain_password.encode("utf-8"),
            hashed_password.encode("utf-8")
            if isinstance(hashed_password, str)
            else hashed_password
        )
    except Exception:
        return False


def hash_password(password):
    return bcrypt.hashpw(
        password.encode("utf-8"),
        bcrypt.gensalt()
    ).decode("utf-8")


def generate_random_password(length=12):
    characters = string.ascii_letters + string.digits + "!@#$%&*"
    return "".join(
        secrets.choice(characters)
        for _ in range(length)
    )


def send_password_email(
    employee_email: str,
    employee_name: str,
    new_password: str
):
    smtp_host = os.getenv("SMTP_HOST")
    smtp_port = int(os.getenv("SMTP_PORT", "587"))
    smtp_user = os.getenv("SMTP_USER")
    smtp_password = os.getenv("SMTP_PASSWORD")
    smtp_from = os.getenv("SMTP_FROM")

    missing_settings = []

    if not smtp_host:
        missing_settings.append("SMTP_HOST")

    if not smtp_user:
        missing_settings.append("SMTP_USER")

    if not smtp_password:
        missing_settings.append("SMTP_PASSWORD")

    if not smtp_from:
        missing_settings.append("SMTP_FROM")

    if missing_settings:
        raise Exception(
            "إعدادات البريد ناقصة: "
            + ", ".join(missing_settings)
        )

    msg = MIMEMultipart("alternative")

    msg["Subject"] = "إعادة تعيين كلمة المرور - نظام إدارة الاستراتيجية"
    msg["From"] = smtp_from
    msg["To"] = employee_email

    html_body = f"""
    <!DOCTYPE html>
    <html lang="ar" dir="rtl">
    <head>
        <meta charset="UTF-8">
        <title>إعادة تعيين كلمة المرور</title>
    </head>

    <body style="
        font-family: Arial, sans-serif;
        background-color: #f5f5f5;
        padding: 30px;
    ">

        <div style="
            max-width: 600px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
        ">

            <h2 style="text-align:center;">
                إعادة تعيين كلمة المرور
            </h2>

            <p>
                مرحبًا {employee_name}،
            </p>

            <p>
                تم طلب إعادة تعيين كلمة المرور الخاصة بحسابك
                في نظام إدارة الاستراتيجية.
            </p>

            <p>
                كلمة المرور المؤقتة الخاصة بك هي:
            </p>

            <div style="
                background-color: #f1f1f1;
                padding: 15px;
                text-align: center;
                font-size: 22px;
                font-weight: bold;
                letter-spacing: 2px;
                margin: 20px 0;
            ">
                {new_password}
            </div>

            <p>
                استخدم كلمة المرور هذه لتسجيل الدخول إلى النظام.
            </p>

            <p>
                بعد تسجيل الدخول، يرجى تغيير كلمة المرور
                من صفحة <strong>تغيير كلمة المرور</strong>.
            </p>

            <hr>

            <p style="font-size: 13px; color: #777;">
                إذا لم تكن أنت من طلب إعادة تعيين كلمة المرور،
                يرجى التواصل مع مسؤول النظام.
            </p>

        </div>

    </body>
    </html>
    """

    msg.attach(
        MIMEText(
            html_body,
            "html",
            "utf-8"
        )
    )

    server = None

    try:
        server = smtplib.SMTP(
            smtp_host,
            smtp_port,
            timeout=20
        )

        server.ehlo()
        server.starttls()
        server.ehlo()

        server.login(
            smtp_user,
            smtp_password
        )

        server.sendmail(
            smtp_from,
            employee_email,
            msg.as_string()
        )

    finally:
        if server:
            try:
                server.quit()
            except Exception:
                pass


@router.post("/login")
async def login(
    request: LoginRequest,
    db: Session = Depends(get_db)
):
    try:
        employee = db.query(Employee).filter(
            Employee.email == request.email
        ).first()

        if not employee:
            raise HTTPException(
                status_code=401,
                detail="البريد الإلكتروني أو كلمة المرور غير صحيحة"
            )

        if not verify_password(
            request.password,
            employee.password
        ):
            raise HTTPException(
                status_code=401,
                detail="البريد الإلكتروني أو كلمة المرور غير صحيحة"
            )

        if not employee.is_active:
            raise HTTPException(
                status_code=403,
                detail="الحساب غير نشط"
            )

        roles = [
            role.name
            for role in employee.roles
        ] if employee.roles else []

        department_name = (
            employee.department.name
            if employee.department
            else ""
        )

        access_token = create_access_token(
            data={
                "sub": str(employee.employee_id),
                "email": employee.email,
                "employee_id": employee.employee_id
            }
        )

        employee.last_login = datetime.now()
        db.commit()

        log_audit(
            employee_id=employee.employee_id,
            action="LOGIN",
            table_name="employees",
            record_id=employee.employee_id,
            new_data={
                "email": employee.email,
                "full_name": employee.full_name
            }
        )

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
        raise HTTPException(
            status_code=500,
            detail=f"خطأ: {str(e)}"
        )


@router.get("/me")
async def get_current_user_info(
    db: Session = Depends(get_db),
    employee_id: int = Depends(get_current_employee)
):
    try:
        employee = db.query(Employee).filter(
            Employee.employee_id == employee_id
        ).first()

        if not employee:
            raise HTTPException(
                status_code=404,
                detail="الموظف غير موجود"
            )

        roles = [
            role.name
            for role in employee.roles
        ] if employee.roles else []

        return {
            "success": True,
            "data": {
                "employee_id": employee.employee_id,
                "full_name": employee.full_name,
                "email": employee.email,
                "phone_number": employee.phone_number,
                "job_title": employee.job_title,
                "department_name": (
                    employee.department.name
                    if employee.department
                    else ""
                ),
                "roles": roles,
                "is_active": employee.is_active
            }
        }

    except HTTPException:
        raise

    except Exception as e:
        raise HTTPException(
            status_code=500,
            detail=f"خطأ: {str(e)}"
        )


@router.post("/forgot-password")
async def forgot_password(
    request: ForgotPasswordRequest,
    db: Session = Depends(get_db)
):
    try:
        employee = db.query(Employee).filter(
            Employee.email == request.email,
            Employee.phone_number == request.phone_number
        ).first()

        if not employee:
            return {
                "success": True,
                "data": {
                    "message":
                        "إذا كانت المعلومات صحيحة، "
                        "ستتلقى كلمة المرور الجديدة عبر البريد الإلكتروني"
                }
            }

        new_password = generate_random_password(12)

        employee.password = hash_password(new_password)

        try:
            send_password_email(
                employee_email=employee.email,
                employee_name=employee.full_name,
                new_password=new_password
            )

        except Exception as email_error:
            db.rollback()

            print(f"SMTP ERROR: {str(email_error)}")

            raise HTTPException(
                status_code=500,
                detail=(
                    "تعذر إرسال البريد الإلكتروني. "
                    "تحقق من إعدادات SMTP."
                )
            )

        db.commit()

        try:
            log_audit(
                employee_id=employee.employee_id,
                action="PASSWORD_RESET",
                table_name="employees",
                record_id=employee.employee_id,
                new_data={
                    "email": employee.email,
                    "method": "EMAIL"
                }
            )
        except Exception as audit_error:
            print(f"AUDIT LOG ERROR: {str(audit_error)}")

        return {
            "success": True,
            "data": {
                "message":
                    "تم إرسال كلمة المرور الجديدة "
                    "إلى بريدك الإلكتروني"
            }
        }

    except HTTPException:
        raise

    except Exception as e:
        db.rollback()

        raise HTTPException(
            status_code=500,
            detail=f"خطأ: {str(e)}"
        )


@router.post("/change-password")
async def change_password(
    request: ChangePasswordRequest,
    db: Session = Depends(get_db),
    employee_id: int = Depends(get_current_employee)
):
    try:
        employee = db.query(Employee).filter(
            Employee.employee_id == employee_id
        ).first()

        if not employee:
            raise HTTPException(
                status_code=404,
                detail="الموظف غير موجود"
            )

        if not verify_password(
            request.current_password,
            employee.password
        ):
            raise HTTPException(
                status_code=400,
                detail="كلمة المرور الحالية غير صحيحة"
            )

        employee.password = hash_password(
            request.new_password
        )

        db.commit()

        return {
            "success": True,
            "message": "تم تغيير كلمة المرور بنجاح"
        }

    except HTTPException:
        raise

    except Exception as e:
        db.rollback()

        raise HTTPException(
            status_code=500,
            detail=f"خطأ: {str(e)}"
        )

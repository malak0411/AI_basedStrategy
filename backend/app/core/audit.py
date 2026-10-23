import json
from datetime import datetime
from typing import Optional

from fastapi import Request

from app.database import SessionLocal
from app.models import AuditLog


def _extract_request_info(request: Optional[Request]):
    if request is None:
        return "127.0.0.1", "System"

    forwarded = request.headers.get("x-forwarded-for")
    if forwarded:
        ip = forwarded.split(",")[0].strip()
    elif request.client and request.client.host:
        ip = request.client.host
    else:
        ip = "127.0.0.1"

    user_agent = request.headers.get("user-agent", "Unknown")
    return ip, user_agent


def log_audit(
    employee_id: Optional[int] = None,
    action: str = "",
    table_name: str = "",
    record_id: Optional[int] = None,
    old_data: Optional[dict] = None,
    new_data: Optional[dict] = None,
    request: Optional[Request] = None,
    ip_address: Optional[str] = None,
    user_agent: Optional[str] = None,
    success: bool = True,
    error_message: Optional[str] = None,
):
    db = SessionLocal()
    try:
        auto_ip, auto_ua = _extract_request_info(request)

        payload = dict(new_data) if new_data else {}
        payload["success"] = success
        if error_message:
            payload["error"] = error_message

        log = AuditLog(
            employee_id=employee_id,
            action=action,
            table_name=table_name,
            record_id=record_id,
            old_data=json.dumps(old_data, ensure_ascii=False) if old_data else None,
            new_data=json.dumps(payload, ensure_ascii=False),
            ip_address=ip_address or auto_ip,
            user_agent=user_agent or auto_ua,
            created_at=datetime.now(),
        )
        db.add(log)
        db.commit()
    except Exception as e:
        print(f" خطأ في تسجيل التدقيق: {str(e)}")
        db.rollback()
    finally:
        db.close()

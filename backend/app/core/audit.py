"""
نظام تسجيل التدقيق التلقائي
"""
from app.database import SessionLocal
from app.models import AuditLog, Employee
from datetime import datetime

def log_audit(employee_id: int, action: str, table_name: str, record_id: int = None, old_data: dict = None, new_data: dict = None, ip_address: str = None):
    """
    تسجيل إجراء في سجل التدقيق
    
    Args:
        employee_id: معرف الموظف
        action: الإجراء (CREATE, UPDATE, DELETE, LOGIN, etc.)
        table_name: اسم الجدول
        record_id: معرف السجل
        old_data: البيانات القديمة (للتحديث)
        new_data: البيانات الجديدة
        ip_address: عنوان IP
    """
    db = SessionLocal()
    try:
        import json
        log = AuditLog(
            employee_id=employee_id,
            action=action,
            table_name=table_name,
            record_id=record_id,
            old_data=json.dumps(old_data) if old_data else None,
            new_data=json.dumps(new_data) if new_data else None,
            ip_address=ip_address or "127.0.0.1",
            user_agent="System",
            created_at=datetime.now()
        )
        db.add(log)
        db.commit()
    except Exception as e:
        print(f"⚠️ خطأ في تسجيل التدقيق: {str(e)}")
        db.rollback()
    finally:
        db.close()

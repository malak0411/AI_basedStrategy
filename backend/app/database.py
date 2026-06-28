from sqlalchemy import create_engine
from sqlalchemy.ext.declarative import declarative_base
from sqlalchemy.orm import sessionmaker
from .config import settings

# إنشاء محرك قاعدة البيانات
engine = create_engine(
    settings.DATABASE_URL,
    pool_pre_ping=True,
    echo=settings.DEBUG
)

# مصنع الجلسات
SessionLocal = sessionmaker(autocommit=False, autoflush=False, bind=engine)

# القاعدة الأساسية للنماذج
Base = declarative_base()

def get_db():
    """دالة للحصول على جلسة قاعدة البيانات (تُستخدم في الـ APIs)"""
    db = SessionLocal()
    try:
        yield db
    finally:
        db.close()

import bcrypt
from datetime import datetime, timedelta
from jose import JWTError, jwt
from ..config import settings

# ================================================================
# تشفير كلمات المرور
# ================================================================

def hash_password(password: str) -> str:
    """تشفير كلمة المرور باستخدام bcrypt"""
    return bcrypt.hashpw(password.encode('utf-8'), bcrypt.gensalt()).decode('utf-8')

def verify_password(plain_password: str, hashed_password: str) -> bool:
    """التحقق من كلمة المرور"""
    return bcrypt.checkpw(plain_password.encode('utf-8'), hashed_password.encode('utf-8'))

# ================================================================
# واجهة موحدة لتشفير كلمة المرور (للاستخدام في جميع أنحاء التطبيق)
# ================================================================

get_password_hash = hash_password  # <-- أضف هذا السطر

# ================================================================
# JWT
# ================================================================

def create_access_token(data: dict, expires_delta: timedelta = None) -> str:
    to_encode = data.copy()
    if expires_delta:
        expire = datetime.utcnow() + expires_delta
    else:
        expire = datetime.utcnow() + timedelta(minutes=settings.ACCESS_TOKEN_EXPIRE_MINUTES)
    to_encode.update({"exp": expire, "iat": datetime.utcnow()})
    return jwt.encode(to_encode, settings.SECRET_KEY, algorithm=settings.ALGORITHM)

def decode_access_token(token: str) -> dict:
    try:
        payload = jwt.decode(token, settings.SECRET_KEY, algorithms=[settings.ALGORITHM], options={"verify_exp": True})
        return payload
    except JWTError:
        return None

# ================================================================
# أدوات أمنية إضافية
# ================================================================

def hash_ip_address(ip: str) -> str:
    import hashlib
    if not ip:
        return "unknown"
    return hashlib.sha256(f"{ip}{settings.SECRET_KEY}".encode()).hexdigest()

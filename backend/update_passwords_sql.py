
import pymysql
import bcrypt
from app.config import settings

def hash_password(password: str) -> str:
    return bcrypt.hashpw(password.encode('utf-8'), bcrypt.gensalt()).decode('utf-8')

def update_all_passwords(default_password: str = "password123"):
  
    connection = pymysql.connect(
        host=settings.DB_HOST,
        user=settings.DB_USER,
        password=settings.DB_PASSWORD,
        database=settings.DB_NAME,
        charset='utf8mb4'
    )
    try:
        with connection.cursor() as cursor:
            hashed = hash_password(default_password)
            sql = "UPDATE employees SET password = %s"
            cursor.execute(sql, (hashed,))
            affected = cursor.rowcount
            connection.commit()
            print(f" تم تحديث {affected} موظف بكلمة المرور: '{default_password}'")
            print(" يمكنك الآن تسجيل الدخول باستخدام أي بريد إلكتروني وكلمة المرور المذكورة أعلاه.")
    except Exception as e:
        print(f" حدث خطأ: {e}")
    finally:
        connection.close()

if __name__ == "__main__":
    update_all_passwords("password123")


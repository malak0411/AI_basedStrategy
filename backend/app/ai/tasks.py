"""
مهام AI - نسخة SQL مباشرة (بدون Celery)
"""
from app.ai.services.gemini_client import gemini_client
from app.database import SessionLocal
from app.models import AiJob
from datetime import datetime
import json


def call_gemini_sync(job_id: int, prompt: str, system_instruction: str = None):
    """
    استدعاء Gemini API بشكل متزامن وتحديث قاعدة البيانات مباشرة
    
    Args:
        job_id: معرف المهمة في جدول ai_jobs
        prompt: النص المرسل لـ Gemini
        system_instruction: تعليمات النظام (اختياري)
    
    Returns:
        dict: نتيجة الاستدعاء
    """
    db = SessionLocal()
    
    try:
        # تحديث الحالة إلى "قيد التنفيذ"
        job = db.query(AiJob).filter(AiJob.job_id == job_id).first()
        if job:
            job.status = "running"
            job.updated_at = datetime.now()
            db.commit()
        
        # استدعاء Gemini
        result_text = gemini_client.generate(prompt, system_instruction)
        
        # تحديث الحالة إلى "مكتمل"
        if job:
            job.status = "completed"
            # محاولة تحويل النتيجة إلى JSON إذا أمكن
            try:
                job.result_json = json.loads(result_text) if isinstance(result_text, str) else result_text
            except:
                job.result_json = {"text": result_text}
            job.updated_at = datetime.now()
            db.commit()
        
        return {
            "success": True,
            "job_id": job_id,
            "status": "completed",
            "result": result_text
        }
        
    except Exception as e:
        # تحديث الحالة إلى "فشل"
        if job:
            job.status = "failed"
            job.result_json = {"error": str(e)}
            job.updated_at = datetime.now()
            db.commit()
        
        return {
            "success": False,
            "job_id": job_id,
            "status": "failed",
            "error": str(e)
        }
    
    finally:
        db.close()


def train_delay_model_sync():
    """تدريب نموذج التأخير بشكل متزامن"""
    try:
        from app.ai.models.delay_predictor import DelayPredictor
        predictor = DelayPredictor()
        metrics = predictor.train(force=True)
        return {"success": True, "metrics": metrics}
    except Exception as e:
        return {"success": False, "error": str(e)}

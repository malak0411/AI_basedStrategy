"""
AI Router - جميع نقاط نهاية الذكاء الاصطناعي
نسخة SQL مباشرة (بدون Celery)
"""
from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session
from datetime import datetime

from app.database import get_db
from app.models import OperationalTask, AIPrediction
from app.ai.services.prediction_service import prediction_service
from app.ai.services.gemini_client import gemini_client
from app.ai.models.delay_predictor import DelayPredictor
from app.ai.models.recommender import Recommender
from app.ai.tasks import call_gemini_sync, train_delay_model_sync

router = APIRouter(prefix="/api/ai", tags=["AI"])

# ============================================================
# التنبؤ بتأخير المهام (AI-3)
# ============================================================

@router.get("/predict-delay/{task_id}")
async def predict_task_delay(task_id: int):
    """تنبؤ بتأخير مهمة واحدة"""
    try:
        result = prediction_service.predict_single(task_id, save=True)
        return {"success": True, "data": result}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))


@router.get("/predict-all-delays")
async def predict_all_delays():
    """تنبؤ بجميع المهام النشطة"""
    try:
        results = prediction_service.predict_all_active(save=True)
        return {"success": True, "data": results, "count": len(results)}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))


@router.get("/dashboard")
async def ai_dashboard(db: Session = Depends(get_db)):
    """لوحة معلومات AI"""
    try:
        stats = prediction_service.get_dashboard_stats()
        
        # أعلى 5 مهام خطورة
        high_risk = db.query(AIPrediction).filter(
            AIPrediction.prediction_type == 'delay'
        ).order_by(AIPrediction.probability.desc()).limit(5).all()
        
        risky_tasks = []
        for p in high_risk:
            task = db.query(OperationalTask).filter(
                OperationalTask.task_id == p.task_id
            ).first()
            risky_tasks.append({
    "task_id": int(p.task_id),
    "task_name": str(task.title) if task else "غير معروف",
    "delay_probability": round(float(p.probability or 0), 1),
    "risk_level": "High" if (float(p.probability or 0)) > 0.7 else "Medium"
})

        
        return {"success": True, "data": {**stats, "risky_tasks": risky_tasks}}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))


# ============================================================
# تدريب النماذج
# ============================================================

@router.post("/train-delay")
async def train_delay_model():
    """تدريب نموذج التنبؤ بالتأخير (مباشر)"""
    result = train_delay_model_sync()
    return result


@router.post("/train-recommender")
async def train_recommender_model():
    """تدريب نموذج التوصيات"""
    try:
        rec = Recommender()
        metrics = rec.train(force=True)
        return {"success": True, "data": metrics}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))


# ============================================================
# التوصيات (AI-4)
# ============================================================

@router.get("/recommendations/{task_id}")
async def get_recommendations(task_id: int):
    """توصيات لمهمة محددة"""
    try:
        rec = Recommender()
        result = rec.recommend(task_id=task_id)
        return {"success": True, "data": result}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))


# ============================================================
# Gemini
# ============================================================

@router.post("/gemini/test")
async def test_gemini():
    """اختبار اتصال Gemini"""
    try:
        response = gemini_client.generate(
            prompt="اكتب رسالة ترحيبية قصيرة لنظام إدارة استراتيجية حكومي بالعربية",
            model_type="flash"
        )
        return {"success": True, "message": response}
    except Exception as e:
        return {"success": False, "error": str(e)}


# ============================================================
# حالة النماذج
# ============================================================

@router.get("/models/status")
async def models_status():
    """حالة جميع النماذج"""
    predictor = DelayPredictor()
    recommender = Recommender()
    
    return {
        "success": True,
        "data": {
            "delay_predictor": {
                "trained": predictor.load_model(),
                "version": predictor.model_version
            },
            "recommender": {
                "trained": recommender.load_model(),
                "version": recommender.model_version
            },
            "gemini": {
                "available": True,
                "model": gemini_client.MODEL_PRO
            }
        }
    }

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
from app.ai.services.strategic_planner import StrategicPlanner
from app.ai.services.scheduler import ai_scheduler


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
        results = prediction_service.predict_all_active(save=False)
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
# AI Strategic Task Generation
# ============================================================

@router.post("/strategic/generate-major-tasks/{initiative_id}")
async def generate_major_tasks(
    initiative_id: int,
    request: dict = None,
    db: Session = Depends(get_db)
):
    """توليد المهام الرئيسية من مبادرة باستخدام Gemini"""
    try:
        planner = StrategicPlanner()
        
        # جمع السياق
        context = planner.get_initiative_context(initiative_id)
        
        # إنشاء Job
        job_id = planner.create_job(initiative_id)
        
        # بناء الـ Prompt
        user_instructions = request.get('instructions', '') if request else ''
        prompt = planner.build_prompt(context, user_instructions)
        
        # استدعاء Gemini
        try:
            response_text = gemini_client.generate(prompt, model_type="pro")
            result = planner.parse_gemini_response(response_text)
            
            # تحديث job
            planner.update_job(job_id, "review", {
                "initiative_id": initiative_id,
                "major_tasks": result.get("major_tasks", []),
                "raw_response": response_text
            })
            
            return {
                "success": True,
                "data": {
                    "job_id": job_id,
                    "status": "review",
                    "major_tasks": result.get("major_tasks", [])
                }
            }
        except Exception as e:
            planner.update_job(job_id, "failed", {"error": str(e)})
            raise e
        finally:
            planner.close()
            
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")


@router.get("/jobs/{job_id}")
async def get_job_status(job_id: int, db: Session = Depends(get_db)):
    """حالة مهمة AI"""
    job = db.query(AiJob).filter(AiJob.job_id == job_id).first()
    if not job:
        raise HTTPException(status_code=404, detail="المهمة غير موجودة")
    
    return {
        "success": True,
        "data": {
            "job_id": job.job_id,
            "status": job.status,
            "result": json.loads(job.result_json) if job.result_json else None
        }
    }


@router.post("/strategic/edit-task-plan/{job_id}")
async def edit_task_plan(job_id: int, request: dict, db: Session = Depends(get_db)):
    """تعديل خطة المهام باستخدام Prompt"""
    try:
        job = db.query(AiJob).filter(AiJob.job_id == job_id).first()
        if not job:
            raise HTTPException(status_code=404, detail="غير موجود")
        
        current_result = json.loads(job.result_json) if job.result_json else {}
        edit_instruction = request.get('instruction', '')
        
        # بناء prompt للتعديل
        prompt = f"""
        لديك خطة المهام التالية:
        {json.dumps(current_result.get('major_tasks', []), ensure_ascii=False, indent=2)}
        
        التعليمات: {edit_instruction}
        
        أعد JSON معدل بنفس الشكل.
        """
        
        response_text = gemini_client.generate(prompt, model_type="pro")
        new_result = StrategicPlanner().parse_gemini_response(response_text)
        
        # تحديث job
        job.result_json = json.dumps({
            **current_result,
            "major_tasks": new_result.get("major_tasks", current_result.get("major_tasks", []))
        })
        job.updated_at = datetime.now()
        db.commit()
        
        return {
            "success": True,
            "data": {
                "job_id": job_id,
                "major_tasks": new_result.get("major_tasks", [])
            }
        }
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))


@router.post("/strategic/approve-task-plan/{job_id}")
async def approve_task_plan(job_id: int, db: Session = Depends(get_db)):
    """اعتماد وحفظ المهام الرئيسية"""
    try:
        job = db.query(AiJob).filter(AiJob.job_id == job_id).first()
        if not job:
            raise HTTPException(status_code=404, detail="غير موجود")
        
        result = json.loads(job.result_json) if job.result_json else {}
        initiative_id = result.get('initiative_id')
        tasks = result.get('major_tasks', [])
        
        if not tasks:
            raise HTTPException(status_code=400, detail="لا توجد مهام للحفظ")
        
        planner = StrategicPlanner()
        saved = planner.save_major_tasks(initiative_id, tasks, job.created_by)
        planner.update_job(job_id, "completed", {"saved_tasks": saved})
        planner.close()
        
        return {
            "success": True,
            "data": {
                "message": f"تم حفظ {len(saved)} مهمة رئيسية",
                "tasks": saved
            }
        }
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

# ============================================================
# الجدولة التلقائية
# ============================================================

@router.post("/scheduler/start")
async def start_scheduler():
    """بدء الجدولة التلقائية"""
    try:
        ai_scheduler.start()
        return {"success": True, "message": "تم بدء الجدولة التلقائية - تنبؤ يومي 6:00 + تدريب أسبوعي"}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@router.post("/scheduler/stop")
async def stop_scheduler():
    """إيقاف الجدولة"""
    try:
        ai_scheduler.stop()
        return {"success": True, "message": "تم إيقاف الجدولة"}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@router.get("/scheduler/status")
async def scheduler_status():
    """حالة الجدولة"""
    return {
        "success": True,
        "data": {
            "is_running": ai_scheduler.is_running,
            "next_prediction": "06:00 يومياً",
            "next_training": "الإثنين 02:00 أسبوعياً"
        }
    }

@router.post("/predict-now")
async def predict_now():
    """تشغيل التنبؤ فوراً"""
    try:
        results = ai_scheduler.run_daily_prediction()
        return {
            "success": True,
            "data": {
                "total_tasks": len(results) if results else 0,
                "high_risk": sum(1 for r in results if r.get('risk_level') == 'High') if results else 0,
                "message": "تم التنبؤ بنجاح"
            }
        }
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@router.post("/train-now")
async def train_now():
    """تشغيل التدريب فوراً"""
    try:
        metrics = ai_scheduler.run_weekly_training()
        return {"success": True, "data": metrics}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

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

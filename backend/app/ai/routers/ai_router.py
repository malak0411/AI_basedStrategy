
import json
import threading
from datetime import datetime
from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session

from app.database import get_db, SessionLocal
from app.models import OperationalTask, AIPrediction, AiJob
from app.ai.services.prediction_service import prediction_service
from app.ai.services.ollama_client import ollama_client
from app.ai.models.delay_predictor import DelayPredictor
from app.ai.models.recommender import Recommender
from app.ai.services.strategic_planner import StrategicPlanner
from app.ai.services.scheduler import ai_scheduler

router = APIRouter(prefix="/api/ai", tags=["AI"])


def generate_in_background(job_id: int, initiative_id: int, user_instructions: str = ""):
    try:
        print(f"Job #{job_id}: Starting background task generation")
        planner = StrategicPlanner()
        context = planner.get_initiative_context(initiative_id)
        prompt = planner.build_compact_prompt(context, user_instructions)
        planner.close()

        response_text = ollama_client.generate(
            prompt,
            system_instruction="You are a government strategic planning expert. Return ONLY valid JSON array."
        )

        planner2 = StrategicPlanner()
        raw_tasks = planner2.parse_response(response_text)
        if not raw_tasks:
            raise ValueError("No tasks generated")
        processed_tasks = planner2.post_process_tasks(raw_tasks, context)

        planner2.update_job(job_id, "review", {
            "initiative_id": initiative_id,
            "major_tasks": processed_tasks
        })
        planner2.close()
        print(f"Job #{job_id}: Completed - {len(processed_tasks)} tasks")

    except Exception as e:
        print(f"Job #{job_id}: Failed - {str(e)}")
        import traceback
        traceback.print_exc()
        db = SessionLocal()
        try:
            job = db.query(AiJob).filter(AiJob.job_id == job_id).first()
            if job:
                job.status = "failed"
                job.result_json = json.dumps({"error": str(e)}, ensure_ascii=False)
                job.updated_at = datetime.now()
                db.commit()
        except Exception:
            pass
        finally:
            db.close()


@router.get("/predict-delay/{task_id}")
async def predict_task_delay(task_id: int):
    try:
        result = prediction_service.predict_single(task_id, save=True)
        return {"success": True, "data": result}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))


@router.get("/predict-all-delays")
async def predict_all_delays():
    try:
        results = prediction_service.predict_all_active(save=False)
        return {"success": True, "data": results, "count": len(results)}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))


@router.get("/dashboard")
async def ai_dashboard(db: Session = Depends(get_db)):
    try:
        stats = prediction_service.get_dashboard_stats()
        high_risk = db.query(AIPrediction).filter(
            AIPrediction.prediction_type == 'delay'
        ).order_by(AIPrediction.probability.desc()).limit(5).all()
        risky_tasks = []
        for p in high_risk:
            task = db.query(OperationalTask).filter(OperationalTask.task_id == p.task_id).first()
            prob = float(p.probability or 0)
            prob_pct = round(prob * 100, 1) if prob <= 1 else round(prob, 1)
            risky_tasks.append({
                "task_id": int(p.task_id),
                "task_name": str(task.title) if task else "Unknown",
                "delay_probability": prob_pct,
                "risk_level": "High" if prob > 0.7 else "Medium"
            })
        return {"success": True, "data": {**stats, "risky_tasks": risky_tasks}}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))


@router.post("/train-delay")
async def train_delay_model():
    return train_delay_model_sync()


@router.post("/train-recommender")
async def train_recommender_model():
    try:
        rec = Recommender()
        metrics = rec.train(force=True)
        return {"success": True, "data": metrics}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))


@router.get("/recommendations/{task_id}")
async def get_recommendations(task_id: int):
    try:
        rec = Recommender()
        result = rec.recommend(task_id=task_id)
        return {"success": True, "data": result}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))


@router.post("/strategic/generate-major-tasks/{initiative_id}")
async def generate_major_tasks(initiative_id: int, request: dict = None):
    try:
        user_instructions = request.get('instructions', '') if request else ''
        planner = StrategicPlanner()
        job_id = planner.create_job(initiative_id)
        planner.close()

        thread = threading.Thread(
            target=generate_in_background,
            args=(job_id, initiative_id, user_instructions),
            daemon=True
        )
        thread.start()

        return {
            "success": True,
            "data": {
                "job_id": job_id,
                "status": "pending",
                "message": "Task generation started in background"
            }
        }
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))


@router.get("/jobs/{job_id}")
async def get_job_status(job_id: int, db: Session = Depends(get_db)):
    job = db.query(AiJob).filter(AiJob.job_id == job_id).first()
    if not job:
        raise HTTPException(status_code=404, detail="Job not found")
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
    try:
        job = db.query(AiJob).filter(AiJob.job_id == job_id).first()
        if not job:
            raise HTTPException(status_code=404, detail="Job not found")
        current_result = json.loads(job.result_json) if job.result_json else {}
        prompt = f"""
Current task plan:
{json.dumps(current_result.get('major_tasks', []), ensure_ascii=False, indent=2)}

Instructions: {request.get('instruction', '')}

Return the modified JSON array in the same format.
"""
        response_text = ollama_client.generate(
            prompt,
            system_instruction="Return ONLY valid JSON array. No other text."
        )
        planner = StrategicPlanner()
        new_result = planner.parse_response(response_text)
        planner.close()
        job.result_json = json.dumps({
            **current_result,
            "major_tasks": new_result if new_result else current_result.get("major_tasks", [])
        }, ensure_ascii=False)
        job.updated_at = datetime.now()
        db.commit()
        return {
            "success": True,
            "data": {
                "job_id": job_id,
                "major_tasks": new_result if new_result else current_result.get("major_tasks", [])
            }
        }
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))


@router.post("/strategic/approve-task-plan/{job_id}")
async def approve_task_plan(job_id: int, db: Session = Depends(get_db)):
    try:
        job = db.query(AiJob).filter(AiJob.job_id == job_id).first()
        if not job:
            raise HTTPException(status_code=404, detail="Job not found")
        result = json.loads(job.result_json) if job.result_json else {}
        initiative_id = result.get('initiative_id')
        tasks = result.get('major_tasks', [])
        if not tasks:
            raise HTTPException(status_code=400, detail="No tasks to save")
        planner = StrategicPlanner()
        saved = planner.save_major_tasks(initiative_id, tasks, job.created_by)
        planner.update_job(job_id, "completed", {"saved_tasks": saved})
        planner.close()
        return {"success": True, "data": {"message": f"Saved {len(saved)} major tasks", "tasks": saved}}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))


@router.post("/scheduler/start")
async def start_scheduler():
    try:
        ai_scheduler.start()
        return {"success": True, "message": "Scheduler started"}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))


@router.post("/scheduler/stop")
async def stop_scheduler():
    try:
        ai_scheduler.stop()
        return {"success": True, "message": "Scheduler stopped"}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))


@router.get("/scheduler/status")
async def scheduler_status():
    return {
        "success": True,
        "data": {
            "is_running": ai_scheduler.is_running,
            "next_prediction": "06:00 daily",
            "next_training": "Monday 02:00 weekly"
        }
    }


@router.post("/predict-now")
async def predict_now():
    try:
        results = ai_scheduler.run_daily_prediction()
        return {
            "success": True,
            "data": {
                "total_tasks": len(results) if results else 0,
                "high_risk": sum(1 for r in results if r.get('risk_level') == 'High') if results else 0,
                "message": "Prediction completed"
            }
        }
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))


@router.post("/train-now")
async def train_now():
    try:
        metrics = ai_scheduler.run_weekly_training()
        return {"success": True, "data": metrics}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))


@router.get("/models/status")
async def models_status():
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
            "ollama": {
                "available": True,
                "model": "qwen2.5:3b"
            }
        }
    }

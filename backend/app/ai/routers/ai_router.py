import json
import threading
from datetime import datetime
from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session


from app.database import get_db, SessionLocal
from app.models import OperationalTask, AIPrediction, AiJob, Employee, AIRecommendation, DictPriority
from app.ai.services.prediction_service import prediction_service
from app.ai.services.ollama_client import ollama_client
from app.ai.models.delay_predictor import DelayPredictor
from app.ai.models.recommender import Recommender
from app.ai.services.strategic_planner import StrategicPlanner
from app.ai.services.scheduler import ai_scheduler
from app.ai.services.operational_planner import OperationalPlanner
from app.ai.services.risk_detection_service import RiskDetectionService
from app.ai.services.risk_recommendation_service import RiskRecommendationService
from app.ai.services.risk_reassessment_service import RiskReassessmentService
from app.core.dependencies import get_current_user




router = APIRouter(prefix="/api/ai", tags=["AI"])




def operational_in_background(job_id: int, major_task_id: int, instructions: str = ""):
    try:
        print(f"Job #{job_id}: Starting operational task generation")
        planner = OperationalPlanner()
        context = planner.get_major_task_context(major_task_id)
        prompt = planner.build_prompt(context, instructions)
        planner.close()


        response_text = ollama_client.generate(
            prompt,
            system_instruction="Return ONLY valid JSON array. No other text."
        )


        planner2 = OperationalPlanner()
        tasks = planner2.parse_response(response_text)
        tasks = planner2.normalize_tasks(tasks, major_task_id)
        if not tasks:
            raise ValueError("No valid tasks generated")


        planner2.update_job(
            job_id,
            "review",
            {
                "major_task_id": major_task_id,
                "operational_tasks": tasks
            }
        )
        planner2.close()
        print(f"Job #{job_id}: Completed - {len(tasks)} tasks generated")


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




def generate_in_background(job_id: int, initiative_id: int, user_instructions: str = ""):
    try:
        print(f"Job #{job_id}: Starting background task generation")
        planner = StrategicPlanner()
        context = planner.get_initiative_context(initiative_id)
        prompt = planner.build_compact_prompt(context, user_instructions)
        planner.close()


        response_text = ollama_client.generate(
            prompt,
            system_instruction="Return ONLY valid JSON array. No other text."
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




def edit_in_background(job_id: int, instruction: str):
    try:
        print(f"Job #{job_id}: Starting background edit...")
        db = SessionLocal()
        job = db.query(AiJob).filter(AiJob.job_id == job_id).first()


        if not job:
            db.close()
            return


        current_result = json.loads(job.result_json) if job.result_json else {}
        current_tasks = current_result.get('major_tasks', [])


        prompt = f"""
You have this task plan:
{json.dumps(current_tasks, ensure_ascii=False, indent=2)}


User instruction: {instruction}


Apply the user's instruction to modify the task plan. Return the COMPLETE modified JSON array with ALL tasks.


Return ONLY the JSON array.
"""
        response_text = ollama_client.generate(
            prompt,
            system_instruction="Return ONLY the complete modified JSON array. No other text."
        )


        planner = StrategicPlanner()
        new_tasks = planner.parse_response(response_text)
        planner.close()


        if new_tasks and len(new_tasks) > 0:
            current_result["major_tasks"] = new_tasks
        else:
            current_result["major_tasks"] = current_tasks


        job.result_json = json.dumps(current_result, ensure_ascii=False)
        job.status = "review"
        job.updated_at = datetime.now()
        db.commit()
        print(f"Job #{job_id}: Edit completed")


    except Exception as e:
        print(f"Job #{job_id}: Edit failed - {str(e)}")
        db = SessionLocal()
        try:
            job = db.query(AiJob).filter(AiJob.job_id == job_id).first()
            if job:
                job.status = "review"
                db.commit()
        finally:
            db.close()
    finally:
        if 'db' in locals():
            db.close()




def train_delay_model_sync():
    try:
        predictor = DelayPredictor()
        metrics = predictor.train(force=True)
        if metrics:
            return {"success": True, "data": metrics}
        return {"success": False, "data": None, "message": "Training failed"}
    except Exception as e:
        return {"success": False, "data": None, "message": str(e)}




@router.post("/operational/generate-tasks/{major_task_id}")
async def generate_operational_tasks(major_task_id: int, request: dict = None):
    try:
        planner = OperationalPlanner()
        job_id = planner.create_job(major_task_id)
        planner.close()


        instructions = (request or {}).get('instructions', '')
        thread = threading.Thread(
            target=operational_in_background,
            args=(job_id, major_task_id, instructions),
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




@router.post("/operational/edit-tasks/{job_id}")
async def edit_operational_tasks(job_id: int, request: dict, db: Session = Depends(get_db)):
    try:
        job = db.query(AiJob).filter(AiJob.job_id == job_id).first()
        if not job:
            raise HTTPException(status_code=404, detail="Job not found")


        current = json.loads(job.result_json) if job.result_json else {}
        current_tasks = current.get('operational_tasks', [])


        prompt = f"""
Current tasks:
{json.dumps(current_tasks, ensure_ascii=False, indent=2)}


Instructions: {request.get('instruction', '')}


Apply the instructions and return the modified JSON array.
"""
        response_text = ollama_client.generate(
            prompt,
            system_instruction="Return ONLY valid JSON array. No other text."
        )


        planner = OperationalPlanner()
        new_tasks = planner.parse_response(response_text)
        planner.close()


        if new_tasks:
            current["operational_tasks"] = new_tasks


        job.result_json = json.dumps(current, ensure_ascii=False)
        job.updated_at = datetime.now()
        db.commit()


        return {
            "success": True,
            "data": {
                "job_id": job_id,
                "operational_tasks": current.get("operational_tasks", [])
            }
        }
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))




@router.post("/operational/approve-tasks/{job_id}")
async def approve_operational_tasks(
    job_id: int,
    db: Session = Depends(get_db),
    current_user: Employee = Depends(get_current_user)
):
    try:
        job = db.query(AiJob).filter(AiJob.job_id == job_id).first()


        if not job:
            raise HTTPException(status_code=404, detail="Job not found")


        result = json.loads(job.result_json) if job.result_json else {}


        major_task_id = result.get("major_task_id")
        tasks = result.get("operational_tasks", [])


        if not major_task_id:
            raise HTTPException(status_code=400, detail="Major task ID not found")


        if not tasks:
            raise HTTPException(status_code=400, detail="No tasks to save")


        department_id = current_user.department_id
        employee_id = current_user.employee_id


        if not department_id:
            raise HTTPException(status_code=400, detail="Current employee has no department")


        planner = OperationalPlanner()


        saved = planner.save_tasks(
            major_task_id=major_task_id,
            tasks=tasks,
            department_id=department_id,
            created_by=employee_id
        )


        planner.update_job(
            job_id,
            "completed",
            {
                "major_task_id": major_task_id,
                "operational_tasks": tasks,
                "saved_tasks": saved
            }
        )


        planner.close()


        return {
            "success": True,
            "data": {
                "message": f"Saved {len(saved)} operational tasks",
                "major_task_id": major_task_id,
                "department_id": department_id,
                "created_by": employee_id,
                "tasks": saved
            }
        }


    except HTTPException:
        raise
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




@router.post("/strategic/edit-task-plan/{job_id}")
async def edit_task_plan(job_id: int, request: dict, db: Session = Depends(get_db)):
    try:
        job = db.query(AiJob).filter(AiJob.job_id == job_id).first()
        if not job:
            raise HTTPException(status_code=404, detail="Job not found")


        edit_instruction = request.get('instruction', '')


        job.status = "editing"
        db.commit()


        thread = threading.Thread(
            target=edit_in_background,
            args=(job_id, edit_instruction),
            daemon=True
        )
        thread.start()


        return {
            "success": True,
            "data": {
                "job_id": job_id,
                "status": "editing",
                "message": "Edit started in background"
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


        return {
            "success": True,
            "data": {
                "message": f"Saved {len(saved)} major tasks",
                "tasks": saved
            }
        }
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))




@router.post("/strategic/save-edited-tasks/{job_id}")
async def save_edited_tasks(job_id: int, request: dict, db: Session = Depends(get_db)):
    try:
        job = db.query(AiJob).filter(AiJob.job_id == job_id).first()
        if not job:
            raise HTTPException(status_code=404, detail="Job not found")


        initiative_id = request.get('initiative_id')
        tasks = request.get('major_tasks', [])


        if not tasks:
            raise HTTPException(status_code=400, detail="No tasks to save")


        planner = StrategicPlanner()
        saved = planner.save_major_tasks(initiative_id, tasks, job.created_by)
        planner.update_job(job_id, "completed", {"saved_tasks": saved})
        planner.close()


        return {
            "success": True,
            "data": {
                "message": f"Saved {len(saved)} major tasks",
                "tasks": saved
            }
        }
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))




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




@router.post("/predict-and-detect/{task_id}")
async def predict_and_detect_risk(
    task_id: int,
    current_user: Employee = Depends(get_current_user)
):
    try:
        result = prediction_service.predict_single(
            task_id, save=True, detect_risk=True
        )
        return {"success": True, "data": result}
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
            task = db.query(OperationalTask).filter(
                OperationalTask.task_id == p.task_id
            ).first()
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
async def get_recommendations(task_id: int, save: bool = False):
    try:
        rec = Recommender()
        result = rec.recommend(task_id=task_id, save_to_db=save)
        return {"success": True, "data": result}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))




@router.post("/risk/{risk_id}/recommendations")
async def generate_risk_recommendations(
    risk_id: int,
    current_user: Employee = Depends(get_current_user)
):
    try:
        service = RiskRecommendationService()
        try:
            result = service.generate_for_risk(risk_id, created_by=current_user.employee_id)
            return {"success": True, "data": result}
        finally:
            service.close()
    except ValueError as e:
        raise HTTPException(status_code=404, detail=str(e))
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))




@router.post("/risk/{risk_id}/reassess")
async def reassess_risk(
    risk_id: int,
    current_user: Employee = Depends(get_current_user)
):
    try:
        service = RiskReassessmentService()
        try:
            result = service.reassess(risk_id, created_by=current_user.employee_id)
            return {"success": True, "data": result}
        finally:
            service.close()
    except ValueError as e:
        raise HTTPException(status_code=404, detail=str(e))
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))




@router.get("/risk/{risk_id}/ai-recommendations")
async def get_risk_ai_recommendations(
    risk_id: int,
    db: Session = Depends(get_db)
):
    try:
        from app.models import Risk
        risk = db.query(Risk).filter(Risk.risk_id == risk_id).first()
        if not risk:
            raise HTTPException(status_code=404, detail="الخطر غير موجود")


        if not risk.task_id:
            return {"success": True, "data": []}


        recommendations = db.query(AIRecommendation).filter(
            AIRecommendation.task_id == risk.task_id
        ).order_by(AIRecommendation.created_at.desc()).limit(20).all()


        result = []
        for r in recommendations:
            parsed_reasoning = None
            reasoning_risk_id = None
            try:
                parsed_reasoning = json.loads(r.reasoning) if r.reasoning else None
                if parsed_reasoning:
                    reasoning_risk_id = parsed_reasoning.get('risk_id')
            except Exception:
                parsed_reasoning = None


            if reasoning_risk_id is not None and reasoning_risk_id != risk_id:
                continue


            priority = None
            if r.priority_id:
                p = db.query(DictPriority).filter(
                    DictPriority.priority_id == r.priority_id
                ).first()
                if p:
                    priority = {"code": p.code, "name_ar": p.name_ar}


            reasoning_text = r.reasoning
            if parsed_reasoning:
                reasoning_text = parsed_reasoning.get('text', r.reasoning)


            result.append({
                "recommendation_id": r.recommendation_id,
                "text": r.text,
                "reasoning": reasoning_text,
                "priority": priority,
                "status_id": r.status_id,
                "implemented_at": r.implemented_at.isoformat() if r.implemented_at else None,
                "created_at": r.created_at.isoformat() if r.created_at else None
            })


        return {"success": True, "data": result}
    except HTTPException:
        raise
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))




@router.get("/jobs/{job_id}")
async def get_job_status(job_id: int, db: Session = Depends(get_db)):
    job = db.query(AiJob).filter(AiJob.job_id == job_id).first()
    if not job:
        raise HTTPException(status_code=404, detail="Job not found")


    result = None
    if job.result_json:
        try:
            result = json.loads(job.result_json)
        except Exception:
            result = job.result_json


    return {
        "success": True,
        "data": {
            "job_id": job.job_id,
            "job_type": job.job_type,
            "status": job.status,
            "result": result,
            "created_at": job.created_at.isoformat() if job.created_at else None,
            "updated_at": job.updated_at.isoformat() if job.updated_at else None
        }
    }




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

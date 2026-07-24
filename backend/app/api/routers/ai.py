from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session
from app.database import get_db
from app.models import AIModel, AIPrediction, AIRecommendation, OperationalTask
from app.ai.models.delay_predictor import delay_predictor
from app.ai.models.recommender import recommender

router = APIRouter(prefix="/api/ai", tags=["AI"])

@router.get("/dashboard")
async def ai_dashboard(db: Session = Depends(get_db)):
    try:
        total_models = db.query(AIModel).count()
        total_predictions = db.query(AIPrediction).count()
        total_recommendations = db.query(AIRecommendation).count()
        models = db.query(AIModel).limit(5).all()
        models_data = [{"id": m.model_id, "name": m.name, "description": m.description or "", "status": "active" if m.is_active else "inactive", "accuracy": float(m.accuracy) if m.accuracy else 0} for m in models]
        risky_tasks = db.query(OperationalTask).filter(OperationalTask.status_id == 3).limit(5).all()
        risky_data = [{"task_name": t.title, "delay_probability": 75} for t in risky_tasks]
        return {"success": True, "data": {"total_models": total_models, "total_predictions": total_predictions, "total_recommendations": total_recommendations, "accuracy": 85, "models": models_data, "risky_tasks": risky_data, "recent_recommendations": []}}
    except Exception as e: raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")

@router.get("/models")
async def ai_models(db: Session = Depends(get_db)):
    try:
        models = db.query(AIModel).all()
        return {"success": True, "data": [{"id": m.model_id, "name": m.name, "type": m.name, "description": m.description or "", "status": "active" if m.is_active else "inactive", "accuracy": float(m.accuracy) if m.accuracy else 0, "version": m.version or "1.0", "last_trained": m.deployed_at.isoformat() if m.deployed_at else None} for m in models]}
    except Exception as e: raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")

@router.get("/models/{model_id}")
async def model_detail(model_id: int, db: Session = Depends(get_db)):
    try:
        m = db.query(AIModel).filter(AIModel.model_id == model_id).first()
        if not m: raise HTTPException(status_code=404, detail="غير موجود")
        return {"success": True, "data": {"id": m.model_id, "name": m.name, "type": m.name, "description": m.description or "", "status": "active" if m.is_active else "inactive", "accuracy": float(m.accuracy) if m.accuracy else 0, "version": m.version or "1.0", "last_trained": m.deployed_at.isoformat() if m.deployed_at else None, "parameters": {}}}
    except HTTPException: raise
    except Exception as e: raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")

@router.get("/predictions")
async def ai_predictions(db: Session = Depends(get_db)):
    try:
        predictions = db.query(AIPrediction).order_by(AIPrediction.created_at.desc()).limit(50).all()
        return {"success": True, "data": [{"id": p.prediction_id, "model_name": f"نموذج {p.model_id}", "result": p.text or "", "confidence": float(p.confidence) if p.confidence else 0, "created_at": p.created_at.isoformat() if p.created_at else None, "input_data": {}, "details": {}} for p in predictions]}
    except Exception as e: raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")

@router.get("/predictions/{prediction_id}")
async def prediction_detail(prediction_id: int, db: Session = Depends(get_db)):
    try:
        p = db.query(AIPrediction).filter(AIPrediction.prediction_id == prediction_id).first()
        if not p: raise HTTPException(status_code=404, detail="غير موجود")
        return {"success": True, "data": {"id": p.prediction_id, "model_name": f"نموذج {p.model_id}", "result": p.text or "", "confidence": float(p.confidence) if p.confidence else 0, "created_at": p.created_at.isoformat() if p.created_at else None}}
    except HTTPException: raise
    except Exception as e: raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")

@router.get("/recommendations")
async def ai_recommendations(db: Session = Depends(get_db)):
    try:
        recs = db.query(AIRecommendation).order_by(AIRecommendation.created_at.desc()).limit(50).all()
        return {"success": True, "data": [{"id": r.recommendation_id, "title": r.reasoning or "توصية", "description": r.reasoning or "", "priority": "medium", "category": "عام", "status": "جديد", "created_at": r.created_at.isoformat() if r.created_at else None} for r in recs]}
    except Exception as e: raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")

@router.get("/recommendations/{recommendation_id}")
async def recommendation_detail(recommendation_id: int, db: Session = Depends(get_db)):
    try:
        r = db.query(AIRecommendation).filter(AIRecommendation.recommendation_id == recommendation_id).first()
        if not r: raise HTTPException(status_code=404, detail="غير موجودة")
        return {"success": True, "data": {"id": r.recommendation_id, "title": r.reasoning or "توصية", "description": r.reasoning or "", "priority": "medium", "category": "عام", "status": "جديد", "created_at": r.created_at.isoformat() if r.created_at else None, "actions": []}}
    except HTTPException: raise
    except Exception as e: raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")


@router.get("/predict-delay/{task_id}")
async def predict_task_delay(task_id: int, db: Session = Depends(get_db)):

    try:
        task = db.query(OperationalTask).filter(OperationalTask.task_id == task_id).first()
        if not task:
            raise HTTPException(status_code=404, detail="المهمة غير موجودة")
        
        task_data = {
            'progress': 0,
            'end_date': task.end_date,
            'start_date': task.start_date,
            'priority_id': task.priority_id or 2,
            'estimated_hours': float(task.estimated_hours or 0),
            'actual_hours': float(task.actual_hours or 0),
            'is_cross_functional': task.is_cross_functional or False,
        }
        
        result = delay_predictor.predict(task_data)
        
        return {"success": True, "data": result}
    except HTTPException:
        raise
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")


@router.get("/predict-all-delays")
async def predict_all_delays(db: Session = Depends(get_db)):

    try:
        tasks = db.query(OperationalTask).filter(
            OperationalTask.status_id.in_([1, 2])  
        ).all()
        
        results = []
        for task in tasks:
            task_data = {
                'progress': 0,
                'end_date': task.end_date,
                'start_date': task.start_date,
                'priority_id': task.priority_id or 2,
                'estimated_hours': float(task.estimated_hours or 0),
                'actual_hours': float(task.actual_hours or 0),
                'is_cross_functional': task.is_cross_functional or False,
            }
            
            prediction = delay_predictor.predict(task_data)
            
            results.append({
                "task_id": task.task_id,
                "task_name": task.title,
                "delay_probability": prediction['delay_probability'],
                "risk_level": prediction['risk_level'],
                "risk_label": prediction['risk_label']
            })
        

        results.sort(key=lambda x: x['delay_probability'], reverse=True)
        
        return {"success": True, "data": results}
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")


@router.get("/recommendations/task/{task_id}")
async def task_recommendations(task_id: int, db: Session = Depends(get_db)):

    try:
        task = db.query(OperationalTask).filter(OperationalTask.task_id == task_id).first()
        if not task:
            raise HTTPException(status_code=404, detail="المهمة غير موجودة")
        
        from datetime import date
        days_remaining = (task.end_date - date.today()).days if task.end_date else 30
        
        task_data = {
            'task_name': task.title,
            'task_id': task.task_id,
            'status_id': task.status_id,
            'progress': 0,
            'priority_id': task.priority_id or 2,
            'days_remaining': days_remaining,
            'estimated_hours': float(task.estimated_hours or 0),
            'actual_hours': float(task.actual_hours or 0),
            'employee_task_count': 3,
            'budget_spent_percent': 0,
            'has_dependencies': False,
        }
        
        result = recommender.get_task_recommendations(task_data)
        return {"success": True, "data": result}
    except HTTPException:
        raise
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")


@router.get("/recommendations/dashboard")
async def dashboard_recommendations(db: Session = Depends(get_db)):

    try:
        from datetime import date
        
        tasks = db.query(OperationalTask).filter(
            OperationalTask.status_id.in_([1, 2, 3])
        ).all()
        
        tasks_data = []
        for task in tasks:
            days_remaining = (task.end_date - date.today()).days if task.end_date else 30
            tasks_data.append({
                'task_name': task.title,
                'task_id': task.task_id,
                'status_id': task.status_id,
                'progress': 0,
                'priority_id': task.priority_id or 2,
                'days_remaining': days_remaining,
                'estimated_hours': float(task.estimated_hours or 0),
                'actual_hours': float(task.actual_hours or 0),
                'employee_task_count': 3,
                'budget_spent_percent': 0,
                'has_dependencies': False,
            })
        
        result = recommender.get_dashboard_recommendations(tasks_data, limit=10)
        stats = recommender.get_summary_stats(tasks_data)
        
        return {
            "success": True,
            "data": {
                "recommendations": result,
                "stats": stats
            }
        }
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"خطأ: {str(e)}")

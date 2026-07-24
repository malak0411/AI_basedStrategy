
import json
from datetime import datetime
from sqlalchemy import func
from app.database import SessionLocal
from app.models import OperationalTask, AIPrediction
from app.ai.services.data_loader import DataLoader
from app.ai.models.delay_predictor import DelayPredictor

class PredictionService:
    
    def __init__(self):
        self.predictor = DelayPredictor()
        self._loaded = False
    
    def _ensure_loaded(self):
        if not self._loaded:
            self._loaded = self.predictor.load_model()
    
    def predict_single(self, task_id: int, save: bool = True):

        self._ensure_loaded()
        

        loader = DataLoader()
        try:
            features = loader.get_task_features(task_id)
        finally:
            loader.close()
        
        if not features:
            return {"error": "المهمة غير موجودة", "task_id": int(task_id)}
        

        db = SessionLocal()
        try:
            task = db.query(OperationalTask).filter(OperationalTask.task_id == task_id).first()
            task_name = task.title if task else f"مهمة #{task_id}"
        finally:
            db.close()
        

        result = self.predictor.predict(task_features=features)
        result["task_id"] = int(task_id)
        result["task_name"] = task_name
        
        if "delay_probability" in result:
            result["delay_probability"] = round(float(result["delay_probability"]), 3)
        if "confidence" in result:
            result["confidence"] = round(float(result["confidence"]), 3)
        
        if save:
            self._save_prediction(task_id, result)
        
        return result
    
    def predict_all_active(self, save: bool = True):

        self._ensure_loaded()
        
        db = SessionLocal()
        try:
            tasks = db.query(OperationalTask).all()
            results = []
            for task in tasks:
                result = self.predict_single(task.task_id, save=save)
                results.append(result)
            results.sort(key=lambda x: x.get('delay_probability', 0), reverse=True)
            return results
        finally:
            db.close()
    
    def get_dashboard_stats(self):

        db = SessionLocal()
        try:
            total_predicted_tasks = db.query(AIPrediction).filter(
                AIPrediction.prediction_type == 'delay'
            ).count()
            
            high_risk = db.query(AIPrediction).filter(
                AIPrediction.prediction_type == 'delay',
                AIPrediction.probability > 0.7
            ).count()
            
            avg_prob = db.query(func.avg(AIPrediction.probability)).filter(
                AIPrediction.prediction_type == 'delay'
            ).scalar() or 0
            
            last_prediction = db.query(AIPrediction).order_by(
                AIPrediction.created_at.desc()
            ).first()
            
            return {
                "total_predictions": int(total_predicted_tasks),
                "high_risk_tasks": int(high_risk),
                "average_delay_probability": round(float(avg_prob) * 100, 1),
                "model_version": str(self.predictor.model_version),
                "is_model_trained": bool(self._loaded),
                "last_prediction_time": last_prediction.created_at.isoformat() if last_prediction else None,
                "is_scheduler_running": True
            }
        finally:
            db.close()
    
    def _save_prediction(self, task_id: int, result: dict):

        db = SessionLocal()
        try:

            existing = db.query(AIPrediction).filter(
                AIPrediction.task_id == int(task_id),
                AIPrediction.prediction_type == 'delay'
            ).first()
            
            if existing:

                existing.probability = float(result.get('delay_probability', 0))
                existing.confidence = float(result.get('confidence', 0))
                existing.text = f"Risk: {result.get('risk_level', 'Unknown')}"
                existing.features_used = json.dumps(result.get('top_factors', []), ensure_ascii=False)
                existing.created_at = datetime.now()
            else:

                prediction = AIPrediction(
                    task_id=int(task_id),
                    prediction_type='delay',
                    text=f"Risk: {result.get('risk_level', 'Unknown')}",
                    probability=float(result.get('delay_probability', 0)),
                    confidence=float(result.get('confidence', 0)),
                    features_used=json.dumps(result.get('top_factors', []), ensure_ascii=False),
                    created_at=datetime.now()
                )
                db.add(prediction)
            
            db.commit()
        except Exception as e:
            print(f" خطأ في حفظ التنبؤ: {str(e)}")
            db.rollback()
        finally:
            db.close()


prediction_service = PredictionService()

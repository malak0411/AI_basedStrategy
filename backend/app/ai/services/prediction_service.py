import json
from datetime import datetime
from sqlalchemy import func

from app.database import SessionLocal
from app.models import OperationalTask, AIPrediction, AIModel
from app.ai.services.data_loader import DataLoader
from app.ai.models.delay_predictor import DelayPredictor
from app.ai.services.risk_detection_service import RiskDetectionService
from app.ai.config.constants import ACTIVE_TASK_STATUSES, TASK_STATUS_COMPLETED


class PredictionService:

    def __init__(self):
        self.predictor = DelayPredictor()
        self._loaded = False
        self._load_attempted = False
        self._model_id = None

    def _ensure_loaded(self):
        if self._load_attempted:
            return
        self._load_attempted = True
        self._loaded = self.predictor.load_model()
        self._model_id = self._resolve_model_id()

    def reload_model(self):
        self.predictor = DelayPredictor()
        self._loaded = self.predictor.load_model()
        self._load_attempted = True
        self._model_id = self._resolve_model_id()
        return self._loaded

    def _resolve_model_id(self):
        db = SessionLocal()
        try:
            model = db.query(AIModel).filter(
                AIModel.name.like('%Delay%'),
                AIModel.is_active == True,
            ).first()
            return model.model_id if model else None
        finally:
            db.close()

    def predict_single(self, task_id, save=True, detect_risk=True, generate_recommendations=False):
        self._ensure_loaded()
        db = SessionLocal()
        try:
            loader = DataLoader(db=db)
            features = loader.get_task_features(task_id)
            if not features:
                return {"error": "المهمة غير موجودة", "task_id": int(task_id)}

            task = db.query(OperationalTask).filter(
                OperationalTask.task_id == task_id
            ).first()
            task_name = task.title if task else f"مهمة #{task_id}"
            created_by = task.created_by if task else None

            result = self.predictor.predict(task_features=features)
            result["task_id"] = int(task_id)
            result["task_name"] = task_name

            if save:
                self._save_prediction(task_id, result, db)

            if detect_risk:
                if task and task.status_id == TASK_STATUS_COMPLETED:
                    result["risk_detection"] = {
                        "detected": False,
                        "reason": "task_completed",
                        "task_id": int(task_id),
                    }
                else:
                    detection = RiskDetectionService(db=db)
                    try:
                        risk_result = detection.detect_from_prediction(
                            task_id, result, created_by=created_by
                        )
                        result["risk_detection"] = risk_result
                        if generate_recommendations and risk_result.get("detected"):
                            rid = risk_result.get("risk_id")
                            if rid:
                                from app.ai.services.risk_recommendation_service import RiskRecommendationService
                                rec = RiskRecommendationService(db=db)
                                try:
                                    result["recommendations_result"] = rec.generate_for_risk(
                                        rid, created_by=created_by
                                    )
                                finally:
                                    rec.close()
                    finally:
                        detection.close()

            return result
        finally:
            db.close()

    def predict_all_active(self, save=True, detect_risk=False):
        self._ensure_loaded()
        db = SessionLocal()
        try:
            tasks = db.query(OperationalTask).filter(
                OperationalTask.is_active == True,
                OperationalTask.status_id.in_(ACTIVE_TASK_STATUSES),
            ).all()
            results = []
            for task in tasks:
                r = self.predict_single(task.task_id, save=save, detect_risk=detect_risk)
                if "error" not in r:
                    results.append(r)
            results.sort(key=lambda x: x.get('delay_probability', 0), reverse=True)
            return results
        finally:
            db.close()

    def get_dashboard_stats(self):
        db = SessionLocal()
        try:
            total = db.query(AIPrediction).filter(
                AIPrediction.prediction_type == 'delay'
            ).count()
            high_risk = db.query(AIPrediction).filter(
                AIPrediction.prediction_type == 'delay',
                AIPrediction.probability > 0.7,
            ).count()
            avg = db.query(func.avg(AIPrediction.probability)).filter(
                AIPrediction.prediction_type == 'delay'
            ).scalar() or 0
            last = db.query(AIPrediction).order_by(
                AIPrediction.created_at.desc()
            ).first()
            return {
                "total_predictions": int(total),
                "high_risk_tasks": int(high_risk),
                "average_delay_probability": round(float(avg) * 100, 1) if float(avg) <= 1 else round(float(avg), 1),
                "model_version": str(self.predictor.model_version),
                "is_model_trained": bool(self._loaded),
                "last_prediction_time": last.created_at.isoformat() if last else None,
            }
        finally:
            db.close()

    def _save_prediction(self, task_id, result, db):
        try:
            existing = db.query(AIPrediction).filter(
                AIPrediction.task_id == int(task_id),
                AIPrediction.prediction_type == 'delay',
            ).first()
            prob = float(result.get('delay_probability', 0))
            conf = float(result.get('confidence', 0))
            text = f"Risk: {result.get('risk_level', 'Unknown')}"
            features = json.dumps(result.get('top_factors', []), ensure_ascii=False)
            if existing:
                existing.probability = prob
                existing.confidence = conf
                existing.text = text
                existing.features_used = features
                if self._model_id:
                    existing.model_id = self._model_id
            else:
                db.add(AIPrediction(
                    task_id=int(task_id),
                    prediction_type='delay',
                    text=text,
                    probability=prob,
                    confidence=conf,
                    model_id=self._model_id,
                    features_used=features,
                    created_at=datetime.now(),
                ))
            db.commit()
        except Exception as e:
            print(f"save prediction fail: {e}")
            db.rollback()


prediction_service = PredictionService()

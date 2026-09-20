import json
from datetime import datetime
from sqlalchemy import func
from app.database import SessionLocal
from app.models import OperationalTask, AIPrediction, AIModel
from app.ai.services.data_loader import DataLoader
from app.ai.models.delay_predictor import DelayPredictor
from app.ai.services.risk_detection_service import RiskDetectionService




class PredictionService:


    def __init__(self):
        self.predictor = DelayPredictor()
        self._loaded = False
        self._model_id = None


    def _ensure_loaded(self):
        if not self._loaded:
            self._loaded = self.predictor.load_model()
        if self._model_id is None:
            self._model_id = self._resolve_model_id()


    def _resolve_model_id(self):
        db = SessionLocal()
        try:
            model = db.query(AIModel).filter(
                AIModel.name.like('%Delay%'),
                AIModel.is_active == True
            ).first()
            if model:
                return model.model_id
            model = db.query(AIModel).filter(AIModel.is_active == True).first()
            return model.model_id if model else None
        finally:
            db.close()


    def predict_single(self, task_id: int, save: bool = True, detect_risk: bool = True,
                       generate_recommendations: bool = False):


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
            created_by = task.created_by if task else None
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


        if detect_risk:
            try:
                detection = RiskDetectionService()
                try:
                    risk_result = detection.detect_from_prediction(
                        task_id, result, created_by=created_by
                    )
                    result["risk_detection"] = risk_result


                    if generate_recommendations and risk_result.get("detected"):
                        risk_id = risk_result.get("risk_id")
                        if risk_id:
                            try:
                                from app.ai.services.risk_recommendation_service import RiskRecommendationService
                                rec_service = RiskRecommendationService()
                                try:
                                    rec_result = rec_service.generate_for_risk(
                                        risk_id, created_by=created_by
                                    )
                                    result["recommendations_result"] = rec_result
                                finally:
                                    rec_service.close()
                            except Exception as rec_err:
                                result["recommendations_result"] = {
                                    "success": False,
                                    "error": str(rec_err)
                                }
                finally:
                    detection.close()
            except Exception as e:
                result["risk_detection"] = {"detected": False, "error": str(e)}


        return result


    def predict_all_active(self, save: bool = True, detect_risk: bool = False):


        self._ensure_loaded()


        db = SessionLocal()
        try:
            tasks = db.query(OperationalTask).all()
            results = []
            for task in tasks:
                result = self.predict_single(task.task_id, save=save, detect_risk=detect_risk)
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
                "average_delay_probability": round(float(avg_prob) * 100, 1) if float(avg_prob) <= 1 else round(float(avg_prob), 1),
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
                prediction = AIPrediction(
                    task_id=int(task_id),
                    prediction_type='delay',
                    text=text,
                    probability=prob,
                    confidence=conf,
                    model_id=self._model_id,
                    features_used=features,
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

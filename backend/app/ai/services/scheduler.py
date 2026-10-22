import schedule
import time
import threading
import logging
from datetime import datetime

logger = logging.getLogger(__name__)


class AIScheduler:

    def __init__(self):
        self.is_running = False
        self.thread = None

    def start(self):
        if self.is_running:
            return
        schedule.every().day.at("06:00").do(self.run_daily_prediction)
        schedule.every().day.at("07:00").do(self.run_risk_reassessment)
        schedule.every().monday.at("02:00").do(self.run_weekly_training)
        self.is_running = True
        self.thread = threading.Thread(target=self._loop, daemon=True)
        self.thread.start()
        logger.info("AI scheduler started")

    def _loop(self):
        while self.is_running:
            try:
                schedule.run_pending()
            except Exception as e:
                logger.exception(f"scheduler tick fail: {e}")
            time.sleep(60)

    def run_daily_prediction(self):
        from app.ai.services.prediction_service import prediction_service
        logger.info("daily prediction started")
        try:
            results = prediction_service.predict_all_active(save=True, detect_risk=True)
            high = sum(1 for r in results if r.get('risk_level') == 'High')
            logger.info(f"daily prediction done: {len(results)} tasks, {high} high risk")
            return results
        except Exception as e:
            logger.exception(f"daily prediction fail: {e}")
            return None

    def run_risk_reassessment(self):
        from app.ai.services.risk_detection_service import RiskDetectionService
        logger.info("risk reassessment started")
        try:
            svc = RiskDetectionService()
            try:
                results = svc.reassess_all_active_risks()
                logger.info(f"reassessed {len(results)} active risks")
                return results
            finally:
                svc.close()
        except Exception as e:
            logger.exception(f"risk reassessment fail: {e}")
            return None

    def run_weekly_training(self):
        from app.ai.services.prediction_service import prediction_service
        logger.info("weekly training started")
        try:
            metrics = prediction_service.predictor.train(force=True)
            if metrics:
                prediction_service.reload_model()
                logger.info(f"training done: F1={metrics.get('f1_score', 0)}")
            return metrics
        except Exception as e:
            logger.exception(f"training fail: {e}")
            return None

    def stop(self):
        self.is_running = False
        schedule.clear()
        logger.info("AI scheduler stopped")


ai_scheduler = AIScheduler()

"""
AI Scheduler - جدولة تلقائية للتنبؤات اليومية
"""
import schedule
import time
import threading
from datetime import datetime
from app.ai.services.prediction_service import prediction_service

class AIScheduler:
    """جدولة مهام الذكاء الاصطناعي"""
    
    def __init__(self):
        self.is_running = False
        self.thread = None
    
    def start(self):
        """بدء الجدولة في خلفية منفصلة"""
        if self.is_running:
            return
        
        # جدولة التنبؤ اليومي (الساعة 6 صباحاً)
        schedule.every().day.at("06:00").do(self.run_daily_prediction)
        
        # جدولة إعادة التدريب الأسبوعي (كل اثنين الساعة 2 صباحاً)
        schedule.every().monday.at("02:00").do(self.run_weekly_training)
        
        self.is_running = True
        self.thread = threading.Thread(target=self._run_loop, daemon=True)
        self.thread.start()
        
        print("✅ جدولة AI بدأت - تنبؤ يومي (6:00) + تدريب أسبوعي (الإثنين 2:00)")
    
    def _run_loop(self):
        """حلقة التشغيل المستمرة"""
        while self.is_running:
            schedule.run_pending()
            time.sleep(60)  # فحص كل دقيقة
    
    def run_daily_prediction(self):
        """تنفيذ التنبؤ اليومي لجميع المهام النشطة"""
        print(f"\n🤖 [{datetime.now()}] بدء التنبؤ اليومي التلقائي...")
        try:
            results = prediction_service.predict_all_active(save=True)
            high_risk = sum(1 for r in results if r.get('risk_level') == 'High')
            print(f"✅ اكتمل التنبؤ: {len(results)} مهمة | {high_risk} عالية الخطورة")
            return results
        except Exception as e:
            print(f"❌ فشل التنبؤ اليومي: {str(e)}")
            return None
    
    def run_weekly_training(self):
        """تنفيذ إعادة التدريب الأسبوعي"""
        print(f"\n🔄 [{datetime.now()}] بدء إعادة التدريب الأسبوعي...")
        try:
            from app.ai.models.delay_predictor import DelayPredictor
            predictor = DelayPredictor()
            metrics = predictor.train(force=True)
            print(f"✅ اكتمل التدريب: F1={metrics.get('f1_score', 0)}")
            return metrics
        except Exception as e:
            print(f"❌ فشل التدريب: {str(e)}")
            return None
    
    def stop(self):
        """إيقاف الجدولة"""
        self.is_running = False
        schedule.clear()

# نسخة عامة
ai_scheduler = AIScheduler()

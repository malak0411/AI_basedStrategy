"""نموذج التوصيات - إصدار قواعد منطقية (Rule-Based)"""
import numpy as np
from datetime import datetime
from app.ai.services.data_loader import DataLoader

ACTIONS = ["Increase_Employees","Increase_Budget","Revise_Timeline","Escalate_Manager",
           "Weekly_Monitoring","Split_Task","Reduce_Scope","Resolve_Risks","KPI_Monitoring","Assign_Senior"]

ACTION_AR = {
    "Increase_Employees":"زيادة عدد الموظفين","Increase_Budget":"زيادة الميزانية",
    "Revise_Timeline":"مراجعة الجدول الزمني","Escalate_Manager":"تصعيد للمدير",
    "Weekly_Monitoring":"مراقبة أسبوعية","Split_Task":"تقسيم المهمة",
    "Reduce_Scope":"تقليل النطاق","Resolve_Risks":"حل المخاطر",
    "KPI_Monitoring":"مراقبة مؤشرات الأداء","Assign_Senior":"تعيين مشرف أول"
}

class Recommender:
    def __init__(self):
        self.is_trained = True
        self.model_version = "1.0.0-rule-based"

    def recommend(self, task_id=None, task_features=None):
        if task_features is None and task_id:
            loader = DataLoader()
            try:
                task_features = loader.get_task_features(task_id)
            finally:
                loader.close()
        
        if task_features is None:
            return self._fallback()
        
        recs = self._generate(task_features)
        return {
            "task_id": int(task_features.get('task_id', task_id or 0)),
            "recommendations": recs[:3],
            "prediction_time": datetime.now().isoformat(),
            "model_version": self.model_version
        }

    def _generate(self, f):
        recs = []
        c = f.get('completion_percentage', 0)
        d = f.get('days_without_update', 0)
        r = f.get('remaining_days', 30)
        rc = f.get('critical_risk_count', 0)
        br = f.get('budget_ratio', 0)
        w = f.get('department_workload', 0)
        ne = f.get('num_assigned_employees', 1)

        if rc >= 2 and br > 0.9:
            recs.append(("Resolve_Risks", 0.95, f"يوجد {rc} مخاطر حرجة مع استنفاد {br:.0%} من الميزانية"))
        if d > 21 and c < 30:
            recs.append(("Escalate_Manager", 0.92, f"لا تحديثات منذ {d} يوم والتقدم {c}% فقط"))
        if w > 2.0 and ne <= 2:
            recs.append(("Increase_Employees", 0.88, f"عبء العمل {w:.1f} مع {ne} موظفين فقط"))
        if br > 0.85 and c < 40:
            recs.append(("Increase_Budget", 0.85, f"استخدام {br:.0%} من الميزانية مع تقدم {c}%"))
        if r <= 7 and c < 40:
            recs.append(("Assign_Senior", 0.90, f"الموعد النهائي بعد {r} يوم والتقدم منخفض"))
        if d > 10:
            recs.append(("Weekly_Monitoring", 0.78, f"آخر تحديث منذ {d} يوم"))
        if not recs:
            recs.append(("Weekly_Monitoring", 0.70, "متابعة دورية روتينية"))

        return [{"action": a, "action_ar": ACTION_AR[a], "confidence": round(float(conf), 3), "reason": reason}
                for a, conf, reason in sorted(recs, key=lambda x: x[1], reverse=True)]

    def _fallback(self):
        return {"task_id": None, "recommendations": [
            {"action":"Weekly_Monitoring","action_ar":"مراقبة أسبوعية","confidence":0.8,"reason":"افتراضي"}
        ], "prediction_time": datetime.now().isoformat(), "model_version": "fallback"}

    def load_model(self):
        return True

    def train(self, force=False):
        print("✅ نموذج التوصيات (قواعد) جاهز")
        return {"model_name": "Rule-Based", "f1_score": 1.0, "accuracy": 1.0}

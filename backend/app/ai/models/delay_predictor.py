import numpy as np
import pandas as pd
import joblib
import os
from datetime import datetime


from xgboost import XGBClassifier
from sklearn.ensemble import RandomForestClassifier


try:
    from lightgbm import LGBMClassifier
    LIGHTGBM_AVAILABLE = True
except ImportError:
    LIGHTGBM_AVAILABLE = False


from sklearn.model_selection import StratifiedKFold


from app.ai.services.data_loader import DataLoader
from app.ai.services.feature_engineer import FeatureEngineer
from app.ai.services.model_evaluator import ModelEvaluator


MODELS_DIR = os.path.join(os.path.dirname(__file__), '..', '..', '..', 'models')
FEATURE_NAMES_FILE = os.path.join(MODELS_DIR, 'feature_names.json')




class DelayPredictor:


    def __init__(self):
        self.model = None
        self.feature_engineer = FeatureEngineer()
        self.feature_names = []
        self.model_version = "2.0.0"
        self.training_date = None
        self.metrics = {}
        self.is_trained = False


        self.model_path = os.path.join(MODELS_DIR, 'delay_model.pkl')
        self.engineer_path = os.path.join(MODELS_DIR, 'delay_feature_engineer.pkl')


    def train(self, force=False):


        print("\n" + "=" * 60)
        print(" تدريب نموذج التنبؤ بتأخير المهام - النسخة 2.0")
        print("=" * 60)


        if not force and self.load_model():
            print(" النموذج مدرب مسبقاً")
            return self.metrics


        print("\n الخطوة 1: تحميل البيانات")
        loader = DataLoader()
        try:
            tasks = loader.load_all_tasks()
            df = loader.extract_features(tasks)
        finally:
            loader.close()


        if df.empty or len(df) < 10:
            print(" عدد البيانات غير كافٍ للتدريب (أقل من 10)")
            return None


        print(f" عدد السجلات: {len(df)}")
        if 'is_delayed' in df.columns:
            delayed_count = int((df['is_delayed'] == 1).sum())
            normal_count = int((df['is_delayed'] == 0).sum())
            print(f" تأخير: {delayed_count} | غير متأخر: {normal_count}")


        self.feature_names = [c for c in df.columns if c not in ['task_id', 'is_delayed', 'delay_days']]


        print("\n الخطوة 2: معالجة الميزات")
        X_train, X_test, y_train, y_test = self.feature_engineer.prepare_data(df)


        print("\n الخطوة 3: تدريب ومقارنة النماذج")
        evaluator = ModelEvaluator()
        cv = StratifiedKFold(n_splits=5, shuffle=True, random_state=42)


        models = {
            'XGBoost': XGBClassifier(
                n_estimators=200,
                max_depth=6,
                learning_rate=0.08,
                subsample=0.85,
                colsample_bytree=0.85,
                min_child_weight=2,
                gamma=0.1,
                reg_alpha=0.1,
                reg_lambda=1.5,
                random_state=42,
                eval_metric='logloss'
            ),
            'Random Forest': RandomForestClassifier(
                n_estimators=150,
                max_depth=12,
                min_samples_split=3,
                random_state=42,
                class_weight='balanced'
            )
        }


        if LIGHTGBM_AVAILABLE:
            models['LightGBM'] = LGBMClassifier(
                n_estimators=200,
                max_depth=6,
                learning_rate=0.08,
                subsample=0.85,
                colsample_bytree=0.85,
                random_state=42,
                verbose=-1,
                class_weight='balanced'
            )


        for name, model in models.items():
            try:
                model.fit(X_train, y_train)
                evaluator.evaluate_model(model, X_test, y_test, model_name=name, cv=cv)
            except Exception as e:
                print(f"   فشل تدريب {name}: {str(e)}")


        evaluator.compare_models()
        best_result = evaluator.select_best()


        if best_result:
            self.model = models[best_result['model_name']]
            self.metrics = best_result
            self.training_date = datetime.now()
            self.is_trained = True
            self._save_model()


        return self.metrics


    def predict(self, task_id=None, task_features=None):


        if task_features is None and task_id is not None:
            task_features = self._get_features_for_task(task_id)


        if task_features is None:
            return self._empty_prediction(task_id)


        is_completed = int(task_features.get('is_completed', 0))
        days_overdue = int(task_features.get('days_overdue', 0))
        delay_days = int(task_features.get('delay_days', 0))


        is_currently_delayed = False
        if days_overdue > 0 and is_completed == 0:
            is_currently_delayed = True
        elif delay_days > 0:
            is_currently_delayed = True


        model_prob = 0.0
        model_confidence = 0.0
        model_used = False


        if not self.is_trained:
            self.load_model()


        if self.is_trained and self.model is not None:
            try:
                X = self.feature_engineer.transform_single(task_features)
                proba = self.model.predict_proba(X)[0]
                model_prob = float(proba[1]) if len(proba) > 1 else float(proba[0])
                model_confidence = float(max(proba))
                model_used = True
            except Exception as e:
                print(f" فشل التنبؤ من النموذج: {str(e)}")
                model_used = False


        if is_currently_delayed:
            if days_overdue > 0:
                delay_prob = max(model_prob, 0.95)
            else:
                delay_prob = max(model_prob, 0.90)
            risk_level = "High"
        else:
            delay_prob = model_prob
            if delay_prob > 0.75:
                risk_level = "High"
            elif delay_prob > 0.50:
                risk_level = "Medium"
            elif delay_prob > 0.25:
                risk_level = "Low"
            else:
                risk_level = "Normal"


        top_factors = self._get_top_factors(task_features)


        return {
            "task_id": task_features.get('task_id', task_id),
            "delay_probability": round(delay_prob, 3),
            "risk_level": risk_level,
            "confidence": round(model_confidence, 3) if model_used else 0.0,
            "top_factors": top_factors,
            "is_currently_delayed": is_currently_delayed,
            "days_overdue": days_overdue,
            "delay_days": delay_days,
            "prediction_time": datetime.now().isoformat(),
            "model_version": self.model_version,
            "model_used": model_used
        }


    def _get_features_for_task(self, task_id):
        loader = DataLoader()
        try:
            return loader.get_task_features(task_id)
        finally:
            loader.close()


    def _get_top_factors(self, features):
        factors = []


        completion = float(features.get('completion_percentage', 0))
        expected = float(features.get('expected_progress', 0))
        progress_gap = float(features.get('progress_gap', 0))
        days_overdue = int(features.get('days_overdue', 0))
        delay_days = int(features.get('delay_days', 0))
        remaining = int(features.get('remaining_days', 30))
        days_without = int(features.get('days_without_update', 0))
        critical_risks = int(features.get('critical_risk_count', 0))
        budget_ratio = float(features.get('budget_ratio', 0))
        num_employees = int(features.get('num_assigned_employees', 0))
        workload = float(features.get('department_workload', 0))


        if days_overdue > 0:
            factors.append({
                "feature": f"المهمة متأخرة فعلياً بـ {days_overdue} يوم",
                "importance": 0.40
            })


        if delay_days > 0 and days_overdue == 0:
            factors.append({
                "feature": f"اكتملت المهمة بعد موعدها بـ {delay_days} يوم",
                "importance": 0.35
            })


        if progress_gap < -20:
            factors.append({
                "feature": f"التقدم متأخر عن المتوقع بـ {abs(int(progress_gap))}%",
                "importance": 0.30
            })


        if completion < 30 and expected > 30:
            factors.append({
                "feature": f"نسبة الإنجاز منخفضة ({int(completion)}%) مقارنة بالمدة المنقضية",
                "importance": 0.28
            })


        if days_without > 14:
            factors.append({
                "feature": f"لا تحديثات منذ {days_without} يوم",
                "importance": 0.22
            })


        if critical_risks > 0:
            factors.append({
                "feature": f"يوجد {critical_risks} مخاطر حرجة",
                "importance": 0.18
            })


        if 0 < remaining < 7 and completion < 50:
            factors.append({
                "feature": f"الموعد النهائي بعد {remaining} يوم والتقدم ضعيف",
                "importance": 0.15
            })


        if budget_ratio > 0.9:
            factors.append({
                "feature": f"الميزانية مستنفدة ({int(budget_ratio * 100)}%)",
                "importance": 0.10
            })


        if num_employees < 2 and workload > 1.5:
            factors.append({
                "feature": f"عدد الموظفين غير كافٍ ({num_employees})",
                "importance": 0.08
            })


        if not factors:
            factors.append({
                "feature": "المهمة تسير بشكل طبيعي",
                "importance": 0.5
            })


        return factors[:4]


    def _empty_prediction(self, task_id=None):
        return {
            "task_id": int(task_id) if task_id else None,
            "delay_probability": 0.0,
            "risk_level": "Unknown",
            "confidence": 0.0,
            "top_factors": [{"feature": "لا يمكن التنبؤ - المهمة غير موجودة", "importance": 0.0}],
            "is_currently_delayed": False,
            "days_overdue": 0,
            "delay_days": 0,
            "prediction_time": datetime.now().isoformat(),
            "model_version": self.model_version,
            "model_used": False
        }


    def _save_model(self):
        os.makedirs(MODELS_DIR, exist_ok=True)


        joblib.dump({
            'model': self.model,
            'model_version': self.model_version,
            'training_date': self.training_date.isoformat() if self.training_date else None,
            'metrics': self.metrics,
            'feature_names': self.feature_names
        }, self.model_path)


        self.feature_engineer.save(self.engineer_path)


        print(f"\n تم حفظ النموذج في: {self.model_path}")


    def load_model(self):
        if os.path.exists(self.model_path):
            try:
                data = joblib.load(self.model_path)
                self.model = data.get('model')
                self.model_version = data.get('model_version', '1.0.0')
                self.metrics = data.get('metrics', {})
                self.feature_names = data.get('feature_names', [])
                self.is_trained = True


                self.feature_engineer.load(self.engineer_path)


                print(f" تم تحميل النموذج المدرب - الإصدار: {self.model_version}")
                return True
            except Exception as e:
                print(f" فشل تحميل النموذج: {str(e)}")
                self.is_trained = False
                self.model = None


        return False

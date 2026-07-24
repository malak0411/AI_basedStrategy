
from pyexpat import features

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
    print(" LightGBM غير متوفر")

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
        self.model_version = "1.0.0"
        self.training_date = None
        self.metrics = {}
        self.is_trained = False
        
        self.model_path = os.path.join(MODELS_DIR, 'delay_model.pkl')
        self.engineer_path = os.path.join(MODELS_DIR, 'delay_feature_engineer.pkl')
    
    def train(self, force=False):
       
        print("\n" + "=" * 60)
        print(" تدريب نموذج التنبؤ بتأخير المهام")
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
        
        if len(df) < 10:
            print(" عدد البيانات غير كافٍ للتدريب (أقل من 10)")
            return None
        
        self.feature_names = [c for c in df.columns if c not in ['task_id', 'is_delayed']]
        
        print("\n الخطوة 2: معالجة الميزات")
        X_train, X_test, y_train, y_test = self.feature_engineer.prepare_data(df)
        
        print("\n الخطوة 3: تدريب ومقارنة النماذج")
        evaluator = ModelEvaluator()
        cv = StratifiedKFold(n_splits=5, shuffle=True, random_state=42)
        
        models = {
            'XGBoost': XGBClassifier(
                n_estimators=100, max_depth=5, learning_rate=0.1,
                random_state=42, eval_metric='logloss'
            ),
            'Random Forest': RandomForestClassifier(
                n_estimators=100, max_depth=10, random_state=42
            )
        }
        
        if LIGHTGBM_AVAILABLE:
            models['LightGBM'] = LGBMClassifier(
                n_estimators=100, max_depth=5, learning_rate=0.1,
                random_state=42, verbose=-1
            )
        
        for name, model in models.items():
            try:
                model.fit(X_train, y_train)
                evaluator.evaluate_model(model, X_test, y_test, model_name=name, cv=cv)
            except Exception as e:
                print(f"   ⚠️ فشل تدريب {name}: {str(e)}")
        
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
       
        if not self.is_trained:
            if not self.load_model():
                return self._fallback_prediction()
        
        if task_features is None and task_id is not None:
            task_features = self._get_features_for_task(task_id)
        
        if task_features is None:
            return self._fallback_prediction()
        
        try:
            X = self.feature_engineer.transform_single(task_features)
            
            proba = self.model.predict_proba(X)[0]
            delay_prob = proba[1] if len(proba) > 1 else proba[0]
            
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
                "confidence": round(max(proba), 3),
                "top_factors": top_factors,
                "prediction_time": datetime.now().isoformat(),
                "model_version": self.model_version
            }
            
        except Exception as e:
            print(f" خطأ في التنبؤ: {str(e)}")
            return self._fallback_prediction()
    
    def _get_features_for_task(self, task_id):
        loader = DataLoader()
        try:
            tasks = loader.load_all_tasks()
            for task in tasks:
                if task.task_id == task_id:
                    return loader._extract_single_task(task)
        finally:
            loader.close()
        return None
    
    def _get_top_factors(self, features):
        factors = []
    
        completion = float(features.get('completion_percentage', 100))
        days_without = int(features.get('days_without_update', 0))
        critical_risks = int(features.get('critical_risk_count', 0))
        remaining = int(features.get('remaining_days', 30))
        budget_ratio = float(features.get('budget_ratio', 0))
        num_employees = int(features.get('num_assigned_employees', 0))
        workload = float(features.get('department_workload', 0))
    
        if completion < 30:
            factors.append({"feature": "نسبة الإنجاز منخفضة جداً ({}%)".format(int(completion)), "importance": 0.32})
        if days_without > 14:
            factors.append({"feature": "لا تحديثات منذ {} يوم".format(days_without), "importance": 0.25})
        if critical_risks > 0:
            factors.append({"feature": "يوجد {} مخاطر حرجة".format(critical_risks), "importance": 0.18})
        if remaining < 7 and completion < 50:
            factors.append({"feature": "الموعد النهائي قريب ({} يوم) والتقدم ضعيف".format(remaining), "importance": 0.15})
        if budget_ratio > 0.9:
            factors.append({"feature": "الميزانية مستنفدة تقريباً ({}%)".format(int(budget_ratio * 100)), "importance": 0.10})
        if num_employees < 2 and workload > 1.5:
            factors.append({"feature": "عدد الموظفين غير كافٍ ({} موظف)".format(num_employees), "importance": 0.08})
        if completion >= 50:
            factors.append({"feature": "التقدم جيد ({}%) - استمرار المتابعة".format(int(completion)), "importance": 0.05})
    
        if not factors:
            factors.append({"feature": "المهمة تسير بشكل طبيعي", "importance": 0.5})

        
        return factors[:3] 
    
    def _fallback_prediction(self):
        return {
            "task_id": task_features.get('task_id', task_id),
            "delay_probability": round(float(delay_prob), 3),
            "risk_level": risk_level,
            "confidence": round(float(max(proba)), 3),
            "top_factors": top_factors,
            "prediction_time": datetime.now().isoformat(),
            "model_version": self.model_version
        }

    
    def _save_model(self):
        os.makedirs(MODELS_DIR, exist_ok=True)
        
        joblib.dump({
            'model': self.model,
            'model_version': self.model_version,
            'training_date': self.training_date.isoformat(),
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
                
                print(" تم تحميل النموذج المدرب")
                return True
            except Exception as e:
                print(f" فشل تحميل النموذج: {str(e)}")
        
        return False

import os
import joblib
from datetime import datetime, date
from xgboost import XGBClassifier
from sklearn.ensemble import RandomForestClassifier
from sklearn.model_selection import StratifiedKFold
from app.ai.services.data_loader import DataLoader
from app.ai.services.feature_engineer import FeatureEngineer
from app.ai.services.model_evaluator import ModelEvaluator

try:
    from lightgbm import LGBMClassifier
    LIGHTGBM_AVAILABLE = True
except ImportError:
    LIGHTGBM_AVAILABLE = False

MODELS_DIR = os.path.join(os.path.dirname(__file__), '..', '..', '..', 'models')


class DelayPredictor:

    def __init__(self):
        self.model = None
        self.feature_engineer = FeatureEngineer()
        self.model_version = "3.1.0"
        self.training_date = None
        self.metrics = {}
        self.is_trained = False
        self.model_path = os.path.join(MODELS_DIR, 'delay_model.pkl')
        self.engineer_path = os.path.join(MODELS_DIR, 'delay_feature_engineer.pkl')

    def train(self, force=False):
        if not force and self.load_model():
            return self.metrics

        loader = DataLoader()
        try:
            tasks = loader.load_tasks_for_training()
            df = loader.extract_features(tasks)
        finally:
            loader.close()

        if df.empty or len(df) < 10:
            return None

        X_train, X_test, y_train, y_test = self.feature_engineer.prepare_data(df)

        evaluator = ModelEvaluator()
        cv = StratifiedKFold(n_splits=5, shuffle=True, random_state=42)

        models = {
            'XGBoost': XGBClassifier(
                n_estimators=250, max_depth=6, learning_rate=0.08,
                subsample=0.85, colsample_bytree=0.85, min_child_weight=2,
                gamma=0.1, reg_alpha=0.1, reg_lambda=1.5,
                random_state=42, eval_metric='logloss',
            ),
            'Random Forest': RandomForestClassifier(
                n_estimators=200, max_depth=14, min_samples_split=3,
                random_state=42, class_weight='balanced',
            ),
        }
        if LIGHTGBM_AVAILABLE:
            models['LightGBM'] = LGBMClassifier(
                n_estimators=250, max_depth=6, learning_rate=0.08,
                subsample=0.85, colsample_bytree=0.85,
                random_state=42, verbose=-1, class_weight='balanced',
            )

        for name, model in models.items():
            try:
                model.fit(X_train, y_train)
                evaluator.evaluate_model(model, X_test, y_test, model_name=name, cv=cv)
            except Exception as e:
                print(f"train fail {name}: {e}")

        evaluator.compare_models()
        best = evaluator.select_best()

        if best:
            self.model = models[best['model_name']]
            self.metrics = best
            self.training_date = datetime.now()
            self.is_trained = True
            self._save_model()
            self._sync_model_registry()

        return self.metrics

    def predict(self, task_id=None, task_features=None):
        if task_features is None and task_id is not None:
            task_features = self._get_features_for_task(task_id)
        if task_features is None:
            return self._empty_prediction(task_id)

        state = self._determine_task_state(task_features)
        delay_prob = self._compute_delay_probability(task_features, state)
        risk_level = self._risk_from_state(state, delay_prob)
        top_factors = self._get_top_factors(task_features, state)

        return {
            "task_id": task_features.get('task_id', task_id),
            "task_state": state["state"],
            "task_state_label": state["label"],
            "delay_category": state["delay_category"],
            "delay_category_label": state["delay_category_label"],
            "delay_probability": round(delay_prob, 3),
            "risk_level": risk_level,
            "confidence": round(state.get("confidence", 0.0), 3),
            "is_currently_delayed": state["is_really_delayed"],
            "days_overdue": state["effective_overdue"],
            "delay_days": state["delay_days"],
            "completion_status": state["completion_status"],
            "is_active": state["is_active"],
            "is_completed": state["is_completed"],
            "top_factors": top_factors,
            "prediction_time": datetime.now().isoformat(),
            "model_version": self.model_version,
            "model_used": state["model_used"],
        }

    def _determine_task_state(self, f):
        is_completed = int(f.get('is_completed', 0))
        status_id = int(f.get('status_id', 0))
        days_overdue = int(f.get('days_overdue', 0))
        delay_days = int(f.get('delay_days', 0))
        is_active = status_id in (5, 6, 8, 16, 26)

        computed_overdue = self._compute_overdue_from_dates(f, is_completed)
        effective_overdue = max(days_overdue, computed_overdue)

        is_really_delayed = (effective_overdue > 0 and is_completed == 0) or delay_days > 0

        completion_status = None
        if is_completed:
            if delay_days > 0:
                completion_status = "completed_late"
            else:
                completion_status = "completed_on_time"

        delay_category = 0
        delay_category_label = "في الموعد"
        effective_delay = delay_days if is_completed else effective_overdue
        if effective_delay > 0:
            if effective_delay <= 7:
                delay_category = 1
                delay_category_label = "تأخير بسيط"
            elif effective_delay <= 30:
                delay_category = 2
                delay_category_label = "تأخير متوسط"
            else:
                delay_category = 3
                delay_category_label = "تأخير شديد"

        if is_completed:
            state = "completed_late" if delay_days > 0 else "completed_on_time"
            label = "مكتملة متأخرة" if delay_days > 0 else "مكتملة في الوقت"
        elif status_id == 5:
            state = "blocked"
            label = "معلقة (قرار إداري)"
        elif status_id == 16:
            state = "blocked_no_staff"
            label = "معلقة (بدون موظفين)"
        elif status_id == 8:
            state = "in_review"
            label = "قيد المراجعة"
        elif is_really_delayed:
            state = "delayed"
            label = f"متأخرة بـ {effective_overdue} يوم"
        else:
            state = "active"
            label = "قيد التنفيذ"

        model_used, model_prob, model_conf = self._run_model(f)

        return {
            "state": state,
            "label": label,
            "is_active": is_active,
            "is_completed": bool(is_completed),
            "is_really_delayed": is_really_delayed,
            "effective_overdue": effective_overdue,
            "delay_days": delay_days,
            "completion_status": completion_status,
            "delay_category": delay_category,
            "delay_category_label": delay_category_label,
            "model_used": model_used,
            "model_prob": model_prob,
            "confidence": model_conf,
            "status_id": status_id,
        }

    def _compute_overdue_from_dates(self, f, is_completed):
        if is_completed:
            return 0
        end_date_raw = f.get('end_date')
        if not end_date_raw:
            return 0
        end_date_val = self._parse_date(end_date_raw)
        if not end_date_val:
            return 0
        today = date.today()
        if end_date_val < today:
            return (today - end_date_val).days
        return 0

    def _parse_date(self, raw):
        if isinstance(raw, datetime):
            return raw.date()
        if isinstance(raw, date):
            return raw
        if isinstance(raw, str):
            try:
                if 'T' in raw:
                    return datetime.fromisoformat(raw).date()
                return date.fromisoformat(raw)
            except Exception:
                return None
        return None

    def _run_model(self, f):
        if not self.is_trained:
            self.load_model()
        if not self.is_trained or self.model is None:
            return False, 0.0, 0.0
        try:
            X = self.feature_engineer.transform_single(f)
            proba = self.model.predict_proba(X)[0]
            prob = float(proba[1]) if len(proba) > 1 else float(proba[0])
            conf = float(max(proba))
            return True, prob, conf
        except Exception as e:
            print(f"model predict fail: {e}")
            return False, 0.0, 0.0

    def _compute_delay_probability(self, f, state):
        model_prob = state["model_prob"]
        status_id = state["status_id"]
        is_completed = state["is_completed"]

        if state["is_really_delayed"]:
            return max(model_prob, 0.95)

        if is_completed:
            if state["completion_status"] == "completed_late":
                return max(model_prob, 0.90)
            return min(model_prob, 0.10)

        if status_id == 16:
            return max(model_prob, 0.55)
        if status_id == 5:
            return max(model_prob, 0.45)
        if status_id == 8:
            return max(model_prob, 0.20)

        remaining = int(f.get('remaining_days', 999))
        progress = float(f.get('completion_percentage', 0))
        gap = float(f.get('progress_gap', 0))

        if remaining <= 3 and progress < 50:
            return max(model_prob, 0.70)
        if remaining <= 7 and progress < 60:
            return max(model_prob, 0.55)
        if gap < -30:
            return max(model_prob, 0.50)
        if gap < -15:
            return max(model_prob, 0.35)

        return model_prob

    def _risk_from_state(self, state, delay_prob):
        if state["is_really_delayed"]:
            return "High"
        if state["completion_status"] == "completed_on_time":
            return "Normal"
        if state["completion_status"] == "completed_late":
            return "Medium"
        if state["status_id"] in (5, 16):
            return "High" if delay_prob >= 0.55 else "Medium"
        if delay_prob > 0.75:
            return "High"
        if delay_prob > 0.50:
            return "Medium"
        if delay_prob > 0.25:
            return "Low"
        return "Normal"

    def _get_top_factors(self, f, state):
        factors = []

        if state["is_completed"]:
            if state["completion_status"] == "completed_late":
                factors.append({"feature": f"اكتملت المهمة متأخرة بـ {state['delay_days']} يوم", "importance": 0.40})
            else:
                factors.append({"feature": "المهمة مكتملة في الوقت المحدد", "importance": 0.50})
            return factors[:5]

        if state["is_really_delayed"]:
            factors.append({"feature": f"المهمة متأخرة فعلياً بـ {state['effective_overdue']} يوم", "importance": 0.40})

        status_id = state["status_id"]
        if status_id == 16:
            factors.append({"feature": "المهمة معلقة لعدم إسناد موظفين", "importance": 0.35})
        elif status_id == 5:
            factors.append({"feature": "المهمة معلقة بقرار إداري", "importance": 0.30})
        elif status_id == 8:
            factors.append({"feature": "المهمة قيد المراجعة", "importance": 0.20})

        blocking = int(f.get('num_blocking_deps', 0))
        if blocking > 0:
            factors.append({"feature": f"محجوبة بـ {blocking} اعتمادية غير مكتملة", "importance": 0.32})

        gap = float(f.get('progress_gap', 0))
        if gap < -20:
            factors.append({"feature": f"التقدم متأخر عن المتوقع بـ {abs(int(gap))}%", "importance": 0.28})

        completion = float(f.get('completion_percentage', 0))
        expected = float(f.get('expected_progress', 0))
        if completion < 30 and expected > 30:
            factors.append({"feature": f"نسبة الإنجاز منخفضة ({int(completion)}%)", "importance": 0.25})

        remaining = int(f.get('remaining_days', 999))
        if 0 < remaining <= 7 and completion < 60:
            factors.append({"feature": f"الموعد النهائي بعد {remaining} يوم والتقدم ضعيف", "importance": 0.25})

        critical = int(f.get('critical_risk_count', 0))
        if critical > 0:
            factors.append({"feature": f"يوجد {critical} مخاطر حرجة", "importance": 0.20})

        unmitigated = int(f.get('unmitigated_risk_count', 0))
        if unmitigated > 0:
            factors.append({"feature": f"{unmitigated} مخاطر بدون إجراءات معالجة", "importance": 0.18})

        days_without = int(f.get('days_without_update', 0))
        if days_without > 14:
            factors.append({"feature": f"لا تحديثات منذ {days_without} يوم", "importance": 0.20})

        overloaded = int(f.get('overloaded', 0))
        if overloaded > 0:
            max_tasks = int(f.get('max_employee_active_tasks', 0))
            factors.append({"feature": f"الموظفون محمّلون (أعلى حِمل: {max_tasks} مهمة)", "importance": 0.18})

        if not factors:
            factors.append({"feature": "المهمة تسير بشكل طبيعي", "importance": 0.5})

        return factors[:5]

    def _get_features_for_task(self, task_id):
        loader = DataLoader()
        try:
            return loader.get_task_features(task_id)
        finally:
            loader.close()

    def _empty_prediction(self, task_id=None):
        return {
            "task_id": int(task_id) if task_id else None,
            "task_state": "unknown",
            "task_state_label": "غير معروف",
            "delay_category": 0,
            "delay_category_label": "غير محدد",
            "delay_probability": 0.0,
            "risk_level": "Unknown",
            "confidence": 0.0,
            "is_currently_delayed": False,
            "days_overdue": 0,
            "delay_days": 0,
            "completion_status": None,
            "is_active": False,
            "is_completed": False,
            "top_factors": [{"feature": "المهمة غير موجودة", "importance": 0.0}],
            "prediction_time": datetime.now().isoformat(),
            "model_version": self.model_version,
            "model_used": False,
        }

    def _save_model(self):
        os.makedirs(MODELS_DIR, exist_ok=True)
        joblib.dump({
            'model': self.model,
            'model_version': self.model_version,
            'training_date': self.training_date.isoformat() if self.training_date else None,
            'metrics': self.metrics,
        }, self.model_path)
        self.feature_engineer.save(self.engineer_path)

    def _sync_model_registry(self):
        from app.database import SessionLocal
        from app.models import AIModel
        db = SessionLocal()
        try:
            db.query(AIModel).update({AIModel.is_active: False})
            row = db.query(AIModel).filter(
                AIModel.name == 'Delay Predictor',
                AIModel.version == self.model_version,
            ).first()
            if not row:
                row = AIModel(name='Delay Predictor', version=self.model_version)
                db.add(row)
            row.description = f"{self.metrics.get('model_name', 'Ensemble')} delay classifier"
            row.accuracy = round(self.metrics.get('accuracy', 0) * 100, 2)
            row.f1_score = round(self.metrics.get('f1_score', 0) * 100, 2)
            row.deployed_at = datetime.now()
            row.is_active = True
            db.commit()
        except Exception as e:
            print(f"registry sync fail: {e}")
            db.rollback()
        finally:
            db.close()

    def load_model(self):
        if not os.path.exists(self.model_path):
            return False
        try:
            data = joblib.load(self.model_path)
            self.model = data.get('model')
            self.model_version = data.get('model_version', '1.0.0')
            self.metrics = data.get('metrics', {})
            self.is_trained = True
            self.feature_engineer.load(self.engineer_path)
            return True
        except Exception as e:
            print(f"load fail: {e}")
            self.is_trained = False
            self.model = None
            return False

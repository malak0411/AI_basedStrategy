import json
from datetime import datetime, date
from app.database import SessionLocal
from app.models import (
    Risk, OperationalTask, DictStatus, DictRiskLevel
)




RISK_THRESHOLD = 0.5
OVERDUE_DAYS_CRITICAL = 14
CLOSED_RISK_CODES = ['resolved', 'closed', 'completed', 'done']




class RiskDetectionService:


    def __init__(self):
        self.db = SessionLocal()


    def detect_from_prediction(self, task_id, prediction_data, created_by=None):
        task = self.db.query(OperationalTask).filter(
            OperationalTask.task_id == task_id
        ).first()


        if not task:
            return {
                "detected": False,
                "reason": "Task not found",
                "task_id": int(task_id)
            }


        is_currently_delayed = bool(prediction_data.get('is_currently_delayed', False))
        days_overdue = int(prediction_data.get('days_overdue', 0))
        delay_probability = float(prediction_data.get('delay_probability', 0))


        if not is_currently_delayed and delay_probability < RISK_THRESHOLD:
            return {
                "detected": False,
                "reason": f"delay_probability {delay_probability} below threshold",
                "task_id": int(task_id)
            }


        existing_risk = self._find_active_risk(task_id)


        probability = max(1, min(10, round(delay_probability * 10)))
        impact = self._calculate_impact(task, prediction_data, days_overdue)
        risk_score = probability * impact
        risk_level = self._get_risk_level(risk_score)


        if existing_risk:
            existing_risk.probability = probability
            existing_risk.impact = impact
            existing_risk.risk_score = risk_score
            if risk_level:
                existing_risk.risk_level_id = risk_level.risk_level_id
            existing_risk.updated_at = datetime.now()
            self.db.commit()
            self.db.refresh(existing_risk)


            return {
                "detected": True,
                "action": "updated",
                "risk_id": existing_risk.risk_id,
                "is_currently_delayed": is_currently_delayed,
                "days_overdue": days_overdue,
                "risk": self._serialize(existing_risk)
            }


        default_status = self._get_default_risk_status()


        if is_currently_delayed:
            risk_name = f"تأخير فعلي: {task.title[:180]}"
            if days_overdue > 0:
                risk_name = f"تأخير {days_overdue} يوم: {task.title[:150]}"
        else:
            risk_name = f"خطر محتمل: {task.title[:180]}"


        risk_description = self._build_description(task, prediction_data, is_currently_delayed, days_overdue)


        new_risk = Risk(
            task_id=task_id,
            name=risk_name[:255],
            description=risk_description,
            probability=probability,
            impact=impact,
            risk_score=risk_score,
            risk_level_id=risk_level.risk_level_id if risk_level else None,
            identified_by=created_by or task.created_by,
            identified_at=datetime.now(),
            target_date=task.end_date,
            status_id=default_status.status_id if default_status else None
        )


        self.db.add(new_risk)
        self.db.commit()
        self.db.refresh(new_risk)


        return {
            "detected": True,
            "action": "created",
            "risk_id": new_risk.risk_id,
            "is_currently_delayed": is_currently_delayed,
            "days_overdue": days_overdue,
            "risk": self._serialize(new_risk)
        }


    def detect_overdue_task(self, task_id, created_by=None):
        task = self.db.query(OperationalTask).filter(
            OperationalTask.task_id == task_id
        ).first()


        if not task:
            return {"detected": False, "reason": "Task not found"}


        if not task.end_date:
            return {"detected": False, "reason": "Task has no deadline"}


        if task.status_id == 19:
            return {"detected": False, "reason": "Task is completed"}


        today = date.today()
        if today <= task.end_date:
            return {"detected": False, "reason": "Task is not overdue"}


        days_overdue = (today - task.end_date).days


        existing_risk = self._find_active_risk(task_id)


        if days_overdue >= OVERDUE_DAYS_CRITICAL:
            probability = 10
        elif days_overdue >= 7:
            probability = 9
        else:
            probability = 8


        impact = self._calculate_impact(task, {}, days_overdue)
        risk_score = probability * impact
        risk_level = self._get_risk_level(risk_score)


        if existing_risk:
            existing_risk.probability = probability
            existing_risk.impact = impact
            existing_risk.risk_score = risk_score
            if risk_level:
                existing_risk.risk_level_id = risk_level.risk_level_id
            existing_risk.updated_at = datetime.now()
            self.db.commit()


            return {
                "detected": True,
                "action": "updated",
                "risk_id": existing_risk.risk_id,
                "days_overdue": days_overdue
            }


        default_status = self._get_default_risk_status()


        new_risk = Risk(
            task_id=task_id,
            name=f"تأخير {days_overdue} يوم: {task.title[:180]}"[:255],
            description=f"المهمة تجاوزت موعد التسليم ({task.end_date}) بـ {days_overdue} يوم دون إكمالها.",
            probability=probability,
            impact=impact,
            risk_score=risk_score,
            risk_level_id=risk_level.risk_level_id if risk_level else None,
            identified_by=created_by or task.created_by,
            identified_at=datetime.now(),
            target_date=task.end_date,
            status_id=default_status.status_id if default_status else None
        )


        self.db.add(new_risk)
        self.db.commit()
        self.db.refresh(new_risk)


        return {
            "detected": True,
            "action": "created",
            "risk_id": new_risk.risk_id,
            "days_overdue": days_overdue
        }


    def _find_active_risk(self, task_id):
        try:
            all_statuses = self.db.query(DictStatus.status_id, DictStatus.code).filter(
                DictStatus.category == 'risk'
            ).all()


            active_ids = [sid for sid, code in all_statuses if code not in CLOSED_RISK_CODES]


            if active_ids:
                return self.db.query(Risk).filter(
                    Risk.task_id == task_id,
                    Risk.status_id.in_(active_ids)
                ).first()
        except Exception:
            pass


        return None


    def _get_default_risk_status(self):
        status = self.db.query(DictStatus).filter(
            DictStatus.category == 'risk',
            DictStatus.code == 'mitigating'
        ).first()


        if status:
            return status


        status = self.db.query(DictStatus).filter(
            DictStatus.category == 'risk',
            DictStatus.is_default == True
        ).first()


        if status:
            return status


        return self.db.query(DictStatus).filter(
            DictStatus.category == 'risk'
        ).first()


    def _get_risk_level(self, risk_score):
        return self.db.query(DictRiskLevel).filter(
            DictRiskLevel.min_score <= risk_score,
            DictRiskLevel.max_score >= risk_score
        ).first()


    def _calculate_impact(self, task, prediction_data, days_overdue):
        impact = 3.0


        if task.priority_id == 1:
            impact += 2.0
        elif task.priority_id == 2:
            impact += 1.0


        if task.is_cross_functional:
            impact += 1.0


        if days_overdue > 14:
            impact += 2.0
        elif days_overdue > 7:
            impact += 1.5
        elif days_overdue > 0:
            impact += 1.0


        top_factors = prediction_data.get('top_factors', [])
        for factor in top_factors:
            importance = factor.get('importance', 0)
            if importance >= 0.30:
                impact += 1.0
            elif importance >= 0.20:
                impact += 0.5


        return max(1, min(10, int(round(impact))))


    def _build_description(self, task, prediction_data, is_delayed, days_overdue):
        parts = []


        if is_delayed and days_overdue > 0:
            parts.append(f"المهمة متأخرة فعلياً بـ {days_overdue} يوم.")


        probability = prediction_data.get('delay_probability', 0)
        parts.append(f"احتمال التأخير المتوقع: {round(probability * 100, 1)}%.")


        completion = task.actual_hours or 0
        estimated = task.estimated_hours or 0
        if estimated > 0:
            parts.append(f"نسبة الساعات المستهلكة: {round(float(completion) / float(estimated) * 100, 1)}%.")


        top_factors = prediction_data.get('top_factors', [])
        if top_factors:
            factors_text = "؛ ".join([f.get('feature', '') for f in top_factors[:3]])
            parts.append(f"العوامل الرئيسية: {factors_text}")


        return " ".join(parts)[:2000]


    def _serialize(self, risk):
        level = None
        if risk.risk_level_id:
            lvl = self.db.query(DictRiskLevel).filter(
                DictRiskLevel.risk_level_id == risk.risk_level_id
            ).first()
            if lvl:
                level = {
                    "risk_level_id": lvl.risk_level_id,
                    "code": lvl.code,
                    "name_ar": lvl.name_ar
                }


        status = None
        if risk.status_id:
            st = self.db.query(DictStatus).filter(
                DictStatus.status_id == risk.status_id
            ).first()
            if st:
                status = {
                    "status_id": st.status_id,
                    "code": st.code,
                    "name_ar": st.name_ar
                }


        return {
            "risk_id": risk.risk_id,
            "task_id": risk.task_id,
            "name": risk.name,
            "probability": risk.probability,
            "impact": risk.impact,
            "risk_score": risk.risk_score,
            "risk_level": level,
            "status": status,
            "identified_at": risk.identified_at.isoformat() if risk.identified_at else None,
            "target_date": risk.target_date.isoformat() if risk.target_date else None
        }


    def close(self):
        if self.db:
            self.db.close()

from datetime import datetime, date

from app.database import SessionLocal
from app.models import (
    Risk, OperationalTask, DictStatus, DictRiskLevel, RiskMitigation,
)
from app.ai.config.constants import (
    TASK_STATUS_COMPLETED,
    RISK_STATUS_MITIGATING,
    RISK_STATUS_RESOLVED,
    CLOSED_MITIGATION_STATUS_IDS,
    CLOSED_RISK_STATUS_IDS,
    PRIORITY_HIGH,
    PRIORITY_MEDIUM,
)

RISK_THRESHOLD = 0.5
OVERDUE_DAYS_CRITICAL = 14


class RiskDetectionService:

    def __init__(self, db=None):
        self.db = db or SessionLocal()
        self._owns_db = db is None

    def detect_from_prediction(self, task_id, prediction_data, created_by=None):
        task = self.db.query(OperationalTask).filter(
            OperationalTask.task_id == task_id
        ).first()
        if not task:
            return {"detected": False, "reason": "Task not found", "task_id": int(task_id)}

        if task.status_id == TASK_STATUS_COMPLETED:
            existing = self._find_active_risk(task_id)
            resolved = False
            if existing:
                status_info = self._evaluate_mitigation_status(existing.risk_id)
                resolved = self._maybe_resolve(existing, status_info)
            return {
                "detected": False,
                "reason": "task_completed",
                "task_id": int(task_id),
                "existing_risk_id": existing.risk_id if existing else None,
                "resolved_now": resolved,
            }

        is_delayed = bool(prediction_data.get('is_currently_delayed', False))
        days_overdue = int(prediction_data.get('days_overdue', 0))
        prob = float(prediction_data.get('delay_probability', 0))

        if not is_delayed and prob < RISK_THRESHOLD:
            return {
                "detected": False,
                "reason": f"below_threshold({prob})",
                "task_id": int(task_id),
            }

        existing = self._find_active_risk(task_id)
        probability = max(1, min(10, round(prob * 10)))
        impact = self._calculate_impact(task, prediction_data, days_overdue)
        score = probability * impact
        level = self._get_risk_level(score)

        if existing:
            existing.probability = probability
            existing.impact = impact
            existing.name = self._build_risk_name(task, is_delayed, days_overdue)[:255]
            existing.description = self._build_description(task, prediction_data, is_delayed, days_overdue)
            if level:
                existing.risk_level_id = level.risk_level_id
            existing.updated_at = datetime.now()
            self.db.commit()
            self.db.refresh(existing)
            status_info = self._evaluate_mitigation_status(existing.risk_id)
            self._maybe_resolve(existing, status_info)
            return {
                "detected": True,
                "action": "updated",
                "risk_id": existing.risk_id,
                "is_currently_delayed": is_delayed,
                "days_overdue": days_overdue,
                "risk": self._serialize(existing),
                "mitigation_status": status_info,
            }

        status = self._get_default_risk_status()
        name = self._build_risk_name(task, is_delayed, days_overdue)
        description = self._build_description(task, prediction_data, is_delayed, days_overdue)

        new_risk = Risk(
            task_id=task_id,
            name=name[:255],
            description=description,
            probability=probability,
            impact=impact,
            risk_level_id=level.risk_level_id if level else None,
            identified_by=created_by or task.created_by,
            identified_at=datetime.now(),
            target_date=task.end_date,
            status_id=status.status_id if status else RISK_STATUS_MITIGATING,
        )
        self.db.add(new_risk)
        self.db.commit()
        self.db.refresh(new_risk)

        return {
            "detected": True,
            "action": "created",
            "risk_id": new_risk.risk_id,
            "is_currently_delayed": is_delayed,
            "days_overdue": days_overdue,
            "risk": self._serialize(new_risk),
            "mitigation_status": self._evaluate_mitigation_status(new_risk.risk_id),
        }

    def reassess_all_active_risks(self):
        risks = self.db.query(Risk).filter(
            Risk.status_id.notin_(CLOSED_RISK_STATUS_IDS)
        ).all()
        reassessed = []
        for r in risks:
            status_info = self._evaluate_mitigation_status(r.risk_id)
            resolved = self._maybe_resolve(r, status_info)
            reassessed.append({
                "risk_id": r.risk_id,
                "mitigation_status": status_info,
                "resolved": resolved,
            })
        return reassessed

    def detect_overdue_task(self, task_id, created_by=None):
        task = self.db.query(OperationalTask).filter(
            OperationalTask.task_id == task_id
        ).first()
        if not task:
            return {"detected": False, "reason": "Task not found"}
        if not task.end_date:
            return {"detected": False, "reason": "No deadline"}
        if task.status_id == TASK_STATUS_COMPLETED:
            return {"detected": False, "reason": "Completed"}

        today = date.today()
        if today <= task.end_date:
            return {"detected": False, "reason": "Not overdue"}

        days_overdue = (today - task.end_date).days
        existing = self._find_active_risk(task_id)

        if days_overdue >= OVERDUE_DAYS_CRITICAL:
            probability = 10
        elif days_overdue >= 7:
            probability = 9
        else:
            probability = 8

        impact = self._calculate_impact(task, {}, days_overdue)
        score = probability * impact
        level = self._get_risk_level(score)

        if existing:
            existing.probability = probability
            existing.impact = impact
            if level:
                existing.risk_level_id = level.risk_level_id
            existing.updated_at = datetime.now()
            self.db.commit()
            return {
                "detected": True,
                "action": "updated",
                "risk_id": existing.risk_id,
                "days_overdue": days_overdue,
            }

        status = self._get_default_risk_status()
        new_risk = Risk(
            task_id=task_id,
            name=f"تأخير {days_overdue} يوم: {task.title[:180]}"[:255],
            description=f"المهمة تجاوزت موعد التسليم ({task.end_date}) بـ {days_overdue} يوم.",
            probability=probability,
            impact=impact,
            risk_level_id=level.risk_level_id if level else None,
            identified_by=created_by or task.created_by,
            identified_at=datetime.now(),
            target_date=task.end_date,
            status_id=status.status_id if status else RISK_STATUS_MITIGATING,
        )
        self.db.add(new_risk)
        self.db.commit()
        self.db.refresh(new_risk)
        return {
            "detected": True,
            "action": "created",
            "risk_id": new_risk.risk_id,
            "days_overdue": days_overdue,
        }

    def _evaluate_mitigation_status(self, risk_id):
        mits = self.db.query(RiskMitigation).filter(
            RiskMitigation.risk_id == risk_id
        ).all()
        if not mits:
            return {
                "total": 0, "completed": 0, "in_progress": 0,
                "overdue": 0, "all_complete": False, "completion_rate": 0.0,
            }
        today = date.today()
        completed = sum(1 for m in mits if m.status_id in CLOSED_MITIGATION_STATUS_IDS)
        overdue = sum(
            1 for m in mits
            if m.due_date and m.due_date < today and m.status_id not in CLOSED_MITIGATION_STATUS_IDS
        )
        return {
            "total": len(mits),
            "completed": completed,
            "in_progress": len(mits) - completed,
            "overdue": overdue,
            "all_complete": completed == len(mits) and len(mits) > 0,
            "completion_rate": round(completed / len(mits), 3) if mits else 0.0,
        }

    def _maybe_resolve(self, risk, status_info):
        if not status_info.get("all_complete"):
            return False
        resolved = self.db.query(DictStatus).filter(
            DictStatus.status_id == RISK_STATUS_RESOLVED
        ).first()
        if not resolved:
            return False
        risk.status_id = resolved.status_id
        risk.updated_at = datetime.now()
        self.db.commit()
        return True

    def _find_active_risk(self, task_id):
        return self.db.query(Risk).filter(
            Risk.task_id == task_id,
            Risk.status_id.notin_(CLOSED_RISK_STATUS_IDS),
        ).order_by(Risk.identified_at.desc()).first()

    def _get_default_risk_status(self):
        status = self.db.query(DictStatus).filter(
            DictStatus.status_id == RISK_STATUS_MITIGATING
        ).first()
        if status:
            return status
        return self.db.query(DictStatus).filter(
            DictStatus.category == 'risk',
            DictStatus.is_default == True,
        ).first()

    def _get_risk_level(self, score):
        return self.db.query(DictRiskLevel).filter(
            DictRiskLevel.min_score <= score,
            DictRiskLevel.max_score >= score,
        ).order_by(DictRiskLevel.min_score.desc()).first()

    def _calculate_impact(self, task, prediction_data, days_overdue):
        impact = 3.0
        if task.priority_id == PRIORITY_HIGH:
            impact += 2.0
        elif task.priority_id == PRIORITY_MEDIUM:
            impact += 1.0
        if task.is_cross_functional:
            impact += 1.0
        if days_overdue > 14:
            impact += 2.0
        elif days_overdue > 7:
            impact += 1.5
        elif days_overdue > 0:
            impact += 1.0
        for factor in prediction_data.get('top_factors', []):
            importance = factor.get('importance', 0)
            if importance >= 0.30:
                impact += 1.0
            elif importance >= 0.20:
                impact += 0.5
        return max(1, min(10, int(round(impact))))

    def _build_risk_name(self, task, is_delayed, days_overdue):
        if is_delayed:
            if days_overdue > 0:
                return f"تأخير {days_overdue} يوم: {task.title[:150]}"
            return f"تأخير فعلي: {task.title[:180]}"
        return f"خطر محتمل: {task.title[:180]}"

    def _build_description(self, task, prediction_data, is_delayed, days_overdue):
        parts = []
        if is_delayed and days_overdue > 0:
            parts.append(f"المهمة متأخرة بـ {days_overdue} يوم.")
        prob = prediction_data.get('delay_probability', 0)
        parts.append(f"احتمال التأخير: {round(prob * 100, 1)}%.")
        estimated = float(task.estimated_hours or 0)
        actual = float(task.actual_hours or 0)
        if estimated > 0:
            parts.append(f"الساعات المستهلكة: {round(actual / estimated * 100, 1)}%.")
        factors = prediction_data.get('top_factors', [])
        if factors:
            parts.append("العوامل: " + "؛ ".join(f.get('feature', '') for f in factors[:3]))
        return " ".join(parts)[:2000]

    def _serialize(self, risk):
        level = None
        if risk.risk_level_id:
            lvl = self.db.query(DictRiskLevel).filter(
                DictRiskLevel.risk_level_id == risk.risk_level_id
            ).first()
            if lvl:
                level = {"code": lvl.code, "name_ar": lvl.name_ar}
        status = None
        if risk.status_id:
            st = self.db.query(DictStatus).filter(
                DictStatus.status_id == risk.status_id
            ).first()
            if st:
                status = {"code": st.code, "name_ar": st.name_ar}
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
            "target_date": risk.target_date.isoformat() if risk.target_date else None,
        }

    def close(self):
        if self._owns_db and self.db:
            self.db.close()

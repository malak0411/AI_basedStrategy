import json
from datetime import datetime, date

from app.database import SessionLocal
from app.models import (
    Risk, RiskMitigation, OperationalTask, DictStatus, DictRiskLevel, AiJob,
)
from app.ai.services.ollama_client import ollama_client
from app.ai.config.constants import (
    CLOSED_MITIGATION_STATUS_IDS,
    RISK_STATUS_RESOLVED,
    TASK_STATUS_COMPLETED,
)


class RiskReassessmentService:

    def __init__(self, db=None):
        self.db = db or SessionLocal()
        self._owns_db = db is None

    def reassess(self, risk_id, created_by=None):
        risk = self.db.query(Risk).filter(Risk.risk_id == risk_id).first()
        if not risk:
            raise ValueError("Risk not found")

        job = self._create_job(risk_id, created_by)
        try:
            context = self._build_context(risk)
            prompt = self._build_prompt(context)
            response = ollama_client.generate(
                prompt,
                system_instruction="أنت محلل مخاطر. أعد JSON فقط بدون Markdown.",
            )
            parsed = self._parse(response)
            old_score = risk.risk_score
            self._apply(risk, parsed)
            self.db.commit()
            self.db.refresh(risk)
            self._update_job(job.job_id, "completed", {
                "risk_id": risk_id,
                "old_score": old_score,
                "new_score": risk.risk_score,
                "assessment": parsed.get("assessment", {}),
            })
            return {
                "success": True,
                "job_id": job.job_id,
                "risk_id": risk_id,
                "old_score": old_score,
                "new_score": risk.risk_score,
                "probability": risk.probability,
                "impact": risk.impact,
                "assessment": parsed.get("assessment", {}),
            }
        except Exception as e:
            self._update_job(job.job_id, "failed", {"error": str(e)})
            raise

    def _build_context(self, risk):
        mits = self.db.query(RiskMitigation).filter(
            RiskMitigation.risk_id == risk.risk_id
        ).all()
        today = date.today()
        mit_data = []
        completed = in_progress = overdue = 0
        for m in mits:
            st_code = None
            if m.status_id:
                st = self.db.query(DictStatus).filter(
                    DictStatus.status_id == m.status_id
                ).first()
                st_code = st.code if st else None
            if m.status_id in CLOSED_MITIGATION_STATUS_IDS:
                completed += 1
            else:
                in_progress += 1
                if m.due_date and m.due_date < today:
                    overdue += 1
            mit_data.append({
                "action": m.action,
                "status_code": st_code,
                "status_id": m.status_id,
                "due_date": m.due_date.isoformat() if m.due_date else None,
                "completed_at": m.completed_at.isoformat() if m.completed_at else None,
                "notes": m.notes,
            })

        task_ctx = None
        if risk.task_id:
            task = self.db.query(OperationalTask).filter(
                OperationalTask.task_id == risk.task_id
            ).first()
            if task:
                from app.models import TaskProgressLog
                latest = self.db.query(TaskProgressLog).filter(
                    TaskProgressLog.task_id == task.task_id
                ).order_by(TaskProgressLog.log_time.desc()).first()
                task_ctx = {
                    "task_id": task.task_id,
                    "title": task.title,
                    "status_id": task.status_id,
                    "end_date": task.end_date.isoformat() if task.end_date else None,
                    "progress_percent": latest.progress_percent if latest else 0,
                }

        return {
            "risk": {
                "risk_id": risk.risk_id,
                "name": risk.name,
                "description": risk.description,
                "probability": risk.probability,
                "impact": risk.impact,
                "risk_score": risk.risk_score,
                "identified_at": risk.identified_at.isoformat() if risk.identified_at else None,
                "target_date": risk.target_date.isoformat() if risk.target_date else None,
            },
            "mitigations": mit_data,
            "mitigation_stats": {
                "total": len(mits),
                "completed": completed,
                "in_progress": in_progress,
                "overdue": overdue,
                "completion_rate": round(completed / len(mits), 2) if mits else 0.0,
            },
            "task_progress": task_ctx,
        }

    def _build_prompt(self, context):
        return json.dumps({
            "role": "أنت محلل مخاطر استراتيجية.",
            "task": "أعد تقييم الخطر بناءً على الإجراءات المنجزة والحالة الحالية.",
            "context": context,
            "rules": [
                "خفّض الاحتمال إذا كانت الإجراءات ناجحة.",
                "لا تخفّض التأثير إلا إذا تغيرت طبيعة الخطر.",
                "إذا كانت كل الإجراءات مكتملة والمهمة تحسنت، اعتبر state=resolved.",
                "الحد الأدنى 1 والأقصى 10.",
                "أعد JSON فقط.",
            ],
            "output_format": {
                "assessment": {
                    "state": "active|reduced|resolved|escalated",
                    "new_probability": 5,
                    "new_impact": 7,
                    "reason": "سبب التقييم",
                }
            }
        }, ensure_ascii=False)

    def _parse(self, text):
        if not text:
            raise ValueError("Empty response")
        s = text.strip()
        if s.startswith("```json"):
            s = s[7:]
        if s.startswith("```"):
            s = s[3:]
        if s.endswith("```"):
            s = s[:-3]
        s = s.strip()
        try:
            return json.loads(s)
        except json.JSONDecodeError:
            import re
            m = re.search(r"\{[\s\S]*\}", s)
            if m:
                return json.loads(m.group(0))
            raise ValueError("Invalid JSON")

    def _apply(self, risk, parsed):
        a = parsed.get("assessment", {})
        np_ = a.get("new_probability")
        ni = a.get("new_impact")
        if np_ is not None:
            risk.probability = max(1, min(10, int(np_)))
        if ni is not None:
            risk.impact = max(1, min(10, int(ni)))
        score = risk.probability * risk.impact
        level = self.db.query(DictRiskLevel).filter(
            DictRiskLevel.min_score <= score,
            DictRiskLevel.max_score >= score,
        ).order_by(DictRiskLevel.min_score.desc()).first()
        if level:
            risk.risk_level_id = level.risk_level_id
        state = a.get("state", "active")
        if state == "resolved":
            st = self.db.query(DictStatus).filter(
                DictStatus.status_id == RISK_STATUS_RESOLVED
            ).first()
            if st:
                risk.status_id = st.status_id
        risk.updated_at = datetime.now()

    def _create_job(self, risk_id, created_by):
        job = AiJob(
            job_type="risk_reassessment",
            status="processing",
            input_data=json.dumps({"risk_id": risk_id}),
            created_by=created_by,
        )
        self.db.add(job)
        self.db.commit()
        self.db.refresh(job)
        return job

    def _update_job(self, job_id, status, result):
        job = self.db.query(AiJob).filter(AiJob.job_id == job_id).first()
        if job:
            job.status = status
            job.result_json = json.dumps(result, ensure_ascii=False)
            job.updated_at = datetime.now()
            self.db.commit()

    def close(self):
        if self._owns_db and self.db:
            self.db.close()

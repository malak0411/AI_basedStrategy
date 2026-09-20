import json
from datetime import datetime
from app.database import SessionLocal
from app.models import (
    Risk, RiskMitigation, OperationalTask, AIRecommendation,
    DictStatus, DictRiskLevel, DictPriority, AiJob
)
from app.ai.services.ollama_client import ollama_client




CLOSED_MITIGATION_CODES = ['completed', 'resolved', 'cancelled']




class RiskReassessmentService:


    def __init__(self):
        self.db = SessionLocal()


    def reassess(self, risk_id, created_by=None):
        risk = self.db.query(Risk).filter(Risk.risk_id == risk_id).first()
        if not risk:
            raise ValueError("Risk not found")


        job = self._create_job(risk_id, created_by)
        job_id = job.job_id


        try:
            context = self._build_context(risk)
            prompt = self._build_prompt(context)


            response = ollama_client.generate(
                prompt,
                system_instruction="أنت محلل مخاطر. أعد JSON فقط بدون Markdown."
            )


            parsed = self._parse_response(response)
            old_score = risk.risk_score


            self._apply_reassessment(risk, parsed)


            self.db.commit()
            self.db.refresh(risk)


            new_score = risk.risk_score


            self._update_job(job_id, "completed", {
                "risk_id": risk_id,
                "old_score": old_score,
                "new_score": new_score,
                "assessment": parsed.get("assessment", {})
            })


            return {
                "success": True,
                "job_id": job_id,
                "risk_id": risk_id,
                "old_score": old_score,
                "new_score": new_score,
                "probability": risk.probability,
                "impact": risk.impact,
                "assessment": parsed.get("assessment", {})
            }
        except Exception as e:
            self._update_job(job_id, "failed", {"error": str(e)})
            raise


    def _build_context(self, risk):
        context = {
            "risk": {
                "risk_id": risk.risk_id,
                "name": risk.name,
                "description": risk.description,
                "probability": risk.probability,
                "impact": risk.impact,
                "risk_score": risk.risk_score,
                "identified_at": risk.identified_at.isoformat() if risk.identified_at else None,
                "target_date": risk.target_date.isoformat() if risk.target_date else None
            },
            "mitigations": [],
            "task_progress": None
        }


        if risk.risk_level_id:
            level = self.db.query(DictRiskLevel).filter(
                DictRiskLevel.risk_level_id == risk.risk_level_id
            ).first()
            if level:
                context["risk"]["risk_level"] = {
                    "code": level.code,
                    "name_ar": level.name_ar
                }


        mitigations = self.db.query(RiskMitigation).filter(
            RiskMitigation.risk_id == risk.risk_id
        ).all()


        completed_count = 0
        in_progress_count = 0
        pending_count = 0
        overdue_count = 0


        for m in mitigations:
            status_code = None
            status_name = None
            if m.status_id:
                st = self.db.query(DictStatus).filter(
                    DictStatus.status_id == m.status_id
                ).first()
                if st:
                    status_code = st.code
                    status_name = st.name_ar


            if status_code in CLOSED_MITIGATION_CODES:
                completed_count += 1
            elif status_code in ['in_progress', 'mitigating']:
                in_progress_count += 1
            else:
                pending_count += 1


            if m.due_date and m.due_date < datetime.now().date():
                if status_code not in CLOSED_MITIGATION_CODES:
                    overdue_count += 1


            context["mitigations"].append({
                "action": m.action,
                "status_code": status_code,
                "status_name": status_name,
                "due_date": m.due_date.isoformat() if m.due_date else None,
                "completed_at": m.completed_at.isoformat() if m.completed_at else None,
                "notes": m.notes
            })


        context["mitigation_stats"] = {
            "total": len(mitigations),
            "completed": completed_count,
            "in_progress": in_progress_count,
            "pending": pending_count,
            "overdue": overdue_count,
            "completion_rate": round(completed_count / len(mitigations), 2) if mitigations else 0.0
        }


        if risk.task_id:
            task = self.db.query(OperationalTask).filter(
                OperationalTask.task_id == risk.task_id
            ).first()
            if task:
                from app.models import TaskProgressLog
                latest = self.db.query(TaskProgressLog).filter(
                    TaskProgressLog.task_id == task.task_id
                ).order_by(TaskProgressLog.log_time.desc()).first()


                context["task_progress"] = {
                    "task_id": task.task_id,
                    "title": task.title,
                    "progress_percent": latest.progress_percent if latest else 0,
                    "status_id": task.status_id,
                    "end_date": task.end_date.isoformat() if task.end_date else None
                }


        return context


    def _build_prompt(self, context):
        payload = {
            "role": "أنت محلل مخاطر استراتيجية.",
            "task": "أعد تقييم الخطر بناءً على الإجراءات المنجزة والحالة الحالية.",
            "context": context,
            "rules": [
                "خفّض الاحتمال إذا كانت الإجراءات ناجحة.",
                "لا تخفّض التأثير إلا إذا تغيرت طبيعة الخطر.",
                "إذا زادت المخاطر، ارفع الاحتمال.",
                "الحد الأدنى 1 والأقصى 10.",
                "أعد JSON فقط."
            ],
            "output_format": {
                "assessment": {
                    "state": "active|reduced|resolved|escalated",
                    "new_probability": 5,
                    "new_impact": 7,
                    "reason": "سبب التقييم"
                }
            }
        }
        return json.dumps(payload, ensure_ascii=False, indent=2)


    def _parse_response(self, response_text):
        if not response_text:
            raise ValueError("Empty response")


        text = response_text.strip()
        if text.startswith("```json"):
            text = text[7:]
        if text.startswith("```"):
            text = text[3:]
        if text.endswith("```"):
            text = text[:-3]
        text = text.strip()


        try:
            return json.loads(text)
        except json.JSONDecodeError:
            import re
            match = re.search(r"\{[\s\S]*\}", text)
            if match:
                return json.loads(match.group(0))
            raise ValueError("Invalid JSON from Ollama")


    def _apply_reassessment(self, risk, parsed):
        assessment = parsed.get("assessment", {})
        new_prob = assessment.get("new_probability")
        new_impact = assessment.get("new_impact")


        if new_prob is not None:
            risk.probability = max(1, min(10, int(new_prob)))
        if new_impact is not None:
            risk.impact = max(1, min(10, int(new_impact)))


        risk.risk_score = risk.probability * risk.impact


        level = self.db.query(DictRiskLevel).filter(
            DictRiskLevel.min_score <= risk.risk_score,
            DictRiskLevel.max_score >= risk.risk_score
        ).first()
        if level:
            risk.risk_level_id = level.risk_level_id


        state = assessment.get("state", "active")
        if state == "resolved":
            resolved_status = self.db.query(DictStatus).filter(
                DictStatus.category == 'risk',
                DictStatus.code == 'resolved'
            ).first()
            if resolved_status:
                risk.status_id = resolved_status.status_id


        risk.updated_at = datetime.now()


    def _create_job(self, risk_id, created_by):
        job = AiJob(
            job_type="risk_reassessment",
            status="processing",
            input_data=json.dumps({"risk_id": risk_id}, ensure_ascii=False),
            created_by=created_by
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
        if self.db:
            self.db.close()

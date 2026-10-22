import json
import re
from datetime import datetime, date

from app.database import SessionLocal
from app.models import (
    Risk, RiskMitigation, OperationalTask, MajorTask, Initiative,
    Department, TaskAssignment, Employee, TaskProgressLog,
    TaskDependency, BudgetLine, AIRecommendation, DictStatus,
    DictPriority, DictRiskLevel, AiJob,
)
from app.ai.services.ollama_client import ollama_client
from app.ai.config.constants import CLOSED_MITIGATION_STATUS_IDS


class RiskRecommendationService:

    def __init__(self, db=None):
        self.db = db or SessionLocal()
        self._owns_db = db is None

    def generate_for_risk(self, risk_id, created_by=None):
        risk = self.db.query(Risk).filter(Risk.risk_id == risk_id).first()
        if not risk:
            raise ValueError("Risk not found")

        job = self._create_job(risk_id, created_by)
        try:
            ctx = self._build_context(risk)
            prompt = self._build_prompt(ctx)
            response = ollama_client.generate(
                prompt,
                system_instruction="أنت مستشار مخاطر. أعد JSON فقط بدون Markdown.",
            )
            parsed = self._parse(response)
            recs = self._save(risk, parsed, created_by)
            self._update_job(job.job_id, "completed", {
                "risk_id": risk_id,
                "recommendations_count": len(recs),
            })
            return {
                "success": True,
                "job_id": job.job_id,
                "risk_id": risk_id,
                "recommendations": recs,
                "assessment": parsed.get("risk_assessment", {}),
            }
        except Exception as e:
            self._update_job(job.job_id, "failed", {"error": str(e)})
            raise

    def _build_context(self, risk):
        ctx = {
            "risk": {
                "risk_id": risk.risk_id,
                "name": risk.name,
                "description": risk.description,
                "probability": risk.probability,
                "impact": risk.impact,
                "risk_score": risk.risk_score,
                "target_date": risk.target_date.isoformat() if risk.target_date else None,
            },
            "task": None,
            "previous_recommendations": [],
            "mitigation_actions": [],
        }
        if risk.risk_level_id:
            lvl = self.db.query(DictRiskLevel).filter(
                DictRiskLevel.risk_level_id == risk.risk_level_id
            ).first()
            if lvl:
                ctx["risk"]["risk_level"] = {"code": lvl.code, "name_ar": lvl.name_ar}
        if risk.status_id:
            st = self.db.query(DictStatus).filter(
                DictStatus.status_id == risk.status_id
            ).first()
            if st:
                ctx["risk"]["status"] = {"code": st.code, "name_ar": st.name_ar}

        if risk.task_id:
            task = self.db.query(OperationalTask).filter(
                OperationalTask.task_id == risk.task_id
            ).first()
            if task:
                ctx["task"] = self._task_ctx(task)

            all_prev = self.db.query(AIRecommendation).filter(
                AIRecommendation.task_id == risk.task_id
            ).order_by(AIRecommendation.created_at.desc()).all()
            for r in all_prev:
                ctx["previous_recommendations"].append({
                    "text": r.text,
                    "reasoning": r.reasoning,
                })

        for m in self.db.query(RiskMitigation).filter(
            RiskMitigation.risk_id == risk.risk_id
        ).order_by(RiskMitigation.created_at.desc()).all():
            st_code = None
            if m.status_id:
                st = self.db.query(DictStatus).filter(
                    DictStatus.status_id == m.status_id
                ).first()
                st_code = st.code if st else None
            assigned = None
            if m.assigned_to:
                e = self.db.query(Employee).filter(
                    Employee.employee_id == m.assigned_to
                ).first()
                assigned = e.full_name if e else None
            ctx["mitigation_actions"].append({
                "action": m.action,
                "status": st_code,
                "status_id": m.status_id,
                "assigned_to": assigned,
                "due_date": m.due_date.isoformat() if m.due_date else None,
                "completed_at": m.completed_at.isoformat() if m.completed_at else None,
            })
        return ctx

    def _task_ctx(self, task):
        major = self.db.query(MajorTask).filter(
            MajorTask.major_task_id == task.major_task_id
        ).first() if task.major_task_id else None
        initiative = self.db.query(Initiative).filter(
            Initiative.initiative_id == major.initiative_id
        ).first() if major and major.initiative_id else None
        department = None
        if task.department_id:
            d = self.db.query(Department).filter(
                Department.department_id == task.department_id
            ).first()
            department = d.name if d else None
        employees = []
        for a in self.db.query(TaskAssignment).filter(
            TaskAssignment.task_id == task.task_id,
            TaskAssignment.is_active == True,
        ).all():
            e = self.db.query(Employee).filter(
                Employee.employee_id == a.employee_id
            ).first()
            if e:
                employees.append(e.full_name)
        latest = self.db.query(TaskProgressLog).filter(
            TaskProgressLog.task_id == task.task_id
        ).order_by(TaskProgressLog.log_time.desc()).first()
        deps = self.db.query(TaskDependency).filter(
            TaskDependency.task_id == task.task_id
        ).count()
        alloc = spent = 0.0
        for bl in self.db.query(BudgetLine).filter(
            BudgetLine.budgetable_id == task.task_id,
            BudgetLine.budgetable_type == 'operational_task',
        ).all():
            alloc += float(bl.allocated_amount or 0)
            spent += float(bl.spent_amount or 0)
        return {
            "task_id": task.task_id,
            "title": task.title,
            "description": task.description,
            "priority_id": task.priority_id,
            "start_date": task.start_date.isoformat() if task.start_date else None,
            "end_date": task.end_date.isoformat() if task.end_date else None,
            "progress_percent": latest.progress_percent if latest else 0,
            "department": department,
            "assigned_employees": employees,
            "dependencies_count": deps,
            "budget_allocated": alloc,
            "budget_spent": spent,
            "major_task": major.name if major else None,
            "initiative": initiative.name if initiative else None,
        }

    def _build_prompt(self, context):
        payload = {
            "role": "أنت مستشار إدارة مخاطر استراتيجية.",
            "task": "حلّل الخطر واقترح توصيات عملية.",
            "context": context,
            "rules": [
                "لا تقترح إجراءً موجوداً في mitigation_actions.",
                "لا تكرر أي نص من previous_recommendations.",
                "إن كان هناك إجراء قيد التنفيذ، اقترح خطوات تكميلية.",
                "اقترح 2 إلى 4 توصيات.",
                "كل توصية محددة وقابلة للتنفيذ.",
                "لا تخترع موظفين أو أقسام.",
                "أعد JSON فقط.",
            ],
            "output_format": {
                "risk_assessment": {
                    "current_state": "active|reduced|resolved|escalated",
                    "severity": "critical|high|medium|low",
                    "reason": "سبب التقييم",
                },
                "recommendations": [
                    {"text": "...", "reasoning": "...", "priority": "high|medium|low"}
                ],
            },
        }
        return json.dumps(payload, ensure_ascii=False, indent=2)

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
            m = re.search(r"\{[\s\S]*\}", s)
            if m:
                return json.loads(m.group(0))
            raise ValueError("Invalid JSON")

    def _is_duplicate(self, text, prev_texts, mit_actions):
        t = re.sub(r"\s+", " ", text.lower()).strip()
        for p in prev_texts:
            if not p:
                continue
            pp = re.sub(r"\s+", " ", p.lower()).strip()
            if t == pp or (len(t) > 15 and t in pp) or (len(pp) > 15 and pp in t):
                return True
            ta, pa = set(t.split()), set(pp.split())
            if ta and pa:
                if len(ta & pa) / len(ta | pa) > 0.75:
                    return True
        for m in mit_actions:
            action = m.get("action", "")
            if action:
                a = re.sub(r"\s+", " ", action.lower()).strip()
                if t == a or (len(t) > 15 and t in a) or (len(a) > 15 and a in t):
                    return True
        return False

    def _save(self, risk, parsed, created_by):
        recs = parsed.get("recommendations", [])
        if not recs:
            return []

        default_status = self.db.query(DictStatus).filter(
            DictStatus.category == 'recommendation',
            DictStatus.is_default == True,
        ).first()
        if not default_status:
            default_status = self.db.query(DictStatus).filter(
                DictStatus.category == 'recommendation'
            ).first()
        if not default_status:
            print("WARN: no default recommendation status — saving without status_id")

        p_map = {}
        for p in self.db.query(DictPriority).all():
            if p.code:
                p_map[p.code.lower()] = p.priority_id
        medium_pid = p_map.get("medium") or (next(iter(p_map.values())) if p_map else None)

        prev_texts = []
        if risk.task_id:
            prev = self.db.query(AIRecommendation).filter(
                AIRecommendation.task_id == risk.task_id
            ).all()
            prev_texts = [p.text for p in prev if p.text]

        mit_actions = [{"action": m.action} for m in self.db.query(RiskMitigation).filter(
            RiskMitigation.risk_id == risk.risk_id
        ).all()]

        saved = []
        try:
            for rec in recs[:4]:
                text = rec.get("text", "").strip()
                if not text:
                    continue
                if self._is_duplicate(text, prev_texts, mit_actions):
                    continue
                pc = str(rec.get("priority", "medium")).lower()
                if pc not in p_map:
                    pc = "medium"
                pid = p_map.get(pc) or medium_pid

                reasoning_json = json.dumps({
                    "risk_id": risk.risk_id,
                    "risk_name": risk.name,
                    "text": rec.get("reasoning", ""),
                }, ensure_ascii=False)

                new = AIRecommendation(
                    task_id=risk.task_id,
                    text=text,
                    reasoning=reasoning_json,
                    priority_id=pid,
                    status_id=default_status.status_id if default_status else None,
                    created_at=datetime.now(),
                )
                self.db.add(new)
                self.db.flush()
                saved.append({
                    "recommendation_id": new.recommendation_id,
                    "text": new.text,
                    "priority": pc,
                    "risk_id": risk.risk_id,
                })
            self.db.commit()
        except Exception as e:
            print(f"save risk recommendations fail: {e}")
            self.db.rollback()
            saved = []
        return saved

    def _create_job(self, risk_id, created_by):
        job = AiJob(
            job_type="risk_recommendation",
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

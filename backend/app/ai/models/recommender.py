import json
import re
from datetime import datetime, date
from app.database import SessionLocal
from app.models import (
    AIRecommendation, DictStatus, DictPriority, OperationalTask, MajorTask,
    Initiative, Program, StrategicGoal, StrategicPillar, Department,
    TaskAssignment, Employee, TaskProgressLog, TaskDependency,
    BudgetLine, Risk, RiskMitigation, DictRiskLevel,
)
from app.ai.services.ollama_client import ollama_client


class Recommender:

    def __init__(self, db=None):
        self.db = db or SessionLocal()
        self._owns_db = db is None
        self.is_trained = True
        self.model_version = "4.1.0-ollama"

    def recommend(self, task_id=None, task_features=None, save_to_db=False):
        if task_id is None and task_features is not None:
            task_id = task_features.get('task_id')
        if task_id is None:
            return self._empty(task_id)

        context = self._build_context(int(task_id))
        if context is None:
            return self._empty(task_id)

        recs = []
        try:
            recs = self._generate_with_ollama(context)
        except Exception as e:
            print(f"ollama recommend fail: {e}")

        if not recs:
            recs = self._fallback(context)

        result = {
            "task_id": int(task_id),
            "recommendations": recs[:4],
            "prediction_time": datetime.now().isoformat(),
            "model_version": self.model_version,
            "source": "ollama" if recs and recs[0].get('source') == 'ollama' else "fallback",
        }
        if save_to_db:
            result["saved_ids"] = self._save(int(task_id), result["recommendations"])
        return result

    def _build_context(self, task_id):
        task = self.db.query(OperationalTask).filter(
            OperationalTask.task_id == task_id
        ).first()
        if not task:
            return None

        major = self.db.query(MajorTask).filter(
            MajorTask.major_task_id == task.major_task_id
        ).first() if task.major_task_id else None
        initiative = self.db.query(Initiative).filter(
            Initiative.initiative_id == major.initiative_id
        ).first() if major and major.initiative_id else None
        program = self.db.query(Program).filter(
            Program.program_id == initiative.program_id
        ).first() if initiative and initiative.program_id else None
        goal = self.db.query(StrategicGoal).filter(
            StrategicGoal.goal_id == program.goal_id
        ).first() if program and program.goal_id else None
        pillar = self.db.query(StrategicPillar).filter(
            StrategicPillar.pillar_id == goal.pillar_id
        ).first() if goal and goal.pillar_id else None

        dept_name = None
        if task.department_id:
            d = self.db.query(Department).filter(
                Department.department_id == task.department_id
            ).first()
            dept_name = d.name if d else None

        assignments = self.db.query(TaskAssignment).filter(
            TaskAssignment.task_id == task_id,
            TaskAssignment.is_active == True,
        ).all()
        employees = []
        for a in assignments:
            e = self.db.query(Employee).filter(
                Employee.employee_id == a.employee_id
            ).first()
            if e:
                employees.append({"name": e.full_name, "job_title": e.job_title or ""})

        logs = self.db.query(TaskProgressLog).filter(
            TaskProgressLog.task_id == task_id
        ).order_by(TaskProgressLog.log_time.desc()).limit(5).all()
        history = []
        for log in logs:
            e = self.db.query(Employee).filter(
                Employee.employee_id == log.employee_id
            ).first() if log.employee_id else None
            history.append({
                "date": log.log_time.isoformat() if log.log_time else None,
                "progress": log.progress_percent or 0,
                "notes": log.notes or "",
                "by": e.full_name if e else None,
            })
        latest_progress = history[0]["progress"] if history else 0
        days_since = 0
        if logs and logs[0].log_time:
            days_since = (datetime.now() - logs[0].log_time).days

        dep_count = self.db.query(TaskDependency).filter(
            TaskDependency.task_id == task_id
        ).count()

        budget_lines = self.db.query(BudgetLine).filter(
            BudgetLine.budgetable_id == task_id,
            BudgetLine.budgetable_type == 'operational_task',
        ).all()
        alloc = sum(float(b.allocated_amount or 0) for b in budget_lines)
        spent = sum(float(b.spent_amount or 0) for b in budget_lines)

        risks = self.db.query(Risk).filter(Risk.task_id == task_id).all()
        risks_data = []
        for r in risks:
            level = None
            if r.risk_level_id:
                lvl = self.db.query(DictRiskLevel).filter(
                    DictRiskLevel.risk_level_id == r.risk_level_id
                ).first()
                level = lvl.code if lvl else None
            risks_data.append({
                "risk_id": r.risk_id,
                "name": r.name,
                "probability": r.probability,
                "impact": r.impact,
                "risk_score": r.risk_score,
                "level": level,
            })

        mitigations = self.db.query(RiskMitigation).join(
            Risk, Risk.risk_id == RiskMitigation.risk_id
        ).filter(Risk.task_id == task_id).all()
        mit_data = []
        for m in mitigations:
            st = None
            if m.status_id:
                st = self.db.query(DictStatus).filter(
                    DictStatus.status_id == m.status_id
                ).first()
            mit_data.append({
                "action": m.action,
                "status": st.code if st else None,
                "due_date": m.due_date.isoformat() if m.due_date else None,
                "notes": m.notes,
            })

        prev = self.db.query(AIRecommendation).filter(
            AIRecommendation.task_id == task_id
        ).order_by(AIRecommendation.created_at.desc()).limit(20).all()
        prev_data = [{"text": p.text, "created_at": p.created_at.isoformat() if p.created_at else None}
                     for p in prev]

        today = date.today()
        planned = (task.end_date - task.start_date).days if (task.start_date and task.end_date) else 0
        elapsed = (today - task.start_date).days if task.start_date else 0
        remaining = (task.end_date - today).days if task.end_date else 0

        return {
            "task": {
                "task_id": task.task_id,
                "title": task.title,
                "description": task.description or "",
                "priority_id": task.priority_id,
                "start_date": task.start_date.isoformat() if task.start_date else None,
                "end_date": task.end_date.isoformat() if task.end_date else None,
                "estimated_hours": float(task.estimated_hours or 0),
                "actual_hours": float(task.actual_hours or 0),
                "is_cross_functional": bool(task.is_cross_functional),
                "planned_duration_days": planned,
                "elapsed_days": elapsed,
                "remaining_days": remaining,
                "latest_progress": latest_progress,
                "days_since_last_update": days_since,
                "dependencies_count": dep_count,
                "department": dept_name,
            },
            "assigned_employees": employees,
            "progress_history": history,
            "strategic_context": {
                "major_task": major.name if major else None,
                "initiative": initiative.name if initiative else None,
                "program": program.name if program else None,
                "goal": goal.title if goal else None,
                "pillar": pillar.name if pillar else None,
            },
            "budget": {
                "allocated": alloc,
                "spent": spent,
                "remaining": alloc - spent,
                "usage_ratio": round(spent / alloc, 3) if alloc > 0 else 0.0,
            },
            "risks": risks_data,
            "mitigation_actions": mit_data,
            "previous_recommendations": prev_data,
        }

    def _generate_with_ollama(self, context):
        payload = {
            "role": "أنت مستشار خبير في إدارة المشاريع الحكومية.",
            "task": "حلّل المهمة واقترح توصيات تصحيحية محددة.",
            "context": context,
            "instructions": [
                "لا تكرر أي نص من previous_recommendations بأي شكل.",
                "لا تقترح إجراءً قائم بالفعل في mitigation_actions.",
                "إن كان هناك إجراء قيد التنفيذ، اقترح خطوات تكميلية.",
                "إن كان هناك خطر نشط، عالجه.",
                "استخدم أرقاماً حقيقية من السياق.",
                "لا تخترع أسماء موظفين أو أقسام.",
                "3 إلى 5 توصيات.",
                "أولوية كل توصية: high أو medium أو low.",
            ],
            "output_format": {
                "recommendations": [
                    {"text": "...", "reasoning": "...", "priority": "high|medium|low"}
                ]
            },
            "important": "أعد JSON فقط بدون Markdown.",
        }
        prompt = json.dumps(payload, ensure_ascii=False, indent=2)
        response = ollama_client.generate(
            prompt,
            system_instruction="أنت مستشار إداري حكومي. أعد JSON فقط.",
        )
        parsed = self._parse_json(response)
        if not parsed:
            return []
        raw = parsed.get("recommendations", [])
        if not isinstance(raw, list):
            return []
        prev_texts = [r.get("text", "").strip() for r in context.get("previous_recommendations", [])]
        result = []
        seen = set()
        for r in raw:
            if not isinstance(r, dict):
                continue
            text = str(r.get("text", "")).strip()
            reasoning = str(r.get("reasoning", "")).strip()
            priority = str(r.get("priority", "medium")).lower()
            if not text:
                continue
            if priority not in ("high", "medium", "low"):
                priority = "medium"
            if self._is_duplicate(text, prev_texts) or text in seen:
                continue
            seen.add(text)
            result.append({"text": text, "reasoning": reasoning, "priority": priority, "source": "ollama"})
        return result

    def _is_duplicate(self, text, prev_texts):
        if not prev_texts:
            return False
        t = re.sub(r"\s+", " ", text.lower()).strip()
        for p in prev_texts:
            if not p:
                continue
            pp = re.sub(r"\s+", " ", p.lower()).strip()
            if t == pp or (len(t) > 15 and t in pp) or (len(pp) > 15 and pp in t):
                return True
            ta, pa = set(t.split()), set(pp.split())
            if ta and pa:
                overlap = len(ta & pa) / len(ta | pa)
                if overlap > 0.75:
                    return True
        return False

    def _parse_json(self, text):
        if not text:
            return None
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
                try:
                    return json.loads(m.group(0))
                except json.JSONDecodeError:
                    return None
        return None

    def _fallback(self, context):
        task = context.get("task", {})
        risks = context.get("risks", [])
        progress = task.get("latest_progress", 0)
        days_since = task.get("days_since_last_update", 0)
        remaining = task.get("remaining_days", 0)
        recs = []
        if days_since > 14:
            recs.append({
                "text": f"تحديث حالة المهمة فوراً (آخر تحديث منذ {days_since} يوم).",
                "reasoning": "انقطاع المتابعة يمنع الكشف المبكر عن الانحراف.",
                "priority": "high",
                "source": "fallback",
            })
        if 0 < remaining < 14 and progress < 60:
            recs.append({
                "text": f"مراجعة خطة المهمة وتسريع التنفيذ (الموعد بعد {remaining} يوم).",
                "reasoning": "الفارق الزمني يهدد الموعد النهائي.",
                "priority": "high",
                "source": "fallback",
            })
        active = [r for r in risks if r.get("level") in ("critical", "high")]
        if active:
            recs.append({
                "text": f"معالجة {len(active)} مخاطر حرجة مرتبطة بالمهمة.",
                "reasoning": "المخاطر النشطة تهدد نجاح المهمة.",
                "priority": "high",
                "source": "fallback",
            })
        if not recs:
            recs.append({
                "text": "متابعة أسبوعية دورية وتحديث نسبة الإنجاز.",
                "reasoning": "ضمان الاستمرارية وعدم تراكم الانحراف.",
                "priority": "medium",
                "source": "fallback",
            })
        return recs

    def _save(self, task_id, recommendations):
        saved = []
        try:
            default_status = self.db.query(DictStatus).filter(
                DictStatus.category == 'recommendation',
                DictStatus.is_default == True,
            ).first()
            if not default_status:
                default_status = self.db.query(DictStatus).filter(
                    DictStatus.category == 'recommendation'
                ).first()
            p_map = {p.code.lower(): p.priority_id for p in self.db.query(DictPriority).all()}
            for r in recommendations:
                priority_code = r.get("priority", "medium").lower()
                pid = p_map.get(priority_code) or p_map.get("medium")
                new = AIRecommendation(
                    task_id=int(task_id),
                    text=r.get("text", ""),
                    reasoning=r.get("reasoning", ""),
                    priority_id=pid,
                    status_id=default_status.status_id if default_status else None,
                    created_at=datetime.now(),
                )
                self.db.add(new)
                self.db.flush()
                saved.append(new.recommendation_id)
            self.db.commit()
        except Exception as e:
            print(f"save recommendations fail: {e}")
            self.db.rollback()
        return saved

    def _empty(self, task_id=None):
        return {
            "task_id": int(task_id) if task_id else None,
            "recommendations": [],
            "prediction_time": datetime.now().isoformat(),
            "model_version": self.model_version,
            "source": "empty",
        }

    def close(self):
        if self._owns_db and self.db:
            self.db.close()

    def load_model(self):
        return True

    def train(self, force=False):
        return {
            "model_name": "Ollama-Powered Recommender",
            "f1_score": None,
            "accuracy": None,
            "version": self.model_version,
        }

import json
from datetime import datetime
from app.database import SessionLocal
from app.models import (
    AIRecommendation, DictStatus, DictPriority,
    OperationalTask, MajorTask, Initiative, Program,
    StrategicGoal, StrategicPillar, Department,
    TaskAssignment, Employee, TaskProgressLog,
    TaskDependency, BudgetLine, Risk, RiskMitigation
)
from app.ai.services.ollama_client import ollama_client




class Recommender:


    def __init__(self):
        self.is_trained = True
        self.model_version = "4.0.0-ollama-only"


    def recommend(self, task_id=None, task_features=None, save_to_db=False):
        if task_id is None and task_features is not None:
            task_id = task_features.get('task_id')


        if task_id is None:
            return self._empty_response()


        db = SessionLocal()
        try:
            context = self._build_full_context(db, int(task_id))
        finally:
            db.close()


        if context is None:
            return self._empty_response(task_id)


        recommendations = []


        try:
            recommendations = self._generate_with_ollama(context)
        except Exception as e:
            print(f" Ollama recommendation failed: {str(e)}")


        if not recommendations:
            recommendations = self._safe_fallback(context)


        result = {
            "task_id": int(task_id),
            "recommendations": recommendations[:4],
            "prediction_time": datetime.now().isoformat(),
            "model_version": self.model_version,
            "source": "ollama" if recommendations and recommendations[0].get('source') == 'ollama' else "fallback"
        }


        if save_to_db:
            result["saved_ids"] = self._save_to_database(int(task_id), result["recommendations"])


        return result


    def _build_full_context(self, db, task_id):
        task = db.query(OperationalTask).filter(
            OperationalTask.task_id == task_id
        ).first()


        if not task:
            return None


        major_task = db.query(MajorTask).filter(
            MajorTask.major_task_id == task.major_task_id
        ).first() if task.major_task_id else None


        initiative = None
        program = None
        goal = None
        pillar = None


        if major_task and major_task.initiative_id:
            initiative = db.query(Initiative).filter(
                Initiative.initiative_id == major_task.initiative_id
            ).first()


        if initiative and initiative.program_id:
            program = db.query(Program).filter(
                Program.program_id == initiative.program_id
            ).first()


        if program and program.goal_id:
            goal = db.query(StrategicGoal).filter(
                StrategicGoal.goal_id == program.goal_id
            ).first()


        if goal and goal.pillar_id:
            pillar = db.query(StrategicPillar).filter(
                StrategicPillar.pillar_id == goal.pillar_id
            ).first()


        department_name = None
        if task.department_id:
            d = db.query(Department).filter(
                Department.department_id == task.department_id
            ).first()
            if d:
                department_name = d.name


        assignments = db.query(TaskAssignment).filter(
            TaskAssignment.task_id == task_id,
            TaskAssignment.is_active == True
        ).all()


        assigned_employees = []
        for a in assignments:
            emp = db.query(Employee).filter(
                Employee.employee_id == a.employee_id
            ).first()
            if emp:
                assigned_employees.append({
                    "name": emp.full_name,
                    "job_title": emp.job_title or ""
                })


        progress_logs = db.query(TaskProgressLog).filter(
            TaskProgressLog.task_id == task_id
        ).order_by(TaskProgressLog.log_time.desc()).limit(5).all()


        progress_history = []
        for log in progress_logs:
            emp = db.query(Employee).filter(
                Employee.employee_id == log.employee_id
            ).first() if log.employee_id else None
            progress_history.append({
                "date": log.log_time.isoformat() if log.log_time else None,
                "progress": log.progress_percent or 0,
                "notes": log.notes or "",
                "by": emp.full_name if emp else None
            })


        latest_progress = progress_history[0]["progress"] if progress_history else 0
        last_update_days = 0
        if progress_logs and progress_logs[0].log_time:
            last_update_days = (datetime.now() - progress_logs[0].log_time).days


        dependencies_count = db.query(TaskDependency).filter(
            TaskDependency.task_id == task_id
        ).count()


        budget_lines = db.query(BudgetLine).filter(
            BudgetLine.budgetable_id == task_id,
            BudgetLine.budgetable_type == 'operational_task'
        ).all()


        total_allocated = sum(float(bl.allocated_amount or 0) for bl in budget_lines)
        total_spent = sum(float(bl.spent_amount or 0) for bl in budget_lines)


        risks = db.query(Risk).filter(Risk.task_id == task_id).all()


        risks_data = []
        for r in risks:
            level_code = None
            if r.risk_level_id:
                from app.models import DictRiskLevel
                lvl = db.query(DictRiskLevel).filter(
                    DictRiskLevel.risk_level_id == r.risk_level_id
                ).first()
                if lvl:
                    level_code = lvl.code


            risks_data.append({
                "risk_id": r.risk_id,
                "name": r.name,
                "description": r.description,
                "probability": r.probability,
                "impact": r.impact,
                "risk_score": r.risk_score,
                "level": level_code
            })


        mitigations = db.query(RiskMitigation).join(
            Risk, Risk.risk_id == RiskMitigation.risk_id
        ).filter(Risk.task_id == task_id).all()


        mitigations_data = []
        for m in mitigations:
            status_code = None
            if m.status_id:
                st = db.query(DictStatus).filter(
                    DictStatus.status_id == m.status_id
                ).first()
                if st:
                    status_code = st.code


            assigned_name = None
            if m.assigned_to:
                emp = db.query(Employee).filter(
                    Employee.employee_id == m.assigned_to
                ).first()
                if emp:
                    assigned_name = emp.full_name


            mitigations_data.append({
                "action": m.action,
                "status": status_code,
                "assigned_to": assigned_name,
                "due_date": m.due_date.isoformat() if m.due_date else None,
                "completed_at": m.completed_at.isoformat() if m.completed_at else None,
                "notes": m.notes
            })


        previous_recs = db.query(AIRecommendation).filter(
            AIRecommendation.task_id == task_id
        ).order_by(AIRecommendation.created_at.desc()).limit(5).all()


        previous_recs_data = []
        for pr in previous_recs:
            previous_recs_data.append({
                "text": pr.text,
                "reasoning": pr.reasoning,
                "implemented_at": pr.implemented_at.isoformat() if pr.implemented_at else None,
                "created_at": pr.created_at.isoformat() if pr.created_at else None
            })


        start_date = task.start_date
        end_date = task.end_date
        today = datetime.now().date()


        planned_duration = (end_date - start_date).days if start_date and end_date else 0
        elapsed_days = (today - start_date).days if start_date else 0
        remaining_days = (end_date - today).days if end_date else 0


        return {
            "task": {
                "task_id": task.task_id,
                "title": task.title,
                "description": task.description or "",
                "priority_id": task.priority_id,
                "start_date": start_date.isoformat() if start_date else None,
                "end_date": end_date.isoformat() if end_date else None,
                "estimated_hours": float(task.estimated_hours) if task.estimated_hours else 0,
                "actual_hours": float(task.actual_hours) if task.actual_hours else 0,
                "is_cross_functional": bool(task.is_cross_functional),
                "planned_duration_days": planned_duration,
                "elapsed_days": elapsed_days,
                "remaining_days": remaining_days,
                "latest_progress": latest_progress,
                "days_since_last_update": last_update_days,
                "dependencies_count": dependencies_count,
                "department": department_name
            },
            "assigned_employees": assigned_employees,
            "progress_history": progress_history,
            "strategic_context": {
                "major_task": major_task.name if major_task else None,
                "initiative": initiative.name if initiative else None,
                "program": program.name if program else None,
                "goal": goal.title if goal else None,
                "pillar": pillar.name if pillar else None
            },
            "budget": {
                "allocated": total_allocated,
                "spent": total_spent,
                "remaining": total_allocated - total_spent,
                "usage_ratio": round(total_spent / total_allocated, 3) if total_allocated > 0 else 0.0
            },
            "risks": risks_data,
            "mitigation_actions": mitigations_data,
            "previous_recommendations": previous_recs_data
        }


    def _generate_with_ollama(self, context):
        prompt_payload = {
            "role": "أنت مستشار خبير في إدارة المشاريع الحكومية والتخطيط التشغيلي.",
            "task": "حلّل حالة المهمة التالية واقترح توصيات تصحيحية محددة وقابلة للتنفيذ.",
            "context": context,
            "instructions": [
                "اقرأ السياق كاملاً بعناية.",
                "لا تقترح إجراءً تم تنفيذه بالفعل (تحقق من previous_recommendations و mitigation_actions).",
                "لا تكرر التوصيات السابقة.",
                "إذا كانت هناك إجراءات قيد التنفيذ، اقترح خطوات تكميلية أو تصحيحية.",
                "إذا كان الخطر نشطاً، قم بمعالجته.",
                "استخدم أرقاماً حقيقية من السياق.",
                "لا تخترع أسماء موظفين أو أقسام.",
                "اقترح من 3 إلى 5 توصيات.",
                "كل توصية يجب أن تكون محددة، قابلة للقياس، وقابلة للتنفيذ.",
                "الأولوية لكل توصية: high أو medium أو low."
            ],
            "output_format": {
                "recommendations": [
                    {
                        "text": "نص التوصية بالعربية",
                        "reasoning": "سبب التوصية بناءً على السياق",
                        "priority": "high"
                    }
                ]
            },
            "important": "أعد JSON فقط بدون أي نص إضافي أو Markdown."
        }


        prompt = json.dumps(prompt_payload, ensure_ascii=False, indent=2)


        response = ollama_client.generate(
            prompt,
            system_instruction="أنت مستشار إداري حكومي. أعد JSON فقط."
        )


        parsed = self._parse_json(response)
        if not parsed:
            return []


        raw_recs = parsed.get("recommendations", [])
        if not isinstance(raw_recs, list):
            return []


        result = []
        for r in raw_recs:
            if not isinstance(r, dict):
                continue


            text = str(r.get("text", "")).strip()
            reasoning = str(r.get("reasoning", "")).strip()
            priority = str(r.get("priority", "medium")).lower()


            if not text:
                continue


            if priority not in ("high", "medium", "low"):
                priority = "medium"


            result.append({
                "text": text,
                "reasoning": reasoning,
                "priority": priority,
                "source": "ollama"
            })


        return result


    def _parse_json(self, text):
        if not text:
            return None


        cleaned = text.strip()


        if cleaned.startswith("```json"):
            cleaned = cleaned[7:]
        if cleaned.startswith("```"):
            cleaned = cleaned[3:]
        if cleaned.endswith("```"):
            cleaned = cleaned[:-3]
        cleaned = cleaned.strip()


        try:
            return json.loads(cleaned)
        except json.JSONDecodeError:
            import re
            match = re.search(r"\{[\s\S]*\}", cleaned)
            if match:
                try:
                    return json.loads(match.group(0))
                except json.JSONDecodeError:
                    return None
        return None


    def _safe_fallback(self, context):
        task = context.get("task", {})
        risks = context.get("risks", [])
        progress = task.get("latest_progress", 0)
        days_since = task.get("days_since_last_update", 0)
        remaining = task.get("remaining_days", 0)


        recs = []


        if days_since > 14:
            recs.append({
                "text": f"تحديث حالة المهمة فوراً، فآخر تحديث كان منذ {days_since} يوم.",
                "reasoning": f"انقطاع المتابعة يمنع الكشف المبكر عن الانحرافات.",
                "priority": "high",
                "source": "fallback"
            })


        if remaining < 14 and progress < 60:
            recs.append({
                "text": f"مراجعة خطة المهمة وتسريع التنفيذ، فالموعد النهائي بعد {remaining} يوم فقط.",
                "reasoning": "الفارق الزمني بين المتبقي والإنجاز يهدد الموعد النهائي.",
                "priority": "high",
                "source": "fallback"
            })


        active_risks = [r for r in risks if r.get("level") in ("critical", "high")]
        if active_risks:
            recs.append({
                "text": f"معالجة {len(active_risks)} من المخاطر الحرجة المرتبطة بالمهمة.",
                "reasoning": "وجود مخاطر حرجة نشطة يهدد نجاح المهمة.",
                "priority": "high",
                "source": "fallback"
            })


        if not recs:
            recs.append({
                "text": "متابعة دورية أسبوعية للمهمة وتحديث نسبة الإنجاز.",
                "reasoning": "ضمان استمرارية المتابعة وعدم تراكم الانحرافات.",
                "priority": "medium",
                "source": "fallback"
            })


        return recs


    def _save_to_database(self, task_id, recommendations):
        db = SessionLocal()
        saved_ids = []
        try:
            default_status = db.query(DictStatus).filter(
                DictStatus.category == 'recommendation',
                DictStatus.is_default == True
            ).first()
            if not default_status:
                default_status = db.query(DictStatus).filter(
                    DictStatus.category == 'recommendation'
                ).first()


            priority_map = {}
            for p in db.query(DictPriority).all():
                priority_map[p.code] = p.priority_id


            for rec in recommendations:
                priority_code = rec.get("priority", "medium")
                priority_id = priority_map.get(priority_code) or priority_map.get("medium")


                new_rec = AIRecommendation(
                    task_id=int(task_id),
                    text=rec.get("text", ""),
                    reasoning=rec.get("reasoning", ""),
                    priority_id=priority_id,
                    status_id=default_status.status_id if default_status else None,
                    created_at=datetime.now()
                )
                db.add(new_rec)
                db.flush()
                saved_ids.append(new_rec.recommendation_id)


            db.commit()
        except Exception as e:
            print(f" خطأ في حفظ التوصيات: {str(e)}")
            db.rollback()
        finally:
            db.close()
        return saved_ids


    def _empty_response(self, task_id=None):
        return {
            "task_id": int(task_id) if task_id else None,
            "recommendations": [],
            "prediction_time": datetime.now().isoformat(),
            "model_version": self.model_version,
            "source": "empty"
        }


    def load_model(self):
        return True


    def train(self, force=False):
        return {
            "model_name": "Ollama-Powered Recommender",
            "f1_score": None,
            "accuracy": None,
            "version": self.model_version
        }

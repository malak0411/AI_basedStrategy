import json
from datetime import datetime
from app.database import SessionLocal
from app.models import (
    Risk, RiskMitigation, OperationalTask, MajorTask, Initiative,
    Program, StrategicGoal, StrategicPillar, Department,
    TaskAssignment, Employee, TaskProgressLog, TaskDependency,
    BudgetLine, AIRecommendation, DictStatus, DictPriority, DictRiskLevel,
    AiJob
)
from app.ai.services.ollama_client import ollama_client




class RiskRecommendationService:


    def __init__(self):
        self.db = SessionLocal()


    def generate_for_risk(self, risk_id, created_by=None):
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
                system_instruction="أنت مستشار في إدارة المخاطر. أعد JSON فقط بدون Markdown."
            )


            parsed = self._parse_response(response)
            recommendations = self._save_recommendations(risk, parsed, created_by)


            self._update_job(job_id, "completed", {
                "risk_id": risk_id,
                "recommendations_count": len(recommendations)
            })


            return {
                "success": True,
                "job_id": job_id,
                "risk_id": risk_id,
                "recommendations": recommendations,
                "assessment": parsed.get("risk_assessment", {})
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
                "target_date": risk.target_date.isoformat() if risk.target_date else None
            },
            "task": None,
            "previous_recommendations": [],
            "mitigation_actions": []
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


        if risk.status_id:
            status = self.db.query(DictStatus).filter(
                DictStatus.status_id == risk.status_id
            ).first()
            if status:
                context["risk"]["status"] = {
                    "code": status.code,
                    "name_ar": status.name_ar
                }


        if risk.task_id:
            task = self.db.query(OperationalTask).filter(
                OperationalTask.task_id == risk.task_id
            ).first()


            if task:
                context["task"] = self._build_task_context(task)


        previous_recs = self.db.query(AIRecommendation).filter(
            AIRecommendation.task_id == risk.task_id
        ).order_by(AIRecommendation.created_at.desc()).limit(5).all()


        for r in previous_recs:
            context["previous_recommendations"].append({
                "recommendation_id": r.recommendation_id,
                "text": r.text,
                "reasoning": r.reasoning,
                "created_at": r.created_at.isoformat() if r.created_at else None
            })


        mitigations = self.db.query(RiskMitigation).filter(
            RiskMitigation.risk_id == risk.risk_id
        ).order_by(RiskMitigation.created_at.desc()).all()


        for m in mitigations:
            m_status = None
            if m.status_id:
                st = self.db.query(DictStatus).filter(
                    DictStatus.status_id == m.status_id
                ).first()
                if st:
                    m_status = st.name_ar


            assigned_name = None
            if m.assigned_to:
                emp = self.db.query(Employee).filter(
                    Employee.employee_id == m.assigned_to
                ).first()
                if emp:
                    assigned_name = emp.full_name


            context["mitigation_actions"].append({
                "mitigation_id": m.mitigation_id,
                "action": m.action,
                "status": m_status,
                "assigned_to": assigned_name,
                "due_date": m.due_date.isoformat() if m.due_date else None,
                "completed_at": m.completed_at.isoformat() if m.completed_at else None,
                "notes": m.notes
            })


        return context


    def _build_task_context(self, task):
        major_task = self.db.query(MajorTask).filter(
            MajorTask.major_task_id == task.major_task_id
        ).first() if task.major_task_id else None


        initiative = None
        program = None
        goal = None
        pillar = None


        if major_task and major_task.initiative_id:
            initiative = self.db.query(Initiative).filter(
                Initiative.initiative_id == major_task.initiative_id
            ).first()


        if initiative and initiative.program_id:
            program = self.db.query(Program).filter(
                Program.program_id == initiative.program_id
            ).first()


        if program and program.goal_id:
            goal = self.db.query(StrategicGoal).filter(
                StrategicGoal.goal_id == program.goal_id
            ).first()


        if goal and goal.pillar_id:
            pillar = self.db.query(StrategicPillar).filter(
                StrategicPillar.pillar_id == goal.pillar_id
            ).first()


        department = None
        if task.department_id:
            d = self.db.query(Department).filter(
                Department.department_id == task.department_id
            ).first()
            if d:
                department = d.name


        assignments = self.db.query(TaskAssignment).filter(
            TaskAssignment.task_id == task.task_id,
            TaskAssignment.is_active == True
        ).all()


        assigned_names = []
        for a in assignments:
            emp = self.db.query(Employee).filter(
                Employee.employee_id == a.employee_id
            ).first()
            if emp:
                assigned_names.append(emp.full_name)


        progress_logs = self.db.query(TaskProgressLog).filter(
            TaskProgressLog.task_id == task.task_id
        ).order_by(TaskProgressLog.log_time.desc()).limit(5).all()


        latest_progress = 0
        if progress_logs:
            latest_progress = progress_logs[0].progress_percent or 0


        dependencies_count = self.db.query(TaskDependency).filter(
            TaskDependency.task_id == task.task_id
        ).count()


        budget_lines = self.db.query(BudgetLine).filter(
            BudgetLine.budgetable_id == task.task_id,
            BudgetLine.budgetable_type == 'operational_task'
        ).all()


        total_allocated = sum(float(bl.allocated_amount or 0) for bl in budget_lines)
        total_spent = sum(float(bl.spent_amount or 0) for bl in budget_lines)


        return {
            "task_id": task.task_id,
            "title": task.title,
            "description": task.description,
            "priority_id": task.priority_id,
            "start_date": task.start_date.isoformat() if task.start_date else None,
            "end_date": task.end_date.isoformat() if task.end_date else None,
            "estimated_hours": float(task.estimated_hours) if task.estimated_hours else 0,
            "progress_percent": latest_progress,
            "department": department,
            "assigned_employees": assigned_names,
            "dependencies_count": dependencies_count,
            "budget_allocated": total_allocated,
            "budget_spent": total_spent,
            "major_task": major_task.name if major_task else None,
            "initiative": initiative.name if initiative else None,
            "program": program.name if program else None,
            "goal": goal.title if goal else None,
            "pillar": pillar.name if pillar else None
        }


    def _build_prompt(self, context):
        payload = {
            "role": "أنت مستشار في إدارة المخاطر الاستراتيجية في بيئة حكومية.",
            "task": "حلل الخطر واقترح توصيات عملية.",
            "context": context,
            "rules": [
                "لا تقترح إجراءً تم تنفيذه بنجاح.",
                "لا تتجاهل الإجراءات قيد التنفيذ.",
                "اقترح من 2 إلى 4 توصيات.",
                "كل توصية محددة وقابلة للتنفيذ.",
                "لا تخترع موظفين أو أقسامًا.",
                "لا تغير حالة الخطر.",
                "أعد JSON فقط."
            ],
            "output_format": {
                "risk_assessment": {
                    "current_state": "active|reduced|resolved|escalated",
                    "severity": "critical|high|medium|low",
                    "reason": "سبب التقييم"
                },
                "recommendations": [
                    {
                        "text": "نص التوصية",
                        "reasoning": "سبب التوصية",
                        "priority": "high|medium|low"
                    }
                ]
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


    def _save_recommendations(self, risk, parsed, created_by):
        recommendations = parsed.get("recommendations", [])
        if not recommendations:
            return []


        default_status = self.db.query(DictStatus).filter(
            DictStatus.category == 'recommendation',
            DictStatus.is_default == True
        ).first()


        if not default_status:
            default_status = self.db.query(DictStatus).filter(
                DictStatus.category == 'recommendation'
            ).first()


        priority_map = {"high": "high", "medium": "medium", "low": "low"}
        saved = []


        for rec in recommendations[:4]:
            priority_code = priority_map.get(rec.get("priority", "medium"), "medium")
            priority = self.db.query(DictPriority).filter(
                DictPriority.code == priority_code
            ).first()


            if not priority:
                priority = self.db.query(DictPriority).filter(
                    DictPriority.code == "medium"
                ).first()


            reasoning_json = json.dumps({
                "risk_id": risk.risk_id,
                "risk_name": risk.name,
                "text": rec.get("reasoning", "")
            }, ensure_ascii=False)


            new_rec = AIRecommendation(
                task_id=risk.task_id,
                text=rec.get("text", ""),
                reasoning=reasoning_json,
                priority_id=priority.priority_id if priority else None,
                status_id=default_status.status_id if default_status else None,
                created_at=datetime.now()
            )


            self.db.add(new_rec)
            self.db.flush()


            saved.append({
                "recommendation_id": new_rec.recommendation_id,
                "text": new_rec.text,
                "priority": priority_code,
                "risk_id": risk.risk_id
            })


        self.db.commit()
        return saved


    def _create_job(self, risk_id, created_by):
        job = AiJob(
            job_type="risk_recommendation",
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

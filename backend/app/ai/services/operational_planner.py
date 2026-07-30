import json
import re
from datetime import datetime
from app.database import SessionLocal
from app.models import MajorTask, OperationalTask, TaskAssignment, Employee, Department, AiJob
from app.ai.services.ollama_client import ollama_client

class OperationalPlanner:
    def __init__(self):
        self.db = SessionLocal()

    def get_major_task_context(self, major_task_id: int) -> dict:
        t = self.db.query(MajorTask).filter(MajorTask.major_task_id == major_task_id).first()
        if not t:
            raise ValueError("Major task not found")

        depts = self.db.query(Department).filter(Department.is_active == True).all()
        emps = self.db.query(Employee).filter(Employee.is_active == True).all()

        return {
            "major_task": {
                "id": t.major_task_id,
                "name": t.name,
                "description": t.description or "",
                "duration": t.estimated_duration_days,
                "is_cross": t.is_cross_department
            },
            "departments": [{"id": d.department_id, "name": d.name} for d in depts],
            "employees": [{"id": e.employee_id, "name": e.full_name, "dept_id": e.department_id} for e in emps]
        }

    def build_prompt(self, ctx: dict, instructions: str = "") -> str:
        return json.dumps({
            "task": "Generate 3-5 operational tasks for this major task. Return JSON array.",
            "major_task": ctx["major_task"],
            "departments": ctx["departments"],
            "instructions": instructions or "",
            "format": [{
                "title": "Task title in Arabic",
                "description": "Description in Arabic",
                "priority_id": 2,
                "estimated_hours": 40,
                "start_date": "YYYY-MM-DD",
                "end_date": "YYYY-MM-DD",
                "department_id": 1,
                "assigned_employees": []
            }]
        }, ensure_ascii=False)

    def parse_response(self, text: str) -> list:
        text = text.strip()
        for prefix in ["```json", "```"]:
            if text.startswith(prefix):
                text = text[len(prefix):]
        if text.endswith("```"):
            text = text[:-3]
        text = text.strip()
        try:
            data = json.loads(text)
            if isinstance(data, list):
                return data
            if isinstance(data, dict) and "operational_tasks" in data:
                return data["operational_tasks"]
        except json.JSONDecodeError:
            pass
        match = re.search(r'\[.*\]', text, re.DOTALL)
        if match:
            try:
                return json.loads(match.group())
            except json.JSONDecodeError:
                pass
        return []

    def save_tasks(self, major_task_id: int, tasks: list):
        saved = []
        for t in tasks:
            ot = OperationalTask(
                major_task_id=major_task_id,
                department_id=t.get("department_id"),
                title=t["title"],
                description=t.get("description", ""),
                status_id=5,
                priority_id=t.get("priority_id", 2),
                start_date=t.get("start_date"),
                end_date=t.get("end_date"),
                estimated_hours=t.get("estimated_hours", 40),
                is_active=True
            )
            self.db.add(ot)
            self.db.flush()
            for eid in t.get("assigned_employees", []):
                self.db.add(TaskAssignment(task_id=ot.task_id, employee_id=eid, assigned_by=1))
            saved.append({"id": ot.task_id, "title": ot.title})
        self.db.commit()
        return saved

    def create_job(self, major_task_id: int) -> int:
        job = AiJob(
            job_type="operational_task_generation",
            status="pending",
            input_data=json.dumps({"major_task_id": major_task_id})
        )
        self.db.add(job)
        self.db.commit()
        self.db.refresh(job)
        return job.job_id

    def update_job(self, job_id: int, status: str, result: dict = None):
        job = self.db.query(AiJob).filter(AiJob.job_id == job_id).first()
        if job:
            job.status = status
            if result:
                job.result_json = json.dumps(result, ensure_ascii=False)
            job.updated_at = datetime.now()
            self.db.commit()

    def close(self):
        if self.db:
            self.db.close()

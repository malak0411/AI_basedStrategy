import json
import re
from datetime import datetime, timedelta
from app.database import SessionLocal
from app.models import MajorTask, OperationalTask, Department, AiJob
from app.api.dependencies import get_current_user
class OperationalPlanner:
    def __init__(self):
        self.db = SessionLocal()


    def get_major_task_context(self, major_task_id: int) -> dict:
        task = self.db.query(MajorTask).filter(
            MajorTask.major_task_id == major_task_id
        ).first()


        if not task:
            raise ValueError("Major task not found")


        return {
            "major_task": {
                "id": task.major_task_id,
                "name": task.name,
                "description": task.description or "",
                "duration_days": task.estimated_duration_days or 30,
                "is_cross_department": bool(task.is_cross_department)
            }
        }


    def build_prompt(self, ctx: dict, instructions: str = "") -> str:
        today = datetime.now().date()
        duration = int(ctx["major_task"].get("duration_days") or 30)
        end_date = today + timedelta(days=max(duration - 1, 0))


        return json.dumps({
            "task": "Generate 3 to 5 operational tasks for the given major task.",
            "language": "Arabic",
            "today": today.isoformat(),
            "major_task_end_date": end_date.isoformat(),
            "major_task": ctx["major_task"],
            "instructions": instructions or "",
            "rules": [
                "Return ONLY a valid JSON array.",
                "Generate 3 to 5 operational tasks.",
                "Every task must contain title, description, priority_id, estimated_hours, start_date and end_date.",
                "title must be a clear operational task title in Arabic.",
                "description must explain the work only and must not contain dates or estimated hours.",
                "estimated_hours must be a number between 1 and 160.",
                "priority_id must be an integer.",
                "start_date must be a real date in YYYY-MM-DD format.",
                "end_date must be a real date in YYYY-MM-DD format.",
                "Do not use YYYY-MM-DD literally.",
                "Do not use placeholder dates.",
                "start_date must not be before today.",
                "end_date must be equal to or later than start_date.",
                "Do not include department_id.",
                "Do not include assigned_employees.",
                "Do not include employee_id.",
                "Do not include any fields other than title, description, priority_id, estimated_hours, start_date and end_date."
            ],
            "format": [
                {
                    "title": "إعداد خطة تنفيذية للمهمة",
                    "description": "إعداد خطة عملية واضحة لتنفيذ الأعمال المطلوبة ضمن المهمة الرئيسية.",
                    "priority_id": 2,
                    "estimated_hours": 40,
                    "start_date": today.isoformat(),
                    "end_date": end_date.isoformat()
                }
            ]
        }, ensure_ascii=False)


    def parse_response(self, text: str) -> list:
        text = text.strip()


        text = re.sub(r"^```json\s*", "", text, flags=re.IGNORECASE)
        text = re.sub(r"^```\s*", "", text)
        text = re.sub(r"\s*```$", "", text)


        try:
            data = json.loads(text)


            if isinstance(data, list):
                return data


            if isinstance(data, dict):
                tasks = data.get("operational_tasks")


                if isinstance(tasks, list):
                    return tasks


        except json.JSONDecodeError:
            pass


        match = re.search(r".*", text, re.DOTALL)


        if match:
            try:
                data = json.loads(match.group())


                if isinstance(data, list):
                    return data


            except json.JSONDecodeError:
                pass


        return []


    def normalize_tasks(self, tasks: list, major_task_id: int) -> list:
        major_task = self.db.query(MajorTask).filter(
            MajorTask.major_task_id == major_task_id
        ).first()


        if not major_task:
            raise ValueError("Major task not found")


        today = datetime.now().date()


        duration = int(
            major_task.estimated_duration_days
            or 30
        )


        major_end = today + timedelta(
            days=max(duration - 1, 0)
        )


        normalized = []


        for index, task in enumerate(tasks):
            if not isinstance(task, dict):
                continue


            title = str(
                task.get("title")
                or task.get("name")
                or ""
            ).strip()


            description = str(
                task.get("description")
                or ""
            ).strip()


            if not title:
                continue


            priority_id = task.get("priority_id", 2)


            try:
                priority_id = int(priority_id)
            except (TypeError, ValueError):
                priority_id = 2


            estimated_hours = task.get(
                "estimated_hours",
                40
            )


            try:
                estimated_hours = float(
                    estimated_hours
                )
            except (TypeError, ValueError):
                estimated_hours = 40


            if estimated_hours <= 0:
                estimated_hours = 40


            if estimated_hours > 160:
                estimated_hours = 160


            start_date = self.parse_date(
                task.get("start_date")
            )


            end_date = self.parse_date(
                task.get("end_date")
            )


            if not start_date:
                start_date = today + timedelta(
                    days=index
                )


            if start_date < today:
                start_date = today


            if not end_date:
                duration_days = max(
                    1,
                    int(
                        (estimated_hours + 7) // 8
                    )
                )


                end_date = start_date + timedelta(
                    days=duration_days - 1
                )


            if end_date < start_date:
                end_date = start_date


            if end_date > major_end:
                end_date = major_end


            if end_date < start_date:
                end_date = start_date


            description = self.clean_description(
                description
            )


            normalized.append({
                "title": title[:255],
                "description": description,
                "priority_id": priority_id,
                "estimated_hours": estimated_hours,
                "start_date": start_date.isoformat(),
                "end_date": end_date.isoformat()
            })


        return normalized


    def parse_date(self, value):
        if not value:
            return None


        if not isinstance(value, str):
            return None


        value = value.strip()


        if value in [
            "YYYY-MM-DD",
            "yyyy-mm-dd",
            "YYYY/MM/DD",
            "yyyy/mm/dd",
            ""
        ]:
            return None


        patterns = [
            "%Y-%m-%d",
            "%Y/%m/%d",
            "%d-%m-%Y",
            "%d/%m/%Y"
        ]


        for pattern in patterns:
            try:
                return datetime.strptime(
                    value,
                    pattern
                ).date()
            except ValueError:
                continue


        return None


    def clean_description(self, description: str) -> str:
        description = re.sub(
            r"\b\d{4}-\d{2}-\d{2}\b",
            "",
            description
        )


        description = re.sub(
            r"\b\d+\s*(ساعة|ساعات|hour|hours)\b",
            "",
            description,
            flags=re.IGNORECASE
        )


        description = re.sub(
            r"\s+",
            " ",
            description
        ).strip()


        return description


    def save_tasks(
        self,
        major_task_id: int,
        tasks: list,
        department_id: int,
        created_by: int
    ):
        normalized_tasks = self.normalize_tasks(
            tasks,
            major_task_id
        )


        if not normalized_tasks:
            raise ValueError(
                "No valid operational tasks to save"
            )


        department = self.db.query(
            Department
        ).filter(
            Department.department_id == department_id,
            Department.is_active == True
        ).first()


        if not department:
            raise ValueError(
                "Department not found or inactive"
            )


        saved = []


        for task in normalized_tasks:
            operational_task = OperationalTask(
                major_task_id=major_task_id,
                department_id=department_id,
                title=task["title"],
                description=task["description"],
                status_id=16,
                priority_id=task["priority_id"],
                start_date=self.parse_date(task["start_date"]),
                end_date=self.parse_date(task["end_date"]),
                estimated_hours=task["estimated_hours"],
                actual_hours=None,
                is_cross_functional=False,
                created_by=created_by,
                is_active=True,
            )

            self.db.add(operational_task)
            self.db.flush()


            saved.append({
                "id": operational_task.task_id,
                "title": operational_task.title,
                "description": operational_task.description,
                "priority_id": operational_task.priority_id,
                "estimated_hours": float(
                    operational_task.estimated_hours
                ),
                "start_date": operational_task.start_date.isoformat()
                if operational_task.start_date
                else None,
                "end_date": operational_task.end_date.isoformat()
                if operational_task.end_date
                else None,
                "department_id": operational_task.department_id
            })


        self.db.commit()


        return saved


    def create_job(self, major_task_id: int) -> int:
        job = AiJob(
            job_type="operational_task_generation",
            status="pending",
            input_data=json.dumps(
                {
                    "major_task_id": major_task_id
                },
                ensure_ascii=False
            )
        )


        self.db.add(job)
        self.db.commit()
        self.db.refresh(job)


        return job.job_id


    def update_job(
        self,
        job_id: int,
        status: str,
        result: dict = None
    ):
        job = self.db.query(AiJob).filter(
            AiJob.job_id == job_id
        ).first()


        if job:
            job.status = status


            if result is not None:
                job.result_json = json.dumps(
                    result,
                    ensure_ascii=False
                )


            job.updated_at = datetime.now()


            self.db.commit()


    def close(self):
        if self.db:
            self.db.close()

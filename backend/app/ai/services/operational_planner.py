import json
import re
from datetime import datetime, timedelta


from app.database import SessionLocal
from app.models import (
    MajorTask,
    OperationalTask,
    Department,
    AiJob,
    Initiative,
    Program,
    StrategicGoal,
    MajorTaskDepartment,
    DictPriority,
)




class OperationalPlanner:
    def __init__(self):
        self.db = SessionLocal()


    def close(self):
        if self.db:
            self.db.close()



    def _safe_iso(self, value):
        try:
            return value.isoformat() if value else None
        except Exception:
            return None


    def parse_date(self, value):
        if not value or not isinstance(value, str):
            return None
        value = value.strip()
        if value.lower() in ("yyyy-mm-dd", "yyyy/mm/dd", ""):
            return None
        for fmt in ("%Y-%m-%d", "%Y/%m/%d", "%d-%m-%Y", "%d/%m/%Y"):
            try:
                return datetime.strptime(value, fmt).date()
            except ValueError:
                continue
        return None


    def clean_description(self, description: str) -> str:
        description = re.sub(r"\b\d{4}-\d{2}-\d{2}\b", "", description or "")
        description = re.sub(
            r"\b\d+\s*(ساعة|ساعات|hour|hours)\b", "", description, flags=re.IGNORECASE
        )
        return re.sub(r"\s+", " ", description).strip()


    def _compute_window(self, ctx: dict):
        today = datetime.now().date()
        duration = int(ctx["major_task"].get("duration_days") or 30)


        ini = ctx.get("initiative") or {}
        ini_start = self.parse_date(ini.get("start_date")) if ini else None
        ini_end = self.parse_date(ini.get("end_date")) if ini else None


        effective_start = max(today, ini_start) if ini_start else today
        mt_end_candidate = effective_start + timedelta(days=duration - 1)
        effective_end = min(mt_end_candidate, ini_end) if ini_end else mt_end_candidate
        if effective_end < effective_start:
            effective_end = effective_start
        return today, duration, effective_start, effective_end



    def get_major_task_context(self, major_task_id: int) -> dict:
        task = (
            self.db.query(MajorTask)
            .filter(
                MajorTask.major_task_id == major_task_id,
                MajorTask.is_active == True,
            )
            .first()
        )
        if not task:
            raise ValueError("Major task not found or inactive")


        initiative = (
            self.db.query(Initiative)
            .filter(Initiative.initiative_id == task.initiative_id)
            .first()
        )
        program = None
        goal = None
        if initiative:
            program = (
                self.db.query(Program)
                .filter(Program.program_id == initiative.program_id)
                .first()
            )
            if program:
                goal = (
                    self.db.query(StrategicGoal)
                    .filter(StrategicGoal.goal_id == program.goal_id)
                    .first()
                )


        departments = []
        for row in (
            self.db.query(MajorTaskDepartment)
            .filter(MajorTaskDepartment.major_task_id == major_task_id)
            .all()
        ):
            dept = (
                self.db.query(Department)
                .filter(Department.department_id == row.department_id)
                .first()
            )
            if dept:
                departments.append(
                    {
                        "department_id": dept.department_id,
                        "department_name": dept.name,
                        "responsibility_type": row.responsibility_type or "SUPPORT",
                    }
                )


        priorities = []
        for p in self.db.query(DictPriority).all():
            priorities.append(
                {
                    "priority_id": p.priority_id,
                    "code": getattr(p, "code", None),
                    "name_ar": getattr(p, "name_ar", None),
                }
            )


        return {
            "major_task": {
                "id": task.major_task_id,
                "name": task.name,
                "description": task.description or "",
                "duration_days": task.estimated_duration_days or 30,
                "priority_id": task.priority_id,
                "is_cross_department": bool(task.is_cross_department),
            },
            "initiative": (
                {
                    "id": initiative.initiative_id,
                    "name": initiative.name,
                    "description": initiative.description or "",
                    "start_date": self._safe_iso(initiative.start_date),
                    "end_date": self._safe_iso(initiative.end_date),
                    "priority_id": getattr(initiative, "priority_id", None),
                }
                if initiative
                else None
            ),
            "program": (
                {
                    "id": program.program_id,
                    "name": program.name,
                    "description": program.description or "",
                    "start_date": self._safe_iso(program.start_date),
                    "end_date": self._safe_iso(program.end_date),
                }
                if program
                else None
            ),
            "goal": (
                {
                    "id": goal.goal_id,
                    "title": goal.title,
                    "description": goal.description or "",
                    "target_date": self._safe_iso(goal.target_date),
                }
                if goal
                else None
            ),
            "departments": departments,
            "priorities": priorities,
            "today": datetime.now().date().isoformat(),
        }


    def build_prompt(self, ctx: dict, instructions: str = "") -> str:
        today, duration, effective_start, effective_end = self._compute_window(ctx)
        total_days = (effective_end - effective_start).days + 1
        max_total_hours = max(8, total_days * 8)
        max_tasks = min(5, max(3, max_total_hours // 8))
        priority_ids = [p["priority_id"] for p in ctx.get("priorities", [])] or [1, 2, 3]


        return json.dumps(
            {
                "task": "Generate operational tasks for the given major task aligned with its strategic context.",
                "language": "Arabic",
                "today": today.isoformat(),
                "effective_start_date": effective_start.isoformat(),
                "effective_end_date": effective_end.isoformat(),
                "major_task_duration_days": duration,
                "max_total_hours_for_all_tasks": max_total_hours,
                "allowed_priority_ids": priority_ids,
                "strategic_goal": ctx.get("goal"),
                "program": ctx.get("program"),
                "initiative": ctx.get("initiative"),
                "major_task": ctx["major_task"],
                "departments": ctx.get("departments", []),
                "instructions": instructions or "",
                "rules": [
                    "Return ONLY a valid JSON array (no markdown, no comments).",
                    f"Generate between 3 and {max_tasks} operational tasks.",
                    "Each task MUST contain exactly: title, description, priority_id, estimated_hours, start_date, end_date.",
                    "title in Arabic, clear, specific, actionable (3-120 chars).",
                    "description in Arabic, 20-400 chars, describes the work only — no dates, no hours.",
                    "priority_id MUST be one of: " + str(priority_ids),
                    f"SUM of estimated_hours across ALL tasks MUST NOT exceed {max_total_hours}.",
                    "Each estimated_hours between 1 and 120.",
                    "start_date and end_date MUST be YYYY-MM-DD.",
                    f"start_date MUST be between {effective_start.isoformat()} and {effective_end.isoformat()}.",
                    f"end_date MUST be between start_date and {effective_end.isoformat()}.",
                    "AVOID duplicates and generic tasks.",
                    "Align tasks with the strategic goal.",
                    "Do NOT include department_id, employee_id, status_id, dependency_id.",
                ],
                "format_example": [
                    {
                        "title": "تحليل المتطلبات التفصيلية للمهمة",
                        "description": "دراسة وتحليل المتطلبات الفنية والوظيفية اللازمة لتنفيذ المهمة الرئيسية.",
                        "priority_id": priority_ids[0] if priority_ids else 2,
                        "estimated_hours": 16,
                        "start_date": effective_start.isoformat(),
                        "end_date": (effective_start + timedelta(days=1)).isoformat(),
                    }
                ],
            },
            ensure_ascii=False,
        )


    def build_edit_prompt(self, ctx: dict, current_tasks: list, instruction: str) -> str:
        base = json.loads(self.build_prompt(ctx, ""))
        base["task"] = (
            "Apply the user's instruction to MODIFY the current operational tasks "
            "and return the COMPLETE updated array."
        )
        base["current_tasks"] = current_tasks
        base["user_instruction"] = instruction
        base["rules"].insert(0, "Return ALL tasks after modification.")
        return json.dumps(base, ensure_ascii=False)


    def parse_response(self, text: str) -> list:
        if not text or not isinstance(text, str):
            return []
        text = text.strip()
        text = re.sub(r"^```json\s*", "", text, flags=re.IGNORECASE)
        text = re.sub(r"^```\s*", "", text)
        text = re.sub(r"\s*```$", "", text)


        try:
            data = json.loads(text)
            if isinstance(data, list):
                return data
            if isinstance(data, dict):
                for key in ("operational_tasks", "tasks", "data", "result"):
                    if isinstance(data.get(key), list):
                        return data[key]
        except json.JSONDecodeError:
            pass


        m = re.search(r"\[[\s\S]*\]", text)
        if m:
            try:
                data = json.loads(m.group())
                if isinstance(data, list):
                    return data
            except json.JSONDecodeError:
                pass
        return []


    def normalize_tasks(self, tasks: list, major_task_id: int) -> list:
        ctx = self.get_major_task_context(major_task_id)
        _, _, effective_start, effective_end = self._compute_window(ctx)


        total_days = (effective_end - effective_start).days + 1
        max_total_hours = max(8, total_days * 8)
        valid_priorities = [p["priority_id"] for p in ctx.get("priorities", [])] or [1, 2, 3]


        normalized = []
        seen = set()


        for task in tasks or []:
            if not isinstance(task, dict):
                continue


            title = str(task.get("title") or task.get("name") or "").strip()
            if len(title) < 3:
                continue


            key = re.sub(r"\s+", " ", title).lower()
            if key in seen:
                continue
            seen.add(key)


            description = self.clean_description(str(task.get("description") or "").strip())
            if not description:
                description = title


            try:
                priority_id = int(task.get("priority_id", 2))
            except (TypeError, ValueError):
                priority_id = 2
            if priority_id not in valid_priorities:
                priority_id = valid_priorities[0]


            try:
                estimated_hours = float(task.get("estimated_hours", 40))
            except (TypeError, ValueError):
                estimated_hours = 40
            if estimated_hours <= 0:
                estimated_hours = 8
            if estimated_hours > 120:
                estimated_hours = 120


            start_date = self.parse_date(task.get("start_date"))
            end_date = self.parse_date(task.get("end_date"))


            if not start_date or start_date < effective_start:
                start_date = effective_start
            if start_date > effective_end:
                start_date = effective_end


            if not end_date:
                days_needed = max(1, int((estimated_hours + 7) // 8))
                end_date = start_date + timedelta(days=days_needed - 1)


            if end_date < start_date:
                end_date = start_date
            if end_date > effective_end:
                end_date = effective_end


            normalized.append(
                {
                    "title": title[:255],
                    "description": description[:1000],
                    "priority_id": priority_id,
                    "estimated_hours": float(estimated_hours),
                    "start_date": start_date.isoformat(),
                    "end_date": end_date.isoformat(),
                }
            )


        total = sum(t["estimated_hours"] for t in normalized)
        if total > max_total_hours and normalized:
            ratio = max_total_hours / total
            for t in normalized:
                t["estimated_hours"] = max(1.0, round(t["estimated_hours"] * ratio, 1))


        return normalized


    def save_tasks(self, major_task_id: int, tasks: list, department_id: int, created_by: int):
        normalized = self.normalize_tasks(tasks, major_task_id)
        if not normalized:
            raise ValueError("No valid operational tasks to save")


        department = (
            self.db.query(Department)
            .filter(
                Department.department_id == department_id,
                Department.is_active == True,
            )
            .first()
        )
        if not department:
            raise ValueError("Department not found or inactive")


        titles = [t["title"] for t in normalized]
        existing = (
            self.db.query(OperationalTask)
            .filter(
                OperationalTask.major_task_id == major_task_id,
                OperationalTask.department_id == department_id,
                OperationalTask.title.in_(titles),
                OperationalTask.is_active == True,
            )
            .all()
        )
        existing_titles = {e.title.strip().lower() for e in existing}


        saved = []
        for task in normalized:
            if task["title"].lower() in existing_titles:
                continue


            ot = OperationalTask(
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
            self.db.add(ot)
            self.db.flush()
            saved.append(
                {
                    "id": ot.task_id,
                    "title": ot.title,
                    "description": ot.description,
                    "priority_id": ot.priority_id,
                    "estimated_hours": float(ot.estimated_hours or 0),
                    "start_date": ot.start_date.isoformat() if ot.start_date else None,
                    "end_date": ot.end_date.isoformat() if ot.end_date else None,
                    "department_id": ot.department_id,
                }
            )


        self.db.commit()
        return saved


    def create_job(self, major_task_id: int, created_by: int = None) -> int:
        job = AiJob(
            job_type="operational_task_generation",
            status="pending",
            input_data=json.dumps({"major_task_id": major_task_id}, ensure_ascii=False),
        )
        if created_by is not None and hasattr(job, "created_by"):
            job.created_by = created_by
        self.db.add(job)
        self.db.commit()
        self.db.refresh(job)
        return job.job_id


    def update_job(self, job_id: int, status: str, result: dict = None):
        job = self.db.query(AiJob).filter(AiJob.job_id == job_id).first()
        if job:
            job.status = status
            if result is not None:
                job.result_json = json.dumps(result, ensure_ascii=False)
            job.updated_at = datetime.now()
            self.db.commit()

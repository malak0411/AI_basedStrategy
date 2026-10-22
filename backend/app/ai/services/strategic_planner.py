import json
import re
from datetime import datetime


from app.database import SessionLocal
from app.models import (
    Initiative, Program, StrategicGoal, StrategicPillar, StrategicVision,
    SWOTAnalysis, PESTELAnalysis, Department, BudgetLine, Risk,
    GoalKPI, KPI, SystemConfig, AiJob, MajorTask, MajorTaskDepartment,
    DictPriority,
)
from app.ai.services.ollama_client import ollama_client




class StrategicPlanner:
    PRIORITY_MAP = {"High": 3, "Medium": 2, "Low": 1}
    PRIORITY_MAP_INV = {3: "High", 2: "Medium", 1: "Low"}
    RESPONSIBILITY_TYPES = ("LEAD", "SUPPORT")
    MIN_DURATION = 10
    MAX_DURATION = 365


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


    def _parse_date(self, value):
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


    # ===========================================================
    # 1) Context (full strategic chain)
    # ===========================================================
    def get_initiative_context(self, initiative_id: int) -> dict:
        initiative = self.db.query(Initiative).filter(
            Initiative.initiative_id == initiative_id,
            Initiative.is_active == True,
        ).first()
        if not initiative:
            raise ValueError("Initiative not found or inactive")


        program = None
        if initiative.program_id:
            program = self.db.query(Program).filter(Program.program_id == initiative.program_id).first()


        goal = None
        if program and program.goal_id:
            goal = self.db.query(StrategicGoal).filter(StrategicGoal.goal_id == program.goal_id).first()


        pillar = None
        if goal and goal.pillar_id:
            pillar = self.db.query(StrategicPillar).filter(StrategicPillar.pillar_id == goal.pillar_id).first()


        vision = self.db.query(StrategicVision).filter(StrategicVision.is_current == True).first()
        swot = self.db.query(SWOTAnalysis).first()
        pestel = self.db.query(PESTELAnalysis).first()


        departments = []
        for d in self.db.query(Department).filter(Department.is_active == True).all():
            departments.append({
                "department_id": d.department_id,
                "name": d.name,
                "code": d.code or "",
                "description": (d.description or "")[:200],
                "level": d.level or 1,
            })


        budgets = self.db.query(BudgetLine).filter(
            BudgetLine.budgetable_id == initiative_id,
            BudgetLine.budgetable_type == "initiative",
        ).all()
        total_budget = sum(float(b.allocated_amount or 0) for b in budgets)


        risks = self.db.query(Risk).limit(5).all()
        risk_list = [{"name": r.name, "level": r.risk_level_id} for r in risks]


        kpis = []
        if goal:
            for gk in self.db.query(GoalKPI).filter(GoalKPI.goal_id == goal.goal_id).all():
                kpi = self.db.query(KPI).filter(KPI.kpi_id == gk.kpi_id).first()
                if kpi:
                    kpis.append({"name": kpi.name, "target": float(gk.target_value or 0)})


        priorities = []
        for p in self.db.query(DictPriority).all():
            priorities.append({
                "priority_id": p.priority_id,
                "code": getattr(p, "code", None),
                "name_ar": getattr(p, "name_ar", None),
                "level": getattr(p, "level", None),
            })


        return {
            "initiative": {
                "id": initiative.initiative_id,
                "name": initiative.name,
                "description": initiative.description or "",
                "start_date": self._safe_iso(initiative.start_date),
                "end_date": self._safe_iso(initiative.end_date),
                "budget": float(initiative.budget_estimate or 0),
                "priority_id": initiative.priority_id,
                "status_id": initiative.status_id,
            },
            "program": ({
                "id": program.program_id,
                "name": program.name,
                "description": program.description or "",
                "start_date": self._safe_iso(program.start_date),
                "end_date": self._safe_iso(program.end_date),
            } if program else None),
            "goal": ({
                "id": goal.goal_id,
                "title": goal.title,
                "description": goal.description or "",
                "target_date": self._safe_iso(goal.target_date),
            } if goal else None),
            "pillar": ({
                "id": pillar.pillar_id,
                "name": pillar.name,
                "description": getattr(pillar, "description", "") or "",
            } if pillar else None),
            "vision": (vision.text[:500] if vision and getattr(vision, "text", None) else ""),
            "swot": self._format_swot(swot),
            "pestel": self._format_pestel(pestel),
            "departments": departments,
            "priorities": priorities,
            "total_budget": total_budget,
            "risks": risk_list,
            "kpis": kpis,
        }


    def _format_swot(self, swot):
        if not swot:
            return ""
        parts = []
        for label, attr in (("S", "strengths"), ("W", "weaknesses"),
                            ("O", "opportunities"), ("T", "threats")):
            val = getattr(swot, attr, None)
            if val:
                parts.append(f"{label}:{str(val)[:120]}")
        return " | ".join(parts)


    def _format_pestel(self, pestel):
        if not pestel:
            return ""
        parts = []
        for label, attr in (("P", "political"), ("E", "economic"),
                            ("S", "social"), ("T", "technological"),
                            ("En", "environmental"), ("L", "legal")):
            val = getattr(pestel, attr, None)
            if val:
                parts.append(f"{label}:{str(val)[:80]}")
        return " | ".join(parts)


    # ===========================================================
    # 2) Prompt (generation)
    # ===========================================================
    def build_prompt(self, ctx: dict, user_instructions: str = "") -> str:
        ini = ctx["initiative"]
        num_tasks_hint = self._estimate_task_count(ctx)


        return json.dumps({
            "task": "Generate MAJOR tasks for this government initiative aligned with its strategic goal.",
            "language": "Arabic",
            "initiative": ini,
            "program": ctx.get("program"),
            "goal": ctx.get("goal"),
            "pillar": ctx.get("pillar"),
            "vision": ctx.get("vision", "")[:300],
            "swot": ctx.get("swot", ""),
            "pestel": ctx.get("pestel", ""),
            "total_budget": ctx.get("total_budget", 0),
            "risks": [r["name"] for r in ctx.get("risks", [])],
            "kpis": [k["name"] for k in ctx.get("kpis", [])],
            "available_departments": [
                {"id": d["department_id"], "name": d["name"],
                 "description": d["description"][:150], "level": d["level"]}
                for d in ctx.get("departments", [])
            ],
            "user_instructions": user_instructions or "",
            "rules": [
                "Return ONLY a valid JSON array (no markdown, no comments).",
                f"Generate between 3 and {num_tasks_hint} MAJOR tasks.",
                "Each task MUST contain: name, description, objective, priority, estimated_duration_days, deliverables (array), dependencies (array).",
                "name in Arabic, 5-120 chars, specific and measurable.",
                "description in Arabic, 20-500 chars, describes WHAT not HOW.",
                "objective in Arabic, 10-200 chars, links to the strategic goal.",
                "priority MUST be exactly one of: High, Medium, Low.",
                f"estimated_duration_days between {self.MIN_DURATION} and {self.MAX_DURATION}.",
                "Sum of durations should fit within initiative date window.",
                "deliverables: 1-3 concrete deliverables per task.",
                "dependencies: names of other tasks in the array this task depends on (or empty array).",
                "AVOID duplicates and overlapping scope.",
                "AVOID operational/day-to-day tasks — those come later.",
                "Tasks MUST collectively cover the initiative scope and align with the goal.",
                "Do NOT include: department_id, employee_id, status_id in the JSON.",
            ],
            "format_example": [
                {
                    "name": "تطوير إطار حوكمة المبادرة",
                    "description": "بناء إطار الحوكمة والسياسات التشغيلية التي تضمن تنفيذ المبادرة وفق الأهداف الاستراتيجية.",
                    "objective": "ضمان اتساق تنفيذ المبادرة مع الرؤية والركيزة الاستراتيجية.",
                    "priority": "High",
                    "estimated_duration_days": 45,
                    "deliverables": ["وثيقة حوكمة معتمدة", "أدلة إجرائية"],
                    "dependencies": [],
                }
            ],
        }, ensure_ascii=False)


    def _estimate_task_count(self, ctx: dict) -> int:
        try:
            budget = float(ctx.get("total_budget", 0) or ctx["initiative"].get("budget", 0) or 0)
        except (TypeError, ValueError):
            budget = 0
        try:
            start = self._parse_date(ctx["initiative"].get("start_date"))
            end = self._parse_date(ctx["initiative"].get("end_date"))
            days = (end - start).days if start and end else 0
        except Exception:
            days = 0


        count = 4
        if budget > 500_000 or days > 180:
            count = 5
        if budget > 2_000_000 or days > 365:
            count = 6
        return max(3, min(6, count))


    def build_edit_prompt(self, ctx: dict, current_tasks: list, instruction: str) -> str:
        base = json.loads(self.build_prompt(ctx, ""))
        base["task"] = (
            "Apply the user's instruction to MODIFY the current MAJOR tasks. "
            "Return the COMPLETE updated array."
        )
        base["current_tasks"] = current_tasks
        base["user_instruction"] = instruction
        base["rules"].insert(0, "Return ALL tasks after modification.")
        return json.dumps(base, ensure_ascii=False)


    # ===========================================================
    # 3) Parsing
    # ===========================================================
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
                for key in ("major_tasks", "tasks", "data", "result"):
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


    # ===========================================================
    # 4) Normalization & department assignment
    # ===========================================================
    def normalize_tasks(self, raw_tasks: list, ctx: dict) -> list:
        departments = ctx.get("departments", [])
        normalized = []
        seen = set()


        for task in raw_tasks or []:
            if not isinstance(task, dict):
                continue


            name = str(task.get("name") or task.get("title") or "").strip()
            if len(name) < 5:
                continue


            key = re.sub(r"\s+", " ", name).lower()
            if key in seen:
                continue
            seen.add(key)


            description = str(task.get("description") or "").strip()
            if len(description) < 10:
                continue


            objective = str(task.get("objective") or "").strip()


            priority = str(task.get("priority") or "Medium").strip().title()
            if priority not in self.PRIORITY_MAP:
                priority = "Medium"


            try:
                duration = int(task.get("estimated_duration_days", 60))
            except (TypeError, ValueError):
                duration = 60
            duration = max(self.MIN_DURATION, min(duration, self.MAX_DURATION))


            deliverables = task.get("deliverables")
            if not isinstance(deliverables, list):
                deliverables = []
            deliverables = [str(d).strip() for d in deliverables if str(d).strip()][:5]


            dependencies = task.get("dependencies")
            if not isinstance(dependencies, list):
                dependencies = []
            dependencies = [str(d).strip() for d in dependencies if str(d).strip()]


            assigned = self._assign_departments(name, description, objective, departments)
            if not assigned:
                continue


            normalized.append({
                "name": name[:255],
                "description": description[:1000],
                "objective": objective[:500],
                "priority": priority,
                "estimated_duration_days": duration,
                "deliverables": deliverables,
                "dependencies": dependencies,
                "departments": assigned,
                "is_cross_department": len(assigned) > 1,
            })


        return normalized


    def _assign_departments(self, name, description, objective, departments):
        if not departments:
            return []


        task_text = f"{name} {description} {objective}".strip()


        lead = self._find_lead_heuristic(task_text, departments)
        if not lead:
            lead = departments[0]


        result = [{
            "department_id": lead["department_id"],
            "department_name": lead["name"],
            "responsibility_type": "LEAD",
            "notes": "الإدارة المسؤولة الرئيسية عن تنفيذ المهمة",
        }]


        supports = self._find_support_heuristic(task_text, lead, departments)
        for s in supports[:2]:
            result.append({
                "department_id": s["department_id"],
                "department_name": s["name"],
                "responsibility_type": "SUPPORT",
                "notes": "تقديم الدعم الفني والمساندة",
            })


        return result


    def _is_executive(self, dept: dict) -> bool:
        name = (dept.get("name", "") + " " + dept.get("code", "")).lower()
        exec_kw = ["وزير", "وكيل", "ديوان", "مكتب", "قيادة", "minister", "deputy", "executive", "diwan"]
        return any(kw in name for kw in exec_kw) or dept.get("level", 1) <= 1


    def _is_operational(self, text: str) -> bool:
        op_kw = ["تطوير", "تنفيذ", "إنشاء", "بناء", "تركيب", "تشغيل", "صيانة",
                 "تحديث", "ترقية", "اختبار", "تدريب", "تحليل", "تصميم",
                 "برمجة", "إعداد", "تنظيم", "تطبيق", "نظام", "شبكة",
                 "برنامج", "حاسب", "أتمتة", "رقمي"]
        return any(kw in text for kw in op_kw)


    def _find_lead_heuristic(self, task_text, departments):
        candidates = []
        for d in departments:
            if self._is_executive(d):
                continue
            score = 0
            name_l = (d.get("name", "") + " " + d.get("code", "")).lower()
            desc_l = d.get("description", "").lower()
            task_l = task_text.lower()


            for token in task_l.split():
                if len(token) < 3:
                    continue
                if token in name_l:
                    score += 3
                if token in desc_l:
                    score += 1


            if "مالي" in task_l or "ميزانية" in task_l:
                if "مال" in name_l or "ميزان" in name_l or "finance" in name_l:
                    score += 5
            if "تقني" in task_l or "نظام" in task_l:
                if "تقن" in name_l or "it" in name_l or "حاسب" in name_l:
                    score += 5
            if "تدريب" in task_l or "موارد" in task_l:
                if "تدريب" in name_l or "موارد" in name_l or "hr" in name_l:
                    score += 5


            candidates.append((score, d))


        candidates.sort(key=lambda x: x[0], reverse=True)
        if candidates and candidates[0][0] > 0:
            return candidates[0][1]


        for d in departments:
            if not self._is_executive(d) and d.get("level", 1) >= 2:
                return d
        return departments[0] if departments else None


    def _find_support_heuristic(self, task_text, lead, departments):
        support = []
        for d in departments:
            if d["department_id"] == lead["department_id"]:
                continue
            if self._is_executive(d):
                continue
            name_l = d.get("name", "").lower()
            desc_l = d.get("description", "").lower()
            task_l = task_text.lower()
            score = 0
            if "مالي" in task_l and ("مال" in name_l or "finance" in name_l):
                score += 4
            if "تقني" in task_l and ("تقن" in name_l or "it" in name_l):
                score += 4
            if "قانوني" in task_l and ("قانون" in name_l or "legal" in name_l):
                score += 4
            for token in task_l.split():
                if len(token) < 3:
                    continue
                if token in desc_l:
                    score += 1
            if score > 0:
                support.append((score, d))


        support.sort(key=lambda x: x[0], reverse=True)
        return [d for _, d in support[:2]]


    # ===========================================================
    # 5) Save (approval)
    # ===========================================================
    def save_major_tasks(self, initiative_id: int, tasks_data: list, created_by: int = None):
        initiative = self.db.query(Initiative).filter(
            Initiative.initiative_id == initiative_id,
            Initiative.is_active == True,
        ).first()
        if not initiative:
            raise ValueError("Initiative not found or inactive")


        valid_dept_ids = {
            d.department_id
            for d in self.db.query(Department).filter(Department.is_active == True).all()
        }


        ctx = self.get_initiative_context(initiative_id)
        normalized = self.normalize_tasks(tasks_data, ctx)
        if not normalized:
            raise ValueError("No valid major tasks to save")


        existing = self.db.query(MajorTask).filter(
            MajorTask.initiative_id == initiative_id,
            MajorTask.is_active == True,
        ).all()
        existing_names = {t.name.strip().lower() for t in existing}


        saved = []
        try:
            for task in normalized:
                if task["name"].lower() in existing_names:
                    continue


                dept_entries = []
                seen_ids = set()
                lead_count = 0
                for d in task["departments"]:
                    did = d.get("department_id")
                    if not did or did in seen_ids:
                        continue
                    if did not in valid_dept_ids:
                        continue
                    resp = str(d.get("responsibility_type", "SUPPORT")).upper()
                    if resp not in self.RESPONSIBILITY_TYPES:
                        resp = "SUPPORT"
                    if resp == "LEAD":
                        lead_count += 1
                    seen_ids.add(did)
                    dept_entries.append({
                        "department_id": did,
                        "responsibility_type": resp,
                        "notes": d.get("notes", ""),
                    })


                if lead_count != 1:
                    continue


                mt = MajorTask(
                    initiative_id=initiative_id,
                    name=task["name"],
                    description=task["description"],
                    priority_id=self.PRIORITY_MAP.get(task["priority"], 2),
                    estimated_duration_days=task["estimated_duration_days"],
                    is_cross_department=len(dept_entries) > 1,
                    created_by=created_by,
                    is_active=True,
                )
                self.db.add(mt)
                self.db.flush()


                for d in dept_entries:
                    self.db.add(MajorTaskDepartment(
                        major_task_id=mt.major_task_id,
                        department_id=d["department_id"],
                        responsibility_type=d["responsibility_type"],
                        notes=d["notes"],
                    ))


                saved.append({
                    "id": mt.major_task_id,
                    "name": mt.name,
                    "is_cross_department": mt.is_cross_department,
                })


            self.db.commit()
            return saved
        except Exception:
            self.db.rollback()
            raise


    # ===========================================================
    # 6) Job helpers
    # ===========================================================
    def create_job(self, initiative_id: int, created_by: int = None) -> int:
        job = AiJob(
            job_type="strategic_task_generation",
            status="pending",
            input_data=json.dumps({"initiative_id": initiative_id}, ensure_ascii=False),
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

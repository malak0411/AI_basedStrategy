import json
import re
from datetime import datetime
from app.database import SessionLocal
from app.models import (
    Initiative, Program, StrategicGoal, StrategicPillar, StrategicVision,
    SWOTAnalysis, PESTELAnalysis, Department, BudgetLine, Risk,
    GoalKPI, KPI, SystemConfig, AiJob, MajorTask, MajorTaskDepartment
)
from app.ai.services.ollama_client import ollama_client

class StrategicPlanner:
    def __init__(self):
        self.db = SessionLocal()

    def get_initiative_context(self, initiative_id: int) -> dict:
        initiative = self.db.query(Initiative).filter(Initiative.initiative_id == initiative_id).first()
        if not initiative:
            raise ValueError("Initiative not found")

        program = self.db.query(Program).filter(Program.program_id == initiative.program_id).first()
        goal = self.db.query(StrategicGoal).filter(StrategicGoal.goal_id == program.goal_id if program else None).first()
        pillar = self.db.query(StrategicPillar).filter(StrategicPillar.pillar_id == goal.pillar_id if goal else None).first()
        vision = self.db.query(StrategicVision).filter(StrategicVision.is_current == True).first()
        swot = self.db.query(SWOTAnalysis).first()
        pestel = self.db.query(PESTELAnalysis).first()

        departments = self.db.query(Department).filter(Department.is_active == True).all()
        dept_list = []
        for d in departments:
            dept_list.append({
                "department_id": d.department_id,
                "name": d.name,
                "code": d.code or "",
                "description": d.description or "",
                "level": d.level or 1
            })

        budgets = self.db.query(BudgetLine).filter(
            BudgetLine.budgetable_id == initiative_id,
            BudgetLine.budgetable_type == 'initiative'
        ).all()
        total_budget = sum(float(b.allocated_amount or 0) for b in budgets)

        risks = self.db.query(Risk).limit(5).all()
        risk_list = [{"name": r.name, "level": r.risk_level_id} for r in risks]

        kpis = []
        if goal:
            goal_kpis = self.db.query(GoalKPI).filter(GoalKPI.goal_id == goal.goal_id).all()
            for gk in goal_kpis:
                kpi = self.db.query(KPI).filter(KPI.kpi_id == gk.kpi_id).first()
                if kpi:
                    kpis.append({"name": kpi.name, "target": float(gk.target_value or 0)})

        configs = self.db.query(SystemConfig).all()
        org_info = {c.config_key: c.config_value for c in configs}

        return {
            "initiative": {
                "id": initiative.initiative_id,
                "name": initiative.name,
                "description": initiative.description or "",
                "start_date": initiative.start_date.isoformat() if initiative.start_date else None,
                "end_date": initiative.end_date.isoformat() if initiative.end_date else None,
                "budget": float(initiative.budget_estimate or 0)
            },
            "program": program.name if program else "",
            "goal": goal.title if goal else "",
            "pillar": pillar.name if pillar else "",
            "vision": vision.text[:200] if vision else "",
            "departments": dept_list,
            "total_budget": total_budget,
            "risks": risk_list,
            "kpis": kpis,
            "swot": self._format_swot(swot),
            "pestel": self._format_pestel(pestel),
            "organization": org_info
        }

    def _format_swot(self, swot):
        if not swot: return ""
        parts = []
        if swot.strengths: parts.append(f"S:{swot.strengths[:100]}")
        if swot.weaknesses: parts.append(f"W:{swot.weaknesses[:100]}")
        if swot.opportunities: parts.append(f"O:{swot.opportunities[:100]}")
        if swot.threats: parts.append(f"T:{swot.threats[:100]}")
        return "|".join(parts)

    def _format_pestel(self, pestel):
        if not pestel: return ""
        parts = []
        if pestel.political: parts.append(f"P:{pestel.political[:60]}")
        if pestel.economic: parts.append(f"E:{pestel.economic[:60]}")
        if pestel.technological: parts.append(f"T:{pestel.technological[:60]}")
        return "|".join(parts)

    def _validate_department_assignment(self, dept_id: int, resp_type: str, task_text: str, departments: list) -> bool:
        dept = next((d for d in departments if d["department_id"] == dept_id), None)
        if not dept:
            return False

        name = (dept.get("name", "") + " " + dept.get("code", "")).lower()
        desc = dept.get("description", "").lower()
        task_lower = task_text.lower()

        exec_keywords = ["وزير", "وكيل", "ديوان", "مكتب", "قيادة", "minister", "deputy", "executive", "diwan"]
        is_executive = any(kw in name for kw in exec_keywords) or dept.get("level", 1) <= 1

        operational_keywords = ["تطوير", "تنفيذ", "إنشاء", "بناء", "تركيب", "تشغيل", "صيانة", "تحديث", "ترقية", "اختبار", "تدريب", "تحليل", "تصميم", "برمجة", "إعداد", "تنظيم", "تطبيق", "نظام", "شبكة", "برنامج", "حاسب", "أتمتة", "رقمي"]
        is_operational = any(kw in task_lower for kw in operational_keywords)

        if is_executive and resp_type == "LEAD" and is_operational:
            return False

        if resp_type == "LEAD" and desc:
            task_words = set(task_lower.split())
            desc_words = set(desc.split())
            if len(task_words & desc_words) < 2:
                return False

        return True

    def _is_executive_dept(self, dept: dict) -> bool:
        name = (dept.get("name", "") + " " + dept.get("code", "")).lower()
        exec_keywords = ["وزير", "وكيل", "ديوان", "مكتب", "قيادة", "minister", "deputy", "executive", "diwan"]
        return any(kw in name for kw in exec_keywords) or dept.get("level", 1) <= 1

    def _is_operational_task(self, task_text: str) -> bool:
        operational_keywords = ["تطوير", "تنفيذ", "إنشاء", "بناء", "تركيب", "تشغيل", "صيانة", "تحديث", "ترقية", "اختبار", "تدريب", "تحليل", "تصميم", "برمجة", "إعداد", "تنظيم", "تطبيق", "نظام", "شبكة", "برنامج", "حاسب", "أتمتة", "رقمي"]
        return any(kw in task_text for kw in operational_keywords)

    def _find_best_lead(self, task_text: str, departments: list) -> dict:
        dept_descriptions = json.dumps([{
            "id": d["department_id"],
            "name": d["name"],
            "code": d["code"],
            "description": d["description"],
            "level": d["level"]
        } for d in departments], ensure_ascii=False)

        prompt = f"""Task: {task_text}

Departments with their responsibilities:
{dept_descriptions}

Analyze each department's description and select the ONE most suitable department to be LEAD for this task.

Rules:
- Choose based on the department's actual responsibilities and description, not just its name.
- Executive offices (Minister Office, Deputy Office, Diwan) should ONLY be LEAD for policy approval, strategic decisions, or high-level coordination tasks.
- For operational tasks (development, implementation, training, installation, maintenance), choose the specialized operational department.
- For technical tasks, choose the IT/technical department.
- For financial tasks, choose the finance department.
- For HR/training tasks, choose the HR/training department.

Return ONLY a JSON object with the selected department_id and a brief reason in Arabic:
{{"department_id": 15, "reason": "تم اختيار الإدارة لأنها مسؤولة عن..."}}"""

        try:
            response = ollama_client.generate(prompt, system_instruction="Return ONLY valid JSON. No other text.")
            data = json.loads(response.strip())
            dept_id = data.get("department_id")
            if dept_id:
                dept = next((d for d in departments if d["department_id"] == dept_id), None)
                if dept:
                    is_operational = self._is_operational_task(task_text)
                    if self._is_executive_dept(dept) and is_operational:
                        for d in departments:
                            if not self._is_executive_dept(d) and d.get("level", 1) >= 2:
                                return d
                    return dept
        except:
            pass

        for d in departments:
            if not self._is_executive_dept(d) and d.get("level", 1) >= 2:
                return d
        return departments[0] if departments else None

    def _find_support_depts(self, lead_dept: dict, task_text: str, departments: list) -> list:
        dept_descriptions = json.dumps([{
            "id": d["department_id"],
            "name": d["name"],
            "code": d["code"],
            "description": d["description"]
        } for d in departments if d["department_id"] != lead_dept["department_id"] and not self._is_executive_dept(d)], ensure_ascii=False)

        prompt = f"""Task: {task_text}
LEAD Department: {lead_dept['name']} ({lead_dept.get('description', '')})

Other departments:
{dept_descriptions}

Select 1-2 departments that should SUPPORT this task. Choose only if their responsibilities are truly relevant.

Return ONLY a JSON array:
[{{"department_id": 3, "reason": "سبب الاختيار بالعربية"}}]"""

        try:
            response = ollama_client.generate(prompt, system_instruction="Return ONLY valid JSON array. No other text.")
            data = json.loads(response.strip())
            support = []
            seen = set()
            for item in data[:2]:
                dept_id = item.get("department_id")
                if dept_id and dept_id not in seen:
                    dept = next((d for d in departments if d["department_id"] == dept_id), None)
                    if dept and not self._is_executive_dept(dept):
                        seen.add(dept_id)
                        support.append({
                            "department_id": dept["department_id"],
                            "department_name": dept["name"],
                            "responsibility_type": "SUPPORT",
                            "notes": item.get("reason", "تقديم الدعم والمساندة")
                        })
            return support
        except:
            return []

    def _generate_dept_notes(self, dept: dict, task_name: str, task_desc: str, resp_type: str) -> str:
        try:
            prompt = f"""Task: {task_name}
Description: {task_desc}
Department: {dept['name']} - {dept.get('description', '')}
Role: {resp_type}

Write ONE short Arabic sentence explaining the specific role of this department in this task."""
            notes = ollama_client.generate(prompt, system_instruction="Reply with one Arabic sentence only.")
            return notes.strip()
        except:
            return "مسؤولة عن التنفيذ والمتابعة" if resp_type == "LEAD" else "تقديم الدعم الفني والمساندة"

    def build_compact_prompt(self, context: dict, user_instructions: str = "") -> str:
        dept_compact = [{"id": d["department_id"], "name": d["name"], "desc": (d["description"] or "")[:80]} for d in context["departments"]]
        prompt = json.dumps({
            "task": "Generate 3-5 major tasks for this government initiative. Return ONLY JSON array.",
            "initiative": context["initiative"],
            "goal": context["goal"],
            "program": context["program"],
            "vision": context["vision"][:150],
            "budget": context["total_budget"],
            "risks": [r["name"] for r in context["risks"]],
            "kpis": [k["name"] for k in context["kpis"]],
            "swot": context["swot"],
            "pestel": context["pestel"],
            "departments": dept_compact,
            "instructions": user_instructions or "",
            "output_format": [
                {
                    "name": "Task name in Arabic",
                    "description": "Description in Arabic",
                    "objective": "What this task achieves",
                    "priority": "High|Medium|Low",
                    "estimated_duration_days": 60,
                    "deliverables": ["Specific deliverable"],
                    "dependencies": []
                }
            ]
        }, ensure_ascii=False)
        return prompt

    def parse_response(self, response_text: str) -> list:
        text = response_text.strip()
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
            if isinstance(data, dict) and "major_tasks" in data:
                return data["major_tasks"]
        except json.JSONDecodeError:
            pass
        match = re.search(r'\[.*\]', text, re.DOTALL)
        if match:
            try:
                return json.loads(match.group())
            except json.JSONDecodeError:
                pass
        tasks = []
        for match in re.finditer(r'\{[^}]+\}', text):
            try:
                task = json.loads(match.group())
                if 'name' in task:
                    tasks.append(task)
            except json.JSONDecodeError:
                continue
        return tasks

    def post_process_tasks(self, raw_tasks: list, context: dict) -> list:
        departments = context["departments"]
        processed = []
        for task in raw_tasks:
            task_text = task.get("name", "") + " " + task.get("description", "") + " " + task.get("objective", "")
            lead_dept = self._find_best_lead(task_text, departments)
            dept_list = []
            if lead_dept:
                notes = self._generate_dept_notes(lead_dept, task.get("name", ""), task.get("description", ""), "LEAD")
                dept_list.append({
                    "department_id": lead_dept["department_id"],
                    "department_name": lead_dept["name"],
                    "responsibility_type": "LEAD",
                    "notes": notes
                })
                if self._validate_department_assignment(lead_dept["department_id"], "LEAD", task_text, departments):
                    support_depts = self._find_support_depts(lead_dept, task_text, departments)
                    dept_list.extend(support_depts)
            priority = task.get("priority", "Medium")
            if priority not in ("High", "Medium", "Low"):
                priority = "Medium"
            deliverables = [d for d in task.get("deliverables", []) if d != "D1"]
            processed.append({
                "name": task.get("name", ""),
                "description": task.get("description", ""),
                "objective": task.get("objective", ""),
                "priority": priority,
                "estimated_duration_days": max(10, min(task.get("estimated_duration_days", 30), 180)),
                "is_cross_department": len(dept_list) > 1,
                "departments": dept_list,
                "deliverables": deliverables,
                "dependencies": task.get("dependencies", [])
            })
        return processed

    def save_major_tasks(self, initiative_id: int, tasks_data: list, created_by: int = None):
        saved = []
        priority_map = {"High": 3, "Medium": 2, "Low": 1}
        for task in tasks_data:
            priority_id = priority_map.get(task.get("priority", "Medium"), 2)
            mt = MajorTask(
                initiative_id=initiative_id,
                name=task["name"],
                description=task.get("description", ""),
                priority_id=priority_id,
                estimated_duration_days=task.get("estimated_duration_days", 30),
                is_cross_department=task.get("is_cross_department", False),
                created_by=created_by
            )
            self.db.add(mt)
            self.db.flush()
            for dept in task.get("departments", []):
                mtd = MajorTaskDepartment(
                    major_task_id=mt.major_task_id,
                    department_id=dept["department_id"],
                    responsibility_type=dept.get("responsibility_type", "SUPPORT"),
                    notes=dept.get("notes", "")
                )
                self.db.add(mtd)
            saved.append({"id": mt.major_task_id, "name": mt.name})
        self.db.commit()
        return saved

    def create_job(self, initiative_id: int, created_by: int = None) -> int:
        job = AiJob(
            job_type="strategic_task_generation",
            status="pending",
            input_data=json.dumps({"initiative_id": initiative_id}),
            created_by=created_by
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

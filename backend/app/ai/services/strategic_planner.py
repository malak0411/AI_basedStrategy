import json, re
from datetime import datetime
from app.database import SessionLocal
from app.models import (
    Initiative, Program, StrategicGoal, StrategicPillar, StrategicVision,
    SWOTAnalysis, PESTELAnalysis, Department, BudgetLine, Risk,
    GoalKPI, KPI, SystemConfig, AiJob, MajorTask, MajorTaskDepartment,
    DictPriority
)
from app.ai.services.ollama_client import ollama_client

class StrategicPlanner:
    def __init__(self):
        self.db = SessionLocal()

    def get_initiative_context(self, initiative_id: int) -> dict:
        initiative = self.db.query(Initiative).filter(Initiative.initiative_id == initiative_id).first()
        if not initiative:
            raise ValueError("المبادرة غير موجودة")

        program = self.db.query(Program).filter(Program.program_id == initiative.program_id).first()
        goal = self.db.query(StrategicGoal).filter(StrategicGoal.goal_id == program.goal_id if program else None).first()
        pillar = self.db.query(StrategicPillar).filter(StrategicPillar.pillar_id == goal.pillar_id if goal else None).first()
        vision = self.db.query(StrategicVision).filter(StrategicVision.is_current == True).first()
        swot = self.db.query(SWOTAnalysis).first()
        pestel = self.db.query(PESTELAnalysis).first()

        departments = self.db.query(Department).filter(Department.is_active == True).all()
        dept_list = [{"id": d.department_id, "name": d.name, "code": d.code or "", "description": d.description or ""} for d in departments]

        budgets = self.db.query(BudgetLine).filter(BudgetLine.budgetable_id == initiative_id, BudgetLine.budgetable_type == 'initiative').all()
        total_budget = sum(float(b.allocated_amount or 0) for b in budgets)

        risks = self.db.query(Risk).limit(5).all()
        risk_names = [r.name for r in risks] if risks else ["لا توجد مخاطر مسجلة"]

        kpi_names = []
        if goal:
            goal_kpis = self.db.query(GoalKPI).filter(GoalKPI.goal_id == goal.goal_id).all()
            for gk in goal_kpis:
                kpi = self.db.query(KPI).filter(KPI.kpi_id == gk.kpi_id).first()
                if kpi:
                    kpi_names.append(f"{kpi.name} (المستهدف: {float(gk.target_value or 0)})")

        configs = self.db.query(SystemConfig).all()
        org_info = {c.config_key: c.config_value for c in configs}

        swot_text = ""
        if swot:
            parts = []
            if swot.strengths: parts.append(f"نقاط القوة: {swot.strengths[:200]}")
            if swot.weaknesses: parts.append(f"نقاط الضعف: {swot.weaknesses[:200]}")
            if swot.opportunities: parts.append(f"الفرص: {swot.opportunities[:200]}")
            if swot.threats: parts.append(f"التهديدات: {swot.threats[:200]}")
            swot_text = " | ".join(parts)

        pestel_text = ""
        if pestel:
            parts = []
            if pestel.political: parts.append(f"سياسي: {pestel.political[:100]}")
            if pestel.economic: parts.append(f"اقتصادي: {pestel.economic[:100]}")
            if pestel.social: parts.append(f"اجتماعي: {pestel.social[:100]}")
            if pestel.technological: parts.append(f"تقني: {pestel.technological[:100]}")
            pestel_text = " | ".join(parts)

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
            "vision": vision.text[:300] if vision else "",
            "departments": dept_list,
            "dept_names": [d["name"] for d in dept_list],
            "dept_details": [f"{d['name']} ({d['description'][:80]})" if d['description'] else d['name'] for d in dept_list],
            "total_budget": total_budget,
            "risks": risk_names,
            "kpis": kpi_names if kpi_names else ["لا توجد مؤشرات"],
            "swot_text": swot_text,
            "pestel_text": pestel_text,
            "organization": org_info
        }

    def build_compact_prompt(self, context: dict, user_instructions: str = "") -> str:
        dept_str = "\n".join([f"- {d}" for d in context["dept_details"]])
        risk_str = "\n".join([f"- {r}" for r in context["risks"]])
        kpi_str = "\n".join([f"- {k}" for k in context["kpis"]])

        prompt = f"""أنت مخطط استراتيجي حكومي خبير. حول المبادرة التالية إلى مهام رئيسية.

=== المبادرة ===
الاسم: {context['initiative']['name']}
الوصف: {context['initiative']['description']}
المدة: {context['initiative']['start_date']} إلى {context['initiative']['end_date']}
الميزانية: {context['initiative']['budget']:,} {context['organization'].get('currency', 'YER')}

=== التسلسل الهرمي ===
الرؤية: {context['vision']}
الركيزة: {context['pillar']}
الهدف: {context['goal']}
البرنامج: {context['program']}

=== الإدارات المتاحة (استخدم نفس الأسماء بالضبط) ===
{dept_str}

=== المخاطر ===
{risk_str}

=== مؤشرات الأداء ===
{kpi_str}

=== التحليل الاستراتيجي ===
{context['swot_text']}
{context['pestel_text']}

{f"=== تعليمات إضافية ===\n{user_instructions}" if user_instructions else ""}

=== قواعد صارمة ===
1. أنشئ 3-5 مهام رئيسية تغطي دورة حياة المبادرة كاملة.
2. استخدم أسماء الإدارات الموجودة في القائمة أعلاه بالضبط.
3. حدد "responsibility_type" لكل إدارة: "LEAD" للمسؤولة رئيسياً، "SUPPORT" للمساندة.
4. أضف "notes" عربية قصيرة تصف دور كل إدارة (مثلاً: "تقديم الدعم الفني والتقني").
5. "is_cross_department": true فقط إذا كانت المهمة تضم إدارتين أو أكثر.
6. المهمة التي لها إدارة واحدة فقط: "is_cross_department": false.
7. "deliverables" تكون مخرجات محددة وقابلة للقياس بالعربية.
8. "estimated_duration_days" بين 15 و 120 يوم.
9. جميع النصوص باللغة العربية.

أعد JSON فقط:
[
  {{
    "name": "اسم المهمة بالعربية",
    "description": "وصف تفصيلي بالعربية",
    "priority": "High",
    "estimated_duration_days": 60,
    "is_cross_department": false,
    "departments": [
      {{ "name": "اسم الإدارة", "responsibility_type": "LEAD", "notes": "وصف دور الإدارة بالعربية" }}
    ],
    "deliverables": ["مخرج 1", "مخرج 2"],
    "dependencies": []
  }}
]"""
        return prompt

    def post_process_tasks(self, raw_tasks: list, context: dict) -> list:
        dept_map = {d["name"]: d for d in context["departments"]}
        processed = []

        for task in raw_tasks:
            priority = task.get("priority", "Medium")
            if priority not in ["High", "Medium", "Low"]:
                priority = "Medium"

            dept_list = []
            for d in task.get("departments", []):
                dept_name = d.get("name", "").strip()
                dept_info = dept_map.get(dept_name)
                if dept_info:
                    dept_list.append({
                        "department_id": dept_info["id"],
                        "department_name": dept_name,
                        "responsibility_type": d.get("responsibility_type", "SUPPORT"),
                        "notes": d.get("notes", "")
                    })

            is_cross = len(dept_list) > 1

            if not dept_list and context["departments"]:
                first_dept = context["departments"][0]
                dept_list.append({
                    "department_id": first_dept["id"],
                    "department_name": first_dept["name"],
                    "responsibility_type": "LEAD",
                    "notes": "الإدارة المسؤولة عن التنفيذ"
                })

            processed.append({
                "name": task.get("name", ""),
                "description": task.get("description", ""),
                "priority": priority,
                "estimated_duration_days": max(10, min(task.get("estimated_duration_days", 30), 180)),
                "is_cross_department": is_cross,
                "departments": dept_list,
                "deliverables": task.get("deliverables", []),
                "dependencies": task.get("dependencies", [])
            })

        return processed

    def parse_response(self, response_text: str) -> list:
        text = response_text.strip()
    
    # إزالة علامات markdown
        if text.startswith("```json"):
            text = text[7:]
        elif text.startswith("```"):
            text = text[3:]
        if text.endswith("```"):
            text = text[:-3]
        text = text.strip()
    
    # محاولة 1: تحليل JSON مباشرة
        try:
            data = json.loads(text)
            if isinstance(data, list):
                return data
            elif isinstance(data, dict) and "major_tasks" in data:
                return data["major_tasks"]
        except json.JSONDecodeError:
            pass
    
    # محاولة 2: استخراج مصفوفة JSON
        match = re.search(r'\[.*\]', text, re.DOTALL)
        if match:
            try:
                return json.loads(match.group())
            except json.JSONDecodeError:
                pass
    
    # محاولة 3: إصلاح JSON غير المكتمل
    # البحث عن آخر } كاملة وإغلاق المصفوفة
        text = text.strip()
        if text.startswith('['):
            last_complete = text.rfind('"}')
            if last_complete > 0:
                text = text[:last_complete + 2] + '\n]'
                try:
                    return json.loads(text)
                except json.JSONDecodeError:
                    pass
    
    # محاولة 4: استخراج المهام الفردية
        tasks = []
        for match in re.finditer(r'\{[^}]+\}', text):
            try:
                task = json.loads(match.group())
                if 'name' in task:
                    tasks.append(task)
            except json.JSONDecodeError:
                continue
    
        if tasks:
            return tasks
    
        print(f"⚠️ فشل تحليل JSON. النص المستلم: {text[:500]}")
        return []


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
        job = AiJob(job_type="strategic_task_generation", status="pending",
                    input_data=json.dumps({"initiative_id": initiative_id}), created_by=created_by)
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

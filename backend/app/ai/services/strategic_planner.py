"""
Strategic Planning Service - تحويل المبادرات إلى مهام رئيسية باستخدام Gemini
"""
import json
import re
from datetime import datetime
from app.database import SessionLocal
from app.models import (
    Initiative, Program, StrategicGoal, StrategicPillar, StrategicVision,
    SWOTAnalysis, PESTELAnalysis, Department, BudgetLine, Risk,
    GoalKPI, KPI, SystemConfig, AiJob, MajorTask, MajorTaskDepartment
)
from app.ai.services.gemini_client import gemini_client


class StrategicPlanner:
    """مخطط استراتيجي يستخدم Gemini لتوليد المهام الرئيسية"""

    def __init__(self):
        self.db = SessionLocal()

    def get_initiative_context(self, initiative_id: int) -> dict:
        """جمع كل البيانات المرتبطة بمبادرة"""
        initiative = self.db.query(Initiative).filter(
            Initiative.initiative_id == initiative_id
        ).first()

        if not initiative:
            raise ValueError("المبادرة غير موجودة")

        # البرنامج
        program = self.db.query(Program).filter(
            Program.program_id == initiative.program_id
        ).first()

        # الهدف
        goal = self.db.query(StrategicGoal).filter(
            StrategicGoal.goal_id == program.goal_id if program else None
        ).first()

        # الركيزة
        pillar = self.db.query(StrategicPillar).filter(
            StrategicPillar.pillar_id == goal.pillar_id if goal else None
        ).first()

        # الرؤية
        vision = self.db.query(StrategicVision).filter(
            StrategicVision.is_current == True
        ).first()

        # SWOT
        swot = self.db.query(SWOTAnalysis).first()

        # PESTEL
        pestel = self.db.query(PESTELAnalysis).first()

        # الإدارات
        departments = self.db.query(Department).filter(Department.is_active == True).all()
        dept_list = [{
            "id": d.department_id,
            "name": d.name,
            "code": d.code or "",
            "responsibilities": d.description or ""
        } for d in departments]

        # الميزانية
        budgets = self.db.query(BudgetLine).filter(
            BudgetLine.budgetable_id == initiative_id,
            BudgetLine.budgetable_type == 'initiative'
        ).all()
        total_budget = sum(float(b.allocated_amount or 0) for b in budgets)

        # المخاطر
        risks = self.db.query(Risk).limit(10).all()
        risk_list = [{"name": r.name, "level": r.risk_level_id} for r in risks]

        # KPIs
        kpis = []
        if goal:
            goal_kpis = self.db.query(GoalKPI).filter(GoalKPI.goal_id == goal.goal_id).all()
            for gk in goal_kpis:
                kpi = self.db.query(KPI).filter(KPI.kpi_id == gk.kpi_id).first()
                if kpi:
                    kpis.append({"name": kpi.name, "target": float(gk.target_value or 0)})

        # إعدادات النظام
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
            "program": {
                "name": program.name if program else "",
                "description": program.description if program else ""
            } if program else None,
            "goal": {
                "title": goal.title if goal else "",
                "description": goal.description if goal else ""
            } if goal else None,
            "pillar": {
                "name": pillar.name if pillar else "",
                "description": pillar.description if pillar else ""
            } if pillar else None,
            "vision": vision.text if vision else "",
            "swot": {
                "strengths": swot.strengths if swot else "",
                "weaknesses": swot.weaknesses if swot else "",
                "opportunities": swot.opportunities if swot else "",
                "threats": swot.threats if swot else ""
            } if swot else None,
            "pestel": {
                "political": pestel.political if pestel else "",
                "economic": pestel.economic if pestel else "",
                "social": pestel.social if pestel else "",
                "technological": pestel.technological if pestel else "",
                "environmental": pestel.environmental if pestel else "",
                "legal": pestel.legal if pestel else ""
            } if pestel else None,
            "departments": dept_list,
            "total_budget": total_budget,
            "risks": risk_list,
            "kpis": kpis,
            "organization": org_info
        }

    def build_prompt(self, context: dict, user_instructions: str = "") -> str:
        """بناء Prompt احترافي لـ Gemini"""
        prompt = f"""
أنت خبير في التخطيط الاستراتيجي الحكومي. مهمتك تحليل مبادرة استراتيجية وتحويلها إلى مهام رئيسية قابلة للتنفيذ.

=== بيانات المؤسسة ===
المؤسسة: {context['organization'].get('ministry_name', 'وزارة النفط والمعادن')}
نوع القطاع: {context['organization'].get('sector_type', 'حكومي')}
العملة: {context['organization'].get('currency', 'YER')}

=== الرؤية ===
{context['vision']}

=== الركيزة الاستراتيجية ===
{context['pillar']['name'] if context['pillar'] else ''}
{context['pillar']['description'] if context['pillar'] else ''}

=== الهدف الاستراتيجي ===
{context['goal']['title'] if context['goal'] else ''}
{context['goal']['description'] if context['goal'] else ''}

=== البرنامج ===
{context['program']['name'] if context['program'] else ''}

=== المبادرة ===
الاسم: {context['initiative']['name']}
الوصف: {context['initiative']['description']}
المدة: من {context['initiative']['start_date']} إلى {context['initiative']['end_date']}
الميزانية: {context['initiative']['budget']}

=== الإدارات المتاحة ===
{json.dumps(context['departments'], ensure_ascii=False, indent=2)}

=== الميزانية الإجمالية ===
{context['total_budget']}

=== المخاطر ===
{json.dumps(context['risks'], ensure_ascii=False, indent=2)}

=== مؤشرات الأداء ===
{json.dumps(context['kpis'], ensure_ascii=False, indent=2)}

=== SWOT ===
{json.dumps(context['swot'], ensure_ascii=False, indent=2) if context['swot'] else ''}

=== PESTEL ===
{json.dumps(context['pestel'], ensure_ascii=False, indent=2) if context['pestel'] else ''}

{f'=== تعليمات إضافية ===\n{user_instructions}' if user_instructions else ''}

=== المطلوب ===
1. إنشاء المهام الرئيسية (Major Tasks) لتنفيذ هذه المبادرة.
2. كل مهمة يجب أن تكون مرتبطة مباشرة بهدف المبادرة.
3. تحديد الإدارة المسؤولة (LEAD) والإدارات المساندة (SUPPORT).
4. تحديد هل المهمة مشتركة بين إدارات.
5. تحديد مدة تنفيذ معقولة لكل مهمة.
6. تحديد الأولوية (High/Medium/Low).
7. تحديد المخرجات المتوقعة.
8. تحديد الاعتماديات بين المهام.

=== شكل الإخراج ===
أعد JSON فقط بدون أي نص آخر:

{{
  "major_tasks": [
    {{
      "name": "اسم المهمة",
      "description": "وصف تفصيلي",
      "priority": "High",
      "estimated_duration_days": 60,
      "is_cross_department": false,
      "departments": [
        {{
          "department_id": 1,
          "responsibility_type": "LEAD",
          "notes": "الإدارة الرئيسية"
        }}
      ],
      "deliverables": ["مخرج 1", "مخرج 2"],
      "dependencies": []
    }}
  ]
}}
"""
        return prompt

    def parse_gemini_response(self, response_text: str) -> dict:
        """تحليل استجابة Gemini واستخراج JSON"""
        text = response_text.strip()
        
        # إزالة علامات markdown
        if text.startswith("```json"):
            text = text[7:]
        if text.startswith("```"):
            text = text[3:]
        if text.endswith("```"):
            text = text[:-3]
        text = text.strip()

        # محاولة تحليل JSON مباشرة
        try:
            return json.loads(text)
        except json.JSONDecodeError:
            pass

        # محاولة استخراج JSON من النص
        match = re.search(r'\{.*\}', text, re.DOTALL)
        if match:
            try:
                return json.loads(match.group())
            except json.JSONDecodeError:
                pass

        raise ValueError("فشل تحليل استجابة Gemini - لم يتم العثور على JSON صالح")

    def save_major_tasks(self, initiative_id: int, tasks_data: list, created_by: int = None):
        """حفظ المهام الرئيسية في قاعدة البيانات"""
        saved_tasks = []

        for task_data in tasks_data:
            priority_map = {"High": 3, "Medium": 2, "Low": 1}
            priority_id = priority_map.get(task_data.get("priority", "Medium"), 2)

            major_task = MajorTask(
                initiative_id=initiative_id,
                name=task_data["name"],
                description=task_data.get("description", ""),
                priority_id=priority_id,
                estimated_duration_days=task_data.get("estimated_duration_days", 30),
                is_cross_department=task_data.get("is_cross_department", False),
                created_by=created_by
            )
            self.db.add(major_task)
            self.db.flush()

            for dept in task_data.get("departments", []):
                mtd = MajorTaskDepartment(
                    major_task_id=major_task.major_task_id,
                    department_id=dept["department_id"],
                    responsibility_type=dept.get("responsibility_type", "SUPPORT"),
                    notes=dept.get("notes", "")
                )
                self.db.add(mtd)

            saved_tasks.append({
                "id": major_task.major_task_id,
                "name": major_task.name
            })

        self.db.commit()
        return saved_tasks

    def create_job(self, initiative_id: int, created_by: int = None) -> int:
        """إنشاء سجل ai_jobs"""
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
        """تحديث حالة ai_jobs"""
        job = self.db.query(AiJob).filter(AiJob.job_id == job_id).first()
        if job:
            job.status = status
            if result:
                job.result_json = json.dumps(result, ensure_ascii=False)
            job.updated_at = datetime.now()
            self.db.commit()

    def close(self):
        """إغلاق اتصال قاعدة البيانات"""
        if self.db:
            self.db.close()

from datetime import datetime, date, timedelta
from typing import Optional, List


from fastapi import APIRouter, Depends, HTTPException, Query
from sqlalchemy import func, and_, or_, desc
from sqlalchemy.orm import Session, joinedload


from app.database import get_db
from app.core.dependencies import get_current_user
from app.models import (
    Employee, Department, OperationalTask, MajorTask, Initiative,
    TaskAssignment, TaskProgressLog, Risk, RiskMitigation,
    AIPrediction, StrategicPillar, StrategicGoal, Program,
    KPI, KPIMeasurement, DictStatus, DictPriority, DictRiskLevel,DictRoleType
)
from calendar import monthrange




router = APIRouter(prefix="/api/dashboard", tags=["Dashboard"])


# ─── Helpers ──────────────────────────────────────────────────
def _latest_progress_map(db: Session, task_ids: List[int]) -> dict:
    """يعيد {task_id: latest_progress_percent}"""
    if not task_ids:
        return {}
    sub = (
        db.query(
            TaskProgressLog.task_id,
            func.max(TaskProgressLog.log_time).label("last_time")
        )
        .filter(TaskProgressLog.task_id.in_(task_ids))
        .group_by(TaskProgressLog.task_id)
        .subquery()
    )
    rows = (
        db.query(TaskProgressLog.task_id, TaskProgressLog.progress_percent)
        .join(sub, and_(
            TaskProgressLog.task_id == sub.c.task_id,
            TaskProgressLog.log_time == sub.c.last_time
        ))
        .all()
    )
    return {r.task_id: float(r.progress_percent or 0) for r in rows}




def _risk_level_by_score(score: float) -> str:
    if score >= 80: return "critical"
    if score >= 60: return "high"
    if score >= 40: return "medium"
    if score >= 20: return "low"
    return "very_low"




def _scope_employee_tasks(db: Session, employee_id: int):
    """مهام الموظف = tasks المُسندة إليه عبر task_assignments.is_active=1"""
    return (
        db.query(OperationalTask)
        .join(TaskAssignment, TaskAssignment.task_id == OperationalTask.task_id)
        .filter(
            TaskAssignment.employee_id == employee_id,
            TaskAssignment.is_active == True,
            OperationalTask.is_active == True,
        )
    )








def _latest_progress_map(db, task_ids):
    if not task_ids:
        return {}
    sub = (
        db.query(
            TaskProgressLog.task_id,
            func.max(TaskProgressLog.log_time).label("last_time"),
        )
        .filter(TaskProgressLog.task_id.in_(task_ids))
        .group_by(TaskProgressLog.task_id)
        .subquery()
    )
    rows = (
        db.query(TaskProgressLog.task_id, TaskProgressLog.progress_percent)
        .join(sub, and_(
            TaskProgressLog.task_id == sub.c.task_id,
            TaskProgressLog.log_time == sub.c.last_time,
        ))
        .all()
    )
    return {r.task_id: float(r.progress_percent or 0) for r in rows}




COMPLETED_STATUS_IDS = {19, 3}   # acc, completed




@router.get("/employee")
def employee_dashboard(db: Session = Depends(get_db), user=Depends(get_current_user)):
    emp_id = user
    emp = (
        db.query(Employee)
        .options(joinedload(Employee.department))
        .filter(Employee.employee_id == emp_id)
        .first()
    )
    if not emp:
        raise HTTPException(404, "Employee not found")


    today = date.today()


    statuses = {s.status_id: s for s in db.query(DictStatus).all()}
    roles_d  = {r.role_type_id: r for r in db.query(DictRoleType).all()}


    assignments = (
        db.query(TaskAssignment, OperationalTask)
        .join(OperationalTask, OperationalTask.task_id == TaskAssignment.task_id)
        .filter(
            TaskAssignment.employee_id == emp_id,
            TaskAssignment.is_active == True,
            OperationalTask.is_active == True,
        )
        .all()
    )
    accepted = [(a, t) for a, t in assignments if a.acceptance_status == "accepted"]
    pending  = [(a, t) for a, t in assignments if a.acceptance_status == "pending"]


    task_ids     = [t.task_id for _, t in accepted]
    progress_map = _latest_progress_map(db, task_ids)


    # ─── KPI ───
    total        = len(accepted)
    completed    = sum(1 for _, t in accepted if t.status_id in COMPLETED_STATUS_IDS)
    in_progress  = sum(1 for _, t in accepted if t.status_id == 6)
    on_hold      = sum(1 for _, t in accepted if t.status_id == 16)
    review       = sum(1 for _, t in accepted if t.status_id == 8)
    delayed_rows = [
        (a, t) for a, t in accepted
        if t.end_date and t.end_date < today and t.status_id not in COMPLETED_STATUS_IDS
    ]
    delayed = len(delayed_rows)
    completion_rate = round((completed / total) * 100, 1) if total else 0


    estimated_hours = sum(float(t.estimated_hours or 0) for _, t in accepted)
    actual_hours    = sum(float(t.actual_hours or 0) for _, t in accepted)


    actions_count = (
        db.query(func.count(TaskProgressLog.log_id))
        .filter(TaskProgressLog.employee_id == emp_id)
        .scalar() or 0
    )


    # ─── Chart (12 شهرًا من السنة الحالية) ───
    cy = today.year
    chart_labels, chart_tasks, chart_rate, chart_hours, chart_actions = [], [], [], [], []
    for m in range(1, 13):
        m_start = date(cy, m, 1)
        m_end   = date(cy, m, monthrange(cy, m)[1])
        chart_labels.append(f"{cy}-{m:02d}")


        m_assigned = (
            db.query(TaskAssignment, OperationalTask)
            .join(OperationalTask, OperationalTask.task_id == TaskAssignment.task_id)
            .filter(
                TaskAssignment.employee_id == emp_id,
                TaskAssignment.is_active == True,
                func.date(TaskAssignment.assigned_at).between(m_start, m_end),
            )
            .all()
        )
        chart_tasks.append(len(m_assigned))
        m_comp = sum(1 for _, t in m_assigned if t.status_id in COMPLETED_STATUS_IDS)
        chart_rate.append(round((m_comp / len(m_assigned)) * 100, 1) if m_assigned else 0)
        chart_hours.append(sum(float(t.actual_hours or 0) for _, t in m_assigned))
        chart_actions.append(
            db.query(func.count(TaskProgressLog.log_id))
            .filter(
                TaskProgressLog.employee_id == emp_id,
                func.date(TaskProgressLog.log_time).between(m_start, m_end),
            )
            .scalar() or 0
        )


    # ─── يحتاج إلى انتباهك ───
    needs_attention = []


    if task_ids:
        for p, t in (
            db.query(AIPrediction, OperationalTask)
            .join(OperationalTask, OperationalTask.task_id == AIPrediction.task_id)
            .filter(
                AIPrediction.task_id.in_(task_ids),
                AIPrediction.prediction_type == "delay",
                AIPrediction.probability >= 0.7,
            ).all()
        ):
            needs_attention.append({
                "type": "ai_delay_risk", "task_id": t.task_id,
                "task_title": t.title,
                "probability": float(p.probability or 0),
                "message": f"يتوقع النظام احتمال تأخر هذه المهمة بنسبة {round(float(p.probability or 0)*100)}%",
            })


        for r, t in (
            db.query(Risk, OperationalTask)
            .join(OperationalTask, OperationalTask.task_id == Risk.task_id)
            .filter(
                Risk.task_id.in_(task_ids),
                Risk.probability * Risk.impact >= 40,
            ).all()
        ):
            needs_attention.append({
                "type": "risk_detected", "task_id": t.task_id,
                "risk_id": r.risk_id, "risk_name": r.name,
                "score": (r.probability or 0) * (r.impact or 0),
                "message": f"خطر مرتفع على المهمة: {r.name}",
            })


    for _, t in delayed_rows:
        needs_attention.append({
            "type": "actually_delayed", "task_id": t.task_id,
            "task_title": t.title,
            "days_late": (today - t.end_date).days,
            "message": f"متأخرة فعليًا بـ {(today - t.end_date).days} يوم",
        })


    for a, t in pending:
        needs_attention.append({
            "type": "pending_acceptance",
            "assignment_id": a.assignment_id, "task_id": t.task_id,
            "task_title": t.title,
            "message": "في انتظار قبولك للمهمة",
        })


    for m, r in (
        db.query(RiskMitigation, Risk)
        .join(Risk, Risk.risk_id == RiskMitigation.risk_id)
        .filter(
            RiskMitigation.assigned_to == str(emp_id),
            RiskMitigation.status_id == 9,
        ).all()
    ):
        needs_attention.append({
            "type": "mitigation_assigned",
            "mitigation_id": m.mitigation_id,
            "risk_name": r.name,
            "due_date": str(m.due_date) if m.due_date else None,
            "message": f"إجراء معالجة خطر مسند إليك: {m.action}",
        })


    for _, t in [(a, t) for a, t in accepted if t.status_id == 8]:
        needs_attention.append({
            "type": "returned_for_review", "task_id": t.task_id,
            "task_title": t.title,
            "message": "أُعيدت المهمة للمراجعة",
        })


    # ─── My Tasks ───
    tasks_out = []
    for a, t in accepted:
        major = db.query(MajorTask).get(t.major_task_id) if t.major_task_id else None
        init  = db.query(Initiative).get(major.initiative_id) if major and major.initiative_id else None
        ai_pred = (
            db.query(AIPrediction)
            .filter(AIPrediction.task_id == t.task_id, AIPrediction.prediction_type == "delay")
            .order_by(desc(AIPrediction.probability)).first()
        )
        is_delayed = bool(t.end_date and t.end_date < today and t.status_id not in COMPLETED_STATUS_IDS)
        st = statuses.get(t.status_id)
        rt = roles_d.get(a.role_type_id)
        tasks_out.append({
            "task_id": t.task_id, "title": t.title,
            "my_role": rt.name_ar if rt else None,
            "initiative": init.name if init else None,
            "major_task": major.name if major else None,
            "end_date": str(t.end_date) if t.end_date else None,
            "progress": progress_map.get(t.task_id, 0),
            "status_ar": st.name_ar if st else None,
            "status_code": st.code if st else None,
            "is_delayed": is_delayed,
            "ai_risk": {
                "probability": float(ai_pred.probability or 0),
                "level": "high" if float(ai_pred.probability or 0) >= 0.7 else "normal",
            } if ai_pred else None,
        })


    # ─── Pending Assignments ───
    pending_out = []
    for a, t in pending:
        major = db.query(MajorTask).get(t.major_task_id) if t.major_task_id else None
        init  = db.query(Initiative).get(major.initiative_id) if major and major.initiative_id else None
        rt    = roles_d.get(a.role_type_id)
        pending_out.append({
            "assignment_id": a.assignment_id, "task_id": t.task_id,
            "task_title": t.title,
            "initiative": init.name if init else None,
            "role_type": rt.name_ar if rt else None,
            "assigned_at": str(a.assigned_at) if a.assigned_at else None,
        })


    # ─── AI Insights ───
    ai_insights = {
        "high_delay_risk_count": sum(1 for x in needs_attention if x["type"] == "ai_delay_risk"),
        "high_risk_count":       sum(1 for x in needs_attention if x["type"] == "risk_detected"),
        "pending_mitigations":   sum(1 for x in needs_attention if x["type"] == "mitigation_assigned"),
        "actually_delayed":      delayed,
        "pending_acceptance":    len(pending),
    }


    return {
        "employee": {
            "employee_id": emp.employee_id, "full_name": emp.full_name,
            "job_title": emp.job_title,
            "department": emp.department.name if emp.department else None,
            "email": emp.email,
        },
        "kpis": {
            "total_tasks": total, "completed": completed,
            "in_progress": in_progress, "on_hold": on_hold, "review": review,
            "delayed": delayed, "completion_rate": completion_rate,
            "estimated_hours": round(estimated_hours, 1),
            "actual_hours": round(actual_hours, 1),
            "actions_count": actions_count,
        },
        "chart": {
            "labels": chart_labels, "tasks": chart_tasks,
            "completion_rate": chart_rate,
            "work_hours": chart_hours, "actions": chart_actions,
        },
        "needs_attention": needs_attention,
        "tasks": tasks_out,
        "pending_assignments": pending_out,
        "ai_insights": ai_insights,
    }




# ─── قبول/رفض الإسناد (ضمن نفس الـ router) ───
from pydantic import BaseModel


class RejectPayload(BaseModel):
    rejection_reason: str


@router.post("/employee/assignments/{assignment_id}/accept")
def accept_assignment(
    assignment_id: int,
    db: Session = Depends(get_db),
    user=Depends(get_current_user),
):
    a = (
        db.query(TaskAssignment)
        .filter(
            TaskAssignment.assignment_id == assignment_id,
            TaskAssignment.employee_id == user.employee_id,
        )
        .first()
    )
    if not a:
        raise HTTPException(404, "Assignment not found")
    a.acceptance_status = "accepted"
    a.accepted_at = datetime.utcnow()
    db.commit()
    return {"success": True, "status": "accepted"}


@router.post("/employee/assignments/{assignment_id}/reject")
def reject_assignment(
    assignment_id: int,
    payload: RejectPayload,
    db: Session = Depends(get_db),
    user=Depends(get_current_user),
):
    if not payload.rejection_reason or len(payload.rejection_reason.strip()) < 3:
        raise HTTPException(400, "Rejection reason is required")
    a = (
        db.query(TaskAssignment)
        .filter(
            TaskAssignment.assignment_id == assignment_id,
            TaskAssignment.employee_id == user.employee_id,
        )
        .first()
    )
    if not a:
        raise HTTPException(404, "Assignment not found")
    a.acceptance_status = "rejected"
    a.rejection_reason  = payload.rejection_reason.strip()
    db.commit()
    return {"success": True, "status": "rejected"}


# ─── Manager Dashboard ────────────────────────────────────────
@router.get("/manager")
def manager_dashboard(db: Session = Depends(get_db), user=Depends(get_current_user)):
    emp_id = user
    emp = db.query(Employee).get(emp_id)
    if not emp:
        raise HTTPException(404, "Employee not found")


    dept_id = emp.department_id


    # البيانات الشخصية (نفس employee)
    personal = employee_dashboard(db=db, user=user)


    # بيانات الإدارة
    dept_tasks = (
        db.query(OperationalTask)
        .filter(OperationalTask.department_id == dept_id,
                OperationalTask.is_active == True)
        .all()
    )
    dept_task_ids = [t.task_id for t in dept_tasks]
    progress_map = _latest_progress_map(db, dept_task_ids)
    status_codes = {s.status_id: s.code for s in db.query(DictStatus).all()}


    employees = (
        db.query(Employee)
        .filter(Employee.department_id == dept_id, Employee.is_active == True)
        .all()
    )


    # KPIs الإدارة
    total = len(dept_tasks)
    completed = sum(1 for t in dept_tasks if status_codes.get(t.status_id) == "completed")
    delayed = sum(1 for t in dept_tasks if status_codes.get(t.status_id) == "delayed")
    in_prog = sum(1 for t in dept_tasks if status_codes.get(t.status_id) == "in_progress")
    high_risks = (
        db.query(func.count(Risk.risk_id))
        .filter(Risk.task_id.in_(dept_task_ids),
                Risk.probability * Risk.impact >= 60)
        .scalar() or 0
    ) if dept_task_ids else 0


    # مهام الإدارة مع اسم الموظف
    assignments = (
        db.query(TaskAssignment, Employee)
        .join(Employee, Employee.employee_id == TaskAssignment.employee_id)
        .filter(TaskAssignment.task_id.in_(dept_task_ids),
                TaskAssignment.is_active == True)
        .all()
    ) if dept_task_ids else []
    task_to_emp = {}
    for a, e in assignments:
        task_to_emp.setdefault(a.task_id, []).append(e.full_name)


    dept_tasks_out = [{
        "task_id": t.task_id, "title": t.title,
        "assignees": task_to_emp.get(t.task_id, []),
        "priority_id": t.priority_id,
        "end_date": str(t.end_date) if t.end_date else None,
        "progress": progress_map.get(t.task_id, 0),
        "status_id": t.status_id,
        "status_code": status_codes.get(t.status_id),
    } for t in dept_tasks]


    # أداء الموظفين
    employees_perf = []
    for e in employees:
        e_tasks = [t for t in dept_tasks if e.employee_id in
                   [a.employee_id for a in
                    db.query(TaskAssignment).filter(
                        TaskAssignment.task_id == t.task_id,
                        TaskAssignment.is_active == True).all()]]
        # أسرع: aggregate via assignments
    # (نفّذ استعلامًا مُجمّعًا)
    agg = (
        db.query(
            TaskAssignment.employee_id,
            func.count(func.distinct(OperationalTask.task_id)).label("total"),
        )
        .join(OperationalTask, OperationalTask.task_id == TaskAssignment.task_id)
        .filter(TaskAssignment.employee_id.in_([e.employee_id for e in employees]),
                TaskAssignment.is_active == True,
                OperationalTask.is_active == True)
        .group_by(TaskAssignment.employee_id)
        .all()
    )
    emp_total = {r.employee_id: r.total for r in agg}


    for e in employees:
        emp_tasks = (
            db.query(OperationalTask)
            .join(TaskAssignment, TaskAssignment.task_id == OperationalTask.task_id)
            .filter(TaskAssignment.employee_id == e.employee_id,
                    TaskAssignment.is_active == True,
                    OperationalTask.is_active == True)
            .all()
        )
        ids = [t.task_id for t in emp_tasks]
        pm = _latest_progress_map(db, ids)
        c = sum(1 for t in emp_tasks if status_codes.get(t.status_id) == "completed")
        d = sum(1 for t in emp_tasks if status_codes.get(t.status_id) == "delayed")
        tt = len(emp_tasks)
        employees_perf.append({
            "employee_id": e.employee_id,
            "full_name": e.full_name,
            "job_title": e.job_title,
            "total": tt, "completed": c, "delayed": d,
            "completion_rate": round((c / tt) * 100, 1) if tt else 0,
            "avg_progress": round(sum(pm.values()) / tt, 1) if tt else 0,
        })


    # مخاطر الإدارة
    dept_risks = []
    if dept_task_ids:
        rows = (
            db.query(Risk, OperationalTask, DictRiskLevel)
            .join(OperationalTask, OperationalTask.task_id == Risk.task_id)
            .outerjoin(DictRiskLevel, DictRiskLevel.risk_level_id == Risk.risk_level_id)
            .filter(Risk.task_id.in_(dept_task_ids))
            .all()
        )
        for r, t, lvl in rows:
            score = (r.probability or 0) * (r.impact or 0)
            dept_risks.append({
                "risk_id": r.risk_id, "name": r.name,
                "task_id": t.task_id, "task_title": t.title,
                "probability": r.probability, "impact": r.impact,
                "score": score,
                "level_code": lvl.code if lvl else _risk_level_by_score(score),
                "status_id": r.status_id,
            })


    # إجراءات التخفيف
    mitigations = []
    if dept_risks:
        risk_ids = [r["risk_id"] for r in dept_risks]
        mit_rows = (
            db.query(RiskMitigation, Employee, DictStatus)
            .outerjoin(Employee, Employee.employee_id == RiskMitigation.assigned_to)
            .outerjoin(DictStatus, DictStatus.status_id == RiskMitigation.status_id)
            .filter(RiskMitigation.risk_id.in_(risk_ids))
            .all()
        )
        for m, e, s in mit_rows:
            mitigations.append({
                "mitigation_id": m.mitigation_id,
                "risk_id": m.risk_id,
                "action": m.action,
                "assigned_to": e.full_name if e else None,
                "due_date": str(m.due_date) if m.due_date else None,
                "status_code": s.code if s else None,
                "status_ar": s.name_ar if s else None,
            })


    # AI predictions للقسم
    ai_preds = []
    if dept_task_ids:
        preds = (
            db.query(AIPrediction, OperationalTask, TaskAssignment, Employee)
            .join(OperationalTask, OperationalTask.task_id == AIPrediction.task_id)
            .outerjoin(TaskAssignment, TaskAssignment.task_id == OperationalTask.task_id)
            .outerjoin(Employee, Employee.employee_id == TaskAssignment.employee_id)
            .filter(AIPrediction.task_id.in_(dept_task_ids),
                    AIPrediction.prediction_type == "delay",
                    AIPrediction.probability >= 0.5)
            .order_by(desc(AIPrediction.probability))
            .limit(20).all()
        )
        for p, t, a, e in preds:
            ai_preds.append({
                "prediction_id": p.prediction_id,
                "task_id": t.task_id, "task_title": t.title,
                "employee": e.full_name if e else None,
                "probability": float(p.probability or 0),
                "confidence": float(p.confidence or 0),
                "features": p.features_used,
                "text": p.text,
            })


    return {
        "personal": personal,
        "department": {
            "department_id": dept_id,
            "department_name": emp.department.name if emp.department else None,
            "employees_count": len(employees),
            "total_tasks": total,
            "in_progress": in_prog,
            "delayed": delayed,
            "completed": completed,
            "high_risks": high_risks,
            "completion_rate": round((completed / total) * 100, 1) if total else 0,
        },
        "tasks": dept_tasks_out,
        "employees": employees_perf,
        "risks": dept_risks,
        "mitigations": mitigations,
        "ai_predictions": ai_preds,
    }




# ─── Minister Dashboard ───────────────────────────────────────
@router.get("/minister")
def minister_dashboard(db: Session = Depends(get_db), user=Depends(get_current_user)):
    status_codes = {s.status_id: s.code for s in db.query(DictStatus).all()}


    # Strategic KPIs
    pillars_count = db.query(func.count(StrategicPillar.pillar_id)).filter(
        StrategicPillar.is_active == True).scalar() or 0
    goals_count = db.query(func.count(StrategicGoal.goal_id)).filter(
        StrategicGoal.is_active == True).scalar() or 0
    programs_count = db.query(func.count(Program.program_id)).filter(
        Program.is_active == True).scalar() or 0
    initiatives_count = db.query(func.count(Initiative.initiative_id)).filter(
        Initiative.is_active == True).scalar() or 0


    # نسبة الإنجاز الاستراتيجي: متوسط progress على كل operational_tasks النشطة
    all_tasks = db.query(OperationalTask).filter(OperationalTask.is_active == True).all()
    ids = [t.task_id for t in all_tasks]
    pm = _latest_progress_map(db, ids)
    strategic_achievement = round(sum(pm.values()) / len(ids), 1) if ids else 0


    delayed_inits = (
        db.query(func.count(Initiative.initiative_id))
        .filter(Initiative.is_active == True,
                Initiative.end_date < date.today(),
                Initiative.status_id != 3)
        .scalar() or 0
    )


    critical_risks = (
        db.query(func.count(Risk.risk_id))
        .filter(Risk.probability * Risk.impact >= 80)
        .scalar() or 0
    )


    # Strategic Hierarchy — Pillars → Goals → Programs → Initiatives
    pillars = []
    for p in db.query(StrategicPillar).filter(StrategicPillar.is_active == True).order_by(StrategicPillar.order_index).all():
        goals = []
        for g in db.query(StrategicGoal).filter(
                StrategicGoal.pillar_id == p.pillar_id,
                StrategicGoal.is_active == True).all():
            programs = []
            for pr in db.query(Program).filter(
                    Program.goal_id == g.goal_id,
                    Program.is_active == True).all():
                inits = []
                for i in db.query(Initiative).filter(
                        Initiative.program_id == pr.program_id,
                        Initiative.is_active == True).all():
                    # progress: متوسط مهامه
                    it_tasks = (
                        db.query(OperationalTask.task_id)
                        .join(MajorTask, MajorTask.major_task_id == OperationalTask.major_task_id)
                        .filter(MajorTask.initiative_id == i.initiative_id,
                                OperationalTask.is_active == True)
                        .all()
                    )
                    i_ids = [x.task_id for x in it_tasks]
                    i_pm = _latest_progress_map(db, i_ids)
                    i_prog = round(sum(i_pm.values()) / len(i_ids), 1) if i_ids else 0
                    inits.append({
                        "initiative_id": i.initiative_id, "name": i.name,
                        "status_id": i.status_id,
                        "end_date": str(i.end_date) if i.end_date else None,
                        "progress": i_prog,
                    })
                programs.append({
                    "program_id": pr.program_id, "name": pr.name,
                    "initiatives": inits,
                })
            goals.append({
                "goal_id": g.goal_id, "title": g.title,
                "target_date": str(g.target_date) if g.target_date else None,
                "programs": programs,
            })
        pillars.append({
            "pillar_id": p.pillar_id, "name": p.name,
            "goals": goals,
        })


    # KPI Performance
    kpi_rows = []
    for k in db.query(KPI).filter(KPI.is_active == True).all():
        latest = (
            db.query(KPIMeasurement)
            .filter(KPIMeasurement.kpi_id == k.kpi_id)
            .order_by(desc(KPIMeasurement.measured_at))
            .first()
        )
        prev = (
            db.query(KPIMeasurement)
            .filter(KPIMeasurement.kpi_id == k.kpi_id)
            .order_by(desc(KPIMeasurement.measured_at))
            .offset(1).first()
        )
        target = float(k.target_max or 0) or float(k.target_min or 0) or 100
        current = float(latest.value) if latest else 0
        achievement = round((current / target) * 100, 1) if target else 0
        trend = "flat"
        if latest and prev:
            if float(latest.value) > float(prev.value): trend = "up"
            elif float(latest.value) < float(prev.value): trend = "down"
        kpi_rows.append({
            "kpi_id": k.kpi_id, "name": k.name, "unit": k.unit,
            "target": target, "current": current,
            "achievement": achievement, "trend": trend,
            "measured_at": str(latest.measured_at) if latest else None,
        })


    # Initiatives requiring attention
    attention = []
    for i in db.query(Initiative).filter(Initiative.is_active == True).all():
        it_tasks = (
            db.query(OperationalTask.task_id)
            .join(MajorTask, MajorTask.major_task_id == OperationalTask.major_task_id)
            .filter(MajorTask.initiative_id == i.initiative_id,
                    OperationalTask.is_active == True).all()
        )
        i_ids = [x.task_id for x in it_tasks]
        i_pm = _latest_progress_map(db, i_ids)
        prog = round(sum(i_pm.values()) / len(i_ids), 1) if i_ids else 0
        delay = None
        if i.end_date and i.end_date < date.today() and prog < 100:
            delay = (date.today() - i.end_date).days
        if (i.end_date and delay) or prog < 30:
            attention.append({
                "initiative_id": i.initiative_id, "name": i.name,
                "program": i.program.name if i.program else None,
                "goal": i.program.goal.title if i.program and i.program.goal else None,
                "progress": prog,
                "end_date": str(i.end_date) if i.end_date else None,
                "delay_days": delay,
                "status_id": i.status_id,
            })


    # Department Performance
    depts = []
    for d in db.query(Department).filter(Department.is_active == True).all():
        d_tasks = db.query(OperationalTask).filter(
            OperationalTask.department_id == d.department_id,
            OperationalTask.is_active == True).all()
        d_ids = [t.task_id for t in d_tasks]
        d_pm = _latest_progress_map(db, d_ids)
        c = sum(1 for t in d_tasks if status_codes.get(t.status_id) == "completed")
        dl = sum(1 for t in d_tasks if status_codes.get(t.status_id) == "delayed")
        hr = (
            db.query(func.count(Risk.risk_id))
            .filter(Risk.task_id.in_(d_ids),
                    Risk.probability * Risk.impact >= 60).scalar() or 0
        ) if d_ids else 0
        depts.append({
            "department_id": d.department_id, "name": d.name,
            "total_tasks": len(d_tasks), "completed": c, "delayed": dl,
            "completion_rate": round((c / len(d_tasks)) * 100, 1) if d_tasks else 0,
            "avg_progress": round(sum(d_pm.values()) / len(d_ids), 1) if d_ids else 0,
            "high_risks": hr,
        })


    # Strategic Risks
    strategic_risks = []
    rows = (
        db.query(Risk, OperationalTask, DictRiskLevel, MajorTask, Initiative)
        .join(OperationalTask, OperationalTask.task_id == Risk.task_id)
        .join(MajorTask, MajorTask.major_task_id == OperationalTask.major_task_id)
        .join(Initiative, Initiative.initiative_id == MajorTask.initiative_id)
        .outerjoin(DictRiskLevel, DictRiskLevel.risk_level_id == Risk.risk_level_id)
        .filter(Risk.probability * Risk.impact >= 40)
        .all()
    )
    for r, t, lvl, mt, i in rows:
        score = (r.probability or 0) * (r.impact or 0)
        strategic_risks.append({
            "risk_id": r.risk_id, "name": r.name,
            "task": t.title, "initiative": i.name,
            "probability": r.probability, "impact": r.impact, "score": score,
            "level_code": lvl.code if lvl else _risk_level_by_score(score),
        })


    # AI Insights — مستخرجة من البيانات الحقيقية
    insights = []
    high_pred = (
        db.query(func.count(AIPrediction.prediction_id))
        .filter(AIPrediction.prediction_type == "delay",
                AIPrediction.probability >= 0.9).scalar() or 0
    )
    if high_pred:
        insights.append({
            "type": "warning",
            "text": f"تشير توقعات النظام إلى وجود {high_pred} مهمة باحتمالية تأخر مرتفعة جدًا.",
        })
    if delayed_inits:
        insights.append({
            "type": "danger",
            "text": f"يوجد {delayed_inits} مبادرة استراتيجية متأخرة عن موعدها المخطط.",
        })
    if critical_risks:
        insights.append({
            "type": "danger",
            "text": f"يوجد {critical_risks} خطر حرج يستوجب تدخلاً فوريًا.",
        })


    return {
        "strategic": {
            "pillars_count": pillars_count,
            "goals_count": goals_count,
            "programs_count": programs_count,
            "initiatives_count": initiatives_count,
            "strategic_achievement": strategic_achievement,
            "delayed_initiatives": delayed_inits,
            "critical_risks": critical_risks,
        },
        "hierarchy": pillars,
        "kpis": kpi_rows,
        "initiatives_attention": attention,
        "departments": depts,
        "strategic_risks": strategic_risks,
        "ai_insights": insights,
    }




# ─── Departments performance (موجود مسبقًا - محفوظ) ──────────
@router.get("/departments-performance")
def departments_performance(db: Session = Depends(get_db), user=Depends(get_current_user)):
    status_codes = {s.status_id: s.code for s in db.query(DictStatus).all()}
    out = []
    for d in db.query(Department).filter(Department.is_active == True).all():
        tasks = db.query(OperationalTask).filter(
            OperationalTask.department_id == d.department_id,
            OperationalTask.is_active == True).all()
        c = sum(1 for t in tasks if status_codes.get(t.status_id) == "completed")
        out.append({
            "department_id": d.department_id,
            "department_name": d.name,
            "total_tasks": len(tasks),
            "completed": c,
            "completion_rate": round((c / len(tasks)) * 100, 1) if tasks else 0,
        })
    return out

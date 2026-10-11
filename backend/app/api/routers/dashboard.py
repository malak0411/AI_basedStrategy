from datetime import date, datetime
from calendar import monthrange
from typing import Optional


from fastapi import APIRouter, Depends, HTTPException
from pydantic import BaseModel
from sqlalchemy import func, and_, desc, text
from sqlalchemy.orm import Session, joinedload


from app.database import get_db
from app.core.dependencies import get_current_user
from app.models import (
    Employee, Department, OperationalTask, MajorTask, Initiative,
    Program, StrategicPillar, StrategicGoal,
    TaskAssignment, TaskProgressLog, Risk, RiskMitigation,
    AIPrediction, DictStatus, DictPriority, DictRoleType, DictRiskLevel,
    KPI, KPIMeasurement, BudgetLine, BudgetTransaction, AIRecommendation,
)


router = APIRouter(prefix="/api/dashboard", tags=["Dashboard"])


TASK_COMPLETED    = 19
TASK_IN_PROGRESS  = 6
TASK_READY        = 26
TASK_ON_HOLD      = 16
TASK_PENDING      = 5
TASK_REVIEW       = 8
INITIATIVE_ACTIVE = 2




def _latest_progress_map(db: Session, task_ids):
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




def _risk_level_code(score: float) -> str:
    if score >= 80: return "critical"
    if score >= 60: return "high"
    if score >= 40: return "medium"
    if score >= 20: return "low"
    return "very_low"




@router.get("/employee")
def employee_dashboard(
    db: Session = Depends(get_db),
    current_user: int = Depends(get_current_user),
):
    emp_id = int(current_user)
    emp = (
        db.query(Employee)
        .options(joinedload(Employee.department))
        .filter(Employee.employee_id == emp_id)
        .first()
    )
    if not emp:
        raise HTTPException(404, "Employee not found")


    today = date.today()


    statuses_d = {s.status_id: s for s in db.query(DictStatus).all()}
    roles_d    = {r.role_type_id: r for r in db.query(DictRoleType).all()}


    rows = (
        db.query(TaskAssignment, OperationalTask, MajorTask, Initiative)
        .join(OperationalTask, OperationalTask.task_id == TaskAssignment.task_id)
        .join(MajorTask, MajorTask.major_task_id == OperationalTask.major_task_id)
        .join(Initiative, Initiative.initiative_id == MajorTask.initiative_id)
        .filter(
            TaskAssignment.employee_id == emp_id,
            TaskAssignment.is_active == True,
            OperationalTask.is_active == True,
            Initiative.is_active == True,
            Initiative.status_id == INITIATIVE_ACTIVE,
        )
        .all()
    )


    accepted = [(a, t, m, i) for a, t, m, i in rows if a.acceptance_status == "accepted"]
    pending  = [(a, t, m, i) for a, t, m, i in rows if a.acceptance_status == "pending"]
    rejected = [(a, t, m, i) for a, t, m, i in rows if a.acceptance_status == "rejected"]


    unique_tasks = {t.task_id: t for _, t, _, _ in accepted}


    total        = len(unique_tasks)
    completed    = sum(1 for t in unique_tasks.values() if t.status_id == TASK_COMPLETED)
    in_progress  = sum(1 for t in unique_tasks.values() if t.status_id == TASK_IN_PROGRESS)
    on_hold      = sum(1 for t in unique_tasks.values() if t.status_id == TASK_ON_HOLD)
    review       = sum(1 for t in unique_tasks.values() if t.status_id == TASK_REVIEW)
    ready        = sum(1 for t in unique_tasks.values() if t.status_id == TASK_READY)
    pending_st   = sum(1 for t in unique_tasks.values() if t.status_id == TASK_PENDING)
    delayed      = sum(
        1 for t in unique_tasks.values()
        if t.end_date and t.end_date < today and t.status_id != TASK_COMPLETED
    )
    completion_rate = round((completed / total) * 100, 1) if total else 0


    estimated_hours = sum(float(a.estimated_hours or 0) for a, _, _, _ in accepted)
    actual_hours    = sum(
        float(t.actual_hours or 0)
        for _, t, _, _ in accepted
        if t.status_id == TASK_COMPLETED
    )


    all_tasks = []
    for a, t, m, i in rows:
        st = statuses_d.get(t.status_id)
        rt = roles_d.get(a.role_type_id)
        is_delayed = bool(t.end_date and t.end_date < today and t.status_id != TASK_COMPLETED)
        all_tasks.append({
            "task_id": t.task_id,
            "title": t.title,
            "my_role": rt.name_ar if rt else None,
            "initiative": i.name,
            "major_task": m.name,
            "status_id": t.status_id,
            "status_ar": st.name_ar if st else None,
            "status_code": st.code if st else None,
            "acceptance_status": a.acceptance_status,
            "rejection_reason": a.rejection_reason,
            "start_date": str(t.start_date) if t.start_date else None,
            "end_date": str(t.end_date) if t.end_date else None,
            "estimated_hours": float(a.estimated_hours or 0),
            "actual_hours": float(t.actual_hours or 0) if t.status_id == TASK_COMPLETED else 0,
            "is_delayed": is_delayed,
        })


    pending_assignments = []
    for a, t, m, i in pending:
        rt = roles_d.get(a.role_type_id)
        pending_assignments.append({
            "assignment_id": a.assignment_id,
            "task_id": t.task_id,
            "title": t.title,
            "note": t.description,
            "initiative": i.name,
            "major_task": m.name,
            "role_type": rt.name_ar if rt else None,
            "estimated_hours": float(a.estimated_hours or 0),
            "assigned_at": str(a.assigned_at) if a.assigned_at else None,
        })


    my_mitigations = []
    mit_rows = (
        db.query(RiskMitigation, Risk, OperationalTask, DictStatus)
        .join(Risk, Risk.risk_id == RiskMitigation.risk_id)
        .outerjoin(OperationalTask, OperationalTask.task_id == RiskMitigation.task_id)
        .outerjoin(DictStatus, DictStatus.status_id == RiskMitigation.status_id)
        .filter(RiskMitigation.assigned_to == str(emp_id))
        .all()
    )
    for m, r, t, s in mit_rows:
        my_mitigations.append({
            "mitigation_id": m.mitigation_id,
            "action": m.action,
            "risk_id": r.risk_id if r else None,
            "risk_name": r.name if r else None,
            "task_id": t.task_id if t else None,
            "task_title": t.title if t else None,
            "status_id": m.status_id,
            "status_ar": s.name_ar if s else None,
            "status_color": s.color_hex if s else "#6c757d",
            "due_date": str(m.due_date) if m.due_date else None,
            "completed_at": str(m.completed_at) if m.completed_at else None,
            "notes": m.notes,
        })


    return {
        "employee": {
            "employee_id": emp.employee_id,
            "full_name": emp.full_name,
            "job_title": emp.job_title,
            "department": emp.department.name if emp.department else None,
        },
        "kpis": {
            "total_tasks": total,
            "completed": completed,
            "in_progress": in_progress,
            "on_hold": on_hold,
            "review": review,
            "ready": ready,
            "pending_status": pending_st,
            "delayed": delayed,
            "completion_rate": completion_rate,
            "estimated_hours": round(estimated_hours, 1),
            "actual_hours": round(actual_hours, 1),
            "hours_diff": round(actual_hours - estimated_hours, 1),
            "my_mitigations_total": len(my_mitigations),
            "pending_acceptance": len(pending),
            "accepted_count": len(accepted),
            "rejected_count": len(rejected),
        },
        "all_tasks": all_tasks,
        "pending_assignments": pending_assignments,
        "my_mitigations": my_mitigations,
    }




class RejectPayload(BaseModel):
    rejection_reason: str




@router.post("/employee/assignments/{assignment_id}/accept")
def accept_assignment(
    assignment_id: int,
    db: Session = Depends(get_db),
    current_user: int = Depends(get_current_user),
):
    emp_id = int(current_user)
    a = (
        db.query(TaskAssignment)
        .filter(
            TaskAssignment.assignment_id == assignment_id,
            TaskAssignment.employee_id == emp_id,
        )
        .first()
    )
    if not a:
        raise HTTPException(404, "Assignment not found")
    if a.acceptance_status != "pending":
        raise HTTPException(400, "Assignment is not pending")
    a.acceptance_status = "accepted"
    a.accepted_at = datetime.utcnow()
    db.commit()
    return {"success": True}




@router.post("/employee/assignments/{assignment_id}/reject")
def reject_assignment(
    assignment_id: int,
    payload: RejectPayload,
    db: Session = Depends(get_db),
    current_user: int = Depends(get_current_user),
):
    emp_id = int(current_user)
    reason = (payload.rejection_reason or "").strip()
    if len(reason) < 3:
        raise HTTPException(400, "Rejection reason is required")
    a = (
        db.query(TaskAssignment)
        .filter(
            TaskAssignment.assignment_id == assignment_id,
            TaskAssignment.employee_id == emp_id,
        )
        .first()
    )
    if not a:
        raise HTTPException(404, "Assignment not found")
    if a.acceptance_status != "pending":
        raise HTTPException(400, "Assignment is not pending")
    a.acceptance_status = "rejected"
    a.rejection_reason = reason
    db.commit()
    return {"success": True}




@router.get("/manager")
def manager_dashboard(
    db: Session = Depends(get_db),
    current_user: int = Depends(get_current_user),
):
    emp_id = int(current_user)
    emp = (
        db.query(Employee)
        .options(joinedload(Employee.department))
        .filter(Employee.employee_id == emp_id)
        .first()
    )
    if not emp:
        raise HTTPException(404, "Employee not found")


    dept_id = emp.department_id
    dept    = db.query(Department).filter(Department.department_id == dept_id).first()


    statuses_d   = {s.status_id: s for s in db.query(DictStatus).all()}
    priorities_d = {p.priority_id: p for p in db.query(DictPriority).all()}
    roles_d      = {r.role_type_id: r for r in db.query(DictRoleType).all()}


    active_inits = (
        db.query(Initiative)
        .filter(Initiative.is_active == True, Initiative.status_id == INITIATIVE_ACTIVE)
        .all()
    )
    active_init_ids = [i.initiative_id for i in active_inits]
    inits_map = {i.initiative_id: i for i in active_inits}


    mtd_rows = db.execute(text("""
        SELECT major_task_id, responsibility_type
        FROM major_task_departments
        WHERE department_id = :d
    """), {"d": dept_id}).fetchall()
    mtd_role_map = {row[0]: (row[1] or "").strip().upper() for row in mtd_rows}
    my_major_ids = list(mtd_role_map.keys())


    major_tasks = []
    if my_major_ids and active_init_ids:
        major_tasks = (
            db.query(MajorTask)
            .filter(
                MajorTask.is_active == True,
                MajorTask.major_task_id.in_(my_major_ids),
                MajorTask.initiative_id.in_(active_init_ids),
            )
            .all()
        )
    major_map = {mt.major_task_id: mt for mt in major_tasks}
    major_ids = list(major_map.keys())


    op_tasks = []
    if major_ids:
        op_tasks = (
            db.query(OperationalTask)
            .filter(
                OperationalTask.is_active == True,
                OperationalTask.department_id == dept_id,
                OperationalTask.major_task_id.in_(major_ids),
            )
            .all()
        )
    op_ids = [t.task_id for t in op_tasks]
    progress_map = _latest_progress_map(db, op_ids)
    today = date.today()


    assignments = []
    if op_ids:
        assignments = (
            db.query(TaskAssignment, Employee)
            .join(Employee, Employee.employee_id == TaskAssignment.employee_id)
            .filter(
                TaskAssignment.task_id.in_(op_ids),
                TaskAssignment.is_active == True,
            )
            .all()
        )


    participants_map = {}
    accepted_ids_per_task = {}
    for a, e in assignments:
        if a.acceptance_status == "accepted":
            participants_map.setdefault(a.task_id, []).append(e.full_name)
            accepted_ids_per_task.setdefault(a.task_id, set()).add(e.employee_id)


    unassigned_tasks = [
        t for t in op_tasks
        if t.status_id == TASK_ON_HOLD or not accepted_ids_per_task.get(t.task_id)
    ]


    def role_ar(rt):
        rt = (rt or "").upper()
        if rt == "LEAD":    return "مسؤولة"
        if rt == "SUPPORT": return "مساندة"
        if rt in ("CO-LEAD", "CO_LEAD"): return "مسؤولة مشاركة"
        return rt or "—"


    major_out = []
    for mt in major_tasks:
        mt_ops = [t for t in op_tasks if t.major_task_id == mt.major_task_id]
        total_mt = len(mt_ops)
        comp_mt  = sum(1 for t in mt_ops if t.status_id == TASK_COMPLETED)
        prog_mt  = round(sum(progress_map.get(t.task_id, 0) for t in mt_ops) / total_mt, 1) if total_mt else 0
        init = inits_map.get(mt.initiative_id)
        pr   = priorities_d.get(mt.priority_id)
        rt   = mtd_role_map.get(mt.major_task_id, "")


        major_out.append({
            "major_task_id": mt.major_task_id,
            "name": mt.name,
            "description": mt.description,
            "initiative_id": mt.initiative_id,
            "initiative_name": init.name if init else None,
            "priority_id": mt.priority_id,
            "priority_name": pr.name_ar if pr else None,
            "priority_color": pr.color_hex if pr else None,
            "estimated_duration_days": mt.estimated_duration_days,
            "is_cross_department": bool(mt.is_cross_department),
            "total_tasks": total_mt,
            "completed_tasks": comp_mt,
            "progress": prog_mt,
            "responsibility_type": rt,
            "responsibility_ar": role_ar(rt),
        })


    def build_op(t):
        mt = major_map.get(t.major_task_id)
        init = inits_map.get(mt.initiative_id) if mt else None
        st = statuses_d.get(t.status_id)
        pr = priorities_d.get(t.priority_id)
        is_delayed = bool(t.end_date and t.end_date < today and t.status_id != TASK_COMPLETED)
        return {
            "task_id": t.task_id,
            "title": t.title,
            "description": t.description,
            "major_task_id": t.major_task_id,
            "major_task_name": mt.name if mt else None,
            "initiative_name": init.name if init else None,
            "status_id": t.status_id,
            "status_name": st.name_ar if st else None,
            "status_color": st.color_hex if st else None,
            "priority_id": t.priority_id,
            "priority_name": pr.name_ar if pr else None,
            "priority_color": pr.color_hex if pr else None,
            "start_date": str(t.start_date) if t.start_date else None,
            "end_date": str(t.end_date) if t.end_date else None,
            "estimated_hours": float(t.estimated_hours or 0),
            "actual_hours": float(t.actual_hours or 0),
            "progress": progress_map.get(t.task_id, 0),
            "assignees": participants_map.get(t.task_id, []),
            "has_assignees": bool(participants_map.get(t.task_id)),
            "is_delayed": is_delayed,
        }


    op_out         = [build_op(t) for t in op_tasks]
    review_tasks   = [build_op(t) for t in op_tasks if t.status_id == TASK_REVIEW]
    unassigned_out = [build_op(t) for t in unassigned_tasks]


    total       = len(op_out)
    completed   = sum(1 for t in op_out if t["status_id"] == TASK_COMPLETED)
    in_progress = sum(1 for t in op_out if t["status_id"] == TASK_IN_PROGRESS)
    delayed     = sum(1 for t in op_out if t["is_delayed"])
    review      = len(review_tasks)
    unassigned  = len(unassigned_out)
    on_hold_count = sum(1 for t in op_tasks if t.status_id == TASK_ON_HOLD)
    completion_rate = round((completed / total) * 100, 1) if total else 0
    avg_progress    = round(sum(progress_map.values()) / total, 1) if total else 0


    lead_count    = sum(1 for m in major_out if m["responsibility_type"] == "LEAD")
    support_count = sum(1 for m in major_out if m["responsibility_type"] == "SUPPORT")


    employees = (
        db.query(Employee)
        .filter(Employee.department_id == dept_id, Employee.is_active == True)
        .all()
    )


    employees_out = []
    for e in employees:
        emp_asg = [(a, t) for a, emp2 in assignments
                   for t in op_tasks
                   if emp2.employee_id == e.employee_id and t.task_id == a.task_id]
        emp_task_ids = list({a.task_id for a, _ in emp_asg})


        e_total     = len(emp_asg)
        e_completed = sum(1 for _, t in emp_asg if t.status_id == TASK_COMPLETED)
        e_delayed   = sum(1 for _, t in emp_asg
                          if t.end_date and t.end_date < today and t.status_id != TASK_COMPLETED)
        e_accepted  = sum(1 for a, _ in emp_asg if a.acceptance_status == "accepted")
        e_pending   = sum(1 for a, _ in emp_asg if a.acceptance_status == "pending")
        e_rejected  = sum(1 for a, _ in emp_asg if a.acceptance_status == "rejected")
        e_est_hours = sum(float(a.estimated_hours or 0) for a, _ in emp_asg)
        e_act_hours = sum(float(t.actual_hours or 0) for _, t in emp_asg)
        e_completion = round((e_completed / e_total) * 100, 1) if e_total else 0
        e_avg_prog = round(sum(progress_map.get(t.task_id, 0) for _, t in emp_asg) / e_total, 1) if e_total else 0


        employees_out.append({
            "employee_id": e.employee_id,
            "full_name": e.full_name,
            "job_title": e.job_title,
            "email": e.email,
            "phone_number": e.phone_number,
            "total_assignments": e_total,
            "total_tasks": len(emp_task_ids),
            "completed": e_completed,
            "delayed": e_delayed,
            "accepted": e_accepted,
            "pending": e_pending,
            "rejected": e_rejected,
            "estimated_hours": round(e_est_hours, 1),
            "actual_hours": round(e_act_hours, 1),
            "completion_rate": e_completion,
            "avg_progress": e_avg_prog,
        })


    status_dist = [
        {"label": "مكتملة",       "value": completed,   "color": "#4caf50"},
        {"label": "قيد التنفيذ",   "value": in_progress, "color": "#3b82f6"},
        {"label": "قيد المراجعة",  "value": review,      "color": "#8b5cf6"},
        {"label": "متأخرة",       "value": delayed,     "color": "#ef4444"},
        {"label": "معلقة / غير موزعة", "value": unassigned, "color": "#9ca3af"},
    ]
    top_employees = sorted(employees_out, key=lambda x: -x["completion_rate"])[:6]


    return {
        "manager": {
            "employee_id": emp.employee_id,
            "full_name": emp.full_name,
            "job_title": emp.job_title,
            "department_id": dept_id,
            "department_name": dept.name if dept else None,
        },
        "kpis": {
            "initiatives_count": len(active_inits),
            "major_tasks_count": len(major_tasks),
            "major_lead_count": lead_count,
            "major_support_count": support_count,
            "operational_tasks_count": total,
            "employees_count": len(employees),
            "completed": completed,
            "in_progress": in_progress,
            "review": review,
            "delayed": delayed,
            "unassigned": unassigned,
            "on_hold": on_hold_count,
            "completion_rate": completion_rate,
            "avg_progress": avg_progress,
        },
        "major_tasks": major_out,
        "operational_tasks": op_out,
        "review_tasks": review_tasks,
        "unassigned_tasks": unassigned_out,
        "employees": employees_out,
        "charts": {
            "status_distribution": status_dist,
            "top_employees": [
                {
                    "name": e["full_name"],
                    "completion_rate": e["completion_rate"],
                    "avg_progress": e["avg_progress"],
                    "total": e["total_assignments"],
                }
                for e in top_employees
            ],
        },
    }




@router.get("/manager/task-timeline")
def manager_task_timeline(
    db: Session = Depends(get_db),
    current_user: int = Depends(get_current_user),
):
    emp_id = int(current_user)
    emp = db.query(Employee).filter(Employee.employee_id == emp_id).first()
    if not emp:
        raise HTTPException(404, "Employee not found")


    dept_id = emp.department_id


    mtd_rows = db.execute(text("""
        SELECT major_task_id FROM major_task_departments
        WHERE department_id = :d
    """), {"d": dept_id}).fetchall()
    my_major_ids = [r[0] for r in mtd_rows]


    active_inits = (
        db.query(Initiative)
        .filter(Initiative.is_active == True, Initiative.status_id == INITIATIVE_ACTIVE)
        .all()
    )
    active_init_ids = [i.initiative_id for i in active_inits]


    op_tasks = []
    if my_major_ids and active_init_ids:
        op_tasks = (
            db.query(OperationalTask)
            .join(MajorTask, MajorTask.major_task_id == OperationalTask.major_task_id)
            .filter(
                OperationalTask.is_active == True,
                OperationalTask.department_id == dept_id,
                OperationalTask.major_task_id.in_(my_major_ids),
                MajorTask.initiative_id.in_(active_init_ids),
            )
            .all()
        )


    statuses_d = {s.status_id: s for s in db.query(DictStatus).all()}
    today = date.today()


    timeline = []
    for t in op_tasks:
        logs = (
            db.query(TaskProgressLog)
            .filter(TaskProgressLog.task_id == t.task_id)
            .order_by(TaskProgressLog.log_time.asc())
            .all()
        )


        first_log = logs[0].log_time if logs else None
        last_log  = logs[-1].log_time if logs else None


        started_at   = None
        completed_at = None
        for l in logs:
            if l.status_new == TASK_IN_PROGRESS and not started_at:
                started_at = l.log_time
            if l.status_new == TASK_COMPLETED and not completed_at:
                completed_at = l.log_time


        planned_start = t.start_date
        planned_end   = t.end_date
        planned_days  = (planned_end - planned_start).days if (planned_start and planned_end) else None


        actual_start = started_at.date() if started_at else None
        actual_end   = completed_at.date() if completed_at else None
        actual_days  = (actual_end - actual_start).days if (actual_start and actual_end) else None


        status_durations = {}
        for i, l in enumerate(logs):
            from_status = l.status_old
            if not from_status:
                continue
            next_time = logs[i + 1].log_time if i + 1 < len(logs) else None
            if next_time:
                dur = (next_time - l.log_time).days
            else:
                dur = (datetime.utcnow() - l.log_time).days
            status_durations[from_status] = status_durations.get(from_status, 0) + dur


        time_in_progress = status_durations.get(TASK_IN_PROGRESS, 0)
        time_on_hold     = status_durations.get(TASK_ON_HOLD, 0)
        time_in_review   = status_durations.get(TASK_REVIEW, 0)
        time_pending     = status_durations.get(TASK_PENDING, 0)


        is_delayed = bool(planned_end and planned_end < today and t.status_id != TASK_COMPLETED)
        delay_days = (today - planned_end).days if is_delayed else 0


        final_delay_days = 0
        if completed_at and planned_end:
            final_delay_days = (completed_at.date() - planned_end).days


        st = statuses_d.get(t.status_id)


        timeline.append({
            "task_id": t.task_id,
            "title": t.title,
            "planned_start": str(planned_start) if planned_start else None,
            "planned_end": str(planned_end) if planned_end else None,
            "planned_days": planned_days,
            "actual_start": str(actual_start) if actual_start else None,
            "actual_end": str(actual_end) if actual_end else None,
            "actual_days": actual_days,
            "first_log": str(first_log) if first_log else None,
            "last_log": str(last_log) if last_log else None,
            "logs_count": len(logs),
            "status_id": t.status_id,
            "status_name": st.name_ar if st else None,
            "time_in_progress_days": time_in_progress,
            "time_on_hold_days": time_on_hold,
            "time_in_review_days": time_in_review,
            "time_pending_days": time_pending,
            "is_delayed": is_delayed,
            "delay_days": delay_days,
            "final_delay_days": final_delay_days,
        })


    total = len(timeline)
    completed_rows = [r for r in timeline if r["status_id"] == TASK_COMPLETED]
    delayed_rows   = [r for r in timeline if r["is_delayed"]]
    on_hold_rows   = [r for r in timeline if r["time_on_hold_days"] > 0]


    avg_planned = round(sum(r["planned_days"] or 0 for r in timeline) / total, 1) if total else 0
    completed_with_actual = [r for r in completed_rows if r["actual_days"] is not None]
    avg_actual = round(sum(r["actual_days"] for r in completed_with_actual) / len(completed_with_actual), 1) if completed_with_actual else 0
    avg_delay = round(sum(r["final_delay_days"] for r in completed_rows) / len(completed_rows), 1) if completed_rows else 0


    kpis = {
        "total_tasks": total,
        "completed": len(completed_rows),
        "delayed": len(delayed_rows),
        "on_hold": len(on_hold_rows),
        "avg_planned_days": avg_planned,
        "avg_actual_days": avg_actual,
        "avg_delay_days": avg_delay,
    }


    return {"kpis": kpis, "timeline": timeline}




@router.get("/employee/{employee_id}/detail")
def employee_detail(
    employee_id: int,
    db: Session = Depends(get_db),
    current_user: int = Depends(get_current_user),
):
    emp = (
        db.query(Employee)
        .options(joinedload(Employee.department))
        .filter(Employee.employee_id == employee_id)
        .first()
    )
    if not emp:
        raise HTTPException(404, "Employee not found")


    today = date.today()
    statuses_d   = {s.status_id: s for s in db.query(DictStatus).all()}
    priorities_d = {p.priority_id: p for p in db.query(DictPriority).all()}
    roles_d      = {r.role_type_id: r for r in db.query(DictRoleType).all()}


    assignments = (
        db.query(TaskAssignment, OperationalTask, MajorTask, Initiative)
        .join(OperationalTask, OperationalTask.task_id == TaskAssignment.task_id)
        .join(MajorTask, MajorTask.major_task_id == OperationalTask.major_task_id)
        .join(Initiative, Initiative.initiative_id == MajorTask.initiative_id)
        .filter(
            TaskAssignment.employee_id == employee_id,
            TaskAssignment.is_active == True,
        )
        .all()
    )


    task_ids = list({t.task_id for _, t, _, _ in assignments})
    progress_map = _latest_progress_map(db, task_ids)


    accepted = [(a, t, m, i) for a, t, m, i in assignments if a.acceptance_status == "accepted"]
    pending  = [(a, t, m, i) for a, t, m, i in assignments if a.acceptance_status == "pending"]
    rejected = [(a, t, m, i) for a, t, m, i in assignments if a.acceptance_status == "rejected"]


    est_hours    = sum(float(a.estimated_hours or 0) for a, _, _, _ in assignments)
    actual_hours = sum(float(t.actual_hours    or 0) for _, t, _, _ in assignments)
    completed    = sum(1 for _, t, _, _ in accepted if t.status_id == TASK_COMPLETED)
    in_progress  = sum(1 for _, t, _, _ in accepted if t.status_id == TASK_IN_PROGRESS)
    delayed      = sum(1 for _, t, _, _ in accepted
                       if t.end_date and t.end_date < today and t.status_id != TASK_COMPLETED)


    tasks_out = []
    for a, t, m, i in accepted:
        st = statuses_d.get(t.status_id)
        pr = priorities_d.get(t.priority_id)
        rt = roles_d.get(a.role_type_id)
        is_delayed = bool(t.end_date and t.end_date < today and t.status_id != TASK_COMPLETED)
        tasks_out.append({
            "task_id": t.task_id,
            "title": t.title,
            "major_task_name": m.name,
            "initiative_name": i.name,
            "my_role": rt.name_ar if rt else None,
            "status_id": t.status_id,
            "status_name": st.name_ar if st else None,
            "priority_name": pr.name_ar if pr else None,
            "end_date": str(t.end_date) if t.end_date else None,
            "estimated_hours": float(t.estimated_hours or 0),
            "actual_hours": float(t.actual_hours or 0),
            "my_estimated_hours": float(a.estimated_hours or 0),
            "progress": progress_map.get(t.task_id, 0),
            "is_delayed": is_delayed,
            "acceptance_status": a.acceptance_status,
        })


    status_counts = {}
    for t in tasks_out:
        key = t["status_name"] or "غير معروف"
        status_counts[key] = status_counts.get(key, 0) + 1


    return {
        "employee": {
            "employee_id": emp.employee_id,
            "full_name": emp.full_name,
            "job_title": emp.job_title,
            "email": emp.email,
            "phone_number": emp.phone_number,
            "hire_date": str(emp.hire_date) if emp.hire_date else None,
            "last_login": str(emp.last_login) if emp.last_login else None,
            "department_name": emp.department.name if emp.department else None,
        },
        "kpis": {
            "total_assignments": len(assignments),
            "accepted": len(accepted),
            "pending": len(pending),
            "rejected": len(rejected),
            "completed": completed,
            "in_progress": in_progress,
            "delayed": delayed,
            "estimated_hours": round(est_hours, 1),
            "actual_hours": round(actual_hours, 1),
            "hours_diff": round(actual_hours - est_hours, 1),
            "avg_progress": round(sum(progress_map.values()) / len(task_ids), 1) if task_ids else 0,
            "completion_rate": round((completed / len(accepted)) * 100, 1) if accepted else 0,
        },
        "tasks": tasks_out,
        "status_distribution": [{"label": k, "value": v} for k, v in status_counts.items()],
    }


@router.get("/minister")
def minister_dashboard(
    db: Session = Depends(get_db),
    current_user: int = Depends(get_current_user),
):
    today = date.today()


    def kpi_health(value, target):
        if not target:
            return "red"
        pct = (float(value) / float(target)) * 100
        if pct >= 90: return "green"
        if pct >= 70: return "yellow"
        return "red"


    all_statuses = {s.status_id: s for s in db.query(DictStatus).all()}


    all_pillars = (
        db.query(StrategicPillar)
        .filter(StrategicPillar.is_active == True)
        .order_by(StrategicPillar.order_index)
        .all()
    )
    all_pillar_ids = [p.pillar_id for p in all_pillars]


    all_goals = []
    if all_pillar_ids:
        all_goals = (
            db.query(StrategicGoal)
            .filter(
                StrategicGoal.is_active == True,
                StrategicGoal.pillar_id.in_(all_pillar_ids),
            )
            .all()
        )
    goals_by_pillar_all = {}
    for g in all_goals:
        goals_by_pillar_all.setdefault(g.pillar_id, []).append(g)
    all_goal_ids = [g.goal_id for g in all_goals]


    all_programs = []
    if all_goal_ids:
        all_programs = (
            db.query(Program)
            .filter(Program.is_active == True, Program.goal_id.in_(all_goal_ids))
            .all()
        )
    programs_by_goal_all = {}
    for p in all_programs:
        programs_by_goal_all.setdefault(p.goal_id, []).append(p)
    all_program_ids = [p.program_id for p in all_programs]


    all_inits = []
    if all_program_ids:
        all_inits = (
            db.query(Initiative)
            .filter(
                Initiative.is_active == True,
                Initiative.program_id.in_(all_program_ids),
            )
            .all()
        )
    inits_by_program_all = {}
    for i in all_inits:
        inits_by_program_all.setdefault(i.program_id, []).append(i)
    all_init_ids = [i.initiative_id for i in all_inits]


    all_majors_map = []
    if all_init_ids:
        all_majors_map = (
            db.query(MajorTask)
            .filter(
                MajorTask.is_active == True,
                MajorTask.initiative_id.in_(all_init_ids),
            )
            .all()
        )
    majors_by_init_all = {}
    for mt in all_majors_map:
        majors_by_init_all.setdefault(mt.initiative_id, []).append(mt)
    all_major_ids_map = [mt.major_task_id for mt in all_majors_map]


    all_ops_map = []
    if all_major_ids_map:
        all_ops_map = (
            db.query(OperationalTask)
            .filter(
                OperationalTask.is_active == True,
                OperationalTask.major_task_id.in_(all_major_ids_map),
            )
            .all()
        )
    tasks_by_major_all = {}
    for t in all_ops_map:
        tasks_by_major_all.setdefault(t.major_task_id, []).append(t)


    all_progress_map = _latest_progress_map(db, [t.task_id for t in all_ops_map])


    def init_progress_all(init_id):
        mts = majors_by_init_all.get(init_id, [])
        tids = []
        for mt in mts:
            for t in tasks_by_major_all.get(mt.major_task_id, []):
                tids.append(t.task_id)
        if not tids:
            return 0, 0
        total = sum(all_progress_map.get(tid, 0) for tid in tids)
        return round(total / len(tids), 1), len(tids)


    full_hierarchy = []
    for p in all_pillars:
        p_goals_all = goals_by_pillar_all.get(p.pillar_id, [])
        goals_out_all = []
        for g in p_goals_all:
            p_progs = programs_by_goal_all.get(g.goal_id, [])
            programs_out_all = []
            for pr in p_progs:
                p_inits = inits_by_program_all.get(pr.program_id, [])
                inits_out_all = []
                for i in p_inits:
                    prog, task_count = init_progress_all(i.initiative_id)
                    st = all_statuses.get(i.status_id)
                    inits_out_all.append({
                        "initiative_id": i.initiative_id,
                        "name": i.name,
                        "progress": prog,
                        "status_id": i.status_id,
                        "status_ar": st.name_ar if st else None,
                        "status_color": st.color_hex if st else "#6c757d",
                        "end_date": str(i.end_date) if i.end_date else None,
                        "is_delayed": bool(i.end_date and i.end_date < today and i.status_id != 3),
                        "task_count": task_count,
                    })
                avg_p = round(sum(x["progress"] for x in inits_out_all) / len(inits_out_all), 1) if inits_out_all else 0
                pr_st = all_statuses.get(pr.status_id)
                programs_out_all.append({
                    "program_id": pr.program_id,
                    "name": pr.name,
                    "progress": avg_p,
                    "status_ar": pr_st.name_ar if pr_st else None,
                    "status_color": pr_st.color_hex if pr_st else "#6c757d",
                    "initiatives": inits_out_all,
                })
            avg_g = round(sum(x["progress"] for x in programs_out_all) / len(programs_out_all), 1) if programs_out_all else 0
            goals_out_all.append({
                "goal_id": g.goal_id,
                "title": g.title,
                "target_date": str(g.target_date) if g.target_date else None,
                "is_current": bool(g.is_current),
                "progress": avg_g,
                "programs": programs_out_all,
            })
        avg_pil = round(sum(x["progress"] for x in goals_out_all) / len(goals_out_all), 1) if goals_out_all else 0
        full_hierarchy.append({
            "pillar_id": p.pillar_id,
            "name": p.name,
            "progress": avg_pil,
            "goals": goals_out_all,
        })


    current_goals = [g for g in all_goals if g.is_current]
    current_goal_ids = [g.goal_id for g in current_goals]


    current_programs = [p for p in all_programs if p.goal_id in current_goal_ids]
    current_program_ids = [p.program_id for p in current_programs]


    active_inits = [
        i for i in all_inits
        if i.program_id in current_program_ids and i.status_id == INITIATIVE_ACTIVE
    ]
    active_init_ids = [i.initiative_id for i in active_inits]
    inits_by_program = {}
    for i in active_inits:
        inits_by_program.setdefault(i.program_id, []).append(i)


    goals_by_pillar = {}
    for g in current_goals:
        goals_by_pillar.setdefault(g.pillar_id, []).append(g)


    programs_by_goal = {}
    for p in current_programs:
        programs_by_goal.setdefault(p.goal_id, []).append(p)


    active_major_tasks = [mt for mt in all_majors_map if mt.initiative_id in active_init_ids]
    majors_by_init = {}
    for mt in active_major_tasks:
        majors_by_init.setdefault(mt.initiative_id, []).append(mt)
    active_major_ids = [mt.major_task_id for mt in active_major_tasks]


    active_tasks = [t for t in all_ops_map if t.major_task_id in active_major_ids]
    active_task_ids = [t.task_id for t in active_tasks]


    tasks_by_major = {}
    for t in active_tasks:
        tasks_by_major.setdefault(t.major_task_id, []).append(t)


    progress_map = {tid: all_progress_map[tid] for tid in active_task_ids if tid in all_progress_map}


    def initiative_progress(init_id):
        mts = majors_by_init.get(init_id, [])
        tids = []
        for mt in mts:
            for t in tasks_by_major.get(mt.major_task_id, []):
                tids.append(t.task_id)
        if not tids:
            return 0, 0
        total = sum(progress_map.get(tid, 0) for tid in tids)
        return round(total / len(tids), 1), len(tids)


    kpis = db.query(KPI).filter(KPI.is_active == True).all()
    kpi_ids = [k.kpi_id for k in kpis]


    latest_measurements = {}
    prev_measurements = {}
    if kpi_ids:
        all_m = (
            db.query(KPIMeasurement)
            .filter(KPIMeasurement.kpi_id.in_(kpi_ids))
            .order_by(KPIMeasurement.kpi_id, desc(KPIMeasurement.measured_at))
            .all()
        )
        for m in all_m:
            if m.kpi_id not in latest_measurements:
                latest_measurements[m.kpi_id] = m
            elif m.kpi_id not in prev_measurements:
                prev_measurements[m.kpi_id] = m


    kpi_rows = []
    for k in kpis:
        latest = latest_measurements.get(k.kpi_id)
        prev = prev_measurements.get(k.kpi_id)
        target = float(k.target_max or 0) or float(k.target_min or 0) or 100
        current = float(latest.value) if latest else 0
        achievement = round((current / target) * 100, 1) if target else 0
        trend = "flat"
        if latest and prev:
            if float(latest.value) > float(prev.value): trend = "up"
            elif float(latest.value) < float(prev.value): trend = "down"
        kpi_rows.append({
            "kpi_id": k.kpi_id, "name": k.name, "category": k.category,
            "unit": k.unit, "target": target, "current": current,
            "achievement": achievement, "trend": trend,
            "health": kpi_health(current, target),
            "measured_at": str(latest.measured_at) if latest else None,
        })


    green_c = sum(1 for k in kpi_rows if k["health"] == "green")
    yellow_c = sum(1 for k in kpi_rows if k["health"] == "yellow")
    red_c = sum(1 for k in kpi_rows if k["health"] == "red")
    overall_index = round(sum(k["achievement"] for k in kpi_rows) / len(kpi_rows), 1) if kpi_rows else 0


    completed_inits = sum(1 for i in active_inits if i.status_id == 3)
    delayed_inits = sum(1 for i in active_inits if i.end_date and i.end_date < today and i.status_id != 3)
    completed_pct = round((completed_inits / len(active_inits)) * 100, 1) if active_inits else 0


    total_alloc = 0.0
    total_spent = 0.0
    dept_budgets = {}
    try:
        budget_row = db.query(
            func.coalesce(func.sum(BudgetLine.allocated_amount), 0),
            func.coalesce(func.sum(BudgetLine.spent_amount), 0),
        ).first()
        total_alloc = float(budget_row[0] or 0)
        total_spent = float(budget_row[1] or 0)


        budget_by_dept = (
            db.query(
                BudgetLine.department_id,
                func.coalesce(func.sum(BudgetLine.allocated_amount), 0),
                func.coalesce(func.sum(BudgetLine.spent_amount), 0),
            )
            .group_by(BudgetLine.department_id)
            .all()
        )
        for did, a, s in budget_by_dept:
            dept_budgets[did] = (float(a or 0), float(s or 0))
    except Exception:
        pass


    spent_pct = round((total_spent / total_alloc) * 100, 1) if total_alloc else 0


    impact_categories = ("أثر", "نتائج", "اجتماعي", "تنموي")
    impact_kpis = [k for k in kpi_rows if (k["category"] or "") in impact_categories]
    if not impact_kpis:
        impact_kpis = kpi_rows[:5]


    goal_by_id = {g.goal_id: g for g in current_goals}
    program_by_id = {p.program_id: p for p in current_programs}
    pillar_by_id = {p.pillar_id: p for p in all_pillars}


    transform_inits = []
    for i in active_inits:
        pr = program_by_id.get(i.program_id)
        goal = goal_by_id.get(pr.goal_id) if pr else None
        pillar = pillar_by_id.get(goal.pillar_id) if goal else None
        prog, task_count = initiative_progress(i.initiative_id)
        delay = (today - i.end_date).days if (i.end_date and i.end_date < today and i.status_id != 3) else 0
        transform_inits.append({
            "initiative_id": i.initiative_id,
            "name": i.name,
            "pillar_name": pillar.name if pillar else None,
            "goal_name": goal.title if goal else None,
            "priority_id": i.priority_id,
            "progress": prog,
            "end_date": str(i.end_date) if i.end_date else None,
            "status_id": i.status_id,
            "delay_days": delay,
            "task_count": task_count,
        })
    transform_inits.sort(key=lambda x: (x["priority_id"] or 99, -x["progress"]))
    transform_inits = transform_inits[:8]


    strat_risks = []
    if active_task_ids:
        risk_rows = (
            db.query(Risk, OperationalTask, Initiative, DictRiskLevel)
            .join(OperationalTask, OperationalTask.task_id == Risk.task_id)
            .join(MajorTask, MajorTask.major_task_id == OperationalTask.major_task_id)
            .join(Initiative, Initiative.initiative_id == MajorTask.initiative_id)
            .outerjoin(DictRiskLevel, DictRiskLevel.risk_level_id == Risk.risk_level_id)
            .filter(
                Risk.task_id.in_(active_task_ids),
                Initiative.status_id == INITIATIVE_ACTIVE,
            )
            .all()
        )
        for r, t, i, lvl in risk_rows:
            score = (r.probability or 0) * (r.impact or 0)
            if score < 40:
                continue
            strat_risks.append({
                "risk_id": r.risk_id, "name": r.name,
                "task": t.title, "initiative": i.name,
                "score": score, "probability": r.probability, "impact": r.impact,
                "level_ar": lvl.name_ar if lvl else _risk_level_code(score),
                "level_color": lvl.color_hex if lvl else "#6c757d",
            })
    strat_risks.sort(key=lambda x: -x["score"])
    strat_risks = strat_risks[:10]


    cy = today.year
    year_start = date(cy, 1, 1)
    year_end = date(cy, 12, 31)


    kpi_trend_raw = (
        db.query(
            func.extract('month', KPIMeasurement.measured_at).label('m'),
            func.avg(KPIMeasurement.value).label('v'),
        )
        .filter(func.date(KPIMeasurement.measured_at).between(year_start, year_end))
        .group_by('m')
        .all()
    )
    kpi_month_map = {int(r.m): round(float(r.v), 1) for r in kpi_trend_raw if r.v is not None}


    task_created_raw = {}
    task_done_raw = {}
    if active_task_ids:
        created = (
            db.query(
                func.extract('month', OperationalTask.created_at).label('m'),
                func.count(OperationalTask.task_id),
            )
            .filter(
                OperationalTask.task_id.in_(active_task_ids),
                func.date(OperationalTask.created_at).between(year_start, year_end),
            )
            .group_by('m').all()
        )
        task_created_raw = {int(r[0]): r[1] for r in created}


        done = (
            db.query(
                func.extract('month', OperationalTask.updated_at).label('m'),
                func.count(OperationalTask.task_id),
            )
            .filter(
                OperationalTask.task_id.in_(active_task_ids),
                OperationalTask.status_id == TASK_COMPLETED,
                func.date(OperationalTask.updated_at).between(year_start, year_end),
            )
            .group_by('m').all()
        )
        task_done_raw = {int(r[0]): r[1] for r in done}


    kpi_trend = [{"label": f"{cy}-{m:02d}", "value": kpi_month_map.get(m, 0)} for m in range(1, 13)]
    task_trend = [{"label": f"{cy}-{m:02d}", "created": task_created_raw.get(m, 0), "completed": task_done_raw.get(m, 0)} for m in range(1, 13)]


    depts = db.query(Department).filter(Department.is_active == True).all()
    dept_names_dd = {d.department_id: d.name for d in depts}


    mtd_all = []
    try:
        mtd_all = db.execute(text("""
            SELECT major_task_id, department_id, responsibility_type
            FROM major_task_departments
        """)).fetchall()
    except Exception:
        mtd_all = []


    major_ids_active_set = set(active_major_ids)
    dept_major_role = {}
    for mid, did, rt in mtd_all:
        if mid not in major_ids_active_set:
            continue
        rt_norm = (rt or "").strip().upper()
        dept_major_role.setdefault(did, {}).setdefault(mid, rt_norm)


    all_risk_ids = []
    if active_task_ids:
        risk_ids_rows = db.query(Risk.risk_id).filter(Risk.task_id.in_(active_task_ids)).all()
        all_risk_ids = [r[0] for r in risk_ids_rows]


    risk_by_task = {}
    if active_task_ids:
        risk_rows_full = (
            db.query(Risk, DictRiskLevel)
            .outerjoin(DictRiskLevel, DictRiskLevel.risk_level_id == Risk.risk_level_id)
            .filter(Risk.task_id.in_(active_task_ids))
            .all()
        )
        for r, lvl in risk_rows_full:
            score = (r.probability or 0) * (r.impact or 0)
            risk_by_task.setdefault(r.task_id, []).append({
                "risk_id": r.risk_id,
                "name": r.name,
                "score": score,
                "status_id": r.status_id,
                "level_color": lvl.color_hex if lvl else "#6c757d",
                "level_ar": lvl.name_ar if lvl else None,
            })


    mit_status_by_task = {}
    if all_risk_ids:
        mit_rows = (
            db.query(RiskMitigation, Risk)
            .join(Risk, Risk.risk_id == RiskMitigation.risk_id)
            .filter(RiskMitigation.risk_id.in_(all_risk_ids))
            .all()
        )
        for m, r in mit_rows:
            mit_status_by_task.setdefault(r.task_id, []).append(m.status_id)


    tasks_by_id_full = {t.task_id: t for t in active_tasks}
    majors_by_id_active = {mt.major_task_id: mt for mt in active_major_tasks}


    dept_full_stats = []
    for d in depts:
        role_map = dept_major_role.get(d.department_id, {})
        d_major_ids = list(role_map.keys())
        lead_count = sum(1 for rt in role_map.values() if rt == "LEAD")
        support_count = sum(1 for rt in role_map.values() if rt == "SUPPORT")
        co_lead_count = sum(1 for rt in role_map.values() if rt in ("CO-LEAD", "CO_LEAD"))


        d_majors_detail = []
        d_task_ids = []
        for mid in d_major_ids:
            mt = majors_by_id_active.get(mid)
            if not mt:
                continue
            tids = [t.task_id for t in tasks_by_major.get(mid, [])]
            d_task_ids.extend(tids)
            mt_prog = round(sum(progress_map.get(tid, 0) for tid in tids) / len(tids), 1) if tids else 0
            mt_completed = sum(1 for tid in tids if tasks_by_id_full.get(tid) and tasks_by_id_full[tid].status_id == TASK_COMPLETED)
            mt_delayed = sum(
                1 for tid in tids
                if tasks_by_id_full.get(tid)
                and tasks_by_id_full[tid].end_date
                and tasks_by_id_full[tid].end_date < today
                and tasks_by_id_full[tid].status_id != TASK_COMPLETED
            )
            init = next((x for x in active_inits if x.initiative_id == mt.initiative_id), None)
            d_majors_detail.append({
                "major_task_id": mid,
                "name": mt.name,
                "initiative_id": mt.initiative_id,
                "initiative_name": init.name if init else None,
                "responsibility_type": role_map.get(mid, ""),
                "responsibility_ar": "مسؤولة" if role_map.get(mid) == "LEAD" else ("مساندة" if role_map.get(mid) == "SUPPORT" else "مشاركة"),
                "task_count": len(tids),
                "completed": mt_completed,
                "delayed": mt_delayed,
                "progress": mt_prog,
            })


        unique_tids = list(set(d_task_ids))
        total_tasks = len(unique_tids)
        completed_tasks = sum(1 for tid in unique_tids if tasks_by_id_full.get(tid) and tasks_by_id_full[tid].status_id == TASK_COMPLETED)
        delayed_tasks = sum(
            1 for tid in unique_tids
            if tasks_by_id_full.get(tid)
            and tasks_by_id_full[tid].end_date
            and tasks_by_id_full[tid].end_date < today
            and tasks_by_id_full[tid].status_id != TASK_COMPLETED
        )
        in_progress_tasks = sum(1 for tid in unique_tids if tasks_by_id_full.get(tid) and tasks_by_id_full[tid].status_id == TASK_IN_PROGRESS)
        review_tasks = sum(1 for tid in unique_tids if tasks_by_id_full.get(tid) and tasks_by_id_full[tid].status_id == TASK_REVIEW)


        avg_progress = round(sum(progress_map.get(tid, 0) for tid in unique_tids) / len(unique_tids), 1) if unique_tids else 0
        completion_rate = round((completed_tasks / total_tasks) * 100, 1) if total_tasks else 0


        dept_risk_total = 0
        dept_risk_high = 0
        dept_risk_critical = 0
        for tid in unique_tids:
            for r in risk_by_task.get(tid, []):
                dept_risk_total += 1
                if r["score"] >= 80: dept_risk_critical += 1
                elif r["score"] >= 60: dept_risk_high += 1


        dept_mit_total = 0
        dept_mit_pending = 0
        dept_mit_active = 0
        dept_mit_awaiting = 0
        dept_mit_completed = 0
        for tid in unique_tids:
            for sid in mit_status_by_task.get(tid, []):
                dept_mit_total += 1
                if sid == 41: dept_mit_pending += 1
                elif sid in (42, 9): dept_mit_active += 1
                elif sid == 43: dept_mit_awaiting += 1
                elif sid in (44, 10): dept_mit_completed += 1


        lead_avg = round(
            sum(m["progress"] for m in d_majors_detail if m["responsibility_type"] == "LEAD") /
            max(1, sum(1 for m in d_majors_detail if m["responsibility_type"] == "LEAD")), 1
        ) if any(m["responsibility_type"] == "LEAD" for m in d_majors_detail) else 0


        alloc, spent = dept_budgets.get(d.department_id, (0.0, 0.0))
        spent_pct_d = round((spent / alloc) * 100, 1) if alloc else 0


        dept_full_stats.append({
            "department_id": d.department_id,
            "name": d.name,
            "major_tasks_count": len(d_major_ids),
            "major_lead_count": lead_count,
            "major_support_count": support_count,
            "major_co_lead_count": co_lead_count,
            "major_tasks_detail": d_majors_detail,
            "total_tasks": total_tasks,
            "completed": completed_tasks,
            "delayed": delayed_tasks,
            "in_progress": in_progress_tasks,
            "review": review_tasks,
            "avg_progress": avg_progress,
            "completion_rate": completion_rate,
            "lead_avg_progress": lead_avg,
            "risk_total": dept_risk_total,
            "risk_high": dept_risk_high,
            "risk_critical": dept_risk_critical,
            "mit_total": dept_mit_total,
            "mit_pending": dept_mit_pending,
            "mit_active": dept_mit_active,
            "mit_awaiting": dept_mit_awaiting,
            "mit_completed": dept_mit_completed,
            "allocated": round(alloc, 1),
            "spent": round(spent, 1),
            "spent_pct": spent_pct_d,
        })


    ranking = sorted(dept_full_stats, key=lambda x: (-x["major_tasks_count"], -x["completion_rate"]))


    employees = db.query(Employee).filter(Employee.is_active == True).all()


    all_tasks_for_emp = all_ops_map
    all_task_ids_for_emp = [t.task_id for t in all_tasks_for_emp]
    tasks_by_id_emp = {t.task_id: t for t in all_tasks_for_emp}


    assignments_emp = []
    if all_task_ids_for_emp:
        assignments_emp = (
            db.query(TaskAssignment)
            .filter(
                TaskAssignment.is_active == True,
                TaskAssignment.acceptance_status == "accepted",
                TaskAssignment.task_id.in_(all_task_ids_for_emp),
            )
            .all()
        )


    tasks_by_emp = {}
    for a in assignments_emp:
        tasks_by_emp.setdefault(a.employee_id, []).append(a.task_id)


    emp_full_stats = []
    for e in employees:
        t_ids = tasks_by_emp.get(e.employee_id, [])
        tasks = [tasks_by_id_emp[tid] for tid in t_ids if tid in tasks_by_id_emp]
        t_total = len(tasks)
        t_comp = sum(1 for t in tasks if t.status_id == TASK_COMPLETED)
        t_del = sum(1 for t in tasks if t.end_date and t.end_date < today and t.status_id != TASK_COMPLETED)
        t_inprog = sum(1 for t in tasks if t.status_id == TASK_IN_PROGRESS)
        t_review = sum(1 for t in tasks if t.status_id == TASK_REVIEW)
        comp_r = round((t_comp / t_total) * 100, 1) if t_total else 0
        avg_p = round(sum(all_progress_map.get(tid, 0) for tid in t_ids) / t_total, 1) if t_total else 0


        emp_full_stats.append({
            "employee_id": e.employee_id,
            "full_name": e.full_name,
            "job_title": e.job_title,
            "department_id": e.department_id,
            "department_name": dept_names_dd.get(e.department_id, "—"),
            "total_tasks": t_total,
            "completed": t_comp,
            "delayed": t_del,
            "in_progress": t_inprog,
            "review": t_review,
            "completion_rate": comp_r,
            "avg_progress": avg_p,
        })


    emp_full_stats.sort(key=lambda x: (-x["completion_rate"], -x["total_tasks"]))


    total_emp = len(emp_full_stats)
    avg_productivity = round(sum(x["completion_rate"] for x in emp_full_stats) / total_emp, 1) if total_emp else 0
    total_tasks_all = sum(x["total_tasks"] for x in emp_full_stats)
    total_tasks_comp = sum(x["completed"] for x in emp_full_stats)
    strategic_completion = round((total_tasks_comp / total_tasks_all) * 100, 1) if total_tasks_all else 0


    excellent = sum(1 for x in emp_full_stats if x["completion_rate"] >= 75)
    good = sum(1 for x in emp_full_stats if 40 <= x["completion_rate"] < 75)
    low = sum(1 for x in emp_full_stats if x["completion_rate"] < 40)


    top_performers = [x for x in emp_full_stats if x["total_tasks"] >= 2][:8]


    leader_ids = [d.manager_employee_id for d in depts if d.manager_employee_id]
    leaders = [x for x in emp_full_stats if x["employee_id"] in leader_ids]


    status_dist = [
        {"label": "مكتملة", "value": sum(1 for t in active_tasks if t.status_id == TASK_COMPLETED), "color": "#4caf50"},
        {"label": "قيد التنفيذ", "value": sum(1 for t in active_tasks if t.status_id == TASK_IN_PROGRESS), "color": "#3b82f6"},
        {"label": "مراجعة", "value": sum(1 for t in active_tasks if t.status_id == TASK_REVIEW), "color": "#8b5cf6"},
        {"label": "معلقة", "value": sum(1 for t in active_tasks if t.status_id == TASK_ON_HOLD), "color": "#f59e0b"},
        {"label": "جاهزة", "value": sum(1 for t in active_tasks if t.status_id == TASK_READY), "color": "#06b6d4"},
        {"label": "معلقة انتظار", "value": sum(1 for t in active_tasks if t.status_id == TASK_PENDING), "color": "#9ca3af"},
    ]


    insights = []
    high_pred = (
        db.query(func.count(AIPrediction.prediction_id))
        .filter(AIPrediction.prediction_type == "delay", AIPrediction.probability >= 0.9)
        .scalar() or 0
    )
    if high_pred:
        insights.append({"type": "warning", "text": f"{high_pred} مهمة باحتمالية تأخر مرتفعة جدًا."})
    if delayed_inits:
        insights.append({"type": "danger", "text": f"{delayed_inits} مبادرة استراتيجية متأخرة."})
    if red_c:
        insights.append({"type": "danger", "text": f"{red_c} مؤشر أداء حرج يحتاج تدخلًا."})
    if overall_index >= 80:
        insights.append({"type": "success", "text": f"الأداء العام مرتفع ({overall_index}%) — استمرار المتابعة."})


    return {
        "scope": {
            "current_goals_count": len(current_goals),
            "current_programs_count": len(current_programs),
            "active_initiatives_count": len(active_inits),
            "active_tasks_count": len(active_tasks),
        },
        "overview": {
            "overall_index": overall_index,
            "kpi_health": {"green": green_c, "yellow": yellow_c, "red": red_c, "total": len(kpi_rows)},
            "programs": {
                "total": len(active_inits), "completed": completed_inits,
                "active": len(active_inits), "delayed": delayed_inits,
                "completion_pct": completed_pct,
            },
            "financial": {
                "allocated": round(total_alloc, 1), "spent": round(total_spent, 1),
                "remaining": round(total_alloc - total_spent, 1), "spent_pct": spent_pct,
            },
        },
        "strategy_map": {"pillars": full_hierarchy},
        "ministerial_performance": {
            "impact_kpis": impact_kpis,
            "transformational_initiatives": transform_inits,
            "strategic_risks": strat_risks,
            "kpi_trend": kpi_trend,
            "task_trend": task_trend,
        },
        "departmental_performance": {
            "ranking": ranking,
        },
        "employee_performance": {
            "productivity": {
                "total_employees": total_emp,
                "avg_completion": avg_productivity,
                "total_tasks": total_tasks_all,
                "completed_tasks": total_tasks_comp,
                "strategic_completion": strategic_completion,
            },
            "distribution": {"excellent": excellent, "good": good, "low": low},
            "top_performers": top_performers,
            "leaders": leaders,
            "all_employees": emp_full_stats,
        },
        "task_status_distribution": status_dist,
        "ai_insights": insights,
    }



@router.get("/departments-performance")
def departments_performance(
    db: Session = Depends(get_db),
    current_user: int = Depends(get_current_user),
):
    out = []
    for d in db.query(Department).filter(Department.is_active == True).all():
        tasks = db.query(OperationalTask).filter(
            OperationalTask.department_id == d.department_id,
            OperationalTask.is_active == True,
        ).all()
        c = sum(1 for t in tasks if t.status_id == TASK_COMPLETED)
        out.append({
            "department_id": d.department_id,
            "department_name": d.name,
            "total_tasks": len(tasks),
            "completed": c,
            "completion_rate": round((c / len(tasks)) * 100, 1) if tasks else 0,
        })
    return out

from datetime import date, datetime
from calendar import monthrange
from typing import Optional


from fastapi import APIRouter, Depends, HTTPException
from pydantic import BaseModel
from sqlalchemy import func, and_, desc
from sqlalchemy.orm import Session, joinedload


from app.database import get_db
from app.core.dependencies import get_current_user
from app.models import (
    Employee, Department, OperationalTask, MajorTask, Initiative,
    Program, StrategicPillar, StrategicGoal,
    TaskAssignment, TaskProgressLog, Risk, RiskMitigation,
    AIPrediction, DictStatus, DictPriority, DictRoleType, DictRiskLevel,
    KPI, KPIMeasurement,
)


router = APIRouter(prefix="/api/dashboard", tags=["Dashboard"])


COMPLETED_TASK   = 19
IN_PROGRESS_TASK = 6
REVIEW_TASK      = 8
ON_HOLD_TASK     = 16
PENDING_TASK     = 5
READY_TASK       = 26
ACTIVE_INITIATIVE = 2




# ══════════════════════════════════════════════════════════════
# Helpers
# ══════════════════════════════════════════════════════════════
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




def _emp_scope_task_ids(db: Session, employee_id: int):
    rows = (
        db.query(TaskAssignment.task_id)
        .join(OperationalTask, OperationalTask.task_id == TaskAssignment.task_id)
        .join(MajorTask, MajorTask.major_task_id == OperationalTask.major_task_id)
        .join(Initiative, Initiative.initiative_id == MajorTask.initiative_id)
        .filter(
            TaskAssignment.employee_id == employee_id,
            TaskAssignment.is_active == True,
            OperationalTask.is_active == True,
            Initiative.is_active == True,
            Initiative.status_id == ACTIVE_INITIATIVE,
        )
        .all()
    )
    return [r[0] for r in rows]




# ══════════════════════════════════════════════════════════════
# EMPLOYEE DASHBOARD
# ══════════════════════════════════════════════════════════════
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
    priorities_d = {p.priority_id: p for p in db.query(DictPriority).all()}
    roles_d = {r.role_type_id: r for r in db.query(DictRoleType).all()}


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
            Initiative.status_id == ACTIVE_INITIATIVE,
        )
        .all()
    )


    accepted = [(a, t, m, i) for a, t, m, i in rows if a.acceptance_status == "accepted"]
    pending  = [(a, t, m, i) for a, t, m, i in rows if a.acceptance_status == "pending"]
    rejected = [(a, t, m, i) for a, t, m, i in rows if a.acceptance_status == "rejected"]


    task_ids = list({t.task_id for _, t, _, _ in accepted})
    progress_map = _latest_progress_map(db, task_ids)


    risks_count_map = {}
    mitigations_count_map = {}
    if task_ids:
        risks_count_map = dict(
            db.query(Risk.task_id, func.count(Risk.risk_id))
            .filter(Risk.task_id.in_(task_ids))
            .group_by(Risk.task_id).all()
        )
        mitigations_count_map = dict(
            db.query(RiskMitigation.task_id, func.count(RiskMitigation.mitigation_id))
            .filter(
                RiskMitigation.task_id.in_(task_ids),
                RiskMitigation.assigned_to == str(emp_id),
            )
            .group_by(RiskMitigation.task_id).all()
        )


    unique_tasks = {t.task_id: t for _, t, _, _ in accepted}


    total      = len(unique_tasks)
    completed  = sum(1 for t in unique_tasks.values() if t.status_id == COMPLETED_TASK)
    in_progress = sum(1 for t in unique_tasks.values() if t.status_id == IN_PROGRESS_TASK)
    on_hold    = sum(1 for t in unique_tasks.values() if t.status_id == ON_HOLD_TASK)
    review     = sum(1 for t in unique_tasks.values() if t.status_id == REVIEW_TASK)
    delayed    = sum(
        1 for t in unique_tasks.values()
        if t.end_date and t.end_date < today and t.status_id != COMPLETED_TASK
    )
    completion_rate = round((completed / total) * 100, 1) if total else 0


    estimated_hours = sum(float(t.estimated_hours or 0) for t in unique_tasks.values())
    actual_hours    = sum(float(t.actual_hours    or 0) for t in unique_tasks.values())


    my_mitigations_total = (
        db.query(func.count(RiskMitigation.mitigation_id))
        .filter(RiskMitigation.assigned_to == str(emp_id))
        .scalar() or 0
    )


    incomplete_tasks = []
    for a, t, m, i in accepted:
        if t.status_id == COMPLETED_TASK:
            continue
        st = statuses_d.get(t.status_id)
        rt = roles_d.get(a.role_type_id)
        incomplete_tasks.append({
            "task_id": t.task_id,
            "title": t.title,
            "my_role": rt.name_ar if rt else None,
            "initiative": i.name,
            "major_task": m.name,
            "estimated_hours": float(t.estimated_hours or 0),
            "status_ar": st.name_ar if st else None,
            "status_code": st.code if st else None,
            "end_date": str(t.end_date) if t.end_date else None,
            "risks_count": int(risks_count_map.get(t.task_id, 0)),
            "my_mitigations_count": int(mitigations_count_map.get(t.task_id, 0)),
            "is_delayed": bool(t.end_date and t.end_date < today),
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
            "delayed": delayed,
            "completion_rate": completion_rate,
            "estimated_hours": round(estimated_hours, 1),
            "actual_hours": round(actual_hours, 1),
            "hours_diff": round(actual_hours - estimated_hours, 1),
            "my_mitigations_total": my_mitigations_total,
            "pending_acceptance": len(pending),
            "rejected": len(rejected),
        },
        "incomplete_tasks": incomplete_tasks,
        "pending_assignments": pending_assignments,
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




# ══════════════════════════════════════════════════════════════
# MANAGER DASHBOARD
# ══════════════════════════════════════════════════════════════
@router.get("/manager")
def manager_dashboard(
    db: Session = Depends(get_db),
    current_user: int = Depends(get_current_user),
):
    from sqlalchemy import text


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


    statuses_d   = {s.status_id:   s for s in db.query(DictStatus).all()}
    priorities_d = {p.priority_id: p for p in db.query(DictPriority).all()}
    roles_d      = {r.role_type_id: r for r in db.query(DictRoleType).all()}


    # ═══════════════════════════════════════════════════════════
    # 1) المهام الرئيسية التي لهذه الإدارة دور فيها
    #    (من major_task_departments: LEAD / SUPPORT / Co-Lead)
    # ═══════════════════════════════════════════════════════════
    mtd_rows = db.execute(text("""
        SELECT major_task_id, responsibility_type
        FROM major_task_departments
        WHERE department_id = :dept_id
    """), {"dept_id": dept_id}).fetchall()


    mtd_role_map = {row[0]: (row[1] or "").strip().upper() for row in mtd_rows}
    my_major_task_ids = list(mtd_role_map.keys())


    # ═══════════════════════════════════════════════════════════
    # 2) المبادرات قيد التنفيذ
    # ═══════════════════════════════════════════════════════════
    active_inits = (
        db.query(Initiative)
        .filter(Initiative.is_active == True, Initiative.status_id == ACTIVE_INITIATIVE)
        .all()
    )
    active_init_ids = [i.initiative_id for i in active_inits]
    inits_map = {i.initiative_id: i for i in active_inits}


    # ═══════════════════════════════════════════════════════════
    # 3) المهام الرئيسية = (لهذه الإدارة دور) AND (مبادرتها قيد التنفيذ)
    # ═══════════════════════════════════════════════════════════
    major_tasks = []
    if my_major_task_ids and active_init_ids:
        major_tasks = (
            db.query(MajorTask)
            .filter(
                MajorTask.is_active == True,
                MajorTask.major_task_id.in_(my_major_task_ids),
                MajorTask.initiative_id.in_(active_init_ids),
            )
            .all()
        )
    major_map = {mt.major_task_id: mt for mt in major_tasks}
    major_ids = list(major_map.keys())


    # ═══════════════════════════════════════════════════════════
    # 4) المهام التشغيلية = التابعة لتلك المهام الرئيسية + قسم المدير
    # ═══════════════════════════════════════════════════════════
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


    # ═══════════════════════════════════════════════════════════
    # 5) الإسنادات
    # ═══════════════════════════════════════════════════════════
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


    unassigned_tasks = [t for t in op_tasks if not accepted_ids_per_task.get(t.task_id)]


    # ═══════════════════════════════════════════════════════════
    # 6) المهام الرئيسية في الرد
    # ═══════════════════════════════════════════════════════════
    def role_ar(rt):
        rt = (rt or "").upper()
        if rt == "LEAD":     return "مسؤولة"
        if rt == "SUPPORT":  return "مساندة"
        if rt in ("CO-LEAD", "CO_LEAD"): return "مسؤولة مشاركة"
        return rt or "—"


    major_out = []
    for mt in major_tasks:
        mt_ops = [t for t in op_tasks if t.major_task_id == mt.major_task_id]
        total_mt = len(mt_ops)
        comp_mt  = sum(1 for t in mt_ops if t.status_id == COMPLETED_TASK)
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


    # ═══════════════════════════════════════════════════════════
    # 7) المهام التشغيلية في الرد
    # ═══════════════════════════════════════════════════════════
    def build_op(t):
        mt   = major_map.get(t.major_task_id)
        init = inits_map.get(mt.initiative_id) if mt else None
        st   = statuses_d.get(t.status_id)
        pr   = priorities_d.get(t.priority_id)
        is_delayed = bool(t.end_date and t.end_date < today and t.status_id != COMPLETED_TASK)
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
    review_tasks   = [build_op(t) for t in op_tasks if t.status_id == REVIEW_TASK]
    unassigned_out = [build_op(t) for t in unassigned_tasks]


    # ═══════════════════════════════════════════════════════════
    # 8) KPIs
    # ═══════════════════════════════════════════════════════════
    total       = len(op_out)
    completed   = sum(1 for t in op_out if t["status_id"] == COMPLETED_TASK)
    in_progress = sum(1 for t in op_out if t["status_id"] == IN_PROGRESS_TASK)
    delayed     = sum(1 for t in op_out if t["is_delayed"])
    review      = len(review_tasks)
    unassigned  = len(unassigned_out)
    completion_rate = round((completed / total) * 100, 1) if total else 0
    avg_progress    = round(sum(progress_map.values()) / total, 1) if total else 0


    lead_count    = sum(1 for m in major_out if m["responsibility_type"] == "LEAD")
    support_count = sum(1 for m in major_out if m["responsibility_type"] == "SUPPORT")


    # ═══════════════════════════════════════════════════════════
    # 9) الموظفون
    # ═══════════════════════════════════════════════════════════
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
        e_completed = sum(1 for _, t in emp_asg if t.status_id == COMPLETED_TASK)
        e_delayed   = sum(1 for _, t in emp_asg
                          if t.end_date and t.end_date < today and t.status_id != COMPLETED_TASK)
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


    # ═══════════════════════════════════════════════════════════
    # 10) المخططات
    # ═══════════════════════════════════════════════════════════
    status_dist = [
        {"label": "مكتملة",       "value": completed,   "color": "#4caf50"},
        {"label": "قيد التنفيذ",   "value": in_progress, "color": "#3b82f6"},
        {"label": "قيد المراجعة",  "value": review,      "color": "#8b5cf6"},
        {"label": "متأخرة",       "value": delayed,     "color": "#ef4444"},
        {"label": "غير موزعة",     "value": unassigned,  "color": "#9ca3af"},
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

# ══════════════════════════════════════════════════════════════
# EMPLOYEE DETAIL (from manager view)
# ══════════════════════════════════════════════════════════════
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
    statuses_d   = {s.status_id:   s for s in db.query(DictStatus).all()}
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
    completed    = sum(1 for _, t, _, _ in accepted if t.status_id == COMPLETED_TASK)
    in_progress  = sum(1 for _, t, _, _ in accepted if t.status_id == IN_PROGRESS_TASK)
    delayed      = sum(1 for _, t, _, _ in accepted
                       if t.end_date and t.end_date < today and t.status_id != COMPLETED_TASK)


    tasks_out = []
    for a, t, m, i in accepted:
        st = statuses_d.get(t.status_id)
        pr = priorities_d.get(t.priority_id)
        rt = roles_d.get(a.role_type_id)
        is_delayed = bool(t.end_date and t.end_date < today and t.status_id != COMPLETED_TASK)
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




# ══════════════════════════════════════════════════════════════
# MINISTER DASHBOARD
# ══════════════════════════════════════════════════════════════
@router.get("/minister")
def minister_dashboard(
    db: Session = Depends(get_db),
    current_user: int = Depends(get_current_user),
):
    statuses_d = {s.status_id: s for s in db.query(DictStatus).all()}


    pillars_count = (
        db.query(func.count(StrategicPillar.pillar_id))
        .filter(StrategicPillar.is_active == True).scalar() or 0
    )
    goals_count = (
        db.query(func.count(StrategicGoal.goal_id))
        .filter(StrategicGoal.is_active == True).scalar() or 0
    )
    programs_count = (
        db.query(func.count(Program.program_id))
        .filter(Program.is_active == True).scalar() or 0
    )
    initiatives_count = (
        db.query(func.count(Initiative.initiative_id))
        .filter(Initiative.is_active == True).scalar() or 0
    )


    all_tasks = db.query(OperationalTask).filter(OperationalTask.is_active == True).all()
    ids = [t.task_id for t in all_tasks]
    pm  = _latest_progress_map(db, ids)
    strategic_achievement = round(sum(pm.values()) / len(ids), 1) if ids else 0


    delayed_inits = (
        db.query(func.count(Initiative.initiative_id))
        .filter(
            Initiative.is_active == True,
            Initiative.end_date < date.today(),
            Initiative.status_id != 3,
        ).scalar() or 0
    )


    critical_risks = (
        db.query(func.count(Risk.risk_id))
        .filter(Risk.probability * Risk.impact >= 80).scalar() or 0
    )


    kpi_rows = []
    for k in db.query(KPI).filter(KPI.is_active == True).all():
        latest = (
            db.query(KPIMeasurement)
            .filter(KPIMeasurement.kpi_id == k.kpi_id)
            .order_by(desc(KPIMeasurement.measured_at)).first()
        )
        prev = (
            db.query(KPIMeasurement)
            .filter(KPIMeasurement.kpi_id == k.kpi_id)
            .order_by(desc(KPIMeasurement.measured_at)).offset(1).first()
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


    depts = []
    for d in db.query(Department).filter(Department.is_active == True).all():
        d_tasks = db.query(OperationalTask).filter(
            OperationalTask.department_id == d.department_id,
            OperationalTask.is_active == True,
        ).all()
        d_ids = [t.task_id for t in d_tasks]
        d_pm  = _latest_progress_map(db, d_ids)
        c  = sum(1 for t in d_tasks if t.status_id == COMPLETED_TASK)
        dl = sum(1 for t in d_tasks if t.end_date and t.end_date < date.today()
                 and t.status_id != COMPLETED_TASK)
        depts.append({
            "department_id": d.department_id, "name": d.name,
            "total_tasks": len(d_tasks), "completed": c, "delayed": dl,
            "completion_rate": round((c / len(d_tasks)) * 100, 1) if d_tasks else 0,
            "avg_progress": round(sum(d_pm.values()) / len(d_ids), 1) if d_ids else 0,
        })


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
            "level_code": lvl.code if lvl else _risk_level_code(score),
        })


    insights = []
    high_pred = (
        db.query(func.count(AIPrediction.prediction_id))
        .filter(
            AIPrediction.prediction_type == "delay",
            AIPrediction.probability >= 0.9,
        ).scalar() or 0
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
            "text": f"يوجد {critical_risks} خطر حرج يستوجب تدخلًا فوريًا.",
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
        "kpis": kpi_rows,
        "departments": depts,
        "strategic_risks": strategic_risks,
        "ai_insights": insights,
    }




# ══════════════════════════════════════════════════════════════
# DEPARTMENTS PERFORMANCE
# ══════════════════════════════════════════════════════════════
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
        c = sum(1 for t in tasks if t.status_id == COMPLETED_TASK)
        out.append({
            "department_id": d.department_id,
            "department_name": d.name,
            "total_tasks": len(tasks),
            "completed": c,
            "completion_rate": round((c / len(tasks)) * 100, 1) if tasks else 0,
        })
    return out

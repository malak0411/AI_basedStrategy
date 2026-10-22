import pandas as pd
import numpy as np
from datetime import datetime, date, timedelta
from sqlalchemy import func
from app.database import SessionLocal
from app.models import (
    OperationalTask, TaskProgressLog, TaskAssignment,
    Employee, Department, BudgetLine, Risk, RiskMitigation,
    TaskComment, DictStatus, DictRiskLevel, TaskDependency,
    MajorTask, Initiative, DictPriority,
)
from app.ai.config.constants import (
    TASK_STATUS_COMPLETED, TASK_STATUS_IN_PROGRESS, TASK_STATUS_READY,
    TASK_STATUS_PENDING, TASK_STATUS_SUSPENDED_NO_STAFF, TASK_STATUS_REVIEW,
    ACTIVE_TASK_STATUSES, TERMINAL_TASK_STATUSES,
    INITIATIVE_STATUS_IN_PROGRESS, TRAINABLE_INITIATIVE_STATUSES,
    PRIORITY_HIGH, PRIORITY_MEDIUM,
    CLOSED_MITIGATION_STATUS_IDS, CLOSED_RISK_STATUS_IDS,
    HEAVY_ACTIVE_TASKS_THRESHOLD, HEAVY_ESTIMATED_HOURS_THRESHOLD,
    STALE_UPDATE_DAYS, HISTORY_WINDOW_DAYS,
    DELAY_CATEGORY_ON_TIME, DELAY_CATEGORY_SLIGHT,
    DELAY_CATEGORY_MODERATE, DELAY_CATEGORY_SEVERE,
)


class DataLoader:

    def __init__(self, db=None):
        self.db = db or SessionLocal()
        self._owns_db = db is None
        self._critical_level_ids = None
        self._dept_history = None
        self._priority_map = None

    @property
    def critical_level_ids(self):
        if self._critical_level_ids is None:
            rows = self.db.query(DictRiskLevel.risk_level_id).filter(
                DictRiskLevel.code.in_(['critical', 'high'])
            ).all()
            self._critical_level_ids = [r[0] for r in rows]
        return self._critical_level_ids

    @property
    def priority_level_map(self):
        if self._priority_map is None:
            rows = self.db.query(DictPriority.priority_id, DictPriority.level).all()
            self._priority_map = {pid: lvl for pid, lvl in rows}
        return self._priority_map

    def load_tasks_for_training(self):
        initiative_ids = [r[0] for r in self.db.query(Initiative.initiative_id).filter(
            Initiative.status_id.in_(TRAINABLE_INITIATIVE_STATUSES)
        ).all()]
        if not initiative_ids:
            return []
        major_ids = [r[0] for r in self.db.query(MajorTask.major_task_id).filter(
            MajorTask.initiative_id.in_(initiative_ids)
        ).all()]
        if not major_ids:
            return []
        return self.db.query(OperationalTask).filter(
            OperationalTask.major_task_id.in_(major_ids)
        ).all()

    def load_tasks_for_prediction(self):
        initiative_ids = [r[0] for r in self.db.query(Initiative.initiative_id).filter(
            Initiative.status_id.in_([2, 17])
        ).all()]
        if not initiative_ids:
            return []
        major_ids = [r[0] for r in self.db.query(MajorTask.major_task_id).filter(
            MajorTask.initiative_id.in_(initiative_ids)
        ).all()]
        if not major_ids:
            return []
        return self.db.query(OperationalTask).filter(
            OperationalTask.major_task_id.in_(major_ids),
            OperationalTask.is_active == True,
        ).all()

    def load_all_tasks(self):
        return self.db.query(OperationalTask).all()

    def extract_features(self, tasks):
        features_list = []
        self._dept_history = self._build_department_history()
        for task in tasks:
            try:
                features_list.append(self._extract_single_task(task))
            except Exception as e:
                print(f"skip task {task.task_id}: {e}")
        if not features_list:
            return pd.DataFrame()
        df = pd.DataFrame(features_list)
        numeric = df.select_dtypes(include=[np.number]).columns
        df[numeric] = df[numeric].fillna(0)
        df = df.replace([np.inf, -np.inf], 0)
        if 'is_delayed' in df.columns:
            df['is_delayed'] = df['is_delayed'].fillna(0).astype(int)
        return df

    def _build_department_history(self):
        cutoff = date.today() - timedelta(days=HISTORY_WINDOW_DAYS)
        rows = self.db.query(OperationalTask).filter(
            OperationalTask.status_id == TASK_STATUS_COMPLETED,
            OperationalTask.end_date >= cutoff,
        ).all()
        history = {}
        for t in rows:
            dept = t.department_id or 0
            if dept not in history:
                history[dept] = {'total': 0, 'on_time': 0, 'delayed': 0, 'total_delay_days': 0}
            history[dept]['total'] += 1
            delay = self._compute_completion_delay(t)
            if delay <= 0:
                history[dept]['on_time'] += 1
            else:
                history[dept]['delayed'] += 1
                history[dept]['total_delay_days'] += delay
        for d, h in history.items():
            h['on_time_rate'] = round(h['on_time'] / h['total'], 3) if h['total'] else 0.0
            h['delay_rate'] = round(h['delayed'] / h['total'], 3) if h['total'] else 0.0
            h['avg_delay_days'] = round(h['total_delay_days'] / h['delayed'], 2) if h['delayed'] else 0.0
        return history

    def _compute_completion_delay(self, task):
        if task.status_id != TASK_STATUS_COMPLETED or not task.end_date:
            return 0
        log = self.db.query(TaskProgressLog).filter(
            TaskProgressLog.task_id == task.task_id,
            TaskProgressLog.status_new == TASK_STATUS_COMPLETED,
        ).order_by(TaskProgressLog.log_time.desc()).first()
        if log and log.log_time:
            completion = log.log_time.date()
            if completion > task.end_date:
                return (completion - task.end_date).days
        return 0

    def _extract_single_task(self, task):
        today = date.today()
        tid = task.task_id
        start_date = task.start_date
        end_date = task.end_date
        status_id = task.status_id or 0

        planned_duration = max((end_date - start_date).days, 1) if (start_date and end_date) else 0
        elapsed_days = max((today - start_date).days, 0) if start_date else 0
        remaining_days = (end_date - today).days if end_date else 0
        days_overdue = 0
        if end_date and end_date < today and status_id != TASK_STATUS_COMPLETED:
            days_overdue = (today - end_date).days

        elapsed_ratio = min(elapsed_days / planned_duration, 3.0) if planned_duration > 0 else 0.0
        time_remaining_ratio = round(remaining_days / planned_duration, 3) if planned_duration > 0 else 0.0
        is_past_deadline = 1 if end_date and today > end_date and status_id != TASK_STATUS_COMPLETED else 0

        is_completed = 1 if status_id == TASK_STATUS_COMPLETED else 0
        is_in_progress = 1 if status_id == TASK_STATUS_IN_PROGRESS else 0
        is_ready = 1 if status_id == TASK_STATUS_READY else 0
        is_pending_status = 1 if status_id == TASK_STATUS_PENDING else 0
        is_review = 1 if status_id == TASK_STATUS_REVIEW else 0

        estimated_hours = float(task.estimated_hours or 0)
        actual_hours = float(task.actual_hours or 0)
        hours_ratio = min(actual_hours / estimated_hours, 3.0) if estimated_hours > 0 else 0.0

        progress_logs = self.db.query(TaskProgressLog).filter(
            TaskProgressLog.task_id == tid
        ).order_by(TaskProgressLog.log_time.asc()).all()

        num_updates = len(progress_logs)
        completion_pct = 0.0
        days_without_update = max(elapsed_days, 30)
        avg_progress_rate = 0.0
        progress_7d = 0.0
        progress_30d = 0.0

        if progress_logs:
            last = progress_logs[-1]
            completion_pct = float(last.progress_percent or 0)
            if last.log_time:
                days_without_update = max((datetime.now() - last.log_time).days, 0)
            if len(progress_logs) >= 2:
                first = progress_logs[0]
                if first.log_time and last.log_time:
                    span = max((last.log_time - first.log_time).days, 1)
                    avg_progress_rate = ((last.progress_percent or 0) - (first.progress_percent or 0)) / span
            progress_7d = self._calc_progress_window(progress_logs, 7)
            progress_30d = self._calc_progress_window(progress_logs, 30)

        expected_progress = min((elapsed_days / planned_duration) * 100, 100) if planned_duration > 0 else 0.0
        progress_gap = completion_pct - expected_progress

        assignments = self.db.query(TaskAssignment).filter(
            TaskAssignment.task_id == tid,
            TaskAssignment.is_active == True,
        ).all()
        num_assigned = len(assignments)
        employee_ids = [a.employee_id for a in assignments]

        dept_active = self.db.query(func.count(OperationalTask.task_id)).filter(
            OperationalTask.department_id == task.department_id,
            OperationalTask.status_id.in_(ACTIVE_TASK_STATUSES),
        ).scalar() or 0
        dept_emps = self.db.query(func.count(Employee.employee_id)).filter(
            Employee.department_id == task.department_id
        ).scalar() or 1
        dept_workload = round(dept_active / dept_emps, 3)

        budget_lines = self.db.query(BudgetLine).filter(
            BudgetLine.budgetable_id == tid,
            BudgetLine.budgetable_type == 'operational_task',
        ).all()
        budget_allocated = sum(float(bl.allocated_amount or 0) for bl in budget_lines)
        budget_spent = sum(float(bl.spent_amount or 0) for bl in budget_lines)
        budget_remaining = budget_allocated - budget_spent
        budget_ratio = min(budget_spent / budget_allocated, 2.0) if budget_allocated > 0 else 0.0

        risks = self.db.query(Risk).filter(Risk.task_id == tid).all()
        risk_count = len(risks)
        critical_risks = sum(1 for r in risks if r.risk_level_id in self.critical_level_ids) if self.critical_level_ids else 0
        active_risks = [r for r in risks if r.status_id not in CLOSED_RISK_STATUS_IDS]
        max_risk_score = max((r.risk_score or 0) for r in risks) if risks else 0
        avg_risk_score = round(sum((r.risk_score or 0) for r in risks) / len(risks), 2) if risks else 0.0
        unmitigated_risks = 0
        for r in active_risks:
            mit_count = self.db.query(func.count(RiskMitigation.mitigation_id)).filter(
                RiskMitigation.risk_id == r.risk_id,
                RiskMitigation.status_id.notin_(CLOSED_MITIGATION_STATUS_IDS),
            ).scalar() or 0
            if mit_count == 0:
                unmitigated_risks += 1

        mitigations = self.db.query(RiskMitigation).join(
            Risk, Risk.risk_id == RiskMitigation.risk_id
        ).filter(Risk.task_id == tid).all()

        mitigation_count = len(mitigations)
        completed_mits = sum(1 for m in mitigations if m.status_id in CLOSED_MITIGATION_STATUS_IDS)
        overdue_mits = sum(
            1 for m in mitigations
            if m.due_date and m.due_date < today and m.status_id not in CLOSED_MITIGATION_STATUS_IDS
        )
        mitigation_completion_rate = round(completed_mits / mitigation_count, 3) if mitigation_count else 0.0
        overdue_mitigation_rate = round(overdue_mits / mitigation_count, 3) if mitigation_count else 0.0

        comment_count = self.db.query(func.count(TaskComment.comment_id)).filter(
            TaskComment.task_id == tid
        ).scalar() or 0

        dep_stats = self._analyze_dependencies(tid, status_id)
        emp_stats = self._analyze_employee_workload(employee_ids)
        initiative_ctx = self._analyze_initiative_context(task)
        pending_reasons = self._classify_pending_reason(
            status_id, num_assigned, dep_stats['num_blocking_deps'], start_date
        )
        dept_hist = self._dept_history.get(task.department_id or 0, {}) if self._dept_history else {}

        is_delayed, delay_days, delay_category = self._classify_delay(task, status_id, end_date, days_overdue)

        return {
            'task_id': tid,
            'major_task_id': task.major_task_id or 0,
            'department_id': task.department_id or 0,
            'priority_id': task.priority_id or PRIORITY_MEDIUM,
            'status_id': status_id,
            'end_date': end_date.isoformat() if end_date else None,
            'start_date': start_date.isoformat() if start_date else None,
            'is_cross_functional': 1 if task.is_cross_functional else 0,

            'planned_duration_days': planned_duration,
            'elapsed_days': elapsed_days,
            'remaining_days': remaining_days,
            'days_overdue': days_overdue,
            'is_past_deadline': is_past_deadline,
            'time_remaining_ratio': time_remaining_ratio,
            'elapsed_ratio': round(elapsed_ratio, 3),

            'estimated_hours': estimated_hours,
            'actual_hours': actual_hours,
            'hours_ratio': round(hours_ratio, 3),

            'completion_percentage': completion_pct,
            'expected_progress': round(expected_progress, 3),
            'progress_gap': round(progress_gap, 3),
            'num_updates': num_updates,
            'days_without_update': days_without_update,
            'avg_progress_rate': round(avg_progress_rate, 3),
            'progress_7_days': round(progress_7d, 3),
            'progress_30_days': round(progress_30d, 3),

            'num_assigned_employees': num_assigned,
            'department_workload': dept_workload,

            'budget_allocated': budget_allocated,
            'budget_spent': budget_spent,
            'budget_remaining': budget_remaining,
            'budget_ratio': round(budget_ratio, 3),

            'risk_count': risk_count,
            'critical_risk_count': critical_risks,
            'active_risk_count': len(active_risks),
            'unmitigated_risk_count': unmitigated_risks,
            'max_risk_score': max_risk_score,
            'avg_risk_score': avg_risk_score,

            'mitigation_count': mitigation_count,
            'completed_mitigation_count': completed_mits,
            'overdue_mitigation_count': overdue_mits,
            'mitigation_completion_rate': mitigation_completion_rate,
            'overdue_mitigation_rate': overdue_mitigation_rate,

            'comment_count': comment_count,

            'is_completed': is_completed,
            'is_in_progress': is_in_progress,
            'is_ready': is_ready,
            'is_pending_status': is_pending_status,
            'is_review': is_review,

            'has_dependencies': dep_stats['has_dependencies'],
            'num_dependencies': dep_stats['num_dependencies'],
            'num_blocking_deps': dep_stats['num_blocking_deps'],
            'num_satisfied_deps': dep_stats['num_satisfied_deps'],
            'fs_count': dep_stats['fs_count'],
            'ss_count': dep_stats['ss_count'],
            'ff_count': dep_stats['ff_count'],
            'sf_count': dep_stats['sf_count'],
            'max_dep_lag_days': dep_stats['max_dep_lag_days'],

            'avg_employee_active_tasks': emp_stats['avg_employee_active_tasks'],
            'max_employee_active_tasks': emp_stats['max_employee_active_tasks'],
            'avg_employee_overdue_tasks': emp_stats['avg_employee_overdue_tasks'],
            'max_employee_overdue_tasks': emp_stats['max_employee_overdue_tasks'],
            'avg_employee_estimated_hours': emp_stats['avg_employee_estimated_hours'],
            'max_employee_estimated_hours': emp_stats['max_employee_estimated_hours'],
            'understaffed': emp_stats['understaffed'],
            'overloaded': emp_stats['overloaded'],

            'initiative_is_active': initiative_ctx['initiative_is_active'],
            'initiative_progress_pct': initiative_ctx['initiative_progress_pct'],
            'initiative_days_remaining': initiative_ctx['initiative_days_remaining'],
            'sibling_tasks_count': initiative_ctx['sibling_tasks_count'],
            'sibling_delayed_pct': initiative_ctx['sibling_delayed_pct'],

            'is_pending_no_staff': pending_reasons['is_pending_no_staff'],
            'is_pending_dep_blocked': pending_reasons['is_pending_dep_blocked'],
            'is_pending_manager': pending_reasons['is_pending_manager'],
            'is_ready_awaiting': pending_reasons['is_ready_awaiting'],

            'dept_on_time_rate': dept_hist.get('on_time_rate', 0.0),
            'dept_delay_rate': dept_hist.get('delay_rate', 0.0),
            'dept_avg_delay_days': dept_hist.get('avg_delay_days', 0.0),

            'is_delayed': is_delayed,
            'delay_days': delay_days,
            'delay_category': delay_category,
        }

    def _calc_progress_window(self, logs, days):
        if not logs or len(logs) < 2:
            return 0.0
        cutoff = datetime.now().timestamp() - (days * 86400)
        recent = [l for l in logs if l.log_time and l.log_time.timestamp() > cutoff]
        if len(recent) >= 2:
            return round((recent[-1].progress_percent or 0) - (recent[0].progress_percent or 0), 3)
        return 0.0

    def _analyze_dependencies(self, task_id, status_id):
        result = {
            'has_dependencies': 0,
            'num_dependencies': 0,
            'num_blocking_deps': 0,
            'num_satisfied_deps': 0,
            'fs_count': 0, 'ss_count': 0, 'ff_count': 0, 'sf_count': 0,
            'max_dep_lag_days': 0,
        }
        deps = self.db.query(TaskDependency).filter(
            TaskDependency.task_id == task_id
        ).all()
        if not deps:
            return result
        result['has_dependencies'] = 1
        result['num_dependencies'] = len(deps)
        today = date.today()
        for dep in deps:
            pred = self.db.query(OperationalTask).filter(
                OperationalTask.task_id == dep.depends_on_task_id
            ).first()
            if not pred:
                continue
            dep_type = dep.dependency_type or 'FS'
            lag = dep.lag_days or 0
            result['max_dep_lag_days'] = max(result['max_dep_lag_days'], lag)
            pred_completed = pred.status_id == TASK_STATUS_COMPLETED
            pred_started = pred.start_date and pred.start_date <= today
            satisfied = False
            if dep_type == 'FS':
                result['fs_count'] += 1
                satisfied = pred_completed
            elif dep_type == 'SS':
                result['ss_count'] += 1
                satisfied = pred_started or pred_completed
            elif dep_type == 'FF':
                result['ff_count'] += 1
                satisfied = pred_completed
            elif dep_type == 'SF':
                result['sf_count'] += 1
                satisfied = pred_started or pred_completed
            if satisfied:
                result['num_satisfied_deps'] += 1
            else:
                result['num_blocking_deps'] += 1
        return result

    def _analyze_employee_workload(self, employee_ids):
        empty = {
            'avg_employee_active_tasks': 0.0,
            'max_employee_active_tasks': 0,
            'avg_employee_overdue_tasks': 0.0,
            'max_employee_overdue_tasks': 0,
            'avg_employee_estimated_hours': 0.0,
            'max_employee_estimated_hours': 0.0,
            'understaffed': 0,
            'overloaded': 0,
        }
        if not employee_ids:
            return empty

        today = date.today()
        per_emp = []
        for emp_id in employee_ids:
            assigns = self.db.query(TaskAssignment).filter(
                TaskAssignment.employee_id == emp_id,
                TaskAssignment.is_active == True,
            ).all()
            emp_task_ids = [a.task_id for a in assigns]
            if not emp_task_ids:
                per_emp.append({'active': 0, 'overdue': 0, 'hours': 0.0})
                continue
            active = self.db.query(OperationalTask).filter(
                OperationalTask.task_id.in_(emp_task_ids),
                OperationalTask.status_id.in_(ACTIVE_TASK_STATUSES),
            ).all()
            overdue = sum(1 for t in active if t.end_date and t.end_date < today)
            hours = sum(float(t.estimated_hours or 0) for t in active)
            per_emp.append({'active': len(active), 'overdue': overdue, 'hours': hours})

        if not per_emp:
            return empty
        return {
            'avg_employee_active_tasks': round(sum(p['active'] for p in per_emp) / len(per_emp), 2),
            'max_employee_active_tasks': max(p['active'] for p in per_emp),
            'avg_employee_overdue_tasks': round(sum(p['overdue'] for p in per_emp) / len(per_emp), 2),
            'max_employee_overdue_tasks': max(p['overdue'] for p in per_emp),
            'avg_employee_estimated_hours': round(sum(p['hours'] for p in per_emp) / len(per_emp), 1),
            'max_employee_estimated_hours': round(max(p['hours'] for p in per_emp), 1),
            'understaffed': 1 if len(employee_ids) < 2 and max(p['active'] for p in per_emp) > 3 else 0,
            'overloaded': 1 if (max(p['active'] for p in per_emp) > HEAVY_ACTIVE_TASKS_THRESHOLD
                                or max(p['hours'] for p in per_emp) > HEAVY_ESTIMATED_HOURS_THRESHOLD) else 0,
        }

    def _analyze_initiative_context(self, task):
        default = {
            'initiative_is_active': 0,
            'initiative_progress_pct': 0.0,
            'initiative_days_remaining': 0,
            'sibling_tasks_count': 0,
            'sibling_delayed_pct': 0.0,
        }
        if not task.major_task_id:
            return default
        major = self.db.query(MajorTask).filter(
            MajorTask.major_task_id == task.major_task_id
        ).first()
        if not major or not major.initiative_id:
            return default
        init = self.db.query(Initiative).filter(
            Initiative.initiative_id == major.initiative_id
        ).first()
        if not init:
            return default
        today = date.today()
        days_remaining = (init.end_date - today).days if init.end_date else 0
        siblings = self.db.query(OperationalTask).filter(
            OperationalTask.major_task_id == task.major_task_id
        ).all()
        sibling_count = len(siblings)
        delayed = sum(
            1 for s in siblings
            if s.status_id == TASK_STATUS_COMPLETED and s.end_date
            and self._compute_completion_delay(s) > 0
        )
        return {
            'initiative_is_active': 1 if init.status_id == INITIATIVE_STATUS_IN_PROGRESS else 0,
            'initiative_progress_pct': 0.0,
            'initiative_days_remaining': days_remaining,
            'sibling_tasks_count': sibling_count,
            'sibling_delayed_pct': round(delayed / sibling_count, 3) if sibling_count else 0.0,
        }

    def _classify_pending_reason(self, status_id, num_assigned, blocking_deps, start_date):
        today = date.today()
        return {
            'is_pending_no_staff': 1 if status_id == TASK_STATUS_SUSPENDED_NO_STAFF and num_assigned == 0 else 0,
            'is_pending_dep_blocked': 1 if blocking_deps > 0 else 0,
            'is_pending_manager': 1 if status_id == TASK_STATUS_PENDING and num_assigned > 0 and blocking_deps == 0 else 0,
            'is_ready_awaiting': 1 if status_id == TASK_STATUS_READY and start_date and start_date <= today else 0,
        }

    def _classify_delay(self, task, status_id, end_date, days_overdue):
        if status_id == TASK_STATUS_COMPLETED:
            delay = self._compute_completion_delay(task)
            if delay <= 0:
                return 0, 0, DELAY_CATEGORY_ON_TIME
            if delay <= 7:
                return 1, delay, DELAY_CATEGORY_SLIGHT
            if delay <= 30:
                return 1, delay, DELAY_CATEGORY_MODERATE
            return 1, delay, DELAY_CATEGORY_SEVERE
        if days_overdue > 0:
            if days_overdue <= 7:
                return 1, days_overdue, DELAY_CATEGORY_SLIGHT
            if days_overdue <= 30:
                return 1, days_overdue, DELAY_CATEGORY_MODERATE
            return 1, days_overdue, DELAY_CATEGORY_SEVERE
        return 0, 0, DELAY_CATEGORY_ON_TIME

    def get_task_features(self, task_id):
        task = self.db.query(OperationalTask).filter(
            OperationalTask.task_id == task_id
        ).first()
        if not task:
            return None
        self._dept_history = self._build_department_history()
        return self._extract_single_task(task)

    def close(self):
        if self._owns_db and self.db:
            self.db.close()

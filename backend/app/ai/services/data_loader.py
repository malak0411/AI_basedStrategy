
import pandas as pd
import numpy as np
from datetime import datetime, date
from sqlalchemy import func
from app.database import SessionLocal
from app.models import (
    OperationalTask, TaskProgressLog, TaskAssignment,
    Employee, Department, BudgetLine, Risk,
    TaskComment, DictStatus
)

class DataLoader:
    
    def __init__(self):
        self.db = SessionLocal()
        self.feature_count = 0
        self._delayed_id = None
    
    @property
    def delayed_status_id(self):

        if self._delayed_id is None:
            delayed = self.db.query(DictStatus).filter(
                DictStatus.name_ar.like('%متأخر%'),
                DictStatus.category == 'task'
            ).first()
            self._delayed_id = delayed.status_id if delayed else 7
            print(f" تم اكتشاف status_id للمتأخر: {self._delayed_id}")
        return self._delayed_id
    
    def load_all_tasks(self):
        print("جلب المهام من قاعدة البيانات...")
        tasks = self.db.query(OperationalTask).all()
        
        delayed = sum(1 for t in tasks if t.status_id == self.delayed_status_id)
        
        print(f" تم جلب {len(tasks)} مهمة (متأخر: {delayed})")
        return tasks
    
    def extract_features(self, tasks):
        print(f"\n بدء استخراج الميزات  {len(tasks)} مهمة...")
        
        features_list = []
        total = len(tasks)
        
        for i, task in enumerate(tasks):
            if (i + 1) % 20 == 0 or i == 0:
                print(f"   معالجة {i + 1}/{total}...")
            
            try:
                features = self._extract_single_task(task)
                features_list.append(features)
            except Exception as e:
                print(f"   خطأ في المهمة #{task.task_id}: {str(e)}")
                continue
        
        df = pd.DataFrame(features_list)
        numeric_cols = df.select_dtypes(include=[np.number]).columns
        df[numeric_cols] = df[numeric_cols].fillna(0)
        df = df.replace([np.inf, -np.inf], 0)
        
        if 'is_delayed' in df.columns:
            df['is_delayed'] = df['is_delayed'].fillna(0).astype(int)
        
        exclude = ['task_id', 'is_delayed']
        self.feature_count = len([c for c in df.columns if c not in exclude])
        
        d1 = (df['is_delayed'] == 1).sum()
        d0 = (df['is_delayed'] == 0).sum()
        print(f"\n📋 إجمالي: {len(df)} | متأخر: {d1} | غير متأخر: {d0} | ميزات: {self.feature_count}")
        
        return df
    
    def _extract_single_task(self, task):

        today = date.today()
        tid = task.task_id
        

        start_date = task.start_date
        end_date = task.end_date
        
        planned_duration = 0
        elapsed_days = 0
        remaining_days = 0
        
        if start_date and end_date:
            planned_duration = max((end_date - start_date).days, 1)
        if start_date:
            elapsed_days = max((today - start_date).days, 0)
        if end_date:
            remaining_days = (end_date - today).days
        
        elapsed_ratio = min(elapsed_days / planned_duration, 3.0) if planned_duration > 0 else 0.0
        is_overdue = 1 if remaining_days < 0 else 0
        

        estimated_hours = float(task.estimated_hours or 0)
        actual_hours = float(task.actual_hours or 0)
        hours_ratio = min(actual_hours / estimated_hours, 3.0) if estimated_hours > 0 else 0.0
        

        progress_logs = self.db.query(TaskProgressLog).filter(
            TaskProgressLog.task_id == tid
        ).order_by(TaskProgressLog.log_time.asc()).all()
        
        num_updates = len(progress_logs)
        completion_pct = 0
        days_without_update = max(elapsed_days, 30)
        avg_progress_rate = 0.0
        progress_7_days = 0.0
        progress_30_days = 0.0
        
        if progress_logs:
            last_log = progress_logs[-1]
            completion_pct = last_log.progress_percent or 0
            if last_log.log_time:
                days_without_update = max((datetime.now() - last_log.log_time).days, 0)
            if len(progress_logs) >= 2:
                first_log = progress_logs[0]
                if first_log.log_time and last_log.log_time:
                    dbetween = max((last_log.log_time - first_log.log_time).days, 1)
                    avg_progress_rate = ((last_log.progress_percent or 0) - (first_log.progress_percent or 0)) / dbetween
            progress_7_days = self._calc_progress(progress_logs, 7)
            progress_30_days = self._calc_progress(progress_logs, 30)
        

        assignments = self.db.query(TaskAssignment).filter(TaskAssignment.task_id == tid).all()
        num_assigned = len(assignments)
        

        dept_workload = 0.0
        if task.department_id:
            dept_active = self.db.query(func.count(OperationalTask.task_id)).filter(
                OperationalTask.department_id == task.department_id
            ).scalar() or 0
            dept_emps = self.db.query(func.count(Employee.employee_id)).filter(
                Employee.department_id == task.department_id
            ).scalar() or 1
            dept_workload = round(dept_active / dept_emps, 3)
        

        budget_lines = self.db.query(BudgetLine).filter(
            BudgetLine.budgetable_id == tid,
            BudgetLine.budgetable_type == 'operational_task'
        ).all()
        
        budget_allocated = sum(float(bl.allocated_amount or 0) for bl in budget_lines)
        budget_spent = sum(float(bl.spent_amount or 0) for bl in budget_lines)
        budget_remaining = budget_allocated - budget_spent
        budget_ratio = min(budget_spent / budget_allocated, 2.0) if budget_allocated > 0 else 0.0
        

        risks = self.db.query(Risk).filter(Risk.task_id == tid).all()
        risk_count = len(risks)
        critical_risks = sum(1 for r in risks if r.risk_level_id in [3, 4])
        

        comment_count = self.db.query(func.count(TaskComment.comment_id)).filter(
            TaskComment.task_id == tid
        ).scalar() or 0
        

        is_delayed = 1 if task.status_id == self.delayed_status_id else 0
        
        return {
            'task_id': tid,
            'major_task_id': task.major_task_id or 0,
            'department_id': task.department_id or 0,
            'priority_id': task.priority_id or 2,
            'status_id': task.status_id or 0,
            'is_cross_functional': 1 if task.is_cross_functional else 0,
            'planned_duration_days': planned_duration,
            'elapsed_days': elapsed_days,
            'remaining_days': remaining_days,
            'elapsed_ratio': round(elapsed_ratio, 3),
            'is_overdue': is_overdue,
            'estimated_hours': estimated_hours,
            'actual_hours': actual_hours,
            'hours_ratio': round(hours_ratio, 3),
            'completion_percentage': completion_pct,
            'num_updates': num_updates,
            'days_without_update': days_without_update,
            'avg_progress_rate': round(avg_progress_rate, 3),
            'progress_7_days': round(progress_7_days, 3),
            'progress_30_days': round(progress_30_days, 3),
            'num_assigned_employees': num_assigned,
            'department_workload': dept_workload,
            'budget_allocated': budget_allocated,
            'budget_spent': budget_spent,
            'budget_remaining': budget_remaining,
            'budget_ratio': round(budget_ratio, 3),
            'risk_count': risk_count,
            'critical_risk_count': critical_risks,
            'comment_count': comment_count,
            'avg_kpi_achievement': 0.0,
            'is_delayed': is_delayed
        }
    
    def _calc_progress(self, logs, days):
        if not logs or len(logs) < 2:
            return 0.0
        now = datetime.now()
        cutoff = now.timestamp() - (days * 86400)
        recent = [l for l in logs if l.log_time and l.log_time.timestamp() > cutoff]
        if len(recent) >= 2:
            return round((recent[-1].progress_percent or 0) - (recent[0].progress_percent or 0), 3)
        return 0.0
    
    def get_task_features(self, task_id):
        task = self.db.query(OperationalTask).filter(OperationalTask.task_id == task_id).first()
        return self._extract_single_task(task) if task else None
    
    def close(self):
        if self.db:
            self.db.close()


def load_training_data():
    loader = DataLoader()
    try:
        tasks = loader.load_all_tasks()
        return loader.extract_features(tasks)
    finally:
        loader.close()


def get_features_for_task(task_id):
    loader = DataLoader()
    try:
        return loader.get_task_features(task_id)
    finally:
        loader.close()

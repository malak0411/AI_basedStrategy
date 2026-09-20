import json
from datetime import datetime, date


from app.database import SessionLocal
from app.models import (
    OperationalTask, TaskProgressLog, DictStatus,
    Employee, AiJob
)




IN_PROGRESS_CODE = 'in_progress'
DELAYED_CODE = 'delayed'
TASK_CATEGORY = 'task'


AUTO_UPDATE_NOTE = "تم تغيير حالة المهمة تلقائياً: تجاوزت تاريخ الانتهاء المحدد"




class TaskStatusUpdater:


    def __init__(self):
        self.db = SessionLocal()


    def update_overdue_tasks(self, created_by=None):
        try:
            in_progress_status = self._get_status(IN_PROGRESS_CODE)
            delayed_status = self._get_status(DELAYED_CODE)


            if not in_progress_status:
                return {
                    "success": False,
                    "error": f"Status '{IN_PROGRESS_CODE}' not found in dict_statuses"
                }


            if not delayed_status:
                return {
                    "success": False,
                    "error": f"Status '{DELAYED_CODE}' not found in dict_statuses"
                }


            today = date.today()


            overdue_tasks = self.db.query(OperationalTask).filter(
                OperationalTask.status_id == in_progress_status.status_id,
                OperationalTask.end_date < today,
                OperationalTask.is_active == True
            ).all()


            if not overdue_tasks:
                return {
                    "success": True,
                    "updated_count": 0,
                    "task_ids": [],
                    "message": "No overdue tasks found"
                }


            system_employee_id = created_by or self._resolve_system_employee()


            updated_ids = []


            for task in overdue_tasks:
                old_status = task.status_id
                task.status_id = delayed_status.status_id
                task.updated_at = datetime.now()


                last_progress = self._get_last_progress(task.task_id)


                log = TaskProgressLog(
                    task_id=task.task_id,
                    employee_id=system_employee_id,
                    progress_percent=last_progress,
                    status_old=old_status,
                    status_new=delayed_status.status_id,
                    notes=AUTO_UPDATE_NOTE,
                    log_time=datetime.now()
                )
                self.db.add(log)


                updated_ids.append(task.task_id)


            self.db.commit()


            return {
                "success": True,
                "updated_count": len(updated_ids),
                "task_ids": updated_ids,
                "old_status_id": in_progress_status.status_id,
                "new_status_id": delayed_status.status_id,
                "message": f"Updated {len(updated_ids)} tasks"
            }


        except Exception as e:
            self.db.rollback()
            return {
                "success": False,
                "error": str(e)
            }


    def run_as_job(self, created_by=None):
        try:
            job = AiJob(
                job_type="task_status_auto_update",
                status="processing",
                input_data=json.dumps({
                    "trigger": "auto",
                    "run_at": datetime.now().isoformat()
                }, ensure_ascii=False),
                created_by=created_by
            )
            self.db.add(job)
            self.db.commit()
            self.db.refresh(job)


            result = self.update_overdue_tasks(created_by=created_by)


            job.status = "completed" if result.get("success") else "failed"
            job.result_json = json.dumps(result, ensure_ascii=False)
            job.updated_at = datetime.now()
            self.db.commit()


            return result


        except Exception as e:
            return {
                "success": False,
                "error": str(e)
            }


    def _get_status(self, code):
        return self.db.query(DictStatus).filter(
            DictStatus.code == code,
            DictStatus.category == TASK_CATEGORY
        ).first()


    def _get_last_progress(self, task_id):
        last_log = self.db.query(TaskProgressLog).filter(
            TaskProgressLog.task_id == task_id
        ).order_by(TaskProgressLog.log_time.desc()).first()


        if last_log and last_log.progress_percent is not None:
            return last_log.progress_percent


        return 0


    def _resolve_system_employee(self):
        admin = self.db.query(Employee).filter(
            Employee.employee_number == 'ADMIN001',
            Employee.is_active == True
        ).first()


        if admin:
            return admin.employee_id


        any_employee = self.db.query(Employee).filter(
            Employee.is_active == True
        ).first()


        if any_employee:
            return any_employee.employee_id


        raise ValueError("No active employee found for logging")


    def close(self):
        if self.db:
            self.db.close()

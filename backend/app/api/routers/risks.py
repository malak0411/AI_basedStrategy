from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy.orm import Session
from typing import List, Optional
from datetime import datetime

from ...database import get_db
from ...models import (
    Risk, RiskMitigation, OperationalTask, DictStatus, DictRiskLevel, Employee
)
from ...schemas import RiskCreate, RiskUpdate, RiskResponse
from ...core.dependencies import get_current_user, has_role

router = APIRouter(prefix="/api/risks", tags=["المخاطر"])

@router.get("/", response_model=List[RiskResponse])
async def get_risks(
    task_id: Optional[int] = None,
    status_id: Optional[int] = None,
    db: Session = Depends(get_db),
    current_user: Employee = Depends(get_current_user)
):
    query = db.query(Risk)
    if task_id:
        query = query.filter(Risk.task_id == task_id)
    if status_id:
        query = query.filter(Risk.status_id == status_id)
    risks = query.all()
    result = []
    for risk in risks:
        task = db.query(OperationalTask).filter(OperationalTask.task_id == risk.task_id).first()
        level = db.query(DictRiskLevel).filter(DictRiskLevel.risk_level_id == risk.risk_level_id).first()
        status = db.query(DictStatus).filter(DictStatus.status_id == risk.status_id).first()
        identified_by = db.query(Employee).filter(Employee.employee_id == risk.identified_by).first()
        result.append(RiskResponse(
            risk_id=risk.risk_id,
            task_id=risk.task_id,
            task_title=task.title if task else None,
            name=risk.name,
            description=risk.description,
            risk_level_id=risk.risk_level_id,
            risk_level_name=level.name_ar if level else None,
            probability=risk.probability,
            impact=risk.impact,
            risk_score=risk.probability * risk.impact // 10 if risk.probability and risk.impact else 0,
            identified_by=risk.identified_by,
            identified_by_name=identified_by.full_name if identified_by else None,
            identified_at=risk.identified_at,
            target_date=risk.target_date,
            status_id=risk.status_id,
            status_name=status.name_ar if status else None,
            updated_at=risk.updated_at
        ))
    return result

# ... باقي الدوال (create, update, delete) بدون تغيير، ولكن تأكد من استيراد Employee فيها

from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy.orm import Session
from typing import List, Optional
from datetime import datetime

from ...database import get_db
from ...models import KPI, KPIMeasurement, Employee
from ...schemas import KPICreate, KPIUpdate, KPIResponse, KPIMeasurementCreate, KPIMeasurementResponse
from ...core.dependencies import get_current_user, has_role

router = APIRouter(prefix="/api/kpis", tags=["مؤشرات الأداء"])

@router.get("/", response_model=List[KPIResponse])
async def get_kpis(
    category: Optional[str] = None,
    db: Session = Depends(get_db),
    current_user: Employee = Depends(get_current_user)
):
    query = db.query(KPI).filter(KPI.is_active == True)
    if category:
        query = query.filter(KPI.category == category)
    kpis = query.all()
    return [KPIResponse(
        kpi_id=k.kpi_id,
        name=k.name,
        description=k.description,
        category=k.category,
        unit=k.unit,
        target_min=k.target_min,
        target_max=k.target_max,
        calculation_method=k.calculation_method,
        is_active=k.is_active,
        created_at=k.created_at,
        updated_at=k.updated_at
    ) for k in kpis]

@router.post("/", response_model=KPIResponse, status_code=status.HTTP_201_CREATED)
async def create_kpi(
    kpi_data: KPICreate,
    db: Session = Depends(get_db),
    current_user: Employee = Depends(has_role(["Super Admin", "وزير / قيادة العليا"]))
):
    new_kpi = KPI(**kpi_data.dict())
    db.add(new_kpi)
    db.commit()
    db.refresh(new_kpi)
    return KPIResponse(
        kpi_id=new_kpi.kpi_id,
        name=new_kpi.name,
        description=new_kpi.description,
        category=new_kpi.category,
        unit=new_kpi.unit,
        target_min=new_kpi.target_min,
        target_max=new_kpi.target_max,
        calculation_method=new_kpi.calculation_method,
        is_active=new_kpi.is_active,
        created_at=new_kpi.created_at,
        updated_at=new_kpi.updated_at
    )

@router.put("/{kpi_id}", response_model=KPIResponse)
async def update_kpi(
    kpi_id: int,
    kpi_data: KPIUpdate,
    db: Session = Depends(get_db),
    current_user: Employee = Depends(has_role(["Super Admin", "وزير / قيادة العليا"]))
):
    kpi = db.query(KPI).filter(KPI.kpi_id == kpi_id).first()
    if not kpi:
        raise HTTPException(status_code=404, detail="المؤشر غير موجود")
    for key, value in kpi_data.dict(exclude_unset=True).items():
        setattr(kpi, key, value)
    kpi.updated_at = datetime.now()
    db.commit()
    db.refresh(kpi)
    return KPIResponse(
        kpi_id=kpi.kpi_id,
        name=kpi.name,
        description=kpi.description,
        category=kpi.category,
        unit=kpi.unit,
        target_min=kpi.target_min,
        target_max=kpi.target_max,
        calculation_method=kpi.calculation_method,
        is_active=kpi.is_active,
        created_at=kpi.created_at,
        updated_at=kpi.updated_at
    )

@router.delete("/{kpi_id}")
async def delete_kpi(
    kpi_id: int,
    db: Session = Depends(get_db),
    current_user: Employee = Depends(has_role(["Super Admin"]))
):
    kpi = db.query(KPI).filter(KPI.kpi_id == kpi_id).first()
    if not kpi:
        raise HTTPException(status_code=404, detail="المؤشر غير موجود")
    # التحقق من وجود قياسات
    measurements_count = db.query(KPIMeasurement).filter(KPIMeasurement.kpi_id == kpi_id).count()
    if measurements_count > 0:
        raise HTTPException(status_code=400, detail=f"لا يمكن الحذف: يوجد {measurements_count} قياسات مرتبطة")
    db.delete(kpi)
    db.commit()
    return {"message": "تم حذف المؤشر بنجاح"}

@router.post("/measurements", response_model=KPIMeasurementResponse, status_code=status.HTTP_201_CREATED)
async def create_measurement(
    measurement_data: KPIMeasurementCreate,
    db: Session = Depends(get_db),
    current_user: Employee = Depends(get_current_user)
):
    kpi = db.query(KPI).filter(KPI.kpi_id == measurement_data.kpi_id).first()
    if not kpi:
        raise HTTPException(status_code=404, detail="المؤشر غير موجود")
    new_meas = KPIMeasurement(
        kpi_id=measurement_data.kpi_id,
        value=measurement_data.value,
        source_type=measurement_data.source_type,
        source_id=measurement_data.source_id,
        recorded_by=current_user.employee_id,
        notes=measurement_data.notes
    )
    db.add(new_meas)
    db.commit()
    db.refresh(new_meas)
    return KPIMeasurementResponse(
        measurement_id=new_meas.measurement_id,
        kpi_id=new_meas.kpi_id,
        kpi_name=kpi.name,
        value=new_meas.value,
        source_type=new_meas.source_type,
        source_id=new_meas.source_id,
        measured_at=new_meas.measured_at,
        recorded_by=new_meas.recorded_by,
        recorded_by_name=current_user.full_name,
        notes=new_meas.notes
    )

@router.get("/measurements/{kpi_id}", response_model=List[KPIMeasurementResponse])
async def get_measurements(
    kpi_id: int,
    limit: int = 30,
    db: Session = Depends(get_db),
    current_user: Employee = Depends(get_current_user)
):
    measurements = db.query(KPIMeasurement).filter(
        KPIMeasurement.kpi_id == kpi_id
    ).order_by(KPIMeasurement.measured_at.desc()).limit(limit).all()
    kpi = db.query(KPI).filter(KPI.kpi_id == kpi_id).first()
    result = []
    for m in measurements:
        recorded_by = db.query(Employee).filter(Employee.employee_id == m.recorded_by).first()
        result.append(KPIMeasurementResponse(
            measurement_id=m.measurement_id,
            kpi_id=m.kpi_id,
            kpi_name=kpi.name if kpi else None,
            value=m.value,
            source_type=m.source_type,
            source_id=m.source_id,
            measured_at=m.measured_at,
            recorded_by=m.recorded_by,
            recorded_by_name=recorded_by.full_name if recorded_by else None,
            notes=m.notes
        ))
    return result

 

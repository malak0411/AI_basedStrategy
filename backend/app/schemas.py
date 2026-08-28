from pydantic import BaseModel, Field, EmailStr, validator
from typing import Optional, List, Dict
from datetime import date, datetime
from decimal import Decimal
from enum import Enum

# ================================================================
# Enums
# ================================================================

class TaskStatusEnum(str, Enum):
    PENDING = "pending"
    IN_PROGRESS = "in_progress"
    COMPLETED = "completed"
    DELAYED = "delayed"
    REVIEW = "review"

class InitiativeStatusEnum(str, Enum):
    DRAFT = "draft"
    ACTIVE = "active"
    COMPLETED = "completed"
    CANCELLED = "cancelled"

class RiskSeverityEnum(str, Enum):
    LOW = "low"
    MEDIUM = "medium"
    HIGH = "high"
    CRITICAL = "critical"

class ResponsibilityTypeEnum(str, Enum):
    LEAD = "LEAD"
    SUPPORT = "SUPPORT"

class AcceptanceStatusEnum(str, Enum):
    PENDING = "pending"
    ACCEPTED = "accepted"
    REJECTED = "rejected"

# ================================================================
# Auth Schemas
# ================================================================

class LoginRequest(BaseModel):
    email: EmailStr
    password: str = Field(..., min_length=6)

class LoginResponse(BaseModel):
    access_token: str
    token_type: str = "bearer"
    employee_id: int
    full_name: str
    email: str
    department_id: int
    department_name: Optional[str] = None
    roles: List[str] = []
    is_active: bool

class UserInfoResponse(BaseModel):
    employee_id: int
    full_name: str
    email: str
    job_title: Optional[str] = None
    department_id: int
    department_name: Optional[str] = None
    roles: List[str] = []
    permissions: List[str] = []

# ================================================================
# Password Management Schemas (NEW)
# ================================================================

class ForgotPasswordRequest(BaseModel):
    email: EmailStr
    phone_number: str

class ForgotPasswordResponse(BaseModel):
    message: str
    email_sent: bool

class ChangePasswordRequest(BaseModel):
    email: EmailStr
    old_password: str = Field(..., min_length=6)
    new_password: str = Field(..., min_length=6)

class ChangePasswordResponse(BaseModel):
    message: str

# ================================================================
# Strategic Pillars Schemas
# ================================================================

class StrategicPillarCreate(BaseModel):
    name: str = Field(..., min_length=1, max_length=255)
    description: Optional[str] = None
    order_index: Optional[int] = 0

class StrategicPillarUpdate(BaseModel):
    name: Optional[str] = Field(None, min_length=1, max_length=255)
    description: Optional[str] = None
    order_index: Optional[int] = None
    is_active: Optional[bool] = None

class StrategicPillarResponse(BaseModel):
    pillar_id: int
    name: str
    description: Optional[str] = None
    order_index: int
    is_active: bool
    created_at: Optional[datetime] = None
    updated_at: Optional[datetime] = None
    goals_count: Optional[int] = 0

# ================================================================
# Strategic Goals Schemas
# ================================================================

class StrategicGoalCreate(BaseModel):
    pillar_id: int
    title: str = Field(..., min_length=1, max_length=255)
    description: Optional[str] = None
    target_date: Optional[date] = None
    weight: Optional[int] = Field(1, ge=1, le=5)
    valid_from: Optional[date] = None
    valid_until: Optional[date] = None

    @validator('valid_until')
    def validate_dates(cls, v, values):
        if v and values.get('valid_from'):
            if v < values['valid_from']:
                raise ValueError('تاريخ الانتهاء يجب أن يكون بعد تاريخ البدء')
        return v

class StrategicGoalUpdate(BaseModel):
    pillar_id: Optional[int] = None
    title: Optional[str] = Field(None, min_length=1, max_length=255)
    description: Optional[str] = None
    target_date: Optional[date] = None
    weight: Optional[int] = Field(None, ge=1, le=5)
    valid_from: Optional[date] = None
    valid_until: Optional[date] = None
    is_active: Optional[bool] = None

class StrategicGoalResponse(BaseModel):
    goal_id: int
    pillar_id: int
    pillar_name: Optional[str] = None
    title: str
    description: Optional[str] = None
    target_date: Optional[date] = None
    weight: int
    valid_from: Optional[date] = None
    valid_until: Optional[date] = None
    is_current: Optional[bool] = None
    is_active: bool
    created_at: Optional[datetime] = None
    updated_at: Optional[datetime] = None
    programs_count: Optional[int] = 0
    achievement_percentage: Optional[float] = 0

# ================================================================
# Programs Schemas
# ================================================================

class ProgramCreate(BaseModel):
    goal_id: int
    name: str = Field(..., min_length=1, max_length=255)
    description: Optional[str] = None
    budget_estimate: Optional[Decimal] = None
    start_date: Optional[date] = None
    end_date: Optional[date] = None
    status_id: Optional[int] = None

    @validator('end_date')
    def validate_dates(cls, v, values):
        if v and values.get('start_date'):
            if v < values['start_date']:
                raise ValueError('تاريخ الانتهاء يجب أن يكون بعد تاريخ البدء')
        return v

class ProgramUpdate(BaseModel):
    goal_id: Optional[int] = None
    name: Optional[str] = Field(None, min_length=1, max_length=255)
    description: Optional[str] = None
    budget_estimate: Optional[Decimal] = None
    start_date: Optional[date] = None
    end_date: Optional[date] = None
    status_id: Optional[int] = None
    is_active: Optional[bool] = None

class ProgramResponse(BaseModel):
    program_id: int
    goal_id: int
    goal_name: Optional[str] = None
    name: str
    description: Optional[str] = None
    budget_estimate: Optional[Decimal] = None
    start_date: Optional[date] = None
    end_date: Optional[date] = None
    status_id: Optional[int] = None
    status_name: Optional[str] = None
    is_active: bool
    created_at: Optional[datetime] = None
    updated_at: Optional[datetime] = None
    initiatives_count: Optional[int] = 0

# ================================================================
# Initiatives Schemas
# ================================================================

class InitiativeCreate(BaseModel):
    program_id: int
    name: str = Field(..., min_length=1, max_length=255)
    description: Optional[str] = None
    status_id: Optional[int] = None
    priority_id: Optional[int] = None
    start_date: Optional[date] = None
    end_date: Optional[date] = None
    budget_estimate: Optional[Decimal] = None

    @validator('end_date')
    def validate_dates(cls, v, values):
        if v and values.get('start_date'):
            if v < values['start_date']:
                raise ValueError('تاريخ الانتهاء يجب أن يكون بعد تاريخ البدء')
        return v

class InitiativeUpdate(BaseModel):
    program_id: Optional[int] = None
    name: Optional[str] = Field(None, min_length=1, max_length=255)
    description: Optional[str] = None
    status_id: Optional[int] = None
    priority_id: Optional[int] = None
    start_date: Optional[date] = None
    end_date: Optional[date] = None
    budget_estimate: Optional[Decimal] = None
    is_active: Optional[bool] = None

class InitiativeResponse(BaseModel):
    initiative_id: int
    program_id: int
    program_name: Optional[str] = None
    name: str
    description: Optional[str] = None
    status_id: Optional[int] = None
    status_name: Optional[str] = None
    priority_id: Optional[int] = None
    priority_name: Optional[str] = None
    start_date: Optional[date] = None
    end_date: Optional[date] = None
    budget_estimate: Optional[Decimal] = None
    is_active: bool
    created_at: Optional[datetime] = None
    updated_at: Optional[datetime] = None
    major_tasks_count: Optional[int] = 0

# ================================================================
# Major Tasks Schemas
# ================================================================

class MajorTaskCreate(BaseModel):
    initiative_id: int
    name: str = Field(..., min_length=1, max_length=255)
    description: Optional[str] = None
    priority_id: Optional[int] = None
    estimated_duration_days: Optional[int] = None
    is_cross_department: Optional[bool] = False

class MajorTaskUpdate(BaseModel):
    initiative_id: Optional[int] = None
    name: Optional[str] = Field(None, min_length=1, max_length=255)
    description: Optional[str] = None
    priority_id: Optional[int] = None
    estimated_duration_days: Optional[int] = None
    is_cross_department: Optional[bool] = None
    is_active: Optional[bool] = None

class MajorTaskResponse(BaseModel):
    major_task_id: int
    initiative_id: int
    initiative_name: Optional[str] = None
    name: str
    description: Optional[str] = None
    priority_id: Optional[int] = None
    priority_name: Optional[str] = None
    estimated_duration_days: Optional[int] = None
    is_cross_department: bool
    is_active: bool
    created_at: Optional[datetime] = None
    updated_at: Optional[datetime] = None
    operational_tasks_count: Optional[int] = 0

# ================================================================
# Operational Tasks Schemas
# ================================================================

class OperationalTaskCreate(BaseModel):
    major_task_id: int
    department_id: int
    title: str = Field(..., min_length=1, max_length=255)
    description: Optional[str] = None
    status_id: Optional[int] = None
    priority_id: Optional[int] = None
    start_date: Optional[date] = None
    end_date: Optional[date] = None
    estimated_hours: Optional[Decimal] = None
    is_cross_functional: Optional[bool] = False

class OperationalTaskUpdate(BaseModel):
    major_task_id: Optional[int] = None
    department_id: Optional[int] = None
    title: Optional[str] = Field(None, min_length=1, max_length=255)
    description: Optional[str] = None
    status_id: Optional[int] = None
    priority_id: Optional[int] = None
    start_date: Optional[date] = None
    end_date: Optional[date] = None
    estimated_hours: Optional[Decimal] = None
    actual_hours: Optional[Decimal] = None
    is_cross_functional: Optional[bool] = None
    is_active: Optional[bool] = None

class OperationalTaskResponse(BaseModel):
    task_id: int
    major_task_id: int
    major_task_name: Optional[str] = None
    department_id: int
    department_name: Optional[str] = None
    title: str
    description: Optional[str] = None
    status_id: Optional[int] = None
    status_name: Optional[str] = None
    priority_id: Optional[int] = None
    priority_name: Optional[str] = None
    start_date: Optional[date] = None
    end_date: Optional[date] = None
    estimated_hours: Optional[Decimal] = None
    actual_hours: Optional[Decimal] = None
    is_cross_functional: bool
    is_active: bool
    created_at: Optional[datetime] = None
    updated_at: Optional[datetime] = None
    current_progress: Optional[int] = 0
    is_delayed: Optional[bool] = False
    remaining_days: Optional[int] = None

# ================================================================
# Task Assignments Schemas
# ================================================================

class TaskAssignmentCreate(BaseModel):
    employee_id: int
    role_type_id: Optional[int] = None
    estimated_hours: Optional[Decimal] = None

class TaskAssignmentUpdate(BaseModel):
    role_type_id: Optional[int] = None
    acceptance_status: Optional[AcceptanceStatusEnum] = None
    rejection_reason: Optional[str] = None
    estimated_hours: Optional[Decimal] = None
    is_active: Optional[bool] = None

class TaskAssignmentResponse(BaseModel):
    assignment_id: int
    task_id: int
    task_title: Optional[str] = None
    employee_id: int
    employee_name: Optional[str] = None
    role_type_id: Optional[int] = None
    role_type_name: Optional[str] = None
    acceptance_status: str
    rejection_reason: Optional[str] = None
    accepted_at: Optional[datetime] = None
    estimated_hours: Optional[Decimal] = None
    assigned_by: Optional[int] = None
    assigned_by_name: Optional[str] = None
    assigned_at: datetime
    is_active: bool

# ================================================================
# Task Progress Schemas
# ================================================================

class TaskProgressUpdate(BaseModel):
    progress_percent: int = Field(..., ge=0, le=100)
    notes: Optional[str] = None
    latitude: Optional[float] = Field(None, ge=-90, le=90)
    longitude: Optional[float] = Field(None, ge=-180, le=180)

class TaskProgressLogResponse(BaseModel):
    log_id: int
    task_id: int
    employee_id: int
    employee_name: Optional[str] = None
    progress_percent: int
    status_old: Optional[int] = None
    status_new: Optional[int] = None
    notes: Optional[str] = None
    log_time: datetime

# ================================================================
# Department Schemas
# ================================================================

class DepartmentCreate(BaseModel):
    name: str = Field(..., min_length=1, max_length=255)
    code: Optional[str] = Field(None, max_length=50)
    description: Optional[str] = None
    parent_department_id: Optional[int] = None
    manager_employee_id: Optional[int] = None
    level: Optional[int] = 1

class DepartmentUpdate(BaseModel):
    name: Optional[str] = Field(None, min_length=1, max_length=255)
    code: Optional[str] = Field(None, max_length=50)
    description: Optional[str] = None
    parent_department_id: Optional[int] = None
    manager_employee_id: Optional[int] = None
    level: Optional[int] = None
    is_active: Optional[bool] = None

class DepartmentResponse(BaseModel):
    department_id: int
    name: str
    code: Optional[str] = None
    description: Optional[str] = None
    parent_department_id: Optional[int] = None
    parent_name: Optional[str] = None
    manager_employee_id: Optional[int] = None
    manager_name: Optional[str] = None
    level: int
    is_active: bool
    created_at: Optional[datetime] = None
    updated_at: Optional[datetime] = None
    employees_count: Optional[int] = 0

# ================================================================
# Employee Schemas
# ================================================================

class EmployeeCreate(BaseModel):
    department_id: int
    full_name: str = Field(..., min_length=2, max_length=255)
    email: EmailStr
    password: str = Field(..., min_length=6)
    phone_number: Optional[str] = None
    job_title: Optional[str] = None
    employment_status_id: Optional[int] = None
    hire_date: Optional[date] = None
    gps_enabled: Optional[bool] = True
    role_ids: Optional[List[int]] = []

class EmployeeUpdate(BaseModel):
    department_id: Optional[int] = None
    full_name: Optional[str] = Field(None, min_length=2, max_length=255)
    email: Optional[EmailStr] = None
    phone_number: Optional[str] = None
    job_title: Optional[str] = None
    employment_status_id: Optional[int] = None
    hire_date: Optional[date] = None
    gps_enabled: Optional[bool] = None
    is_active: Optional[bool] = None
    role_ids: Optional[List[int]] = None

class EmployeeResponse(BaseModel):
    employee_id: int
    department_id: int
    department_name: Optional[str] = None
    employee_number: Optional[str] = None
    full_name: str
    email: str
    phone_number: Optional[str] = None
    job_title: Optional[str] = None
    employment_status_id: Optional[int] = None
    employment_status_name: Optional[str] = None
    hire_date: Optional[date] = None
    gps_enabled: bool
    is_active: bool
    last_login: Optional[datetime] = None
    created_at: Optional[datetime] = None
    updated_at: Optional[datetime] = None
    roles: List[str] = []

# ================================================================
# Dashboard Schemas
# ================================================================

class DepartmentPerformance(BaseModel):
    department_id: int
    name: str
    total_tasks: int
    completed_tasks: int
    completion_rate: float
    delayed_tasks: int
    average_progress: float

class MinisterDashboardResponse(BaseModel):
    total_goals: int
    total_initiatives: int
    total_tasks: int
    overall_completion: float
    departments_performance: List[DepartmentPerformance]
    financial_summary: dict
    critical_risks: List[dict]
    ai_briefing: str

class EmployeeDashboardResponse(BaseModel):
    employee_name: str
    job_title: Optional[str] = None
    department: Optional[str] = None
    tasks_summary: dict
    recent_activities: List[dict]

# ================================================================
# Budget Schemas
# ================================================================

class BudgetLineCreate(BaseModel):
    budgetable_type: str  # program, initiative, major_task, operational_task
    budgetable_id: int
    department_id: Optional[int] = None
    allocated_amount: Decimal = Decimal(0)
    fiscal_year: int
    parent_budget_id: Optional[int] = None

class BudgetLineUpdate(BaseModel):
    allocated_amount: Optional[Decimal] = None
    spent_amount: Optional[Decimal] = None
    department_id: Optional[int] = None

class BudgetLineResponse(BaseModel):
    budget_id: int
    budgetable_type: str
    budgetable_id: int
    budgetable_name: Optional[str] = None
    department_id: Optional[int] = None
    department_name: Optional[str] = None
    allocated_amount: Decimal
    spent_amount: Decimal
    remaining_amount: Decimal
    fiscal_year: int
    parent_budget_id: Optional[int] = None
    created_at: datetime
    updated_at: datetime

class BudgetTransactionCreate(BaseModel):
    budget_id: int
    amount: Decimal
    transaction_type_id: int
    description: Optional[str] = None

class BudgetTransactionResponse(BaseModel):
    transaction_id: int
    budget_id: int
    amount: Decimal
    transaction_type_id: int
    transaction_type_name: Optional[str] = None
    description: Optional[str] = None
    transaction_date: datetime
    created_by: Optional[int] = None
    created_by_name: Optional[str] = None
    approved_by: Optional[int] = None
    approved_by_name: Optional[str] = None
    approved_at: Optional[datetime] = None

# ================================================================
# Risk Schemas
# ================================================================

class RiskCreate(BaseModel):
    task_id: Optional[int] = None
    name: str = Field(..., min_length=1, max_length=255)
    description: Optional[str] = None
    risk_level_id: Optional[int] = None
    probability: Optional[int] = Field(None, ge=1, le=10)
    impact: Optional[int] = Field(None, ge=1, le=10)
    target_date: Optional[date] = None
    status_id: Optional[int] = None

class RiskUpdate(BaseModel):
    task_id: Optional[int] = None
    name: Optional[str] = Field(None, min_length=1, max_length=255)
    description: Optional[str] = None
    risk_level_id: Optional[int] = None
    probability: Optional[int] = Field(None, ge=1, le=10)
    impact: Optional[int] = Field(None, ge=1, le=10)
    target_date: Optional[date] = None
    status_id: Optional[int] = None

class RiskResponse(BaseModel):
    risk_id: int
    task_id: Optional[int] = None
    task_title: Optional[str] = None
    name: str
    description: Optional[str] = None
    risk_level_id: Optional[int] = None
    risk_level_name: Optional[str] = None
    probability: Optional[int] = None
    impact: Optional[int] = None
    risk_score: Optional[int] = None
    identified_by: Optional[int] = None
    identified_by_name: Optional[str] = None
    identified_at: datetime
    target_date: Optional[date] = None
    status_id: Optional[int] = None
    status_name: Optional[str] = None
    updated_at: datetime

# ================================================================
# KPI Schemas
# ================================================================

class KPICreate(BaseModel):
    name: str = Field(..., min_length=1, max_length=255)
    description: Optional[str] = None
    category: Optional[str] = None
    unit: Optional[str] = None
    target_min: Optional[Decimal] = None
    target_max: Optional[Decimal] = None
    calculation_method: Optional[str] = None

class KPIUpdate(BaseModel):
    name: Optional[str] = Field(None, min_length=1, max_length=255)
    description: Optional[str] = None
    category: Optional[str] = None
    unit: Optional[str] = None
    target_min: Optional[Decimal] = None
    target_max: Optional[Decimal] = None
    calculation_method: Optional[str] = None
    is_active: Optional[bool] = None

class KPIResponse(BaseModel):
    kpi_id: int
    name: str
    description: Optional[str] = None
    category: Optional[str] = None
    unit: Optional[str] = None
    target_min: Optional[Decimal] = None
    target_max: Optional[Decimal] = None
    calculation_method: Optional[str] = None
    is_active: bool
    created_at: datetime
    updated_at: datetime

class KPIMeasurementCreate(BaseModel):
    kpi_id: int
    value: Decimal
    source_type: Optional[str] = "manual"
    source_id: Optional[str] = None
    notes: Optional[str] = None

class KPIMeasurementResponse(BaseModel):
    measurement_id: int
    kpi_id: int
    kpi_name: Optional[str] = None
    value: Decimal
    source_type: str
    source_id: Optional[str] = None
    measured_at: datetime
    recorded_by: Optional[int] = None
    recorded_by_name: Optional[str] = None
    notes: Optional[str] = None

# ================================================================
# AI Schemas
# ================================================================

class DelayPredictionRequest(BaseModel):
    task_id: int
    current_progress: int = Field(..., ge=0, le=100)
    remaining_days: int = Field(..., ge=0)
    history: dict  # date -> progress

class DelayPredictionResponse(BaseModel):
    prediction: str  # "on_track", "likely_delayed", "critical"
    probability_delay: float
    confidence: float
    suggestion: str
    estimated_completion_days: Optional[float] = None

class TaskAttachmentBase(BaseModel):
    file_name: str
    file_type: str
    file_extension: str
    file_size: int
    file_path: str
    storage_disk: str = "local"
    description: Optional[str] = None

class TaskAttachmentCreate(BaseModel):
    description: Optional[str] = None

class TaskAttachmentUpdate(BaseModel):
    description: Optional[str] = None

class TaskAttachmentResponse(BaseModel):
    attachment_id: int
    task_id: int
    employee_id: int
    file_name: str
    file_type: str
    file_extension: str
    file_size: int
    description: Optional[str]
    created_at: datetime
    updated_at: datetime
    download_url: str

    class Config:
        from_attributes = True

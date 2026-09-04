from pydantic import BaseModel
from sqlalchemy import Column, Integer, String, Text, Date, DateTime, Boolean, ForeignKey, Enum, Table, DECIMAL
from sqlalchemy.orm import relationship
from sqlalchemy.sql import func
from .database import Base
from datetime import datetime, date
from typing import Optional, List

employee_roles = Table(
    'employee_roles',
    Base.metadata,
    Column('employee_id', Integer, ForeignKey('employees.employee_id'), primary_key=True),
    Column('role_id', Integer, ForeignKey('roles.role_id'), primary_key=True)
)

role_permissions = Table(
    'role_permissions',
    Base.metadata,
    Column('role_id', Integer, ForeignKey('roles.role_id'), primary_key=True),
    Column('permission_id', Integer, ForeignKey('permissions.permission_id'), primary_key=True)
)


class DictStatus(Base):
    __tablename__ = "dict_statuses"

    status_id = Column(Integer, primary_key=True, autoincrement=True)
    code = Column(String(30), unique=True, nullable=False)
    name_ar = Column(String(100), nullable=False)
    name_en = Column(String(100), nullable=False)
    category = Column(Enum('initiative', 'task', 'risk', 'employee', 'budget', 'recommendation'), nullable=False)
    color_hex = Column(String(7), default='#6c757d')
    is_default = Column(Boolean, default=False)
    is_system = Column(Boolean, default=True)


class DictPriority(Base):
    __tablename__ = "dict_priorities"

    priority_id = Column(Integer, primary_key=True, autoincrement=True)
    code = Column(String(20), unique=True, nullable=False)
    name_ar = Column(String(50), nullable=False)
    name_en = Column(String(50), nullable=False)
    level = Column(Integer, nullable=False)
    color_hex = Column(String(7), default='#6c757d')


class DictRoleType(Base):
    __tablename__ = "dict_role_types"

    role_type_id = Column(Integer, primary_key=True, autoincrement=True)
    code = Column(String(20), unique=True, nullable=False)
    name_ar = Column(String(50), nullable=False)
    name_en = Column(String(50), nullable=False)
    description = Column(Text)


class DictRiskLevel(Base):
    __tablename__ = "dict_risk_levels"

    risk_level_id = Column(Integer, primary_key=True, autoincrement=True)
    code = Column(String(20), unique=True, nullable=False)
    name_ar = Column(String(50), nullable=False)
    name_en = Column(String(50), nullable=False)
    min_score = Column(Integer, default=0)
    max_score = Column(Integer, default=100)
    color_hex = Column(String(7), default='#6c757d')


class DictTransactionType(Base):
    __tablename__ = "dict_transaction_types"

    trans_type_id = Column(Integer, primary_key=True, autoincrement=True)
    code = Column(String(30), unique=True, nullable=False)
    name_ar = Column(String(100), nullable=False)
    name_en = Column(String(100), nullable=False)
    sign = Column(Integer, nullable=False)


class Department(Base):
    __tablename__ = "departments"

    department_id = Column(Integer, primary_key=True, autoincrement=True)
    name = Column(String(255), nullable=False)
    code = Column(String(50), unique=True)
    description = Column(Text)
    parent_department_id = Column(Integer, ForeignKey("departments.department_id"))
    manager_employee_id = Column(Integer, ForeignKey("employees.employee_id"))
    level = Column(Integer, default=1)
    is_active = Column(Boolean, default=True)
    created_at = Column(DateTime, server_default=func.now())
    updated_at = Column(DateTime, onupdate=func.now())

    parent = relationship("Department", remote_side=[department_id])
    employees = relationship("Employee", back_populates="department", foreign_keys="[Employee.department_id]")


class Employee(Base):
    __tablename__ = "employees"

    employee_id = Column(Integer, primary_key=True, autoincrement=True)
    department_id = Column(Integer, ForeignKey("departments.department_id"), nullable=False)
    employee_number = Column(String(50), unique=True)
    full_name = Column(String(255), nullable=False)
    email = Column(String(191), unique=True, nullable=False)
    password = Column(String(255), nullable=False)
    phone_number = Column(String(30))
    job_title = Column(String(150))
    employment_status_id = Column(Integer, ForeignKey("dict_statuses.status_id"), nullable=True)
    hire_date = Column(Date)
    gps_enabled = Column(Boolean, default=True)
    is_active = Column(Boolean, default=True)
    last_login = Column(DateTime)
    profile_image = Column(String(500))
    created_at = Column(DateTime, server_default=func.now())
    updated_at = Column(DateTime, onupdate=func.now())

    department = relationship("Department", back_populates="employees", foreign_keys=[department_id])
    employment_status = relationship("DictStatus", foreign_keys=[employment_status_id])
    roles = relationship("Role", secondary=employee_roles, back_populates="employees")
    task_assignments = relationship("TaskAssignment", back_populates="employee", foreign_keys="[TaskAssignment.employee_id]")
    location_logs = relationship("LocationLog", back_populates="employee")
    task_attachments = relationship("TaskAttachment", back_populates="employee")
    task_comments = relationship("TaskComment", back_populates="employee") 



class Role(Base):
    __tablename__ = "roles"

    role_id = Column(Integer, primary_key=True, autoincrement=True)
    name = Column(String(100), unique=True, nullable=False)
    description = Column(Text)
    is_system = Column(Boolean, default=False)
    created_at = Column(DateTime, server_default=func.now())
    updated_at = Column(DateTime, onupdate=func.now())

    employees = relationship("Employee", secondary=employee_roles, back_populates="roles")
    permissions = relationship("Permission", secondary=role_permissions, back_populates="roles")


class Permission(Base):
    __tablename__ = "permissions"

    permission_id = Column(Integer, primary_key=True, autoincrement=True)
    name = Column(String(150), unique=True, nullable=False)
    resource = Column(String(100))
    action = Column(String(50))
    description = Column(Text)

    roles = relationship("Role", secondary=role_permissions, back_populates="permissions")



class StrategicVision(Base):
    __tablename__ = "strategic_visions"

    vision_id = Column(Integer, primary_key=True, autoincrement=True)
    text = Column(Text, nullable=False)
    description = Column(Text)
    version = Column(Integer, default=1)
    effective_date = Column(Date, nullable=False)
    is_current = Column(Boolean, default=False)
    approved_by = Column(Integer, ForeignKey("employees.employee_id"))
    approved_at = Column(DateTime)
    created_by = Column(Integer, ForeignKey("employees.employee_id"))
    created_at = Column(DateTime, server_default=func.now())


class StrategicPillar(Base):
    __tablename__ = "strategic_pillars"

    pillar_id = Column(Integer, primary_key=True, autoincrement=True)
    name = Column(String(255), nullable=False)
    description = Column(Text)
    order_index = Column(Integer, default=0)
    created_by = Column(Integer, ForeignKey("employees.employee_id"))
    created_at = Column(DateTime, server_default=func.now())
    updated_at = Column(DateTime, onupdate=func.now())
    is_active = Column(Boolean, default=True)

    goals = relationship("StrategicGoal", back_populates="pillar")


class StrategicGoal(Base):
    __tablename__ = "strategic_goals"

    goal_id = Column(Integer, primary_key=True, autoincrement=True)
    pillar_id = Column(Integer, ForeignKey("strategic_pillars.pillar_id"), nullable=False)
    title = Column(String(255), nullable=False)
    description = Column(Text)
    target_date = Column(Date)
    weight = Column(Integer, default=1)
    valid_from = Column(Date)
    valid_until = Column(Date)
    created_by = Column(Integer, ForeignKey("employees.employee_id"))
    created_at = Column(DateTime, server_default=func.now())
    updated_at = Column(DateTime, onupdate=func.now())
    is_active = Column(Boolean, default=True)

    pillar = relationship("StrategicPillar", back_populates="goals")
    programs = relationship("Program", back_populates="goal")
    creator = relationship("Employee", foreign_keys=[created_by])


class Program(Base):
    __tablename__ = "programs"

    program_id = Column(Integer, primary_key=True, autoincrement=True)
    goal_id = Column(Integer, ForeignKey("strategic_goals.goal_id"), nullable=False)
    name = Column(String(255), nullable=False)
    description = Column(Text)
    budget_estimate = Column(DECIMAL(15, 2))
    start_date = Column(Date)
    end_date = Column(Date)
    status_id = Column(Integer, ForeignKey("dict_statuses.status_id"))
    created_by = Column(Integer, ForeignKey("employees.employee_id"))
    created_at = Column(DateTime, server_default=func.now())
    updated_at = Column(DateTime, onupdate=func.now())
    is_active = Column(Boolean, default=True)

    goal = relationship("StrategicGoal", back_populates="programs")
    initiatives = relationship("Initiative", back_populates="program")


class Initiative(Base):
    __tablename__ = "initiatives"

    initiative_id = Column(Integer, primary_key=True, autoincrement=True)
    program_id = Column(Integer, ForeignKey("programs.program_id"), nullable=False)
    name = Column(String(255), nullable=False)
    description = Column(Text)
    status_id = Column(Integer, ForeignKey("dict_statuses.status_id"))
    priority_id = Column(Integer, ForeignKey("dict_priorities.priority_id"))
    start_date = Column(Date)
    end_date = Column(Date)
    budget_estimate = Column(DECIMAL(15, 2))
    created_by = Column(Integer, ForeignKey("employees.employee_id"))
    created_at = Column(DateTime, server_default=func.now())
    updated_at = Column(DateTime, onupdate=func.now())
    is_active = Column(Boolean, default=True)

    program = relationship("Program", back_populates="initiatives")
    major_tasks = relationship("MajorTask", back_populates="initiative")



class MajorTask(Base):
    __tablename__ = "major_tasks"

    major_task_id = Column(Integer, primary_key=True, autoincrement=True)
    initiative_id = Column(Integer, ForeignKey("initiatives.initiative_id"), nullable=False)
    name = Column(String(255), nullable=False)
    description = Column(Text)
    priority_id = Column(Integer, ForeignKey("dict_priorities.priority_id"))
    estimated_duration_days = Column(Integer)
    is_cross_department = Column(Boolean, default=False)
    created_by = Column(Integer, ForeignKey("employees.employee_id"))
    created_at = Column(DateTime, server_default=func.now())
    updated_at = Column(DateTime, onupdate=func.now())
    is_active = Column(Boolean, default=True)

    
    initiative = relationship("Initiative", back_populates="major_tasks")
    operational_tasks = relationship("OperationalTask", back_populates="major_task")


class MajorTaskDepartment(Base):
    __tablename__ = "major_task_departments"

    id = Column(Integer, primary_key=True, autoincrement=True)
    major_task_id = Column(Integer, ForeignKey("major_tasks.major_task_id"), nullable=False)
    department_id = Column(Integer, ForeignKey("departments.department_id"), nullable=False)
    responsibility_type = Column(Enum('LEAD', 'SUPPORT'), default='SUPPORT')
    assigned_at = Column(DateTime, server_default=func.now())
    notes = Column(Text)


class OperationalTask(Base):
    __tablename__ = "operational_tasks"

    task_id = Column(Integer, primary_key=True, autoincrement=True)
    major_task_id = Column(Integer, ForeignKey("major_tasks.major_task_id"), nullable=False)
    department_id = Column(Integer, ForeignKey("departments.department_id"), nullable=False)
    title = Column(String(255), nullable=False)
    description = Column(Text)
    status_id = Column(Integer, ForeignKey("dict_statuses.status_id"))
    priority_id = Column(Integer, ForeignKey("dict_priorities.priority_id"))
    start_date = Column(Date)
    end_date = Column(Date)
    estimated_hours = Column(DECIMAL(8, 2))
    actual_hours = Column(DECIMAL(8, 2))
    is_cross_functional = Column(Boolean, default=False)
    created_by = Column(Integer, ForeignKey("employees.employee_id"))
    created_at = Column(DateTime, server_default=func.now())
    updated_at = Column(DateTime, onupdate=func.now())
    is_active = Column(Boolean, default=True)

    major_task = relationship("MajorTask", back_populates="operational_tasks")
    department = relationship("Department")
    assignments = relationship("TaskAssignment", back_populates="task")
    progress_logs = relationship("TaskProgressLog", back_populates="task")
    predictions = relationship("AIPrediction", back_populates="task")
    recommendations = relationship("AIRecommendation", back_populates="task")
    attachments = relationship("TaskAttachment", back_populates="task", cascade="all, delete-orphan")
    comments = relationship("TaskComment", back_populates="task", cascade="all, delete-orphan")  

    
    budget_lines = relationship(
        "BudgetLine",
        primaryjoin="and_(OperationalTask.task_id == BudgetLine.budgetable_id, BudgetLine.budgetable_type == 'operational_task')",
        foreign_keys="[BudgetLine.budgetable_id]",
        viewonly=False,
        back_populates="task"
    )



class TaskAssignment(Base):
    __tablename__ = "task_assignments"

    assignment_id = Column(Integer, primary_key=True, autoincrement=True)
    task_id = Column(Integer, ForeignKey("operational_tasks.task_id"), nullable=False)
    employee_id = Column(Integer, ForeignKey("employees.employee_id"), nullable=False)
    role_type_id = Column(Integer, ForeignKey("dict_role_types.role_type_id"))
    acceptance_status = Column(Enum('pending', 'accepted', 'rejected'), default='pending')
    rejection_reason = Column(Text)
    accepted_at = Column(DateTime)
    estimated_hours = Column(DECIMAL(8, 2))
    assigned_by = Column(Integer, ForeignKey("employees.employee_id"))
    assigned_at = Column(DateTime, server_default=func.now())
    is_active = Column(Boolean, default=True)

    
    task = relationship("OperationalTask", back_populates="assignments")
    employee = relationship("Employee", back_populates="task_assignments", foreign_keys=[employee_id])


class TaskProgressLog(Base):
    __tablename__ = "task_progress_logs"
    
    log_id = Column(Integer, primary_key=True, autoincrement=True)
    task_id = Column(Integer, ForeignKey("operational_tasks.task_id"), nullable=False)
    employee_id = Column(Integer, ForeignKey("employees.employee_id"), nullable=False)
    progress_percent = Column(Integer, default=0)
    status_old = Column(Integer, ForeignKey("dict_statuses.status_id"))
    status_new = Column(Integer, ForeignKey("dict_statuses.status_id"), nullable=False)
    notes = Column(Text)
    log_time = Column(DateTime, server_default=func.now())
    
    task = relationship("OperationalTask", back_populates="progress_logs")
    employee = relationship("Employee")



class TaskDependency(Base):
    __tablename__ = "task_dependencies"

    dependency_id = Column(Integer, primary_key=True, autoincrement=True)
    task_id = Column(Integer, ForeignKey("operational_tasks.task_id"), nullable=False)
    depends_on_task_id = Column(Integer, ForeignKey("operational_tasks.task_id"), nullable=False)
    dependency_type = Column(Enum('FS', 'SS', 'FF', 'SF'), default='FS')
    lag_days = Column(Integer, default=0)
    created_at = Column(DateTime, server_default=func.now())
    task = relationship("OperationalTask", foreign_keys=[task_id], backref="dependencies")

class TaskComment(Base):
    __tablename__ = "task_comments"

    comment_id = Column(Integer, primary_key=True, autoincrement=True)
    task_id = Column(Integer, ForeignKey("operational_tasks.task_id"), nullable=False)
    employee_id = Column(Integer, ForeignKey("employees.employee_id"), nullable=False)
    comment = Column(Text, nullable=False)
    parent_comment_id = Column(Integer, ForeignKey("task_comments.comment_id"))
    created_at = Column(DateTime, server_default=func.now())
    updated_at = Column(DateTime, onupdate=func.now())

    # العلاقات
    task = relationship("OperationalTask", back_populates="comments")
    employee = relationship("Employee", back_populates="task_comments")
    parent = relationship("TaskComment", remote_side=[comment_id], backref="replies")

class TaskCommentResponse(BaseModel):
    comment_id: int
    task_id: int
    employee_id: int
    employee_name: str
    comment: str
    parent_comment_id: Optional[int] = None
    has_children: bool = False
    created_at: Optional[str] = None
    updated_at: Optional[str] = None

    class Config:
        from_attributes = True

class BudgetLine(Base):
    __tablename__ = "budget_lines"

    budget_id = Column(Integer, primary_key=True, autoincrement=True)
    budgetable_type = Column(Enum('program', 'initiative', 'major_task', 'operational_task'), nullable=False)
    budgetable_id = Column(Integer, nullable=False)
    department_id = Column(Integer, ForeignKey("departments.department_id"))
    allocated_amount = Column(DECIMAL(15, 2), default=0)
    spent_amount = Column(DECIMAL(15, 2), default=0)
    fiscal_year = Column(Integer, nullable=False)
    parent_budget_id = Column(Integer, ForeignKey("budget_lines.budget_id"))
    created_at = Column(DateTime, server_default=func.now())
    updated_at = Column(DateTime, onupdate=func.now())

    parent = relationship("BudgetLine", remote_side=[budget_id])
    transactions = relationship("BudgetTransaction", back_populates="budget")
    
    task = relationship(
        "OperationalTask",
        primaryjoin="and_(BudgetLine.budgetable_id == OperationalTask.task_id, BudgetLine.budgetable_type == 'operational_task')",
        foreign_keys="[BudgetLine.budgetable_id]",
        viewonly=False,
        back_populates="budget_lines"
    )


class BudgetTransaction(Base):
    __tablename__ = "budget_transactions"

    transaction_id = Column(Integer, primary_key=True, autoincrement=True)
    budget_id = Column(Integer, ForeignKey("budget_lines.budget_id"), nullable=False)
    amount = Column(DECIMAL(15, 2), nullable=False)
    transaction_type_id = Column(Integer, ForeignKey("dict_transaction_types.trans_type_id"))
    description = Column(Text)
    transaction_date = Column(DateTime, server_default=func.now())
    created_by = Column(Integer, ForeignKey("employees.employee_id"))
    approved_by = Column(Integer, ForeignKey("employees.employee_id"))
    approved_at = Column(DateTime)

    # 
    budget = relationship("BudgetLine", back_populates="transactions")


class Risk(Base):
    __tablename__ = "risks"

    risk_id = Column(Integer, primary_key=True, autoincrement=True)
    task_id = Column(Integer, ForeignKey("operational_tasks.task_id"))
    name = Column(String(255), nullable=False)
    description = Column(Text)
    risk_level_id = Column(Integer, ForeignKey("dict_risk_levels.risk_level_id"))
    probability = Column(Integer)
    impact = Column(Integer)
    risk_score = Column(Integer, default=0) 
    identified_by = Column(Integer, ForeignKey("employees.employee_id"))
    identified_at = Column(DateTime, server_default=func.now())
    target_date = Column(Date)
    status_id = Column(Integer, ForeignKey("dict_statuses.status_id"))
    updated_at = Column(DateTime, onupdate=func.now())

    task = relationship("OperationalTask", foreign_keys=[task_id])
    mitigations = relationship("RiskMitigation", back_populates="risk")



class RiskMitigation(Base):
    __tablename__ = "risk_mitigations"

    mitigation_id = Column(Integer, primary_key=True, autoincrement=True)
    risk_id = Column(Integer, ForeignKey("risks.risk_id"), nullable=False)
    task_id = Column(Integer, ForeignKey("operational_tasks.task_id"))
    action = Column(Text, nullable=False)
    status_id = Column(Integer, ForeignKey("dict_statuses.status_id"))
    assigned_to = Column(Integer, ForeignKey("employees.employee_id"))
    due_date = Column(Date)
    completed_at = Column(DateTime)
    notes = Column(Text)
    created_at = Column(DateTime, server_default=func.now())
    updated_at = Column(DateTime, onupdate=func.now())

    risk = relationship("Risk", back_populates="mitigations")



class KPI(Base):
    __tablename__ = "kpis"

    kpi_id = Column(Integer, primary_key=True, autoincrement=True)
    name = Column(String(255), nullable=False)
    description = Column(Text)
    category = Column(String(100))
    unit = Column(String(50))
    target_min = Column(DECIMAL(15, 2))
    target_max = Column(DECIMAL(15, 2))
    calculation_method = Column(Text)
    is_active = Column(Boolean, default=True)
    created_at = Column(DateTime, server_default=func.now())
    updated_at = Column(DateTime, onupdate=func.now())


class GoalKPI(Base):
    __tablename__ = "goal_kpis"

    id = Column(Integer, primary_key=True, autoincrement=True)
    goal_id = Column(Integer, ForeignKey("strategic_goals.goal_id"), nullable=False)
    kpi_id = Column(Integer, ForeignKey("kpis.kpi_id"), nullable=False)
    target_value = Column(DECIMAL(15, 2))
    weight = Column(DECIMAL(5, 2), default=1.0)
    baseline_value = Column(DECIMAL(15, 2))
    created_at = Column(DateTime, server_default=func.now())
    updated_at = Column(DateTime, onupdate=func.now())


class KPIMeasurement(Base):
    __tablename__ = "kpi_measurements"

    measurement_id = Column(Integer, primary_key=True, autoincrement=True)
    kpi_id = Column(Integer, ForeignKey("kpis.kpi_id"), nullable=False)
    value = Column(DECIMAL(15, 2), nullable=False)
    source_type = Column(Enum('manual', 'system', 'api', 'ai'), default='manual')
    source_id = Column(String(100))
    measured_at = Column(DateTime, server_default=func.now())
    recorded_by = Column(Integer, ForeignKey("employees.employee_id"))
    notes = Column(Text)

    kpi = relationship("KPI")


class SWOTAnalysis(Base):
    __tablename__ = "swot_analysis"

    id = Column(Integer, primary_key=True, autoincrement=True)
    department_id = Column(Integer, ForeignKey("departments.department_id"))
    strengths = Column(Text)
    weaknesses = Column(Text)
    opportunities = Column(Text)
    threats = Column(Text)
    analysis_date = Column(Date, nullable=False)
    created_by = Column(Integer, ForeignKey("employees.employee_id"))
    created_at = Column(DateTime, server_default=func.now())
    updated_at = Column(DateTime, onupdate=func.now())


class PESTELAnalysis(Base):
    __tablename__ = "pestel_analysis"

    id = Column(Integer, primary_key=True, autoincrement=True)
    department_id = Column(Integer, ForeignKey("departments.department_id"))
    political = Column(Text)
    economic = Column(Text)
    social = Column(Text)
    technological = Column(Text)
    environmental = Column(Text)
    legal = Column(Text)
    analysis_date = Column(Date, nullable=False)
    created_by = Column(Integer, ForeignKey("employees.employee_id"))
    created_at = Column(DateTime, server_default=func.now())
    updated_at = Column(DateTime, onupdate=func.now())


class AIModel(Base):
    __tablename__ = "ai_models"

    model_id = Column(Integer, primary_key=True, autoincrement=True)
    name = Column(String(100), nullable=False)
    version = Column(String(20), nullable=False)
    description = Column(Text)
    accuracy = Column(DECIMAL(5, 2))
    f1_score = Column(DECIMAL(5, 2))
    deployed_at = Column(DateTime)
    is_active = Column(Boolean, default=False)
    created_at = Column(DateTime, server_default=func.now())


class AIPrediction(Base):
    __tablename__ = "ai_predictions"

    prediction_id = Column(Integer, primary_key=True, autoincrement=True)
    task_id = Column(Integer, ForeignKey("operational_tasks.task_id"))
    prediction_type = Column(Enum('delay', 'budget', 'risk', 'performance'), nullable=False)
    text = Column(Text, nullable=False)
    probability = Column(DECIMAL(5, 2))
    confidence = Column(DECIMAL(5, 2))
    model_id = Column(Integer, ForeignKey("ai_models.model_id"))
    features_used = Column(Text)  
    created_at = Column(DateTime, server_default=func.now())
    expires_at = Column(DateTime)

    task = relationship("OperationalTask", back_populates="predictions")


class AIRecommendation(Base):
    __tablename__ = "ai_recommendations"

    recommendation_id = Column(Integer, primary_key=True, autoincrement=True)
    task_id = Column(Integer, ForeignKey("operational_tasks.task_id"))
    text = Column(Text, nullable=False)
    reasoning = Column(Text)
    priority_id = Column(Integer, ForeignKey("dict_priorities.priority_id"))
    status_id = Column(Integer, ForeignKey("dict_statuses.status_id"))
    implemented_by = Column(Integer, ForeignKey("employees.employee_id"))
    implemented_at = Column(DateTime)
    created_at = Column(DateTime, server_default=func.now())
    updated_at = Column(DateTime, onupdate=func.now())

    task = relationship("OperationalTask", back_populates="recommendations")



class LocationLog(Base):
    __tablename__ = "location_logs"

    location_id = Column(Integer, primary_key=True, autoincrement=True)
    employee_id = Column(Integer, ForeignKey("employees.employee_id"), nullable=False)
    task_id = Column(Integer, ForeignKey("operational_tasks.task_id"))
    latitude = Column(DECIMAL(10, 8))
    longitude = Column(DECIMAL(11, 8))
    accuracy = Column(DECIMAL(10, 2))
    recorded_at = Column(DateTime, server_default=func.now())
    source = Column(String(50))

    employee = relationship("Employee", back_populates="location_logs")


class AuditLog(Base):
    __tablename__ = "audit_logs"

    audit_id = Column(Integer, primary_key=True, autoincrement=True)
    employee_id = Column(Integer, ForeignKey("employees.employee_id"))
    action = Column(String(255), nullable=False)
    table_name = Column(String(100), nullable=False)
    record_id = Column(Integer)
    old_data = Column(Text)  
    new_data = Column(Text)  
    ip_address = Column(String(45))
    user_agent = Column(Text)
    created_at = Column(DateTime, server_default=func.now())

class SystemConfig(Base):
    __tablename__ = "system_config"
    
    config_id = Column(Integer, primary_key=True, index=True)
    config_key = Column(String(100), unique=True, nullable=False)
    config_value = Column(String(500), nullable=False)
    description = Column(String(500))
    updated_at = Column(DateTime, server_default=func.now())

class AiJob(Base):
    __tablename__ = "ai_jobs"
    
    job_id = Column(Integer, primary_key=True, autoincrement=True)
    job_type = Column(String(50), nullable=False)
    status = Column(String(20), default="pending")
    input_data = Column(Text)  
    result_json = Column(Text)  
    created_by = Column(Integer, ForeignKey("employees.employee_id"))
    created_at = Column(DateTime, server_default=func.now())
    updated_at = Column(DateTime, onupdate=func.now())

class TaskAttachment(Base):
    __tablename__ = "task_attachments"

    attachment_id = Column(Integer, primary_key=True, autoincrement=True)
    task_id = Column(Integer, ForeignKey("operational_tasks.task_id"), nullable=False)
    employee_id = Column(Integer, ForeignKey("employees.employee_id"), nullable=False)
    file_name = Column(String(255), nullable=False)
    file_type = Column(String(100))
    file_extension = Column(String(10))
    file_size = Column(Integer)
    file_path = Column(String(500))
    storage_disk = Column(String(50), default='local')
    description = Column(Text)
    created_at = Column(DateTime, server_default=func.now())
    updated_at = Column(DateTime, onupdate=func.now())
    is_active = Column(Boolean, default=True)

    
    task = relationship("OperationalTask", back_populates="attachments")
    employee = relationship("Employee", back_populates="task_attachments")

class AttachmentEmployeeInfo(BaseModel):
    employee_id: int
    full_name: str

class TaskAttachmentResponse(BaseModel):
    attachment_id: int
    task_id: int
    file_name: str
    file_type: str
    file_extension: Optional[str] = None
    file_size: int
    file_path: str
    storage_disk: Optional[str] = "local"
    description: Optional[str] = None
    created_at: datetime
    uploaded_by: AttachmentEmployeeInfo

    class Config:
        from_attributes = True


class KPICreate(BaseModel):
    name: str
    description: Optional[str] = None
    category: Optional[str] = None
    unit: Optional[str] = None
    target_min: Optional[float] = None
    target_max: Optional[float] = None
    calculation_method: Optional[str] = None
    goal_id: int
    target_value: float
    baseline_value: Optional[float] = None
    weight: Optional[float] = 1.0

class KPIUpdate(BaseModel):
    name: Optional[str] = None
    description: Optional[str] = None
    category: Optional[str] = None
    unit: Optional[str] = None
    target_min: Optional[float] = None
    target_max: Optional[float] = None
    calculation_method: Optional[str] = None
    goal_id: Optional[int] = None
    target_value: Optional[float] = None
    baseline_value: Optional[float] = None
    weight: Optional[float] = None

class KPIMeasurementCreate(BaseModel):
    value: float
    measured_at: Optional[datetime] = None
    notes: Optional[str] = None

class GoalInfo(BaseModel):
    goal_id: Optional[int] = None
    title: Optional[str] = None
    description: Optional[str] = None

class GoalKPIInfo(BaseModel):
    target_value: Optional[float] = None
    baseline_value: Optional[float] = None
    weight: Optional[float] = None

class MeasurementResponse(BaseModel):
    measurement_id: int
    value: float
    measured_at: Optional[str] = None
    source_type: str
    notes: Optional[str] = None
    recorded_by: Optional[int] = None

class KPIResponse(BaseModel):
    kpi_id: int
    name: str
    description: Optional[str] = None
    category: Optional[str] = None
    unit: Optional[str] = None
    target_min: Optional[float] = None
    target_max: Optional[float] = None
    calculation_method: Optional[str] = None
    is_active: bool = True
    created_at: Optional[str] = None
    updated_at: Optional[str] = None
    goal_title: Optional[str] = None
    goal_id: Optional[int] = None
    current_value: Optional[float] = None
    target_value: Optional[float] = None
    achievement_percentage: Optional[float] = None
    trend: Optional[str] = None
    status: Optional[str] = None
    last_updated: Optional[str] = None

class KPIDetailResponse(BaseModel):
    kpi_id: int
    name: str
    description: Optional[str] = None
    category: Optional[str] = None
    unit: Optional[str] = None
    target_min: Optional[float] = None
    target_max: Optional[float] = None
    calculation_method: Optional[str] = None
    is_active: bool = True
    created_at: Optional[str] = None
    updated_at: Optional[str] = None
    goal: Optional[GoalInfo] = None
    goal_kpi: Optional[GoalKPIInfo] = None
    current_value: Optional[float] = None
    achievement_percentage: Optional[float] = None
    trend: Optional[str] = None
    status: Optional[str] = None
    measurements: Optional[List[MeasurementResponse]] = []

class BudgetLineCreate(BaseModel):
    budgetable_type: str
    budgetable_id: int
    department_id: Optional[int] = None
    fiscal_year: int
    allocated_amount: float
    parent_budget_id: Optional[int] = None

class BudgetLineUpdate(BaseModel):
    department_id: Optional[int] = None
    fiscal_year: Optional[int] = None
    allocated_amount: Optional[float] = None
    parent_budget_id: Optional[int] = None

class BudgetTransactionCreate(BaseModel):
    amount: float
    transaction_type_id: int
    description: Optional[str] = None
    transaction_date: Optional[datetime] = None

class BudgetTransactionUpdate(BaseModel):
    amount: Optional[float] = None
    transaction_type_id: Optional[int] = None
    description: Optional[str] = None
    transaction_date: Optional[datetime] = None

class DepartmentInfo(BaseModel):
    id: Optional[int] = None
    name: Optional[str] = None

class BudgetableInfo(BaseModel):
    id: Optional[int] = None
    name: Optional[str] = None
    type: Optional[str] = None

class TransactionTypeInfo(BaseModel):
    id: Optional[int] = None
    name: Optional[str] = None

class EmployeeInfo(BaseModel):
    id: Optional[int] = None
    name: Optional[str] = None

class BudgetTransactionResponse(BaseModel):
    transaction_id: int
    budget_id: int
    budgetable_type: Optional[str] = None
    budgetable_name: Optional[str] = None
    amount: float
    transaction_type: Optional[TransactionTypeInfo] = None
    description: Optional[str] = None
    transaction_date: Optional[str] = None
    created_by: Optional[EmployeeInfo] = None
    approved_by: Optional[EmployeeInfo] = None
    approved_at: Optional[str] = None
    is_approved: bool = False

class BudgetLineResponse(BaseModel):
    budget_id: int
    budgetable_type: str
    budgetable_id: int
    budgetable_name: Optional[str] = None
    department: Optional[DepartmentInfo] = None
    fiscal_year: int
    allocated_amount: float
    spent_amount: float
    remaining_amount: float
    execution_percentage: float
    status: str
    parent_budget: Optional[dict] = None
    transactions: Optional[List[BudgetTransactionResponse]] = []
    created_at: Optional[str] = None
    updated_at: Optional[str] = None

class BudgetOptionsResponse(BaseModel):
    budget_types: List[str] = []
    programs: List[dict] = []
    initiatives: List[dict] = []
    major_tasks: List[dict] = []
    operational_tasks: List[dict] = []
    departments: List[dict] = []
    transaction_types: List[dict] = []
    fiscal_years: List[int] = []
    parent_budgets: List[dict] = []


class RiskLevelInfo(BaseModel):
    risk_level_id: Optional[int] = None
    name_ar: Optional[str] = None
    name_en: Optional[str] = None
    color_hex: Optional[str] = "#6c757d"

class StatusInfo(BaseModel):
    status_id: Optional[int] = None
    name_ar: Optional[str] = None
    name_en: Optional[str] = None
    color_hex: Optional[str] = "#6c757d"

class TaskInfo(BaseModel):
    task_id: Optional[int] = None
    title: Optional[str] = None

class EmployeeInfo(BaseModel):
    employee_id: Optional[int] = None
    full_name: Optional[str] = None

class RiskCreate(BaseModel):
    name: str
    description: Optional[str] = None
    task_id: Optional[int] = None
    probability: int
    impact: int
    target_date: Optional[date] = None
    status_id: Optional[int] = None

class RiskUpdate(BaseModel):
    name: Optional[str] = None
    description: Optional[str] = None
    task_id: Optional[int] = None
    probability: Optional[int] = None
    impact: Optional[int] = None
    target_date: Optional[date] = None
    status_id: Optional[int] = None

class RiskMitigationCreate(BaseModel):
    action: str
    task_id: Optional[int] = None
    assigned_to: Optional[int] = None
    due_date: Optional[date] = None
    status_id: Optional[int] = None
    notes: Optional[str] = None

class RiskMitigationUpdate(BaseModel):
    action: Optional[str] = None
    task_id: Optional[int] = None
    assigned_to: Optional[int] = None
    due_date: Optional[date] = None
    status_id: Optional[int] = None
    notes: Optional[str] = None

class RiskMitigationResponse(BaseModel):
    mitigation_id: int
    action: str
    task: Optional[TaskInfo] = None
    assigned_to: Optional[EmployeeInfo] = None
    status: Optional[StatusInfo] = None
    due_date: Optional[str] = None
    completed_at: Optional[str] = None
    notes: Optional[str] = None
    created_at: Optional[str] = None
    updated_at: Optional[str] = None

class RiskResponse(BaseModel):
    risk_id: int
    name: str
    description: Optional[str] = None
    task_id: Optional[int] = None
    task: Optional[TaskInfo] = None
    probability: int
    impact: int
    risk_score: int
    risk_level: Optional[RiskLevelInfo] = None
    status: Optional[StatusInfo] = None
    identified_by: Optional[EmployeeInfo] = None
    identified_at: Optional[str] = None
    target_date: Optional[str] = None
    updated_at: Optional[str] = None
    mitigations: Optional[List[RiskMitigationResponse]] = []


class EmployeeLocationInfo(BaseModel):
    employee_id: Optional[int] = None
    employee_number: Optional[str] = None
    full_name: Optional[str] = None
    job_title: Optional[str] = None
    gps_enabled: bool = False
    is_active: bool = False
    department: Optional[dict] = None

class TaskLocationInfo(BaseModel):
    task_id: Optional[int] = None
    title: Optional[str] = None

class LocationLogCreate(BaseModel):
    employee_id: int
    task_id: Optional[int] = None
    latitude: float
    longitude: float
    accuracy: Optional[float] = None
    recorded_at: Optional[datetime] = None
    source: Optional[str] = None

class LocationLogUpdate(BaseModel):
    task_id: Optional[int] = None
    latitude: Optional[float] = None
    longitude: Optional[float] = None
    accuracy: Optional[float] = None
    source: Optional[str] = None

class LocationLogResponse(BaseModel):
    location_id: int
    employee_id: int
    employee: Optional[EmployeeLocationInfo] = None
    task_id: Optional[int] = None
    task: Optional[TaskLocationInfo] = None
    latitude: Optional[float] = None
    longitude: Optional[float] = None
    accuracy: Optional[float] = None
    recorded_at: Optional[str] = None
    source: Optional[str] = None

class LocationEmployeeLatestResponse(BaseModel):
    employee_id: int
    employee: EmployeeLocationInfo
    has_location: bool = False
    location: Optional[LocationLogResponse] = None

class LocationEmployeeHistoryResponse(BaseModel):
    employee: EmployeeLocationInfo
    data: List[LocationLogResponse] = []
    total: int = 0
    page: int = 1
    per_page: int = 10
    total_pages: int = 0

class LocationSummaryResponse(BaseModel):
    total_logs: int = 0
    employees_with_gps: int = 0
    locations_today: int = 0
    active_employees: int = 0
    latest_recorded_at: Optional[str] = None

class LocationMapResponse(BaseModel):
    location_id: int
    employee_id: int
    employee_name: Optional[str] = None
    employee_number: Optional[str] = None
    job_title: Optional[str] = None
    task_id: Optional[int] = None
    task_title: Optional[str] = None
    latitude: Optional[float] = None
    longitude: Optional[float] = None
    accuracy: Optional[float] = None
    recorded_at: Optional[str] = None
    source: Optional[str] = None

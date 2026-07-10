from sqlalchemy import Column, Integer, String, Text, Date, DateTime, Boolean, ForeignKey, Enum, Table, DECIMAL
from sqlalchemy.orm import relationship
from sqlalchemy.sql import func
from .database import Base


# ================================================================
# جداول الربط (Many-to-Many)
# ================================================================

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

# ================================================================
# 1. القواميس الأساسية (Dictionaries)
# ================================================================

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

# ================================================================
# 2. الطبقة التنظيمية (Organization)
# ================================================================

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

    # العلاقات
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

    # العلاقات
    department = relationship("Department", back_populates="employees", foreign_keys=[department_id])
    employment_status = relationship("DictStatus", foreign_keys=[employment_status_id])
    roles = relationship("Role", secondary=employee_roles, back_populates="employees")
    task_assignments = relationship("TaskAssignment", back_populates="employee", foreign_keys="[TaskAssignment.employee_id]")
    location_logs = relationship("LocationLog", back_populates="employee")


class Role(Base):
    __tablename__ = "roles"

    role_id = Column(Integer, primary_key=True, autoincrement=True)
    name = Column(String(100), unique=True, nullable=False)
    description = Column(Text)
    is_system = Column(Boolean, default=False)
    created_at = Column(DateTime, server_default=func.now())
    updated_at = Column(DateTime, onupdate=func.now())

    # العلاقات
    employees = relationship("Employee", secondary=employee_roles, back_populates="roles")
    permissions = relationship("Permission", secondary=role_permissions, back_populates="roles")


class Permission(Base):
    __tablename__ = "permissions"

    permission_id = Column(Integer, primary_key=True, autoincrement=True)
    name = Column(String(150), unique=True, nullable=False)
    resource = Column(String(100))
    action = Column(String(50))
    description = Column(Text)

    # العلاقات
    roles = relationship("Role", secondary=role_permissions, back_populates="permissions")


# ================================================================
# 3. الطبقة الاستراتيجية (Strategic)
# ================================================================

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

    # العلاقات
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

    # العلاقات
    pillar = relationship("StrategicPillar", back_populates="goals")
    programs = relationship("Program", back_populates="goal")


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

    # العلاقات
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

    # العلاقات
    program = relationship("Program", back_populates="initiatives")
    major_tasks = relationship("MajorTask", back_populates="initiative")


# ================================================================
# 4. طبقة التنفيذ (Execution)
# ================================================================

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

    # العلاقات
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

    # العلاقات - تم إصلاح budget_lines باستخدام primaryjoin
    major_task = relationship("MajorTask", back_populates="operational_tasks")
    department = relationship("Department")
    assignments = relationship("TaskAssignment", back_populates="task")
    progress_logs = relationship("TaskProgressLog", back_populates="task")
    predictions = relationship("AIPrediction", back_populates="task")
    recommendations = relationship("AIRecommendation", back_populates="task")
    
    # علاقة خاصة بـ budget_lines (متعدد الأغراض)
    budget_lines = relationship(
        "BudgetLine",
        primaryjoin="and_(OperationalTask.task_id == BudgetLine.budgetable_id, BudgetLine.budgetable_type == 'operational_task')",
        foreign_keys="[BudgetLine.budgetable_id]",
        viewonly=False,
        back_populates="task"
    )


# ================================================================
# 5. توزيع المهام والتقدم
# ================================================================

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

    # العلاقات
    task = relationship("OperationalTask", back_populates="assignments")
    employee = relationship("Employee", back_populates="task_assignments", foreign_keys=[employee_id])


class TaskProgressLog(Base):
    __tablename__ = "task_progress_logs"

    log_id = Column(Integer, primary_key=True, autoincrement=True)
    task_id = Column(Integer, ForeignKey("operational_tasks.task_id"), nullable=False)
    employee_id = Column(Integer, ForeignKey("employees.employee_id"), nullable=False)
    progress_percent = Column(Integer)
    status_old = Column(Integer, ForeignKey("dict_statuses.status_id"))
    status_new = Column(Integer, ForeignKey("dict_statuses.status_id"))
    notes = Column(Text)
    log_time = Column(DateTime, server_default=func.now())

    # العلاقات
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


class TaskComment(Base):
    __tablename__ = "task_comments"

    comment_id = Column(Integer, primary_key=True, autoincrement=True)
    task_id = Column(Integer, ForeignKey("operational_tasks.task_id"), nullable=False)
    employee_id = Column(Integer, ForeignKey("employees.employee_id"), nullable=False)
    comment = Column(Text, nullable=False)
    parent_comment_id = Column(Integer, ForeignKey("task_comments.comment_id"))
    created_at = Column(DateTime, server_default=func.now())
    updated_at = Column(DateTime, onupdate=func.now())


class TaskAttachment(Base):
    __tablename__ = "task_attachments"

    attachment_id = Column(Integer, primary_key=True, autoincrement=True)
    task_id = Column(Integer, ForeignKey("operational_tasks.task_id"), nullable=False)
    employee_id = Column(Integer, ForeignKey("employees.employee_id"), nullable=False)
    file_name = Column(String(255), nullable=False)
    file_path = Column(String(500), nullable=False)
    file_type = Column(String(50))
    file_size = Column(Integer)
    uploaded_at = Column(DateTime, server_default=func.now())


# ================================================================
# 6. الميزانية والمخاطر
# ================================================================

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

    # العلاقات - تم إصلاح task باستخدام primaryjoin
    parent = relationship("BudgetLine", remote_side=[budget_id])
    transactions = relationship("BudgetTransaction", back_populates="budget")
    
    # علاقة عكسية لـ operational_task
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

    # العلاقات
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
    risk_score = Column(Integer, default=0)  # <-- أضف هذا السطر
    identified_by = Column(Integer, ForeignKey("employees.employee_id"))
    identified_at = Column(DateTime, server_default=func.now())
    target_date = Column(Date)
    status_id = Column(Integer, ForeignKey("dict_statuses.status_id"))
    updated_at = Column(DateTime, onupdate=func.now())

    # العلاقات
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

    # العلاقات
    risk = relationship("Risk", back_populates="mitigations")


# ================================================================
# 7. مؤشرات الأداء (KPIs)
# ================================================================

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

    # العلاقات
    kpi = relationship("KPI")


# ================================================================
# 8. تحليل (SWOT / PESTEL)
# ================================================================

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


# ================================================================
# 9. الذكاء الاصطناعي (AI)
# ================================================================

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
    features_used = Column(Text)  # JSON
    created_at = Column(DateTime, server_default=func.now())
    expires_at = Column(DateTime)

    # العلاقات
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

    # العلاقات
    task = relationship("OperationalTask", back_populates="recommendations")


# ================================================================
# 10. الموقع والتدقيق
# ================================================================

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

    # العلاقات
    employee = relationship("Employee", back_populates="location_logs")


class AuditLog(Base):
    __tablename__ = "audit_logs"

    audit_id = Column(Integer, primary_key=True, autoincrement=True)
    employee_id = Column(Integer, ForeignKey("employees.employee_id"))
    action = Column(String(255), nullable=False)
    table_name = Column(String(100), nullable=False)
    record_id = Column(Integer)
    old_data = Column(Text)  # JSON
    new_data = Column(Text)  # JSON
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
    input_data = Column(Text)  # JSON
    result_json = Column(Text)  # JSON
    created_by = Column(Integer, ForeignKey("employees.employee_id"))
    created_at = Column(DateTime, server_default=func.now())
    updated_at = Column(DateTime, onupdate=func.now())

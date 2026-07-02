<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\CheckJwtToken;

// Controllers - Auth
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ChangePasswordController;

// Controllers - Dashboard
use App\Http\Controllers\Dashboard\EmployeeDashboardController;
use App\Http\Controllers\Dashboard\ManagerDashboardController;
use App\Http\Controllers\Dashboard\MinisterDashboardController;

// Controllers - Tasks
use App\Http\Controllers\Task\TaskController;

// Controllers - Strategic
use App\Http\Controllers\Strategic\PillarController;
use App\Http\Controllers\Strategic\GoalController;
use App\Http\Controllers\Strategic\ProgramController;
use App\Http\Controllers\Strategic\InitiativeController;
use App\Http\Controllers\Strategic\SwotController;
use App\Http\Controllers\Strategic\PestelController;

// Controllers - KPIs, Budget, Risks
use App\Http\Controllers\KpiController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\RiskController;

// Controllers - AI
use App\Http\Controllers\AiController;

// Controllers - Admin
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\SystemConfigController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\EmployeeRoleController;
use App\Http\Controllers\Admin\RolePermissionController;

// Controllers - Other
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\LocationController;

// ============================================================
// تسجيل Middleware
// ============================================================
Route::aliasMiddleware('check.jwt', CheckJwtToken::class);

// ============================================================
// الصفحة الرئيسية
// ============================================================
Route::get('/', function () {
    return redirect()->route('login');
});

// ============================================================
// المصادقة (زائر)
// ============================================================
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.submit');
    Route::get('/forgot-password', [ForgotPasswordController::class, 'showForgotForm'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink'])->name('password.email');
});

// ============================================================
// محمي بـ JWT
// ============================================================
Route::middleware('check.jwt')->group(function () {

    // ========== تغيير كلمة المرور ==========
    Route::get('/change-password', [ChangePasswordController::class, 'showChangeForm'])->name('password.change');
    Route::post('/change-password', [ChangePasswordController::class, 'changePassword'])->name('password.update');

    // ========== لوحات التحكم ==========
    Route::get('/dashboard/employee', [EmployeeDashboardController::class, 'index'])->name('dashboard.employee');
    Route::get('/dashboard/manager', [ManagerDashboardController::class, 'index'])->name('dashboard.manager');
    Route::get('/dashboard/minister', [MinisterDashboardController::class, 'index'])->name('dashboard.minister');

    // ========== المهام ==========
    Route::prefix('tasks')->name('tasks.')->group(function () {
        Route::get('/', [TaskController::class, 'index'])->name('index');
        Route::get('/create', [TaskController::class, 'create'])->name('create');
        Route::post('/', [TaskController::class, 'store'])->name('store');
        Route::get('/{id}', [TaskController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [TaskController::class, 'edit'])->name('edit');
        Route::put('/{id}', [TaskController::class, 'update'])->name('update');
    });

    // ========== التخطيط الاستراتيجي ==========
    Route::prefix('strategic')->name('strategic.')->group(function () {
        // الركائز
        Route::get('/pillars', [PillarController::class, 'index'])->name('pillars.index');
        
        // الأهداف
        Route::get('/goals', [GoalController::class, 'index'])->name('goals.index');
        Route::get('/goals/create', [GoalController::class, 'create'])->name('goals.create');
        Route::post('/goals', [GoalController::class, 'store'])->name('goals.store');
        Route::get('/goals/{id}', [GoalController::class, 'show'])->name('goals.show');
        Route::get('/goals/{id}/edit', [GoalController::class, 'edit'])->name('goals.edit');
        Route::put('/goals/{id}', [GoalController::class, 'update'])->name('goals.update');
        
        // البرامج
        Route::get('/programs', [ProgramController::class, 'index'])->name('programs.index');
        Route::get('/programs/create', [ProgramController::class, 'create'])->name('programs.create');
        Route::post('/programs', [ProgramController::class, 'store'])->name('programs.store');
        Route::get('/programs/{id}', [ProgramController::class, 'show'])->name('programs.show');
        Route::get('/programs/{id}/edit', [ProgramController::class, 'edit'])->name('programs.edit');
        Route::put('/programs/{id}', [ProgramController::class, 'update'])->name('programs.update');
        
        // المبادرات
        Route::get('/initiatives', [InitiativeController::class, 'index'])->name('initiatives.index');
        Route::get('/initiatives/create', [InitiativeController::class, 'create'])->name('initiatives.create');
        Route::post('/initiatives', [InitiativeController::class, 'store'])->name('initiatives.store');
        Route::get('/initiatives/{id}', [InitiativeController::class, 'show'])->name('initiatives.show');
        Route::get('/initiatives/{id}/edit', [InitiativeController::class, 'edit'])->name('initiatives.edit');
        Route::put('/initiatives/{id}', [InitiativeController::class, 'update'])->name('initiatives.update');
        
        // التحليل
        Route::get('/swot', [SwotController::class, 'index'])->name('swot.index');
        Route::get('/pestel', [PestelController::class, 'index'])->name('pestel.index');
    });

    // ========== مؤشرات الأداء ==========
    Route::prefix('kpis')->name('kpis.')->group(function () {
        Route::get('/', [KpiController::class, 'index'])->name('index');
        Route::get('/create', [KpiController::class, 'create'])->name('create');
        Route::post('/', [KpiController::class, 'store'])->name('store');
        Route::get('/{id}', [KpiController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [KpiController::class, 'edit'])->name('edit');
        Route::put('/{id}', [KpiController::class, 'update'])->name('update');
        Route::get('/{id}/measurements', [KpiController::class, 'measurements'])->name('measurements');
        Route::get('/{id}/measurements/create', [KpiController::class, 'createMeasurement'])->name('measurements.create');
        Route::post('/{id}/measurements', [KpiController::class, 'storeMeasurement'])->name('measurements.store');
    });

    // ========== الميزانية ==========
    Route::prefix('budget')->name('budget.')->group(function () {
        Route::get('/', [BudgetController::class, 'index'])->name('index');
        Route::get('/create', [BudgetController::class, 'create'])->name('create');
        Route::post('/', [BudgetController::class, 'store'])->name('store');
        Route::get('/{id}', [BudgetController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [BudgetController::class, 'edit'])->name('edit');
        Route::put('/{id}', [BudgetController::class, 'update'])->name('update');
        // المعاملات
        Route::get('/transactions', [BudgetController::class, 'transactions'])->name('transactions');
        Route::get('/transactions/create', [BudgetController::class, 'createTransaction'])->name('transactions.create');
        Route::post('/transactions', [BudgetController::class, 'storeTransaction'])->name('transactions.store');
        Route::get('/transactions/{id}', [BudgetController::class, 'showTransaction'])->name('transactions.show');
    });

    // ========== المخاطر ==========
    Route::prefix('risks')->name('risks.')->group(function () {
        Route::get('/', [RiskController::class, 'index'])->name('index');
        Route::get('/create', [RiskController::class, 'create'])->name('create');
        Route::post('/', [RiskController::class, 'store'])->name('store');
        Route::get('/{id}', [RiskController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [RiskController::class, 'edit'])->name('edit');
        Route::put('/{id}', [RiskController::class, 'update'])->name('update');
        // خطط التخفيف
        Route::get('/{id}/mitigations', [RiskController::class, 'mitigations'])->name('mitigations');
        Route::get('/{id}/mitigations/create', [RiskController::class, 'createMitigation'])->name('mitigations.create');
        Route::post('/{id}/mitigations', [RiskController::class, 'storeMitigation'])->name('mitigations.store');
    });

    // ========== الذكاء الاصطناعي ==========
    Route::prefix('ai')->name('ai.')->group(function () {
        Route::get('/dashboard', [AiController::class, 'dashboard'])->name('dashboard');
        Route::get('/models', [AiController::class, 'models'])->name('models');
        Route::get('/models/{id}', [AiController::class, 'showModel'])->name('models.show');
        Route::get('/predictions', [AiController::class, 'predictions'])->name('predictions');
        Route::get('/predictions/{id}', [AiController::class, 'showPrediction'])->name('predictions.show');
        Route::get('/recommendations', [AiController::class, 'recommendations'])->name('recommendations');
        Route::get('/recommendations/{id}', [AiController::class, 'showRecommendation'])->name('recommendations.show');
    });

    // ========== التقارير ==========
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

    // ========== الملف الشخصي ==========
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // ========== تتبع المواقع ==========
    Route::prefix('location-tracking')->name('location.')->group(function () {
        Route::get('/', [LocationController::class, 'index'])->name('index');
        Route::get('/employee/{employeeId}', [LocationController::class, 'employee'])->name('employee');
        Route::get('/history', [LocationController::class, 'history'])->name('history');
    });

    // ========== إدارة النظام (مشرف عام) ==========
    Route::prefix('admin')->name('admin.')->group(function () {
        // الموظفين
        Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
        Route::get('/employees/{id}', [EmployeeController::class, 'show'])->name('employees.show');
        
        // الإدارات
        Route::get('/departments', [DepartmentController::class, 'index'])->name('departments.index');
        Route::get('/departments/{id}', [DepartmentController::class, 'show'])->name('departments.show');
        
        // تهيئة النظام
        Route::get('/settings', [SystemConfigController::class, 'index'])->name('settings.index');
        Route::post('/settings/update', [SystemConfigController::class, 'update'])->name('settings.update');
        
        // سجل التدقيق
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
        Route::get('/audit-logs/{id}', [AuditLogController::class, 'show'])->name('audit-logs.show');
        
        // الأدوار
        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
        Route::get('/roles/create', [RoleController::class, 'create'])->name('roles.create');
        Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
        Route::get('/roles/{id}', [RoleController::class, 'show'])->name('roles.show');
        Route::get('/roles/{id}/edit', [RoleController::class, 'edit'])->name('roles.edit');
        Route::put('/roles/{id}', [RoleController::class, 'update'])->name('roles.update');
        
        // الصلاحيات
        Route::get('/permissions', [PermissionController::class, 'index'])->name('permissions.index');
        Route::get('/permissions/{id}', [PermissionController::class, 'show'])->name('permissions.show');
        
        // ربط الموظفين بالأدوار
        Route::get('/employee-roles', [EmployeeRoleController::class, 'index'])->name('employee-roles.index');
        
        // ربط الأدوار بالصلاحيات
        Route::get('/role-permissions', [RolePermissionController::class, 'index'])->name('role-permissions.index');
    });

    // ========== تسجيل الخروج ==========
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
});

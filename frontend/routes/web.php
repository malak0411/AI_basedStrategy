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

// Controllers - Admin
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\DepartmentController;

// Controllers - Other
use App\Http\Controllers\KpiController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\RiskController;
use App\Http\Controllers\AiController;

// ============================================================
// تسجيل Middleware مخصص للتحقق من JWT
// ============================================================
Route::aliasMiddleware('check.jwt', CheckJwtToken::class);

// ============================================================
// الصفحة الرئيسية
// ============================================================
Route::get('/', function () {
    return redirect()->route('login');
});

// ============================================================
// المصادقة (زائر - بدون تسجيل دخول)
// ============================================================
Route::middleware('guest')->group(function () {
    
    // تسجيل الدخول
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.submit');
    
    // نسيت كلمة المرور
    Route::get('/forgot-password', [ForgotPasswordController::class, 'showForgotForm'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink'])->name('password.email');
});

// ============================================================
// لوحات التحكم والإدارة (محمية بـ JWT)
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
    Route::get('/tasks', [TaskController::class, 'index'])->name('tasks.index');
    Route::get('/tasks/create', [TaskController::class, 'create'])->name('tasks.create');
    Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::get('/tasks/{id}', [TaskController::class, 'show'])->name('tasks.show');
    Route::get('/tasks/{id}/edit', [TaskController::class, 'edit'])->name('tasks.edit');
    Route::put('/tasks/{id}', [TaskController::class, 'update'])->name('tasks.update');
    
    // ========== التخطيط الاستراتيجي ==========
    Route::prefix('strategic')->name('strategic.')->group(function () {
        Route::get('/pillars', [PillarController::class, 'index'])->name('pillars.index');
        Route::get('/goals', [GoalController::class, 'index'])->name('goals.index');
        Route::get('/programs', [ProgramController::class, 'index'])->name('programs.index');
        Route::get('/initiatives', [InitiativeController::class, 'index'])->name('initiatives.index');
        Route::get('/swot', [SwotController::class, 'index'])->name('swot.index');
        Route::get('/pestel', [PestelController::class, 'index'])->name('pestel.index');
    });
    
    // ========== مؤشرات الأداء ==========
    Route::get('/kpis', [KpiController::class, 'index'])->name('kpis.index');
    
    // ========== الميزانية ==========
    Route::get('/budget', [BudgetController::class, 'index'])->name('budget.index');
    
    // ========== المخاطر ==========
    Route::get('/risks', [RiskController::class, 'index'])->name('risks.index');
    
    // ========== توصيات الذكاء الاصطناعي ==========
    Route::get('/ai/recommendations', [AiController::class, 'recommendations'])->name('ai.recommendations');
    
    // ========== إدارة النظام (للمشرف العام) ==========
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
        Route::get('/employees/{id}', [EmployeeController::class, 'show'])->name('employees.show');
        Route::get('/departments', [DepartmentController::class, 'index'])->name('departments.index');
        Route::get('/departments/{id}', [DepartmentController::class, 'show'])->name('departments.show');
    });
    
    // ========== تسجيل الخروج ==========
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
});

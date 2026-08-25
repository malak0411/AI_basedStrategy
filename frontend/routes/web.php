<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\CheckJwtToken;

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ChangePasswordController;

use App\Http\Controllers\Dashboard\EmployeeDashboardController;
use App\Http\Controllers\Dashboard\ManagerDashboardController;
use App\Http\Controllers\Dashboard\MinisterDashboardController;

use App\Http\Controllers\Task\TaskController;
use App\Http\Controllers\OperationalTaskController;

use App\Http\Controllers\Strategic\PillarController;
use App\Http\Controllers\Strategic\GoalController;
use App\Http\Controllers\Strategic\ProgramController;
use App\Http\Controllers\Strategic\InitiativeController;
use App\Http\Controllers\Strategic\SwotController;
use App\Http\Controllers\Strategic\PestelController;
use App\Http\Controllers\Strategic\VisionController;

use App\Http\Controllers\KpiController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\RiskController;

use App\Http\Controllers\AiController;
use App\Http\Controllers\AiDashboardController;
use App\Http\Controllers\AiStrategicController;

use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\SystemConfigController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\EmployeeRoleController;
use App\Http\Controllers\Admin\RolePermissionController;

use App\Http\Controllers\Admin\Dictionary\DictStatusController;
use App\Http\Controllers\Admin\Dictionary\DictPriorityController;
use App\Http\Controllers\Admin\Dictionary\DictRiskLevelController;
use App\Http\Controllers\Admin\Dictionary\DictRoleTypeController;
use App\Http\Controllers\Admin\Dictionary\DictTransactionTypeController;

use App\Http\Controllers\ReportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\PerformanceController;

Route::aliasMiddleware('check.jwt', CheckJwtToken::class);

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.submit');
    Route::get('/forgot-password', [ForgotPasswordController::class, 'showForgotForm'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink'])->name('password.email');
});

Route::middleware('check.jwt')->group(function () {

    Route::get('/change-password', [ChangePasswordController::class, 'showChangeForm'])->name('password.change');
    Route::post('/change-password', [ChangePasswordController::class, 'changePassword'])->name('password.update');

    Route::get('/dashboard/employee', [EmployeeDashboardController::class, 'index'])->name('dashboard.employee');
    Route::get('/dashboard/manager', [ManagerDashboardController::class, 'index'])->name('dashboard.manager');
    Route::get('/dashboard/minister', [MinisterDashboardController::class, 'index'])->name('dashboard.minister');

    Route::prefix('tasks')->name('tasks.')->group(function () {
        Route::get('/', [TaskController::class, 'index'])->name('index');
        Route::get('/create', [TaskController::class, 'create'])->name('create');
        Route::post('/', [TaskController::class, 'store'])->name('store');
        Route::get('/{id}', [TaskController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [TaskController::class, 'edit'])->name('edit');
        Route::put('/{id}', [TaskController::class, 'update'])->name('update');
    });


    Route::middleware(['web', 'check.jwt'])->group(function () {

    Route::get('/operational/major-tasks', [OperationalTaskController::class, 'majorTasks'])->name('operational.major-tasks');

    Route::get('/operational/major-task/{id}', [OperationalTaskController::class, 'showMajorTask'])->name('operational.show-major-task');

    Route::get('/operational/dependencies/{majorTaskId}', [OperationalTaskController::class, 'dependencies'])->name('operational.dependencies');

    Route::get('/operational/dependencies/data/{majorTaskId}', [OperationalTaskController::class, 'getDependenciesData'])->name('operational.dependencies-data');

    Route::post('/operational/dependencies/add', [OperationalTaskController::class, 'addDependency'])
        ->name('operational.add-dependency');

    Route::put('/operational/dependencies/{id}', [OperationalTaskController::class, 'updateDependency'])
        ->name('operational.update-dependency');

    Route::delete('/operational/dependencies/{id}', [OperationalTaskController::class, 'deleteDependency'])
        ->name('operational.delete-dependency');

    Route::post('/operational/dependencies/check-cycle', [OperationalTaskController::class, 'checkCycle'])
        ->name('operational.check-cycle');

    Route::post('/operational/store', [OperationalTaskController::class, 'store'])
        ->name('operational.store');

    Route::put('/operational/tasks/{id}', [OperationalTaskController::class, 'update'])
        ->name('operational.update');

    Route::delete('/operational/tasks/{id}', [OperationalTaskController::class, 'destroy'])
        ->name('operational.destroy');

    Route::get('/operational/assign/{taskId}', [OperationalTaskController::class, 'assignEmployees'])
        ->name('operational.assign');

    Route::post('/operational/assign-task-to-employee', [OperationalTaskController::class, 'assignTaskToEmployee'])
        ->name('operational.assign-task-to-employee');

    Route::put('/operational/update-assignment-hours', [OperationalTaskController::class, 'updateAssignmentHours'])
        ->name('operational.update-assignment-hours');

    Route::delete('/operational/remove-assignment', [OperationalTaskController::class, 'removeTaskAssignment'])
        ->name('operational.remove-assignment');

    Route::post('/operational/finalize-task', [OperationalTaskController::class, 'finalizeTask'])
        ->name('operational.finalize-task');

    Route::get('/operational/kanban/{majorTaskId?}', [OperationalTaskController::class, 'kanbanBoard'])
        ->name('operational.kanban');

    Route::put('/operational/update-status', [OperationalTaskController::class, 'updateTaskStatus'])
        ->name('operational.update-status');

    Route::get('/operational/generate/{id}', [OperationalTaskController::class, 'generate'])
        ->name('operational.generate');

    Route::post('/operational/start-generation', [OperationalTaskController::class, 'startGeneration'])
        ->name('operational.start-generation');

    Route::get('/operational/waiting', [OperationalTaskController::class, 'waiting'])
        ->name('operational.waiting');

    Route::get('/operational/review', [OperationalTaskController::class, 'review'])
        ->name('operational.review');

    Route::post('/operational/edit-with-prompt', [OperationalTaskController::class, 'editWithPrompt'])
        ->name('operational.edit-with-prompt');

    Route::post('/operational/approve', [OperationalTaskController::class, 'approve'])
        ->name('operational.approve');

    Route::get('/operational/task/{id}', [OperationalTaskController::class, 'showOperationalTask'])
        ->name('operational.task-detail');
});


    Route::get('/operational/kanban', [OperationalTaskController::class, 'kanbanBoard'])->name('operational.kanban');
    Route::get('/operational/kanban/{id}', [OperationalTaskController::class, 'showKanbanTask'])->name('operational.kanban');
    Route::get('/operational/task/{id}', [OperationalTaskController::class, 'showoperationalTask'])->name('operational.task-detail');
    Route::prefix('operational')->name('operational.')->group(function () {
        Route::get('/major-tasks', [OperationalTaskController::class, 'majorTasks'])->name('major-tasks');
        Route::get('/major-task/{id}', [OperationalTaskController::class, 'showMajorTask'])->name('show-major-task');
        Route::post('/store', [OperationalTaskController::class, 'store'])->name('store');
        Route::put('/update/{id}', [OperationalTaskController::class, 'update'])->name('update');
        Route::delete('/destroy/{id}', [OperationalTaskController::class, 'destroy'])->name('destroy');
        Route::get('/generate/{majorTaskId}', [OperationalTaskController::class, 'generate'])->name('generate');
        Route::post('/start-generation', [OperationalTaskController::class, 'startGeneration'])->name('start-generation');
        Route::get('/waiting', [OperationalTaskController::class, 'waiting'])->name('waiting');
        Route::get('/review', [OperationalTaskController::class, 'review'])->name('review');
        Route::post('/edit-prompt', [OperationalTaskController::class, 'editWithPrompt'])->name('edit-prompt');
        Route::post('/approve', [OperationalTaskController::class, 'approve'])->name('approve');
        Route::get('/api/employees/department/{id}', [OperationalTaskController::class, 'getDepartmentEmployees']);
        
    });

    Route::prefix('strategic')->name('strategic.')->group(function () {
        Route::get('/vision', [VisionController::class, 'index'])->name('vision.index');
        Route::put('/vision', [VisionController::class, 'update'])->name('vision.update');

        Route::get('/pillars', [PillarController::class, 'index'])->name('pillars.index');
        Route::get('/pillars/{id}', [PillarController::class, 'show'])->name('pillars.show');
        Route::post('/pillars', [PillarController::class, 'store'])->name('pillars.store');
        Route::put('/pillars/{id}', [PillarController::class, 'update'])->name('pillars.update');
        Route::delete('/pillars/{id}', [PillarController::class, 'destroy'])->name('pillars.destroy');

        Route::get('/goals', [GoalController::class, 'index'])->name('goals.index');
        Route::get('/goals/create', [GoalController::class, 'create'])->name('goals.create');
        Route::post('/goals', [GoalController::class, 'store'])->name('goals.store');
        Route::get('/goals/{id}', [GoalController::class, 'show'])->name('goals.show');
        Route::get('/goals/{id}/edit', [GoalController::class, 'edit'])->name('goals.edit');
        Route::put('/goals/{id}', [GoalController::class, 'update'])->name('goals.update');
        Route::delete('/goals/{id}', [GoalController::class, 'destroy'])->name('goals.destroy');

        Route::get('/programs', [ProgramController::class, 'index'])->name('programs.index');
        Route::get('/programs/create', [ProgramController::class, 'create'])->name('programs.create');
        Route::post('/programs', [ProgramController::class, 'store'])->name('programs.store');
        Route::get('/programs/{id}', [ProgramController::class, 'show'])->name('programs.show');
        Route::get('/programs/{id}/edit', [ProgramController::class, 'edit'])->name('programs.edit');
        Route::put('/programs/{id}', [ProgramController::class, 'update'])->name('programs.update');
        Route::delete('/programs/{id}', [ProgramController::class, 'destroy'])->name('programs.destroy');

        Route::get('/initiatives', [InitiativeController::class, 'index'])->name('initiatives.index');
        Route::get('/initiatives/create', [InitiativeController::class, 'create'])->name('initiatives.create');
        Route::post('/initiatives', [InitiativeController::class, 'store'])->name('initiatives.store');
        Route::get('/initiatives/{id}', [InitiativeController::class, 'show'])->name('initiatives.show');
        Route::get('/initiatives/{id}/edit', [InitiativeController::class, 'edit'])->name('initiatives.edit');
        Route::put('/initiatives/{id}', [InitiativeController::class, 'update'])->name('initiatives.update');
        Route::delete('/initiatives/{id}', [InitiativeController::class, 'destroy'])->name('initiatives.destroy');

        Route::get('/swot', [SwotController::class, 'index'])->name('swot.index');
        Route::get('/swot/edit', [SwotController::class, 'edit'])->name('swot.edit');
        Route::put('/swot', [SwotController::class, 'update'])->name('swot.update');

        Route::get('/pestel', [PestelController::class, 'index'])->name('pestel.index');
        Route::get('/pestel/edit', [PestelController::class, 'edit'])->name('pestel.edit');
        Route::put('/pestel', [PestelController::class, 'update'])->name('pestel.update');
    });

    Route::prefix('kpis')->name('kpis.')->group(function () {
        Route::get('/', [KpiController::class, 'index'])->name('index');
        Route::get('/create', [KpiController::class, 'create'])->name('create');
        Route::post('/', [KpiController::class, 'store'])->name('store');
        Route::get('/{id}', [KpiController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [KpiController::class, 'edit'])->name('edit');
        Route::put('/{id}', [KpiController::class, 'update'])->name('update');
        Route::delete('/{id}', [KpiController::class, 'destroy'])->name('destroy');
        Route::get('/{id}/measurements', [KpiController::class, 'measurements'])->name('measurements');
        Route::get('/{id}/measurements/create', [KpiController::class, 'createMeasurement'])->name('measurements.create');
        Route::post('/{id}/measurements', [KpiController::class, 'storeMeasurement'])->name('measurements.store');
    });

    Route::prefix('budget')->name('budget.')->group(function () {
        Route::get('/', [BudgetController::class, 'index'])->name('index');
        Route::get('/create', [BudgetController::class, 'create'])->name('create');
        Route::post('/', [BudgetController::class, 'store'])->name('store');
        Route::get('/{id}', [BudgetController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [BudgetController::class, 'edit'])->name('edit');
        Route::put('/{id}', [BudgetController::class, 'update'])->name('update');
        Route::get('/transactions', [BudgetController::class, 'transactions'])->name('transactions');
        Route::get('/transactions/create', [BudgetController::class, 'createTransaction'])->name('transactions.create');
        Route::post('/transactions', [BudgetController::class, 'storeTransaction'])->name('transactions.store');
        Route::get('/transactions/{id}', [BudgetController::class, 'showTransaction'])->name('transactions.show');
    });

    Route::prefix('risks')->name('risks.')->group(function () {
        Route::get('/', [RiskController::class, 'index'])->name('index');
        Route::get('/create', [RiskController::class, 'create'])->name('create');
        Route::post('/', [RiskController::class, 'store'])->name('store');
        Route::get('/{id}', [RiskController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [RiskController::class, 'edit'])->name('edit');
        Route::put('/{id}', [RiskController::class, 'update'])->name('update');
        Route::get('/{id}/mitigations', [RiskController::class, 'mitigations'])->name('mitigations');
        Route::get('/{id}/mitigations/create', [RiskController::class, 'createMitigation'])->name('mitigations.create');
        Route::post('/{id}/mitigations', [RiskController::class, 'storeMitigation'])->name('mitigations.store');
    });

    Route::prefix('ai')->name('ai.')->group(function () {
        Route::get('/dashboard', [AiDashboardController::class, 'index'])->name('dashboard');
        Route::get('/predict-all', [AiDashboardController::class, 'predictAll'])->name('predict-all');
        Route::get('/train-now', [AiDashboardController::class, 'trainNow'])->name('train-now');
        Route::get('/toggle-scheduler', [AiDashboardController::class, 'toggleScheduler'])->name('toggle-scheduler');
        Route::get('/models', [AiController::class, 'models'])->name('models');
        Route::get('/models/{id}', [AiController::class, 'showModel'])->name('models.show');
        Route::get('/predictions', [AiController::class, 'predictions'])->name('predictions');
        Route::get('/predictions/{id}', [AiController::class, 'showPrediction'])->name('predictions.show');
        Route::get('/recommendations', [AiController::class, 'recommendations'])->name('recommendations');
        Route::get('/recommendations/{id}', [AiController::class, 'showRecommendation'])->name('recommendations.show');

        Route::prefix('strategic')->name('strategic.')->group(function () {
            Route::get('/', [AiStrategicController::class, 'index'])->name('index');
            Route::get('/context/{id}', [AiStrategicController::class, 'getInitiativeContext'])->name('context');
            Route::post('/generate', [AiStrategicController::class, 'generate'])->name('generate');
            Route::get('/waiting', [AiStrategicController::class, 'waiting'])->name('waiting');
            Route::post('/edit-prompt', [AiStrategicController::class, 'editWithPrompt'])->name('edit-prompt');
            Route::get('/review', [AiStrategicController::class, 'review'])->name('review');
            Route::post('/edit-plan', [AiStrategicController::class, 'editPlan'])->name('edit-plan');
            Route::post('/approve', [AiStrategicController::class, 'approve'])->name('approve');
        });
    });

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::prefix('location-tracking')->name('location.')->group(function () {
        Route::get('/', [LocationController::class, 'index'])->name('index');
        Route::get('/employee/{employeeId}', [LocationController::class, 'employee'])->name('employee');
        Route::get('/history', [LocationController::class, 'history'])->name('history');
    });

    Route::prefix('performance')->name('performance.')->group(function () {
        Route::get('/', [PerformanceController::class, 'index'])->name('index');
        Route::get('/employee/{id}', [PerformanceController::class, 'employee'])->name('employee');
        Route::get('/department/{id}', [PerformanceController::class, 'department'])->name('department');
    });

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
        Route::get('/employees/{id}', [EmployeeController::class, 'show'])->name('employees.show');
        Route::get('/employees/{id}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
        Route::put('/employees/{id}', [EmployeeController::class, 'update'])->name('employees.update');

        Route::get('/departments', [DepartmentController::class, 'index'])->name('departments.index');
        Route::get('/departments/create', [DepartmentController::class, 'create'])->name('departments.create');
        Route::post('/departments', [DepartmentController::class, 'store'])->name('departments.store');
        Route::get('/departments/{id}', [DepartmentController::class, 'show'])->name('departments.show');
        Route::get('/departments/{id}/edit', [DepartmentController::class, 'edit'])->name('departments.edit');
        Route::put('/departments/{id}', [DepartmentController::class, 'update'])->name('departments.update');
        Route::delete('/departments/{id}', [DepartmentController::class, 'destroy'])->name('departments.destroy');

        Route::get('/settings', [SystemConfigController::class, 'index'])->name('settings.index');
        Route::post('/settings', [SystemConfigController::class, 'store'])->name('settings.store');
        Route::post('/settings/update', [SystemConfigController::class, 'update'])->name('settings.update');
        Route::delete('/settings/{config_key}', [SystemConfigController::class, 'destroy'])->name('settings.destroy');

        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
        Route::get('/audit-logs/{id}', [AuditLogController::class, 'show'])->name('audit-logs.show');

        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
        Route::get('/roles/create', [RoleController::class, 'create'])->name('roles.create');
        Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
        Route::get('/roles/{id}', [RoleController::class, 'show'])->name('roles.show');
        Route::get('/roles/{id}/edit', [RoleController::class, 'edit'])->name('roles.edit');
        Route::put('/roles/{id}', [RoleController::class, 'update'])->name('roles.update');
        Route::delete('/roles/{id}', [RoleController::class, 'destroy'])->name('roles.destroy');

        Route::get('/permissions', [PermissionController::class, 'index'])->name('permissions.index');
        Route::get('/permissions/{id}', [PermissionController::class, 'show'])->name('permissions.show');

        Route::get('/employee-roles', [EmployeeRoleController::class, 'index'])->name('employee-roles.index');
        Route::post('/employee-roles', [EmployeeRoleController::class, 'store'])->name('employee-roles.store');
        Route::delete('/employee-roles', [EmployeeRoleController::class, 'destroy'])->name('employee-roles.destroy');

        Route::get('/role-permissions', [RolePermissionController::class, 'index'])->name('role-permissions.index');
        Route::post('/role-permissions', [RolePermissionController::class, 'store'])->name('role-permissions.store');
        Route::delete('/role-permissions', [RolePermissionController::class, 'destroy'])->name('role-permissions.destroy');

        Route::prefix('dictionaries')->name('dictionaries.')->group(function () {
            Route::get('/statuses', [DictStatusController::class, 'index'])->name('statuses');
            Route::post('/statuses', [DictStatusController::class, 'store'])->name('statuses.store');
            Route::put('/statuses/{id}', [DictStatusController::class, 'update'])->name('statuses.update');
            Route::delete('/statuses/{id}', [DictStatusController::class, 'destroy'])->name('statuses.destroy');

            Route::get('/priorities', [DictPriorityController::class, 'index'])->name('priorities');
            Route::post('/priorities', [DictPriorityController::class, 'store'])->name('priorities.store');
            Route::put('/priorities/{id}', [DictPriorityController::class, 'update'])->name('priorities.update');
            Route::delete('/priorities/{id}', [DictPriorityController::class, 'destroy'])->name('priorities.destroy');

            Route::get('/risk-levels', [DictRiskLevelController::class, 'index'])->name('risk-levels');
            Route::post('/risk-levels', [DictRiskLevelController::class, 'store'])->name('risk-levels.store');
            Route::put('/risk-levels/{id}', [DictRiskLevelController::class, 'update'])->name('risk-levels.update');
            Route::delete('/risk-levels/{id}', [DictRiskLevelController::class, 'destroy'])->name('risk-levels.destroy');

            Route::get('/role-types', [DictRoleTypeController::class, 'index'])->name('role-types');
            Route::post('/role-types', [DictRoleTypeController::class, 'store'])->name('role-types.store');
            Route::put('/role-types/{id}', [DictRoleTypeController::class, 'update'])->name('role-types.update');
            Route::delete('/role-types/{id}', [DictRoleTypeController::class, 'destroy'])->name('role-types.destroy');

            Route::get('/transaction-types', [DictTransactionTypeController::class, 'index'])->name('transaction-types');
            Route::post('/transaction-types', [DictTransactionTypeController::class, 'store'])->name('transaction-types.store');
            Route::put('/transaction-types/{id}', [DictTransactionTypeController::class, 'update'])->name('transaction-types.update');
            Route::delete('/transaction-types/{id}', [DictTransactionTypeController::class, 'destroy'])->name('transaction-types.destroy');
        });
    });

    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
});

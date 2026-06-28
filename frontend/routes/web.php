<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Controllers\Dashboard\EmployeeDashboardController;
use App\Http\Controllers\Dashboard\ManagerDashboardController;
use App\Http\Middleware\CheckJwtToken;

// تسجيل الـ Middleware المخصص (إذا لم يكن Kernel موجوداً)
Route::aliasMiddleware('check.jwt', CheckJwtToken::class);

// الصفحة الرئيسية
Route::get('/', function () {
    return redirect()->route('login');
});

// المصادقة (بدون تسجيل دخول)
Route::middleware(['guest'])->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.submit');
    Route::get('/forgot-password', [ForgotPasswordController::class, 'showForgotForm'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink'])->name('password.email');
});

// لوحات التحكم (محمية بـ JWT فقط)
Route::middleware(['check.jwt'])->group(function () {
    
    // تغيير كلمة المرور
    Route::get('/change-password', [ChangePasswordController::class, 'showChangeForm'])->name('password.change');
    Route::post('/change-password', [ChangePasswordController::class, 'changePassword'])->name('password.update');
    
    // لوحة تحكم الموظف
    Route::get('/dashboard/employee', [EmployeeDashboardController::class, 'index'])->name('dashboard.employee');
    
    // لوحة تحكم المدير
    Route::get('/dashboard/manager', [ManagerDashboardController::class, 'index'])->name('dashboard.manager');
    
    // تسجيل الخروج
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
});

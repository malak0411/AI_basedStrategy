<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\ApiClient;

class RoleMiddleware
{
    /**
     * التحقق من صلاحيات المستخدم
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        try {
            $token = session('jwt_token');
            $userRole = session('user_role');
            
            // التحقق من وجود الجلسة
            if (!$token || !$userRole) {
                return redirect()->route('login')
                    ->with('error', 'يجب تسجيل الدخول أولاً');
            }
            
            // التحقق من الصلاحية
            if (!in_array($userRole, $roles)) {
                $redirectPath = $this->getDefaultRoute($userRole);
                return redirect($redirectPath)
                    ->with('error', 'ليس لديك صلاحية للوصول إلى هذه الصفحة');
            }
            
            // مشاركة البيانات المشتركة
            $this->shareCommonData($token);
            
            return $next($request);
            
        } catch (\Exception $e) {
            \Log::error('RoleMiddleware Error: ' . $e->getMessage());
            return redirect()->route('login')
                ->with('error', 'حدث خطأ في التحقق من الصلاحيات');
        }
    }
    
    /**
     * تحديد المسار الافتراضي حسب الصلاحية
     */
    private function getDefaultRoute($role)
    {
        return match($role) {
            'minister', 'deputy', 'general_manager' => '/dashboard/minister',
            'manager' => '/dashboard/manager',
            'employee' => '/dashboard/employee',
            default => '/dashboard/employee'
        };
    }
    
    /**
     * مشاركة البيانات المشتركة بين جميع الواجهات
     */
    private function shareCommonData($token)
    {
        try {
            $apiClient = new ApiClient();
            
            // معلومات المستخدم الحالي
            $userResponse = $apiClient->get('/api/auth/me', $token);
            if (isset($userResponse['data'])) {
                view()->share('currentUser', $userResponse['data']);
            }
            
            // عدد الإشعارات غير المقروءة
            try {
                $notifResponse = $apiClient->get('/api/notifications/unread-count', $token);
                view()->share('unreadNotifications', $notifResponse['data']['count'] ?? 0);
            } catch (\Exception $e) {
                view()->share('unreadNotifications', 0);
            }
            
        } catch (\Exception $e) {
            \Log::error('ShareCommonData Error: ' . $e->getMessage());
        }
    }
}

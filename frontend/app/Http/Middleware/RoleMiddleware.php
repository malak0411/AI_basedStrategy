<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\ApiClient;

class RoleMiddleware
{
    
    public function handle(Request $request, Closure $next, ...$roles)
    {
        try {
            $token = session('jwt_token');
            $userRole = session('user_role');
            

            if (!$token || !$userRole) {
                return redirect()->route('login')
                    ->with('error', 'يجب تسجيل الدخول أولاً');
            }
            

            if (!in_array($userRole, $roles)) {
                $redirectPath = $this->getDefaultRoute($userRole);
                return redirect($redirectPath)
                    ->with('error', 'ليس لديك صلاحية للوصول إلى هذه الصفحة');
            }
            

            $this->shareCommonData($token);
            
            return $next($request);
            
        } catch (\Exception $e) {
            \Log::error('RoleMiddleware Error: ' . $e->getMessage());
            return redirect()->route('login')
                ->with('error', 'حدث خطأ في التحقق من الصلاحيات');
        }
    }
    
    
    private function getDefaultRoute($role)
    {
        return match($role) {
            'minister', 'deputy', 'general_manager' => '/dashboard/minister',
            'manager' => '/dashboard/manager',
            'employee' => '/dashboard/employee',
            default => '/dashboard/employee'
        };
    }
    
    
    private function shareCommonData($token)
    {
        try {
            $apiClient = new ApiClient();
            

            $userResponse = $apiClient->get('/api/auth/me', $token);
            if (isset($userResponse['data'])) {
                view()->share('currentUser', $userResponse['data']);
            }
            

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

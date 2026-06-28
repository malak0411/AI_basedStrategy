<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\ApiClient;

class ManagerDashboardController extends Controller
{
    protected $apiClient;

    public function __construct()
    {
        $this->apiClient = new ApiClient();
    }

    /**
     * عرض لوحة تحكم المدير
     */
    public function index()
    {
        $token = session('jwt_token');
        $role = session('user_role');
        
        if (!$token) {
            return redirect()->route('login')->with('error', 'يجب تسجيل الدخول أولاً');
        }

        // السماح فقط للمدير والصلاحيات الأعلى
        $allowedRoles = ['manager', 'general_manager', 'deputy', 'minister'];
        if (!in_array($role, $allowedRoles)) {
            return redirect('/dashboard/employee')
                ->with('error', 'ليس لديك صلاحية للوصول إلى هذه الصفحة');
        }

        try {
            // جلب إحصائيات المدير
            $stats = $this->fetchData('/api/dashboard/manager', $token, $this->defaultStats());
            
            // جلب مهام الإدارة
            $tasks = $this->fetchData('/api/tasks/department', $token, [], ['limit' => 10]);
            
            // جلب الموظفين
            $employees = $this->fetchData('/api/employees/department', $token, []);
            
            // جلب مؤشرات الأداء
            $kpis = $this->fetchData('/api/kpis/department', $token, []);

            return view('dashboard.manager', compact('stats', 'tasks', 'employees', 'kpis'));

        } catch (\Exception $e) {
            \Log::error('Manager Dashboard Error: ' . $e->getMessage());
            
            return view('dashboard.manager', [
                'stats' => $this->defaultStats(),
                'tasks' => [],
                'employees' => [],
                'kpis' => [],
                'error' => 'عذراً، حدث خطأ في تحميل البيانات'
            ]);
        }
    }

    /**
     * جلب البيانات من API مع قيمة افتراضية
     */
    private function fetchData($endpoint, $token, $default, $params = [])
    {
        try {
            $response = $this->apiClient->get($endpoint, $token, $params);
            return $response['data'] ?? $default;
        } catch (\Exception $e) {
            return $default;
        }
    }

    /**
     * إحصائيات افتراضية
     */
    private function defaultStats()
    {
        return [
            'department_name' => session('user_department', 'الإدارة'),
            'total_employees' => 0,
            'active_tasks' => 0,
            'completed_tasks' => 0,
            'delayed_tasks' => 0,
            'completion_rate' => 0,
            'total_tasks' => 0,
        ];
    }
}

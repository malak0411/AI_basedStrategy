<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\ApiClient;

class MinisterDashboardController extends Controller
{
    protected $apiClient;

    public function __construct()
    {
        $this->apiClient = new ApiClient();
    }

    public function index()
    {
        $token = session('jwt_token');
        $role = session('user_role');

        if (!$token) {
            return redirect()->route('login')->with('error', 'يجب تسجيل الدخول أولاً');
        }

        // السماح للقيادات فقط
        if (!in_array($role, ['minister', 'deputy', 'general_manager'])) {
            return redirect()->route('dashboard.employee')
                ->with('error', 'ليس لديك صلاحية للوصول إلى هذه الصفحة');
        }

        try {
            $stats = $this->fetchData('/api/dashboard/minister', $token, [
                'total_employees' => 0,
                'total_departments' => 0,
                'active_projects' => 0,
                'budget_utilization' => 0,
                'completion_rate' => 0,
                'delayed_tasks' => 0,
            ]);

            $departments = $this->fetchData('/api/dashboard/departments-performance', $token, []);
            $pillars = $this->fetchData('/api/strategic/pillars', $token, []);
            $delayedTasks = $this->fetchData('/api/tasks/delayed', $token, []);

            return view('dashboard.minister', compact(
                'stats', 'departments', 'pillars', 'delayedTasks'
            ));

        } catch (\Exception $e) {
            \Log::error('Minister Dashboard Error: ' . $e->getMessage());
            return view('dashboard.minister', [
                'stats' => [],
                'departments' => [],
                'pillars' => [],
                'delayedTasks' => [],
                'error' => 'عذراً، حدث خطأ في تحميل البيانات'
            ]);
        }
    }

    private function fetchData($endpoint, $token, $default = [])
    {
        try {
            $response = $this->apiClient->get($endpoint, $token);
            return $response['data'] ?? $default;
        } catch (\Exception $e) {
            return $default;
        }
    }
}

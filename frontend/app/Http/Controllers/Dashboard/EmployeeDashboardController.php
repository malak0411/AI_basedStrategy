<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\ApiClient;

class EmployeeDashboardController extends Controller
{
    protected $apiClient;

    public function __construct()
    {
        $this->apiClient = new ApiClient();
    }

    /**
     * عرض لوحة تحكم الموظف
     */
    public function index()
    {
        $token = session('jwt_token');

        if (!$token) {
            return redirect()->route('login')->with('error', 'يجب تسجيل الدخول أولاً');
        }

        try {
            // جلب إحصائيات الموظف
            $stats = $this->fetchData('/api/dashboard/employee', $token, [
                'total_tasks' => 0,
                'completed_tasks' => 0,
                'in_progress_tasks' => 0,
                'delayed_tasks' => 0,
                'completion_rate' => 0,
            ]);

            // جلب مهام الموظف الحالية
            $tasks = $this->fetchData('/api/tasks/my-tasks', $token, []);

            return view('dashboard.employee', compact('stats', 'tasks'));

        } catch (\Exception $e) {
            \Log::error('Employee Dashboard Error: ' . $e->getMessage());

            return view('dashboard.employee', [
                'stats' => $this->defaultStats(),
                'tasks' => [],
                'error' => 'عذراً، حدث خطأ في تحميل البيانات'
            ]);
        }
    }

    /**
     * جلب البيانات من API مع قيمة افتراضية
     */
    private function fetchData($endpoint, $token, $default = [])
    {
        try {
            $response = $this->apiClient->get($endpoint, $token);
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
            'total_tasks' => 0,
            'completed_tasks' => 0,
            'in_progress_tasks' => 0,
            'delayed_tasks' => 0,
            'completion_rate' => 0,
        ];
    }
}

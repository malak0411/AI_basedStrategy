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
            // جلب بيانات الموظف
            $statsResponse = $this->apiClient->get('/api/dashboard/employee', $token);
            $stats = $statsResponse['data'] ?? [];

            // جلب مهام الموظف
            $tasksResponse = $this->apiClient->get('/api/tasks/my-tasks', $token);
            $tasks = $tasksResponse['data'] ?? [];

            return view('dashboard.employee', compact('stats', 'tasks'));

        } catch (\Exception $e) {
            \Log::error('Employee Dashboard Error: ' . $e->getMessage());
            
            return view('dashboard.employee', [
                'stats' => [],
                'tasks' => [],
                'error' => 'حدث خطأ في تحميل البيانات'
            ]);
        }
    }
}

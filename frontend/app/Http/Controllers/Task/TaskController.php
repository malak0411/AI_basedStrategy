<?php

namespace App\Http\Controllers\Task;

use App\Http\Controllers\Controller;
use App\Services\ApiClient;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    protected $apiClient;

    public function __construct()
    {
        $this->apiClient = new ApiClient();
    }

    /**
     * عرض قائمة المهام
     */
    public function index(Request $request)
    {
        $token = session('jwt_token');
        if (!$token) {
            return redirect()->route('login')->with('error', 'يجب تسجيل الدخول');
        }

        $role = session('user_role', 'employee');

        try {
            // نختار الـ endpoint حسب الصلاحية
            $endpoint = match (true) {
                in_array($role, ['minister', 'deputy', 'general_manager']) => '/api/tasks/all',
                $role === 'manager' => '/api/tasks/department',
                default => '/api/tasks/my-tasks',
            };

            $response = $this->apiClient->get($endpoint, $token, [
                'limit' => 20,
                'offset' => $request->query('page', 1) - 1,
            ]);

            $tasks = $response['data'] ?? [];

            return view('tasks.index', compact('tasks'));

        } catch (\Exception $e) {
            \Log::error('Task Index Error: ' . $e->getMessage());
            return view('tasks.index', [
                'tasks' => [],
                'error' => 'تعذر تحميل المهام'
            ]);
        }
    }

    /**
     * عرض تفاصيل مهمة
     */
    public function show($id)
    {
        $token = session('jwt_token');
        if (!$token) {
            return redirect()->route('login')->with('error', 'يجب تسجيل الدخول');
        }

        try {
            $response = $this->apiClient->get("/api/tasks/{$id}", $token);
            $task = $response['data'] ?? null;

            if (!$task) {
                return redirect()->route('tasks.index')->with('error', 'المهمة غير موجودة');
            }

            return view('tasks.show', compact('task'));

        } catch (\Exception $e) {
            \Log::error('Task Show Error: ' . $e->getMessage());
            return redirect()->route('tasks.index')->with('error', 'حدث خطأ في تحميل المهمة');
        }
    }
}

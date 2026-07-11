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

    // ============================================================
    // INDEX - قائمة المهام
    // ============================================================
    public function index(Request $request)
    {
        $token = session('jwt_token');
        if (!$token) return redirect()->route('login')->with('error', 'يجب تسجيل الدخول');

        $role = session('user_role', 'employee');
        $endpoint = match (true) {
            in_array($role, ['minister', 'deputy', 'general_manager']) => '/api/tasks/all',
            $role === 'manager' => '/api/tasks/department',
            default => '/api/tasks/my-tasks',
        };

        $response = $this->apiClient->get($endpoint, $token, ['limit' => 20]);
        $tasks = $response['data'] ?? [];

        return view('tasks.index', compact('tasks'));
    }

    // ============================================================
    // SHOW - تفاصيل مهمة
    // ============================================================
    public function show($id)
    {
        $token = session('jwt_token');
        if (!$token) return redirect()->route('login');

        $response = $this->apiClient->get("/api/tasks/{$id}", $token);
        $task = $response['data'] ?? null;

        if (!$task) return redirect()->route('tasks.index')->with('error', 'المهمة غير موجودة');

        // جلب توصيات AI
        $aiResponse = $this->apiClient->get("/api/ai/recommendations/{$id}", $token);
        $aiRecommendations = $aiResponse['data']['recommendations'] ?? [];

        // جلب تنبؤ AI
        $predictResponse = $this->apiClient->get("/api/ai/predict-delay/{$id}", $token);
        $prediction = $predictResponse['data'] ?? null;

        return view('tasks.show', compact('task', 'aiRecommendations', 'prediction'));
    }

    // ============================================================
    // CREATE - عرض صفحة الإنشاء
    // ============================================================
    public function create()
    {
        $token = session('jwt_token');
        
        // جلب المهام الرئيسية للاختيار
        $majorTasks = [];
        $departments = [];
        
        try {
            $majorResponse = $this->apiClient->get('/api/strategic/pillars', $token);
            $majorTasks = $majorResponse['data'] ?? [];
            
            $deptResponse = $this->apiClient->get('/api/departments', $token);
            $departments = $deptResponse['data'] ?? [];
        } catch (\Exception $e) {}

        return view('tasks.create', compact('majorTasks', 'departments'));
    }

    // ============================================================
    // STORE - تخزين مهمة جديدة
    // ============================================================
    public function store(Request $request)
    {
        $token = session('jwt_token');
        if (!$token) return redirect()->route('login')->with('error', 'يجب تسجيل الدخول');

        $request->validate([
            'title' => 'required|string|max:255',
            'major_task_id' => 'required|integer',
            'department_id' => 'required|integer',
            'end_date' => 'nullable|date',
        ]);

        $data = [
            'major_task_id' => (int) $request->major_task_id,
            'department_id' => (int) $request->department_id,
            'title' => $request->title,
            'description' => $request->description ?? '',
            'priority_id' => (int) ($request->priority_id ?? 2),
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'estimated_hours' => (float) ($request->estimated_hours ?? 0),
            'is_cross_functional' => $request->has('is_cross_functional'),
            'assigned_employees' => $request->assigned_employees ?? [],
        ];

        $response = $this->apiClient->post('/api/tasks/', $data, $token);

        if ($response['success'] ?? false) {
            return redirect()->route('tasks.index')->with('success', 'تم إنشاء المهمة بنجاح');
        }

        return back()->with('error', $response['detail'] ?? 'فشل إنشاء المهمة')->withInput();
    }

    // ============================================================
    // EDIT - عرض صفحة التعديل
    // ============================================================
    public function edit($id)
    {
        $token = session('jwt_token');
        if (!$token) return redirect()->route('login');

        $response = $this->apiClient->get("/api/tasks/{$id}", $token);
        $task = $response['data'] ?? [];

        if (empty($task)) return redirect()->route('tasks.index')->with('error', 'المهمة غير موجودة');

        // جلب الإدارات للاختيار
        $departments = [];
        try {
            $deptResponse = $this->apiClient->get('/api/departments', $token);
            $departments = $deptResponse['data'] ?? [];
        } catch (\Exception $e) {}

        return view('tasks.edit', compact('task', 'departments'));
    }

    // ============================================================
    // UPDATE - تحديث مهمة
    // ============================================================
    public function update(Request $request, $id)
    {
        $token = session('jwt_token');
        if (!$token) return redirect()->route('login')->with('error', 'يجب تسجيل الدخول');

        $data = [];
        if ($request->filled('title')) $data['title'] = $request->title;
        if ($request->filled('description')) $data['description'] = $request->description;
        if ($request->filled('status_id')) $data['status_id'] = (int) $request->status_id;
        if ($request->filled('priority_id')) $data['priority_id'] = (int) $request->priority_id;
        if ($request->filled('department_id')) $data['department_id'] = (int) $request->department_id;
        if ($request->filled('end_date')) $data['end_date'] = $request->end_date;
        if ($request->filled('start_date')) $data['start_date'] = $request->start_date;
        if ($request->filled('estimated_hours')) $data['estimated_hours'] = (float) $request->estimated_hours;
        if ($request->filled('actual_hours')) $data['actual_hours'] = (float) $request->actual_hours;
        if ($request->has('is_cross_functional')) $data['is_cross_functional'] = $request->has('is_cross_functional');

        $response = $this->apiClient->put("/api/tasks/{$id}", $data, $token);

        if ($response['success'] ?? false) {
            return redirect()->route('tasks.show', $id)->with('success', 'تم تحديث المهمة بنجاح');
        }

        return back()->with('error', $response['detail'] ?? 'فشل تحديث المهمة');
    }
}

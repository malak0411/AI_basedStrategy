<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\Http\Request;

class OperationalTaskController extends Controller
{
    protected $apiClient;

    public function __construct()
    {
        $this->apiClient = new ApiClient();
    }

    private function isManager(): bool
    {
        $role = session('user_role', 'employee');
        return in_array($role, ['manager', 'general_manager', 'deputy', 'minister']);
    }

    private function getDepartmentId(): int
{
    $deptId = session('user_department_id');
    
    if ($deptId && (int)$deptId > 0) {
        return (int)$deptId;
    }
    
    $token = session('jwt_token');
    $me = $this->apiClient->get('/api/auth/me', $token);
    $deptId = $me['data']['department_id'] ?? 0;
    
    session(['user_department_id' => (int)$deptId]);
    
    return (int)$deptId;
}


    public function majorTasks()
    {
        if (!$this->isManager()) {
            return redirect()->route('dashboard.employee')->with('error', 'غير مصرح');
        }

        $token = session('jwt_token');
        $response = $this->apiClient->get('/api/tasks/major-tasks/by-department', $token);
        $tasks = $response['data'] ?? [];
        return view('operational.major-tasks', compact('tasks'));
    }

    public function showMajorTask($id)
    {
        if (!$this->isManager()) {
            return redirect()->route('dashboard.employee')->with('error', 'غير مصرح');
        }

        $token = session('jwt_token');

        $majorTaskResponse = $this->apiClient->get("/api/tasks/major-tasks/{$id}/details", $token);
        $majorTask = $majorTaskResponse['data'] ?? [];

        $operationalResponse = $this->apiClient->get("/api/tasks/by-major-task/{$id}", $token);
        $operationalTasks = $operationalResponse['data'] ?? [];

        $departmentId = $this->getDepartmentId();

        $employeesResponse = $this->apiClient->get("/api/employees/department/{$departmentId}", $token);
        $employees = $employeesResponse['data'] ?? [];

        $roleTypesResponse = $this->apiClient->get('/api/tasks/role-types', $token);
        $roleTypes = $roleTypesResponse['data'] ?? [];

        $prioritiesResponse = $this->apiClient->get('/api/dict/priorities', $token);
        $priorities = $prioritiesResponse['data'] ?? [];

        $departments = $this->apiClient->safeGet('/api/departments', $token, []);

        return view('operational.show-major-task', compact(
            'majorTask', 'operationalTasks', 'departments', 'employees',
            'roleTypes', 'priorities', 'departmentId'
        ));
    }

    public function storeOperational(Request $request)
    {
        if (!$this->isManager()) {
            return back()->with('error', 'غير مصرح');
        }

        $token = session('jwt_token');

        $isCross = count($request->assigned_employees ?? []) > 1;

        $data = [
            'major_task_id' => (int)$request->major_task_id,
            'department_id' => (int)$request->department_id,
            'title' => $request->title,
            'description' => $request->description ?? '',
            'priority_id' => (int)($request->priority_id ?? 2),
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'estimated_hours' => (float)($request->estimated_hours ?? 0),
            'is_cross_functional' => $isCross,
            'assigned_employees' => $request->assigned_employees ?? [],
        ];

        $response = $this->apiClient->post('/api/tasks/', $data, $token);

        if ($response['success'] ?? false) {
            return back()->with('success', 'تمت إضافة المهمة التشغيلية بنجاح');
        }
        return back()->with('error', $response['detail'] ?? 'فشلت إضافة المهمة');
    }

    public function updateOperational(Request $request, $id)
    {
        if (!$this->isManager()) {
            return back()->with('error', 'غير مصرح');
        }

        $token = session('jwt_token');
        $data = [];

        foreach (['title', 'description', 'status_id', 'priority_id', 'end_date', 'estimated_hours'] as $field) {
            if ($request->filled($field)) {
                $data[$field] = $request->$field;
            }
        }

        $response = $this->apiClient->put("/api/tasks/{$id}", $data, $token);

        if ($response['success'] ?? false) {
            return back()->with('success', 'تم تحديث المهمة بنجاح');
        }
        return back()->with('error', $response['detail'] ?? 'فشل تحديث المهمة');
    }

    public function destroyOperational($id)
    {
        if (!$this->isManager()) {
            return back()->with('error', 'غير مصرح');
        }

        $token = session('jwt_token');
        $response = $this->apiClient->delete("/api/tasks/{$id}", $token);

        if ($response['success'] ?? false) {
            return back()->with('success', 'تم حذف المهمة بنجاح');
        }
        return back()->with('error', $response['detail'] ?? 'فشل حذف المهمة');
    }

    public function generate($majorTaskId)
    {
        if (!$this->isManager()) {
            return redirect()->route('dashboard.employee')->with('error', 'غير مصرح');
        }

        $token = session('jwt_token');
        $majorTask = $this->apiClient->get("/api/tasks/major-tasks/{$majorTaskId}/details", $token);
        return view('operational.generate', ['majorTask' => $majorTask['data'] ?? []]);
    }

    public function startGeneration(Request $request)
    {
        if (!$this->isManager()) {
            return back()->with('error', 'غير مصرح');
        }

        $token = session('jwt_token');
        $response = $this->apiClient->post(
            "/api/ai/operational/generate-tasks/{$request->major_task_id}",
            ['instructions' => $request->instructions ?? ''],
            $token
        );

        if ($response['success'] ?? false) {
            $data = $response['data'];
            return redirect()->to('/operational/waiting?job_id=' . $data['job_id'] . '&major_task_id=' . $request->major_task_id);
        }
        return back()->with('error', 'فشل توليد المهام');
    }

    public function waiting(Request $request)
    {
        return view('operational.waiting', [
            'jobId' => $request->job_id,
            'majorTaskId' => $request->major_task_id
        ]);
    }

    public function review(Request $request)
    {
        $token = session('jwt_token');
        $jobResponse = $this->apiClient->get("/api/ai/jobs/{$request->job_id}", $token);
        $job = $jobResponse['data'] ?? [];
        $tasks = $job['result']['operational_tasks'] ?? [];
        $departments = $this->apiClient->safeGet('/api/departments', $token, []);

        return view('operational.review', compact('tasks', 'job', 'departments'));
    }

    public function editWithPrompt(Request $request)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->post(
            "/api/ai/operational/edit-tasks/{$request->job_id}",
            ['instruction' => $request->instruction],
            $token
        );
        return response()->json($response);
    }

  

    public function approve(Request $request)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->post(
            "/api/ai/operational/approve-tasks/{$request->job_id}",
            [],
            $token
        );

        if ($response['success'] ?? false) {
            $jobResponse = $this->apiClient->get("/api/ai/jobs/{$request->job_id}", $token);
            $jobData = $jobResponse['data'] ?? [];
            $result = json_decode($jobData['result_json'] ?? '{}', true);
            $majorTaskId = $result['major_task_id'] ?? 0;

            return redirect()->to('/operational/major-task/' . $majorTaskId)
                ->with('success', 'تم حفظ المهام التشغيلية بنجاح');
        }
        return back()->with('error', $response['detail'] ?? 'فشل الاعتماد');
    }

public function kanbanBoard()
{
    if (!$this->isManager()) {
        return redirect()->route('dashboard.employee')->with('error', 'غير مصرح');
    }

    $token = session('jwt_token');
    $departmentId = $this->getDepartmentId();

    \Log::info('Kanban Board - Department ID: ' . $departmentId);

    $tasksResponse = $this->apiClient->get("/api/tasks/by-department/{$departmentId}", $token);
    $allTasks = $tasksResponse['data'] ?? [];

    $todoTasks = array_filter($allTasks, fn($t) => in_array($t['status'] ?? 0, [16, 5]));
    $inProgressTasks = array_filter($allTasks, fn($t) => ($t['status'] ?? 0) == 6);
    $pendingTasks = array_filter($allTasks, fn($t) => ($t['status'] ?? 0) == 7);
    $reviewTasks = array_filter($allTasks, fn($t) => ($t['status'] ?? 0) == 8);
    $completedTasks = array_filter($allTasks, fn($t) => in_array($t['status'] ?? 0, [4, 10]));

    return view('operational.kanban', compact(
        'todoTasks', 'inProgressTasks', 'pendingTasks', 'reviewTasks', 'completedTasks'
    ));
}


public function updateTaskStatus(Request $request, $id)
{
    if (!$this->isManager()) {
        return response()->json(['error' => 'غير مصرح'], 403);
    }

    $token = session('jwt_token');
    $response = $this->apiClient->put("/api/tasks/{$id}/update-status", [
        'status_id' => (int)$request->status_id,
        'comment' => $request->comment ?? ''
    ], $token);

    return response()->json($response);
}
}

<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OperationalTaskController extends Controller
{
    protected $apiClient;

    public function __construct(ApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    private function isManager(): bool
    {
        $role = session('user_role', 'employee');

        return in_array($role, [
            'manager',
            'general_manager',
            'deputy',
            'minister',
            'admin',
            'super_admin'
        ]);
    }

    private function getDepartmentId(): int
    {
        $departmentId = session('user_department_id');

        if ($departmentId && (int) $departmentId > 0) {
            return (int) $departmentId;
        }

        $token = session('jwt_token');

        if (!$token) {
            return 0;
        }

        try {
            $response = $this->apiClient->get('/api/auth/me', $token);

            $data = $response['data'] ?? $response;

            $departmentId = $data['department_id'] ?? 0;

            if ((int) $departmentId > 0) {
                session(['user_department_id' => (int) $departmentId]);
            }

            return (int) $departmentId;
        } catch (\Exception $e) {
            Log::error('Error getting department ID: ' . $e->getMessage());
            return 0;
        }
    }

    public function majorTasks()
    {
        if (!$this->isManager()) {
            return redirect()
                ->route('dashboard.employee')
                ->with('error', 'غير مصرح');
        }

        $token = session('jwt_token');

        $response = $this->apiClient->get(
            '/api/tasks/major-tasks/by-department',
            $token
        );

        $tasks = $response['data'] ?? [];

        return view('operational.major-tasks', compact('tasks'));
    }

    public function showMajorTask($id)
    {
        if (!$this->isManager()) {
            return redirect()
                ->route('dashboard.employee')
                ->with('error', 'غير مصرح');
        }

        $token = session('jwt_token');

        $majorTaskResponse = $this->apiClient->get(
            "/api/tasks/major-tasks/{$id}/details",
            $token
        );

        $majorTask = $majorTaskResponse['data'] ?? [];

        $operationalResponse = $this->apiClient->get(
            "/api/tasks/by-major-task/{$id}",
            $token
        );

        $operationalTasks = $operationalResponse['data'] ?? [];

        $departmentId = $this->getDepartmentId();

        if ($departmentId <= 0) {
            return redirect()
                ->route('dashboard.employee')
                ->with('error', 'لم يتم تحديد القسم الخاص بالمستخدم');
        }

        $employeesResponse = $this->apiClient->get(
            "/api/employees/department/{$departmentId}",
            $token
        );

        $employees = $employeesResponse['data'] ?? [];

        $roleTypesResponse = $this->apiClient->get(
            '/api/dict/role-types',
            $token
        );

        $roleTypes = $roleTypesResponse['data'] ?? [];

        $prioritiesResponse = $this->apiClient->get(
            '/api/dict/priorities',
            $token
        );

        $priorities = $prioritiesResponse['data'] ?? [];

        $departments = $this->apiClient->safeGet(
            '/api/departments',
            $token,
            []
        );

        return view('operational.show-major-task', compact(
            'majorTask',
            'operationalTasks',
            'departments',
            'employees',
            'roleTypes',
            'priorities',
            'departmentId'
        ));
    }

    public function storeOperational(Request $request)
    {
        if (!$this->isManager()) {
            return back()->with('error', 'غير مصرح');
        }

        try {
            $token = session('jwt_token');

            if (!$token) {
                return back()
                    ->withInput()
                    ->with('error', 'جلسة الدخول غير صالحة');
            }

            $request->validate([
                'major_task_id' => 'required|integer',
                'title' => 'required|string|max:255|min:3',
                'description' => 'nullable|string',
                'priority_id' => 'required|integer',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'estimated_hours' => 'required|numeric|min:1|max:720',
                'assigned_employees' => 'nullable|array'
            ]);

            $departmentId = $this->getDepartmentId();

            if ($departmentId <= 0) {
                return back()
                    ->withInput()
                    ->with('error', 'لم يتم العثور على قسم المستخدم. تأكد من أن المستخدم مرتبط بقسم صحيح.');
            }

            $assignedEmployees = $request->input('assigned_employees', []);

            if (!is_array($assignedEmployees)) {
                $assignedEmployees = [];
            }

            $assignedEmployees = array_values(
                array_filter(
                    array_map('intval', $assignedEmployees),
                    fn ($id) => $id > 0
                )
            );

            $data = [
                'major_task_id' => (int) $request->major_task_id,
                'department_id' => $departmentId,
                'title' => $request->title,
                'description' => $request->description ?? '',
                'priority_id' => (int) $request->priority_id,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'estimated_hours' => (float) $request->estimated_hours,
                'is_cross_functional' => count($assignedEmployees) > 1,
                'assigned_employees' => $assignedEmployees
            ];

            Log::info('Creating operational task', [
                'major_task_id' => $data['major_task_id'],
                'department_id' => $data['department_id'],
                'title' => $data['title'],
                'assigned_employees' => $assignedEmployees
            ]);

            $response = $this->apiClient->post(
                '/api/tasks/',
                $data,
                $token
            );

            if ($response['success'] ?? false) {
                return redirect()
                    ->route(
                        'operational.show-major-task',
                        $request->major_task_id
                    )
                    ->with('success', 'تمت إضافة المهمة التشغيلية بنجاح');
            }

            return back()
                ->withInput()
                ->with(
                    'error',
                    $response['detail']
                    ?? $response['message']
                    ?? 'فشلت إضافة المهمة التشغيلية'
                );
        } catch (\Exception $e) {
            Log::error(
                'Error creating operational task: ' . $e->getMessage()
            );

            return back()
                ->withInput()
                ->with(
                    'error',
                    'حدث خطأ أثناء إضافة المهمة التشغيلية: ' . $e->getMessage()
                );
        }
    }

    public function store(Request $request)
    {
        return $this->storeOperational($request);
    }

    public function updateOperational(Request $request, $id)
    {
        if (!$this->isManager()) {
            return back()->with('error', 'غير مصرح');
        }

        $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'status_id' => 'nullable|integer',
            'priority_id' => 'nullable|integer',
            'end_date' => 'nullable|date',
            'estimated_hours' => 'nullable|numeric|min:1|max:720'
        ]);

        $token = session('jwt_token');

        $data = [];

        foreach (
            [
                'title',
                'description',
                'status_id',
                'priority_id',
                'end_date',
                'estimated_hours'
            ] as $field
        ) {
            if ($request->filled($field)) {
                $data[$field] = $request->$field;
            }
        }

        if (empty($data)) {
            return back()->with('error', 'لا توجد بيانات للتحديث');
        }

        $response = $this->apiClient->put(
            "/api/tasks/{$id}",
            $data,
            $token
        );

        if ($response['success'] ?? false) {
            return back()->with(
                'success',
                'تم تحديث المهمة بنجاح'
            );
        }

        return back()->with(
            'error',
            $response['detail']
            ?? $response['message']
            ?? 'فشل تحديث المهمة'
        );
    }

    public function update(Request $request, $id)
    {
        return $this->updateOperational($request, $id);
    }

    public function destroyOperational($id)
    {
        if (!$this->isManager()) {
            return back()->with('error', 'غير مصرح');
        }

        $token = session('jwt_token');

        $response = $this->apiClient->delete(
            "/api/tasks/{$id}",
            $token
        );

        if ($response['success'] ?? false) {
            return back()->with(
                'success',
                'تم حذف المهمة بنجاح'
            );
        }

        return back()->with(
            'error',
            $response['detail']
            ?? $response['message']
            ?? 'فشل حذف المهمة'
        );
    }

    public function destroy($id)
    {
        return $this->destroyOperational($id);
    }

    public function assignEmployees($majorTaskId)
    {
        if (!$this->isManager()) {
            return redirect()
                ->route('dashboard.employee')
                ->with('error', 'غير مصرح');
        }

        $token = session('jwt_token');
        $departmentId = $this->getDepartmentId();

        if ($departmentId <= 0) {
            return back()->with(
                'error',
                'لم يتم تحديد القسم الخاص بالمستخدم'
            );
        }

        $majorTask = $this->apiClient->get(
            "/api/tasks/major-tasks/{$majorTaskId}/details",
            $token
        );

        $tasks = $this->apiClient->get(
            "/api/tasks/by-major-task/{$majorTaskId}",
            $token
        );

        $employees = $this->apiClient->get(
            "/api/employees/department/{$departmentId}",
            $token
        );

        $roleTypes = $this->apiClient->get(
            '/api/dict/role-types',
            $token
        );

        return view('operational.assign', [
            'majorTask' => $majorTask['data'] ?? [],
            'tasks' => $tasks['data'] ?? [],
            'employees' => $employees['data'] ?? [],
            'roleTypes' => $roleTypes['data'] ?? [],
            'departmentId' => $departmentId
        ]);
    }

    public function assignTaskToEmployee(Request $request)
    {
        if (!$this->isManager()) {
            return response()->json([
                'success' => false,
                'error' => 'غير مصرح'
            ], 403);
        }

        $request->validate([
            'task_id' => 'required|integer',
            'employee_id' => 'required|integer',
            'role_type_id' => 'nullable|integer'
        ]);

        $token = session('jwt_token');

        $response = $this->apiClient->post(
            "/api/tasks/{$request->task_id}/assign",
            [
                'employee_id' => (int) $request->employee_id,
                'role_type_id' => (int) (
                    $request->role_type_id ?? 1
                )
            ],
            $token
        );

        return response()->json($response);
    }

    public function removeTaskAssignment(Request $request)
    {
        if (!$this->isManager()) {
            return response()->json([
                'success' => false,
                'error' => 'غير مصرح'
            ], 403);
        }

        $request->validate([
            'task_id' => 'required|integer',
            'employee_id' => 'required|integer'
        ]);

        $token = session('jwt_token');

        $response = $this->apiClient->delete(
            "/api/tasks/{$request->task_id}/assign/{$request->employee_id}",
            $token
        );

        return response()->json($response);
    }

    public function generate($majorTaskId)
    {
        if (!$this->isManager()) {
            return redirect()
                ->route('dashboard.employee')
                ->with('error', 'غير مصرح');
        }

        $token = session('jwt_token');

        $majorTask = $this->apiClient->get(
            "/api/tasks/major-tasks/{$majorTaskId}/details",
            $token
        );

        return view('operational.generate', [
            'majorTask' => $majorTask['data'] ?? []
        ]);
    }

    public function startGeneration(Request $request)
    {
        if (!$this->isManager()) {
            return back()->with('error', 'غير مصرح');
        }

        $request->validate([
            'major_task_id' => 'required|integer',
            'instructions' => 'nullable|string'
        ]);

        $token = session('jwt_token');

        $response = $this->apiClient->post(
            "/api/ai/operational/generate-tasks/{$request->major_task_id}",
            [
                'instructions' => $request->instructions ?? ''
            ],
            $token
        );

        if ($response['success'] ?? false) {
            $data = $response['data'] ?? [];

            return redirect()->to(
                '/operational/waiting?job_id=' .
                ($data['job_id'] ?? '') .
                '&major_task_id=' .
                $request->major_task_id
            );
        }

        return back()->with(
            'error',
            $response['detail']
            ?? $response['message']
            ?? 'فشل توليد المهام'
        );
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

        $jobResponse = $this->apiClient->get(
            "/api/ai/jobs/{$request->job_id}",
            $token
        );

        $job = $jobResponse['data'] ?? [];

        $tasks = $job['result']['operational_tasks'] ?? [];

        $departments = $this->apiClient->safeGet(
            '/api/departments',
            $token,
            []
        );

        return view(
            'operational.review',
            compact('tasks', 'job', 'departments')
        );
    }

    public function editWithPrompt(Request $request)
    {
        $request->validate([
            'job_id' => 'required|string',
            'instruction' => 'required|string'
        ]);

        $token = session('jwt_token');

        $response = $this->apiClient->post(
            "/api/ai/operational/edit-tasks/{$request->job_id}",
            [
                'instruction' => $request->instruction
            ],
            $token
        );

        return response()->json($response);
    }

    public function approve(Request $request)
    {
        $request->validate([
            'job_id' => 'required|string'
        ]);

        $token = session('jwt_token');

        $response = $this->apiClient->post(
            "/api/ai/operational/approve-tasks/{$request->job_id}",
            [],
            $token
        );

        if ($response['success'] ?? false) {
            $jobResponse = $this->apiClient->get(
                "/api/ai/jobs/{$request->job_id}",
                $token
            );

            $jobData = $jobResponse['data'] ?? [];

            $result = json_decode(
                $jobData['result_json'] ?? '{}',
                true
            );

            $majorTaskId = $result['major_task_id'] ?? 0;

            return redirect()
                ->to('/operational/major-task/' . $majorTaskId)
                ->with(
                    'success',
                    'تم حفظ المهام التشغيلية بنجاح'
                );
        }

        return back()->with(
            'error',
            $response['detail']
            ?? $response['message']
            ?? 'فشل الاعتماد'
        );
    }

    public function kanbanBoard()
    {
        if (!$this->isManager()) {
            return redirect()
                ->route('dashboard.employee')
                ->with('error', 'غير مصرح');
        }

        $token = session('jwt_token');
        $departmentId = $this->getDepartmentId();

        if ($departmentId <= 0) {
            return redirect()
                ->route('dashboard.employee')
                ->with(
                    'error',
                    'لم يتم تحديد القسم الخاص بالمستخدم'
                );
        }

        Log::info(
            'Kanban Board - Department ID: ' . $departmentId
        );

        $tasksResponse = $this->apiClient->get(
            "/api/tasks/by-department/{$departmentId}",
            $token
        );

        $allTasks = $tasksResponse['data'] ?? [];

        $todoTasks = array_values(
            array_filter(
                $allTasks,
                fn ($task) => in_array(
                    (int) ($task['status'] ?? 0),
                    [16, 5]
                )
            )
        );

        $inProgressTasks = array_values(
            array_filter(
                $allTasks,
                fn ($task) => (int) ($task['status'] ?? 0) === 6
            )
        );

        $pendingTasks = array_values(
            array_filter(
                $allTasks,
                fn ($task) => (int) ($task['status'] ?? 0) === 7
            )
        );

        $reviewTasks = array_values(
            array_filter(
                $allTasks,
                fn ($task) => (int) ($task['status'] ?? 0) === 8
            )
        );

        $completedTasks = array_values(
            array_filter(
                $allTasks,
                fn ($task) => in_array(
                    (int) ($task['status'] ?? 0),
                    [4, 10]
                )
            )
        );

        return view(
            'operational.kanban',
            compact(
                'todoTasks',
                'inProgressTasks',
                'pendingTasks',
                'reviewTasks',
                'completedTasks'
            )
        );
    }

    public function updateTaskStatus(Request $request, $id)
    {
        if (!$this->isManager()) {
            return response()->json([
                'success' => false,
                'error' => 'غير مصرح'
            ], 403);
        }

        $request->validate([
            'status_id' => 'required|integer',
            'comment' => 'nullable|string|max:2000'
        ]);

        $token = session('jwt_token');

        $response = $this->apiClient->put(
            "/api/tasks/{$id}/update-status",
            [
                'status_id' => (int) $request->status_id,
                'comment' => $request->comment ?? ''
            ],
            $token
        );

        return response()->json($response);
    }

    public function showOperationalTask($id)
    {
        $token = session('jwt_token');

        $taskResponse = $this->apiClient->get(
            "/api/tasks/{$id}",
            $token
        );

        $task = $taskResponse['data'] ?? [];

        $logsResponse = $this->apiClient->get(
            "/api/tasks/{$id}/progress-logs",
            $token
        );

        $logs = $logsResponse['data'] ?? [];

        return view(
            'operational.task-detail',
            compact('task', 'logs')
        );
    }
}

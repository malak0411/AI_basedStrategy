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
        if (!$token) return 0;

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
            '/api/tasks/major-tasks/by-department/' . $this->getDepartmentId(),
            $token
        );

        $tasks = $response['data'] ?? [];

        return view('operational.major-tasks', compact('tasks'));
    }

    public function showMajorTask($id)
{
    if (!$this->isManager()) {
        return redirect()->route('dashboard.employee')->with('error', 'غير مصرح');
    }

    $token = session('jwt_token');
    $currentDepartmentId = $this->getDepartmentId();

    $majorTaskResponse = $this->apiClient->get("/api/tasks/major-tasks/{$id}/details", $token);
    $majorTask = $majorTaskResponse['data'] ?? [];

    $initiativeName = $majorTask['initiative_name'] ?? 'غير محدد';
    $initiativeStart = $majorTask['initiative_start_date'] ?? null;
    $initiativeEnd = $majorTask['initiative_end_date'] ?? null;
    $estimatedDays = $majorTask['estimated_duration_days'] ?? 0;

    $majorTaskEndDate = null;
    if ($initiativeStart && $estimatedDays) {
        $majorTaskEndDate = \Carbon\Carbon::parse($initiativeStart)->addDays($estimatedDays)->toDateString();
    } else {
        $majorTaskEndDate = $majorTask['end_date'] ?? date('Y-m-d', strtotime('+30 days'));
    }

    $allOperationalResponse = $this->apiClient->get("/api/tasks/by-major-task/{$id}", $token);
    $allOperationalTasks = $allOperationalResponse['data'] ?? [];

    $filteredTasks = array_filter($allOperationalTasks, function($task) use ($currentDepartmentId) {
        return isset($task['department_id']) && (int)$task['department_id'] === (int)$currentDepartmentId;
    });
    $filteredTasks = array_values($filteredTasks);

    $calculatedEnd = null;
    if ($initiativeStart && $estimatedDays) {
        $calculatedEnd = \Carbon\Carbon::parse($initiativeStart)->addDays($estimatedDays)->toDateString();
    }
    foreach ($filteredTasks as &$task) {
        $task['calculated_end_date'] = $calculatedEnd;
        $task['initiative_start'] = $initiativeStart;
        $task['initiative_end'] = $initiativeEnd;
    }

    $employeesResponse = $this->apiClient->get("/api/employees/department/{$currentDepartmentId}", $token);
    $employees = $employeesResponse['data'] ?? [];

    $roleTypesResponse = $this->apiClient->get('/api/dict/role-types', $token);
    $roleTypes = $roleTypesResponse['data'] ?? [];

    $prioritiesResponse = $this->apiClient->get('/api/dict/priorities', $token);
    $priorities = $prioritiesResponse['data'] ?? [];

    $departments = $this->apiClient->safeGet('/api/departments', $token, []);

    $departmentsList = $majorTask['departments'] ?? [];
    $departmentNotes = null;
    foreach ($departmentsList as $dept) {
        if (isset($dept['department_id']) && (int)$dept['department_id'] === (int)$currentDepartmentId) {
            $departmentNotes = $dept['notes'] ?? null;
            break;
        }
    }

    return view('operational.show-major-task', [
        'majorTask' => $majorTask,
        'operationalTasks' => $filteredTasks,
        'departments' => $departments,
        'employees' => $employees,
        'roleTypes' => $roleTypes,
        'priorities' => $priorities,
        'departmentId' => $currentDepartmentId,
        'initiativeStart' => $initiativeStart,
        'initiativeEnd' => $initiativeEnd,
        'initiativeName' => $initiativeName,
        'tid' => $majorTask['id'] ?? 0,
        'majorTaskEndDate' => $majorTaskEndDate,
        'isActive' => $majorTask['is_active'] ?? true,
        'taskTitle' => $majorTask['name'] ?? $majorTask['title'] ?? 'غير محدد',
        'taskDescription' => $majorTask['description'] ?? '',
        'estimatedDays' => $estimatedDays,
        'isCrossDept' => $majorTask['is_cross_department'] ?? false,
        'departmentsList' => $departmentsList,
        'departmentNotes' => $departmentNotes,
        'calculatedEnd' => $calculatedEnd,
    ]);
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

    public function showOperationalTask($id)
{
    try {
        $token = session('jwt_token');
        if (!$token) {
            return redirect()->route('login')->with('error', 'الرجاء تسجيل الدخول أولاً');
        }

        $taskResponse = $this->apiClient->get("/api/tasks/{$id}", $token);
        $task = $taskResponse['data'] ?? [];

        $logsResponse = $this->apiClient->get("/api/tasks/{$id}/progress-logs", $token);
        $logs = $logsResponse['data'] ?? [];

        $attachmentsResponse = $this->apiClient->get("/api/tasks/{$id}/attachments", $token);
        $attachments = $attachmentsResponse['data'] ?? [];

        $commentsResponse = $this->apiClient->get("/api/tasks/{$id}/comments", $token);
        
        if (isset($commentsResponse['data']) && is_array($commentsResponse['data'])) {
            $comments = $commentsResponse['data'];
        } elseif (is_array($commentsResponse)) {
            $comments = $commentsResponse;
        } else {
            $comments = [];
        }

        \Log::info('Task Detail - Comments loaded: ' . count($comments));

        return view('operational.task-detail', [
            'task' => $task,
            'logs' => $logs,
            'attachments' => $attachments,
            'comments' => $comments
        ]);

    } catch (\Exception $e) {
        \Log::error('Task detail error: ' . $e->getMessage());
        return back()->with('error', 'حدث خطأ أثناء تحميل بيانات المهمة');
    }
}


public function assignEmployees($taskId)
{
    if (!$this->isManager()) {
        return redirect()->route('dashboard.employee')->with('error', 'غير مصرح');
    }

    $token = session('jwt_token');
    $departmentId = $this->getDepartmentId();

    if ($departmentId <= 0) {
        return back()->with('error', 'لم يتم تحديد القسم الخاص بالمستخدم');
    }

    $taskResponse = $this->apiClient->get("/api/tasks/{$taskId}", $token);
    $task = $taskResponse['data'] ?? [];
    $majorTaskId = $task['major_task_id'] ?? 0;

    if (empty($task)) {
        return back()->with('error', 'المهمة غير موجودة');
    }

    $majorTask = null;
    if ($majorTaskId > 0) {
        $majorTaskResponse = $this->apiClient->get("/api/major-tasks/{$majorTaskId}", $token);
        $majorTask = $majorTaskResponse['data'] ?? $majorTaskResponse;
    }

    $employeesResponse = $this->apiClient->get(
        "/api/employees/department/{$departmentId}",
        $token
    );
    $allEmployees = $employeesResponse['data'] ?? [];

    $assignmentsResponse = $this->apiClient->get(
        "/api/tasks/{$taskId}/assignments",
        $token
    );
    $currentAssignments = $assignmentsResponse['data'] ?? [];

    $responsibleAssignments = [];
    $memberAssignments = [];
    $rejectedAssignments = [];
    $pendingAssignments = [];
    $assignedEmployeeIds = [];

    foreach ($currentAssignments as $assign) {
        if ($assign['is_active'] == false) continue;
        
        $assignedEmployeeIds[] = $assign['employee_id'];
        
        if ($assign['acceptance_status'] == 'rejected') {
            $rejectedAssignments[] = $assign;
            continue;
        }
        
        if ($assign['role_type_id'] == 2) {
            $responsibleAssignments[] = $assign;
        } else {
            $memberAssignments[] = $assign;
        }
        
        if ($assign['acceptance_status'] == 'pending') {
            $pendingAssignments[] = $assign;
        }
    }

    $unassignedEmployees = [];
    foreach ($allEmployees as $emp) {
        if (!in_array($emp['employee_id'], $assignedEmployeeIds)) {
            $empId = $emp['employee_id'];
            $statsResponse = $this->apiClient->get(
                "/api/employees/{$empId}/task-stats",
                $token
            );
            $stats = $statsResponse['data'] ?? [];
            $emp['total_tasks'] = $stats['total_tasks'] ?? 0;
            $emp['total_hours'] = $stats['total_hours'] ?? 0;
            $emp['current_tasks'] = $stats['current_tasks'] ?? 0;
            $emp['current_hours'] = $stats['current_hours'] ?? 0;
            $unassignedEmployees[] = $emp;
        }
    }

    foreach ($responsibleAssignments as &$assign) {
        if (isset($assign['employee'])) {
            $empId = $assign['employee']['employee_id'] ?? $assign['employee_id'];
            $statsResponse = $this->apiClient->get(
                "/api/employees/{$empId}/task-stats",
                $token
            );
            $stats = $statsResponse['data'] ?? [];
            $assign['employee']['total_tasks'] = $stats['total_tasks'] ?? 0;
            $assign['employee']['total_hours'] = $stats['total_hours'] ?? 0;
        }
    }

    foreach ($memberAssignments as &$assign) {
        if (isset($assign['employee'])) {
            $empId = $assign['employee']['employee_id'] ?? $assign['employee_id'];
            $statsResponse = $this->apiClient->get(
                "/api/employees/{$empId}/task-stats",
                $token
            );
            $stats = $statsResponse['data'] ?? [];
            $assign['employee']['total_tasks'] = $stats['total_tasks'] ?? 0;
            $assign['employee']['total_hours'] = $stats['total_hours'] ?? 0;
        }
    }

    $roleTypes = $this->apiClient->get('/api/dict/role-types', $token);

    $hasResponsible = count($responsibleAssignments) > 0;

    return view('operational.assign', [
        'task' => $task,
        'taskId' => $taskId,                   
        'majorTaskId' => $majorTaskId,         
        'majorTask' => $majorTask,              
        'unassignedEmployees' => $unassignedEmployees,
        'responsibleAssignments' => $responsibleAssignments,
        'memberAssignments' => $memberAssignments,
        'rejectedAssignments' => $rejectedAssignments,
        'pendingAssignments' => $pendingAssignments,
        'currentAssignments' => $currentAssignments,
        'roleTypes' => $roleTypes['data'] ?? [],
        'departmentId' => $departmentId,
        'hasResponsible' => $hasResponsible,
    ]);
}

    public function assignTaskToEmployee(Request $request)
    {
        if (!$this->isManager()) {
            return response()->json(['success' => false, 'error' => 'غير مصرح'], 403);
        }

        $request->validate([
            'task_id' => 'required|integer',
            'employee_id' => 'required|integer',
            'role_type_id' => 'required|integer',
            'estimated_hours' => 'nullable|numeric|min:1|max:720'
        ]);

        $token = session('jwt_token');

        $response = $this->apiClient->post(
            "/api/tasks/{$request->task_id}/assign",
            [
                'employee_id' => (int) $request->employee_id,
                'role_type_id' => (int) $request->role_type_id,
                'estimated_hours' => (float) ($request->estimated_hours ?? 40)
            ],
            $token
        );

        return response()->json($response);
    }

    public function updateAssignmentHours(Request $request)
    {
        if (!$this->isManager()) {
            return response()->json(['success' => false, 'error' => 'غير مصرح'], 403);
        }

        $request->validate([
            'assignment_id' => 'required|integer',
            'estimated_hours' => 'required|numeric|min:1|max:720'
        ]);

        $token = session('jwt_token');

        $response = $this->apiClient->put(
            "/api/tasks/assignments/{$request->assignment_id}/hours",
            [
                'estimated_hours' => (float) $request->estimated_hours
            ],
            $token
        );

        return response()->json($response);
    }

    public function removeTaskAssignment(Request $request)
    {
        if (!$this->isManager()) {
            return response()->json(['success' => false, 'error' => 'غير مصرح'], 403);
        }

        $request->validate([
            'assignment_id' => 'required|integer'
        ]);

        $token = session('jwt_token');

        $response = $this->apiClient->delete(
            "/api/tasks/assignments/{$request->assignment_id}",
            $token
        );

        return response()->json($response);
    }

    public function finalizeTask(Request $request)
    {
        if (!$this->isManager()) {
            return response()->json(['success' => false, 'error' => 'غير مصرح'], 403);
        }

        $request->validate([
            'task_id' => 'required|integer'
        ]);

        $token = session('jwt_token');

        $response = $this->apiClient->post(
            "/api/tasks/{$request->task_id}/finalize",
            [],
            $token
        );

        return response()->json($response);
    }

public function updateTaskStatus(Request $request)
{
    if (!$this->isManager()) {
        return response()->json(['success' => false, 'error' => 'غير مصرح'], 403);
    }

    $request->validate([
        'task_id' => 'required|integer',
        'status_id' => 'required|integer|in:5,6,7,8,19,26',
        'comment' => 'required|string|max:2000',
        'progress_percent' => 'nullable|integer|min:0|max:100'
    ]);

    $token = session('jwt_token');

    $response = $this->apiClient->put(
        "/api/tasks/{$request->task_id}/update-status",
        [
            'status_id' => (int) $request->status_id,
            'comment' => $request->comment,
            'progress_percent' => $request->progress_percent ?? null
        ],
        $token
    );

    return response()->json($response);
}

public function kanbanBoard($majorTaskId = null)
{
    if (!$this->isManager()) {
        return redirect()->route('dashboard.employee')->with('error', 'غير مصرح');
    }

    $token = session('jwt_token');
    $currentDepartmentId = $this->getDepartmentId();

    if (!$majorTaskId) {
        $majorTaskId = session('current_major_task_id');
    }

    if (!$majorTaskId) {
        $departmentId = $this->getDepartmentId();
        $tasksResponse = $this->apiClient->get(
            "/api/tasks/major-tasks/by-department/{$departmentId}",
            $token
        );
        $tasks = $tasksResponse['data'] ?? [];
        if (!empty($tasks)) {
            $majorTaskId = $tasks[0]['id'] ?? 0;
            session(['current_major_task_id' => $majorTaskId]);
        }
    }

    if (!$majorTaskId) {
        return redirect()->route('operational.major-tasks')->with('error', 'لا توجد مهام رئيسية');
    }

    $majorTaskResponse = $this->apiClient->get("/api/tasks/major-tasks/{$majorTaskId}/details", $token);
    $majorTask = $majorTaskResponse['data'] ?? [];

    $initiativeName = $majorTask['initiative_name'] ?? 'غير محدد';
    $initiativeStart = $majorTask['initiative_start_date'] ?? null;
    $initiativeEnd = $majorTask['initiative_end_date'] ?? null;
    $estimatedDays = $majorTask['estimated_duration_days'] ?? 0;

    $majorTaskEndDate = null;
    if ($initiativeStart && $estimatedDays) {
        $majorTaskEndDate = \Carbon\Carbon::parse($initiativeStart)->addDays($estimatedDays)->toDateString();
    } else {
        $majorTaskEndDate = $majorTask['end_date'] ?? date('Y-m-d', strtotime('+30 days'));
    }

    $allOperationalResponse = $this->apiClient->get("/api/tasks/by-major-task/{$majorTaskId}", $token);
    $allTasks = $allOperationalResponse['data'] ?? [];

    $filteredTasks = array_filter($allTasks, function($task) use ($currentDepartmentId) {
        return isset($task['department_id']) && (int)$task['department_id'] === (int)$currentDepartmentId;
    });
    $filteredTasks = array_values($filteredTasks);

    $calculatedEnd = null;
    if ($initiativeStart && $estimatedDays) {
        $calculatedEnd = \Carbon\Carbon::parse($initiativeStart)->addDays($estimatedDays)->toDateString();
    }

    foreach ($filteredTasks as &$task) {
        $task['calculated_end_date'] = $calculatedEnd;
        $task['initiative_start'] = $initiativeStart;
        $task['initiative_end'] = $initiativeEnd;
    }

    $unassignedTasks = [];
    $readyTasks = [];
    $inProgressTasks = [];
    $holdTasks = [];
    $reviewTasks = [];
    $acceptedTasks = [];

    foreach ($filteredTasks as $task) {
        $statusId = (int) ($task['status'] ?? 16);

        switch ($statusId) {
            case 16:
                $unassignedTasks[] = $task;
                break;
            case 26:
                $readyTasks[] = $task;
                break;
            case 6:
                $inProgressTasks[] = $task;
                break;
            case 5:
                $holdTasks[] = $task;
                break;
            case 8:
                $reviewTasks[] = $task;
                break;
            case 19:
                $acceptedTasks[] = $task;
                break;
            default:
                $unassignedTasks[] = $task;
                break;
        }
    }

    return view('operational.kanban', [
        'majorTaskId' => $majorTaskId,
        'majorTaskTitle' => $majorTask['name'] ?? $majorTask['title'] ?? 'المهمة الرئيسية',
        'majorTaskEndDate' => $majorTaskEndDate,
        'initiativeName' => $initiativeName,
        'initiativeStart' => $initiativeStart,
        'initiativeEnd' => $initiativeEnd,
        'estimatedDays' => $estimatedDays,
        'calculatedEnd' => $calculatedEnd,
        'majorTaskIsActive' => $majorTask['is_active'] ?? true,
        'unassignedTasks' => $unassignedTasks,
        'readyTasks' => $readyTasks,
        'inProgressTasks' => $inProgressTasks,
        'holdTasks' => $holdTasks,
        'reviewTasks' => $reviewTasks,
        'acceptedTasks' => $acceptedTasks,
        'allTasks' => $filteredTasks,
    ]);
}

private function isLeadDepartment($majorTaskId): bool
    {
        $token = session('jwt_token');
        $currentDepartmentId = $this->getDepartmentId();

        $response = $this->apiClient->get("/api/tasks/major-tasks/{$majorTaskId}/details", $token);
        $majorTask = $response['data'] ?? [];

        $departmentsList = $majorTask['departments'] ?? [];
        foreach ($departmentsList as $dept) {
            if (isset($dept['department_id']) &&
                (int)$dept['department_id'] === (int)$currentDepartmentId &&
                ($dept['responsibility_type'] ?? '') === 'LEAD') {
                return true;
            }
        }
        return false;
    }


    public function dependencies($majorTaskId)
    {
        if (!$this->isManager()) {
            return redirect()->route('dashboard.employee')->with('error', 'غير مصرح');
        }

        if (!$this->isLeadDepartment($majorTaskId)) {
            return redirect()
                ->route('operational.show-major-task', $majorTaskId)
                ->with('error', 'إدارتك ليست المسؤولة الرئيسية عن هذه المهمة');
        }

        $token = session('jwt_token');

        $majorTaskResponse = $this->apiClient->get("/api/tasks/major-tasks/{$majorTaskId}/details", $token);
        $majorTask = $majorTaskResponse['data'] ?? [];

        $tasksResponse = $this->apiClient->get("/api/tasks/major/{$majorTaskId}/dependencies", $token);
        $tasks = $tasksResponse['data'] ?? [];

        $dependencyTypes = [
            ['value' => 'FS', 'label' => 'Finish to Start (انتهاء → بدء)'],
            ['value' => 'SS', 'label' => 'Start to Start (بدء → بدء)'],
            ['value' => 'FF', 'label' => 'Finish to Finish (انتهاء → انتهاء)'],
            ['value' => 'SF', 'label' => 'Start to Finish (بدء → انتهاء)']
        ];

        return view('operational.dependencies', [
            'majorTaskId' => $majorTaskId,
            'majorTaskTitle' => $majorTask['name'] ?? $majorTask['title'] ?? 'المهمة الرئيسية',
            'tasks' => $tasks,
            'taskCount' => count($tasks),
            'dependencyTypes' => $dependencyTypes
        ]);
    }

    public function getDependenciesData($majorTaskId)
    {
        if (!$this->isManager()) {
            return response()->json(['success' => false, 'error' => 'غير مصرح'], 403);
        }

        $token = session('jwt_token');
        $response = $this->apiClient->get("/api/tasks/major/{$majorTaskId}/dependencies", $token);

        return response()->json($response);
    }

    public function addDependency(Request $request)
    {
        if (!$this->isManager()) {
            return response()->json(['success' => false, 'error' => 'غير مصرح'], 403);
        }

        $request->validate([
            'task_id' => 'required|integer',
            'depends_on_task_id' => 'required|integer',
            'dependency_type' => 'required|string|in:FS,SS,FF,SF',
            'lag_days' => 'nullable|integer|min:0'
        ]);

        $token = session('jwt_token');

        $response = $this->apiClient->post(
            "/api/tasks/dependencies",
            [
                'task_id' => (int) $request->task_id,
                'depends_on_task_id' => (int) $request->depends_on_task_id,
                'dependency_type' => $request->dependency_type,
                'lag_days' => (int) ($request->lag_days ?? 0)
            ],
            $token
        );

        return response()->json($response);
    }

    public function updateDependency(Request $request, $id)
    {
        if (!$this->isManager()) {
            return response()->json(['success' => false, 'error' => 'غير مصرح'], 403);
        }

        $request->validate([
            'dependency_type' => 'nullable|string|in:FS,SS,FF,SF',
            'lag_days' => 'nullable|integer|min:0'
        ]);

        $token = session('jwt_token');

        $data = [];
        if ($request->filled('dependency_type')) {
            $data['dependency_type'] = $request->dependency_type;
        }
        if ($request->filled('lag_days')) {
            $data['lag_days'] = (int) $request->lag_days;
        }

        if (empty($data)) {
            return response()->json(['success' => false, 'message' => 'لا توجد بيانات للتحديث'], 400);
        }

        $response = $this->apiClient->put(
            "/api/tasks/dependencies/{$id}",
            $data,
            $token
        );

        return response()->json($response);
    }

    public function deleteDependency($id)
    {
        if (!$this->isManager()) {
            return response()->json(['success' => false, 'error' => 'غير مصرح'], 403);
        }

        $token = session('jwt_token');

        $response = $this->apiClient->delete(
            "/api/tasks/dependencies/{$id}",
            $token
        );

        return response()->json($response);
    }

    public function checkCycle(Request $request)
    {
        if (!$this->isManager()) {
            return response()->json(['success' => false, 'error' => 'غير مصرح'], 403);
        }

        $request->validate([
            'task_id' => 'required|integer',
            'depends_on_task_id' => 'required|integer'
        ]);

        $token = session('jwt_token');

        $response = $this->apiClient->post(
            "/api/tasks/dependencies/check-cycle",
            [
                'task_id' => (int) $request->task_id,
                'depends_on_task_id' => (int) $request->depends_on_task_id
            ],
            $token
        );

        return response()->json($response);
    }


public function attachments($taskId)
{
    try {
        $token = session('jwt_token');
        if (!$token) {
            return redirect()->route('login')->with('error', 'الرجاء تسجيل الدخول أولاً');
        }

        $response = $this->apiClient->get("/api/tasks/{$taskId}/attachments", $token);
        $attachments = $response['data'] ?? $response;

        $taskResponse = $this->apiClient->get("/api/tasks/{$taskId}", $token);
        $task = $taskResponse['data'] ?? $taskResponse;

        return view('operational.attachments', compact('attachments', 'task', 'taskId'));

    } catch (\Exception $e) {
        \Log::error('Error loading attachments: ' . $e->getMessage());
        return redirect()->back()->with('error', 'حدث خطأ أثناء تحميل المرفقات');
    }
}


public function downloadAttachment($attachmentId)
{
    try {
        $token = session('jwt_token');
        if (!$token) {
            return redirect()->route('login')->with('error', 'الرجاء تسجيل الدخول أولاً');
        }

        $attachmentData = $this->apiClient->get("/api/tasks/attachments/{$attachmentId}/data", $token);
        
        if (!isset($attachmentData['file_path']) || !isset($attachmentData['file_name'])) {
            return redirect()->back()->with('error', 'بيانات الملف غير موجودة');
        }

        $filePath = $attachmentData['file_path'];
        $fileName = $attachmentData['file_name'];
        $fileType = $attachmentData['file_type'] ?? 'application/octet-stream';

        $fullLocalPath = public_path($filePath);
        
        if (!file_exists($fullLocalPath)) {
            $this->downloadFileFromAPI($attachmentId, $filePath);
        }

        $imageTypes = ['image/png', 'image/jpg', 'image/jpeg', 'image/gif', 'image/svg+xml', 'image/webp', 'image/bmp'];
        if (in_array($fileType, $imageTypes)) {
            return response()->file($fullLocalPath);
        }

        $previewTypes = ['application/pdf', 'text/plain', 'text/html', 'application/json'];
        if (in_array($fileType, $previewTypes) || str_contains($fileType, 'image')) {
            return response()->file($fullLocalPath, [
                'Content-Type' => $fileType,
                'Content-Disposition' => 'inline; filename="' . $fileName . '"'
            ]);
        }

        $officeTypes = [
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'application/msword',
            'application/vnd.ms-excel',
            'application/vnd.ms-powerpoint'
        ];

        if (in_array($fileType, $officeTypes)) {
            return response()->file($fullLocalPath, [
                'Content-Type' => $fileType,
                'Content-Disposition' => 'inline; filename="' . $fileName . '"'
            ]);
        }

        return response()->download($fullLocalPath, $fileName);

    } catch (\Exception $e) {
        \Log::error('Download/Preview error: ' . $e->getMessage());
        return redirect()->back()->with('error', 'حدث خطأ: ' . $e->getMessage());
    }
}

private function downloadFileFromAPI($attachmentId, $filePath)
{
    try {
        $token = session('jwt_token');
        if (!$token) {
            return false;
        }

        $client = new \GuzzleHttp\Client();
        $response = $client->get(
            "http://localhost:8000/api/tasks/attachments/{$attachmentId}/download",
            [
                'headers' => ['Authorization' => 'Bearer ' . $token],
                'stream' => true
            ]
        );

        $fullPath = public_path($filePath);
        $directory = dirname($fullPath);
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($fullPath, $response->getBody()->getContents());

        return true;

    } catch (\Exception $e) {
        \Log::error('Failed to download file from API: ' . $e->getMessage());
        return false;
    }
}

public function deleteAttachment($attachmentId)
{
    try {
        $token = session('jwt_token');
        if (!$token) {
            return redirect()->route('login')->with('error', 'الرجاء تسجيل الدخول أولاً');
        }

        $response = $this->apiClient->delete("/api/tasks/attachments/{$attachmentId}", $token);
        
        return redirect()->back()->with('success', 'تم حذف المرفق بنجاح');

    } catch (\Exception $e) {
        \Log::error('Error deleting attachment: ' . $e->getMessage());
        return back()->with('error', 'حدث خطأ أثناء حذف المرفق');
    }
}

public function uploadAttachment(Request $request, $taskId)
{
    try {
        $token = session('jwt_token');
        if (!$token) {
            return redirect()->route('login')->with('error', 'الرجاء تسجيل الدخول أولاً');
        }

        $request->validate([
            'file' => 'required|file|max:10240', 
        ]);

        $file = $request->file('file');
        $description = $request->input('description', '');

        $allowedExtensions = ['png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'ppt', 'pptx', 'zip', 'rar', '7z'];
        $extension = $file->getClientOriginalExtension();
        
        if (!in_array(strtolower($extension), $allowedExtensions)) {
            return redirect()->back()->with('error', 'نوع الملف غير مدعوم');
        }

        $response = $this->apiClient->uploadFile(
            "/api/tasks/{$taskId}/attachments/upload",
            $file,
            $token,
            ['description' => $description]
        );

        if (isset($response['attachment_id'])) {
            return redirect()->back()->with('success', 'تم رفع الملف بنجاح');
        }

        return redirect()->back()->with('error', $response['message'] ?? 'فشل رفع الملف');

    } catch (\Exception $e) {
        \Log::error('Upload error: ' . $e->getMessage());
        return redirect()->back()->with('error', 'حدث خطأ أثناء رفع الملف: ' . $e->getMessage());
    }
}
public function previewAttachment($attachmentId)
{
    try {
        $token = session('jwt_token');
        if (!$token) {
            return redirect()->route('login')->with('error', 'الرجاء تسجيل الدخول أولاً');
        }

        $attachmentData = $this->apiClient->get("/api/tasks/attachments/{$attachmentId}/data", $token);
        
        if (!isset($attachmentData['file_path'])) {
            return redirect()->back()->with('error', 'بيانات الملف غير موجودة');
        }

        $filePath = $attachmentData['file_path'];
        $fileName = $attachmentData['file_name'];
        $fileType = $attachmentData['file_type'] ?? 'application/octet-stream';

        $fullLocalPath = public_path($filePath);
        if (!file_exists($fullLocalPath)) {
            $this->downloadFileFromAPI($attachmentId, $filePath);
        }

        $browserViewable = [
            'image/jpeg', 'image/png', 'image/gif', 'image/svg+xml', 'image/webp',
            'application/pdf',
            'text/plain', 'text/html', 'text/css', 'text/javascript', 'application/json'
        ];

        if (in_array($fileType, $browserViewable)) {
            return response()->file($fullLocalPath, [
                'Content-Type' => $fileType,
                'Content-Disposition' => 'inline; filename="' . $fileName . '"'
            ]);
        }

        $fileUrl = asset($filePath);
        $googleViewerUrl = "https://docs.google.com/gview?embedded=true&url=" . urlencode($fileUrl);
        
        return view('operational.preview', [
            'fileUrl' => $fileUrl,
            'googleViewerUrl' => $googleViewerUrl,
            'fileName' => $fileName,
            'fileType' => $fileType
        ]);

    } catch (\Exception $e) {
        \Log::error('Preview error: ' . $e->getMessage());
        return redirect()->back()->with('error', 'حدث خطأ أثناء معاينة الملف: ' . $e->getMessage());
    }
}

public function addComment(Request $request, $taskId)
{
    try {
        $token = session('jwt_token');
        if (!$token) {
            return response()->json(['success' => false, 'error' => 'غير مصرح'], 401);
        }

        $request->validate([
            'comment' => 'required|string|max:2000',
            'parent_comment_id' => 'nullable|integer'
        ]);

        $response = $this->apiClient->post(
            "/api/tasks/{$taskId}/comments",
            [
                'comment' => $request->comment,
                'parent_comment_id' => $request->parent_comment_id
            ],
            $token
        );

        if (isset($response['comment_id'])) {
            return response()->json(['success' => true, 'data' => $response]);
        }

        return response()->json(['success' => false, 'error' => 'فشل إضافة التعليق'], 500);

    } catch (\Exception $e) {
        \Log::error('Add comment error: ' . $e->getMessage());
        return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
    }
}

public function deleteComment($commentId)
{
    try {
        $token = session('jwt_token');
        if (!$token) {
            return response()->json(['success' => false, 'error' => 'غير مصرح'], 401);
        }

        // جرب هذا المسار أولاً
        $endpoint = "/api/tasks/comments/{$commentId}";
        \Log::info('Deleting comment with endpoint: ' . $endpoint);
        
        $response = $this->apiClient->delete($endpoint, $token);
        
        \Log::info('Delete response:', $response);

        if (isset($response['message']) || isset($response['success'])) {
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'error' => $response['detail'] ?? 'فشل حذف التعليق'], 500);

    } catch (\Exception $e) {
        \Log::error('Delete comment error: ' . $e->getMessage());
        return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
    }
}


}


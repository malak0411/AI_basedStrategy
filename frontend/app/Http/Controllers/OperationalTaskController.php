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

        if (empty($task)) {
            return back()->with('error', 'المهمة غير موجودة');
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
        'status_id' => 'required|integer',
        'comment' => 'nullable|string|max:2000'
    ]);

    $token = session('jwt_token');

    $response = $this->apiClient->put(
        "/api/tasks/{$request->task_id}/update-status",
        [
            'status_id' => (int) $request->status_id,
            'comment' => $request->comment ?? ''
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
    $rejectedTasks = [];

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
            case 20:
                $rejectedTasks[] = $task;
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
        'rejectedTasks' => $rejectedTasks,
        'allTasks' => $filteredTasks,
    ]);
}
}

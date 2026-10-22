<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use carbon\Carbon;

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

public function addRiskMitigation(Request $request, $riskId)
{
    try {
        $token = session('jwt_token');
        if (!$token) {
            return response()->json(['success' => false, 'error' => 'غير مصرح'], 401);
        }


        $request->validate([
            'action' => 'required|string|max:2000',
            'assigned_to' => 'nullable|integer',
            'due_date' => 'nullable|date',
            'status_id' => 'nullable|integer',
            'notes' => 'nullable|string|max:2000'
        ]);


        $data = [
            'action' => $request->action,
            'task_id' => $request->task_id ? (int) $request->task_id : null,
            'assigned_to' => $request->assigned_to ? (int) $request->assigned_to : null,
            'due_date' => $request->due_date,
            'status_id' => $request->status_id ? (int) $request->status_id : null,
            'notes' => $request->notes
        ];


        $response = $this->apiClient->post("/api/risks/{$riskId}/mitigations", $data, $token);


        if ($response['success'] ?? false) {
            return response()->json(['success' => true, 'message' => 'تم إضافة الإجراء بنجاح']);
        }


        return response()->json([
            'success' => false,
            'error' => $response['detail'] ?? 'فشل إضافة الإجراء'
        ], 500);


    } catch (\Exception $e) {
        Log::error('Add risk mitigation error: ' . $e->getMessage());
        return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
    }
}


public function updateRiskMitigation(Request $request, $mitigationId)
{
    try {
        $token = session('jwt_token');
        if (!$token) {
            return response()->json(['success' => false, 'error' => 'غير مصرح'], 401);
        }


        $data = [];
        foreach (['status_id', 'notes', 'due_date', 'assigned_to', 'action'] as $field) {
            if ($request->has($field)) {
                $data[$field] = $request->$field;
            }
        }


        $response = $this->apiClient->put("/api/risks/mitigations/{$mitigationId}", $data, $token);


        if ($response['success'] ?? false) {
            return response()->json(['success' => true, 'message' => 'تم تحديث الإجراء']);
        }


        return response()->json([
            'success' => false,
            'error' => $response['detail'] ?? 'فشل التحديث'
        ], 500);


    } catch (\Exception $e) {
        Log::error('Update risk mitigation error: ' . $e->getMessage());
        return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
    }
}


public function generateRiskRecommendations($riskId)
{
    try {
        $token = session('jwt_token');
        if (!$token) {
            return response()->json(['success' => false, 'error' => 'غير مصرح'], 401);
        }


        $response = $this->apiClient->post("/api/risks/{$riskId}/recommendations", [], $token);


        if ($response['success'] ?? false) {
            return response()->json([
                'success' => true,
                'message' => 'تم توليد التوصيات',
                'data' => $response['data'] ?? []
            ]);
        }


        return response()->json([
            'success' => false,
            'error' => $response['detail'] ?? 'فشل توليد التوصيات'
        ], 500);


    } catch (\Exception $e) {
        Log::error('Generate risk recommendations error: ' . $e->getMessage());
        return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
    }
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
        'title'           => 'nullable|string|max:255',
        'description'     => 'nullable|string|max:1000',
        'status_id'       => 'nullable|integer|exists:dict_statuses,status_id',
        'priority_id'     => 'nullable|integer|exists:dict_priorities,priority_id',
        'start_date'      => 'nullable|date',
        'end_date'        => 'nullable|date|after_or_equal:start_date',
        'estimated_hours' => 'nullable|numeric|min:1|max:720',
    ]);


    $token = session('jwt_token');


    $data = [];
    foreach (
        ['title', 'description', 'status_id', 'priority_id', 'start_date', 'end_date', 'estimated_hours'] as $field
    ) {
        if ($request->filled($field)) {
            $data[$field] = $request->input($field);  
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
        return back()->with('success', 'تم تحديث المهمة بنجاح');
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
    
    public function startGeneration(Request $request)
{
    if (!$this->isManager()) {
        return back()->with('error', 'غير مصرح');
    }


    $request->validate([
        'major_task_id' => 'required|integer',
        'instructions'  => 'nullable|string',
    ]);


    $token = session('jwt_token');


    $response = $this->apiClient->post(
        "/api/ai/operational/generate-tasks/{$request->major_task_id}",
        ['instructions' => $request->instructions ?? ''],
        $token
    );


    if ($response['success'] ?? false) {
        $jobId = $response['data']['job_id'] ?? null;
        if (!$jobId) {
            return back()->with('error', 'تعذر إنشاء وظيفة التوليد');
        }


        return redirect()->route('operational.generate', [
            'id'     => $request->major_task_id,
            'job_id' => $jobId,
        ]);
    }


    return back()->with(
        'error',
        $response['detail']
        ?? $response['message']
        ?? 'فشل توليد المهام'
    );
}



    public function checkJobStatus($job_id)
{
    $token = session('jwt_token');


    $response = $this->apiClient->get(
        "/api/ai/jobs/{$job_id}",
        $token
    );


    return response()->json($response);
}

public function review(Request $request)
{
    if (!$this->isManager()) {
        return redirect()->route('dashboard.employee')->with('error', 'غير مصرح');
    }


    $request->validate(['job_id' => 'required|integer']);


    $token = session('jwt_token');


    $jobResponse = $this->apiClient->get(
        "/api/ai/jobs/{$request->job_id}",
        $token
    );


    if (!($jobResponse['success'] ?? false)) {
        return redirect()
            ->route('dashboard.employee')
            ->with('error', 'تعذر الحصول على نتيجة التوليد');
    }


    $job    = $jobResponse['data'] ?? [];
    $result = is_array($job['result'] ?? null) ? $job['result'] : [];
    $tasks  = is_array($result['operational_tasks'] ?? null) ? $result['operational_tasks'] : [];
    $majorTaskId = $result['major_task_id'] ?? null;
    $jobStatus   = $job['status'] ?? 'review';


    $strategicMap = [];
    $priorities   = [];
    if ($majorTaskId) {
        $mapResponse = $this->apiClient->get(
            "/api/ai/operational/strategic-map/{$majorTaskId}",
            $token
        );
        if ($mapResponse['success'] ?? false) {
            $strategicMap = $mapResponse['data'] ?? [];
            $priorities   = $strategicMap['priorities'] ?? [];
        }
    }


    if (empty($priorities)) {
        $pr = $this->apiClient->safeGet('/api/priorities', $token, []);
        $priorities = $pr['data'] ?? (is_array($pr) ? $pr : []);
    }


    $departmentsResponse = $this->apiClient->safeGet('/api/departments', $token, []);
    $departments = $departmentsResponse['data'] ?? (is_array($departmentsResponse) ? $departmentsResponse : []);


    return view('operational.review', [
        'job'          => $job,
        'tasks'        => $tasks,
        'departments'  => $departments,
        'priorities'   => $priorities,       
        'strategicMap' => $strategicMap,     
        'majorTaskId'  => $majorTaskId,      
        'jobStatus'    => $jobStatus,        
        'jobId'        => $request->job_id,
    ]);
}

    public function editWithPrompt(Request $request, $job_id)
{
    if (!$this->isManager()) {
        return response()->json(['success' => false, 'detail' => 'غير مصرح'], 403);
    }


    $validator = \Validator::make($request->all(), [
        'instruction' => 'required|string|min:3',
    ]);
    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'detail'  => $validator->errors()->first(),
        ], 422);
    }


    $token = session('jwt_token');
    if (!$token) {
        return response()->json(['success' => false, 'detail' => 'انتهت الجلسة، أعد تسجيل الدخول'], 401);
    }


    $response = $this->apiClient->post(
        "/api/ai/operational/edit-tasks/{$job_id}",
        ['instruction' => $request->input('instruction')],
        $token
    );


    $success = (bool) ($response['success'] ?? false);
    if (!$success) {
        $detail = $response['detail']
            ?? $response['message']
            ?? $response['error']
            ?? 'فشل بدء إعادة التوليد على الخادم';


        if (is_string($detail) && strpos($detail, '{') === 0) {
            $parsed = json_decode($detail, true);
            $detail = $parsed['detail'] ?? $detail;
        }


        return response()->json(['success' => false, 'detail' => $detail], 200);
    }


    return response()->json([
        'success' => true,
        'data'    => [
            'job_id' => (int) $job_id,
            'status' => $response['data']['status'] ?? 'editing',
        ],
    ]);
}


private function extractMajorTaskId(int $jobId, ?string $token): ?int
{
    $jobResponse = $this->apiClient->get("/api/ai/jobs/{$jobId}", $token);
    if (!($jobResponse['success'] ?? false)) {
        return null;
    }
    return $jobResponse['data']['result']['major_task_id'] ?? null;
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


        $predictionResponse = $this->apiClient->get("/api/tasks/{$id}/latest-prediction", $token);
        $prediction = $predictionResponse['data'] ?? null;


        $risksResponse = $this->apiClient->get("/api/tasks/{$id}/risks", $token);
        $risks = $risksResponse['data'] ?? [];


        $riskOptionsResponse = $this->apiClient->get('/api/risks/options', $token);
        $riskOptions = $riskOptionsResponse ?? [];


        $departmentId = $this->getDepartmentId();
        $employeesResponse = $this->apiClient->get("/api/employees/department/{$departmentId}", $token);
        $employees = $employeesResponse['data'] ?? [];

        return view('operational.task-detail', [
            'task' => $task,
            'logs' => $logs,
            'attachments' => $attachments,
            'comments' => $comments,
            'prediction' => $prediction,
            'risks' => $risks,
            'riskOptions' => $riskOptions,
            'employees' => $employees
        ]);


    } catch (\Exception $e) {
        Log::error('Task detail error: ' . $e->getMessage());
        return back()->with('error', 'حدث خطأ أثناء تحميل بيانات المهمة');
    }
}

public function generate($majorTaskId, Request $request)
{
    if (!$this->isManager()) {
        return redirect()->route('dashboard.employee')->with('error', 'غير مصرح');
    }


    $token = session('jwt_token');


    $majorTask = $this->apiClient->get(
        "/api/tasks/major-tasks/{$majorTaskId}/details",
        $token
    );


    $strategicMap = [];
    $mapResponse = $this->apiClient->get(
        "/api/ai/operational/strategic-map/{$majorTaskId}",
        $token
    );
    if ($mapResponse['success'] ?? false) {
        $strategicMap = $mapResponse['data'] ?? [];
    }


    if (empty($strategicMap) && !empty($majorTask['data'])) {
        $mt = $majorTask['data'];
        $strategicMap = [
            'major_task' => [
                'id'                  => $mt['major_task_id'] ?? $mt['id'] ?? $majorTaskId,
                'name'                => $mt['name'] ?? '',
                'description'         => $mt['description'] ?? '',
                'duration_days'       => $mt['estimated_duration_days'] ?? 30,
                'is_cross_department' => $mt['is_cross_department'] ?? false,
            ],
            'departments' => $mt['departments'] ?? [],
        ];
    }


    return view('operational.generate', [
        'majorTask'    => $majorTask['data'] ?? [],
        'strategicMap' => $strategicMap,
        'jobId'        => $request->query('job_id'),
    ]);
}

public function approve(Request $request)
{
    if (!$this->isManager()) {
        return back()->with('error', 'غير مصرح');
    }


    $request->validate([
        'job_id' => 'required|integer',
    ]);


    $token = session('jwt_token');
    if (!$token) {
        return back()->with('error', 'انتهت الجلسة، أعد تسجيل الدخول');
    }


    \Log::info('[approve] start', ['job_id' => $request->job_id]);


    $response = $this->apiClient->post(
        "/api/ai/operational/approve-tasks/{$request->job_id}",
        [],
        $token
    );


    \Log::info('[approve] api response', [
        'job_id'   => $request->job_id,
        'response' => $response,
    ]);


    if ($response['success'] ?? false) {
        $majorTaskId = $response['data']['major_task_id'] ?? null;
        $savedCount  = count($response['data']['tasks'] ?? []);


        if (!$majorTaskId) {
            return back()->with('error', 'تم الحفظ لكن تعذر تحديد المهمة الرئيسية');
        }


        return redirect()
            ->route('operational.show-major-task', ['id' => $majorTaskId])
            ->with('success', "تم اعتماد {$savedCount} مهام تشغيلية وحفظها بنجاح");
    }


    $detail = $response['detail']
        ?? $response['message']
        ?? $response['error']
        ?? null;


    if ($detail && stripos($detail, 'already approved') !== false) {
        $majorTaskId = $this->extractMajorTaskIdFromJob($request->job_id, $token);
        if ($majorTaskId) {
            return redirect()
                ->route('operational.show-major-task', ['id' => $majorTaskId])
                ->with('error', 'هذه الوظيفة معتمدة مسبقًا — لم يُحفظ شيء جديد.');
        }
    }


    if ($detail && (
        stripos($detail, "must be in 'review'") !== false ||
        stripos($detail, 'cannot be edited') !== false
    )) {
        return back()->with('error',
            'لا يمكن اعتماد الوظيفة في حالتها الحالية. أعد تحميل الصفحة.'
        );
    }


    if ($detail && stripos($detail, 'No tasks to save') !== false) {
        return back()->with('error',
            'لا توجد مهام للحفظ. أضف مهامًا أو أعد التوليد.'
        );
    }


    return back()->with('error',
        $detail ?: 'فشل اعتماد المهام التشغيلية'
    );
}


private function extractMajorTaskIdFromJob(int $jobId, ?string $token): ?int
{
    $jobResponse = $this->apiClient->get("/api/ai/jobs/{$jobId}", $token);
    if (!($jobResponse['success'] ?? false)) {
        return null;
    }
    return $jobResponse['data']['result']['major_task_id'] ?? null;
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

    foreach ($rejectedAssignments as &$assign) {
    if (!isset($assign['employee']) || empty($assign['employee'])) {
        $empId = $assign['employee_id'] ?? null;
        if ($empId) {
            $empResponse = $this->apiClient->get(
                "/api/employees/{$empId}",
                $token
            );
            $empData = $empResponse['data'] ?? $empResponse ?? [];
            $assign['employee'] = [
                'employee_id' => $empId,
                'full_name'   => $empData['full_name'] ?? $empData['name'] ?? $assign['employee_name'] ?? 'غير محدد',
                'job_title'   => $empData['job_title'] ?? $empData['title'] ?? 'موظف',
            ];
        }
    }


    if (isset($assign['employee'])) {
        $empId = $assign['employee']['employee_id'] ?? $assign['employee_id'] ?? null;
        if ($empId) {
            $statsResponse = $this->apiClient->get(
                "/api/employees/{$empId}/task-stats",
                $token
            );
            $stats = $statsResponse['data'] ?? [];
            $assign['employee']['total_tasks'] = $stats['total_tasks'] ?? 0;
            $assign['employee']['total_hours'] = $stats['total_hours'] ?? 0;
        }
    }
}
unset($assign);


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

    
    public function forceAssign(Request $request)
{
    if (!$this->isManager()) {
        return response()->json(['success' => false, 'error' => 'غير مصرح'], 403);
    }


    $request->validate([
        'assignment_id' => 'required|integer'
    ]);


    $token = session('jwt_token');


    $response = $this->apiClient->post(
        "/api/tasks/assignments/{$request->assignment_id}/force-assign",
        [],
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

public function sendToReview(Request $request, $id)
{
    $request->validate([
        'status_id' => 'required|integer|in:8',
    ]);


    $token = session('jwt_token');


    $response = $this->apiClient->patch(
        "/api/tasks/{$id}/send-to-review",
        ['status_id' => 8],
        $token
    );


    if (($response['status'] ?? 500) >= 400) {
        return response()->json([
            'success' => false,
            'error'   => $response['detail'] ?? 'فشل إرسال المهمة للمراجعة',
        ], 400);
    }


    return response()->json(['success' => true]);
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
            $majorTaskEndDate = Carbon::parse($initiativeStart)->addDays($estimatedDays)->toDateString();
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
            $calculatedEnd = Carbon::parse($initiativeStart)->addDays($estimatedDays)->toDateString();
        }


        foreach ($filteredTasks as &$task) {
            $task['calculated_end_date'] = $calculatedEnd;
            $task['initiative_start'] = $initiativeStart;
            $task['initiative_end'] = $initiativeEnd;
            if (!isset($task['task_id']) && isset($task['id'])) {
                $task['task_id'] = $task['id'];
            }
            if (!isset($task['status']) && isset($task['status_id'])) {
                $task['status'] = $task['status_id'];
            }
        }
        unset($task);


        $unassignedTasks = [];
        $readyTasks = [];
        $inProgressTasks = [];
        $holdTasks = [];
        $reviewTasks = [];
        $acceptedTasks = [];
        $delayedTasks = [];


        foreach ($filteredTasks as $task) {
            $statusId = (int) ($task['status'] ?? $task['status_id'] ?? 16);


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
                case 7:
                    $delayedTasks[] = $task;
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
            'delayedTasks' => $delayedTasks,
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

public function predictTaskDelay($taskId)
{
    try {
        $token = session('jwt_token');
        if (!$token) {
            return response()->json(['success' => false, 'error' => 'غير مصرح'], 401);
        }

        $response = $this->apiClient->post(
            "/api/ai/predict-and-detect/{$taskId}",
            [],
            $token
        );

        if (!is_array($response)) {
            return response()->json(['success' => false, 'error' => 'رد غير صالح من خدمة AI'], 502);
        }

        if (!($response['success'] ?? false)) {
            return response()->json([
                'success' => false,
                'error' => $response['detail'] ?? $response['error'] ?? 'فشل التنبؤ',
            ], 500);
        }

        $data = $response['data'] ?? [];
        $prob = (float) ($data['delay_probability'] ?? 0);
        $data['probability_percent'] = $prob <= 1 ? round($prob * 100, 1) : round($prob, 1);

        return response()->json(['success' => true, 'data' => $data]);
    } catch (\Throwable $e) {
        \Log::error('predictTaskDelay error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
        return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
    }
}

public function generateTaskRiskRecommendations($riskId)
{
    try {
        $token = session('jwt_token');
        if (!$token) {
            return response()->json(['success' => false, 'error' => 'غير مصرح'], 401);
        }

        $response = $this->apiClient->post(
            "/api/ai/risk/{$riskId}/recommendations",
            [],
            $token
        );

        if (!is_array($response)) {
            return response()->json(['success' => false, 'error' => 'رد غير صالح من خدمة AI'], 502);
        }

        if (!($response['success'] ?? false)) {
            return response()->json([
                'success' => false,
                'error' => $response['detail'] ?? $response['error'] ?? 'فشل التوليد',
            ], 500);
        }

        return response()->json(['success' => true, 'data' => $response['data'] ?? []]);
    } catch (\Throwable $e) {
        \Log::error('generateRecommendations error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
        return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
    }
}


public function addTaskMitigation(Request $request, $riskId)
{
    try {
        $token = session('jwt_token');
        if (!$token) {
            return response()->json(['success' => false, 'error' => 'غير مصرح'], 401);
        }


        $request->validate([
            'action' => 'required|string|max:2000',
            'assigned_to' => 'nullable|integer',
            'due_date' => 'nullable|date',
            'status_id' => 'nullable|integer',
            'notes' => 'nullable|string|max:2000'
        ]);


        $data = [
            'action' => $request->action,
            'assigned_to' => $request->assigned_to ? (int) $request->assigned_to : null,
            'due_date' => $request->due_date,
            'status_id' => $request->status_id ? (int) $request->status_id : null,
            'notes' => $request->notes
        ];


        $response = $this->apiClient->post("/api/risks/{$riskId}/mitigations", $data, $token);


        if ($response['success'] ?? false) {
            return response()->json(['success' => true, 'message' => 'تم إضافة الإجراء بنجاح']);
        }


        return response()->json([
            'success' => false,
            'error' => $response['detail'] ?? 'فشل الإضافة'
        ], 500);


    } catch (\Exception $e) {
        Log::error('Add mitigation error: ' . $e->getMessage());
        return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
    }
}


public function updateTaskMitigation(Request $request, $mitigationId)
{
    try {
        $token = session('jwt_token');
        if (!$token) {
            return response()->json(['success' => false, 'error' => 'غير مصرح'], 401);
        }


        $request->validate([
            'status_id'   => 'nullable|integer|in:41,42,43,44',
            'notes'       => 'nullable|string|max:2000',
            'due_date'    => 'nullable|date',
            'assigned_to' => 'nullable|integer',
            'action'      => 'nullable|string|max:1000',
        ]);


        $data = [];
        foreach (['status_id', 'notes', 'due_date', 'assigned_to', 'action'] as $field) {
            if ($request->has($field)) {
                $data[$field] = $request->input($field);
            }
        }


        if (empty($data)) {
            return response()->json([
                'success' => false,
                'error'   => 'لا توجد بيانات للتحديث'
            ], 400);
        }


        $response = $this->apiClient->put(
            "/api/risks/mitigations/{$mitigationId}",
            $data,
            $token
        );


        if ($response['success'] ?? false) {
            return response()->json(['success' => true, 'message' => 'تم تحديث الإجراء']);
        }


        return response()->json([
            'success' => false,
            'error'   => $response['detail'] ?? 'فشل التحديث'
        ], 500);


    } catch (\Exception $e) {
        \Log::error('Update mitigation error: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'error'   => $e->getMessage()
        ], 500);
    }
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

    public function updateTasks(Request $request, $job_id)
{
    if (!$this->isManager()) {
        return response()->json(['success' => false, 'detail' => 'غير مصرح'], 403);
    }


    $token = session('jwt_token');
    if (!$token) {
        return response()->json(['success' => false, 'detail' => 'انتهت الجلسة، أعد تسجيل الدخول'], 401);
    }


    $payload = $request->input('operational_tasks');
    if (!is_array($payload) || empty($payload)) {
        return response()->json([
            'success' => false,
            'detail'  => 'قائمة المهام مطلوبة (عنصر واحد على الأقل).',
        ]);
    }


    \Log::info('[updateTasks] incoming', [
        'job_id' => $job_id,
        'count'  => count($payload),
        'first'  => $payload[0] ?? null,
    ]);


    $validator = \Validator::make($request->all(), [
        'operational_tasks'                    => 'required|array|min:1',
        'operational_tasks.*.title'            => 'required|string|min:3|max:255',
        'operational_tasks.*.description'      => 'required|string|max:1000',
        'operational_tasks.*.priority_id'      => 'required|integer',
        'operational_tasks.*.estimated_hours'  => 'required|numeric|min:1|max:120',
        'operational_tasks.*.start_date'       => 'required|date',
        'operational_tasks.*.end_date'         => 'required|date|after_or_equal:operational_tasks.*.start_date',
    ], [
        'operational_tasks.min'                     => 'يجب أن تبقى مهمة واحدة على الأقل.',
        'operational_tasks.*.estimated_hours.max'   => 'لا يمكن أن تتجاوز ساعات المهمة 120 ساعة.',
        'operational_tasks.*.end_date.after_or_equal'=> 'تاريخ النهاية يجب أن يكون بعد أو يساوي تاريخ البداية.',
    ]);


    if ($validator->fails()) {
        \Log::warning('[updateTasks] validation failed', $validator->errors()->toArray());
        return response()->json([
            'success' => false,
            'detail'  => $validator->errors()->first(),
            'errors'  => $validator->errors()->toArray(),
        ]);
    }


    try {
        $response = $this->apiClient->put(
            "/api/ai/operational/tasks/{$job_id}",
            ['operational_tasks' => $payload],
            $token
        );
    } catch (\Throwable $e) {
        \Log::error('[updateTasks] apiClient exception', [
            'message' => $e->getMessage(),
            'class'   => get_class($e),
        ]);
        return response()->json([
            'success' => false,
            'detail'  => 'فشل الاتصال بالخادم الذكي: ' . $e->getMessage(),
        ]);
    }


    \Log::info('[updateTasks] api response', ['response' => $response]);


    $success = (bool) ($response['success'] ?? false);
    if (!$success) {
        $detail = $response['detail']
            ?? $response['message']
            ?? $response['error']
            ?? 'فشل تحديث المهام على الخادم';


        return response()->json(['success' => false, 'detail' => $detail]);
    }


    return response()->json([
        'success' => true,
        'data'    => [
            'operational_tasks' => $response['data']['operational_tasks'] ?? $payload,
        ],
    ]);
}



}


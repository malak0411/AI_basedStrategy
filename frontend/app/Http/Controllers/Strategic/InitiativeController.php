<?php


namespace App\Http\Controllers\Strategic;


use App\Http\Controllers\Controller;
use App\Services\ApiClient;
use Illuminate\Http\Request;


class InitiativeController extends Controller
{
    protected $apiClient;


    public function __construct()
    {
        $this->apiClient = new ApiClient();
    }


    public function index()
    {
        $token = session('jwt_token');
        $response = $this->apiClient->get('/api/strategic/initiatives', $token);
        $initiatives = $response['data'] ?? [];
        return view('strategic.initiatives.index', compact('initiatives'));
    }


    public function create()
    {
        $token = session('jwt_token');
        $programsResponse = $this->apiClient->get('/api/strategic/programs', $token);
        $programs = $programsResponse['data'] ?? [];
        return view('strategic.initiatives.create', compact('programs'));
    }


    public function store(Request $request)
    {
        $token = session('jwt_token');
        $data = [
            'program_id' => (int) $request->program_id,
            'name' => $request->name,
            'description' => $request->description ?? '',
            'priority_id' => (int) ($request->priority_id ?? 2),
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'budget_estimate' => (float) ($request->budget_estimate ?? 0),
        ];
        $response = $this->apiClient->post('/api/strategic/initiatives', $data, $token);
        if ($response['success'] ?? false) {
            return redirect()->route('strategic.initiatives.index')
                ->with('success', 'تم إنشاء المبادرة (مسودة)');
        }
        return back()->with('error', $response['detail'] ?? 'فشل إنشاء المبادرة')->withInput();
    }


    public function show($id)
    {
        $token = session('jwt_token');


        $response = $this->apiClient->get("/api/strategic/initiatives/{$id}", $token);
        $initiative = $response['data'] ?? [];


        if (empty($initiative)) {
            return redirect()->route('strategic.initiatives.index')
                ->with('error', 'المبادرة غير موجودة');
        }


        $majorTasks = $initiative['major_tasks'] ?? [];


        $departmentsResponse = $this->apiClient->get('/api/departments', $token);
        $departments = $departmentsResponse['data'] ?? [];


        $prioritiesResponse = $this->apiClient->get('/api/dict/priorities', $token);
        $priorities = $prioritiesResponse['data'] ?? [];


        $userRole = session('user_role', 'employee');


        return view('strategic.initiatives.show', compact(
            'initiative', 'majorTasks', 'departments', 'priorities', 'userRole'
        ));
    }


    public function edit($id)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->get("/api/strategic/initiatives/{$id}", $token);
        $initiative = $response['data'] ?? [];
        $programsResponse = $this->apiClient->get('/api/strategic/programs', $token);
        $programs = $programsResponse['data'] ?? [];


        if (empty($initiative)) {
            return redirect()->route('strategic.initiatives.index')
                ->with('error', 'المبادرة غير موجودة');
        }


        return view('strategic.initiatives.edit', compact('initiative', 'programs'));
    }


    public function update(Request $request, $id)
    {
        $token = session('jwt_token');
        $data = [
            'program_id' => (int) $request->program_id,
            'name' => $request->name,
            'description' => $request->description ?? '',
            'priority_id' => (int) ($request->priority_id ?? 2),
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'budget_estimate' => (float) ($request->budget_estimate ?? 0),
        ];
        $response = $this->apiClient->put("/api/strategic/initiatives/{$id}", $data, $token);
        if ($response['success'] ?? false) {
            $newStatus = $response['new_status_name'] ?? '';
            return redirect()->route('strategic.initiatives.show', $id)
                ->with('success', "تم تحديث المبادرة. الحالة الجديدة: {$newStatus}");
        }
        return back()->with('error', $response['detail'] ?? 'فشل تحديث المبادرة');
    }


    public function changeStatus(Request $request, $id)
    {
        $token = session('jwt_token');
        $userRole = session('user_role', 'employee');


        $response = $this->apiClient->post(
            "/api/strategic/initiatives/{$id}/change-status",
            [
                'status_id' => (int) $request->status_id,
                'user_role' => $userRole,
            ],
            $token
        );


        if ($response['success'] ?? false) {
            return back()->with('success', 'تم تحديث الحالة إلى: ' . ($response['new_status_name'] ?? ''));
        }
        return back()->with('error', $response['detail'] ?? 'فشل تحديث الحالة');
    }


    public function approve($id)
    {
        $userRole = session('user_role', 'employee');
        if ($userRole !== 'minister') {
            return back()->with('error', 'الاعتماد متاح للوزير فقط');
        }


        $token = session('jwt_token');
        $response = $this->apiClient->post(
            "/api/strategic/initiatives/{$id}/change-status",
            ['status_id' => 17, 'user_role' => $userRole],
            $token
        );


        if ($response['success'] ?? false) {
            return back()->with('success', 'تم اعتماد المبادرة بنجاح');
        }
        return back()->with('error', $response['detail'] ?? 'فشل اعتماد المبادرة');
    }


    public function storeMajorTask(Request $request, $id)
    {
        $token = session('jwt_token');


        $departments = [];
        foreach (($request->departments ?? []) as $dept) {
            if (!empty($dept['department_id'])) {
                $departments[] = [
                    'department_id'       => (int) $dept['department_id'],
                    'responsibility_type' => $dept['responsibility_type'] ?? 'SUPPORT',
                    'notes'               => $dept['notes'] ?? '',
                ];
            }
        }


        $hasLead = collect($departments)->contains(fn($d) => $d['responsibility_type'] === 'LEAD');
        if (!$hasLead) {
            return back()->with('error', 'يجب تحديد إدارة مسؤولة واحدة على الأقل');
        }


        $data = [
            'name'                    => $request->name,
            'description'             => $request->description ?? '',
            'priority_id'             => (int) ($request->priority_id ?? 2),
            'estimated_duration_days' => (int) ($request->estimated_duration_days ?? 60),
            'departments'             => $departments,
        ];


        $response = $this->apiClient->post(
            "/api/strategic/initiatives/{$id}/major-tasks",
            $data, $token
        );


        if ($response['success'] ?? false) {
            return back()->with('success', 'تمت إضافة المهمة الرئيسية');
        }
        return back()->with('error', $response['detail'] ?? 'فشل إضافة المهمة');
    }

    public function kanban()
{
    $token = session('jwt_token');
    $response = $this->apiClient->get('/api/strategic/initiatives', $token);
    $initiatives = $response['data'] ?? [];


    $columns = [
        1  => ['name' => 'مسودة', 'color' => '#e2e8f0', 'text_color' => '#4a5568'],
        45 => ['name' => 'معلق', 'color' => '#fefcbf', 'text_color' => '#744210'],
        17 => ['name' => 'معتمد', 'color' => '#d4af37', 'text_color' => '#1a1a2e'],
        2  => ['name' => 'قيد التنفيذ', 'color' => '#bee3f8', 'text_color' => '#2c5282'],
        3  => ['name' => 'مكتمل', 'color' => '#c6f6d5', 'text_color' => '#22543d'],
        4  => ['name' => 'ملغي', 'color' => '#fed7d7', 'text_color' => '#822727'],
    ];


    $grouped = [];
    foreach ($columns as $sid => $col) {
        $grouped[$sid] = [];
    }
    foreach ($initiatives as $i) {
        $sid = $i['status_id'] ?? 1;
        if (isset($grouped[$sid])) {
            $grouped[$sid][] = $i;
        }
    }


    $userRole = session('user_role', 'employee');


    return view('strategic.initiatives.kanban', compact('grouped', 'columns', 'userRole'));
}


public function kanbanMove(Request $request, $id)
{
    $token = session('jwt_token');
    $userRole = session('user_role', 'employee');


    $response = $this->apiClient->post(
        "/api/strategic/initiatives/{$id}/change-status",
        [
            'status_id' => (int) $request->status_id,
            'user_role' => $userRole,
        ],
        $token
    );


    if ($response['success'] ?? false) {
        return response()->json([
            'success' => true,
            'new_status' => $response['new_status'] ?? null,
            'new_status_name' => $response['new_status_name'] ?? '',
        ]);
    }


    return response()->json([
        'success' => false,
        'message' => $response['detail'] ?? 'فشل تحديث الحالة',
    ], 400);
}

}

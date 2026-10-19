<?php


namespace App\Http\Controllers\Strategic;


use App\Http\Controllers\Controller;
use App\Services\ApiClient;
use Illuminate\Http\Request;


class ProgramController extends Controller
{
    protected $apiClient;


    public function __construct()
    {
        $this->apiClient = new ApiClient();
    }


    public function index()
    {
        $token = session('jwt_token');
        $response = $this->apiClient->get('/api/strategic/programs', $token);
        $programs = $response['data'] ?? [];
        return view('strategic.programs.index', compact('programs'));
    }


    public function create()
    {
        $token = session('jwt_token');
        $goalsResponse = $this->apiClient->get('/api/strategic/goals', $token);
        $goals = $goalsResponse['data'] ?? [];
        return view('strategic.programs.create', compact('goals'));
    }


    public function store(Request $request)
    {
        $token = session('jwt_token');
        $data = [
            'goal_id' => (int) $request->goal_id,
            'name' => $request->name,
            'description' => $request->description ?? '',
            'budget_estimate' => (float) ($request->budget_estimate ?? 0),
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ];
        $response = $this->apiClient->post('/api/strategic/programs', $data, $token);
        if ($response['success'] ?? false) {
            return redirect()->route('strategic.programs.index')
                ->with('success', 'تم إنشاء البرنامج (مسودة)');
        }
        return back()->with('error', $response['detail'] ?? 'فشل إنشاء البرنامج')->withInput();
    }


    public function show($id)
    {
        $token = session('jwt_token');


        $response = $this->apiClient->get("/api/strategic/programs/{$id}", $token);
        $program = $response['data'] ?? [];


        if (empty($program)) {
            return redirect()->route('strategic.programs.index')
                ->with('error', 'البرنامج غير موجود');
        }


        $initiatives = $program['initiatives'] ?? [];
        $budgetDetails = $program['budget_details'] ?? [];
        $userRole = session('user_role', 'employee');


        return view('strategic.programs.show', compact(
            'program', 'initiatives', 'budgetDetails', 'userRole'
        ));
    }


    public function edit($id)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->get("/api/strategic/programs/{$id}", $token);
        $program = $response['data'] ?? [];
        $goalsResponse = $this->apiClient->get('/api/strategic/goals', $token);
        $goals = $goalsResponse['data'] ?? [];


        if (empty($program)) {
            return redirect()->route('strategic.programs.index')
                ->with('error', 'البرنامج غير موجود');
        }


        return view('strategic.programs.edit', compact('program', 'goals'));
    }


    public function update(Request $request, $id)
    {
        $token = session('jwt_token');
        $data = [
            'goal_id' => (int) $request->goal_id,
            'name' => $request->name,
            'description' => $request->description ?? '',
            'budget_estimate' => (float) ($request->budget_estimate ?? 0),
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ];
        $response = $this->apiClient->put("/api/strategic/programs/{$id}", $data, $token);
        if ($response['success'] ?? false) {
            $newStatus = $response['new_status_name'] ?? '';
            return redirect()->route('strategic.programs.show', $id)
                ->with('success', "تم تحديث البرنامج. الحالة الجديدة: {$newStatus}");
        }
        return back()->with('error', $response['detail'] ?? 'فشل تحديث البرنامج');
    }


    public function kanban()
{
    $token = session('jwt_token');
    $response = $this->apiClient->get('/api/strategic/programs', $token);
    $programs = $response['data'] ?? [];


    $columns = [
        45 => ['name' => 'معلق', 'color' => '#fefcbf', 'text_color' => '#744210'],
        46 => ['name' => 'مسودة', 'color' => '#e2e8f0', 'text_color' => '#4a5568'],
        47 => ['name' => 'قيد التنفيذ', 'color' => '#bee3f8', 'text_color' => '#2c5282'],
        48 => ['name' => 'مكتمل', 'color' => '#c6f6d5', 'text_color' => '#22543d'],
        49 => ['name' => 'ملغي', 'color' => '#fed7d7', 'text_color' => '#822727'],
    ];


    $grouped = [];
    foreach ($columns as $sid => $col) {
        $grouped[$sid] = [];
    }
    foreach ($programs as $p) {
        $sid = $p['status_id'] ?? 46;
        if (isset($grouped[$sid])) {
            $grouped[$sid][] = $p;
        }
    }


    $userRole = session('user_role', 'employee');


    return view('strategic.programs.kanban', compact('grouped', 'columns', 'userRole'));
}


public function kanbanMove(Request $request, $id)
{
    $token = session('jwt_token');
    $userRole = session('user_role', 'employee');


    $response = $this->apiClient->post(
        "/api/strategic/programs/{$id}/change-status",
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

    public function changeStatus(Request $request, $id)
    {
        $token = session('jwt_token');
        $userRole = session('user_role', 'employee');


        $response = $this->apiClient->post(
            "/api/strategic/programs/{$id}/change-status",
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
}

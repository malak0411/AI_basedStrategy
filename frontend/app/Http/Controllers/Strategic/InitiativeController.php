<?php


namespace App\Http\Controllers\Strategic;


use App\Http\Controllers\Controller;
use App\Services\ApiClient;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;


class InitiativeController extends Controller
{
    protected $apiClient;


    public function __construct()
    {
        $this->apiClient = new ApiClient();
    }


    private function normalizeDate($value): ?string
    {
        if (empty($value) || !is_string($value)) return null;
        $value = trim($value);


        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) return $value;
        if (preg_match('/^(\d{4}-\d{2}-\d{2})[T\s]/', $value, $m)) return $m[1];
        if (preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', $value, $m)) return "{$m[3]}-{$m[2]}-{$m[1]}";


        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }


    private function findDate(array $arr, array $keys)
    {
        foreach ($keys as $key) {
            if (!empty($arr[$key]) && is_string($arr[$key])) return $arr[$key];
        }
        foreach (['period', 'dates', 'duration', 'schedule', 'timeframe', 'period_dates'] as $nested) {
            if (isset($arr[$nested]) && is_array($arr[$nested])) {
                foreach ($keys as $key) {
                    if (!empty($arr[$nested][$key]) && is_string($arr[$nested][$key])) {
                        return $arr[$nested][$key];
                    }
                }
            }
        }
        return null;
    }


    private function extractProgramDates(array $program): array
    {
        $startKeys = ['valid_from','start_date','planned_start_date','start','begin_date','date_from','from_date','start_at','startDate'];
        $endKeys   = ['valid_until','end_date','planned_end_date','end','finish_date','date_to','to_date','end_at','endDate'];


        $start = $this->findDate($program, $startKeys);
        $end   = $this->findDate($program, $endKeys);


        return [$this->normalizeDate($start), $this->normalizeDate($end)];
    }


    private function extractProgramBudget(array $program): float
    {
        foreach (['budget_estimate','budget','estimated_budget','total_budget','allocated_budget'] as $k) {
            if (isset($program[$k]) && is_numeric($program[$k])) {
                return (float) $program[$k];
            }
        }
        return 0.0;
    }


    private function fetchProgramFull(int $programId, string $token): array
    {
        $detail = $this->apiClient->get("/api/strategic/programs/{$programId}", $token);


        $candidates = [
            $detail['data']['program'] ?? null,
            $detail['data'] ?? null,
            $detail['program'] ?? null,
            $detail,
        ];
        foreach ($candidates as $c) {
            if (is_array($c) && !empty($c) && isset($c['id'])) return $c;
        }
        return [];
    }


    private function buildProgramDataMap(array $programs, string $token): array
    {
        $map = [];
        foreach ($programs as $p) {
            $pid = $p['id'] ?? null;
            if (!$pid) continue;


            [$start, $end] = $this->extractProgramDates($p);
            $budget = $this->extractProgramBudget($p);


            if (empty($start) || empty($end) || $budget <= 0) {
                $full = $this->fetchProgramFull((int) $pid, $token);
                [$fs, $fe] = $this->extractProgramDates($full);
                $fb = $this->extractProgramBudget($full);
                $start  = $start  ?: $fs;
                $end    = $end    ?: $fe;
                $budget = $budget > 0 ? $budget : $fb;
            }


            $map[$pid] = [
                'start'  => $start  ?: '',
                'end'    => $end    ?: '',
                'budget' => $budget > 0 ? $budget : 0,
            ];
        }
        return $map;
    }


    private function validateAgainstProgram(Request $request, string $token): ?array
    {
        $program = $this->fetchProgramFull((int) $request->program_id, $token);


        if (empty($program) || !isset($program['id'])) {
            return ['field' => 'program_id', 'message' => 'البرنامج غير موجود.'];
        }


        [$pStart, $pEnd] = $this->extractProgramDates($program);
        $pBudget = $this->extractProgramBudget($program);


        \Log::info('PROGRAM_VALIDATION', [
            'program_id' => $request->program_id,
            'p_start'    => $pStart,
            'p_end'      => $pEnd,
            'p_budget'   => $pBudget,
            'raw'        => $program,
        ]);


        if (empty($pStart) || empty($pEnd)) {
            return ['field' => 'program_id', 'message' => 'لا يحتوي البرنامج المحدد على تاريخي بداية ونهاية صالحين. يرجى تعديل البرنامج أولاً.'];
        }


        if ($request->start_date < $pStart) {
            return ['field' => 'start_date', 'message' => "تاريخ بداية المبادرة يجب أن يكون بعد أو يساوي تاريخ بداية البرنامج ({$pStart})."];
        }
        if ($request->start_date > $pEnd) {
            return ['field' => 'start_date', 'message' => "تاريخ بداية المبادرة يجب أن يكون قبل تاريخ انتهاء البرنامج ({$pEnd})."];
        }
        if ($request->end_date > $pEnd) {
            return ['field' => 'end_date', 'message' => "تاريخ نهاية المبادرة يجب أن يكون قبل أو يساوي تاريخ انتهاء البرنامج ({$pEnd})."];
        }


        if ($pBudget > 0 && (float) $request->budget_estimate > $pBudget) {
            return ['field' => 'budget_estimate', 'message' => "ميزانية المبادرة ({$request->budget_estimate}) يجب أن لا تتجاوز ميزانية البرنامج ({$pBudget})."];
        }


        return null;
    }


    private function fetchPriorities(string $token): array
{
    $response = $this->apiClient->get('/api/dict/priorities', $token);
    $raw = $response['data'] ?? [];


    if (isset($raw['data']) && is_array($raw['data'])) {
        $raw = $raw['data'];
    }


    $normalized = [];


    foreach ($raw as $key => $item) {
        if (is_string($item) || is_numeric($item)) {
            $normalized[] = ['id' => $key, 'name' => (string) $item];
            continue;
        }


        if (!is_array($item)) continue;


        $id = $item['id']
            ?? $item['value']
            ?? $item['priority_id']
            ?? $item['code']
            ?? $item['key']
            ?? null;


        if ($id === null) continue;


        $name = $item['name_ar']
            ?? $item['name']
            ?? $item['label']
            ?? $item['title']
            ?? $item['label_ar']
            ?? $item['description']
            ?? ('أولوية #' . $id);


        $normalized[] = ['id' => $id, 'name' => $name];
    }


    return $normalized;
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
        $programData = $this->buildProgramDataMap($programs, $token);
        $priorities  = $this->fetchPriorities($token);


        return view('strategic.initiatives.create', compact('programs', 'programData', 'priorities'));
    }


    public function store(Request $request)
    {
        $token = session('jwt_token');


        $request->validate([
            'program_id'      => 'required',
            'name'            => 'required|string|max:255',
            'priority_id'     => 'required',
            'start_date'      => 'required|date',
            'end_date'        => 'required|date|after_or_equal:start_date',
            'budget_estimate' => 'required|numeric|min:0',
        ], [
            'program_id.required'      => 'يجب اختيار البرنامج.',
            'name.required'            => 'اسم المبادرة مطلوب.',
            'priority_id.required'     => 'يجب اختيار الأولوية.',
            'start_date.required'      => 'تاريخ البداية مطلوب.',
            'end_date.required'        => 'تاريخ النهاية مطلوب.',
            'end_date.after_or_equal'  => 'تاريخ النهاية يجب أن يكون بعد أو يساوي تاريخ البداية.',
            'budget_estimate.required' => 'ميزانية المبادرة مطلوبة.',
            'budget_estimate.numeric'  => 'الميزانية يجب أن تكون رقماً.',
        ]);


        $err = $this->validateAgainstProgram($request, $token);
        if ($err) {
            return back()->withErrors([$err['field'] => $err['message']])->withInput();
        }


        $data = [
            'program_id'      => (int) $request->program_id,
            'name'            => $request->name,
            'description'     => $request->description ?? '',
            'priority_id'     => (int) ($request->priority_id ?? 2),
            'start_date'      => $request->start_date,
            'end_date'        => $request->end_date,
            'budget_estimate' => (float) ($request->budget_estimate ?? 0),
            'status_id'       => 1,
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


        $programData = $this->buildProgramDataMap($programs, $token);
        $priorities  = $this->fetchPriorities($token);


        return view('strategic.initiatives.edit', compact('initiative', 'programs', 'programData', 'priorities'));
    }


    public function update(Request $request, $id)
    {
        $token = session('jwt_token');


        $request->validate([
            'program_id'      => 'required',
            'name'            => 'required|string|max:255',
            'priority_id'     => 'required',
            'start_date'      => 'required|date',
            'end_date'        => 'required|date|after_or_equal:start_date',
            'budget_estimate' => 'required|numeric|min:0',
        ], [
            'program_id.required'      => 'يجب اختيار البرنامج.',
            'name.required'            => 'اسم المبادرة مطلوب.',
            'priority_id.required'     => 'يجب اختيار الأولوية.',
            'start_date.required'      => 'تاريخ البداية مطلوب.',
            'end_date.required'        => 'تاريخ النهاية مطلوب.',
            'end_date.after_or_equal'  => 'تاريخ النهاية يجب أن يكون بعد أو يساوي تاريخ البداية.',
            'budget_estimate.required' => 'ميزانية المبادرة مطلوبة.',
            'budget_estimate.numeric'  => 'الميزانية يجب أن تكون رقماً.',
        ]);


        $err = $this->validateAgainstProgram($request, $token);
        if ($err) {
            return back()->withErrors([$err['field'] => $err['message']])->withInput();
        }


        $data = [
            'program_id'      => (int) $request->program_id,
            'name'            => $request->name,
            'description'     => $request->description ?? '',
            'priority_id'     => (int) ($request->priority_id ?? 2),
            'start_date'      => $request->start_date,
            'end_date'        => $request->end_date,
            'budget_estimate' => (float) ($request->budget_estimate ?? 0),
        ];


        $response = $this->apiClient->put("/api/strategic/initiatives/{$id}", $data, $token);


        if ($response['success'] ?? false) {
            $newStatus = $response['new_status_name'] ?? '';
            return redirect()->route('strategic.initiatives.show', $id)
                ->with('success', "تم تحديث المبادرة. الحالة الجديدة: {$newStatus}");
        }


        return back()->with('error', $response['detail'] ?? 'فشل تحديث المبادرة')->withInput();
    }


    public function changeStatus(Request $request, $id)
    {
        $token = session('jwt_token');
        $userRole = session('user_role', 'employee');


        $response = $this->apiClient->post(
            "/api/strategic/initiatives/{$id}/change-status",
            ['status_id' => (int) $request->status_id, 'user_role' => $userRole],
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
            1  => ['name' => 'مسودة',       'color' => '#e2e8f0', 'text_color' => '#4a5568'],
            45 => ['name' => 'معلق',        'color' => '#fefcbf', 'text_color' => '#744210'],
            17 => ['name' => 'معتمد',       'color' => '#d4af37', 'text_color' => '#1a1a2e'],
            2  => ['name' => 'قيد التنفيذ', 'color' => '#bee3f8', 'text_color' => '#2c5282'],
            3  => ['name' => 'مكتمل',       'color' => '#c6f6d5', 'text_color' => '#22543d'],
            4  => ['name' => 'ملغي',        'color' => '#fed7d7', 'text_color' => '#822727'],
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
            ['status_id' => (int) $request->status_id, 'user_role' => $userRole],
            $token
        );


        if ($response['success'] ?? false) {
            return response()->json([
                'success'         => true,
                'new_status'      => $response['new_status'] ?? null,
                'new_status_name' => $response['new_status_name'] ?? '',
            ]);
        }


        return response()->json([
            'success' => false,
            'message' => $response['detail'] ?? 'فشل تحديث الحالة',
        ], 400);
    }
}

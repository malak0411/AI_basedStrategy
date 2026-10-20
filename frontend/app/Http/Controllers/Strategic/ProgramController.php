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


    private function normalizeDate($value): ?string
{
    if (empty($value)) return null;
    if (!is_string($value)) return null;


    $value = trim($value);


    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
        return $value;
    }


    if (preg_match('/^(\d{4}-\d{2}-\d{2})[T\s]/', $value, $m)) {
        return $m[1];
    }


    if (preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', $value, $m)) {
        return "{$m[3]}-{$m[2]}-{$m[1]}";
    }


    try {
        return \Illuminate\Support\Carbon::parse($value)->format('Y-m-d');
    } catch (\Exception $e) {
        return null;
    }
}


private function findDate(array $arr, array $keys)
{
    foreach ($keys as $key) {
        if (!empty($arr[$key]) && is_string($arr[$key])) {
            return $arr[$key];
        }
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


private function extractGoalDates(array $goal): array
{
    $startKeys = [
        'valid_from',
        'start_date', 'planned_start_date', 'start', 'begin_date',
        'date_from', 'from_date', 'start_at', 'startDate',
        'plannedStartDate', 'startDateFormatted', 'from', 'begin',
    ];
    $endKeys = [
        'valid_until',
        'end_date', 'planned_end_date', 'end', 'finish_date',
        'date_to', 'to_date', 'end_at', 'endDate',
        'plannedEndDate', 'endDateFormatted', 'to', 'finish', 'due_date',
    ];


    $start = $this->findDate($goal, $startKeys);
    $end   = $this->findDate($goal, $endKeys);


    return [$this->normalizeDate($start), $this->normalizeDate($end)];
}



    private function fetchGoalFull(int $goalId, string $token): array
{
    $detail = $this->apiClient->get("/api/strategic/goals/{$goalId}", $token);


    $candidates = [
        $detail['data']['goal'] ?? null,
        $detail['data'] ?? null,
        $detail['goal'] ?? null,
        $detail,
    ];


    foreach ($candidates as $c) {
        if (is_array($c) && !empty($c) && isset($c['id'])) {
            return $c;
        }
    }
    return [];
}


private function buildGoalDatesMap(array $goals, string $token): array
{
    $map = [];
    foreach ($goals as $g) {
        $gid = $g['id'] ?? null;
        if (!$gid) continue;


        [$start, $end] = $this->extractGoalDates($g);


        if (empty($start) || empty($end)) {
            $full = $this->fetchGoalFull((int) $gid, $token);
            [$fs, $fe] = $this->extractGoalDates($full);
            $start = $start ?: $fs;
            $end   = $end   ?: $fe;
        }


        $map[$gid] = ['start' => $start ?: '', 'end' => $end ?: ''];
    }
    return $map;
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
        $goalDates = $this->buildGoalDatesMap($goals, $token);


        return view('strategic.programs.create', compact('goals', 'goalDates'));
    }


    public function store(Request $request)
    {
        $token = session('jwt_token');


        $request->validate([
            'goal_id'    => 'required',
            'name'       => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after:start_date',
        ], [
            'goal_id.required'    => 'يجب اختيار الهدف.',
            'name.required'       => 'اسم البرنامج مطلوب.',
            'start_date.required' => 'تاريخ البداية مطلوب.',
            'end_date.required'   => 'تاريخ النهاية مطلوب.',
            'end_date.after'      => 'تاريخ النهاية يجب أن يكون بعد تاريخ البداية.',
        ]);


        $goal = $this->fetchGoalFull((int) $request->goal_id, $token);


        if (empty($goal) || !isset($goal['id'])) {
            return back()->withErrors(['goal_id' => 'الهدف غير موجود.'])->withInput();
        }


        [$goalStart, $goalEnd] = $this->extractGoalDates($goal);


        if (empty($goalStart) || empty($goalEnd)) {
            return back()->withErrors([
                'goal_id' => 'لا يحتوي الهدف المحدد على تاريخي بداية ونهاية صالحين. يرجى تعديل الهدف أولاً.',
            ])->withInput();
        }


        if ($request->start_date < $goalStart) {
            return back()->withErrors([
                'start_date' => "تاريخ بداية البرنامج يجب أن يكون بعد أو يساوي تاريخ بداية الهدف ({$goalStart}).",
            ])->withInput();
        }


        if ($request->start_date > $goalEnd) {
            return back()->withErrors([
                'start_date' => "تاريخ بداية البرنامج يجب أن يكون قبل تاريخ انتهاء الهدف ({$goalEnd}).",
            ])->withInput();
        }


        if ($request->end_date > $goalEnd) {
            return back()->withErrors([
                'end_date' => "تاريخ نهاية البرنامج يجب أن يكون قبل أو يساوي تاريخ انتهاء الهدف ({$goalEnd}).",
            ])->withInput();
        }


        $data = [
            'goal_id'         => (int) $request->goal_id,
            'name'            => $request->name,
            'description'     => $request->description ?? '',
            'budget_estimate' => (float) ($request->budget_estimate ?? 0),
            'start_date'      => $request->start_date,
            'end_date'        => $request->end_date,
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


        $initiatives   = $program['initiatives']    ?? [];
        $budgetDetails = $program['budget_details'] ?? [];
        $userRole      = session('user_role', 'employee');


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


        $goalDates = $this->buildGoalDatesMap($goals, $token);


        return view('strategic.programs.edit', compact('program', 'goals', 'goalDates'));
    }


    public function update(Request $request, $id)
    {
        $token = session('jwt_token');


        $request->validate([
            'goal_id'    => 'required',
            'name'       => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after:start_date',
        ], [
            'goal_id.required'    => 'يجب اختيار الهدف.',
            'name.required'       => 'اسم البرنامج مطلوب.',
            'start_date.required' => 'تاريخ البداية مطلوب.',
            'end_date.required'   => 'تاريخ النهاية مطلوب.',
            'end_date.after'      => 'تاريخ النهاية يجب أن يكون بعد تاريخ البداية.',
        ]);


        $goal = $this->fetchGoalFull((int) $request->goal_id, $token);


        if (empty($goal) || !isset($goal['id'])) {
            return back()->withErrors(['goal_id' => 'الهدف غير موجود.'])->withInput();
        }


        [$goalStart, $goalEnd] = $this->extractGoalDates($goal);


        if (empty($goalStart) || empty($goalEnd)) {
            return back()->withErrors([
                'goal_id' => 'لا يحتوي الهدف المحدد على تاريخي بداية ونهاية صالحين. يرجى تعديل الهدف أولاً.',
            ])->withInput();
        }


        if ($request->start_date < $goalStart) {
            return back()->withErrors([
                'start_date' => "تاريخ بداية البرنامج يجب أن يكون بعد أو يساوي تاريخ بداية الهدف ({$goalStart}).",
            ])->withInput();
        }


        if ($request->start_date > $goalEnd) {
            return back()->withErrors([
                'start_date' => "تاريخ بداية البرنامج يجب أن يكون قبل تاريخ انتهاء الهدف ({$goalEnd}).",
            ])->withInput();
        }


        if ($request->end_date > $goalEnd) {
            return back()->withErrors([
                'end_date' => "تاريخ نهاية البرنامج يجب أن يكون قبل أو يساوي تاريخ انتهاء الهدف ({$goalEnd}).",
            ])->withInput();
        }


        $data = [
            'goal_id'         => (int) $request->goal_id,
            'name'            => $request->name,
            'description'     => $request->description ?? '',
            'budget_estimate' => (float) ($request->budget_estimate ?? 0),
            'start_date'      => $request->start_date,
            'end_date'        => $request->end_date,
        ];


        $response = $this->apiClient->put("/api/strategic/programs/{$id}", $data, $token);


        if ($response['success'] ?? false) {
            $newStatus = $response['new_status_name'] ?? '';
            return redirect()->route('strategic.programs.show', $id)
                ->with('success', "تم تحديث البرنامج. الحالة الجديدة: {$newStatus}");
        }


        return back()->with('error', $response['detail'] ?? 'فشل تحديث البرنامج')->withInput();
    }


    public function kanban()
    {
        $token = session('jwt_token');
        $response = $this->apiClient->get('/api/strategic/programs', $token);
        $programs = $response['data'] ?? [];


        $columns = [
            45 => ['name' => 'معلق',        'color' => '#fefcbf', 'text_color' => '#744210'],
            46 => ['name' => 'مسودة',       'color' => '#e2e8f0', 'text_color' => '#4a5568'],
            47 => ['name' => 'قيد التنفيذ', 'color' => '#bee3f8', 'text_color' => '#2c5282'],
            48 => ['name' => 'مكتمل',       'color' => '#c6f6d5', 'text_color' => '#22543d'],
            49 => ['name' => 'ملغي',        'color' => '#fed7d7', 'text_color' => '#822727'],
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


    public function changeStatus(Request $request, $id)
    {
        $token = session('jwt_token');
        $userRole = session('user_role', 'employee');


        $response = $this->apiClient->post(
            "/api/strategic/programs/{$id}/change-status",
            ['status_id' => (int) $request->status_id, 'user_role' => $userRole],
            $token
        );


        if ($response['success'] ?? false) {
            return back()->with('success', 'تم تحديث الحالة إلى: ' . ($response['new_status_name'] ?? ''));
        }


        return back()->with('error', $response['detail'] ?? 'فشل تحديث الحالة');
    }
}

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
            return redirect()->route('strategic.initiatives.index')->with('success', 'تم إنشاء المبادرة');
        }
        return back()->with('error', $response['detail'] ?? 'فشل إنشاء المبادرة')->withInput();
    }

    public function show($id)
{
    $token = session('jwt_token');
    $response = $this->apiClient->get("/api/strategic/initiatives/{$id}", $token);
    $initiative = $response['data'] ?? [];

    if (empty($initiative)) {
        return redirect()->route('strategic.initiatives.index')->with('error', 'المبادرة غير موجودة');
    }

    $majorTasksResponse = $this->apiClient->get("/api/strategic/initiatives/{$id}/major-tasks", $token);
    $majorTasks = $majorTasksResponse['data'] ?? [];

    $departmentsResponse = $this->apiClient->get('/api/departments', $token);
    $departments = $departmentsResponse['data'] ?? [];

    $prioritiesResponse = $this->apiClient->get('/api/dict/priorities', $token);
    $priorities = $prioritiesResponse['data'] ?? [];

    return view('strategic.initiatives.show', compact('initiative', 'majorTasks', 'departments', 'priorities'));
}


    public function edit($id)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->get("/api/strategic/initiatives/{$id}", $token);
        $initiative = $response['data'] ?? [];
        $programsResponse = $this->apiClient->get('/api/strategic/programs', $token);
        $programs = $programsResponse['data'] ?? [];
        if (empty($initiative)) return redirect()->route('strategic.initiatives.index')->with('error', 'المبادرة غير موجودة');
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
            return redirect()->route('strategic.initiatives.show', $id)->with('success', 'تم تحديث المبادرة');
        }
        return back()->with('error', $response['detail'] ?? 'فشل تحديث المبادرة');
    }

    public function destroy($id)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->delete("/api/strategic/initiatives/{$id}", $token);
        if ($response['success'] ?? false) {
            return redirect()->route('strategic.initiatives.index')->with('success', 'تم حذف المبادرة');
        }
        return back()->with('error', $response['detail'] ?? 'فشل حذف المبادرة');
    }
}

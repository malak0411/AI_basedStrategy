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
            'status_id' => (int) ($request->status_id ?? 5),
        ];
        $response = $this->apiClient->post('/api/strategic/programs', $data, $token);
        if ($response['success'] ?? false) {
            return redirect()->route('strategic.programs.index')->with('success', 'تم إنشاء البرنامج');
        }
        return back()->with('error', $response['detail'] ?? 'فشل إنشاء البرنامج')->withInput();
    }

    public function show($id)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->get("/api/strategic/programs/{$id}", $token);
        $program = $response['data'] ?? [];
        if (empty($program)) return redirect()->route('strategic.programs.index')->with('error', 'البرنامج غير موجود');
        return view('strategic.programs.show', compact('program'));
    }

    public function edit($id)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->get("/api/strategic/programs/{$id}", $token);
        $program = $response['data'] ?? [];
        $goalsResponse = $this->apiClient->get('/api/strategic/goals', $token);
        $goals = $goalsResponse['data'] ?? [];
        if (empty($program)) return redirect()->route('strategic.programs.index')->with('error', 'البرنامج غير موجود');
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
            return redirect()->route('strategic.programs.show', $id)->with('success', 'تم تحديث البرنامج');
        }
        return back()->with('error', $response['detail'] ?? 'فشل تحديث البرنامج');
    }

    public function destroy($id)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->delete("/api/strategic/programs/{$id}", $token);
        if ($response['success'] ?? false) {
            return redirect()->route('strategic.programs.index')->with('success', 'تم حذف البرنامج');
        }
        return back()->with('error', $response['detail'] ?? 'فشل حذف البرنامج');
    }
}

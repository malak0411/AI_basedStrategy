<?php

namespace App\Http\Controllers\Strategic;

use App\Http\Controllers\Controller;
use App\Services\ApiClient;
use Illuminate\Http\Request;

class GoalController extends Controller
{
    protected $apiClient;

    public function __construct()
    {
        $this->apiClient = new ApiClient();
    }

    public function index()
    {
        $token = session('jwt_token');
        $response = $this->apiClient->get('/api/strategic/goals', $token);
        $goals = $response['data'] ?? [];
        return view('strategic.goals.index', compact('goals'));
    }

    public function create()
    {
        $token = session('jwt_token');
        $pillarsResponse = $this->apiClient->get('/api/strategic/pillars', $token);
        $pillars = $pillarsResponse['data'] ?? [];
        return view('strategic.goals.create', compact('pillars'));
    }

    public function store(Request $request)
    {
        $token = session('jwt_token');
        $data = [
            'pillar_id' => (int) $request->pillar_id,
            'title' => $request->title,
            'description' => $request->description ?? '',
            'target_date' => $request->target_date,
            'valid_from' => $request->valid_from,
            'valid_until' => $request->valid_until,
        ];
        $response = $this->apiClient->post('/api/strategic/goals', $data, $token);
        if ($response['success'] ?? false) {
            return redirect()->route('strategic.goals.index')->with('success', 'تم إنشاء الهدف بنجاح');
        }
        return back()->with('error', $response['detail'] ?? 'فشل إنشاء الهدف')->withInput();
    }

    public function show($id)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->get("/api/strategic/goals/{$id}", $token);
        $goal = $response['data'] ?? [];
        if (empty($goal)) return redirect()->route('strategic.goals.index')->with('error', 'الهدف غير موجود');
        return view('strategic.goals.show', compact('goal'));
    }

    public function edit($id)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->get("/api/strategic/goals/{$id}", $token);
        $goal = $response['data'] ?? [];
        $pillarsResponse = $this->apiClient->get('/api/strategic/pillars', $token);
        $pillars = $pillarsResponse['data'] ?? [];
        if (empty($goal)) return redirect()->route('strategic.goals.index')->with('error', 'الهدف غير موجود');
        return view('strategic.goals.edit', compact('goal', 'pillars'));
    }

    public function update(Request $request, $id)
    {
        $token = session('jwt_token');
        $data = [
            'pillar_id' => (int) $request->pillar_id,
            'title' => $request->title,
            'description' => $request->description ?? '',
            'target_date' => $request->target_date,
            'valid_from' => $request->valid_from,
            'valid_until' => $request->valid_until,
        ];
        $response = $this->apiClient->put("/api/strategic/goals/{$id}", $data, $token);
        if ($response['success'] ?? false) {
            return redirect()->route('strategic.goals.show', $id)->with('success', 'تم تحديث الهدف');
        }
        return back()->with('error', $response['detail'] ?? 'فشل تحديث الهدف');
    }

    public function destroy($id)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->delete("/api/strategic/goals/{$id}", $token);
        if ($response['success'] ?? false) {
            return redirect()->route('strategic.goals.index')->with('success', 'تم حذف الهدف');
        }
        return back()->with('error', $response['detail'] ?? 'فشل حذف الهدف');
    }
}

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
        
        // استخراج البيانات بأمان
        $goals = [];
        if (isset($response['data'])) {
            $goals = is_array($response['data']) ? $response['data'] : [];
        } elseif (isset($response['success']) && is_array($response)) {
            $goals = $response;
        }

        return view('strategic.goals.index', compact('goals'));
    }

    public function create()
    {
        $token = session('jwt_token');
        $pillarsResponse = $this->apiClient->get('/api/strategic/pillars', $token);
        $pillars = [];
        if (isset($pillarsResponse['data'])) {
            $pillars = is_array($pillarsResponse['data']) ? $pillarsResponse['data'] : [];
        }

        return view('strategic.goals.create', compact('pillars'));
    }

    public function store(Request $request)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->post('/api/strategic/goals', $request->all(), $token);

        if ($response['success'] ?? false) {
            return redirect()->route('strategic.goals.index')->with('success', 'تم إنشاء الهدف بنجاح');
        }
        return back()->with('error', $response['detail'] ?? 'فشل إنشاء الهدف')->withInput();
    }

    public function show($id)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->get("/api/strategic/goals/{$id}", $token);
        $goal = $response['data'] ?? $response ?? [];

        if (empty($goal)) {
            return redirect()->route('strategic.goals.index')->with('error', 'الهدف غير موجود');
        }

        return view('strategic.goals.show', compact('goal'));
    }

    public function edit($id)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->get("/api/strategic/goals/{$id}", $token);
        $goal = $response['data'] ?? $response ?? [];

        $pillarsResponse = $this->apiClient->get('/api/strategic/pillars', $token);
        $pillars = $pillarsResponse['data'] ?? [];

        if (empty($goal)) {
            return redirect()->route('strategic.goals.index')->with('error', 'الهدف غير موجود');
        }

        return view('strategic.goals.edit', compact('goal', 'pillars'));
    }

    public function update(Request $request, $id)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->put("/api/strategic/goals/{$id}", $request->all(), $token);

        if ($response['success'] ?? false) {
            return redirect()->route('strategic.goals.show', $id)->with('success', 'تم تحديث الهدف بنجاح');
        }
        return back()->with('error', $response['detail'] ?? 'فشل تحديث الهدف');
    }
}

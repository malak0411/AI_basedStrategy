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

    /**
     * عرض قائمة البرامج
     */
    public function index()
    {
        $token = session('jwt_token');
        $response = $this->apiClient->get('/api/strategic/programs', $token);
        
        $programs = [];
        if (isset($response['data']) && is_array($response['data'])) {
            $programs = $response['data'];
        }

        return view('strategic.programs.index', compact('programs'));
    }

    /**
     * عرض صفحة إنشاء برنامج جديد
     */
    public function create()
    {
        $token = session('jwt_token');
        $goalsResponse = $this->apiClient->get('/api/strategic/goals', $token);
        $goals = [];
        if (isset($goalsResponse['data']) && is_array($goalsResponse['data'])) {
            $goals = $goalsResponse['data'];
        }

        return view('strategic.programs.create', compact('goals'));
    }

    /**
     * تخزين برنامج جديد
     */
    public function store(Request $request)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->post('/api/strategic/programs', $request->all(), $token);

        if ($response['success'] ?? false) {
            return redirect()->route('strategic.programs.index')
                ->with('success', 'تم إنشاء البرنامج بنجاح');
        }
        return back()->with('error', $response['detail'] ?? 'فشل إنشاء البرنامج')->withInput();
    }

    /**
     * عرض تفاصيل برنامج
     */
    public function show($id)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->get("/api/strategic/programs/{$id}", $token);
        $program = $response['data'] ?? [];

        if (empty($program)) {
            return redirect()->route('strategic.programs.index')
                ->with('error', 'البرنامج غير موجود');
        }

        return view('strategic.programs.show', compact('program'));
    }

    /**
     * عرض صفحة تعديل برنامج
     */
    public function edit($id)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->get("/api/strategic/programs/{$id}", $token);
        $program = $response['data'] ?? [];

        $goalsResponse = $this->apiClient->get('/api/strategic/goals', $token);
        $goals = [];
        if (isset($goalsResponse['data']) && is_array($goalsResponse['data'])) {
            $goals = $goalsResponse['data'];
        }

        if (empty($program)) {
            return redirect()->route('strategic.programs.index')
                ->with('error', 'البرنامج غير موجود');
        }

        return view('strategic.programs.edit', compact('program', 'goals'));
    }

    /**
     * تحديث برنامج
     */
    public function update(Request $request, $id)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->put("/api/strategic/programs/{$id}", $request->all(), $token);

        if ($response['success'] ?? false) {
            return redirect()->route('strategic.programs.show', $id)
                ->with('success', 'تم تحديث البرنامج بنجاح');
        }
        return back()->with('error', $response['detail'] ?? 'فشل تحديث البرنامج');
    }
}

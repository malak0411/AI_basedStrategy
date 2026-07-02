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

    /**
     * عرض قائمة المبادرات
     */
    public function index()
    {
        $token = session('jwt_token');
        $response = $this->apiClient->get('/api/strategic/initiatives', $token);
        
        $initiatives = [];
        if (isset($response['data']) && is_array($response['data'])) {
            $initiatives = $response['data'];
        }

        return view('strategic.initiatives.index', compact('initiatives'));
    }

    /**
     * عرض صفحة إنشاء مبادرة جديدة
     */
    public function create()
    {
        $token = session('jwt_token');
        $programsResponse = $this->apiClient->get('/api/strategic/programs', $token);
        $programs = [];
        if (isset($programsResponse['data']) && is_array($programsResponse['data'])) {
            $programs = $programsResponse['data'];
        }

        return view('strategic.initiatives.create', compact('programs'));
    }

    /**
     * تخزين مبادرة جديدة
     */
    public function store(Request $request)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->post('/api/strategic/initiatives', $request->all(), $token);

        if ($response['success'] ?? false) {
            return redirect()->route('strategic.initiatives.index')
                ->with('success', 'تم إنشاء المبادرة بنجاح');
        }
        return back()->with('error', $response['detail'] ?? 'فشل إنشاء المبادرة')->withInput();
    }

    /**
     * عرض تفاصيل مبادرة
     */
    public function show($id)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->get("/api/strategic/initiatives/{$id}", $token);
        $initiative = $response['data'] ?? [];

        if (empty($initiative)) {
            return redirect()->route('strategic.initiatives.index')
                ->with('error', 'المبادرة غير موجودة');
        }

        return view('strategic.initiatives.show', compact('initiative'));
    }

    /**
     * عرض صفحة تعديل مبادرة
     */
    public function edit($id)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->get("/api/strategic/initiatives/{$id}", $token);
        $initiative = $response['data'] ?? [];

        $programsResponse = $this->apiClient->get('/api/strategic/programs', $token);
        $programs = [];
        if (isset($programsResponse['data']) && is_array($programsResponse['data'])) {
            $programs = $programsResponse['data'];
        }

        if (empty($initiative)) {
            return redirect()->route('strategic.initiatives.index')
                ->with('error', 'المبادرة غير موجودة');
        }

        return view('strategic.initiatives.edit', compact('initiative', 'programs'));
    }

    /**
     * تحديث مبادرة
     */
    public function update(Request $request, $id)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->put("/api/strategic/initiatives/{$id}", $request->all(), $token);

        if ($response['success'] ?? false) {
            return redirect()->route('strategic.initiatives.show', $id)
                ->with('success', 'تم تحديث المبادرة بنجاح');
        }
        return back()->with('error', $response['detail'] ?? 'فشل تحديث المبادرة');
    }
}

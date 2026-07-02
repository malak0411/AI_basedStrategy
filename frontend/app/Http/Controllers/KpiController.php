<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\ApiClient;
use Illuminate\Http\Request;

class KpiController extends Controller
{
    protected $apiClient;

    public function __construct()
    {
        $this->apiClient = new ApiClient();
    }

    public function index()
    {
        $token = session('jwt_token');
        $response = $this->apiClient->get('/api/kpis', $token);
        $kpis = $response['data'] ?? [];

        return view('kpis.index', compact('kpis'));
    }

    public function create()
    {
        return view('kpis.create');
    }

    public function store(Request $request)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->post('/api/kpis', $request->all(), $token);

        if ($response['success']) {
            return redirect()->route('kpis.index')->with('success', 'تم إنشاء المؤشر بنجاح');
        }
        return back()->with('error', $response['detail'] ?? 'فشل إنشاء المؤشر')->withInput();
    }

    public function show($id)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->get("/api/kpis/{$id}", $token);
        $kpi = $response['data'] ?? [];

        if (empty($kpi)) {
            return redirect()->route('kpis.index')->with('error', 'المؤشر غير موجود');
        }

        return view('kpis.show', compact('kpi'));
    }

    public function edit($id)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->get("/api/kpis/{$id}", $token);
        $kpi = $response['data'] ?? [];

        if (empty($kpi)) {
            return redirect()->route('kpis.index')->with('error', 'المؤشر غير موجود');
        }

        return view('kpis.edit', compact('kpi'));
    }

    public function update(Request $request, $id)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->put("/api/kpis/{$id}", $request->all(), $token);

        if ($response['success']) {
            return redirect()->route('kpis.show', $id)->with('success', 'تم تحديث المؤشر بنجاح');
        }
        return back()->with('error', $response['detail'] ?? 'فشل تحديث المؤشر');
    }

    public function measurements($id)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->get("/api/kpis/{$id}/measurements", $token);
        $measurements = $response['data'] ?? [];
        $kpi = $response['kpi'] ?? [];

        return view('kpis.measurements', compact('measurements', 'kpi', 'id'));
    }

    public function createMeasurement($id)
    {
        return view('kpis.measurements-create', compact('id'));
    }

    public function storeMeasurement(Request $request, $id)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->post("/api/kpis/{$id}/measurements", $request->all(), $token);

        if ($response['success']) {
            return redirect()->route('kpis.measurements', $id)->with('success', 'تم تسجيل القياس بنجاح');
        }
        return back()->with('error', $response['detail'] ?? 'فشل تسجيل القياس');
    }
}

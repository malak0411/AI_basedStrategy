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

    // ============================================================
    // INDEX - لوحة مؤشرات الأداء الرئيسية
    // ============================================================
    public function index()
    {
        $token = session('jwt_token');
        $response = $this->apiClient->get('/api/kpis/', $token);
        $kpis = $response['data'] ?? [];

        // إحصائيات
        $total = count($kpis);
        $onTarget = 0;
        $belowTarget = 0;
        foreach ($kpis as $kpi) {
            $current = $kpi['current_value'] ?? 0;
            $target = $kpi['target_value'] ?? 100;
            if ($current >= $target) $onTarget++;
            else $belowTarget++;
        }

        return view('kpis.index', compact('kpis', 'total', 'onTarget', 'belowTarget'));
    }

    // ============================================================
    // CREATE - صفحة إنشاء مؤشر
    // ============================================================
    public function create()
    {
        return view('kpis.create');
    }

    // ============================================================
    // STORE - تخزين مؤشر جديد
    // ============================================================
    public function store(Request $request)
    {
        $token = session('jwt_token');
        $request->validate([
            'name' => 'required|string|max:255',
            'target_max' => 'required|numeric',
        ]);

        $response = $this->apiClient->post('/api/kpis/', [
            'name' => $request->name,
            'description' => $request->description ?? '',
            'category' => $request->category ?? '',
            'unit' => $request->unit ?? '%',
            'target_min' => (float)($request->target_min ?? 0),
            'target_max' => (float)$request->target_max,
            'calculation_method' => $request->calculation_method ?? '',
        ], $token);

        if ($response['success'] ?? false) {
            return redirect()->route('kpis.index')->with('success', 'تم إنشاء المؤشر بنجاح');
        }
        return back()->with('error', $response['detail'] ?? 'فشل إنشاء المؤشر')->withInput();
    }

    // ============================================================
    // SHOW - تفاصيل مؤشر مع القياسات
    // ============================================================
    public function show($id)
    {
        $token = session('jwt_token');
        $kpiResponse = $this->apiClient->get("/api/kpis/{$id}", $token);
        $kpi = $kpiResponse['data'] ?? [];

        $measurementsResponse = $this->apiClient->get("/api/kpis/{$id}/measurements", $token);
        $measurements = $measurementsResponse['data'] ?? [];

        if (empty($kpi)) return redirect()->route('kpis.index')->with('error', 'المؤشر غير موجود');

        // إحصائيات
        $values = array_column($measurements, 'value');
        $avg = count($values) > 0 ? round(array_sum($values) / count($values), 1) : 0;
        $max = count($values) > 0 ? max($values) : 0;
        $min = count($values) > 0 ? min($values) : 0;
        $trend = 'stable';
        if (count($values) >= 2) {
            $last = $values[0];
            $prev = $values[1] ?? $last;
            $trend = $last > $prev ? 'up' : ($last < $prev ? 'down' : 'stable');
        }

        return view('kpis.show', compact('kpi', 'measurements', 'avg', 'max', 'min', 'trend'));
    }

    // ============================================================
    // EDIT - تعديل مؤشر
    // ============================================================
    public function edit($id)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->get("/api/kpis/{$id}", $token);
        $kpi = $response['data'] ?? [];

        if (empty($kpi)) return redirect()->route('kpis.index')->with('error', 'المؤشر غير موجود');
        return view('kpis.edit', compact('kpi'));
    }

    // ============================================================
    // UPDATE - تحديث مؤشر
    // ============================================================
    public function update(Request $request, $id)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->put("/api/kpis/{$id}", [
            'name' => $request->name,
            'description' => $request->description ?? '',
            'category' => $request->category ?? '',
            'unit' => $request->unit ?? '%',
            'target_min' => (float)($request->target_min ?? 0),
            'target_max' => (float)($request->target_max ?? 100),
        ], $token);

        if ($response['success'] ?? false) {
            return redirect()->route('kpis.show', $id)->with('success', 'تم تحديث المؤشر');
        }
        return back()->with('error', $response['detail'] ?? 'فشل التحديث');
    }

    // ============================================================
    // DESTROY - حذف مؤشر
    // ============================================================
    public function destroy($id)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->delete("/api/kpis/{$id}", $token);

        if ($response['success'] ?? false) {
            return redirect()->route('kpis.index')->with('success', 'تم حذف المؤشر');
        }
        return back()->with('error', $response['detail'] ?? 'فشل الحذف');
    }

    // ============================================================
    // MEASUREMENTS - قياسات المؤشر
    // ============================================================
    public function measurements($id)
    {
        $token = session('jwt_token');
        $kpiResponse = $this->apiClient->get("/api/kpis/{$id}", $token);
        $kpi = $kpiResponse['data'] ?? [];

        $measurementsResponse = $this->apiClient->get("/api/kpis/{$id}/measurements", $token);
        $measurements = $measurementsResponse['data'] ?? [];

        if (empty($kpi)) return redirect()->route('kpis.index')->with('error', 'المؤشر غير موجود');
        return view('kpis.measurements', compact('kpi', 'measurements', 'id'));
    }

    // ============================================================
    // CREATE MEASUREMENT - إضافة قياس
    // ============================================================
    public function createMeasurement($id)
    {
        $token = session('jwt_token');
        $kpiResponse = $this->apiClient->get("/api/kpis/{$id}", $token);
        $kpi = $kpiResponse['data'] ?? [];

        return view('kpis.measurements-create', compact('kpi', 'id'));
    }

    // ============================================================
    // STORE MEASUREMENT - تخزين قياس جديد
    // ============================================================
    public function storeMeasurement(Request $request, $id)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->post("/api/kpis/{$id}/measurements", [
            'value' => (float)$request->value,
            'measured_at' => $request->measured_at ?? date('Y-m-d'),
            'notes' => $request->notes ?? '',
        ], $token);

        if ($response['success'] ?? false) {
            return redirect()->route('kpis.measurements', $id)->with('success', 'تم تسجيل القياس');
        }
        return back()->with('error', $response['detail'] ?? 'فشل تسجيل القياس')->withInput();
    }

    // ============================================================
    // DELETE MEASUREMENT - حذف قياس
    // ============================================================
    public function destroyMeasurement($measurementId)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->delete("/api/kpis/measurements/{$measurementId}", $token);

        if ($response['success'] ?? false) {
            return back()->with('success', 'تم حذف القياس');
        }
        return back()->with('error', $response['detail'] ?? 'فشل الحذف');
    }
}

<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class KpiController extends Controller
{
    protected $apiClient;

    public function __construct(ApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    private function isAuthorized(): bool
    {
        $role = session('user_role', 'employee');
        return in_array($role, ['manager', 'general_manager', 'deputy', 'minister', 'admin', 'super_admin']);
    }

    public function index(Request $request)
    {
        try {
            $token = session('jwt_token');
            if (!$token) {
                return redirect()->route('login')->with('error', 'الرجاء تسجيل الدخول أولاً');
            }

            $params = [
                'page' => $request->get('page', 1),
                'per_page' => $request->get('per_page', 10),
            ];

            if ($request->has('search')) {
                $params['search'] = $request->get('search');
            }

            if ($request->has('category')) {
                $params['category'] = $request->get('category');
            }

            if ($request->has('status')) {
                $params['status'] = $request->get('status');
            }

            if ($request->has('goal_id')) {
                $params['goal_id'] = $request->get('goal_id');
            }

            $response = $this->apiClient->get('/api/kpis', $token, $params);
            $kpis = $response['data'] ?? [];
            $pagination = [
                'total' => $response['total'] ?? 0,
                'page' => $response['page'] ?? 1,
                'per_page' => $response['per_page'] ?? 10,
                'total_pages' => $response['total_pages'] ?? 0,
            ];

            $categories = $this->getCategories($token);
            $goals = $this->getGoals($token);

            return view('kpis.index', compact('kpis', 'pagination', 'categories', 'goals'));

        } catch (\Exception $e) {
            Log::error('KPI index error: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء تحميل المؤشرات');
        }
    }

    public function create()
    {
        try {
            $token = session('jwt_token');
            if (!$token) {
                return redirect()->route('login')->with('error', 'الرجاء تسجيل الدخول أولاً');
            }

            if (!$this->isAuthorized()) {
                return redirect()->route('kpis.index')->with('error', 'غير مصرح');
            }

            $goalsResponse = $this->apiClient->get('/api/kpis/goals', $token);
            $goals = $goalsResponse['data'] ?? [];

            return view('kpis.create', compact('goals'));

        } catch (\Exception $e) {
            Log::error('KPI create error: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء تحميل البيانات');
        }
    }

    public function store(Request $request)
    {
        try {
            $token = session('jwt_token');
            if (!$token) {
                return redirect()->route('login')->with('error', 'الرجاء تسجيل الدخول أولاً');
            }

            if (!$this->isAuthorized()) {
                return redirect()->route('kpis.index')->with('error', 'غير مصرح');
            }

            $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'category' => 'nullable|string|max:100',
                'unit' => 'nullable|string|max:50',
                'target_min' => 'nullable|numeric',
                'target_max' => 'nullable|numeric',
                'calculation_method' => 'nullable|string',
                'goal_id' => 'required|integer',
                'target_value' => 'required|numeric',
                'baseline_value' => 'nullable|numeric',
                'weight' => 'nullable|numeric|min:0|max:100'
            ]);

            $data = [
                'name' => $request->name,
                'description' => $request->description,
                'category' => $request->category,
                'unit' => $request->unit,
                'target_min' => $request->target_min,
                'target_max' => $request->target_max,
                'calculation_method' => $request->calculation_method,
                'goal_id' => (int) $request->goal_id,
                'target_value' => (float) $request->target_value,
                'baseline_value' => $request->baseline_value ? (float) $request->baseline_value : null,
                'weight' => $request->weight ? (float) $request->weight : 1.0,
            ];

            $response = $this->apiClient->post('/api/kpis', $data, $token);

            if ($response['success'] ?? false) {
                return redirect()->route('kpis.index')->with('success', 'تم إنشاء المؤشر بنجاح');
            }

            return back()->with('error', $response['detail'] ?? 'فشل إنشاء المؤشر');

        } catch (\Exception $e) {
            Log::error('KPI store error: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء إنشاء المؤشر');
        }
    }

    public function show($id)
    {
        try {
            $token = session('jwt_token');
            if (!$token) {
                return redirect()->route('login')->with('error', 'الرجاء تسجيل الدخول أولاً');
            }

            $response = $this->apiClient->get("/api/kpis/{$id}", $token);
            $kpi = $response ?? [];

            if (empty($kpi)) {
                return redirect()->route('kpis.index')->with('error', 'المؤشر غير موجود');
            }

            $chartResponse = $this->apiClient->get("/api/kpis/{$id}/measurements/chart", $token);
            $chartData = $chartResponse ?? ['labels' => [], 'actual' => [], 'target' => []];

            return view('kpis.show', compact('kpi', 'chartData'));

        } catch (\Exception $e) {
            Log::error('KPI show error: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء تحميل المؤشر');
        }
    }

    public function edit($id)
    {
        try {
            $token = session('jwt_token');
            if (!$token) {
                return redirect()->route('login')->with('error', 'الرجاء تسجيل الدخول أولاً');
            }

            if (!$this->isAuthorized()) {
                return redirect()->route('kpis.index')->with('error', 'غير مصرح');
            }

            $response = $this->apiClient->get("/api/kpis/{$id}", $token);
            $kpi = $response ?? [];

            if (empty($kpi)) {
                return redirect()->route('kpis.index')->with('error', 'المؤشر غير موجود');
            }

            $goalsResponse = $this->apiClient->get('/api/kpis/goals', $token);
            $goals = $goalsResponse['data'] ?? [];

            return view('kpis.edit', compact('kpi', 'goals'));

        } catch (\Exception $e) {
            Log::error('KPI edit error: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء تحميل البيانات');
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $token = session('jwt_token');
            if (!$token) {
                return redirect()->route('login')->with('error', 'الرجاء تسجيل الدخول أولاً');
            }

            if (!$this->isAuthorized()) {
                return redirect()->route('kpis.index')->with('error', 'غير مصرح');
            }

            $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'category' => 'nullable|string|max:100',
                'unit' => 'nullable|string|max:50',
                'target_min' => 'nullable|numeric',
                'target_max' => 'nullable|numeric',
                'calculation_method' => 'nullable|string',
                'goal_id' => 'required|integer',
                'target_value' => 'required|numeric',
                'baseline_value' => 'nullable|numeric',
                'weight' => 'nullable|numeric|min:0|max:100'
            ]);

            $data = [
                'name' => $request->name,
                'description' => $request->description,
                'category' => $request->category,
                'unit' => $request->unit,
                'target_min' => $request->target_min,
                'target_max' => $request->target_max,
                'calculation_method' => $request->calculation_method,
                'goal_id' => (int) $request->goal_id,
                'target_value' => (float) $request->target_value,
                'baseline_value' => $request->baseline_value ? (float) $request->baseline_value : null,
                'weight' => $request->weight ? (float) $request->weight : 1.0,
            ];

            $response = $this->apiClient->put("/api/kpis/{$id}", $data, $token);

            if ($response['success'] ?? false) {
                return redirect()->route('kpis.index')->with('success', 'تم تحديث المؤشر بنجاح');
            }

            return back()->with('error', $response['detail'] ?? 'فشل تحديث المؤشر');

        } catch (\Exception $e) {
            Log::error('KPI update error: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء تحديث المؤشر');
        }
    }

    public function destroy($id)
    {
        try {
            $token = session('jwt_token');
            if (!$token) {
                return response()->json(['success' => false, 'error' => 'غير مصرح'], 401);
            }

            if (!$this->isAuthorized()) {
                return response()->json(['success' => false, 'error' => 'غير مصرح'], 403);
            }

            $response = $this->apiClient->delete("/api/kpis/{$id}", $token);

            if ($response['success'] ?? false) {
                return response()->json(['success' => true, 'message' => $response['message'] ?? 'تم حذف المؤشر']);
            }

            return response()->json(['success' => false, 'error' => $response['detail'] ?? 'فشل حذف المؤشر'], 500);

        } catch (\Exception $e) {
            Log::error('KPI delete error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function measurements($id)
    {
        try {
            $token = session('jwt_token');
            if (!$token) {
                return redirect()->route('login')->with('error', 'الرجاء تسجيل الدخول أولاً');
            }

            $kpiResponse = $this->apiClient->get("/api/kpis/{$id}", $token);
            $kpi = $kpiResponse ?? [];

            if (empty($kpi)) {
                return redirect()->route('kpis.index')->with('error', 'المؤشر غير موجود');
            }

            $measurementsResponse = $this->apiClient->get("/api/kpis/{$id}/measurements", $token);
            $measurements = $measurementsResponse['data'] ?? [];

            return view('kpis.measurements', compact('kpi', 'measurements'));

        } catch (\Exception $e) {
            Log::error('KPI measurements error: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء تحميل القياسات');
        }
    }

    public function createMeasurement($id)
    {
        try {
            $token = session('jwt_token');
            if (!$token) {
                return redirect()->route('login')->with('error', 'الرجاء تسجيل الدخول أولاً');
            }

            $response = $this->apiClient->get("/api/kpis/{$id}", $token);
            $kpi = $response ?? [];

            if (empty($kpi)) {
                return redirect()->route('kpis.index')->with('error', 'المؤشر غير موجود');
            }

            return view('kpis.measurements-create', compact('kpi'));

        } catch (\Exception $e) {
            Log::error('KPI create measurement error: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء تحميل البيانات');
        }
    }

    public function storeMeasurement(Request $request, $id)
    {
        try {
            $token = session('jwt_token');
            if (!$token) {
                return redirect()->route('login')->with('error', 'الرجاء تسجيل الدخول أولاً');
            }

            $request->validate([
                'value' => 'required|numeric',
                'measured_at' => 'nullable|date',
                'notes' => 'nullable|string|max:2000'
            ]);

            $data = [
                'value' => (float) $request->value,
                'measured_at' => $request->measured_at ?? date('Y-m-d'),
                'notes' => $request->notes
            ];

            $response = $this->apiClient->post("/api/kpis/{$id}/measurements", $data, $token);

            if ($response['success'] ?? false) {
                return redirect()->route('kpis.show', $id)->with('success', 'تم تسجيل القياس بنجاح');
            }

            return back()->with('error', $response['detail'] ?? 'فشل تسجيل القياس');

        } catch (\Exception $e) {
            Log::error('KPI store measurement error: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء تسجيل القياس');
        }
    }

    public function destroyMeasurement($measurementId)
    {
        try {
            $token = session('jwt_token');
            if (!$token) {
                return response()->json(['success' => false, 'error' => 'غير مصرح'], 401);
            }

            $response = $this->apiClient->delete("/api/kpis/measurements/{$measurementId}", $token);

            if ($response['success'] ?? false) {
                return response()->json(['success' => true, 'message' => 'تم حذف القياس']);
            }

            return response()->json(['success' => false, 'error' => $response['detail'] ?? 'فشل حذف القياس'], 500);

        } catch (\Exception $e) {
            Log::error('KPI delete measurement error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    private function getCategories($token)
    {
        try {
            $response = $this->apiClient->get('/api/kpis', $token);
            $kpis = $response['data'] ?? [];
            $categories = array_unique(array_column($kpis, 'category'));
            return array_filter($categories);
        } catch (\Exception $e) {
            return [];
        }
    }

    private function getGoals($token)
    {
        try {
            $response = $this->apiClient->get('/api/kpis/goals', $token);
            return $response['data'] ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }
}

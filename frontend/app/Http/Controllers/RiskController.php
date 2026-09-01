<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RiskController extends Controller
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

            if ($request->has('risk_level_id')) {
                $params['risk_level_id'] = $request->get('risk_level_id');
            }

            if ($request->has('status_id')) {
                $params['status_id'] = $request->get('status_id');
            }

            if ($request->has('task_id')) {
                $params['task_id'] = $request->get('task_id');
            }

            if ($request->has('search')) {
                $params['search'] = $request->get('search');
            }

            if ($request->has('overdue')) {
                $params['overdue'] = $request->get('overdue');
            }

            $response = $this->apiClient->get('/api/risks', $token, $params);
            $risks = $response['data'] ?? [];
            $pagination = [
                'total' => $response['total'] ?? 0,
                'page' => $response['page'] ?? 1,
                'per_page' => $response['per_page'] ?? 10,
                'total_pages' => $response['total_pages'] ?? 0,
            ];

            $optionsResponse = $this->apiClient->get('/api/risks/options', $token);
            $options = $optionsResponse ?? [];

            $totalRisks = $pagination['total'] ?? 0;
            $openRisks = 0;
            $overdueRisks = 0;
            $totalMitigations = 0;
            $completedMitigations = 0;
            $overdueMitigations = 0;

            foreach ($risks as $risk) {
                $status = $risk['status']['name_ar'] ?? '';
                if (!in_array($status, ['مكتمل', 'مغلق', 'completed', 'closed'])) {
                    $openRisks++;
                }
                if ($risk['mitigations_count'] ?? 0 > 0) {
                    $totalMitigations += $risk['mitigations_count'];
                }
            }

            return view('risks.index', compact('risks', 'pagination', 'options', 'totalRisks', 'openRisks', 'overdueRisks'));

        } catch (\Exception $e) {
            Log::error('Risk index error: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء تحميل المخاطر');
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
                return redirect()->route('risks.index')->with('error', 'غير مصرح');
            }

            $response = $this->apiClient->get('/api/risks/options', $token);
            $options = $response ?? [];

            return view('risks.create', compact('options'));

        } catch (\Exception $e) {
            Log::error('Risk create error: ' . $e->getMessage());
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
                return redirect()->route('risks.index')->with('error', 'غير مصرح');
            }

            $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'task_id' => 'nullable|integer',
                'probability' => 'required|integer|min:1|max:5',
                'impact' => 'required|integer|min:1|max:5',
                'target_date' => 'nullable|date',
                'status_id' => 'nullable|integer'
            ]);

            $data = [
                'name' => $request->name,
                'description' => $request->description,
                'task_id' => $request->task_id ? (int) $request->task_id : null,
                'probability' => (int) $request->probability,
                'impact' => (int) $request->impact,
                'target_date' => $request->target_date,
                'status_id' => $request->status_id ? (int) $request->status_id : null
            ];

            $response = $this->apiClient->post('/api/risks', $data, $token);

            if ($response['success'] ?? false) {
                return redirect()->route('risks.index')->with('success', 'تم إنشاء الخطر بنجاح');
            }

            return back()->with('error', $response['detail'] ?? 'فشل إنشاء الخطر');

        } catch (\Exception $e) {
            Log::error('Risk store error: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء إنشاء الخطر');
        }
    }

    public function show($id)
    {
        try {
            $token = session('jwt_token');
            if (!$token) {
                return redirect()->route('login')->with('error', 'الرجاء تسجيل الدخول أولاً');
            }

            $response = $this->apiClient->get("/api/risks/{$id}", $token);
            $risk = $response ?? [];

            if (empty($risk)) {
                return redirect()->route('risks.index')->with('error', 'الخطر غير موجود');
            }

            return view('risks.show', compact('risk'));

        } catch (\Exception $e) {
            Log::error('Risk show error: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء تحميل الخطر');
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
                return redirect()->route('risks.index')->with('error', 'غير مصرح');
            }

            $response = $this->apiClient->get("/api/risks/{$id}", $token);
            $risk = $response ?? [];

            if (empty($risk)) {
                return redirect()->route('risks.index')->with('error', 'الخطر غير موجود');
            }

            $optionsResponse = $this->apiClient->get('/api/risks/options', $token);
            $options = $optionsResponse ?? [];

            return view('risks.edit', compact('risk', 'options'));

        } catch (\Exception $e) {
            Log::error('Risk edit error: ' . $e->getMessage());
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
                return redirect()->route('risks.index')->with('error', 'غير مصرح');
            }

            $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'task_id' => 'nullable|integer',
                'probability' => 'required|integer|min:1|max:5',
                'impact' => 'required|integer|min:1|max:5',
                'target_date' => 'nullable|date',
                'status_id' => 'nullable|integer'
            ]);

            $data = [
                'name' => $request->name,
                'description' => $request->description,
                'task_id' => $request->task_id ? (int) $request->task_id : null,
                'probability' => (int) $request->probability,
                'impact' => (int) $request->impact,
                'target_date' => $request->target_date,
                'status_id' => $request->status_id ? (int) $request->status_id : null
            ];

            $response = $this->apiClient->put("/api/risks/{$id}", $data, $token);

            if ($response['success'] ?? false) {
                return redirect()->route('risks.index')->with('success', 'تم تحديث الخطر بنجاح');
            }

            return back()->with('error', $response['detail'] ?? 'فشل تحديث الخطر');

        } catch (\Exception $e) {
            Log::error('Risk update error: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء تحديث الخطر');
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

            $response = $this->apiClient->delete("/api/risks/{$id}", $token);

            if ($response['success'] ?? false) {
                return response()->json(['success' => true, 'message' => 'تم حذف الخطر']);
            }

            return response()->json(['success' => false, 'error' => $response['detail'] ?? 'فشل حذف الخطر'], 500);

        } catch (\Exception $e) {
            Log::error('Risk delete error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function mitigations($id)
    {
        try {
            $token = session('jwt_token');
            if (!$token) {
                return redirect()->route('login')->with('error', 'الرجاء تسجيل الدخول أولاً');
            }

            $riskResponse = $this->apiClient->get("/api/risks/{$id}", $token);
            $risk = $riskResponse ?? [];

            if (empty($risk)) {
                return redirect()->route('risks.index')->with('error', 'الخطر غير موجود');
            }

            $response = $this->apiClient->get("/api/risks/{$id}/mitigations", $token);
            $mitigations = $response['data'] ?? [];
            $pagination = [
                'total' => $response['total'] ?? 0,
                'page' => $response['page'] ?? 1,
                'per_page' => $response['per_page'] ?? 10,
                'total_pages' => $response['total_pages'] ?? 0,
            ];

            return view('risks.mitigations', compact('risk', 'mitigations', 'pagination'));

        } catch (\Exception $e) {
            Log::error('Risk mitigations error: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء تحميل إجراءات المعالجة');
        }
    }

    public function createMitigation($id)
    {
        try {
            $token = session('jwt_token');
            if (!$token) {
                return redirect()->route('login')->with('error', 'الرجاء تسجيل الدخول أولاً');
            }

            $riskResponse = $this->apiClient->get("/api/risks/{$id}", $token);
            $risk = $riskResponse ?? [];

            if (empty($risk)) {
                return redirect()->route('risks.index')->with('error', 'الخطر غير موجود');
            }

            $optionsResponse = $this->apiClient->get('/api/risks/options', $token);
            $options = $optionsResponse ?? [];

            return view('risks.mitigations-create', compact('risk', 'options'));

        } catch (\Exception $e) {
            Log::error('Risk create mitigation error: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء تحميل البيانات');
        }
    }

    public function storeMitigation(Request $request, $id)
    {
        try {
            $token = session('jwt_token');
            if (!$token) {
                return redirect()->route('login')->with('error', 'الرجاء تسجيل الدخول أولاً');
            }

            $request->validate([
                'action' => 'required|string|max:2000',
                'task_id' => 'nullable|integer',
                'assigned_to' => 'nullable|integer',
                'due_date' => 'nullable|date',
                'status_id' => 'nullable|integer',
                'notes' => 'nullable|string|max:2000'
            ]);

            $data = [
                'action' => $request->action,
                'task_id' => $request->task_id ? (int) $request->task_id : null,
                'assigned_to' => $request->assigned_to ? (int) $request->assigned_to : null,
                'due_date' => $request->due_date,
                'status_id' => $request->status_id ? (int) $request->status_id : null,
                'notes' => $request->notes
            ];

            $response = $this->apiClient->post("/api/risks/{$id}/mitigations", $data, $token);

            if ($response['success'] ?? false) {
                return redirect()->route('risks.mitigations', $id)->with('success', 'تم إنشاء إجراء المعالجة بنجاح');
            }

            return back()->with('error', $response['detail'] ?? 'فشل إنشاء إجراء المعالجة');

        } catch (\Exception $e) {
            Log::error('Risk store mitigation error: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء إنشاء إجراء المعالجة');
        }
    }

    public function editMitigation($mitigationId)
    {
        try {
            $token = session('jwt_token');
            if (!$token) {
                return redirect()->route('login')->with('error', 'الرجاء تسجيل الدخول أولاً');
            }

            if (!$this->isAuthorized()) {
                return redirect()->route('risks.index')->with('error', 'غير مصرح');
            }

            $mitigationResponse = $this->apiClient->get("/api/risks/mitigations/{$mitigationId}", $token);
            $mitigation = $mitigationResponse ?? [];

            if (empty($mitigation)) {
                return redirect()->route('risks.index')->with('error', 'إجراء المعالجة غير موجود');
            }

            $optionsResponse = $this->apiClient->get('/api/risks/options', $token);
            $options = $optionsResponse ?? [];

            return view('risks.mitigations-edit', compact('mitigation', 'options'));

        } catch (\Exception $e) {
            Log::error('Risk edit mitigation error: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء تحميل البيانات');
        }
    }

    public function updateMitigation(Request $request, $mitigationId)
    {
        try {
            $token = session('jwt_token');
            if (!$token) {
                return redirect()->route('login')->with('error', 'الرجاء تسجيل الدخول أولاً');
            }

            if (!$this->isAuthorized()) {
                return redirect()->route('risks.index')->with('error', 'غير مصرح');
            }

            $request->validate([
                'action' => 'required|string|max:2000',
                'task_id' => 'nullable|integer',
                'assigned_to' => 'nullable|integer',
                'due_date' => 'nullable|date',
                'status_id' => 'nullable|integer',
                'notes' => 'nullable|string|max:2000'
            ]);

            $data = [
                'action' => $request->action,
                'task_id' => $request->task_id ? (int) $request->task_id : null,
                'assigned_to' => $request->assigned_to ? (int) $request->assigned_to : null,
                'due_date' => $request->due_date,
                'status_id' => $request->status_id ? (int) $request->status_id : null,
                'notes' => $request->notes
            ];

            $response = $this->apiClient->put("/api/risks/mitigations/{$mitigationId}", $data, $token);

            if ($response['success'] ?? false) {
                return redirect()->back()->with('success', 'تم تحديث إجراء المعالجة بنجاح');
            }

            return back()->with('error', $response['detail'] ?? 'فشل تحديث إجراء المعالجة');

        } catch (\Exception $e) {
            Log::error('Risk update mitigation error: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء تحديث إجراء المعالجة');
        }
    }

    public function destroyMitigation($mitigationId)
    {
        try {
            $token = session('jwt_token');
            if (!$token) {
                return response()->json(['success' => false, 'error' => 'غير مصرح'], 401);
            }

            if (!$this->isAuthorized()) {
                return response()->json(['success' => false, 'error' => 'غير مصرح'], 403);
            }

            $response = $this->apiClient->delete("/api/risks/mitigations/{$mitigationId}", $token);

            if ($response['success'] ?? false) {
                return response()->json(['success' => true, 'message' => 'تم حذف إجراء المعالجة']);
            }

            return response()->json(['success' => false, 'error' => $response['detail'] ?? 'فشل حذف إجراء المعالجة'], 500);

        } catch (\Exception $e) {
            Log::error('Risk delete mitigation error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}

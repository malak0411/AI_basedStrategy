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


            if ($request->filled('risk_level_id')) {
                $params['risk_level_id'] = $request->get('risk_level_id');
            }
            if ($request->filled('status_id')) {
                $params['status_id'] = $request->get('status_id');
            }
            if ($request->filled('task_id')) {
                $params['task_id'] = $request->get('task_id');
            }
            if ($request->filled('search')) {
                $params['search'] = $request->get('search');
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


            return view('risks.index', compact('risks', 'pagination', 'options'));


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


            $optionsResponse = $this->apiClient->get('/api/risks/options', $token);
            $options = $optionsResponse ?? [];


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
                'probability' => 'required|integer|min:1|max:10',
                'impact' => 'required|integer|min:1|max:10',
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


            return back()->withInput()->with('error', $response['detail'] ?? 'فشل إنشاء الخطر');


        } catch (\Exception $e) {
            Log::error('Risk store error: ' . $e->getMessage());
            return back()->withInput()->with('error', 'حدث خطأ أثناء إنشاء الخطر');
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


            $optionsResponse = $this->apiClient->get('/api/risks/options', $token);
            $options = $optionsResponse ?? [];


            $aiRecsResponse = $this->apiClient->get("/api/risks/{$id}/ai-recommendations", $token);
            $aiRecommendations = $aiRecsResponse['data'] ?? [];


            return view('risks.show', compact('risk', 'options', 'aiRecommendations'));


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
                'probability' => 'required|integer|min:1|max:10',
                'impact' => 'required|integer|min:1|max:10',
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


            return back()->withInput()->with('error', $response['detail'] ?? 'فشل تحديث الخطر');


        } catch (\Exception $e) {
            Log::error('Risk update error: ' . $e->getMessage());
            return back()->withInput()->with('error', 'حدث خطأ أثناء تحديث الخطر');
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


            return back()->withInput()->with('error', $response['detail'] ?? 'فشل إنشاء إجراء المعالجة');


        } catch (\Exception $e) {
            Log::error('Risk store mitigation error: ' . $e->getMessage());
            return back()->withInput()->with('error', 'حدث خطأ أثناء إنشاء إجراء المعالجة');
        }
    }


    public function updateMitigation(Request $request, $mitigationId)
    {
        try {
            $token = session('jwt_token');
            if (!$token) {
                return response()->json(['success' => false, 'error' => 'غير مصرح'], 401);
            }


            if (!$this->isAuthorized()) {
                return response()->json(['success' => false, 'error' => 'غير مصرح'], 403);
            }


            $data = [];
            foreach (['action', 'task_id', 'assigned_to', 'due_date', 'status_id', 'notes'] as $field) {
                if ($request->has($field)) {
                    $data[$field] = $request->$field;
                }
            }


            $response = $this->apiClient->put("/api/risks/mitigations/{$mitigationId}", $data, $token);


            if ($response['success'] ?? false) {
                return response()->json(['success' => true, 'message' => 'تم تحديث إجراء المعالجة']);
            }


            return response()->json(['success' => false, 'error' => $response['detail'] ?? 'فشل التحديث'], 500);


        } catch (\Exception $e) {
            Log::error('Risk update mitigation error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
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


            return response()->json(['success' => false, 'error' => $response['detail'] ?? 'فشل الحذف'], 500);


        } catch (\Exception $e) {
            Log::error('Risk delete mitigation error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }


    public function generateRecommendations($id)
    {
        try {
            $token = session('jwt_token');
            if (!$token) {
                return response()->json(['success' => false, 'error' => 'غير مصرح'], 401);
            }


            if (!$this->isAuthorized()) {
                return response()->json(['success' => false, 'error' => 'غير مصرح'], 403);
            }


            $response = $this->apiClient->post("/api/risks/{$id}/recommendations", [], $token);


            if ($response['success'] ?? false) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم توليد التوصيات بنجاح',
                    'data' => $response['data'] ?? []
                ]);
            }


            return response()->json([
                'success' => false,
                'error' => $response['detail'] ?? 'فشل توليد التوصيات'
            ], 500);


        } catch (\Exception $e) {
            Log::error('Risk generate recommendations error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }


    public function reassess($id)
    {
        try {
            $token = session('jwt_token');
            if (!$token) {
                return response()->json(['success' => false, 'error' => 'غير مصرح'], 401);
            }


            if (!$this->isAuthorized()) {
                return response()->json(['success' => false, 'error' => 'غير مصرح'], 403);
            }


            $response = $this->apiClient->post("/api/risks/{$id}/reassess", [], $token);


            if ($response['success'] ?? false) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم إعادة تقييم الخطر',
                    'data' => $response['data'] ?? []
                ]);
            }


            return response()->json([
                'success' => false,
                'error' => $response['detail'] ?? 'فشل إعادة التقييم'
            ], 500);


        } catch (\Exception $e) {
            Log::error('Risk reassess error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }


    public function detectForTask($taskId)
    {
        try {
            $token = session('jwt_token');
            if (!$token) {
                return response()->json(['success' => false, 'error' => 'غير مصرح'], 401);
            }


            $response = $this->apiClient->post("/api/risks/auto-detect/{$taskId}", [], $token);


            return response()->json($response);


        } catch (\Exception $e) {
            Log::error('Risk auto detect error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }


    public function getAiRecommendations($id)
    {
        try {
            $token = session('jwt_token');
            if (!$token) {
                return response()->json(['success' => false, 'error' => 'غير مصرح'], 401);
            }


            $response = $this->apiClient->get("/api/risks/{$id}/ai-recommendations", $token);


            return response()->json($response);


        } catch (\Exception $e) {
            Log::error('Risk get AI recommendations error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}

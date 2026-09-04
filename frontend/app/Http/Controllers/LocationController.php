<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LocationController extends Controller
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
                'latest_only' => true,
                'per_page' => 100
            ];

            $response = $this->apiClient->get('/api/locations', $token, $params);
            $locations = $response['data'] ?? [];

            $summaryResponse = $this->apiClient->get('/api/locations/summary', $token);
            $summary = $summaryResponse ?? [];

            $mapResponse = $this->apiClient->get('/api/locations/map', $token);
            $mapData = $mapResponse['data'] ?? [];

            return view('location.index', compact('locations', 'summary', 'mapData'));

        } catch (\Exception $e) {
            Log::error('Location index error: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء تحميل بيانات المواقع');
        }
    }

    public function history(Request $request)
    {
        try {
            $token = session('jwt_token');
            if (!$token) {
                return redirect()->route('login')->with('error', 'الرجاء تسجيل الدخول أولاً');
            }

            $params = [
                'page' => $request->get('page', 1),
                'per_page' => $request->get('per_page', 20),
            ];

            if ($request->has('employee_id')) {
                $params['employee_id'] = $request->get('employee_id');
            }

            if ($request->has('task_id')) {
                $params['task_id'] = $request->get('task_id');
            }

            if ($request->has('date_from')) {
                $params['date_from'] = $request->get('date_from');
            }

            if ($request->has('date_to')) {
                $params['date_to'] = $request->get('date_to');
            }

            if ($request->has('source')) {
                $params['source'] = $request->get('source');
            }

            $response = $this->apiClient->get('/api/locations', $token, $params);
            $locations = $response['data'] ?? [];
            $pagination = [
                'total' => $response['total'] ?? 0,
                'page' => $response['page'] ?? 1,
                'per_page' => $response['per_page'] ?? 20,
                'total_pages' => $response['total_pages'] ?? 0,
            ];

            $employeesResponse = $this->apiClient->get('/api/employees', $token);
            $employees = $employeesResponse['data'] ?? [];

            $tasksResponse = $this->apiClient->get('/api/tasks', $token);
            $tasks = $tasksResponse['data'] ?? [];

            return view('location.history', compact('locations', 'pagination', 'employees', 'tasks'));

        } catch (\Exception $e) {
            Log::error('Location history error: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء تحميل سجل المواقع');
        }
    }

    public function employee($employeeId)
    {
        try {
            $token = session('jwt_token');
            if (!$token) {
                return redirect()->route('login')->with('error', 'الرجاء تسجيل الدخول أولاً');
            }

            $latestResponse = $this->apiClient->get("/api/locations/employees/{$employeeId}/latest", $token);
            $employeeData = $latestResponse ?? [];

            if (empty($employeeData) || !isset($employeeData['employee'])) {
                return redirect()->route('location.index')->with('error', 'الموظف غير موجود');
            }

            $historyResponse = $this->apiClient->get("/api/locations/employees/{$employeeId}/history", $token);
            $historyData = $historyResponse ?? [];

            return view('location.employee', compact('employeeData', 'historyData', 'employeeId'));

        } catch (\Exception $e) {
            Log::error('Location employee error: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء تحميل بيانات الموظف');
        }
    }

    public function store(Request $request)
    {
        try {
            $token = session('jwt_token');
            if (!$token) {
                return response()->json(['success' => false, 'error' => 'غير مصرح'], 401);
            }

            $request->validate([
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180',
                'accuracy' => 'nullable|numeric|min:0',
                'task_id' => 'nullable|integer',
                'source' => 'nullable|string|max:50'
            ]);

            $employeeId = session('employee_id');
            if (!$employeeId) {
                return response()->json(['success' => false, 'error' => 'المستخدم غير موجود'], 401);
            }

            $data = [
                'employee_id' => (int) $employeeId,
                'latitude' => (float) $request->latitude,
                'longitude' => (float) $request->longitude,
                'accuracy' => $request->accuracy ? (float) $request->accuracy : null,
                'task_id' => $request->task_id ? (int) $request->task_id : null,
                'source' => $request->source ?? 'manual'
            ];

            $response = $this->apiClient->post('/api/locations', $data, $token);

            if ($response['success'] ?? false) {
                return response()->json(['success' => true, 'message' => 'تم تسجيل الموقع بنجاح']);
            }

            return response()->json(['success' => false, 'error' => $response['detail'] ?? 'فشل تسجيل الموقع'], 500);

        } catch (\Exception $e) {
            Log::error('Location store error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($locationId)
    {
        try {
            $token = session('jwt_token');
            if (!$token) {
                return response()->json(['success' => false, 'error' => 'غير مصرح'], 401);
            }

            if (!$this->isAuthorized()) {
                return response()->json(['success' => false, 'error' => 'غير مصرح'], 403);
            }

            $response = $this->apiClient->delete("/api/locations/{$locationId}", $token);

            if ($response['success'] ?? false) {
                return response()->json(['success' => true, 'message' => 'تم حذف سجل الموقع']);
            }

            return response()->json(['success' => false, 'error' => $response['detail'] ?? 'فشل حذف سجل الموقع'], 500);

        } catch (\Exception $e) {
            Log::error('Location delete error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function getMapData()
    {
        try {
            $token = session('jwt_token');
            if (!$token) {
                return response()->json(['success' => false, 'error' => 'غير مصرح'], 401);
            }

            $response = $this->apiClient->get('/api/locations/map', $token);
            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('Location map data error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}

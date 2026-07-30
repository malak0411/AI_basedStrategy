<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\Http\Request;

class OperationalTaskController extends Controller
{
    protected $apiClient;

    public function __construct()
    {
        $this->apiClient = new ApiClient();
    }

    public function majorTasks()
    {
        $token = session('jwt_token');
        $response = $this->apiClient->get('/api/tasks/major-tasks/by-department', $token);
        $tasks = $response['data'] ?? [];
        return view('operational.major-tasks', compact('tasks'));
    }

    public function showMajorTask($id)
    {
        $token = session('jwt_token');
        $majorTaskResponse = $this->apiClient->get("/api/tasks/major-tasks/{$id}/details", $token);
        $majorTask = $majorTaskResponse['data'] ?? [];

        $operationalResponse = $this->apiClient->get("/api/tasks/by-major-task/{$id}", $token);
        $operationalTasks = $operationalResponse['data'] ?? [];

        $departments = $this->apiClient->safeGet('/api/departments', $token, []);

        return view('operational.show-major-task', compact('majorTask', 'operationalTasks', 'departments'));
    }

    public function storeOperational(Request $request)
    {
        $token = session('jwt_token');
        $data = [
            'major_task_id' => (int)$request->major_task_id,
            'department_id' => (int)$request->department_id,
            'title' => $request->title,
            'description' => $request->description ?? '',
            'priority_id' => (int)($request->priority_id ?? 2),
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'estimated_hours' => (float)($request->estimated_hours ?? 0),
            'assigned_employees' => $request->assigned_employees ?? [],
        ];
        $response = $this->apiClient->post('/api/tasks/', $data, $token);
        if ($response['success'] ?? false) {
            return back()->with('success', 'تمت إضافة المهمة التشغيلية');
        }
        return back()->with('error', $response['detail'] ?? 'فشلت الإضافة');
    }

    public function updateOperational(Request $request, $id)
    {
        $token = session('jwt_token');
        $data = [];
        foreach (['title', 'description', 'status_id', 'priority_id', 'end_date', 'estimated_hours'] as $field) {
            if ($request->filled($field)) {
                $data[$field] = $request->$field;
            }
        }
        $response = $this->apiClient->put("/api/tasks/{$id}", $data, $token);
        if ($response['success'] ?? false) {
            return back()->with('success', 'تم تحديث المهمة');
        }
        return back()->with('error', $response['detail'] ?? 'فشل التحديث');
    }

    public function destroyOperational($id)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->delete("/api/tasks/{$id}", $token);
        if ($response['success'] ?? false) {
            return back()->with('success', 'تم حذف المهمة');
        }
        return back()->with('error', $response['detail'] ?? 'فشل الحذف');
    }

    public function generate($majorTaskId)
    {
        $token = session('jwt_token');
        $majorTask = $this->apiClient->get("/api/tasks/major-tasks/{$majorTaskId}/details", $token);
        return view('operational.generate', ['majorTask' => $majorTask['data'] ?? []]);
    }

    public function startGeneration(Request $request)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->post(
            "/api/ai/operational/generate-tasks/{$request->major_task_id}",
            ['instructions' => $request->instructions ?? ''],
            $token
        );
        if ($response['success'] ?? false) {
            $data = $response['data'];
            return redirect()->to('/operational/waiting?job_id=' . $data['job_id'] . '&major_task_id=' . $request->major_task_id);
        }
        return back()->with('error', 'فشل التوليد');
    }

    public function waiting(Request $request)
    {
        return view('operational.waiting', [
            'jobId' => $request->job_id,
            'majorTaskId' => $request->major_task_id
        ]);
    }

    public function review(Request $request)
    {
        $token = session('jwt_token');
        $jobResponse = $this->apiClient->get("/api/ai/jobs/{$request->job_id}", $token);
        $job = $jobResponse['data'] ?? [];
        $tasks = $job['result']['operational_tasks'] ?? [];
        $departments = $this->apiClient->safeGet('/api/departments', $token, []);
        return view('operational.review', compact('tasks', 'job', 'departments'));
    }

    public function editWithPrompt(Request $request)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->post(
            "/api/ai/operational/edit-tasks/{$request->job_id}",
            ['instruction' => $request->instruction],
            $token
        );
        return response()->json($response);
    }

    public function approve(Request $request)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->post(
            "/api/ai/operational/approve-tasks/{$request->job_id}",
            [],
            $token
        );
        if ($response['success'] ?? false) {
            $jobResponse = $this->apiClient->get("/api/ai/jobs/{$request->job_id}", $token);
            $jobData = $jobResponse['data'] ?? [];
            $result = json_decode($jobData['result_json'] ?? '{}', true);
            $majorTaskId = $result['major_task_id'] ?? 0;
            return redirect()->to('/operational/major-task/' . $majorTaskId)
                ->with('success', 'تم حفظ المهام التشغيلية بنجاح');
        }
        return back()->with('error', $response['detail'] ?? 'فشل الاعتماد');
    }
}

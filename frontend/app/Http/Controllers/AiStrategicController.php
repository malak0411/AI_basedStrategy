<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\Http\Request;

class AiStrategicController extends Controller
{
    protected $apiClient;

    public function __construct()
    {
        $this->apiClient = new ApiClient();
    }

    /**
     * الصفحة الرئيسية - اختيار المبادرة
     */
    public function index()
    {
        $token = session('jwt_token');
        $initiatives = $this->apiClient->safeGet('/api/strategic/initiatives', $token, []);
        return view('ai.strategic.generate', compact('initiatives'));
    }

    /**
     * جلب سياق المبادرة (API)
     */
    public function getInitiativeContext($id)
    {
        $token = session('jwt_token');
        
        $initiative = $this->apiClient->safeGet("/api/strategic/initiatives/{$id}", $token, []);
        $swot = $this->apiClient->safeGet('/api/strategic/swot', $token, []);
        $pestel = $this->apiClient->safeGet('/api/strategic/pestel', $token, []);
        $vision = $this->apiClient->safeGet('/api/strategic/vision', $token, []);

        return response()->json([
            'initiative' => $initiative,
            'swot' => $swot,
            'pestel' => $pestel,
            'vision' => $vision,
        ]);
    }

    /**
     * توليد المهام - ثم توجيه تلقائي للمراجعة
     */
    public function generate(Request $request)
    {
        $token = session('jwt_token');
        $initiativeId = $request->initiative_id;

        $response = $this->apiClient->post(
            "/api/ai/strategic/generate-major-tasks/{$initiativeId}",
            ['instructions' => $request->instructions ?? ''],
            $token
        );

        if ($response['success'] ?? false) {
            $data = $response['data'];
            
            // ✅ توجيه تلقائي إلى صفحة المراجعة مع البيانات
            return redirect()->route('ai.strategic.review', [
                'job_id' => $data['job_id'],
                'initiative_id' => $initiativeId
            ])->with('tasks', $data['major_tasks'] ?? []);
        }

        return back()->with('error', $response['detail'] ?? 'فشل توليد المهام');
    }

    /**
     * صفحة المراجعة
     */
    public function review(Request $request)
    {
        $token = session('jwt_token');
        $jobId = $request->job_id;
        $initiativeId = $request->initiative_id;

        // جلب حالة الـ Job
        $jobResponse = $this->apiClient->get("/api/ai/jobs/{$jobId}", $token);
        $job = $jobResponse['data'] ?? [];

        // جلب المبادرة للعرض
        $initiative = $this->apiClient->safeGet("/api/strategic/initiatives/{$initiativeId}", $token, []);

        // المهام من الجلسة أو من الـ Job
        $tasks = session('tasks', []);
        if (empty($tasks) && !empty($job['result']['major_tasks'])) {
            $tasks = $job['result']['major_tasks'];
        }

        return view('ai.strategic.review', compact(
            'job', 'initiative', 'tasks', 'jobId', 'initiativeId'
        ));
    }

    /**
     * تعديل الخطة باستخدام Prompt (API)
     */
    public function editPlan(Request $request)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->post(
            "/api/ai/strategic/edit-task-plan/{$request->job_id}",
            ['instruction' => $request->instruction],
            $token
        );

        if ($response['success'] ?? false) {
            return response()->json($response['data']);
        }
        return response()->json(['error' => $response['detail'] ?? 'فشل التعديل'], 500);
    }

    /**
     * اعتماد الخطة وحفظها - ثم توجيه للمبادرة
     */
    public function approve(Request $request)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->post(
            "/api/ai/strategic/approve-task-plan/{$request->job_id}",
            [],
            $token
        );

        if ($response['success'] ?? false) {
            // ✅ توجيه إلى صفحة المبادرة بعد الحفظ
            return redirect()->route('strategic.initiatives.show', $request->initiative_id)
                ->with('success', $response['data']['message'] ?? 'تم حفظ المهام بنجاح');
        }
        return back()->with('error', $response['detail'] ?? 'فشل الاعتماد');
    }
}

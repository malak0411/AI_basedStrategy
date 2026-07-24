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

    public function index()
    {
        $token = session('jwt_token');
        $initiatives = $this->apiClient->safeGet('/api/strategic/initiatives', $token, []);
        return view('ai.strategic.generate', compact('initiatives'));
    }

    public function waiting(Request $request)
    {
        return view('ai.strategic.waiting', [
            'jobId' => $request->job_id,
            'initiativeId' => $request->initiative_id
        ]);
    }


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


    public function generate(Request $request)

    {
        $token = session('jwt_token');
        $initiativeId = $request->initiative_id;

        $response = $this->apiClient->post(
            "/api/ai/strategic/generate-major-tasks/{$initiativeId}",
            ['instructions' => $request->instructions ?? ''],
            $token
        );


        \Log::info('Generate Response: ' . json_encode($response));

        if ($response['success'] ?? false) {
            $data = $response['data'];
        

            return redirect()->to(
                '/ai/strategic/waiting?job_id=' . $data['job_id'] . '&initiative_id=' . $initiativeId
            );
        }

        return back()->with('error', $response['detail'] ?? 'فشل توليد المهام');
    }


    public function checkJobStatus($job_id)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->get("/api/ai/jobs/{$job_id}", $token);
        return response()->json($response);
    }

    public function review(Request $request)
    {
        $token = session('jwt_token');
        $jobId = $request->job_id;
        $initiativeId = $request->initiative_id;

        $jobResponse = $this->apiClient->get("/api/ai/jobs/{$jobId}", $token);
    
        $tasks = [];
        if (!empty($jobResponse['data']['result']['major_tasks'])) {
        $tasks = $jobResponse['data']['result']['major_tasks'];
        }

        $initiative = $this->apiClient->safeGet("/api/strategic/initiatives/{$initiativeId}", $token, []);

        return view('ai.strategic.review', compact('tasks', 'initiative', 'jobId', 'initiativeId'));
    }

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

    public function approve(Request $request)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->post(
            "/api/ai/strategic/approve-task-plan/{$request->job_id}",
            [],
            $token
        );

        if ($response['success'] ?? false) {
            return redirect()->route('strategic.initiatives.show', $request->initiative_id)
                ->with('success', $response['data']['message'] ?? 'تم حفظ المهام بنجاح');
        }
        return back()->with('error', $response['detail'] ?? 'فشل الاعتماد');
    }
}

<?php


namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\ApiClient;



class AiStrategicController extends Controller
{
    protected $apiClient;


    public function __construct(ApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    private function isManager(): bool
    {
        $role = session('user_role', 'employee');

        return in_array($role, [
            'manager',
            'general_manager',
            'deputy',
            'minister',
            'admin',
            'super_admin'
        ]);
    }


    public function generate($initiativeId, Request $request)
    {
        if (!$this->isManager()) {
            return redirect()->route('dashboard.employee')->with('error', 'غير مصرح');
        }


        $token = session('jwt_token');


        $strategicMap = [];
        $mapResponse = $this->apiClient->get(
            "/api/ai/strategic/strategic-map/{$initiativeId}",
            $token
        );
        if (!empty($mapResponse['success']) && is_array($mapResponse['data'] ?? null)) {
            $strategicMap = $mapResponse['data'];
        }


        return view('ai.strategic.generate', [
            'strategicMap' => $strategicMap,
            'initiativeId' => (int) $initiativeId,
            'jobId'        => $request->query('job_id'),
        ]);
    }


    public function startGeneration(Request $request)
    {
        if (!$this->isManager()) {
            return back()->with('error', 'غير مصرح');
        }


        $request->validate([
            'initiative_id' => 'required|integer',
            'instructions'  => 'nullable|string',
        ]);


        $token = session('jwt_token');


        $response = $this->apiClient->post(
            "/api/ai/strategic/generate-major-tasks/{$request->initiative_id}",
            ['instructions' => $request->input('instructions', '')],
            $token
        );


        if ($response['success'] ?? false) {
            $jobId = $response['data']['job_id'] ?? null;
            if (!$jobId) {
                return back()->with('error', 'تعذر إنشاء وظيفة التوليد');
            }
            return redirect()->route('strategic.generate', [
                'id'     => $request->initiative_id,
                'job_id' => $jobId,
            ]);
        }


        return back()->with('error',
            $response['detail'] ?? $response['message'] ?? 'فشل توليد المهام الرئيسية'
        );
    }


    public function checkJobStatus($job_id)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->get("/api/ai/jobs/{$job_id}", $token);
        return response()->json($response);
    }


    public function review(Request $request)
    {
        if (!$this->isManager()) {
            return redirect()->route('dashboard.employee')->with('error', 'غير مصرح');
        }


        $request->validate(['job_id' => 'required|integer']);


        $token = session('jwt_token');


        $jobResponse = $this->apiClient->get(
            "/api/ai/jobs/{$request->job_id}", $token
        );


        if (!($jobResponse['success'] ?? false)) {
            return redirect()->route('dashboard.employee')
                ->with('error', 'تعذر الحصول على نتيجة التوليد');
        }


        $job    = $jobResponse['data'] ?? [];
        $result = is_array($job['result'] ?? null) ? $job['result'] : [];
        $tasks  = is_array($result['major_tasks'] ?? null) ? $result['major_tasks'] : [];
        $initiativeId = $result['initiative_id'] ?? null;
        $jobStatus    = $job['status'] ?? 'review';


        $strategicMap = [];
        $priorities   = [];
        $departments  = [];


        if ($initiativeId) {
            $mapResponse = $this->apiClient->get(
                "/api/ai/strategic/strategic-map/{$initiativeId}", $token
            );
            if ($mapResponse['success'] ?? false) {
                $strategicMap = $mapResponse['data'] ?? [];
                $priorities   = $strategicMap['priorities'] ?? [];
                $departments  = $strategicMap['departments'] ?? [];
            }
        }


        if (empty($priorities)) {
            $pr = $this->apiClient->safeGet('/api/priorities', $token, []);
            $priorities = $pr['data'] ?? (is_array($pr) ? $pr : []);
        }


        return view('ai.strategic.review', [
            'job'           => $job,
            'tasks'         => $tasks,
            'strategicMap'  => $strategicMap,
            'priorities'    => $priorities,
            'departments'   => $departments,
            'initiativeId'  => $initiativeId,
            'jobId'         => $request->job_id,
            'jobStatus'     => $jobStatus,
        ]);
    }


    public function editWithPrompt(Request $request, $job_id)
    {
        if (!$this->isManager()) {
            return response()->json(['success' => false, 'detail' => 'غير مصرح'], 403);
        }


        $validator = \Validator::make($request->all(), [
            'instruction' => 'required|string|min:3',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'detail'  => $validator->errors()->first(),
            ], 422);
        }


        $token = session('jwt_token');
        if (!$token) {
            return response()->json(['success' => false, 'detail' => 'انتهت الجلسة، أعد تسجيل الدخول'], 401);
        }


        $response = $this->apiClient->post(
            "/api/ai/strategic/edit-tasks/{$job_id}",
            ['instruction' => $request->input('instruction')],
            $token
        );


        $success = (bool) ($response['success'] ?? false);
        if (!$success) {
            $detail = $response['detail']
                ?? $response['message']
                ?? $response['error']
                ?? 'فشل بدء إعادة التوليد';
            return response()->json(['success' => false, 'detail' => $detail]);
        }


        return response()->json([
            'success' => true,
            'data'    => [
                'job_id' => (int) $job_id,
                'status' => $response['data']['status'] ?? 'editing',
            ],
        ]);
    }


    public function updateTasks(Request $request, $job_id)
    {
        if (!$this->isManager()) {
            return response()->json(['success' => false, 'detail' => 'غير مصرح'], 403);
        }


        $token = session('jwt_token');
        if (!$token) {
            return response()->json(['success' => false, 'detail' => 'انتهت الجلسة'], 401);
        }


        $payload = $request->input('major_tasks');
        if (!is_array($payload) || empty($payload)) {
            return response()->json([
                'success' => false,
                'detail'  => 'قائمة المهام مطلوبة (عنصر واحد على الأقل).',
            ]);
        }


        $validator = \Validator::make($request->all(), [
            'major_tasks'                            => 'required|array|min:1',
            'major_tasks.*.name'                     => 'required|string|min:5|max:255',
            'major_tasks.*.description'              => 'required|string|max:1000',
            'major_tasks.*.objective'                => 'nullable|string|max:500',
            'major_tasks.*.priority'                 => 'required|in:High,Medium,Low',
            'major_tasks.*.estimated_duration_days'  => 'required|integer|min:10|max:365',
            'major_tasks.*.departments'              => 'required|array|min:1',
            'major_tasks.*.departments.*.department_id'       => 'required|integer',
            'major_tasks.*.departments.*.responsibility_type' => 'required|in:LEAD,SUPPORT',
        ]);


        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'detail'  => $validator->errors()->first(),
                'errors'  => $validator->errors()->toArray(),
            ]);
        }


        foreach ($payload as $i => $task) {
            $leadCount = collect($task['departments'] ?? [])
                ->where('responsibility_type', 'LEAD')->count();
            if ($leadCount !== 1) {
                return response()->json([
                    'success' => false,
                    'detail'  => 'المهمة #' . ($i + 1) . ' يجب أن تحتوي على إدارة مسؤولة (LEAD) واحدة فقط.',
                ]);
            }
        }


        try {
            $response = $this->apiClient->put(
                "/api/ai/strategic/tasks/{$job_id}",
                ['major_tasks' => $payload],
                $token
            );
        } catch (\Throwable $e) {
            Log::error('[strategic updateTasks] exception', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'detail'  => 'فشل الاتصال بالخادم: ' . $e->getMessage(),
            ]);
        }


        $success = (bool) ($response['success'] ?? false);
        if (!$success) {
            return response()->json([
                'success' => false,
                'detail'  => $response['detail'] ?? $response['message'] ?? 'فشل تحديث الخطة',
            ]);
        }


        return response()->json([
            'success' => true,
            'data'    => ['major_tasks' => $response['data']['major_tasks'] ?? $payload],
        ]);
    }


    public function approve(Request $request)
    {
        if (!$this->isManager()) {
            return back()->with('error', 'غير مصرح');
        }


        $request->validate(['job_id' => 'required|integer']);


        $token = session('jwt_token');
        if (!$token) {
            return back()->with('error', 'انتهت الجلسة، أعد تسجيل الدخول');
        }


        $response = $this->apiClient->post(
            "/api/ai/strategic/approve-tasks/{$request->job_id}",
            [],
            $token
        );


        Log::info('[strategic approve] response', ['response' => $response]);


        if (!($response['success'] ?? false)) {
            $detail = $response['detail'] ?? $response['message'] ?? null;


            if ($detail && stripos($detail, 'already approved') !== false) {
                return back()->with('error', 'هذه الوظيفة معتمدة مسبقًا — لم يُحفظ شيء جديد.');
            }
            if ($detail && stripos($detail, "must be in 'review'") !== false) {
                return back()->with('error', 'لا يمكن الاعتماد في الحالة الحالية. أعد تحميل الصفحة.');
            }


            return back()->with('error',
                $detail ?: 'فشل اعتماد المهام الرئيسية'
            );
        }


        $initiativeId = $response['data']['initiative_id'] ?? null;
        $savedCount   = count($response['data']['tasks'] ?? []);


        if (!$initiativeId) {
            return back()->with('error', 'تم الحفظ لكن تعذر تحديد المبادرة');
        }


        return redirect()
            ->route('strategic.initiative.show', ['id' => $initiativeId])
            ->with('success', "تم اعتماد {$savedCount} مهام رئيسية وحفظها بنجاح");
    }
}

<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;

class AiDashboardController extends Controller
{
    protected $apiClient;

    public function __construct()
    {
        $this->apiClient = new ApiClient();
    }

    public function index()
    {
        $token = session('jwt_token');
        
        // ✅ جلب الإحصائيات فقط (سريع)
        $dashboard = $this->apiClient->get('/api/ai/dashboard', $token);
        $models = $this->apiClient->get('/api/ai/models/status', $token);
        $scheduler = $this->apiClient->get('/api/ai/scheduler/status', $token);

        // ✅ جلب آخر تنبؤات من قاعدة البيانات (سريع)
        $allPredictions = $this->apiClient->safeGet('/api/ai/predict-all-delays', $token, []);
        $riskyTasks = array_slice($allPredictions, 0, 5);

        return view('ai.dashboard', [
            'dashboard' => $dashboard['data'] ?? [],
            'models' => $models['data'] ?? [],
            'scheduler' => $scheduler['data'] ?? [],
            'riskyTasks' => $riskyTasks,
            'totalPredicted' => count($allPredictions)
        ]);
    }

    public function predictAll()
    {
        $token = session('jwt_token');
        // ✅ تنبؤ مباشر (بدون Gemini - يستخدم XGBoost المحلي)
        $this->apiClient->post('/api/ai/predict-now', [], $token);
        return redirect()->route('ai.dashboard')->with('success', '✅ تم تحديث جميع التنبؤات');
    }

    public function trainNow()
    {
        $token = session('jwt_token');
        $this->apiClient->post('/api/ai/train-now', [], $token);
        return redirect()->route('ai.dashboard')->with('success', '✅ تم إعادة تدريب النموذج');
    }

    public function toggleScheduler()
    {
        $token = session('jwt_token');
        $status = $this->apiClient->get('/api/ai/scheduler/status', $token);
        
        if ($status['data']['is_running'] ?? false) {
            $this->apiClient->post('/api/ai/scheduler/stop', [], $token);
            return redirect()->route('ai.dashboard')->with('success', '⏸️ تم إيقاف الجدولة');
        } else {
            $this->apiClient->post('/api/ai/scheduler/start', [], $token);
            return redirect()->route('ai.dashboard')->with('success', '▶️ تم تشغيل الجدولة');
        }
    }
}

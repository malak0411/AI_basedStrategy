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
        $dashboard = $this->apiClient->get('/api/ai/dashboard', $token);
        $models = $this->apiClient->get('/api/ai/models/status', $token);

        return view('ai.dashboard', [
            'dashboard' => $dashboard['data'] ?? [],
            'models' => $models['data'] ?? []
        ]);
    }

    public function predictAll()
    {
        $token = session('jwt_token');
        $this->apiClient->get('/api/ai/predict-all-delays', $token);
        return redirect()->route('ai.dashboard')->with('success', 'تم تحديث التنبؤات');
    }
}

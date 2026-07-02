<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\ApiClient;

class AiController extends Controller
{
    protected $apiClient;

    public function __construct()
    {
        $this->apiClient = new ApiClient();
    }

    /**
     * لوحة الذكاء الاصطناعي
     */
    public function dashboard()
    {
        $token = session('jwt_token');
        $response = $this->apiClient->get('/api/ai/dashboard', $token);
        $dashboard = $response['data'] ?? [];

        return view('ai.dashboard', compact('dashboard'));
    }

    /**
     * قائمة التوصيات
     */
    public function recommendations()
    {
        $token = session('jwt_token');
        $response = $this->apiClient->get('/api/ai/recommendations', $token);
        $recommendations = $response['data'] ?? [];

        return view('ai.recommendations', compact('recommendations'));
    }

    /**
     * تفاصيل توصية
     */
    public function showRecommendation($id)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->get("/api/ai/recommendations/{$id}", $token);
        $recommendation = $response['data'] ?? [];

        if (empty($recommendation)) {
            return redirect()->route('ai.recommendations')->with('error', 'التوصية غير موجودة');
        }

        return view('ai.recommendations-show', compact('recommendation'));
    }

    /**
     * قائمة النماذج
     */
    public function models()
    {
        $token = session('jwt_token');
        $response = $this->apiClient->get('/api/ai/models', $token);
        $models = $response['data'] ?? [];

        return view('ai.models', compact('models'));
    }

    /**
     * تفاصيل نموذج
     */
    public function showModel($id)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->get("/api/ai/models/{$id}", $token);
        $model = $response['data'] ?? [];

        if (empty($model)) {
            return redirect()->route('ai.models')->with('error', 'النموذج غير موجود');
        }

        return view('ai.models-show', compact('model'));
    }

    /**
     * قائمة التنبؤات
     */
    public function predictions()
    {
        $token = session('jwt_token');
        $response = $this->apiClient->get('/api/ai/predictions', $token);
        $predictions = $response['data'] ?? [];

        return view('ai.predictions', compact('predictions'));
    }

    /**
     * تفاصيل تنبؤ
     */
    public function showPrediction($id)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->get("/api/ai/predictions/{$id}", $token);
        $prediction = $response['data'] ?? [];

        if (empty($prediction)) {
            return redirect()->route('ai.predictions')->with('error', 'التنبؤ غير موجود');
        }

        return view('ai.predictions-show', compact('prediction'));
    }
}

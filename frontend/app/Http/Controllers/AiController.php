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

    public function recommendations()
    {
        $token = session('jwt_token');
        $response = $this->apiClient->get('/api/ai/recommendations', $token);
        $recommendations = $response['data'] ?? [];

        return view('ai.recommendations', compact('recommendations'));
    }
}

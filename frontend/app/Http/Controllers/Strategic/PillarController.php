<?php

namespace App\Http\Controllers\Strategic;

use App\Http\Controllers\Controller;
use App\Services\ApiClient;

class PillarController extends Controller
{
    protected $apiClient;

    public function __construct()
    {
        $this->apiClient = new ApiClient();
    }

    public function index()
    {
        $token = session('jwt_token');
        $response = $this->apiClient->get('/api/strategic/pillars', $token);
        $pillars = $response['data'] ?? [];

        return view('strategic.pillars', compact('pillars'));
    }
}

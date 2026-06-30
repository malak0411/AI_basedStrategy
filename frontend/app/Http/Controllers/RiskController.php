<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\ApiClient;

class RiskController extends Controller
{
    protected $apiClient;

    public function __construct()
    {
        $this->apiClient = new ApiClient();
    }

    public function index()
    {
        $token = session('jwt_token');
        $response = $this->apiClient->get('/api/risks', $token);
        $risks = $response['data'] ?? [];

        return view('risks.index', compact('risks'));
    }
}

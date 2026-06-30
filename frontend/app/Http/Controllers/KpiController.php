<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\ApiClient;

class KpiController extends Controller
{
    protected $apiClient;

    public function __construct()
    {
        $this->apiClient = new ApiClient();
    }

    public function index()
    {
        $token = session('jwt_token');
        $response = $this->apiClient->get('/api/kpis', $token);
        $kpis = $response['data'] ?? [];

        return view('kpis.index', compact('kpis'));
    }
}

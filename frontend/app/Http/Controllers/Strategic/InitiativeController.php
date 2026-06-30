<?php

namespace App\Http\Controllers\Strategic;

use App\Http\Controllers\Controller;
use App\Services\ApiClient;

class InitiativeController extends Controller
{
    protected $apiClient;

    public function __construct()
    {
        $this->apiClient = new ApiClient();
    }

    public function index()
    {
        $token = session('jwt_token');
        $response = $this->apiClient->get('/api/strategic/initiatives', $token);
        $initiatives = $response['data'] ?? [];

        return view('strategic.initiatives', compact('initiatives'));
    }
}

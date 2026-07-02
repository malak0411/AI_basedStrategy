<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\ApiClient;

class LocationController extends Controller
{
    protected $apiClient;

    public function __construct()
    {
        $this->apiClient = new ApiClient();
    }

    public function index()
    {
        $token = session('jwt_token');
        $response = $this->apiClient->get('/api/location-logs', $token);
        $locations = $response['data'] ?? [];

        return view('location.index', compact('locations'));
    }

    public function employee($employeeId)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->get("/api/location-logs/employee/{$employeeId}", $token);
        $locations = $response['data'] ?? [];

        return view('location.employee', compact('locations', 'employeeId'));
    }

    public function history()
    {
        $token = session('jwt_token');
        $response = $this->apiClient->get('/api/location-logs/history', $token);
        $history = $response['data'] ?? [];

        return view('location.history', compact('history'));
    }
}

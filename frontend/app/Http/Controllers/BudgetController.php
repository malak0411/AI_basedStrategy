<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\ApiClient;

class BudgetController extends Controller
{
    protected $apiClient;

    public function __construct()
    {
        $this->apiClient = new ApiClient();
    }

    public function index()
    {
        $token = session('jwt_token');
        $response = $this->apiClient->get('/api/budget/overview', $token);
        $budget = $response['data'] ?? [];

        return view('budget.index', compact('budget'));
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\ApiClient;

class ReportController extends Controller
{
    protected $apiClient;

    public function __construct()
    {
        $this->apiClient = new ApiClient();
    }

    public function index()
    {
        $token = session('jwt_token');
        
        // جلب إحصائيات التقارير
        $tasksResponse = $this->apiClient->get('/api/reports/tasks-summary', $token);
        $budgetResponse = $this->apiClient->get('/api/reports/budget-summary', $token);
        $risksResponse = $this->apiClient->get('/api/reports/risks-summary', $token);
        $kpisResponse = $this->apiClient->get('/api/reports/kpis-summary', $token);
        $departmentsResponse = $this->apiClient->get('/api/reports/departments-summary', $token);
        $employeesResponse = $this->apiClient->get('/api/reports/employees-summary', $token);

        $reports = [
            'tasks' => $tasksResponse['data'] ?? [],
            'budget' => $budgetResponse['data'] ?? [],
            'risks' => $risksResponse['data'] ?? [],
            'kpis' => $kpisResponse['data'] ?? [],
            'departments' => $departmentsResponse['data'] ?? [],
            'employees' => $employeesResponse['data'] ?? [],
        ];

        return view('reports.index', compact('reports'));
    }
}

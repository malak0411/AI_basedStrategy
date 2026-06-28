<?php
// الملف: app/Http/Controllers/Dashboard/MinisterDashboardController.php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\ApiClient;

class MinisterDashboardController extends Controller
{
    protected $apiClient;

    public function __construct()
    {
        $this->apiClient = new ApiClient();
    }

    /**
     * عرض لوحة تحكم الوزير
     */
    public function index()
    {
        try {
            $token = session('jwt_token');
            
            // جلب جميع البيانات المطلوبة
            $dashboardData = $this->getDashboardData($token);
            $pillars = $this->getStrategicPillars($token);
            $departmentsPerformance = $this->getDepartmentsPerformance($token);
            $aiRecommendations = $this->getAIRecommendations($token);
            $budgetOverview = $this->getBudgetOverview($token);
            $risks = $this->getAllRisks($token);
            
            return view('dashboard.minister', compact(
                'dashboardData',
                'pillars',
                'departmentsPerformance',
                'aiRecommendations',
                'budgetOverview',
                'risks'
            ));
            
        } catch (\Exception $e) {
            \Log::error('Minister Dashboard Error: ' . $e->getMessage());
            
            return view('dashboard.minister', [
                'dashboardData' => [],
                'pillars' => [],
                'departmentsPerformance' => [],
                'aiRecommendations' => [],
                'budgetOverview' => [],
                'risks' => [],
                'error' => 'عذراً، حدث خطأ في تحميل البيانات'
            ]);
        }
    }

    /**
     * جلب بيانات لوحة التحكم الرئيسية
     */
    private function getDashboardData($token)
    {
        try {
            $response = $this->apiClient->get('/api/dashboard/minister', $token);
            return $response['data'] ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * جلب الركائز الاستراتيجية
     */
    private function getStrategicPillars($token)
    {
        try {
            $response = $this->apiClient->get('/api/strategic/pillars', $token, [
                'limit' => 5
            ]);
            return $response['data'] ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * جلب أداء الإدارات
     */
    private function getDepartmentsPerformance($token)
    {
        try {
            $response = $this->apiClient->get('/api/dashboard/departments-performance', $token);
            return $response['data'] ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * جلب توصيات الذكاء الاصطناعي
     */
    private function getAIRecommendations($token)
    {
        try {
            $response = $this->apiClient->get('/api/ai/recommendations', $token, [
                'limit' => 5
            ]);
            return $response['data'] ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * جلب نظرة عامة على الميزانية
     */
    private function getBudgetOverview($token)
    {
        try {
            $response = $this->apiClient->get('/api/budget/overview', $token);
            return $response['data'] ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * جلب جميع المخاطر
     */
    private function getAllRisks($token)
    {
        try {
            $response = $this->apiClient->get('/api/risks/all', $token, [
                'limit' => 5,
                'order_by' => 'risk_level',
                'order' => 'desc'
            ]);
            return $response['data'] ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }
}

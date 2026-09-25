<?php


namespace App\Http\Controllers\Dashboard;


use App\Http\Controllers\Controller;
use App\Services\ApiClient;


class MinisterDashboardController extends Controller
{
    public function index()
    {
        $api   = new ApiClient();
        $token = session('jwt_token');


        $response = $api->get('/api/dashboard/minister', $token);
        $data     = $response['data'] ?? $response;


        $pillarsResp = $api->get('/api/strategic/pillars', $token);
        $pillars     = $pillarsResp['data'] ?? [];


        $delayedResp = $api->get('/api/tasks/delayed', $token);
        $delayed     = $delayedResp['data'] ?? [];


        if (($response['status'] ?? 500) >= 400 || empty($data)) {
            return back()->with('error', $response['detail'] ?? 'تعذر تحميل البيانات الاستراتيجية.');
        }


        $role = session('user_role', 'employee');


        return view('dashboard.minister', compact('data', 'pillars', 'delayed', 'role'));
    }
}

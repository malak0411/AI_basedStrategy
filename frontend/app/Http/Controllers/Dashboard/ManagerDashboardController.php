<?php


namespace App\Http\Controllers\Dashboard;


use App\Http\Controllers\Controller;
use App\Services\ApiClient;


class ManagerDashboardController extends Controller
{
    public function index()
    {
        $api   = new ApiClient();
        $token = session('jwt_token');


        $response = $api->get('/api/dashboard/manager', $token);
        $data     = $response['data'] ?? $response;


        if (($response['status'] ?? 500) >= 400 || empty($data)) {
            return view('dashboard.manager', [
                'data'  => [],
                'error' => $response['detail'] ?? 'تعذر تحميل البيانات.',
            ]);
        }


        return view('dashboard.manager', [
            'data'  => $data,
            'error' => null,
        ]);
    }

    public function employeeDetail($employeeId)
{
    $api   = new ApiClient();
    $token = session('jwt_token');


    $response = $api->get("/api/dashboard/employee/{$employeeId}/detail", $token);
    $data     = $response['data'] ?? $response;


    if (($response['status'] ?? 500) >= 400 || empty($data)) {
        return redirect()->route('dashboard.manager')
            ->with('error', $response['detail'] ?? 'تعذر تحميل تفاصيل الموظف.');
    }


    return view('dashboard.employee-detail', ['data' => $data]);
}

}

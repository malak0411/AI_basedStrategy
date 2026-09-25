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


        // نفس نمط LoginController: البيانات قد تكون داخل 'data'
        $data = $response['data'] ?? $response;


        if (($response['status'] ?? 500) >= 400 || empty($data)) {
            return back()->with('error', $response['detail'] ?? 'تعذر تحميل بيانات لوحة المدير.');
        }


        // نفس نمط LoginController: نعتمد على session('user_role')
        $role = session('user_role', 'employee');


        return view('dashboard.manager', compact('data', 'role'));
    }
}

<?php


namespace App\Http\Controllers\Dashboard;


use App\Http\Controllers\Controller;
use App\Services\ApiClient;
use Illuminate\Http\Request;


class EmployeeDashboardController extends Controller
{
    public function index()
    {
        $api   = new ApiClient();
        $token = session('jwt_token');


        $response = $api->get('/api/dashboard/employee', $token);
        $data     = $response['data'] ?? $response;


        $notifResp     = $api->get('/api/notifications', $token);
        $notifications = $notifResp['data'] ?? [];


        if (($response['status'] ?? 500) >= 400 || empty($data)) {
            return view('dashboard.employee', [
                'data'          => [],
                'notifications' => [],
                'role'          => session('user_role', 'employee'),
                'error'         => $response['detail'] ?? 'تعذر تحميل البيانات.',
            ]);
        }


        return view('dashboard.employee', [
            'data'          => $data,
            'notifications' => $notifications,
            'role'          => session('user_role', 'employee'),
            'error'         => null,
        ]);
    }


    public function acceptAssignment($assignmentId)
    {
        $api   = new ApiClient();
        $token = session('jwt_token');


        $response = $api->post(
            "/api/dashboard/employee/assignments/{$assignmentId}/accept",
            [],
            $token
        );


        if (($response['status'] ?? 500) >= 400) {
            return back()->with('error', $response['detail'] ?? 'تعذر قبول الإسناد.');
        }
        return back()->with('success', 'تم قبول الإسناد بنجاح.');
    }


    public function rejectAssignment(Request $request, $assignmentId)
    {
        $request->validate([
            'rejection_reason' => 'required|string|min:3|max:500',
        ], [
            'rejection_reason.required' => 'سبب الرفض إلزامي.',
            'rejection_reason.min'      => 'سبب الرفض قصير جدًا.',
        ]);


        $api   = new ApiClient();
        $token = session('jwt_token');


        $response = $api->post(
            "/api/dashboard/employee/assignments/{$assignmentId}/reject",
            ['rejection_reason' => $request->rejection_reason],
            $token
        );


        if (($response['status'] ?? 500) >= 400) {
            return back()->with('error', $response['detail'] ?? 'تعذر رفض الإسناد.');
        }
        return back()->with('success', 'تم رفض الإسناد.');
    }
}

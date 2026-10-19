<?php


namespace App\Http\Controllers\Auth;


use App\Http\Controllers\Controller;
use App\Services\ApiClient;
use Illuminate\Http\Request;


class ChangePasswordController extends Controller
{
    protected $apiClient;


    public function __construct()
    {
        $this->apiClient = new ApiClient();
    }


    public function showChangeForm()
    {
        return view('auth.change-password');
    }


    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:6|confirmed',
        ]);


        $token = session('jwt_token');


        if (!$token) {
            return redirect()->route('login')->with('error', 'يجب تسجيل الدخول أولاً');
        }


        $response = $this->apiClient->post('/api/auth/change-password', [
            'current_password' => $request->current_password,
            'new_password' => $request->new_password,
        ], $token);


        if (!empty($response['success'])) {
            return redirect()->route('dashboard.employee')
                ->with('success', 'تم تغيير كلمة المرور بنجاح');
        }


        return back()->withErrors([
            'current_password' => $response['detail']
                ?? $response['message']
                ?? 'كلمة المرور الحالية غير صحيحة',
        ]);
    }
}

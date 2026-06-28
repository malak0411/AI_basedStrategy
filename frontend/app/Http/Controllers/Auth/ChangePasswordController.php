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

    /**
     * عرض صفحة تغيير كلمة المرور
     */
    public function showChangeForm()
    {
        return view('auth.change-password');
    }

    /**
     * تغيير كلمة المرور
     */
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

        if ($response['success']) {
            return redirect()->route('dashboard.employee')
                ->with('success', 'تم تغيير كلمة المرور بنجاح');
        }

        return back()->withErrors([
            'current_password' => $response['detail'] ?? 'كلمة المرور الحالية غير صحيحة',
        ]);
    }
}

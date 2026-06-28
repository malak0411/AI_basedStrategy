<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\ApiClient;
use Illuminate\Http\Request;

class ForgotPasswordController extends Controller
{
    protected $apiClient;

    public function __construct()
    {
        $this->apiClient = new ApiClient();
    }

    /**
     * عرض صفحة نسيت كلمة المرور
     */
    public function showForgotForm()
    {
        return view('auth.forgot-password');
    }

    /**
     * إرسال رابط استعادة كلمة المرور
     */
    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'phone_number' => 'required',
        ]);

        // إرسال الطلب إلى API
        $response = $this->apiClient->post('/api/auth/forgot-password', [
            'email' => $request->email,
            'phone_number' => $request->phone_number,
        ]);

        if ($response['success']) {
            $message = $response['data']['message'] ?? 'تم إرسال كلمة المرور الجديدة';
            
            // إذا كانت كلمة المرور الجديدة موجودة في الرد (وضع التطوير)
            if (isset($response['data']['new_password'])) {
                return back()->with('success', 'تم إعادة تعيين كلمة المرور بنجاح')
                             ->with('new_password', $response['data']['new_password']);
            }
            
            return back()->with('success', $message);
        }

        return back()->withErrors([
            'email' => $response['detail'] ?? 'حدث خطأ في استعادة كلمة المرور',
        ]);
    }
}

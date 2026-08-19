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
     * طلب إعادة تعيين كلمة المرور
     *
     * يتم التحقق من البريد ورقم الهاتف،
     * ثم يقوم FastAPI بتوليد كلمة مرور عشوائية
     * وإرسالها إلى البريد الإلكتروني للموظف.
     */
    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'phone_number' => 'required|string',
        ]);

        try {
            // إرسال الطلب إلى FastAPI
            $response = $this->apiClient->post('/api/auth/forgot-password', [
                'email' => $request->email,
                'phone_number' => $request->phone_number,
            ]);

            // نجاح العملية
            if (!empty($response['success'])) {

                $message = $response['data']['message']
                    ?? 'تم إرسال كلمة المرور المؤقتة إلى بريدك الإلكتروني.';

                return back()->with('success', $message);
            }

            // فشل العملية
            return back()->withErrors([
                'email' => $response['detail']
                    ?? $response['message']
                    ?? 'تعذر إعادة تعيين كلمة المرور.',
            ]);

        } catch (\Exception $e) {

            return back()->withErrors([
                'email' => 'حدث خطأ أثناء الاتصال بالخادم. يرجى المحاولة مرة أخرى.',
            ]);
        }
    }
}

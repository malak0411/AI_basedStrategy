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


    public function showForgotForm()
    {
        return view('auth.forgot-password');
    }


    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'phone_number' => 'required|string',
        ]);


        try {
            $response = $this->apiClient->post('/api/auth/forgot-password', [
                'email' => $request->email,
                'phone_number' => $request->phone_number,
            ]);


            if (!empty($response['success'])) {
                $message = $response['data']['message']
                    ?? 'تم إرسال كلمة المرور المؤقتة إلى بريدك الإلكتروني.';


                $redirect = back()->with('success', $message);


                if (!empty($response['data']['new_password'])) {
                    $redirect = $redirect->with('new_password', $response['data']['new_password']);
                }


                return $redirect;
            }


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

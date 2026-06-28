<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\ApiClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LoginController extends Controller
{
    protected $apiClient;

    public function __construct()
    {
        $this->apiClient = new ApiClient();
    }

    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $response = $this->apiClient->post('/api/auth/login', [
            'email' => $request->email,
            'password' => $request->password,
        ]);

        Log::info('Login API response: ' . json_encode($response));

        // استخراج التوكن والبيانات (قد تكون داخل 'data' أو مباشرة)
        $data = $response['data'] ?? $response;
        $token = $data['access_token'] ?? null;

        if ($token) {
            // البيانات قد تكون ضمن 'user' أو مباشرة
            $user = $data['user'] ?? $data;
            
            // استخراج الصلاحيات من المصفوفة
            $roles = $user['roles'] ?? [];
            $primaryRole = is_array($roles) ? ($roles[0] ?? 'employee') : 'employee';
            
            // تحويل المسمى إلى role_type موحد
            $roleType = match(true) {
                in_array('وزير / قيادة العليا', $roles) => 'minister',
                in_array('وكيل وزارة', $roles) => 'deputy',
                in_array('مدير عام', $roles) => 'general_manager',
                in_array('مدير إدارة', $roles) => 'manager',
                default => 'employee'
            };

            // تخزين في الجلسة
            session([
                'jwt_token' => $token,
                'user_role' => $roleType,
                'user_name' => $user['full_name'] ?? 'مستخدم',
                'user_email' => $user['email'] ?? $request->email,
                'user_department' => $user['department_name'] ?? '',
                'user_id' => $user['employee_id'] ?? '',
            ]);

            // توجيه حسب الصلاحية
            if (in_array($roleType, ['minister', 'deputy', 'general_manager', 'manager'])) {
                return redirect()->route('dashboard.manager');
            }
            return redirect()->route('dashboard.employee');
        }

        return back()->withErrors([
            'email' => $response['detail'] ?? 'بيانات الدخول غير صحيحة',
        ])->withInput($request->except('password'));
    }

    public function logout()
    {
        session()->flush();
        return redirect()->route('login')->with('success', 'تم تسجيل الخروج');
    }
}

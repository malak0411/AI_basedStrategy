<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ApiClient;
use Illuminate\Http\Request;

class SystemConfigController extends Controller
{
    protected $apiClient;

    public function __construct()
    {
        $this->apiClient = new ApiClient();
    }

    /**
     * عرض صفحة إعدادات النظام
     */
    public function index()
    {
        $token = session('jwt_token');
        
        // التحقق من الصلاحية
        if (session('user_role') !== 'super_admin') {
            return redirect()->route('dashboard.employee')
                ->with('error', 'غير مصرح بالوصول');
        }

        try {
            // جلب الإعدادات المجمعة
            $response = $this->apiClient->get('/api/system-config/grouped/all', $token);
            $configs = $response['data'] ?? [];

            return view('admin.settings.index', compact('configs'));

        } catch (\Exception $e) {
            \Log::error('System Config Error: ' . $e->getMessage());
            return view('admin.settings.index', [
                'configs' => [],
                'error' => 'تعذر تحميل إعدادات النظام'
            ]);
        }
    }

    /**
     * تحديث إعداد
     */
    public function update(Request $request)
    {
        $token = session('jwt_token');
        
        if (session('user_role') !== 'super_admin') {
            return response()->json(['error' => 'غير مصرح'], 403);
        }

        $request->validate([
            'config_key' => 'required|string',
            'config_value' => 'required|string',
        ]);

        try {
            $response = $this->apiClient->put(
                "/api/system-config/{$request->config_key}",
                ['config_value' => $request->config_value],
                $token
            );

            return response()->json($response);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}

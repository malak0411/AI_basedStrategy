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

    public function index()
    {
        $token = session('jwt_token');
        if (session('user_role') !== 'super_admin') {
            return redirect()->route('dashboard.employee')->with('error', 'غير مصرح');
        }

        $response = $this->apiClient->get('/api/system-config', $token);
        $configs = $response['data'] ?? [];

        return view('admin.settings.index', compact('configs'));
    }

    public function store(Request $request)
    {
        $token = session('jwt_token');
        $request->validate([
            'config_key' => 'required|string',
            'config_value' => 'required|string',
        ]);

        $response = $this->apiClient->post('/api/system-config', [
            'config_key' => $request->config_key,
            'config_value' => $request->config_value,
            'description' => $request->description ?? '',
        ], $token);

        if ($response['success'] ?? false) {
            return redirect()->route('admin.settings.index')->with('success', 'تم إضافة الإعداد');
        }
        return back()->with('error', $response['detail'] ?? 'فشل إضافة الإعداد')->withInput();
    }

    public function update(Request $request)
    {
        $token = session('jwt_token');
        if (session('user_role') !== 'super_admin') {
            return response()->json(['error' => 'غير مصرح'], 403);
        }

        $response = $this->apiClient->put(
            "/api/system-config/{$request->config_key}",
            ['config_value' => $request->config_value],
            $token
        );

        return response()->json($response);
    }

    public function destroy($config_key)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->delete("/api/system-config/{$config_key}", $token);

        if ($response['success'] ?? false) {
            return redirect()->route('admin.settings.index')->with('success', 'تم حذف الإعداد');
        }
        return back()->with('error', $response['detail'] ?? 'فشل حذف الإعداد');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ApiClient;

class AuditLogController extends Controller
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

        $response = $this->apiClient->get('/api/audit-logs', $token);
        $logs = $response['data'] ?? [];

        return view('admin.audit-logs.index', compact('logs'));
    }

    public function show($id)
    {
        $token = session('jwt_token');
        if (session('user_role') !== 'super_admin') {
            return redirect()->route('dashboard.employee')->with('error', 'غير مصرح');
        }

        $response = $this->apiClient->get("/api/audit-logs/{$id}", $token);
        $log = $response['data'] ?? [];

        if (empty($log)) {
            return redirect()->route('admin.audit-logs.index')->with('error', 'السجل غير موجود');
        }

        return view('admin.audit-logs.show', compact('log'));
    }
}

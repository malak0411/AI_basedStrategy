<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ApiClient;

class DepartmentController extends Controller
{
    protected $apiClient;

    public function __construct()
    {
        $this->apiClient = new ApiClient();
    }

    /**
     * عرض قائمة الإدارات
     */
    public function index()
    {
        $token = session('jwt_token');
        if (session('user_role') !== 'super_admin') {
            return redirect()->route('dashboard.employee')->with('error', 'غير مصرح');
        }

        $response = $this->apiClient->get('/api/departments', $token);
        $departments = $response['data'] ?? [];

        return view('admin.departments.index', compact('departments'));
    }

    /**
     * عرض تفاصيل إدارة
     */
    public function show($id)
    {
        $token = session('jwt_token');
        if (session('user_role') !== 'super_admin') {
            return redirect()->route('dashboard.employee')->with('error', 'غير مصرح');
        }

        $response = $this->apiClient->get("/api/departments/{$id}", $token);
        $department = $response['data'] ?? [];

        if (empty($department)) {
            return redirect()->route('admin.departments.index')
                ->with('error', 'الإدارة غير موجودة');
        }

        return view('admin.departments.show', compact('department'));
    }
}

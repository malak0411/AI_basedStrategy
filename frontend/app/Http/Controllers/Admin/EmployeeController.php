<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ApiClient;

class EmployeeController extends Controller
{
    protected $apiClient;

    public function __construct()
    {
        $this->apiClient = new ApiClient();
    }

    /**
     * عرض قائمة الموظفين
     */
    public function index()
    {
        $token = session('jwt_token');
        if (session('user_role') !== 'super_admin') {
            return redirect()->route('dashboard.employee')
                ->with('error', 'غير مصرح');
        }

        $response = $this->apiClient->get('/api/employees', $token);
        $employees = $response['data'] ?? [];

        return view('admin.employees.index', compact('employees'));
    }

    /**
     * عرض تفاصيل موظف
     */
    public function show($id)
    {
        $token = session('jwt_token');
        if (session('user_role') !== 'super_admin') {
            return redirect()->route('dashboard.employee')
                ->with('error', 'غير مصرح');
        }

        $response = $this->apiClient->get("/api/employees/{$id}", $token);
        $employee = $response['data'] ?? [];

        if (empty($employee)) {
            return redirect()->route('admin.employees.index')
                ->with('error', 'الموظف غير موجود');
        }

        return view('admin.employees.show', compact('employee'));
    }
}

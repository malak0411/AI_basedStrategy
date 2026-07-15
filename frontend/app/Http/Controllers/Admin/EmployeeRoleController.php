<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ApiClient;
use Illuminate\Http\Request;

class EmployeeRoleController extends Controller
{
    protected $apiClient;

    public function __construct()
    {
        $this->apiClient = new ApiClient();
    }

    public function index()
    {
        $token = session('jwt_token');
        if (session('user_role') !== 'super_admin') return redirect()->route('dashboard.employee')->with('error', 'غير مصرح');

        // جلب الموظفين والأدوار
        $employees = $this->apiClient->safeGet('/api/employees', $token, []);
        $roles = $this->apiClient->safeGet('/api/roles', $token, []);
        $employeeRoles = $this->apiClient->safeGet('/api/employee-roles', $token, []);

        return view('admin.employee-roles.index', compact('employees', 'roles', 'employeeRoles'));
    }

    public function store(Request $request)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->post('/api/employee-roles', [
            'employee_id' => (int) $request->employee_id,
            'role_id' => (int) $request->role_id,
        ], $token);

        if ($response['success'] ?? false) {
            return redirect()->route('admin.employee-roles.index')->with('success', 'تم ربط الموظف بالدور');
        }
        return back()->with('error', $response['detail'] ?? 'فشل الربط');
    }

    public function destroy(Request $request)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->delete('/api/employee-roles', [
            'employee_id' => (int) $request->employee_id,
            'role_id' => (int) $request->role_id,
        ], $token);

        if ($response['success'] ?? false) {
            return redirect()->route('admin.employee-roles.index')->with('success', 'تم إلغاء الربط');
        }
        return back()->with('error', $response['detail'] ?? 'فشل إلغاء الربط');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ApiClient;
use Illuminate\Http\Request;

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
            return redirect()->route('dashboard.employee')->with('error', 'غير مصرح');
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
            return redirect()->route('dashboard.employee')->with('error', 'غير مصرح');
        }

        $response = $this->apiClient->get("/api/employees/{$id}", $token);
        $employee = $response['data'] ?? [];

        if (empty($employee)) {
            return redirect()->route('admin.employees.index')->with('error', 'الموظف غير موجود');
        }

        // جلب الأدوار المرتبطة
        $rolesResponse = $this->apiClient->get('/api/employee-roles', $token);
        $allRoles = $rolesResponse['data'] ?? [];
        $employeeRoles = array_filter($allRoles, function($r) use ($id) {
            return ($r['employee_id'] ?? '') == $id;
        });

        return view('admin.employees.show', compact('employee', 'employeeRoles'));
    }

    /**
     * عرض صفحة تعديل موظف
     */
    public function edit($id)
    {
        $token = session('jwt_token');
        if (session('user_role') !== 'super_admin') {
            return redirect()->route('dashboard.employee')->with('error', 'غير مصرح');
        }

        $response = $this->apiClient->get("/api/employees/{$id}", $token);
        $employee = $response['data'] ?? [];

        // جلب الإدارات للاختيار
        $deptResponse = $this->apiClient->get('/api/departments', $token);
        $departments = $deptResponse['data'] ?? [];

        if (empty($employee)) {
            return redirect()->route('admin.employees.index')->with('error', 'الموظف غير موجود');
        }

        return view('admin.employees.edit', compact('employee', 'departments'));
    }

    /**
     * تحديث بيانات موظف
     */
    public function update(Request $request, $id)
    {
        $token = session('jwt_token');
        if (session('user_role') !== 'super_admin') {
            return redirect()->route('dashboard.employee')->with('error', 'غير مصرح');
        }

        $data = [];
        if ($request->filled('full_name')) $data['full_name'] = $request->full_name;
        if ($request->filled('email')) $data['email'] = $request->email;
        if ($request->filled('phone_number')) $data['phone_number'] = $request->phone_number;
        if ($request->filled('job_title')) $data['job_title'] = $request->job_title;
        if ($request->filled('department_id')) $data['department_id'] = (int) $request->department_id;
        if ($request->has('is_active')) $data['is_active'] = $request->has('is_active');

        $response = $this->apiClient->put("/api/employees/{$id}", $data, $token);

        if ($response['success'] ?? false) {
            return redirect()->route('admin.employees.show', $id)->with('success', 'تم تحديث بيانات الموظف');
        }
        return back()->with('error', $response['detail'] ?? 'فشل تحديث الموظف');
    }
}

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


        $rolesResponse = $this->apiClient->get('/api/employee-roles', $token);
        $allRoles = $rolesResponse['data'] ?? [];
        $employeeRoles = array_filter($allRoles, function($r) use ($id) {
            return ($r['employee_id'] ?? '') == $id;
        });

        return view('admin.employees.show', compact('employee', 'employeeRoles'));
    }

    

    public function edit($id)
    {
        $token = session('jwt_token');
        if (session('user_role') !== 'super_admin') {
            return redirect()->route('dashboard.employee')->with('error', 'غير مصرح');
        }

        $response = $this->apiClient->get("/api/employees/{$id}", $token);
        $employee = $response['data'] ?? [];


        $deptResponse = $this->apiClient->get('/api/departments', $token);
        $departments = $deptResponse['data'] ?? [];

        if (empty($employee)) {
            return redirect()->route('admin.employees.index')->with('error', 'الموظف غير موجود');
        }

        return view('admin.employees.edit', compact('employee', 'departments'));
    }

    
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

    public function create()
{
    $token = session('jwt_token');
    if (session('user_role') !== 'super_admin') {
        return redirect()->route('dashboard.employee')->with('error', 'غير مصرح');
    }

    $deptResponse = $this->apiClient->get('/api/departments', $token);
    $departments = $deptResponse['data'] ?? [];

    return view('admin.employees.create', compact('departments'));
}

public function store(Request $request)
{
    $token = session('jwt_token');
    if (session('user_role') !== 'super_admin') {
        return redirect()->route('dashboard.employee')->with('error', 'غير مصرح');
    }

    $request->validate([
        'full_name'             => 'required|string|max:255',
        'email'                 => 'required|email|max:191',
        'password'              => 'required|string|min:6|confirmed',
        'department_id'         => 'required|integer',
        'employee_number'       => 'nullable|string|max:50',
        'phone_number'          => 'nullable|string|max:30',
        'job_title'             => 'nullable|string|max:150',
        'hire_date'             => 'nullable|date',
    ], [
        'password.confirmed'    => 'تأكيد كلمة المرور غير مطابق',
        'email.email'           => 'صيغة البريد الإلكتروني غير صحيحة',
    ]);

    $data = [
        'full_name'             => $request->full_name,
        'email'                 => $request->email,
        'password'              => $request->password,
        'department_id'         => (int) $request->department_id,
        'employee_number'       => $request->employee_number ?: null,
        'phone_number'          => $request->phone_number ?: null,
        'job_title'             => $request->job_title ?: null,
        'hire_date'             => $request->hire_date ?: null,
        'gps_enabled'           => $request->has('gps_enabled'),
        'is_active'             => $request->has('is_active'),
    ];

    $response = $this->apiClient->post('/api/employees/', $data, $token);

    if ($response['success'] ?? false) {
        return redirect()->route('admin.employees.index')
            ->with('success', 'تم إضافة الموظف بنجاح');
    }

    return back()->withInput()
        ->with('error', $response['detail'] ?? $response['message'] ?? 'فشل إضافة الموظف');
}

}

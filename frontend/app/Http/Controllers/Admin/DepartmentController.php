<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ApiClient;
use Illuminate\Http\Request;

class DepartmentController extends Controller
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

        $response = $this->apiClient->get('/api/departments', $token);
        $departments = $response['data'] ?? [];
        return view('admin.departments.index', compact('departments'));
    }

    public function create()
    {
        if (session('user_role') !== 'super_admin') return redirect()->route('dashboard.employee');
        
        $token = session('jwt_token');
        $employees = $this->apiClient->safeGet('/api/employees', $token, []);
        $departments = $this->apiClient->safeGet('/api/departments', $token, []);
        
        return view('admin.departments.create', compact('employees', 'departments'));
    }

    public function store(Request $request)
    {
        $token = session('jwt_token');
        $request->validate(['name' => 'required|string|max:255']);

        $response = $this->apiClient->post('/api/departments/', [
            'name' => $request->name,
            'code' => $request->code ?? '',
            'description' => $request->description ?? '',
            'parent_department_id' => $request->parent_department_id ? (int) $request->parent_department_id : null,
            'manager_employee_id' => $request->manager_employee_id ? (int) $request->manager_employee_id : null,
            'level' => (int) ($request->level ?? 1),
        ], $token);

        if ($response['success'] ?? false) {
            return redirect()->route('admin.departments.index')->with('success', 'تم إنشاء الإدارة بنجاح');
        }
        return back()->with('error', $response['detail'] ?? 'فشل إنشاء الإدارة')->withInput();
    }

    public function show($id)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->get("/api/departments/{$id}", $token);
        $department = $response['data'] ?? [];
        if (empty($department)) return redirect()->route('admin.departments.index')->with('error', 'الإدارة غير موجودة');
        return view('admin.departments.show', compact('department'));
    }

    public function edit($id)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->get("/api/departments/{$id}", $token);
        $department = $response['data'] ?? [];
        
        $employees = $this->apiClient->safeGet('/api/employees', $token, []);
        $departments = $this->apiClient->safeGet('/api/departments', $token, []);
        
        if (empty($department)) return redirect()->route('admin.departments.index')->with('error', 'الإدارة غير موجودة');
        return view('admin.departments.edit', compact('department', 'employees', 'departments'));
    }

    public function update(Request $request, $id)
    {
        $token = session('jwt_token');
        
        $data = [];
        if ($request->filled('name')) $data['name'] = $request->name;
        if ($request->filled('code')) $data['code'] = $request->code;
        if ($request->filled('description')) $data['description'] = $request->description;
        if ($request->filled('parent_department_id')) $data['parent_department_id'] = (int) $request->parent_department_id;
        if ($request->filled('manager_employee_id')) $data['manager_employee_id'] = (int) $request->manager_employee_id;
        if ($request->filled('level')) $data['level'] = (int) $request->level;
        if ($request->has('is_active')) $data['is_active'] = $request->has('is_active');

        $response = $this->apiClient->put("/api/departments/{$id}", $data, $token);

        if ($response['success'] ?? false) {
            return redirect()->route('admin.departments.show', $id)->with('success', 'تم تحديث الإدارة');
        }
        return back()->with('error', $response['detail'] ?? 'فشل تحديث الإدارة');
    }

    public function destroy($id)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->delete('/api/departments/', $token);

        if ($response['success'] ?? false) {
            return redirect()->route('admin.departments.index')->with('success', 'تم حذف الإدارة');
        }
        return back()->with('error', $response['detail'] ?? 'فشل حذف الإدارة');
    }
}

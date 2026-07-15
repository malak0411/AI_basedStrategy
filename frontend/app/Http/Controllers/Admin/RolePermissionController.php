<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ApiClient;
use Illuminate\Http\Request;

class RolePermissionController extends Controller
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

        $roles = $this->apiClient->safeGet('/api/roles', $token, []);
        $permissions = $this->apiClient->safeGet('/api/permissions', $token, []);
        $rolePermissions = $this->apiClient->safeGet('/api/role-permissions', $token, []);

        return view('admin.role-permissions.index', compact('roles', 'permissions', 'rolePermissions'));
    }

    public function store(Request $request)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->post('/api/role-permissions', [
            'role_id' => (int) $request->role_id,
            'permission_id' => (int) $request->permission_id,
        ], $token);

        if ($response['success'] ?? false) {
            return redirect()->route('admin.role-permissions.index')->with('success', 'تم ربط الصلاحية بالدور');
        }
        return back()->with('error', $response['detail'] ?? 'فشل الربط');
    }

    public function destroy(Request $request)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->delete('/api/role-permissions', [
            'role_id' => (int) $request->role_id,
            'permission_id' => (int) $request->permission_id,
        ], $token);

        if ($response['success'] ?? false) {
            return redirect()->route('admin.role-permissions.index')->with('success', 'تم إلغاء الربط');
        }
        return back()->with('error', $response['detail'] ?? 'فشل إلغاء الربط');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ApiClient;

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
        if (session('user_role') !== 'super_admin') {
            return redirect()->route('dashboard.employee')->with('error', 'غير مصرح');
        }

        $response = $this->apiClient->get('/api/role-permissions', $token);
        $rolePermissions = $response['data'] ?? [];

        return view('admin.role-permissions.index', compact('rolePermissions'));
    }
}

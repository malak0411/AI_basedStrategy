<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ApiClient;
use Illuminate\Http\Request;

class RoleController extends Controller
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

        $response = $this->apiClient->get('/api/roles', $token);
        $roles = $response['data'] ?? [];

        return view('admin.roles.index', compact('roles'));
    }

    public function create()
    {
        return view('admin.roles.create');
    }

    public function store(Request $request)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->post('/api/roles', $request->all(), $token);

        if ($response['success']) {
            return redirect()->route('admin.roles.index')->with('success', 'تم إنشاء الدور بنجاح');
        }
        return back()->with('error', $response['detail'] ?? 'فشل إنشاء الدور')->withInput();
    }

    public function show($id)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->get("/api/roles/{$id}", $token);
        $role = $response['data'] ?? [];

        if (empty($role)) {
            return redirect()->route('admin.roles.index')->with('error', 'الدور غير موجود');
        }

        return view('admin.roles.show', compact('role'));
    }

    public function edit($id)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->get("/api/roles/{$id}", $token);
        $role = $response['data'] ?? [];

        if (empty($role)) {
            return redirect()->route('admin.roles.index')->with('error', 'الدور غير موجود');
        }

        return view('admin.roles.edit', compact('role'));
    }

    public function update(Request $request, $id)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->put("/api/roles/{$id}", $request->all(), $token);

        if ($response['success']) {
            return redirect()->route('admin.roles.show', $id)->with('success', 'تم تحديث الدور بنجاح');
        }
        return back()->with('error', $response['detail'] ?? 'فشل تحديث الدور');
    }
}

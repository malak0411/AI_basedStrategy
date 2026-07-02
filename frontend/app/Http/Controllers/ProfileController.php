<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\ApiClient;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    protected $apiClient;

    public function __construct()
    {
        $this->apiClient = new ApiClient();
    }

    public function index()
    {
        $token = session('jwt_token');
        $employeeId = session('user_id');
        
        $response = $this->apiClient->get("/api/employees/{$employeeId}", $token);
        $employee = $response['data'] ?? [];

        return view('profile.index', compact('employee'));
    }

    public function edit()
    {
        $token = session('jwt_token');
        $employeeId = session('user_id');
        
        $response = $this->apiClient->get("/api/employees/{$employeeId}", $token);
        $employee = $response['data'] ?? [];

        return view('profile.edit', compact('employee'));
    }

    public function update(Request $request)
    {
        $token = session('jwt_token');
        $employeeId = session('user_id');
        
        $response = $this->apiClient->put("/api/employees/{$employeeId}", $request->all(), $token);

        if ($response['success']) {
            session(['user_name' => $request->full_name]);
            return redirect()->route('profile.index')->with('success', 'تم تحديث الملف الشخصي بنجاح');
        }
        return back()->with('error', $response['detail'] ?? 'فشل تحديث الملف الشخصي');
    }
}

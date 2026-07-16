<?php

namespace App\Http\Controllers\Strategic;

use App\Http\Controllers\Controller;
use App\Services\ApiClient;
use Illuminate\Http\Request;

class PillarController extends Controller
{
    protected $apiClient;

    public function __construct()
    {
        $this->apiClient = new ApiClient();
    }

    public function index()
    {
        $token = session('jwt_token');
        $response = $this->apiClient->get('/api/strategic/pillars', $token);
        $pillars = $response['data'] ?? [];
        return view('strategic.pillars.index', compact('pillars'));
    }

    public function store(Request $request)
    {
        $token = session('jwt_token');
        $request->validate(['name' => 'required|string|max:255']);

        $response = $this->apiClient->post('/api/strategic/pillars', [
            'name' => $request->name,
            'description' => $request->description ?? '',
            'order_index' => (int)($request->order_index ?? 0),
            'is_active' => $request->has('is_active'),
        ], $token);

        if ($response['success'] ?? false) {
            return redirect()->route('strategic.pillars.index')->with('success', 'تم إنشاء الركيزة');
        }
        return back()->with('error', $response['detail'] ?? 'فشل')->withInput();
    }

    public function update(Request $request, $id)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->put("/api/strategic/pillars/{$id}", [
            'name' => $request->name,
            'description' => $request->description ?? '',
            'order_index' => (int)($request->order_index ?? 0),
            'is_active' => $request->has('is_active'),
        ], $token);

        if ($response['success'] ?? false) {
            return redirect()->route('strategic.pillars.index')->with('success', 'تم تحديث الركيزة');
        }
        return back()->with('error', $response['detail'] ?? 'فشل');
    }

    public function destroy($id)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->delete("/api/strategic/pillars/{$id}", $token);

        if ($response['success'] ?? false) {
            return redirect()->route('strategic.pillars.index')->with('success', 'تم حذف الركيزة');
        }
        return back()->with('error', $response['detail'] ?? 'فشل');
    }
}

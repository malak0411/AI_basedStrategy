<?php

namespace App\Http\Controllers\Admin\Dictionary;

use App\Http\Controllers\Controller;
use App\Services\ApiClient;
use Illuminate\Http\Request;

class DictStatusController extends Controller
{
    protected $apiClient;

    public function __construct()
    {
        $this->apiClient = new ApiClient();
    }

    public function index()
    {
        $token = session('jwt_token');
        $response = $this->apiClient->get('/api/dict/statuses', $token);
        $items = $response['data'] ?? [];
        return view('admin.dictionaries.statuses', compact('items'));
    }

    public function store(Request $request)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->post('/api/dict/statuses', [
            'code' => $request->code,
            'name_ar' => $request->name_ar,
            'name_en' => $request->name_en,
            'category' => $request->category,
            'color_hex' => $request->color_hex ?? '#6c757d',
        ], $token);

        if ($response['success'] ?? false) {
            return back()->with('success', 'تمت الإضافة بنجاح');
        }
        return back()->with('error', $response['detail'] ?? 'فشلت الإضافة')->withInput();
    }

    public function update(Request $request, $id)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->put("/api/dict/statuses/{$id}", $request->all(), $token);

        if ($response['success'] ?? false) {
            return back()->with('success', 'تم التحديث بنجاح');
        }
        return back()->with('error', $response['detail'] ?? 'فشل التحديث');
    }

    public function destroy($id)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->delete("/api/dict/statuses/{$id}", $token);

        if ($response['success'] ?? false) {
            return back()->with('success', 'تم الحذف بنجاح');
        }
        return back()->with('error', $response['detail'] ?? 'فشل الحذف');
    }
}

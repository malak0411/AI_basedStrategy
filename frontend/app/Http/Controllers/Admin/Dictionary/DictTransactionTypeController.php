<?php

namespace App\Http\Controllers\Admin\Dictionary;

use App\Http\Controllers\Controller;
use App\Services\ApiClient;
use Illuminate\Http\Request;

class DictTransactionTypeController extends Controller
{
    protected $apiClient;

    public function __construct()
    {
        $this->apiClient = new ApiClient();
    }

    public function index()
    {
        $token = session('jwt_token');
        $response = $this->apiClient->get('/api/dict/transaction-types', $token);
        $items = $response['data'] ?? [];
        return view('admin.dictionaries.transaction-types', compact('items'));
    }

    public function store(Request $request)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->post('/api/dict/transaction-types', [
            'code' => $request->code,
            'name_ar' => $request->name_ar,
            'name_en' => $request->name_en,
            'sign' => (int)$request->sign,
        ], $token);

        if ($response['success'] ?? false) {
            return back()->with('success', 'تمت الإضافة بنجاح');
        }
        return back()->with('error', $response['detail'] ?? 'فشلت الإضافة')->withInput();
    }

    public function update(Request $request, $id)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->put("/api/dict/transaction-types/{$id}", $request->all(), $token);

        if ($response['success'] ?? false) {
            return back()->with('success', 'تم التحديث بنجاح');
        }
        return back()->with('error', $response['detail'] ?? 'فشل التحديث');
    }

    public function destroy($id)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->delete("/api/dict/transaction-types/{$id}", $token);

        if ($response['success'] ?? false) {
            return back()->with('success', 'تم الحذف بنجاح');
        }
        return back()->with('error', $response['detail'] ?? 'فشل الحذف');
    }
}

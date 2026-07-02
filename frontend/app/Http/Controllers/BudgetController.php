<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\ApiClient;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    protected $apiClient;

    public function __construct()
    {
        $this->apiClient = new ApiClient();
    }

    public function index()
    {
        $token = session('jwt_token');
        $response = $this->apiClient->get('/api/budget/overview', $token);
        $budget = $response['data'] ?? [];

        return view('budget.index', compact('budget'));
    }

    public function create()
    {
        return view('budget.create');
    }

    public function store(Request $request)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->post('/api/budget', $request->all(), $token);

        if ($response['success']) {
            return redirect()->route('budget.index')->with('success', 'تم إنشاء بند الميزانية بنجاح');
        }
        return back()->with('error', $response['detail'] ?? 'فشل إنشاء البند')->withInput();
    }

    public function show($id)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->get("/api/budget/{$id}", $token);
        $item = $response['data'] ?? [];

        if (empty($item)) {
            return redirect()->route('budget.index')->with('error', 'البند غير موجود');
        }

        return view('budget.show', compact('item'));
    }

    public function edit($id)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->get("/api/budget/{$id}", $token);
        $item = $response['data'] ?? [];

        if (empty($item)) {
            return redirect()->route('budget.index')->with('error', 'البند غير موجود');
        }

        return view('budget.edit', compact('item'));
    }

    public function update(Request $request, $id)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->put("/api/budget/{$id}", $request->all(), $token);

        if ($response['success']) {
            return redirect()->route('budget.show', $id)->with('success', 'تم تحديث البند بنجاح');
        }
        return back()->with('error', $response['detail'] ?? 'فشل تحديث البند');
    }

    public function transactions()
    {
        $token = session('jwt_token');
        $response = $this->apiClient->get('/api/budget/transactions', $token);
        $transactions = $response['data'] ?? [];

        return view('budget.transactions', compact('transactions'));
    }

    public function createTransaction()
    {
        return view('budget.transactions-create');
    }

    public function storeTransaction(Request $request)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->post('/api/budget/transactions', $request->all(), $token);

        if ($response['success']) {
            return redirect()->route('budget.transactions')->with('success', 'تم تسجيل المعاملة بنجاح');
        }
        return back()->with('error', $response['detail'] ?? 'فشل تسجيل المعاملة')->withInput();
    }

    public function showTransaction($id)
    {
        $token = session('jwt_token');
        $response = $this->apiClient->get("/api/budget/transactions/{$id}", $token);
        $transaction = $response['data'] ?? [];

        if (empty($transaction)) {
            return redirect()->route('budget.transactions')->with('error', 'المعاملة غير موجودة');
        }

        return view('budget.transactions-show', compact('transaction'));
    }
}

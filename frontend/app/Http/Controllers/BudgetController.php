<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BudgetController extends Controller
{
    protected $apiClient;

    public function __construct(ApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    private function isAuthorized(): bool
    {
        $role = session('user_role', 'employee');
        return in_array($role, ['manager', 'general_manager', 'deputy', 'minister', 'admin', 'super_admin']);
    }

    public function index(Request $request)
    {
        try {
            $token = session('jwt_token');
            if (!$token) {
                return redirect()->route('login')->with('error', 'الرجاء تسجيل الدخول أولاً');
            }

            $params = [
                'page' => $request->get('page', 1),
                'per_page' => $request->get('per_page', 10),
            ];

            if ($request->has('fiscal_year')) {
                $params['fiscal_year'] = $request->get('fiscal_year');
            }

            if ($request->has('budgetable_type')) {
                $params['budgetable_type'] = $request->get('budgetable_type');
            }

            if ($request->has('department_id')) {
                $params['department_id'] = $request->get('department_id');
            }

            if ($request->has('status')) {
                $params['status'] = $request->get('status');
            }

            if ($request->has('search')) {
                $params['search'] = $request->get('search');
            }

            $response = $this->apiClient->get('/api/budget', $token, $params);
            $budgetLines = $response['data'] ?? [];
            $pagination = [
                'total' => $response['total'] ?? 0,
                'page' => $response['page'] ?? 1,
                'per_page' => $response['per_page'] ?? 10,
                'total_pages' => $response['total_pages'] ?? 0,
            ];

            // جلب خيارات الفلاتر
            $optionsResponse = $this->apiClient->get('/api/budget/options', $token);
            $options = $optionsResponse ?? [];

            // حساب الإحصائيات
            $totalBudget = 0;
            $totalAllocated = 0;
            $totalSpent = 0;
            $overspentCount = 0;

            foreach ($budgetLines as $line) {
                $totalBudget++;
                $totalAllocated += $line['allocated_amount'] ?? 0;
                $totalSpent += $line['spent_amount'] ?? 0;
                if (($line['status'] ?? '') == 'متجاوزة') {
                    $overspentCount++;
                }
            }

            $totalRemaining = $totalAllocated - $totalSpent;
            $executionPercentage = $totalAllocated > 0 ? round(($totalSpent / $totalAllocated) * 100, 2) : 0;

            return view('budget.index', compact(
                'budgetLines',
                'pagination',
                'options',
                'totalBudget',
                'totalAllocated',
                'totalSpent',
                'totalRemaining',
                'executionPercentage',
                'overspentCount'
            ));

        } catch (\Exception $e) {
            Log::error('Budget index error: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء تحميل الميزانيات');
        }
    }

    public function create()
    {
        try {
            $token = session('jwt_token');
            if (!$token) {
                return redirect()->route('login')->with('error', 'الرجاء تسجيل الدخول أولاً');
            }

            if (!$this->isAuthorized()) {
                return redirect()->route('budget.index')->with('error', 'غير مصرح');
            }

            $response = $this->apiClient->get('/api/budget/options', $token);
            $options = $response ?? [];

            return view('budget.create', compact('options'));

        } catch (\Exception $e) {
            Log::error('Budget create error: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء تحميل البيانات');
        }
    }

    public function store(Request $request)
    {
        try {
            $token = session('jwt_token');
            if (!$token) {
                return redirect()->route('login')->with('error', 'الرجاء تسجيل الدخول أولاً');
            }

            if (!$this->isAuthorized()) {
                return redirect()->route('budget.index')->with('error', 'غير مصرح');
            }

            $request->validate([
                'budgetable_type' => 'required|in:program,initiative,major_task,operational_task',
                'budgetable_id' => 'required|integer',
                'department_id' => 'nullable|integer',
                'fiscal_year' => 'required|integer',
                'allocated_amount' => 'required|numeric|min:0',
                'parent_budget_id' => 'nullable|integer'
            ]);

            $data = [
                'budgetable_type' => $request->budgetable_type,
                'budgetable_id' => (int) $request->budgetable_id,
                'department_id' => $request->department_id ? (int) $request->department_id : null,
                'fiscal_year' => (int) $request->fiscal_year,
                'allocated_amount' => (float) $request->allocated_amount,
                'parent_budget_id' => $request->parent_budget_id ? (int) $request->parent_budget_id : null
            ];

            $response = $this->apiClient->post('/api/budget', $data, $token);

            if ($response['success'] ?? false) {
                return redirect()->route('budget.index')->with('success', 'تم إنشاء الميزانية بنجاح');
            }

            return back()->with('error', $response['detail'] ?? 'فشل إنشاء الميزانية');

        } catch (\Exception $e) {
            Log::error('Budget store error: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء إنشاء الميزانية');
        }
    }

    public function show($id)
    {
        try {
            $token = session('jwt_token');
            if (!$token) {
                return redirect()->route('login')->with('error', 'الرجاء تسجيل الدخول أولاً');
            }

            $response = $this->apiClient->get("/api/budget/{$id}", $token);
            $budget = $response ?? [];

            if (empty($budget)) {
                return redirect()->route('budget.index')->with('error', 'الميزانية غير موجودة');
            }

            return view('budget.show', compact('budget'));

        } catch (\Exception $e) {
            Log::error('Budget show error: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء تحميل الميزانية');
        }
    }

    public function edit($id)
    {
        try {
            $token = session('jwt_token');
            if (!$token) {
                return redirect()->route('login')->with('error', 'الرجاء تسجيل الدخول أولاً');
            }

            if (!$this->isAuthorized()) {
                return redirect()->route('budget.index')->with('error', 'غير مصرح');
            }

            $response = $this->apiClient->get("/api/budget/{$id}", $token);
            $budget = $response ?? [];

            if (empty($budget)) {
                return redirect()->route('budget.index')->with('error', 'الميزانية غير موجودة');
            }

            $optionsResponse = $this->apiClient->get('/api/budget/options', $token);
            $options = $optionsResponse ?? [];

            return view('budget.edit', compact('budget', 'options'));

        } catch (\Exception $e) {
            Log::error('Budget edit error: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء تحميل البيانات');
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $token = session('jwt_token');
            if (!$token) {
                return redirect()->route('login')->with('error', 'الرجاء تسجيل الدخول أولاً');
            }

            if (!$this->isAuthorized()) {
                return redirect()->route('budget.index')->with('error', 'غير مصرح');
            }

            $request->validate([
                'allocated_amount' => 'required|numeric|min:0',
                'department_id' => 'nullable|integer',
                'fiscal_year' => 'required|integer',
                'parent_budget_id' => 'nullable|integer'
            ]);

            $data = [
                'allocated_amount' => (float) $request->allocated_amount,
                'department_id' => $request->department_id ? (int) $request->department_id : null,
                'fiscal_year' => (int) $request->fiscal_year,
                'parent_budget_id' => $request->parent_budget_id ? (int) $request->parent_budget_id : null
            ];

            $response = $this->apiClient->put("/api/budget/{$id}", $data, $token);

            if ($response['success'] ?? false) {
                return redirect()->route('budget.index')->with('success', 'تم تحديث الميزانية بنجاح');
            }

            return back()->with('error', $response['detail'] ?? 'فشل تحديث الميزانية');

        } catch (\Exception $e) {
            Log::error('Budget update error: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء تحديث الميزانية');
        }
    }

    public function destroy($id)
    {
        try {
            $token = session('jwt_token');
            if (!$token) {
                return response()->json(['success' => false, 'error' => 'غير مصرح'], 401);
            }

            if (!$this->isAuthorized()) {
                return response()->json(['success' => false, 'error' => 'غير مصرح'], 403);
            }

            $response = $this->apiClient->delete("/api/budget/{$id}", $token);

            if ($response['success'] ?? false) {
                return response()->json(['success' => true, 'message' => 'تم حذف الميزانية']);
            }

            return response()->json(['success' => false, 'error' => $response['detail'] ?? 'فشل حذف الميزانية'], 500);

        } catch (\Exception $e) {
            Log::error('Budget delete error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function transactions($id)
    {
        try {
            $token = session('jwt_token');
            if (!$token) {
                return redirect()->route('login')->with('error', 'الرجاء تسجيل الدخول أولاً');
            }

            $budgetResponse = $this->apiClient->get("/api/budget/{$id}", $token);
            $budget = $budgetResponse ?? [];

            if (empty($budget)) {
                return redirect()->route('budget.index')->with('error', 'الميزانية غير موجودة');
            }

            $response = $this->apiClient->get("/api/budget/{$id}/transactions", $token);
            $transactions = $response['data'] ?? [];
            $pagination = [
                'total' => $response['total'] ?? 0,
                'page' => $response['page'] ?? 1,
                'per_page' => $response['per_page'] ?? 10,
                'total_pages' => $response['total_pages'] ?? 0,
            ];

            return view('budget.transactions', compact('budget', 'transactions', 'pagination'));

        } catch (\Exception $e) {
            Log::error('Budget transactions error: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء تحميل العمليات المالية');
        }
    }

    public function createTransaction($id)
    {
        try {
            $token = session('jwt_token');
            if (!$token) {
                return redirect()->route('login')->with('error', 'الرجاء تسجيل الدخول أولاً');
            }

            $budgetResponse = $this->apiClient->get("/api/budget/{$id}", $token);
            $budget = $budgetResponse ?? [];

            if (empty($budget)) {
                return redirect()->route('budget.index')->with('error', 'الميزانية غير موجودة');
            }

            $optionsResponse = $this->apiClient->get('/api/budget/options', $token);
            $options = $optionsResponse ?? [];

            return view('budget.transactions-create', compact('budget', 'options'));

        } catch (\Exception $e) {
            Log::error('Budget create transaction error: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء تحميل البيانات');
        }
    }

    public function storeTransaction(Request $request, $id)
    {
        try {
            $token = session('jwt_token');
            if (!$token) {
                return redirect()->route('login')->with('error', 'الرجاء تسجيل الدخول أولاً');
            }

            $request->validate([
                'amount' => 'required|numeric|min:0.01',
                'transaction_type_id' => 'required|integer',
                'description' => 'nullable|string|max:2000',
                'transaction_date' => 'nullable|date'
            ]);

            $data = [
                'amount' => (float) $request->amount,
                'transaction_type_id' => (int) $request->transaction_type_id,
                'description' => $request->description,
                'transaction_date' => $request->transaction_date ?? date('Y-m-d')
            ];

            $response = $this->apiClient->post("/api/budget/{$id}/transactions", $data, $token);

            if ($response['success'] ?? false) {
                return redirect()->route('budget.transactions', $id)->with('success', 'تم تسجيل العملية المالية بنجاح');
            }

            return back()->with('error', $response['detail'] ?? 'فشل تسجيل العملية');

        } catch (\Exception $e) {
            Log::error('Budget store transaction error: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء تسجيل العملية');
        }
    }

    public function showTransaction($transactionId)
    {
        try {
            $token = session('jwt_token');
            if (!$token) {
                return redirect()->route('login')->with('error', 'الرجاء تسجيل الدخول أولاً');
            }

            $response = $this->apiClient->get("/api/budget/transactions/{$transactionId}", $token);
            $transaction = $response ?? [];

            if (empty($transaction)) {
                return redirect()->route('budget.index')->with('error', 'العملية غير موجودة');
            }

            return view('budget.transactions-show', compact('transaction'));

        } catch (\Exception $e) {
            Log::error('Budget show transaction error: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء تحميل العملية');
        }
    }

    public function destroyTransaction($transactionId)
    {
        try {
            $token = session('jwt_token');
            if (!$token) {
                return response()->json(['success' => false, 'error' => 'غير مصرح'], 401);
            }

            if (!$this->isAuthorized()) {
                return response()->json(['success' => false, 'error' => 'غير مصرح'], 403);
            }

            $response = $this->apiClient->delete("/api/budget/transactions/{$transactionId}", $token);

            if ($response['success'] ?? false) {
                return response()->json(['success' => true, 'message' => 'تم حذف العملية']);
            }

            return response()->json(['success' => false, 'error' => $response['detail'] ?? 'فشل حذف العملية'], 500);

        } catch (\Exception $e) {
            Log::error('Budget delete transaction error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}

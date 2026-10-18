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
            if (!$token) return redirect()->route('login')->with('error', 'الرجاء تسجيل الدخول أولاً');


            $params = [];
            if ($request->filled('fiscal_year')) $params['fiscal_year'] = $request->fiscal_year;


            $treeResponse = $this->apiClient->get('/api/budget/hierarchy', $token, $params);
            $tree = $treeResponse['data'] ?? [];


            $summaryResponse = $this->apiClient->get('/api/budget/summary', $token, $params);
            $summary = $summaryResponse ?? [];


            $optionsResponse = $this->apiClient->get('/api/budget/options', $token);
            $options = $optionsResponse ?? [];


            return view('budget.index', compact('tree', 'summary', 'options'));


        } catch (\Exception $e) {
            Log::error('Budget index error: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء تحميل البيانات المالية');
        }
    }


    public function create(Request $request)
    {
        try {
            $token = session('jwt_token');
            if (!$this->isAuthorized()) return redirect()->route('budget.index')->with('error', 'غير مصرح');


            $options = $this->apiClient->get('/api/budget/options', $token) ?? [];


            return view('budget.create', [
                'options' => $options,
                'type' => $request->query('type'),
                'id' => $request->query('id'),
                'name' => $request->query('name')
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'حدث خطأ');
        }
    }


    public function store(Request $request)
    {
        try {
            $token = session('jwt_token');
            if (!$this->isAuthorized()) return back()->with('error', 'غير مصرح');


            $request->validate([
                'budgetable_type' => 'required',
                'budgetable_id' => 'required|integer',
                'fiscal_year' => 'required|integer',
                'allocated_amount' => 'required|numeric|min:0',
            ]);


            $data = [
                'budgetable_type' => $request->budgetable_type,
                'budgetable_id' => (int) $request->budgetable_id,
                'department_id' => $request->department_id ? (int) $request->department_id : null,
                'fiscal_year' => (int) $request->fiscal_year,
                'allocated_amount' => (float) $request->allocated_amount,
            ];


            $response = $this->apiClient->post('/api/budget', $data, $token);


            if ($response['success'] ?? false) {
                return redirect()->route('budget.index')->with('success', 'تم إنشاء الميزانية بنجاح');
            }


            return back()->with('error', $response['detail'] ?? 'فشل الحفظ');


        } catch (\Exception $e) {
            return back()->with('error', 'حدث خطأ: ' . $e->getMessage());
        }
    }


    public function edit($id)
    {
        try {
            $token = session('jwt_token');
            $budget = $this->apiClient->get("/api/budget/{$id}", $token) ?? [];
            $options = $this->apiClient->get('/api/budget/options', $token) ?? [];


            return view('budget.edit', compact('budget', 'options'));
        } catch (\Exception $e) {
            return back()->with('error', 'حدث خطأ');
        }
    }


    public function update(Request $request, $id)
    {
        try {
            $token = session('jwt_token');
            $data = [
                'allocated_amount' => (float) $request->allocated_amount,
                'department_id' => $request->department_id ? (int) $request->department_id : null,
                'fiscal_year' => (int) $request->fiscal_year,
            ];


            $response = $this->apiClient->put("/api/budget/{$id}", $data, $token);


            if ($response['success'] ?? false) {
                return redirect()->route('budget.index')->with('success', 'تم التحديث بنجاح');
            }
            return back()->with('error', $response['detail'] ?? 'فشل التحديث');
        } catch (\Exception $e) {
            return back()->with('error', 'حدث خطأ: ' . $e->getMessage());
        }
    }


    public function show($id)
    {
        try {
            $token = session('jwt_token');
            $budget = $this->apiClient->get("/api/budget/{$id}", $token) ?? [];
            $transactionsResponse = $this->apiClient->get("/api/budget/{$id}/transactions", $token);
            
            return view('budget.show', [
                'budget' => $budget,
                'transactions' => $transactionsResponse['data'] ?? []
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'حدث خطأ');
        }
    }


    public function transactions($id)
    {
        try {
            $token = session('jwt_token');
            $budget = $this->apiClient->get("/api/budget/{$id}", $token) ?? [];
            $response = $this->apiClient->get("/api/budget/{$id}/transactions", $token);
            
            return view('budget.transactions', [
                'budget' => $budget,
                'transactions' => $response['data'] ?? [],
                'pagination' => $response
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'حدث خطأ');
        }
    }


    public function createTransaction($id)
    {
        try {
            $token = session('jwt_token');
            $budget = $this->apiClient->get("/api/budget/{$id}", $token) ?? [];
            $options = $this->apiClient->get('/api/budget/options', $token) ?? [];


            return view('budget.transactions-create', compact('budget', 'options'));
        } catch (\Exception $e) {
            return back()->with('error', 'حدث خطأ');
        }
    }


    public function storeTransaction(Request $request, $id)
    {
        try {
            $token = session('jwt_token');
            $data = [
                'amount' => (float) $request->amount,
                'transaction_type_id' => (int) $request->transaction_type_id,
                'description' => $request->description,
                'transaction_date' => $request->transaction_date ?? date('Y-m-d')
            ];


            $response = $this->apiClient->post("/api/budget/{$id}/transactions", $data, $token);


            if ($response['success'] ?? false) {
                return redirect()->route('budget.transactions', $id)->with('success', 'تم تسجيل العملية');
            }
            return back()->with('error', $response['detail'] ?? 'فشل التسجيل');
        } catch (\Exception $e) {
            return back()->with('error', 'حدث خطأ: ' . $e->getMessage());
        }
    }


    // الدالة المضافة حديثاً
    public function showTransaction($transactionId)
    {
        try {
            $token = session('jwt_token');
            if (!$token) {
                return redirect()->route('login')->with('error', 'الرجاء تسجيل الدخول أولاً');
            }


            $response = $this->apiClient->get("/api/budget/transactions/{$transactionId}", $token);
            $transaction = $response ?? [];


            if (empty($transaction) || isset($transaction['detail'])) {
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
            $response = $this->apiClient->delete("/api/budget/transactions/{$transactionId}", $token);
            if ($response['success'] ?? false) {
                return response()->json(['success' => true, 'message' => 'تم حذف العملية بنجاح']);
            }
            return response()->json(['success' => false, 'error' => $response['detail']], 500);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}

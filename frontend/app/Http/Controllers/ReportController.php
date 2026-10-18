<?php


namespace App\Http\Controllers;


use App\Services\ApiClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;


class ReportController extends Controller
{
    protected $apiClient;


    public function __construct(ApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
    }


    private function checkAuth()
    {
        $role = session('user_role', 'employee');
        if (!in_array($role, ['manager', 'general_manager', 'deputy', 'minister', 'admin', 'super_admin'])) {
            abort(403, 'غير مصرح لك بالوصول لمركز التقارير');
        }
    }


    public function index()
    {
        $this->checkAuth();
        try {
            $token = session('jwt_token');
            $response = $this->apiClient->get('/api/reports/dashboard', $token);
            $data = $response ?? ['counts' => [], 'financials' => []];
            return view('reports.index', compact('data'));
        } catch (\Exception $e) {
            return back()->with('error', 'حدث خطأ أثناء تحميل لوحة التقارير');
        }
    }


    public function strategic()
    {
        $this->checkAuth();
        try {
            $token = session('jwt_token');
            $response = $this->apiClient->get('/api/reports/strategic', $token);
            $tree = $response['data'] ?? [];
            return view('reports.strategic', compact('tree'));
        } catch (\Exception $e) {
            return back()->with('error', 'حدث خطأ');
        }
    }


    public function budget()
    {
        $this->checkAuth();
        try {
            $token = session('jwt_token');
            $response = $this->apiClient->get('/api/reports/budget', $token);
            $budgets = $response['data'] ?? [];
            return view('reports.budget', compact('budgets'));
        } catch (\Exception $e) {
            return back()->with('error', 'حدث خطأ');
        }
    }


    public function risks()
    {
        $this->checkAuth();
        try {
            $token = session('jwt_token');
            $response = $this->apiClient->get('/api/reports/risks', $token);
            $risks = $response['data'] ?? [];
            return view('reports.risks', compact('risks'));
        } catch (\Exception $e) {
            return back()->with('error', 'حدث خطأ');
        }
    }


    public function kpis()
    {
        $this->checkAuth();
        try {
            $token = session('jwt_token');
            $response = $this->apiClient->get('/api/reports/kpis', $token);
            $kpis = $response['data'] ?? [];
            return view('reports.kpis', compact('kpis'));
        } catch (\Exception $e) {
            return back()->with('error', 'حدث خطأ');
        }
    }


    public function departments()
    {
        $this->checkAuth();
        try {
            $token = session('jwt_token');
            $response = $this->apiClient->get('/api/reports/departments', $token);
            $departments = $response['data'] ?? [];
            return view('reports.departments', compact('departments'));
        } catch (\Exception $e) {
            return back()->with('error', 'حدث خطأ');
        }
    }


    public function employees()
    {
        $this->checkAuth();
        try {
            $token = session('jwt_token');
            $response = $this->apiClient->get('/api/reports/employees', $token);
            $employees = $response['data'] ?? [];
            return view('reports.employees', compact('employees'));
        } catch (\Exception $e) {
            return back()->with('error', 'حدث خطأ');
        }
    }



    public function export($type)
    {
        $this->checkAuth();
        $token = session('jwt_token');
        
        $endpointMap = [
            'strategic' => '/api/reports/strategic',
            'budget' => '/api/reports/budget',
            'risks' => '/api/reports/risks',
            'kpis' => '/api/reports/kpis',
            'departments' => '/api/reports/departments',
            'employees' => '/api/reports/employees',
        ];


        if (!isset($endpointMap[$type])) abort(404);


        $response = $this->apiClient->get($endpointMap[$type], $token);
        $data = $response['data'] ?? [];


        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=report_{$type}_" . date('Y-m-d') . ".csv",
            "Pragma"              => "no-cache",
        ];


        $callback = function() use ($data, $type) {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF"); 
            
            if ($type == 'risks') {
                fputcsv($file, ['الرقم', 'الخطر', 'المستوى', 'الحالة', 'الاحتمال', 'التأثير', 'الإدارة', 'المهمة']);
                foreach ($data as $row) {
                    fputcsv($file, [$row['id'], $row['name'], $row['level'], $row['status'], $row['probability'], $row['impact'], $row['department'], $row['task']]);
                }
            } elseif ($type == 'kpis') {
                fputcsv($file, ['الرقم', 'المؤشر', 'الوحدة', 'المستهدف', 'الفعلي', 'نسبة الإنجاز']);
                foreach ($data as $row) {
                    fputcsv($file, [$row['id'], $row['name'], $row['unit'], $row['target'], $row['actual'], $row['achievement'] . '%']);
                }
            } elseif ($type == 'departments') {
                fputcsv($file, ['الرقم', 'الإدارة', 'الموظفين', 'المهام', 'المكتملة', 'قيد التنفيذ']);
                foreach ($data as $row) {
                    fputcsv($file, [$row['id'], $row['name'], $row['employees'], $row['tasks'], $row['completed_tasks'], $row['in_progress_tasks']]);
                }
            } elseif ($type == 'employees') {
                fputcsv($file, ['الرقم', 'الموظف', 'المسمى الوظيفي', 'المهام المسندة']);
                foreach ($data as $row) {
                    fputcsv($file, [$row['id'], $row['name'], $row['job_title'], $row['assigned_tasks']]);
                }
            }

            
            fclose($file);
        };


        return new StreamedResponse($callback, 200, $headers);
    }
}

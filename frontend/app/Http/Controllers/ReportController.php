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
            
            $data = $response['data'] ?? $response ?? [];
            
            if (!isset($data['counts'])) {
                $data['counts'] = [
                    'pillars' => 0, 'goals' => 0, 'programs' => 0, 'initiatives' => 0, 
                    'major_tasks' => 0, 'op_tasks' => 0, 'employees' => 0, 'active_risks' => 0
                ];
            }

            if (!isset($data['financials'])) {
                $data['financials'] = [
                    'total_allocated' => 0, 'total_spent' => 0, 
                    'total_remaining' => 0, 'execution_pct' => 0
                ];
            }

            return view('reports.index', compact('data'));
        } catch (\Exception $e) {
            Log::error('Report dashboard error: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء تحميل لوحة التقارير');
        }
    }

    public function strategic(Request $request)
    {
        $this->checkAuth();
        try {
            $token = session('jwt_token');
            
            $general = $this->apiClient->get('/api/reports/strategic/general', $token) ?? [];
            
            $treeResponse = $this->apiClient->get('/api/reports/strategic', $token);
            $tree = $treeResponse['data'] ?? [];

            return view('reports.strategic', compact('general', 'tree'));
        } catch (\Exception $e) {
            Log::error('Strategic report error: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء تحميل التقرير الاستراتيجي');
        }
    }

    public function budget(Request $request)
    {
        $this->checkAuth();
        try {
            $token = session('jwt_token');
            $response = $this->apiClient->get('/api/reports/budget', $token, $request->only(['budgetable_type']));
            $budgets = $response['data'] ?? [];
            
            return view('reports.budget', compact('budgets'));
        } catch (\Exception $e) {
            Log::error('Budget report error: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء تحميل تقرير الميزانية');
        }
    }

    public function risks(Request $request)
    {
        $this->checkAuth();
        try {
            $token = session('jwt_token');
            $response = $this->apiClient->get('/api/reports/risks', $token, $request->only(['initiative_id', 'department_id']));
            $risks = $response['data'] ?? [];
            
            return view('reports.risks', compact('risks'));
        } catch (\Exception $e) {
            Log::error('Risks report error: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء تحميل تقرير المخاطر');
        }
    }

    public function kpis(Request $request)
    {
        $this->checkAuth();
        try {
            $token = session('jwt_token');
            $response = $this->apiClient->get('/api/reports/kpis', $token, $request->only(['category', 'goal_id']));
            $kpis = $response['data'] ?? [];
            
            return view('reports.kpis', compact('kpis'));
        } catch (\Exception $e) {
            Log::error('KPIs report error: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء تحميل تقرير مؤشرات الأداء');
        }
    }

    public function departments(Request $request)
    {
        $this->checkAuth();
        try {
            $token = session('jwt_token');
            $response = $this->apiClient->get('/api/reports/departments', $token, $request->only(['department_id']));
            $departments = $response['data'] ?? [];
            
            return view('reports.departments', compact('departments'));
        } catch (\Exception $e) {
            Log::error('Departments report error: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء تحميل تقرير الإدارات');
        }
    }

    public function employees(Request $request)
    {
        $this->checkAuth();
        try {
            $token = session('jwt_token');
            $response = $this->apiClient->get('/api/reports/employees', $token, $request->only(['department_id', 'employee_id']));
            $employees = $response['data'] ?? [];
            
            return view('reports.employees', compact('employees'));
        } catch (\Exception $e) {
            Log::error('Employees report error: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء تحميل تقرير الموظفين');
        }
    }

    public function tasks(Request $request)
    {
        $this->checkAuth();
        try {
            $token = session('jwt_token');
            $response = $this->apiClient->get('/api/reports/tasks', $token, $request->only(['department_id', 'initiative_id']));
            $tasks = $response['data'] ?? [];
            
            return view('reports.tasks', compact('tasks'));
        } catch (\Exception $e) {
            Log::error('Tasks report error: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء تحميل تقرير المهام');
        }
    }

    public function export($type, Request $request)
    {
        $this->checkAuth();
        $token = session('jwt_token');
        
        $endpointMap = [
            'budget' => '/api/reports/budget',
            'risks' => '/api/reports/risks',
            'kpis' => '/api/reports/kpis',
            'departments' => '/api/reports/departments',
            'employees' => '/api/reports/employees',
            'tasks' => '/api/reports/tasks',
        ];

        if (!isset($endpointMap[$type])) {
            abort(404, 'نوع التقرير غير مدعوم للتصدير');
        }

        $response = $this->apiClient->get($endpointMap[$type], $token, $request->all());
        $data = $response['data'] ?? [];

        $fileName = "report_{$type}_" . date('Y-m-d') . ".csv";

        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename={$fileName}",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use ($data, $type) {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF");
            
            if ($type == 'budget') {
                fputcsv($file, ['النوع', 'الاسم', 'التابع لـ (الأب)', 'السنة المالية', 'المخصص', 'المصروف', 'المتبقي']);
                foreach ($data as $row) {
                    fputcsv($file, [$row['type'], $row['name'], $row['parent_name'], $row['fiscal_year'], $row['allocated'], $row['spent'], $row['remaining']]);
                }
            } 
            elseif ($type == 'risks') {
                fputcsv($file, ['الخطر', 'المبادرة', 'المهمة', 'الإدارة', 'المستوى', 'الحالة', 'إجراءات التخفيف']);
                foreach ($data as $row) {
                    fputcsv($file, [$row['name'], $row['initiative'], $row['task'], $row['department'], $row['level'], $row['status'], $row['mitigations_count']]);
                }
            }
            elseif ($type == 'kpis') {
                fputcsv($file, ['المؤشر', 'التصنيف', 'الوحدة', 'المستهدف', 'الفعلي', 'نسبة الإنجاز', 'آخر قياس']);
                foreach ($data as $row) {
                    fputcsv($file, [$row['name'], $row['category'], $row['unit'], $row['target_max'], $row['actual'] ?? 'غير مقاس', $row['achievement'] . '%', $row['last_measured']]);
                }
            }
            elseif ($type == 'departments') {
                fputcsv($file, ['الإدارة', 'الموظفين', 'المهام الرئيسية', 'المكتملة', 'المهام التشغيلية', 'المكتملة', 'قيد التنفيذ', 'المتأخرة', 'نسبة الإنجاز']);
                foreach ($data as $row) {
                    fputcsv($file, [$row['name'], $row['employees_count'], $row['major_tasks_count'], $row['major_tasks_completed'], $row['op_tasks_count'], $row['op_tasks_completed'], $row['op_tasks_in_progress'], $row['op_tasks_delayed'], $row['completion_rate'] . '%']);
                }
            }
            elseif ($type == 'employees') {
                fputcsv($file, ['الاسم', 'المسمى الوظيفي', 'الإدارة', 'المهام المسندة', 'المكتملة', 'قيد التنفيذ', 'المتأخرة', 'المرفوضة', 'إجراءات المخاطر']);
                foreach ($data as $row) {
                    fputcsv($file, [$row['name'], $row['job_title'], $row['department'], $row['assigned_tasks'], $row['completed_tasks'], $row['in_progress_tasks'], $row['delayed_tasks'], $row['rejected_tasks'], $row['mitigations_count']]);
                }
            }
            elseif ($type == 'tasks') {
                fputcsv($file, ['المهمة', 'الحالة', 'تاريخ البدء المخطط', 'تاريخ الانتهاء المخطط', 'تاريخ البدء الفعلي', 'تاريخ الانتهاء الفعلي', 'الساعات المقدرة', 'الساعات الفعلية']);
                foreach ($data as $row) {
                    fputcsv($file, [$row['title'], $row['status'], $row['planned_start'], $row['planned_end'], $row['actual_start'] ?? 'لم تبدأ', $row['actual_end'] ?? 'لم تكتمل', $row['estimated_hours'], $row['actual_hours']]);
                }
            }

            fclose($file);
        };

        return new StreamedResponse($callback, 200, $headers);
    }
}

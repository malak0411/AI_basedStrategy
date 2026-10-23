@extends('layouts.app')


@section('title', 'الميزانية والهيكل الاستراتيجي')


@push('styles')
<style>
    .kpi-card { border-right: 4px solid; transition: transform 0.2s; }
    .kpi-card:hover { transform: translateY(-3px); }
    .tree-node { border-right: 3px solid #ccc; margin-right: 15px; padding-right: 15px; }
    .bg-navy { background-color: #1a1a2e; color: white; }
    .text-navy { color: #1a1a2e; }
    .progress-thin { height: 8px; border-radius: 4px; }
    .financial-table th { background-color: #f8f9fa; font-weight: 600; }
</style>
@endpush


@section('content')
<div class="container-fluid px-4 py-3" dir="rtl">

<div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-navy"><i class="fas fa-landmark ml-2"></i> الخريطة المالية والاستراتيجية</h3>
            <p class="text-muted mb-0">متابعة التخصيصات المالية للمشاريع والبرامج الحكومية</p>
        </div>
        <form action="{{ route('budget.index') }}" method="GET" class="d-flex gap-2">
            <select name="fiscal_year" class="form-select" onchange="this.form.submit()">
                <option value="">كل السنوات المالية</option>
                @foreach($options['fiscal_years'] ?? [] as $year)
                <option value="{{ $year }}" {{ request('fiscal_year') == $year ? 'selected' : '' }}>سنة {{ $year }}</option>
                @endforeach
            </select>
        </form>
    </div>



    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm kpi-card border-primary">
                <div class="card-body">
                    <h6 class="text-muted mb-1">إجمالي الميزانية المعتمدة</h6>
                    <h4 class="fw-bold text-primary">{{ number_format($summary['total_allocated'] ?? 0, 2) }} <small>ريال</small></h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm kpi-card border-danger">
                <div class="card-body">
                    <h6 class="text-muted mb-1">إجمالي المصروفات</h6>
                    <h4 class="fw-bold text-danger">{{ number_format($summary['total_spent'] ?? 0, 2) }} <small>ريال</small></h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm kpi-card border-success">
                <div class="card-body">
                    <h6 class="text-muted mb-1">الرصيد المتبقي</h6>
                    <h4 class="fw-bold text-success">{{ number_format($summary['total_remaining'] ?? 0, 2) }} <small>ريال</small></h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm kpi-card border-warning">
                <div class="card-body">
                    <h6 class="text-muted mb-1">نسبة التنفيذ الكلية</h6>
                    <h4 class="fw-bold text-warning">{{ $summary['execution_percentage'] ?? 0 }}%</h4>
                    <div class="progress progress-thin mt-2">
                        <div class="progress-bar bg-warning" style="width: {{ min(100, $summary['execution_percentage'] ?? 0) }}%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>



    <div class="accordion shadow-sm" id="programTree">
        @foreach($tree as $program)
        <div class="accordion-item mb-2 border rounded">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed bg-white" type="button" data-bs-toggle="collapse" data-bs-target="#prog{{ $program['id'] }}">
                    <div class="d-flex w-100 justify-content-between align-items-center pe-4">
                        <div>
                            <strong class="fs-5 text-navy"><i class="fas fa-layer-group ml-2"></i> {{ $program['name'] }}</strong>
                            <div class="small text-muted mt-1">
                                <span class="badge bg-secondary">الركيزة: {{ $program['strategic_pillar'] }}</span>
                                <span class="badge bg-secondary">الهدف: {{ $program['strategic_goal'] }}</span>
                            </div>
                        </div>
                        <div class="text-start ms-4">
                            <div class="small text-muted">الميزانية المخصصة للبرنامج</div>
                            <div class="fw-bold text-primary">{{ number_format($program['direct_allocated'], 2) }} ريال</div>
                            <div class="small text-danger">المصروف: {{ number_format($program['direct_spent'], 2) }}</div>
                        </div>
                    </div>
                </button>
            </h2>
            <div id="prog{{ $program['id'] }}" class="accordion-collapse collapse">
                <div class="accordion-body bg-light p-2">

                @foreach($program['initiatives'] as $init)
                    <div class="accordion tree-node mb-2" id="initTree{{ $init['id'] }}">
                        <div class="accordion-item border-0 shadow-sm">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#init{{ $init['id'] }}">
                                    <div class="d-flex w-100 justify-content-between align-items-center pe-4">
                                        <div><i class="fas fa-flag text-info ml-2"></i> <strong>{{ $init['name'] }}</strong></div>
                                        <div class="small">
                                            <span class="badge bg-success">مخصص: {{ number_format($init['direct_allocated'], 2) }}</span>
                                            <span class="badge bg-danger">مصروف: {{ number_format($init['direct_spent'], 2) }}</span>
                                        </div>
                                    </div>
                                </button>
                            </h2>
                            <div id="init{{ $init['id'] }}" class="accordion-collapse collapse">
                                <div class="accordion-body p-0">
                                    <table class="table table-sm table-hover mb-0 text-center" style="font-size:14px">
                                        <thead class="table-light">
                                            <tr>
                                                <th>المهمة الرئيسية</th>
                                                <th>المخصص</th>
                                                <th>المصروف</th>
                                                <th>الإجراءات</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($init['major_tasks'] as $mt)
                                            <tr>
                                                <td class="text-start ps-4">
                                                    <i class="fas fa-tasks text-secondary ml-1"></i> {{ $mt['name'] }}
                                                    <button class="btn btn-link btn-sm p-0 ms-2" data-bs-toggle="collapse" data-bs-target="#mt{{ $mt['id'] }}"><i class="fas fa-eye text-primary"></i></button>
                                                </td>
                                                <td class="text-success">{{ number_format($mt['direct_allocated'], 2) }}</td>
                                                <td class="text-danger">{{ number_format($mt['direct_spent'], 2) }}</td>
                                                <td>
                                                    @if($init['status_id'] == 2)
                                                    <a href="{{ route('budget.create', ['type' => 'major_task', 'id' => $mt['id'], 'name' => $mt['name']]) }}" class="btn btn-sm btn-outline-success"><i class="fas fa-plus"></i> ميزانية</a>
                                                    @endif
                                                    @if($mt['budget_id'])
                                                    <a href="{{ route('budget.transactions', $mt['budget_id']) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-list"></i> عمليات</a>
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr class="collapse" id="mt{{ $mt['id'] }}">
                                                <td colspan="4" class="p-0 bg-white">
                                                    <table class="table table-bordered table-sm mb-0 text-center" style="font-size:13px">
                                                        <thead class="table-secondary">
                                                            <tr>
                                                                <th>المهمة التشغيلية</th>
                                                                <th>الإدارة</th>
                                                                <th>المخصص</th>
                                                                <th>المصروف</th>
                                                                <th>الإجراءات</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($mt['operational_tasks'] as $ot)
                                                            <tr>
                                                                <td class="text-start ps-4">{{ $ot['name'] }}</td>
                                                                <td>{{ $ot['department_name'] }}</td>
                                                                <td class="text-success">{{ number_format($ot['direct_allocated'], 2) }}</td>
                                                                <td class="text-danger">{{ number_format($ot['direct_spent'], 2) }}</td>
                                                                <td>
                                                                    @if($ot['status_id'] == 6)
                                                                    <a href="{{ route('budget.create', ['type' => 'operational_task', 'id' => $ot['id'], 'name' => $ot['name']]) }}" class="btn btn-sm btn-outline-success py-0"><i class="fas fa-plus"></i></a>
                                                                    @endif
                                                                    @if($ot['budget_id'])
                                                                    <a href="{{ route('budget.transactions', $ot['budget_id']) }}" class="btn btn-sm btn-outline-primary py-0"><i class="fas fa-list"></i></a>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection

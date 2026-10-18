@extends('layouts.app')


@section('title', 'مركز التقارير')


@push('styles')
<style>
    .kpi-card { border-radius: 12px; border: 1px solid #e2e8f0; transition: 0.3s; background: #fff; }
    .kpi-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.05); transform: translateY(-2px); }
    .kpi-icon { width: 48px; height: 48px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px; }
    .bg-soft-primary { background: #ebf8ff; color: #3182ce; }
    .bg-soft-success { background: #f0fff4; color: #38a169; }
    .bg-soft-warning { background: #fffaf0; color: #dd6b20; }
    .bg-soft-danger { background: #fff5f5; color: #e53e3e; }
    
    .report-nav-card { border-radius: 12px; border: 1px solid #e2e8f0; transition: 0.2s; cursor: pointer; text-decoration: none; color: inherit; }
    .report-nav-card:hover { background: #f8fafc; border-color: #cbd5e0; color: inherit; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
</style>
@endpush


@section('content')
<div class="container-fluid px-4 py-4" dir="rtl">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1"><i class="fas fa-chart-pie text-primary ml-2"></i> مركز التقارير والتحليلات</h3>
            <p class="text-muted mb-0">مؤشرات الأداء والبيانات المالية الدقيقة</p>
        </div>
    </div>


    <!-- المالية -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="kpi-card p-3 d-flex align-items-center gap-3">
                <div class="kpi-icon bg-soft-primary"><i class="fas fa-wallet"></i></div>
                <div>
                    <div class="text-muted small">إجمالي الميزانية (البرامج)</div>
                    <h4 class="fw-bold mb-0">{{ number_format($data['financials']['total_allocated'] ?? 0, 2) }} <small class="text-muted">ريال</small></h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="kpi-card p-3 d-flex align-items-center gap-3">
                <div class="kpi-icon bg-soft-danger"><i class="fas fa-money-bill-wave"></i></div>
                <div>
                    <div class="text-muted small">إجمالي المصروفات</div>
                    <h4 class="fw-bold mb-0">{{ number_format($data['financials']['total_spent'] ?? 0, 2) }} <small class="text-muted">ريال</small></h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="kpi-card p-3 d-flex align-items-center gap-3">
                <div class="kpi-icon bg-soft-success"><i class="fas fa-hand-holding-usd"></i></div>
                <div>
                    <div class="text-muted small">الرصيد المتبقي</div>
                    <h4 class="fw-bold mb-0">{{ number_format($data['financials']['total_remaining'] ?? 0, 2) }} <small class="text-muted">ريال</small></h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="kpi-card p-3 d-flex align-items-center gap-3">
                <div class="kpi-icon bg-soft-warning"><i class="fas fa-tachometer-alt"></i></div>
                <div>
                    <div class="text-muted small">نسبة التنفيذ المالي</div>
                    <h4 class="fw-bold mb-0">{{ $data['financials']['execution_pct'] ?? 0 }}%</h4>
                </div>
            </div>
        </div>
    </div>


    <!-- الأعداد -->
    <div class="row g-3 mb-5">
        @php
            $counts = [
                ['label' => 'الركائز', 'val' => $data['counts']['pillars'] ?? 0, 'color' => 'text-navy'],
                ['label' => 'الأهداف', 'val' => $data['counts']['goals'] ?? 0, 'color' => 'text-navy'],
                ['label' => 'البرامج', 'val' => $data['counts']['programs'] ?? 0, 'color' => 'text-navy'],
                ['label' => 'المبادرات', 'val' => $data['counts']['initiatives'] ?? 0, 'color' => 'text-navy'],
                ['label' => 'المهام التشغيلية', 'val' => $data['counts']['op_tasks'] ?? 0, 'color' => 'text-navy'],
                ['label' => 'المخاطر النشطة', 'val' => $data['counts']['active_risks'] ?? 0, 'color' => 'text-danger'],
            ];
        @endphp
        @foreach($counts as $c)
        <div class="col-6 col-md-2">
            <div class="kpi-card p-3 text-center">
                <h6 class="text-muted mb-1">{{ $c['label'] }}</h6>
                <h3 class="fw-bold {{ $c['color'] }} mb-0">{{ $c['val'] }}</h3>
            </div>
        </div>
        @endforeach
    </div>


    <!-- التنقل -->
    <h4 class="fw-bold mb-3 border-bottom pb-2">مكتبة التقارير</h4>
    <div class="row g-4">
        <div class="col-md-4">
            <a href="{{ route('reports.strategic') }}" class="report-nav-card p-4 h-100 d-flex align-items-center gap-3">
                <div class="kpi-icon bg-soft-primary"><i class="fas fa-sitemap"></i></div>
                <div><h5 class="fw-bold mb-1">التقرير الاستراتيجي</h5><p class="text-muted small mb-0">الركائز، الأهداف، البرامج، المبادرات، والمهام.</p></div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="{{ route('reports.budget') }}" class="report-nav-card p-4 h-100 d-flex align-items-center gap-3">
                <div class="kpi-icon bg-soft-success"><i class="fas fa-coins"></i></div>
                <div><h5 class="fw-bold mb-1">تقرير الميزانية</h5><p class="text-muted small mb-0">التوزيع المالي للمخصصات والمصروفات.</p></div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="{{ route('reports.risks') }}" class="report-nav-card p-4 h-100 d-flex align-items-center gap-3">
                <div class="kpi-icon bg-soft-danger"><i class="fas fa-shield-alt"></i></div>
                <div><h5 class="fw-bold mb-1">تقرير المخاطر</h5><p class="text-muted small mb-0">المخاطر حسب المستوى والحالة والإدارة.</p></div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="{{ route('reports.kpis') }}" class="report-nav-card p-4 h-100 d-flex align-items-center gap-3">
                <div class="kpi-icon bg-soft-warning"><i class="fas fa-chart-line"></i></div>
                <div><h5 class="fw-bold mb-1">تقرير مؤشرات الأداء</h5><p class="text-muted small mb-0">نسب الإنجاز للمؤشرات الاستراتيجية.</p></div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="{{ route('reports.departments') }}" class="report-nav-card p-4 h-100 d-flex align-items-center gap-3">
                <div class="kpi-icon bg-soft-primary"><i class="fas fa-building"></i></div>
                <div><h5 class="fw-bold mb-1">تقرير الإدارات</h5><p class="text-muted small mb-0">أداء وموارد الإدارات المختلفة.</p></div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="{{ route('reports.employees') }}" class="report-nav-card p-4 h-100 d-flex align-items-center gap-3">
                <div class="kpi-icon bg-soft-success"><i class="fas fa-users"></i></div>
                <div><h5 class="fw-bold mb-1">تقرير الموظفين</h5><p class="text-muted small mb-0">توزيع المهام والعبء الوظيفي.</p></div>
            </a>
        </div>
    </div>
</div>
@endsection

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
    .bg-soft-info { background: #e6fffa; color: #319795; }
    .bg-soft-secondary { background: #f7fafc; color: #4a5568; }
    
    .report-nav-card { border-radius: 12px; border: 1px solid #e2e8f0; transition: 0.2s; cursor: pointer; text-decoration: none; color: inherit; background: #fff; }
    .report-nav-card:hover { background: #f8fafc; border-color: #cbd5e0; color: inherit; transform: translateY(-3px); box-shadow: 0 6px 15px rgba(0,0,0,0.08); }
</style>
@endpush

@section('content')
<div class="container-fluid px-4 py-4" dir="rtl">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1 text-navy"><i class="fas fa-chart-pie text-primary ml-2"></i> مركز التقارير والتحليلات</h3>
            <p class="text-muted mb-0">لوحة تحكم شاملة تعكس الأداء التشغيلي والاستراتيجي للوزارة</p>
        </div>
        <div>
            <button onclick="window.print()" class="btn btn-outline-secondary"><i class="fas fa-print"></i> طباعة</button>
        </div>
    </div>


    <div class="row g-3 mb-5">
        @php
            $counts = [
                ['label' => 'الركائز', 'val' => $data['counts']['pillars'] ?? 0, 'icon' => 'fa-flag', 'color' => 'text-primary'],
                ['label' => 'الأهداف', 'val' => $data['counts']['goals'] ?? 0, 'icon' => 'fa-bullseye', 'color' => 'text-success'],
                ['label' => 'البرامج', 'val' => $data['counts']['programs'] ?? 0, 'icon' => 'fa-layer-group', 'color' => 'text-info'],
                ['label' => 'المبادرات', 'val' => $data['counts']['initiatives'] ?? 0, 'icon' => 'fa-flag-checkered', 'color' => 'text-warning'],
                ['label' => 'المهام الرئيسية', 'val' => $data['counts']['major_tasks'] ?? 0, 'icon' => 'fa-diagram-project', 'color' => 'text-navy'],
                ['label' => 'المهام التشغيلية', 'val' => $data['counts']['op_tasks'] ?? 0, 'icon' => 'fa-tasks', 'color' => 'text-secondary'],
                ['label' => 'المخاطر النشطة', 'val' => $data['counts']['active_risks'] ?? 0, 'icon' => 'fa-shield-alt', 'color' => 'text-danger'],
            ];
        @endphp
        @foreach($counts as $c)
        <div class="col-6 col-md-3 col-lg-2">
            <div class="kpi-card p-3 text-center h-100">
                <i class="fas {{ $c['icon'] }} {{ $c['color'] }} mb-2" style="font-size: 24px;"></i>
                <h6 class="text-muted mb-1" style="font-size: 13px;">{{ $c['label'] }}</h6>
                <h3 class="fw-bold mb-0 {{ $c['color'] }}">{{ $c['val'] }}</h3>
            </div>
        </div>
        @endforeach
    </div>


    <h4 class="fw-bold mb-3 border-bottom pb-2">مكتبة التقارير التفصيلية</h4>
    <div class="row g-4">
        <div class="col-md-4">
            <a href="{{ route('reports.strategic') }}" class="report-nav-card p-4 h-100 d-flex align-items-center gap-3">
                <div class="kpi-icon bg-soft-primary"><i class="fas fa-sitemap"></i></div>
                <div><h5 class="fw-bold mb-1">التقرير الاستراتيجي</h5><p class="text-muted small mb-0">الهيكل التنظيمي (الركائز، الأهداف، البرامج، المبادرات).</p></div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="{{ route('reports.budget') }}" class="report-nav-card p-4 h-100 d-flex align-items-center gap-3">
                <div class="kpi-icon bg-soft-success"><i class="fas fa-coins"></i></div>
                <div><h5 class="fw-bold mb-1">تقرير الميزانية</h5><p class="text-muted small mb-0">التوزيع المالي المفصل للمخصصات والمصروفات.</p></div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="{{ route('reports.risks') }}" class="report-nav-card p-4 h-100 d-flex align-items-center gap-3">
                <div class="kpi-icon bg-soft-danger"><i class="fas fa-shield-alt"></i></div>
                <div><h5 class="fw-bold mb-1">تقرير المخاطر</h5><p class="text-muted small mb-0">المخاطر حسب المبادرات، الإدارات، والمستوى.</p></div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="{{ route('reports.kpis') }}" class="report-nav-card p-4 h-100 d-flex align-items-center gap-3">
                <div class="kpi-icon bg-soft-warning"><i class="fas fa-chart-line"></i></div>
                <div><h5 class="fw-bold mb-1">تقرير مؤشرات الأداء</h5><p class="text-muted small mb-0">نسب الإنجاز بناءً على آخر قياس مسجل.</p></div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="{{ route('reports.departments') }}" class="report-nav-card p-4 h-100 d-flex align-items-center gap-3">
                <div class="kpi-icon bg-soft-info"><i class="fas fa-building"></i></div>
                <div><h5 class="fw-bold mb-1">تقرير الإدارات</h5><p class="text-muted small mb-0">أداء الإدارات، المهام، ونسب الإنجاز.</p></div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="{{ route('reports.employees') }}" class="report-nav-card p-4 h-100 d-flex align-items-center gap-3">
                <div class="kpi-icon bg-soft-secondary"><i class="fas fa-users"></i></div>
                <div><h5 class="fw-bold mb-1">تقرير الموظفين</h5><p class="text-muted small mb-0">المهام المكتملة، المتأخرة، والمرفوضة.</p></div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="{{ route('reports.tasks') }}" class="report-nav-card p-4 h-100 d-flex align-items-center gap-3">
                <div class="kpi-icon bg-soft-primary"><i class="fas fa-tasks"></i></div>
                <div><h5 class="fw-bold mb-1">تقرير المهام التشغيلية</h5><p class="text-muted small mb-0">التواريخ المخططة مقابل الفعلية والساعات.</p></div>
            </a>
        </div>
    </div>
</div>
@endsection

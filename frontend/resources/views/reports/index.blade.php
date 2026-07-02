@extends('layouts.app')

@section('title', 'التقارير')

@push('styles')
<style>
    .report-section {
        margin-bottom: 30px;
    }
    .report-card {
        background: #fff;
        border-radius: 16px;
        padding: 20px;
        border: 1px solid #e2e8f0;
        cursor: pointer;
        transition: all 0.3s ease;
        text-align: center;
    }
    .report-card:hover {
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        transform: translateY(-2px);
    }
    .report-card i {
        font-size: 40px;
        margin-bottom: 12px;
    }
    .report-card h5 {
        font-weight: 700;
        margin-bottom: 8px;
    }
    .report-card .stat {
        font-size: 28px;
        font-weight: 800;
        color: var(--primary-dark);
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <h3 class="mb-4"><i class="fas fa-file-alt ml-2"></i>التقارير</h3>

    {{-- بطاقات التقارير --}}
    <div class="row">
        {{-- تقرير المهام --}}
        <div class="col-md-4 mb-4">
            <div class="report-card" onclick="showReport('tasks')">
                <i class="fas fa-tasks text-primary"></i>
                <h5>تقرير المهام</h5>
                <div class="stat">{{ $reports['tasks']['total'] ?? 0 }}</div>
                <small class="text-muted">إجمالي المهام</small>
                <div class="mt-2">
                    <span class="badge bg-success">{{ $reports['tasks']['completed'] ?? 0 }} مكتملة</span>
                    <span class="badge bg-warning">{{ $reports['tasks']['pending'] ?? 0 }} معلقة</span>
                    <span class="badge bg-danger">{{ $reports['tasks']['delayed'] ?? 0 }} متأخرة</span>
                </div>
            </div>
        </div>

        {{-- تقرير الميزانية --}}
        <div class="col-md-4 mb-4">
            <div class="report-card" onclick="showReport('budget')">
                <i class="fas fa-money-bill-wave text-success"></i>
                <h5>تقرير الميزانية</h5>
                <div class="stat">{{ number_format($reports['budget']['total'] ?? 0) }}</div>
                <small class="text-muted">إجمالي الميزانية</small>
                <div class="mt-2">
                    <span class="badge bg-info">مستخدم: {{ number_format($reports['budget']['used'] ?? 0) }}</span>
                    <span class="badge bg-success">متبقي: {{ number_format($reports['budget']['remaining'] ?? 0) }}</span>
                </div>
            </div>
        </div>

        {{-- تقرير المخاطر --}}
        <div class="col-md-4 mb-4">
            <div class="report-card" onclick="showReport('risks')">
                <i class="fas fa-exclamation-triangle text-warning"></i>
                <h5>تقرير المخاطر</h5>
                <div class="stat">{{ $reports['risks']['total'] ?? 0 }}</div>
                <small class="text-muted">إجمالي المخاطر</small>
                <div class="mt-2">
                    <span class="badge bg-danger">{{ $reports['risks']['high'] ?? 0 }} عالية</span>
                    <span class="badge bg-warning">{{ $reports['risks']['medium'] ?? 0 }} متوسطة</span>
                    <span class="badge bg-success">{{ $reports['risks']['low'] ?? 0 }} منخفضة</span>
                </div>
            </div>
        </div>

        {{-- تقرير مؤشرات الأداء --}}
        <div class="col-md-4 mb-4">
            <div class="report-card" onclick="showReport('kpis')">
                <i class="fas fa-chart-line text-info"></i>
                <h5>تقرير مؤشرات الأداء</h5>
                <div class="stat">{{ $reports['kpis']['total'] ?? 0 }}</div>
                <small class="text-muted">إجمالي المؤشرات</small>
                <div class="mt-2">
                    <span class="badge bg-success">{{ $reports['kpis']['on_target'] ?? 0 }} محقق</span>
                    <span class="badge bg-warning">{{ $reports['kpis']['below_target'] ?? 0 }} دون المستهدف</span>
                </div>
            </div>
        </div>

        {{-- تقرير الإدارات --}}
        <div class="col-md-4 mb-4">
            <div class="report-card" onclick="showReport('departments')">
                <i class="fas fa-building text-primary"></i>
                <h5>تقرير الإدارات</h5>
                <div class="stat">{{ $reports['departments']['total'] ?? 0 }}</div>
                <small class="text-muted">إجمالي الإدارات</small>
            </div>
        </div>

        {{-- تقرير الموظفين --}}
        <div class="col-md-4 mb-4">
            <div class="report-card" onclick="showReport('employees')">
                <i class="fas fa-users text-success"></i>
                <h5>تقرير الموظفين</h5>
                <div class="stat">{{ $reports['employees']['total'] ?? 0 }}</div>
                <small class="text-muted">إجمالي الموظفين</small>
                <div class="mt-2">
                    <span class="badge bg-success">{{ $reports['employees']['active'] ?? 0 }} نشط</span>
                    <span class="badge bg-secondary">{{ $reports['employees']['inactive'] ?? 0 }} غير نشط</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function showReport(type) {
        alert('تقرير ' + type + ' - سيتم تحميل التقرير المفصل قريباً');
    }
</script>
@endpush

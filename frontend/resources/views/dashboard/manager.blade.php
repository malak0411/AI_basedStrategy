@extends('layouts.app')

@section('title', 'لوحة تحكم المدير - نظام إدارة الاستراتيجية')

@section('content')
<div class="container-fluid px-4">
    
    {{-- رأس الصفحة مع تحية للمدير --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1 text-primary">
                        <i class="fas fa-tachometer-alt ml-2"></i>
                        لوحة تحكم المدير
                    </h2>
                    <p class="text-muted mb-0">
                        <i class="fas fa-building ml-1"></i>
                        إدارة: {{ $stats['department_name'] ?? 'الإدارة' }}
                        <span class="mx-2">|</span>
                        <i class="far fa-calendar-alt ml-1"></i>
                        {{ \Carbon\Carbon::now()->format('Y-m-d') }}
                    </p>
                </div>
                <div>
                    <span class="text-muted small">
                        <i class="far fa-clock ml-1"></i>
                        آخر تحديث: {{ \Carbon\Carbon::now()->format('H:i:s') }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- رسالة الخطأ --}}
    @if(isset($error))
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle ml-2"></i>
        {{ $error }}
        <button type="button" class="close" data-dismiss="alert">
            <span>&times;</span>
        </button>
    </div>
    @endif

    {{-- بطاقات الإحصائيات الرئيسية --}}
    <div class="row mb-4">
        
        {{-- الموظفين --}}
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-right-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                الموظفين
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $stats['total_employees'] ?? 0 }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- المهام النشطة --}}
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-right-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                مهام نشطة
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $stats['active_tasks'] ?? 0 }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-tasks fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- نسبة الإنجاز --}}
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-right-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                نسبة الإنجاز
                            </div>
                            <div class="row no-gutters align-items-center">
                                <div class="col-auto">
                                    <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800">
                                        {{ $stats['completion_rate'] ?? 0 }}%
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="progress progress-sm mr-2">
                                        <div class="progress-bar bg-info" 
                                             style="width: {{ $stats['completion_rate'] ?? 0 }}%">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clipboard-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- المهام المتأخرة --}}
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-right-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                مهام متأخرة
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $stats['delayed_tasks'] ?? 0 }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- المحتوى الرئيسي: المهام والموظفين --}}
    <div class="row">
        
        {{-- قائمة المهام --}}
        <div class="col-lg-8 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center bg-white">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-tasks ml-2"></i>
                        مهام الإدارة
                    </h6>
                    <div>
                        <button class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-filter ml-1"></i>
                            تصفية
                        </button>
                        <a href="{{ route('tasks.index') }}" class="btn btn-sm btn-primary mr-2">
                            <i class="fas fa-list ml-1"></i>
                            عرض الكل
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th style="width: 40px;">#</th>
                                    <th>المهمة</th>
                                    <th>المسؤول</th>
                                    <th>الحالة</th>
                                    <th>التقدم</th>
                                    <th>التاريخ</th>
                                    <th style="width: 80px;">إجراء</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($tasks as $index => $task)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        <div class="font-weight-bold">{{ $task['task_name'] ?? 'غير محدد' }}</div>
                                        <small class="text-muted">
                                            {{ \Illuminate\Support\Str::limit($task['description'] ?? '', 60) }}
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge badge-light">
                                            <i class="fas fa-user ml-1"></i>
                                            {{ $task['assigned_to_name'] ?? 'غير معين' }}
                                        </span>
                                    </td>
                                    <td>
                                        @php
                                            $status = $task['status'] ?? 'pending';
                                            $statusColors = [
                                                'completed' => 'success',
                                                'in_progress' => 'info',
                                                'pending' => 'warning',
                                                'delayed' => 'danger'
                                            ];
                                            $statusLabels = [
                                                'completed' => 'مكتمل',
                                                'in_progress' => 'قيد التنفيذ',
                                                'pending' => 'معلق',
                                                'delayed' => 'متأخر'
                                            ];
                                            $color = $statusColors[$status] ?? 'secondary';
                                            $label = $statusLabels[$status] ?? $status;
                                        @endphp
                                        <span class="badge badge-{{ $color }}">
                                            {{ $label }}
                                        </span>
                                    </td>
                                    <td style="width: 120px;">
                                        <div class="d-flex align-items-center">
                                            <div class="progress flex-grow-1 ml-2" style="height: 6px;">
                                                <div class="progress-bar bg-{{ ($task['progress'] ?? 0) >= 80 ? 'success' : 'info' }}" 
                                                     style="width: {{ $task['progress'] ?? 0 }}%">
                                                </div>
                                            </div>
                                            <small>{{ $task['progress'] ?? 0 }}%</small>
                                        </div>
                                    </td>
                                    <td>
                                        <small>{{ $task['due_date'] ?? 'غير محدد' }}</small>
                                    </td>
                                    <td>
                                        <a href="{{ route('tasks.show', $task['id'] ?? 0) }}" 
                                           class="btn btn-sm btn-outline-info" 
                                           title="عرض التفاصيل">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                                        <p class="text-muted">لا توجد مهام حالياً</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- قائمة الموظفين --}}
        <div class="col-lg-4 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 bg-white">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-users ml-2"></i>
                        الموظفين
                    </h6>
                </div>
                <div class="card-body p-0">
                    @forelse($employees as $employee)
                    <div class="border-bottom p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm ml-2">
                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" 
                                         style="width: 40px; height: 40px;">
                                        {{ mb_substr($employee['full_name'] ?? 'م', 0, 1) }}
                                    </div>
                                </div>
                                <div>
                                    <div class="font-weight-bold small">
                                        {{ $employee['full_name'] ?? 'غير محدد' }}
                                    </div>
                                    <small class="text-muted">
                                        {{ $employee['job_title'] ?? '' }}
                                    </small>
                                </div>
                            </div>
                            <div>
                                @if($employee['is_active'] ?? false)
                                    <span class="badge badge-success">نشط</span>
                                @else
                                    <span class="badge badge-secondary">غير نشط</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-4">
                        <i class="fas fa-user-slash fa-2x text-muted mb-2 d-block"></i>
                        <p class="text-muted">لا يوجد موظفين</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- مؤشرات الأداء --}}
    @if(!empty($kpis))
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3 bg-white">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-line ml-2"></i>
                        مؤشرات الأداء الرئيسية
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($kpis as $kpi)
                        <div class="col-md-3 mb-3">
                            <div class="border rounded p-3 text-center">
                                <div class="text-muted small mb-2">
                                    {{ $kpi['kpi_name'] ?? 'مؤشر أداء' }}
                                </div>
                                <div class="h4 mb-1 font-weight-bold text-primary">
                                    {{ $kpi['current_value'] ?? 0 }}%
                                </div>
                                <div class="text-muted small">
                                    المستهدف: {{ $kpi['target_value'] ?? 0 }}%
                                </div>
                                <div class="progress mt-2" style="height: 5px;">
                                    @php
                                        $percentage = min(($kpi['current_value'] ?? 0) / ($kpi['target_value'] ?? 1) * 100, 100);
                                    @endphp
                                    <div class="progress-bar bg-{{ $percentage >= 80 ? 'success' : 'warning' }}" 
                                         style="width: {{ $percentage }}%">
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

</div>
@endsection

@push('styles')
<style>
    .border-right-primary {
        border-right: 4px solid #4e73df !important;
    }
    .border-right-success {
        border-right: 4px solid #1cc88a !important;
    }
    .border-right-info {
        border-right: 4px solid #36b9cc !important;
    }
    .border-right-warning {
        border-right: 4px solid #f6c23e !important;
    }
    .card {
        border: none;
        border-radius: 0.5rem;
        transition: transform 0.2s;
    }
    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15) !important;
    }
    .table th {
        border-top: none;
        font-size: 0.85rem;
        font-weight: 600;
        color: #4e73df;
    }
    .table td {
        vertical-align: middle;
        font-size: 0.9rem;
    }
    .progress {
        background-color: #eaecf4;
    }
    .badge {
        font-size: 0.8rem;
        padding: 0.4em 0.8em;
    }
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    // تفعيل tooltips
    $('[title]').tooltip();
    
    // تحديث الصفحة كل 5 دقائق
    setInterval(function() {
        location.reload();
    }, 300000);
    
    console.log('✅ لوحة تحكم المدير جاهزة');
});
</script>
@endpush

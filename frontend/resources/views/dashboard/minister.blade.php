@extends('layouts.app')

@section('title', 'لوحة تحكم الوزير')

@section('content')
<div class="container-fluid px-4">
    <h3 class="mb-4">
        <i class="fas fa-crown text-warning ml-2"></i>
        لوحة التحكم الرئيسية
    </h3>

    @if(isset($error))
        <div class="alert alert-warning">{{ $error }}</div>
    @endif

    {{-- بطاقات إحصائية --}}
    <div class="row">
        <div class="col-md-3 mb-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="number">{{ $stats['total_employees'] ?? 0 }}</div>
                        <div class="label">إجمالي الموظفين</div>
                    </div>
                    <div class="icon"><i class="fas fa-users"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="number">{{ $stats['total_departments'] ?? 0 }}</div>
                        <div class="label">الإدارات</div>
                    </div>
                    <div class="icon"><i class="fas fa-building"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="number">{{ $stats['completion_rate'] ?? 0 }}%</div>
                        <div class="label">نسبة الإنجاز</div>
                    </div>
                    <div class="icon"><i class="fas fa-chart-line"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="number">{{ $stats['delayed_tasks'] ?? 0 }}</div>
                        <div class="label">مهام متأخرة</div>
                    </div>
                    <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
                </div>
            </div>
        </div>
    </div>

    {{-- أداء الإدارات --}}
    <div class="card-custom mb-4">
        <h5>أداء الإدارات</h5>
        @if(!empty($departments))
            <table class="table table-bordered">
                <thead>
                    <tr><th>الإدارة</th><th>المهام المنجزة</th><th>نسبة الإنجاز</th></tr>
                </thead>
                <tbody>
                    @foreach($departments as $dept)
                    <tr>
                        <td>{{ $dept['name'] ?? '' }}</td>
                        <td>{{ $dept['completed_tasks'] ?? 0 }}</td>
                        <td>{{ $dept['completion_rate'] ?? 0 }}%</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="text-muted">لا توجد بيانات حالياً</p>
        @endif
    </div>

    {{-- الركائز الاستراتيجية --}}
    <div class="card-custom mb-4">
        <h5>الركائز الاستراتيجية</h5>
        @if(!empty($pillars))
            <ul>
                @foreach($pillars as $pillar)
                    <li>{{ $pillar['name'] ?? $pillar['title'] ?? '' }}</li>
                @endforeach
            </ul>
        @else
            <p class="text-muted">لا توجد ركائز</p>
        @endif
    </div>

    {{-- مهام متأخرة --}}
    <div class="card-custom">
        <h5>مهام متأخرة على مستوى المؤسسة</h5>
        @if(!empty($delayedTasks))
            <ul>
                @foreach($delayedTasks as $task)
                    <li>{{ $task['task_name'] ?? '' }} ({{ $task['due_date'] ?? '' }})</li>
                @endforeach
            </ul>
        @else
            <p class="text-muted">لا توجد مهام متأخرة</p>
        @endif
    </div>
</div>
@endsection

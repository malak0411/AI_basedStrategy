@extends('layouts.app')

@section('title', 'تقرير الإدارات')

@section('content')
<div class="container-fluid px-4 py-4" dir="rtl">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold"><i class="fas fa-building text-primary ml-2"></i> أداء الإدارات</h3>
        <a href="{{ route('reports.index') }}" class="btn btn-secondary">عودة</a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <table class="table table-bordered text-center align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>الإدارة</th>
                        <th>الموظفين</th>
                        <th>المهام الرئيسية</th>
                        <th>المهام المكتملة (رئيسية)</th>
                        <th>المهام التشغيلية</th>
                        <th>المكتملة</th>
                        <th>قيد التنفيذ</th>
                        <th>المتأخرة</th>
                        <th>نسبة الإنجاز</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($departments as $d)
                    <tr>
                        <td class="text-start fw-bold">{{ $d['name'] }}</td>
                        <td>{{ $d['employees_count'] }}</td>
                        <td>{{ $d['major_tasks_count'] }}</td>
                        <td class="text-success">{{ $d['major_tasks_completed'] }}</td>
                        <td>{{ $d['op_tasks_count'] }}</td>
                        <td class="text-success fw-bold">{{ $d['op_tasks_completed'] }}</td>
                        <td class="text-warning">{{ $d['op_tasks_in_progress'] }}</td>
                        <td class="text-danger">{{ $d['op_tasks_delayed'] }}</td>
                        <td>
                            <div class="progress" style="height: 20px;">
                                <div class="progress-bar bg-success" style="width: {{ $d['completion_rate'] }}%">{{ $d['completion_rate'] }}%</div>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

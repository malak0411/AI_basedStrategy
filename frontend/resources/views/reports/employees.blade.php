@extends('layouts.app')

@section('title', 'تقرير الموظفين')

@section('content')
<div class="container-fluid px-4 py-4" dir="rtl">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold"><i class="fas fa-users text-primary ml-2"></i> تحليل أداء الموظفين</h3>
        <a href="{{ route('reports.index') }}" class="btn btn-secondary">عودة</a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <table class="table table-bordered text-center align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>الاسم</th>
                        <th>المسمى</th>
                        <th>الإدارة</th>
                        <th>المهام المسندة</th>
                        <th>المكتملة</th>
                        <th>قيد التنفيذ</th>
                        <th>المتأخرة</th>
                        <th>المرفوضة</th>
                        <th>إجراءات المخاطر</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($employees as $e)
                    <tr>
                        <td class="text-start fw-bold">{{ $e['name'] }}</td>
                        <td>{{ $e['job_title'] }}</td>
                        <td>{{ $e['department'] }}</td>
                        <td><span class="badge bg-secondary">{{ $e['assigned_tasks'] }}</span></td>
                        <td class="text-success fw-bold">{{ $e['completed_tasks'] }}</td>
                        <td class="text-warning">{{ $e['in_progress_tasks'] }}</td>
                        <td class="text-danger">{{ $e['delayed_tasks'] }}</td>
                        <td class="text-dark">{{ $e['rejected_tasks'] }}</td>
                        <td><span class="badge bg-info text-dark">{{ $e['mitigations_count'] }}</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

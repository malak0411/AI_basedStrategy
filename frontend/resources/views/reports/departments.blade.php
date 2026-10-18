@extends('layouts.app')


@section('title', 'تقرير الإدارات')


@section('content')
<div class="container-fluid px-4 py-4" dir="rtl">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold"><i class="fas fa-building text-primary ml-2"></i> أداء الإدارات</h3>
        <div class="d-flex gap-2">
            <a href="{{ route('reports.export', 'departments') }}" class="btn btn-success"><i class="fas fa-file-excel"></i> تصدير</a>
            <a href="{{ route('reports.index') }}" class="btn btn-secondary">عودة</a>
        </div>
    </div>


    <div class="card shadow-sm border-0">
        <div class="card-body">
            <table class="table table-bordered text-center align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>الإدارة</th>
                        <th>عدد الموظفين</th>
                        <th>إجمالي المهام</th>
                        <th>المهام المكتملة</th>
                        <th>المهام قيد التنفيذ</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($departments as $d)
                    <tr>
                        <td class="text-start fw-bold">{{ $d['name'] }}</td>
                        <td>{{ $d['employees'] }}</td>
                        <td>{{ $d['tasks'] }}</td>
                        <td class="text-success fw-bold">{{ $d['completed_tasks'] }}</td>
                        <td class="text-warning fw-bold">{{ $d['in_progress_tasks'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

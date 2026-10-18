@extends('layouts.app')


@section('title', 'تقرير الميزانية')


@section('content')
<div class="container-fluid px-4 py-4" dir="rtl">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold"><i class="fas fa-coins text-success ml-2"></i> تقرير الميزانية والحركات المالية</h3>
        <div class="d-flex gap-2">
            <a href="{{ route('reports.export', 'budget') }}" class="btn btn-success"><i class="fas fa-file-excel"></i> تصدير</a>
            <a href="{{ route('reports.index') }}" class="btn btn-secondary">عودة</a>
        </div>
    </div>


    <div class="row g-4">
        @php
            $sections = [
                'program' => 'البرامج',
                'initiative' => 'المبادرات',
                'major_task' => 'المهام الرئيسية',
                'operational_task' => 'المهام التشغيلية'
            ];
        @endphp
        @foreach($sections as $key => $title)
        <div class="col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-dark text-white fw-bold">{{ $title }}</div>
                <div class="card-body p-0">
                    <table class="table table-hover text-center mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>المعرف</th>
                                <th>المخصص</th>
                                <th>المصروف</th>
                                <th>المتبقي</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($budgets[$key] ?? [] as $b)
                            <tr>
                                <td>#{{ $b['id'] }}</td>
                                <td class="text-primary fw-bold">{{ number_format($b['allocated'], 2) }}</td>
                                <td class="text-danger">{{ number_format($b['spent'], 2) }}</td>
                                <td class="text-success fw-bold">{{ number_format($b['remaining'], 2) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-muted py-3">لا توجد بيانات</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection

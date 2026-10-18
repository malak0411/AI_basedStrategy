@extends('layouts.app')


@section('title', 'تقرير المخاطر')


@section('content')
<div class="container-fluid px-4 py-4" dir="rtl">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold"><i class="fas fa-shield-alt text-danger ml-2"></i> سجل المخاطر</h3>
        <div class="d-flex gap-2">
            <a href="{{ route('reports.export', 'risks') }}" class="btn btn-success"><i class="fas fa-file-excel"></i> تصدير</a>
            <a href="{{ route('reports.index') }}" class="btn btn-secondary">عودة</a>
        </div>
    </div>


    <div class="card shadow-sm border-0">
        <div class="card-body">
            <table class="table table-bordered table-hover text-center align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>الرقم</th>
                        <th>الخطر</th>
                        <th>المستوى</th>
                        <th>الحالة</th>
                        <th>الاحتمال</th>
                        <th>التأثير</th>
                        <th>الإدارة</th>
                        <th>المهمة المرتبطة</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($risks as $r)
                    <tr>
                        <td>#{{ $r['id'] }}</td>
                        <td class="text-start fw-bold">{{ $r['name'] }}</td>
                        <td><span class="badge bg-danger">{{ $r['level'] }}</span></td>
                        <td><span class="badge bg-secondary">{{ $r['status'] }}</span></td>
                        <td>{{ $r['probability'] }}</td>
                        <td>{{ $r['impact'] }}</td>
                        <td>{{ $r['department'] }}</td>
                        <td class="text-muted small">{{ $r['task'] }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-muted py-4">لا توجد مخاطر مسجلة</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

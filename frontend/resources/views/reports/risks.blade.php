@extends('layouts.app')

@section('title', 'تقرير المخاطر')

@section('content')
<div class="container-fluid px-4 py-4" dir="rtl">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold"><i class="fas fa-shield-alt text-danger ml-2"></i> سجل المخاطر المرتبط بالمبادرات</h3>
        <a href="{{ route('reports.index') }}" class="btn btn-secondary">عودة</a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <table class="table table-bordered table-hover text-center align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>الخطر</th>
                        <th>المبادرة التابع لها</th>
                        <th>المهمة</th>
                        <th>الإدارة</th>
                        <th>المستوى</th>
                        <th>الحالة</th>
                        <th>إجراءات التخفيف</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($risks as $r)
                    <tr>
                        <td class="text-start fw-bold">{{ $r['name'] }}</td>
                        <td><span class="badge bg-info text-dark">{{ $r['initiative'] }}</span></td>
                        <td class="text-muted small">{{ $r['task'] }}</td>
                        <td>{{ $r['department'] }}</td>
                        <td><span class="badge bg-danger">{{ $r['level'] }}</span></td>
                        <td><span class="badge bg-secondary">{{ $r['status'] }}</span></td>
                        <td><span class="badge bg-primary">{{ $r['mitigations_count'] }}</span></td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-muted py-4">لا توجد مخاطر مسجلة حالياً.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

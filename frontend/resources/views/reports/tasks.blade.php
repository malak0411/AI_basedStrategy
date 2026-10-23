@extends('layouts.app')

@section('title', 'تقرير المهام')

@section('content')
<div class="container-fluid px-4 py-4" dir="rtl">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold"><i class="fas fa-tasks text-primary ml-2"></i> المهام التشغيلية (المخطط مقابل الفعلي)</h3>
        <a href="{{ route('reports.index') }}" class="btn btn-secondary">عودة</a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <table class="table table-bordered table-hover text-center align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>المهمة</th>
                        <th>الحالة</th>
                        <th>تاريخ البدء المخطط</th>
                        <th>تاريخ الانتهاء المخطط</th>
                        <th>تاريخ البدء الفعلي</th>
                        <th>تاريخ الانتهاء الفعلي</th>
                        <th>الساعات المقدرة</th>
                        <th>الساعات الفعلية</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tasks as $t)
                    <tr>
                        <td class="text-start fw-bold">{{ $t['title'] }}</td>
                        <td><span class="badge bg-info text-dark">{{ $t['status'] }}</span></td>
                        <td>{{ $t['planned_start'] ? \Carbon\Carbon::parse($t['planned_start'])->format('Y-m-d') : '-' }}</td>
                        <td>{{ $t['planned_end'] ? \Carbon\Carbon::parse($t['planned_end'])->format('Y-m-d') : '-' }}</td>
                        <td class="text-success">{{ $t['actual_start'] ? \Carbon\Carbon::parse($t['actual_start'])->format('Y-m-d') : 'لم تبدأ' }}</td>
                        <td class="text-danger">{{ $t['actual_end'] ? \Carbon\Carbon::parse($t['actual_end'])->format('Y-m-d') : 'لم تكتمل' }}</td>
                        <td>{{ $t['estimated_hours'] }}</td>
                        <td>{{ $t['actual_hours'] }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-muted py-4">لا توجد مهام مسجلة.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

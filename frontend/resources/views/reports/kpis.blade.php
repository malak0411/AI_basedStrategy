@extends('layouts.app')


@section('title', 'مؤشرات الأداء')


@section('content')
<div class="container-fluid px-4 py-4" dir="rtl">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold"><i class="fas fa-chart-line text-warning ml-2"></i> مؤشرات الأداء الاستراتيجية</h3>
        <div class="d-flex gap-2">
            <a href="{{ route('reports.export', 'kpis') }}" class="btn btn-success"><i class="fas fa-file-excel"></i> تصدير</a>
            <a href="{{ route('reports.index') }}" class="btn btn-secondary">عودة</a>
        </div>
    </div>


    <div class="card shadow-sm border-0">
        <div class="card-body">
            <table class="table table-bordered text-center align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>المؤشر</th>
                        <th>الوحدة</th>
                        <th>المستهدف</th>
                        <th>الفعلي</th>
                        <th>نسبة الإنجاز</th>
                        <th>آخر قياس</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($kpis as $k)
                    <tr>
                        <td class="text-start fw-bold">{{ $k['name'] }}</td>
                        <td>{{ $k['unit'] }}</td>
                        <td>{{ number_format($k['target'], 2) }}</td>
                        <td class="fw-bold text-primary">{{ $k['actual'] !== null ? number_format($k['actual'], 2) : 'غير متوفر' }}</td>
                        <td>
                            <div class="progress" style="height: 20px;">
                                <div class="progress-bar {{ $k['achievement'] >= 100 ? 'bg-success' : ($k['achievement'] >= 50 ? 'bg-warning' : 'bg-danger') }}" 
                                     style="width: {{ min(100, $k['achievement']) }}%">
                                    {{ $k['achievement'] }}%
                                </div>
                            </div>
                        </td>
                        <td>{{ $k['last_measured'] ? \Carbon\Carbon::parse($k['last_measured'])->format('Y-m-d') : 'لم يقس بعد' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-muted py-4">لا توجد مؤشرات مسجلة</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@extends('layouts.app')

@section('title', 'تفاصيل المؤشر')

@push('styles')
<style>
    .measurement-table td { vertical-align: middle; }
    .trend-up { color: #38a169; }
    .trend-down { color: #e53e3e; }
    .trend-stable { color: #718096; }
    .chart-placeholder {
        background: linear-gradient(135deg, #f8fafc, #edf2f7);
        border-radius: 12px;
        padding: 40px;
        text-align: center;
        margin-bottom: 20px;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <a href="{{ route('kpis.index') }}" class="btn btn-outline-secondary mb-3">
        <i class="fas fa-arrow-right"></i> العودة للمؤشرات
    </a>

    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif

    @if(empty($kpi))
        <div class="alert alert-info">المؤشر غير موجود</div>
    @else
        <div class="row">
            {{-- بطاقة المؤشر --}}
            <div class="col-lg-4">
                <div class="card-custom mb-4 text-center">
                    @php
                        $current = $kpi['current_value'] ?? 0;
                        $target = $kpi['target_value'] ?? 100;
                        $pct = $target > 0 ? min(round(($current / $target) * 100), 100) : 0;
                    @endphp
                    <h5 class="mb-3">{{ $kpi['name'] ?? $kpi['title'] ?? '' }}</h5>
                    <div class="display-3 fw-bold {{ $current >= $target ? 'text-success' : 'text-danger' }} mb-2">
                        {{ $current }}<small class="fs-6"> {{ $kpi['unit'] ?? '%' }}</small>
                    </div>
                    <p class="text-muted">المستهدف: {{ $target }} {{ $kpi['unit'] ?? '%' }}</p>
                    <div class="progress mb-3" style="height: 12px;">
                        <div class="progress-bar {{ $pct >= 100 ? 'bg-success' : ($pct > 60 ? 'bg-warning' : 'bg-danger') }}" style="width: {{ $pct }}%">{{ $pct }}%</div>
                    </div>
                    <p class="text-muted small">{{ $kpi['description'] ?? '' }}</p>
                    <div class="d-flex justify-content-center gap-2 mt-3">
                        <a href="{{ route('kpis.measurements', $kpi['id']) }}" class="btn btn-outline-info btn-sm">
                            <i class="fas fa-ruler"></i> القياسات
                        </a>
                        <a href="{{ route('kpis.edit', $kpi['id']) }}" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-edit"></i> تعديل
                        </a>
                    </div>
                </div>

                {{-- إحصائيات --}}
                <div class="row">
                    <div class="col-6"><div class="stat-mini text-center p-3 bg-light rounded mb-2"><div class="fw-bold">{{ $avg }}</div><small>المتوسط</small></div></div>
                    <div class="col-6"><div class="stat-mini text-center p-3 bg-light rounded mb-2"><div class="fw-bold">{{ $max }}</div><small>الأعلى</small></div></div>
                    <div class="col-6"><div class="stat-mini text-center p-3 bg-light rounded"><div class="fw-bold">{{ $min }}</div><small>الأدنى</small></div></div>
                    <div class="col-6"><div class="stat-mini text-center p-3 bg-light rounded">
                        <div class="fw-bold"><i class="fas fa-arrow-{{ $trend == 'up' ? 'up text-success' : ($trend == 'down' ? 'down text-danger' : 'right text-muted') }}"></i></div>
                        <small>{{ $trend == 'up' ? 'صاعد' : ($trend == 'down' ? 'هابط' : 'ثابت') }}</small>
                    </div></div>
                </div>
            </div>

            {{-- جدول القياسات --}}
            <div class="col-lg-8">
                <div class="card-custom">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5><i class="fas fa-history ml-2"></i>سجل القياسات</h5>
                        <a href="{{ route('kpis.measurements.create', $kpi['id']) }}" class="btn btn-sm btn-gold">
                            <i class="fas fa-plus"></i> قياس جديد
                        </a>
                    </div>

                    @if(empty($measurements))
                        <div class="text-center py-4">
                            <i class="fas fa-ruler fa-3x text-muted mb-3"></i>
                            <p>لا توجد قياسات بعد</p>
                            <a href="{{ route('kpis.measurements.create', $kpi['id']) }}" class="btn btn-primary">تسجيل أول قياس</a>
                        </div>
                    @else
                        <div class="chart-placeholder">
                            <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                            <p class="text-muted">الرسم البياني للقياسات</p>
                            <small>آخر {{ count($measurements) }} قياس</small>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover measurement-table">
                                <thead><tr><th>#</th><th>القيمة</th><th>التاريخ</th><th>ملاحظات</th><th></th></tr></thead>
                                <tbody>
                                    @foreach($measurements as $m)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td><strong>{{ $m['value'] ?? 0 }}</strong> {{ $kpi['unit'] ?? '%' }}</td>
                                        <td><small>{{ $m['measurement_date'] ?? $m['created_at'] ?? '' }}</small></td>
                                        <td><small>{{ $m['notes'] ?? '' }}</small></td>
                                        <td>
                                            <form action="{{ route('kpis.measurements.destroy', $m['id']) }}" method="POST" onsubmit="return confirm('حذف هذا القياس؟')">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

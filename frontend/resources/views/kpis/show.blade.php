@extends('layouts.app')

@section('title', 'تفاصيل المؤشر')

@section('content')
<div class="container-fluid px-4">
    <a href="{{ route('kpis.index') }}" class="btn btn-outline-secondary mb-3">
        <i class="fas fa-arrow-right"></i> العودة
    </a>

    @if(empty($kpi))
        <div class="alert alert-info">المؤشر غير موجود</div>
    @else
        <div class="card-custom">
            <div class="d-flex justify-content-between align-items-start mb-4">
                <h4>{{ $kpi['name'] ?? $kpi['title'] ?? '' }}</h4>
                <div>
                    <a href="{{ route('kpis.measurements', $kpi['id']) }}" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-ruler"></i> القياسات
                    </a>
                    <a href="{{ route('kpis.edit', $kpi['id']) }}" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-edit"></i> تعديل
                    </a>
                </div>
            </div>

            <p class="text-muted">{{ $kpi['description'] ?? 'لا يوجد وصف' }}</p>

            <div class="row mt-4">
                <div class="col-md-3">
                    <strong>النوع:</strong> {{ $kpi['type'] ?? '' }}
                </div>
                <div class="col-md-3">
                    <strong>المستهدف:</strong> {{ $kpi['target_value'] ?? 0 }}{{ $kpi['unit'] ?? '%' }}
                </div>
                <div class="col-md-3">
                    <strong>الحالي:</strong> {{ $kpi['current_value'] ?? 0 }}{{ $kpi['unit'] ?? '%' }}
                </div>
                <div class="col-md-3">
                    <strong>نسبة الإنجاز:</strong> 
                    @php $pct = ($kpi['target_value'] ?? 0) > 0 ? round(($kpi['current_value'] ?? 0) / ($kpi['target_value'] ?? 1) * 100) : 0; @endphp
                    {{ $pct }}%
                </div>
            </div>

            <div class="mt-4">
                <strong>التقدم نحو المستهدف:</strong>
                <div class="progress mt-2" style="height: 16px;">
                    <div class="progress-bar bg-{{ $pct >= 80 ? 'success' : ($pct >= 50 ? 'info' : 'warning') }}" 
                         style="width: {{ min($pct, 100) }}%">
                        {{ $pct }}%
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

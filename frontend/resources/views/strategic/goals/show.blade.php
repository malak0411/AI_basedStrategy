@extends('layouts.app')

@section('title', 'تفاصيل الهدف')

@push('styles')
<style>
    .program-card {
        background: #fff; border-radius: 12px; padding: 16px; cursor: pointer;
        border: 1px solid #e2e8f0; transition: all 0.2s; border-right: 4px solid #38a169;
    }
    .program-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.06); }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <a href="{{ route('strategic.goals.index') }}" class="btn btn-outline-secondary mb-3">
        <i class="fas fa-arrow-right"></i> العودة للأهداف
    </a>
     <a href="{{ route('strategic.pillars.show', $goal['pillar_id']) }}" class="btn btn-outline-secondary mb-3">
        <i class="fas fa-arrow-right"></i> العودة للركيزة
    </a>

    <div class="card-custom mb-4">
        <h4>{{ $goal['name'] ?? $goal['title'] ?? '' }}</h4>
        <p class="text-muted">{{ $goal['description'] ?? '' }}</p>
        <div class="row mt-3">
            <div class="col-md-3"><small>الركيزة</small><div><strong>{{ $goal['pillar_name'] ?? '' }}</strong></div></div>
            <div class="col-md-3"><small>تاريخ البداية</small><div>{{ $goal['start_date'] ?? '-' }}</div></div>
            <div class="col-md-3"><small>تاريخ النهاية</small><div>{{ $goal['end_date'] ?? '-' }}</div></div>
            <div class="col-md-3"><small>تاريخ الهدف</small><div>{{ $goal['target_date'] ?? '-' }}</div></div>
            <div class="col-md-3"><small>الوزن</small><div>{{ $goal['weight'] ?? 0 }}</div></div>

            <div class="col-md-3"><small>الحالة</small><div>
                <span class="badge bg-{{ ($goal['status'] ?? 'active') == 'active' ? 'success' : 'secondary' }}">
                    {{ ($goal['status'] ?? 'active') == 'active' ? 'نشط' : 'غير نشط' }}
                </span>
            </div></div>
        </div>
    </div>

    <h5 class="mb-3"><i class="fas fa-project-diagram ml-2"></i>البرامج المرتبطة ({{ count($goal['programs'] ?? []) }})</h5>

    @if(empty($goal['programs']))
    <div class="card-custom text-center py-4"><p class="text-muted">لا توجد برامج</p></div>
    @else
    <div class="row">
        @foreach($goal['programs'] as $program)
        <div class="col-md-4 mb-3">
            <div class="program-card" onclick="window.location='{{ route('strategic.programs.show', $program['id']) }}'">
                <h6>{{ $program['name'] ?? '' }}</h6>
                <p class="text-muted small mb-0">{{ Str::limit($program['description'] ?? '', 80) }}</p>
                <small class="text-muted">الميزانية: {{ number_format($program['budget_estimate'] ?? 0) }}</small>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>
@endsection

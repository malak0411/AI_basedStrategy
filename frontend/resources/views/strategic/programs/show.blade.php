@extends('layouts.app')

@section('title', 'تفاصيل البرنامج')

@push('styles')
<style>
    .initiative-card {
        background: #fff; border-radius: 12px; padding: 16px; cursor: pointer;
        border: 1px solid #e2e8f0; transition: all 0.2s; border-right: 4px solid #dd6b20;
    }
    .initiative-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.06); }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <a href="{{ route('strategic.programs.index') }}" class="btn btn-outline-secondary mb-3">
        <i class="fas fa-arrow-right"></i> العودة للبرامج
    </a>

    <div class="card-custom mb-4">
        <h4>{{ $program['name'] ?? $program['title'] ?? '' }}</h4>
        <p class="text-muted">{{ $program['description'] ?? '' }}</p>
        <div class="row mt-3">
            <div class="col-md-3"><small>الهدف</small><div><strong>{{ $program['goal_name'] ?? '' }}</strong></div></div>
            <div class="col-md-3"><small>الميزانية</small><div><strong>{{ number_format($program['budget_estimate'] ?? 0) }}</strong></div></div>
            <div class="col-md-3"><small>تاريخ البداية</small><div>{{ $program['start_date'] ?? '-' }}</div></div>
            <div class="col-md-3"><small>تاريخ النهاية</small><div>{{ $program['end_date'] ?? '-' }}</div></div>
        </div>
    </div>

    <h5 class="mb-3"><i class="fas fa-lightbulb ml-2"></i>المبادرات المرتبطة ({{ count($program['initiatives'] ?? []) }})</h5>

    @if(empty($program['initiatives']))
    <div class="card-custom text-center py-4"><p class="text-muted">لا توجد مبادرات</p></div>
    @else
    <div class="row">
        @foreach($program['initiatives'] as $initiative)
        <div class="col-md-4 mb-3">
            <div class="initiative-card" onclick="window.location='{{ route('strategic.initiatives.show', $initiative['id']) }}'">
                <h6>{{ $initiative['name'] ?? '' }}</h6>
                <p class="text-muted small mb-0">{{ Str::limit($initiative['description'] ?? '', 80) }}</p>
                <small class="text-muted">الأولوية: {{ $initiative['priority_id'] ?? '-' }}</small>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>
@endsection

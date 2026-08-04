@extends('layouts.app')

@section('title', 'تفاصيل الركيزة')

@push('styles')
<style>
    .goal-card {
        background: #fff; border-radius: 12px; padding: 16px; cursor: pointer;
        border: 1px solid #e2e8f0; transition: all 0.2s; border-right: 4px solid #3182ce;
    }
    .goal-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.06); }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <a href="{{ route('strategic.pillars.index') }}" class="btn btn-outline-secondary mb-3">
        <i class="fas fa-arrow-right"></i> العودة للركائز
    </a>

    <div class="card-custom mb-4">
        <h4>{{ $pillar['name'] ?? '' }}</h4>
        <p class="text-muted">{{ $pillar['description'] ?? '' }}</p>
    </div>

    <h5 class="mb-3"><i class="fas fa-bullseye ml-2"></i>الأهداف المرتبطة ({{ count($pillar['goals'] ?? []) }})</h5>

    @if(empty($pillar['goals']))
    <div class="card-custom text-center py-4">
        <p class="text-muted">لا توجد أهداف مرتبطة بهذه الركيزة</p>
    </div>
    @else
    <div class="row">
        @foreach($pillar['goals'] as $goal)
        <div class="col-md-4 mb-3">
            <div class="goal-card" onclick="window.location='{{ route('strategic.goals.show', $goal['id']) }}'">
                <h6>{{ $goal['title'] ?? '' }}</h6>
                <p class="text-muted small mb-0">{{ Str::limit($goal['description'] ?? '', 80) }}</p>
                @if(!empty($goal['target_date']))
                <small class="text-muted">التاريخ المستهدف: {{ $goal['target_date'] }}</small>
                @endif
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>
@endsection

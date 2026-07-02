@extends('layouts.app')

@section('title', 'تفاصيل الهدف')

@section('content')
<div class="container-fluid px-4">
    <a href="{{ route('strategic.goals.index') }}" class="btn btn-outline-secondary mb-3">
        <i class="fas fa-arrow-right"></i> العودة للقائمة
    </a>

    @if(empty($goal))
        <div class="alert alert-info">الهدف غير موجود</div>
    @else
        <div class="card-custom">
            <div class="d-flex justify-content-between align-items-start mb-4">
                <h4>{{ $goal['name'] ?? $goal['title'] ?? '' }}</h4>
                <div>
                    <a href="{{ route('strategic.goals.edit', $goal['id']) }}" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-edit"></i> تعديل
                    </a>
                </div>
            </div>

            <p class="text-muted">{{ $goal['description'] ?? 'لا يوجد وصف' }}</p>

            <div class="row mt-4">
                <div class="col-md-4">
                    <strong>الركيزة:</strong> {{ $goal['pillar_name'] ?? '' }}
                </div>
                <div class="col-md-4">
                    <strong>تاريخ البداية:</strong> {{ $goal['start_date'] ?? 'غير محدد' }}
                </div>
                <div class="col-md-4">
                    <strong>تاريخ النهاية:</strong> {{ $goal['end_date'] ?? 'غير محدد' }}
                </div>
            </div>

            <div class="mt-4">
                <strong>التقدم:</strong>
                <div class="progress mt-2" style="height: 12px;">
                    <div class="progress-bar bg-{{ ($goal['progress'] ?? 0) >= 80 ? 'success' : 'info' }}" 
                         style="width: {{ $goal['progress'] ?? 0 }}%"></div>
                </div>
                <small>{{ $goal['progress'] ?? 0 }}% مكتمل</small>
            </div>
        </div>
    @endif
</div>
@endsection

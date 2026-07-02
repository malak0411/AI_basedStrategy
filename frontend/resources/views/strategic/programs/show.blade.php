@extends('layouts.app')

@section('title', 'تفاصيل البرنامج')

@section('content')
<div class="container-fluid px-4">
    <a href="{{ route('strategic.programs.index') }}" class="btn btn-outline-secondary mb-3">
        <i class="fas fa-arrow-right"></i> العودة للقائمة
    </a>

    @if(empty($program))
        <div class="alert alert-info">البرنامج غير موجود</div>
    @else
        <div class="card-custom">
            <div class="d-flex justify-content-between align-items-start mb-4">
                <h4>{{ $program['name'] ?? $program['title'] ?? '' }}</h4>
                <div>
                    <a href="{{ route('strategic.programs.edit', $program['id']) }}" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-edit"></i> تعديل
                    </a>
                </div>
            </div>

            <p class="text-muted">{{ $program['description'] ?? 'لا يوجد وصف' }}</p>

            <div class="row mt-4">
                <div class="col-md-3">
                    <strong>الهدف:</strong> {{ $program['goal_name'] ?? '' }}
                </div>
                <div class="col-md-3">
                    <strong>الميزانية:</strong> {{ number_format($program['budget'] ?? 0) }}
                </div>
                <div class="col-md-3">
                    <strong>تاريخ البداية:</strong> {{ $program['start_date'] ?? 'غير محدد' }}
                </div>
                <div class="col-md-3">
                    <strong>تاريخ النهاية:</strong> {{ $program['end_date'] ?? 'غير محدد' }}
                </div>
            </div>

            <div class="mt-4">
                <strong>التقدم:</strong>
                <div class="progress mt-2" style="height: 12px;">
                    <div class="progress-bar bg-{{ ($program['progress'] ?? 0) >= 80 ? 'success' : 'info' }}" 
                         style="width: {{ $program['progress'] ?? 0 }}%"></div>
                </div>
                <small>{{ $program['progress'] ?? 0 }}% مكتمل</small>
            </div>
        </div>
    @endif
</div>
@endsection

@extends('layouts.app')

@section('title', 'تفاصيل المبادرة')

@section('content')
<div class="container-fluid px-4">
    <a href="{{ route('strategic.initiatives.index') }}" class="btn btn-outline-secondary mb-3">
        <i class="fas fa-arrow-right"></i> العودة للقائمة
    </a>

    @if(empty($initiative))
        <div class="alert alert-info">المبادرة غير موجودة</div>
    @else
        <div class="card-custom">
            <div class="d-flex justify-content-between align-items-start mb-4">
                <h4>{{ $initiative['name'] ?? $initiative['title'] ?? '' }}</h4>
                <div>
                    <a href="{{ route('strategic.initiatives.edit', $initiative['id']) }}" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-edit"></i> تعديل
                    </a>
                </div>
            </div>

            <p class="text-muted">{{ $initiative['description'] ?? 'لا يوجد وصف' }}</p>

            <div class="row mt-4">
                <div class="col-md-3">
                    <strong>البرنامج:</strong> {{ $initiative['program_name'] ?? '' }}
                </div>
                <div class="col-md-3">
                    <strong>الأولوية:</strong> {{ $initiative['priority'] ?? 'متوسطة' }}
                </div>
                <div class="col-md-3">
                    <strong>تاريخ البداية:</strong> {{ $initiative['start_date'] ?? 'غير محدد' }}
                </div>
                <div class="col-md-3">
                    <strong>تاريخ النهاية:</strong> {{ $initiative['end_date'] ?? 'غير محدد' }}
                </div>
            </div>

            <div class="mt-4">
                <strong>التقدم:</strong>
                <div class="progress mt-2" style="height: 12px;">
                    <div class="progress-bar bg-{{ ($initiative['progress'] ?? 0) >= 80 ? 'success' : 'info' }}" 
                         style="width: {{ $initiative['progress'] ?? 0 }}%"></div>
                </div>
                <small>{{ $initiative['progress'] ?? 0 }}% مكتمل</small>
            </div>
        </div>
    @endif
</div>
@endsection

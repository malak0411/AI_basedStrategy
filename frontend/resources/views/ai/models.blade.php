@extends('layouts.app')

@section('title', 'نماذج الذكاء الاصطناعي')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3><i class="fas fa-microchip ml-2"></i>نماذج الذكاء الاصطناعي</h3>
        <a href="{{ route('ai.dashboard') }}" class="btn btn-outline-secondary">
            <i class="fas fa-chart-bar"></i> لوحة AI
        </a>
    </div>

    @if(empty($models))
        <div class="card-custom text-center py-5">
            <i class="fas fa-microchip fa-3x text-muted mb-3"></i>
            <h5>لا توجد نماذج</h5>
            <p class="text-muted">لم يتم تدريب أي نماذج بعد</p>
        </div>
    @else
        <div class="row">
            @foreach($models as $model)
            <div class="col-md-4 mb-4">
                <div class="card-custom">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h5>{{ $model['name'] ?? 'نموذج' }}</h5>
                            <small class="text-muted">{{ $model['type'] ?? '' }}</small>
                        </div>
                        <span class="badge bg-{{ ($model['status'] ?? '') == 'active' ? 'success' : 'secondary' }}">
                            {{ ($model['status'] ?? '') == 'active' ? 'نشط' : 'غير نشط' }}
                        </span>
                    </div>
                    <p class="text-muted small">{{ Str::limit($model['description'] ?? '', 100) }}</p>
                    <div class="row mt-3">
                        <div class="col-6">
                            <small class="text-muted">الدقة</small>
                            <div class="fw-bold">{{ $model['accuracy'] ?? 0 }}%</div>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">آخر تدريب</small>
                            <div class="fw-bold small">{{ $model['last_trained'] ?? 'غير معروف' }}</div>
                        </div>
                    </div>
                    <a href="{{ route('ai.models.show', $model['id']) }}" class="btn btn-sm btn-outline-info mt-3 w-100">
                        <i class="fas fa-eye"></i> التفاصيل
                    </a>
                </div>
            </div>
            @endforeach
        </div>
    @endif
</div>
@endsection

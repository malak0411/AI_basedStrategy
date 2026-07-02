@extends('layouts.app')

@section('title', 'تفاصيل النموذج')

@section('content')
<div class="container-fluid px-4">
    <a href="{{ route('ai.models') }}" class="btn btn-outline-secondary mb-3">
        <i class="fas fa-arrow-right"></i> العودة
    </a>

    @if(empty($model))
        <div class="alert alert-info">النموذج غير موجود</div>
    @else
        <div class="card-custom">
            <div class="d-flex justify-content-between align-items-start mb-4">
                <h4>{{ $model['name'] ?? '' }}</h4>
                <span class="badge bg-{{ ($model['status'] ?? '') == 'active' ? 'success' : 'secondary' }} fs-6">
                    {{ ($model['status'] ?? '') == 'active' ? 'نشط' : 'غير نشط' }}
                </span>
            </div>

            <p class="text-muted">{{ $model['description'] ?? 'لا يوجد وصف' }}</p>

            <div class="row mt-4">
                <div class="col-md-3">
                    <strong>النوع:</strong> {{ $model['type'] ?? '' }}
                </div>
                <div class="col-md-3">
                    <strong>الدقة:</strong> {{ $model['accuracy'] ?? 0 }}%
                </div>
                <div class="col-md-3">
                    <strong>آخر تدريب:</strong> {{ $model['last_trained'] ?? 'غير معروف' }}
                </div>
                <div class="col-md-3">
                    <strong>الإصدار:</strong> {{ $model['version'] ?? '1.0' }}
                </div>
            </div>

            @if(!empty($model['parameters']))
            <hr>
            <h5>المعلمات</h5>
            <pre class="bg-light p-3 rounded">{{ json_encode($model['parameters'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            @endif
        </div>
    @endif
</div>
@endsection

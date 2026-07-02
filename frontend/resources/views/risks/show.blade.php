@extends('layouts.app')

@section('title', 'تفاصيل الخطر')

@section('content')
<div class="container-fluid px-4">
    <a href="{{ route('risks.index') }}" class="btn btn-outline-secondary mb-3">
        <i class="fas fa-arrow-right"></i> العودة
    </a>

    @if(empty($risk))
        <div class="alert alert-info">الخطر غير موجود</div>
    @else
        <div class="card-custom">
            <div class="d-flex justify-content-between align-items-start mb-4">
                <h4>{{ $risk['name'] ?? $risk['title'] ?? '' }}</h4>
                <div>
                    <a href="{{ route('risks.mitigations', $risk['id']) }}" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-shield-alt"></i> خطط التخفيف
                    </a>
                    <a href="{{ route('risks.edit', $risk['id']) }}" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-edit"></i> تعديل
                    </a>
                </div>
            </div>

            <p class="text-muted">{{ $risk['description'] ?? '' }}</p>

            <div class="row mt-4">
                <div class="col-md-3">
                    <strong>المستوى:</strong>
                    <span class="badge bg-{{ ($risk['level'] ?? '') == 'critical' ? 'danger' : (($risk['level'] ?? '') == 'high' ? 'warning' : 'info') }}">
                        {{ $risk['level'] ?? '' }}
                    </span>
                </div>
                <div class="col-md-3">
                    <strong>الاحتمالية:</strong> {{ $risk['probability'] ?? '' }}
                </div>
                <div class="col-md-3">
                    <strong>التأثير:</strong> {{ $risk['impact'] ?? '' }}
                </div>
                <div class="col-md-3">
                    <strong>الحالة:</strong> {{ $risk['status'] ?? 'نشط' }}
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

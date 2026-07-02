@extends('layouts.app')

@section('title', 'خطط التخفيف')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3><i class="fas fa-shield-alt ml-2"></i>خطط تخفيف: {{ $risk['name'] ?? '' }}</h3>
        <a href="{{ route('risks.mitigations.create', $id) }}" class="btn-gold">
            <i class="fas fa-plus"></i> خطة جديدة
        </a>
    </div>

    <a href="{{ route('risks.show', $id) }}" class="btn btn-outline-secondary mb-3">
        <i class="fas fa-arrow-right"></i> العودة للخطر
    </a>

    @if(empty($mitigations))
        <div class="card-custom text-center py-5">
            <i class="fas fa-shield-alt fa-3x text-muted mb-3"></i>
            <h5>لا توجد خطط تخفيف</h5>
            <a href="{{ route('risks.mitigations.create', $id) }}" class="btn btn-primary mt-3">إضافة خطة</a>
        </div>
    @else
        @foreach($mitigations as $m)
        <div class="card-custom mb-3">
            <h5>{{ $m['name'] ?? $m['title'] ?? 'خطة' }}</h5>
            <p class="text-muted">{{ $m['description'] ?? '' }}</p>
            <span class="badge bg-{{ ($m['status'] ?? '') == 'active' ? 'success' : 'secondary' }}">
                {{ ($m['status'] ?? '') == 'active' ? 'نشط' : 'غير نشط' }}
            </span>
        </div>
        @endforeach
    @endif
</div>
@endsection

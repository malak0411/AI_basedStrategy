@extends('layouts.app')

@section('title', 'تفاصيل الدور')

@section('content')
<div class="container-fluid px-4">
    <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary mb-3">
        <i class="fas fa-arrow-right"></i> العودة
    </a>

    @if(empty($role))
        <div class="alert alert-info">الدور غير موجود</div>
    @else
        <div class="card-custom">
            <div class="d-flex justify-content-between align-items-start mb-4">
                <h4>{{ $role['name'] ?? '' }}</h4>
                <a href="{{ route('admin.roles.edit', $role['id']) }}" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-edit"></i> تعديل
                </a>
            </div>

            <p class="text-muted">{{ $role['description'] ?? 'لا يوجد وصف' }}</p>

            <div class="row mt-3">
                <div class="col-md-6">
                    <strong>نظامي:</strong>
                    <span class="badge bg-{{ ($role['is_system'] ?? false) ? 'warning' : 'info' }}">
                        {{ ($role['is_system'] ?? false) ? 'نعم' : 'لا' }}
                    </span>
                </div>
                <div class="col-md-6">
                    <strong>تاريخ الإنشاء:</strong> {{ $role['created_at'] ?? '' }}
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

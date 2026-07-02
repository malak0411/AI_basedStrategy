@extends('layouts.app')

@section('title', 'تفاصيل الصلاحية')

@section('content')
<div class="container-fluid px-4">
    <a href="{{ route('admin.permissions.index') }}" class="btn btn-outline-secondary mb-3">
        <i class="fas fa-arrow-right"></i> العودة
    </a>

    @if(empty($permission))
        <div class="alert alert-info">الصلاحية غير موجودة</div>
    @else
        <div class="card-custom">
            <h4 class="mb-4">{{ $permission['name'] ?? '' }}</h4>
            <p class="text-muted">{{ $permission['description'] ?? 'لا يوجد وصف' }}</p>
            <small>تاريخ الإنشاء: {{ $permission['created_at'] ?? '' }}</small>
        </div>
    @endif
</div>
@endsection

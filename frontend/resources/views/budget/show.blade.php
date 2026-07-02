@extends('layouts.app')

@section('title', 'تفاصيل بند الميزانية')

@section('content')
<div class="container-fluid px-4">
    <a href="{{ route('budget.index') }}" class="btn btn-outline-secondary mb-3">
        <i class="fas fa-arrow-right"></i> العودة
    </a>

    @if(empty($item))
        <div class="alert alert-info">البند غير موجود</div>
    @else
        <div class="card-custom">
            <div class="d-flex justify-content-between align-items-start mb-4">
                <h4>{{ $item['name'] ?? '' }}</h4>
                <a href="{{ route('budget.edit', $item['id']) }}" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-edit"></i> تعديل
                </a>
            </div>

            <p class="text-muted">{{ $item['description'] ?? 'لا يوجد وصف' }}</p>

            <div class="row mt-4">
                <div class="col-md-4">
                    <strong>المبلغ المخصص:</strong> {{ number_format($item['allocated_amount'] ?? 0) }}
                </div>
                <div class="col-md-4">
                    <strong>المبلغ المستخدم:</strong> {{ number_format($item['used_amount'] ?? 0) }}
                </div>
                <div class="col-md-4">
                    <strong>المتبقي:</strong> {{ number_format(($item['allocated_amount'] ?? 0) - ($item['used_amount'] ?? 0)) }}
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

@extends('layouts.app')

@section('title', 'تفاصيل المعاملة')

@section('content')
<div class="container-fluid px-4">
    <a href="{{ route('budget.transactions') }}" class="btn btn-outline-secondary mb-3">
        <i class="fas fa-arrow-right"></i> العودة
    </a>

    @if(empty($transaction))
        <div class="alert alert-info">المعاملة غير موجودة</div>
    @else
        <div class="card-custom">
            <h4 class="mb-4">تفاصيل المعاملة #{{ $transaction['id'] ?? '' }}</h4>
            <div class="row">
                <div class="col-md-4"><strong>المبلغ:</strong> {{ number_format($transaction['amount'] ?? 0) }}</div>
                <div class="col-md-4"><strong>النوع:</strong> {{ ($transaction['type'] ?? '') == 'expense' ? 'صرف' : 'إيداع' }}</div>
                <div class="col-md-4"><strong>التاريخ:</strong> {{ $transaction['transaction_date'] ?? '' }}</div>
                <div class="col-12 mt-3"><strong>الوصف:</strong> {{ $transaction['description'] ?? '' }}</div>
            </div>
        </div>
    @endif
</div>
@endsection

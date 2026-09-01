@extends('layouts.app')

@section('title', 'إضافة عملية مالية')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3><i class="fas fa-plus ml-2"></i>إضافة عملية مالية</h3>
            <p class="text-muted mb-0">#{{ $budget['budget_id'] ?? '' }} - {{ $budget['budgetable_name'] ?? '' }}</p>
        </div>
        <a href="{{ route('budget.transactions', $budget['budget_id']) }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-right"></i> العودة للعمليات
        </a>
    </div>

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="card-custom">
        <div class="card-body">
            <form action="{{ route('budget.transactions.store', $budget['budget_id']) }}" method="POST">
                @csrf

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">الميزانية</label>
                            <input type="text" class="form-control" value="#{{ $budget['budget_id'] }} - {{ $budget['budgetable_name'] }}" disabled>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">نوع العملية <span class="text-danger">*</span></label>
                            <select name="transaction_type_id" class="form-control @error('transaction_type_id') is-invalid @enderror" required>
                                <option value="">اختر نوع العملية</option>
                                @foreach(($options['transaction_types'] ?? []) as $type)
                                <option value="{{ $type['id'] }}" {{ old('transaction_type_id') == $type['id'] ? 'selected' : '' }}>
                                    {{ $type['name'] }}
                                </option>
                                @endforeach
                            </select>
                            @error('transaction_type_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">المبلغ <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="amount" class="form-control @error('amount') is-invalid @enderror" 
                                value="{{ old('amount') }}" placeholder="0.00" required>
                            @error('amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">تاريخ العملية</label>
                            <input type="date" name="transaction_date" class="form-control @error('transaction_date') is-invalid @enderror" 
                                value="{{ old('transaction_date', date('Y-m-d')) }}">
                            @error('transaction_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">الوصف</label>
                            <textarea name="description" class="form-control @error('description') is-invalid @enderror" 
                                rows="3" placeholder="وصف العملية المالية">{{ old('description') }}</textarea>
                            @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card-custom bg-light">
                            <div class="card-body">
                                <h5 class="mb-3"><i class="fas fa-info-circle text-info"></i> ملخص الميزانية</h5>
                                <div class="row">
                                    <div class="col-6">
                                        <small class="text-muted">المخصص</small>
                                        <h6 class="text-primary">{{ number_format($budget['allocated_amount'] ?? 0, 2) }}</h6>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">المصروف الحالي</small>
                                        <h6 class="text-warning">{{ number_format($budget['spent_amount'] ?? 0, 2) }}</h6>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-6">
                                        <small class="text-muted">المتبقي</small>
                                        <h6 class="{{ ($budget['remaining_amount'] ?? 0) < 0 ? 'text-danger' : 'text-info' }}">
                                            {{ number_format($budget['remaining_amount'] ?? 0, 2) }}
                                        </h6>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">نسبة التنفيذ</small>
                                        <h6 class="{{ ($budget['execution_percentage'] ?? 0) > 100 ? 'text-danger' : 'text-success' }}">
                                            {{ number_format($budget['execution_percentage'] ?? 0, 2) }}%
                                        </h6>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <small class="text-muted">الحالة</small>
                                    <div>
                                        <span class="badge bg-{{ $budget['status'] == 'متجاوزة' ? 'danger' : ($budget['status'] == 'مكتملة' ? 'info' : 'success') }}">
                                            {{ $budget['status'] ?? 'بدون ميزانية' }}
                                        </span>
                                    </div>
                                </div>
                                <hr>
                                <div class="mt-2">
                                    <small class="text-muted">سيتم تحديث المصروف إلى:</small>
                                    <h6 class="text-primary">
                                        {{ number_format(($budget['spent_amount'] ?? 0) + (old('amount', 0)), 2) }}
                                    </h6>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border-top pt-3 mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> تسجيل العملية
                    </button>
                    <a href="{{ route('budget.transactions', $budget['budget_id']) }}" class="btn btn-outline-secondary">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

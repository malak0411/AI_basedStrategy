@extends('layouts.app')

@section('title', 'تعديل الميزانية')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3><i class="fas fa-edit ml-2"></i>تعديل الميزانية</h3>
            <p class="text-muted mb-0">#{{ $budget['budget_id'] ?? '' }} - {{ $budget['budgetable_name'] ?? '' }}</p>
        </div>
        <a href="{{ route('budget.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-right"></i> العودة
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
            <form action="{{ route('budget.update', $budget['budget_id']) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-md-6">
                        <h5 class="mb-3"><i class="fas fa-info-circle text-primary"></i> معلومات الميزانية</h5>

                        <div class="mb-3">
                            <label class="form-label">نوع الميزانية</label>
                            <input type="text" class="form-control" value="{{ $budget['budgetable_type'] ?? '' }}" disabled>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">العنصر المرتبط</label>
                            <input type="text" class="form-control" value="{{ $budget['budgetable_name'] ?? '' }}" disabled>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">القسم</label>
                            <select name="department_id" class="form-control @error('department_id') is-invalid @enderror">
                                <option value="">اختر القسم</option>
                                @foreach(($options['departments'] ?? []) as $dept)
                                <option value="{{ $dept['id'] }}" 
                                    {{ old('department_id', $budget['department']['id'] ?? '') == $dept['id'] ? 'selected' : '' }}>
                                    {{ $dept['name'] }}
                                </option>
                                @endforeach
                            </select>
                            @error('department_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">السنة المالية <span class="text-danger">*</span></label>
                            <select name="fiscal_year" class="form-control @error('fiscal_year') is-invalid @enderror" required>
                                <option value="">اختر السنة المالية</option>
                                @php
                                    $currentYear = date('Y');
                                    $years = range($currentYear - 3, $currentYear + 2);
                                @endphp
                                @foreach($years as $year)
                                <option value="{{ $year }}" {{ old('fiscal_year', $budget['fiscal_year'] ?? $currentYear) == $year ? 'selected' : '' }}>
                                    {{ $year }}
                                </option>
                                @endforeach
                            </select>
                            @error('fiscal_year')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">المبلغ المخصص <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="allocated_amount" class="form-control @error('allocated_amount') is-invalid @enderror" 
                                value="{{ old('allocated_amount', $budget['allocated_amount'] ?? 0) }}" required>
                            @error('allocated_amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">المبلغ المصروف (للقراءة فقط)</label>
                            <input type="text" class="form-control" value="{{ number_format($budget['spent_amount'] ?? 0, 2) }}" disabled>
                            <small class="text-muted">يتم تحديث المصروف عبر العمليات المالية</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">الميزانية الأب</label>
                            <select name="parent_budget_id" class="form-control @error('parent_budget_id') is-invalid @enderror">
                                <option value="">لا يوجد</option>
                                @foreach(($options['parent_budgets'] ?? []) as $parent)
                                <option value="{{ $parent['id'] }}" 
                                    {{ old('parent_budget_id', $budget['parent_budget']['budget_id'] ?? '') == $parent['id'] ? 'selected' : '' }}>
                                    {{ $parent['name'] }}
                                </option>
                                @endforeach
                            </select>
                            @error('parent_budget_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card-custom bg-light">
                            <div class="card-body">
                                <h5 class="mb-3"><i class="fas fa-chart-bar text-success"></i> ملخص الميزانية</h5>
                                <div class="row">
                                    <div class="col-6">
                                        <small class="text-muted">المخصص</small>
                                        <h5 class="text-success">{{ number_format($budget['allocated_amount'] ?? 0, 2) }}</h5>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">المصروف</small>
                                        <h5 class="text-warning">{{ number_format($budget['spent_amount'] ?? 0, 2) }}</h5>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-6">
                                        <small class="text-muted">المتبقي</small>
                                        <h5 class="{{ ($budget['remaining_amount'] ?? 0) < 0 ? 'text-danger' : 'text-info' }}">
                                            {{ number_format($budget['remaining_amount'] ?? 0, 2) }}
                                        </h5>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">نسبة التنفيذ</small>
                                        <h5 class="{{ ($budget['execution_percentage'] ?? 0) > 100 ? 'text-danger' : 'text-success' }}">
                                            {{ number_format($budget['execution_percentage'] ?? 0, 2) }}%
                                        </h5>
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
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border-top pt-3 mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> تحديث الميزانية
                    </button>
                    <a href="{{ route('budget.index') }}" class="btn btn-outline-secondary">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

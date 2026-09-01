@extends('layouts.app')

@section('title', 'تفاصيل الميزانية')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3><i class="fas fa-coins ml-2"></i>تفاصيل الميزانية</h3>
            <p class="text-muted mb-0">#{{ $budget['budget_id'] ?? '' }} - {{ $budget['budgetable_name'] ?? '' }}</p>
        </div>
        <div>
            <a href="{{ route('budget.edit', $budget['budget_id']) }}" class="btn btn-outline-primary">
                <i class="fas fa-edit"></i> تعديل
            </a>
            <a href="{{ route('budget.transactions.create', $budget['budget_id']) }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> إضافة عملية
            </a>
            <a href="{{ route('budget.transactions', $budget['budget_id']) }}" class="btn btn-outline-secondary">
                <i class="fas fa-list"></i> العمليات
            </a>
            <a href="{{ route('budget.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-right"></i> العودة
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card-custom text-center">
                <div class="card-body">
                    <h6 class="text-muted">نوع الميزانية</h6>
                    <h5>
                        @php
                            $typeLabels = [
                                'program' => 'برنامج',
                                'initiative' => 'مبادرة',
                                'major_task' => 'مهمة رئيسية',
                                'operational_task' => 'مهمة تشغيلية'
                            ];
                            $typeLabel = $typeLabels[$budget['budgetable_type'] ?? ''] ?? $budget['budgetable_type'] ?? '';
                        @endphp
                        {{ $typeLabel }}
                    </h5>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-custom text-center">
                <div class="card-body">
                    <h6 class="text-muted">العنصر المرتبط</h6>
                    <h5>{{ $budget['budgetable_name'] ?? 'غير معروف' }}</h5>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-custom text-center">
                <div class="card-body">
                    <h6 class="text-muted">القسم</h6>
                    <h5>{{ $budget['department']['name'] ?? 'غير محدد' }}</h5>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-custom text-center">
                <div class="card-body">
                    <h6 class="text-muted">السنة المالية</h6>
                    <h5>{{ $budget['fiscal_year'] ?? '' }}</h5>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card-custom text-center border-primary">
                <div class="card-body">
                    <h6 class="text-muted">المبلغ المخصص</h6>
                    <h2 class="text-primary">{{ number_format($budget['allocated_amount'] ?? 0, 2) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-custom text-center border-warning">
                <div class="card-body">
                    <h6 class="text-muted">المبلغ المصروف</h6>
                    <h2 class="text-warning">{{ number_format($budget['spent_amount'] ?? 0, 2) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-custom text-center {{ ($budget['remaining_amount'] ?? 0) < 0 ? 'border-danger' : 'border-info' }}">
                <div class="card-body">
                    <h6 class="text-muted">المتبقي</h6>
                    <h2 class="{{ ($budget['remaining_amount'] ?? 0) < 0 ? 'text-danger' : 'text-info' }}">
                        {{ number_format($budget['remaining_amount'] ?? 0, 2) }}
                    </h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-custom text-center {{ ($budget['execution_percentage'] ?? 0) > 100 ? 'border-danger' : 'border-success' }}">
                <div class="card-body">
                    <h6 class="text-muted">نسبة التنفيذ</h6>
                    <h2 class="{{ ($budget['execution_percentage'] ?? 0) > 100 ? 'text-danger' : 'text-success' }}">
                        {{ number_format($budget['execution_percentage'] ?? 0, 2) }}%
                    </h2>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card-custom">
                <div class="card-body">
                    <h6 class="mb-2">تقدم الميزانية</h6>
                    @php
                        $execution = $budget['execution_percentage'] ?? 0;
                        $progressClass = $execution > 100 ? 'bg-danger' : ($execution >= 80 ? 'bg-success' : ($execution >= 50 ? 'bg-warning' : 'bg-info'));
                    @endphp
                    <div class="progress" style="height:30px;">
                        <div class="progress-bar {{ $progressClass }}" 
                             role="progressbar" 
                             style="width: {{ min($execution, 100) }}%;" 
                             aria-valuenow="{{ $execution }}" 
                             aria-valuemin="0" 
                             aria-valuemax="100">
                            {{ number_format($execution, 2) }}%
                        </div>
                    </div>
                    <div class="d-flex justify-content-between mt-2">
                        <span class="text-muted">0%</span>
                        <span class="text-muted">100%</span>
                    </div>
                    @if($execution > 100)
                    <small class="text-danger d-block mt-2">
                        <i class="fas fa-exclamation-triangle"></i> تم تجاوز الميزانية بنسبة {{ number_format($execution - 100, 2) }}%
                    </small>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card-custom">
                <div class="card-body">
                    <h6 class="mb-2">ملخص الميزانية</h6>
                    <div class="row">
                        <div class="col-6">
                            <small class="text-muted">المخصص</small>
                            <div class="fw-bold text-primary">{{ number_format($budget['allocated_amount'] ?? 0, 2) }}</div>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">المصروف</small>
                            <div class="fw-bold text-warning">{{ number_format($budget['spent_amount'] ?? 0, 2) }}</div>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-6">
                            <small class="text-muted">المتبقي</small>
                            <div class="fw-bold {{ ($budget['remaining_amount'] ?? 0) < 0 ? 'text-danger' : 'text-info' }}">
                                {{ number_format($budget['remaining_amount'] ?? 0, 2) }}
                            </div>
                        </div>
                        <div class="col-6">
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
    </div>

    <div class="card-custom">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5><i class="fas fa-receipt me-2"></i>آخر العمليات المالية</h5>
                <a href="{{ route('budget.transactions', $budget['budget_id']) }}" class="btn btn-sm btn-outline-primary">
                    عرض جميع العمليات <i class="fas fa-arrow-left"></i>
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>التاريخ</th>
                            <th>نوع العملية</th>
                            <th>المبلغ</th>
                            <th>الوصف</th>
                            <th>الحالة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(array_slice($budget['transactions'] ?? [], 0, 5) as $transaction)
                        <tr>
                            <td>{{ isset($transaction['transaction_date']) ? \Carbon\Carbon::parse($transaction['transaction_date'])->format('Y-m-d H:i') : '' }}</td>
                            <td>{{ $transaction['transaction_type']['name'] ?? '' }}</td>
                            <td class="fw-bold">{{ number_format($transaction['amount'] ?? 0, 2) }}</td>
                            <td>{{ $transaction['description'] ?? '' }}</td>
                            <td>
                                <span class="badge bg-{{ $transaction['approved_by'] ? 'success' : 'warning' }}">
                                    {{ $transaction['approved_by'] ? 'معتمدة' : 'قيد المراجعة' }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-3">لا توجد عمليات مالية</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

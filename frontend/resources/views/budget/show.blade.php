@extends('layouts.app')


@section('title', 'تفاصيل الميزانية')


@section('content')
<div class="container py-4" dir="rtl">
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-navy text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-file-invoice ml-2"></i> كشف مالي: {{ $budget['budgetable_name'] }}</h5>
            <a href="{{ route('budget.index') }}" class="btn btn-sm btn-light">عودة للمنظومة</a>
        </div>
        <div class="card-body">
            <div class="row mb-4 text-center">
                <div class="col-md-3 border-left">
                    <h6 class="text-muted">التصنيف الهيكلي</h6>
                    <p class="fw-bold">{{ $budget['hierarchy'] }}</p>
                </div>
                <div class="col-md-3 border-left">
                    <h6 class="text-muted">المبلغ المخصص</h6>
                    <h4 class="text-primary fw-bold">{{ number_format($budget['allocated_amount'], 2) }}</h4>
                </div>
                <div class="col-md-3 border-left">
                    <h6 class="text-muted">المبلغ المصروف</h6>
                    <h4 class="text-danger fw-bold">{{ number_format($budget['spent_amount'], 2) }}</h4>
                </div>
                <div class="col-md-3">
                    <h6 class="text-muted">الرصيد المتبقي</h6>
                    <h4 class="text-success fw-bold">{{ number_format($budget['remaining_amount'], 2) }}</h4>
                </div>
            </div>


            @if($budget['parent_warning'])
            <div class="alert alert-warning"><i class="fas fa-exclamation-triangle ml-2"></i> {{ $budget['parent_warning'] }}</div>
            @endif


            <h6 class="fw-bold mt-4 border-bottom pb-2">سجل العمليات المالية المرتبطة</h6>
            <table class="table table-hover text-center mt-3">
                <thead class="table-light">
                    <tr>
                        <th>الرقم</th>
                        <th>التاريخ</th>
                        <th>النوع</th>
                        <th>المبلغ</th>
                        <th>الوصف</th>
                        <th>المنشئ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $t)
                    <tr>
                        <td>#{{ $t['transaction_id'] }}</td>
                        <td>{{ \Carbon\Carbon::parse($t['transaction_date'])->format('Y-m-d') }}</td>
                        <td><span class="badge bg-info text-dark">{{ $t['transaction_type'] }}</span></td>
                        <td class="fw-bold">{{ number_format($t['amount'], 2) }}</td>
                        <td>{{ $t['description'] }}</td>
                        <td>{{ $t['created_by'] }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-muted py-4">لا توجد عمليات مسجلة.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

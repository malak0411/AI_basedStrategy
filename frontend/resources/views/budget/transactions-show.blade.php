@extends('layouts.app')

@section('title', 'تفاصيل العملية المالية')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3><i class="fas fa-receipt ml-2"></i>تفاصيل العملية المالية</h3>
            <p class="text-muted mb-0">#{{ $transaction['transaction_id'] ?? '' }}</p>
        </div>
        <div>
            <a href="{{ route('budget.transactions', $transaction['budget_id']) }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-right"></i> العودة للعمليات
            </a>
        </div>
    </div>

    <div class="card-custom">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <td class="fw-bold">رقم العملية</td>
                            <td>#{{ $transaction['transaction_id'] ?? '' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">الميزانية</td>
                            <td>#{{ $transaction['budget_id'] ?? '' }} - {{ $transaction['budgetable_name'] ?? '' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">نوع العملية</td>
                            <td>{{ $transaction['transaction_type']['name'] ?? '' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">المبلغ</td>
                            <td class="fw-bold text-primary">{{ number_format($transaction['amount'] ?? 0, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">التاريخ</td>
                            <td>{{ isset($transaction['transaction_date']) ? \Carbon\Carbon::parse($transaction['transaction_date'])->format('Y-m-d H:i') : '' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">الوصف</td>
                            <td>{{ $transaction['description'] ?? '' }}</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <td class="fw-bold">منشئ العملية</td>
                            <td>{{ $transaction['created_by']['name'] ?? '' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">المعتمد</td>
                            <td>{{ $transaction['approved_by']['name'] ?? 'قيد المراجعة' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">تاريخ الاعتماد</td>
                            <td>{{ isset($transaction['approved_at']) ? \Carbon\Carbon::parse($transaction['approved_at'])->format('Y-m-d H:i') : 'غير معتمد' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">الحالة</td>
                            <td>
                                <span class="badge bg-{{ $transaction['approved_by'] ? 'success' : 'warning' }}">
                                    {{ $transaction['approved_by'] ? 'معتمدة' : 'قيد المراجعة' }}
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

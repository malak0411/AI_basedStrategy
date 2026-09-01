@extends('layouts.app')

@section('title', 'العمليات المالية')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3><i class="fas fa-receipt ml-2"></i>العمليات المالية</h3>
            <p class="text-muted mb-0">#{{ $budget['budget_id'] ?? '' }} - {{ $budget['budgetable_name'] ?? '' }}</p>
        </div>
        <div>
            <a href="{{ route('budget.transactions.create', $budget['budget_id']) }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> إضافة عملية
            </a>
            <a href="{{ route('budget.show', $budget['budget_id']) }}" class="btn btn-outline-secondary">
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

    <div class="card-custom">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>التاريخ</th>
                            <th>نوع العملية</th>
                            <th>المبلغ</th>
                            <th>الوصف</th>
                            <th>منشئ العملية</th>
                            <th>المعتمد</th>
                            <th>الحالة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $transaction)
                        <tr>
                            <td>{{ $transaction['transaction_id'] ?? '' }}</td>
                            <td>{{ isset($transaction['transaction_date']) ? \Carbon\Carbon::parse($transaction['transaction_date'])->format('Y-m-d H:i') : '' }}</td>
                            <td>{{ $transaction['transaction_type']['name'] ?? '' }}</td>
                            <td class="fw-bold">{{ number_format($transaction['amount'] ?? 0, 2) }}</td>
                            <td>{{ $transaction['description'] ?? '' }}</td>
                            <td>{{ $transaction['created_by']['name'] ?? '' }}</td>
                            <td>{{ $transaction['approved_by']['name'] ?? 'قيد المراجعة' }}</td>
                            <td>
                                <span class="badge bg-{{ $transaction['approved_by'] ? 'success' : 'warning' }}">
                                    {{ $transaction['approved_by'] ? 'معتمدة' : 'قيد المراجعة' }}
                                </span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="{{ route('budget.transactions.show', $transaction['transaction_id']) }}" class="btn btn-sm btn-outline-info" title="عرض التفاصيل">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @if(!$transaction['approved_by'])
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteTransaction({{ $transaction['transaction_id'] }})" title="حذف">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-4">
                                <i class="fas fa-receipt fa-2x text-muted d-block mb-2"></i>
                                <p class="text-muted">لا توجد عمليات مالية</p>
                                <a href="{{ route('budget.transactions.create', $budget['budget_id']) }}" class="btn btn-primary btn-sm">إضافة عملية</a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if(isset($pagination['total_pages']) && $pagination['total_pages'] > 1)
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div>
                    عرض {{ ($pagination['page'] - 1) * $pagination['per_page'] + 1 }} - {{ min($pagination['page'] * $pagination['per_page'], $pagination['total']) }} من {{ $pagination['total'] }}
                </div>
                <nav>
                    <ul class="pagination">
                        @for($i = 1; $i <= $pagination['total_pages']; $i++)
                        <li class="page-item {{ $i == $pagination['page'] ? 'active' : '' }}">
                            <a class="page-link" href="?page={{ $i }}&per_page={{ $pagination['per_page'] }}">{{ $i }}</a>
                        </li>
                        @endfor
                    </ul>
                </nav>
            </div>
            @endif
        </div>
    </div>
</div>

<form id="deleteForm" method="POST" style="display:none;">
    @csrf
    @method('DELETE')
</form>
@endsection

@push('scripts')
<script>
    function deleteTransaction(id) {
        if (confirm('هل أنت متأكد من حذف هذه العملية المالية؟')) {
            var form = document.getElementById('deleteForm');
            form.action = '/budget/transactions/' + id;
            form.submit();
        }
    }
</script>
@endpush

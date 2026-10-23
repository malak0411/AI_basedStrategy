@extends('layouts.app')


@section('title', 'العمليات المالية')


@section('content')
<div class="container py-4" dir="rtl">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold">سجل العمليات: {{ $budget['budgetable_name'] }}</h4>
        <div>
            @php
                $isEditable = false;
                if ($budget['budgetable_type'] == 'program' && $budget['status_id'] == 47) $isEditable = true;
                if ($budget['budgetable_type'] == 'initiative' && $budget['status_id'] == 2) $isEditable = true;
                if ($budget['budgetable_type'] == 'operational_task' && $budget['status_id'] == 6) $isEditable = true;
            @endphp


            @if($isEditable)
            <a href="{{ route('budget.transactions.create', $budget['budget_id']) }}" class="btn btn-success"><i class="fas fa-plus"></i> تسجيل عملية جديدة</a>
            @endif
            <a href="{{ route('budget.index') }}" class="btn btn-secondary">عودة</a>
        </div>
    </div>


    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="row mb-4 text-center bg-light p-3 rounded">
                <div class="col-md-4">
                    <span class="text-muted d-block">المخصص</span>
                    <strong class="text-primary fs-5">{{ number_format($budget['allocated_amount'], 2) }}</strong>
                </div>
                <div class="col-md-4 border-start border-end">
                    <span class="text-muted d-block">المصروف</span>
                    <strong class="text-danger fs-5">{{ number_format($budget['spent_amount'], 2) }}</strong>
                </div>
                <div class="col-md-4">
                    <span class="text-muted d-block">المتبقي</span>
                    <strong class="text-success fs-5">{{ number_format($budget['remaining_amount'], 2) }}</strong>
                </div>
            </div>


            <table class="table table-bordered table-striped text-center align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>الرقم</th>
                        <th>التاريخ</th>
                        <th>النوع</th>
                        <th>المبلغ</th>
                        <th>الوصف</th>
                        <th>بواسطة</th>
                        <th>الاعتماد</th>
                        <th>إجراء</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $t)
                    <tr>
                        <td>#{{ $t['transaction_id'] }}</td>
                        <td>{{ \Carbon\Carbon::parse($t['transaction_date'])->format('Y-m-d') }}</td>
                        <td>
                            @if($t['type_id'] == 1) <span class="badge bg-primary">تخصيص</span>
                            @elseif($t['type_id'] == 2) <span class="badge bg-danger">صرف</span>
                            @elseif($t['type_id'] == 3) <span class="badge bg-success">استرداد</span>
                            @else <span class="badge bg-secondary">تعديل</span>
                            @endif
                        </td>
                        <td class="fw-bold">{{ number_format($t['amount'], 2) }}</td>
                        <td>{{ $t['description'] }}</td>
                        <td>{{ $t['created_by'] }}</td>
                        <td>{{ $t['approved_by'] }}</td>
                        <td>
                            <a href="{{ route('budget.transactions.show', $t['transaction_id']) }}" class="btn btn-sm btn-outline-info"><i class="fas fa-eye"></i></a>
                            @if($isEditable && $t['approved_by'] == 'معلق')
                            <button onclick="deleteTransaction({{ $t['transaction_id'] }})" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-muted py-4">لا توجد عمليات مالية مسجلة.</td></tr>
                    @endforelse
                </tbody>
            </table>
            

            @if(isset($pagination['total_pages']) && $pagination['total_pages'] > 1)
            <nav>
                <ul class="pagination justify-content-center">
                    @for($i = 1; $i <= $pagination['total_pages']; $i++)
                    <li class="page-item {{ $i == $pagination['page'] ? 'active' : '' }}">
                        <a class="page-link" href="?page={{ $i }}">{{ $i }}</a>
                    </li>
                    @endfor
                </ul>
            </nav>
            @endif
        </div>
    </div>
</div>
@endsection


@push('scripts')
<script>
function deleteTransaction(id) {
    if(!confirm('هل أنت متأكد من التراجع عن هذه العملية؟ سيتم تعديل الأرصدة المالية تلقائياً.')) return;
    
    fetch('/budget/transactions/' + id, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            location.reload();
        } else {
            alert(data.error || 'حدث خطأ');
        }
    });
}
</script>
@endpush

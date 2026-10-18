@extends('layouts.app')


@section('title', 'تسجيل عملية مالية')


@section('content')
<div class="container py-4" dir="rtl">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow border-0">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-money-bill-wave ml-2"></i> تسجيل حركة مالية</h5>
                </div>
                <div class="card-body p-4">
                    <div class="alert alert-secondary d-flex justify-content-between">
                        <div>
                            <strong>الميزانية:</strong> {{ $budget['budgetable_name'] }}<br>
                            <small class="text-muted">{{ $budget['hierarchy'] }}</small>
                        </div>
                        <div class="text-start">
                            <small class="d-block text-muted">المتبقي:</small>
                            <strong class="text-success fs-5">{{ number_format($budget['remaining_amount'], 2) }}</strong>
                        </div>
                    </div>


                    <form action="{{ route('budget.transactions.store', $budget['budget_id']) }}" method="POST">
                        @csrf
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">نوع الحركة</label>
                                <select name="transaction_type_id" id="transType" class="form-select" required onchange="updateWarning()">
                                    <option value="">-- اختر --</option>
                                    @foreach($options['transaction_types'] ?? [] as $type)
                                    <option value="{{ $type['id'] }}">{{ $type['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">المبلغ (ريال)</label>
                                <input type="number" step="0.01" name="amount" id="transAmount" class="form-control" required min="0.01" oninput="updateWarning()">
                                <small id="overspendWarning" class="text-danger fw-bold d-none mt-1"><i class="fas fa-exclamation-triangle"></i> المبلغ يتجاوز الرصيد المتبقي!</small>
                            </div>
                        </div>


                        <div class="mb-3">
                            <label class="form-label fw-bold">تاريخ الحركة</label>
                            <input type="date" name="transaction_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>


                        <div class="mb-4">
                            <label class="form-label fw-bold">الوصف / البيان</label>
                            <textarea name="description" class="form-control" rows="3" required placeholder="مثال: صرف مستحقات مورد..."></textarea>
                        </div>


                        <div class="d-flex justify-content-end gap-2 border-top pt-3">
                            <a href="{{ route('budget.transactions', $budget['budget_id']) }}" class="btn btn-light border">إلغاء</a>
                            <button type="submit" class="btn btn-success px-4">تأكيد التسجيل</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection


@push('scripts')
<script>
    const remaining = {{ $budget['remaining_amount'] }};
    
    function updateWarning() {
        const type = document.getElementById('transType').value;
        const amount = parseFloat(document.getElementById('transAmount').value) || 0;
        const warning = document.getElementById('overspendWarning');
        
        // التحذير فقط في حالة الصرف (2) إذا كان يتجاوز المتبقي
        if (type == '2' && amount > remaining) {
            warning.classList.remove('d-none');
        } else {
            warning.classList.add('d-none');
        }
    }
</script>
@endpush

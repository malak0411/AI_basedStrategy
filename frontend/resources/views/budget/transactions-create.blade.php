@extends('layouts.app')

@section('title', 'تسجيل معاملة جديدة')

@section('content')
<div class="container-fluid px-4">
    <a href="{{ route('budget.transactions') }}" class="btn btn-outline-secondary mb-3">
        <i class="fas fa-arrow-right"></i> العودة
    </a>

    <div class="card-custom">
        <h4 class="mb-4"><i class="fas fa-plus-circle ml-2"></i>تسجيل معاملة جديدة</h4>

        <form method="POST" action="{{ route('budget.transactions.store') }}">
            @csrf
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">المبلغ</label>
                    <input type="number" name="amount" class="form-control" step="0.01" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">النوع</label>
                    <select name="type" class="form-control" required>
                        <option value="expense">صرف</option>
                        <option value="deposit">إيداع</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">التاريخ</label>
                    <input type="date" name="transaction_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>
                <div class="col-12 mb-3">
                    <label class="form-label">الوصف</label>
                    <textarea name="description" class="form-control" rows="3"></textarea>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn-gold">تسجيل المعاملة</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@extends('layouts.app')

@section('title', 'تسجيل قياس جديد')

@section('content')
<div class="container-fluid px-4">
    <a href="{{ route('kpis.measurements', $id) }}" class="btn btn-outline-secondary mb-3">
        <i class="fas fa-arrow-right"></i> العودة
    </a>

    <div class="card-custom">
        <h4 class="mb-4"><i class="fas fa-plus-circle ml-2"></i>تسجيل قياس جديد</h4>

        <form method="POST" action="{{ route('kpis.measurements.store', $id) }}">
            @csrf
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">القيمة</label>
                    <input type="number" name="value" class="form-control" step="0.01" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">تاريخ القياس</label>
                    <input type="date" name="measurement_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>
                <div class="col-12 mb-3">
                    <label class="form-label">ملاحظات</label>
                    <textarea name="notes" class="form-control" rows="3"></textarea>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn-gold">تسجيل القياس</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@extends('layouts.app')

@section('title', 'إنشاء مؤشر أداء')

@section('content')
<div class="container-fluid px-4">
    <a href="{{ route('kpis.index') }}" class="btn btn-outline-secondary mb-3">
        <i class="fas fa-arrow-right"></i> العودة
    </a>

    <div class="card-custom">
        <h4 class="mb-4"><i class="fas fa-plus-circle ml-2"></i>إنشاء مؤشر أداء جديد</h4>

        <form method="POST" action="{{ route('kpis.store') }}">
            @csrf
            <div class="row">
                <div class="col-md-8 mb-3">
                    <label class="form-label">اسم المؤشر</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">نوع المؤشر</label>
                    <select name="type" class="form-control">
                        <option value="percentage">نسبة مئوية</option>
                        <option value="number">رقم</option>
                        <option value="currency">عملة</option>
                    </select>
                </div>
                <div class="col-12 mb-3">
                    <label class="form-label">الوصف</label>
                    <textarea name="description" class="form-control" rows="3"></textarea>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">القيمة المستهدفة</label>
                    <input type="number" name="target_value" class="form-control" step="0.01" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">القيمة الحالية</label>
                    <input type="number" name="current_value" class="form-control" step="0.01" value="0">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">وحدة القياس</label>
                    <input type="text" name="unit" class="form-control" placeholder="%">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn-gold">إنشاء المؤشر</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

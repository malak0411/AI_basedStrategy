@extends('layouts.app')

@section('title', 'تعديل المؤشر')

@section('content')
<div class="container-fluid px-4">
    <a href="{{ route('kpis.show', $kpi['id']) }}" class="btn btn-outline-secondary mb-3">
        <i class="fas fa-arrow-right"></i> العودة
    </a>

    <div class="card-custom">
        <h4 class="mb-4"><i class="fas fa-edit ml-2"></i>تعديل المؤشر</h4>

        <form method="POST" action="{{ route('kpis.update', $kpi['id']) }}">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col-md-8 mb-3">
                    <label class="form-label">اسم المؤشر</label>
                    <input type="text" name="name" class="form-control" value="{{ $kpi['name'] ?? '' }}" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">نوع المؤشر</label>
                    <select name="type" class="form-control">
                        <option value="percentage" {{ ($kpi['type'] ?? '') == 'percentage' ? 'selected' : '' }}>نسبة مئوية</option>
                        <option value="number" {{ ($kpi['type'] ?? '') == 'number' ? 'selected' : '' }}>رقم</option>
                        <option value="currency" {{ ($kpi['type'] ?? '') == 'currency' ? 'selected' : '' }}>عملة</option>
                    </select>
                </div>
                <div class="col-12 mb-3">
                    <label class="form-label">الوصف</label>
                    <textarea name="description" class="form-control" rows="3">{{ $kpi['description'] ?? '' }}</textarea>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">القيمة المستهدفة</label>
                    <input type="number" name="target_value" class="form-control" step="0.01" value="{{ $kpi['target_value'] ?? '' }}" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">القيمة الحالية</label>
                    <input type="number" name="current_value" class="form-control" step="0.01" value="{{ $kpi['current_value'] ?? '' }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">وحدة القياس</label>
                    <input type="text" name="unit" class="form-control" value="{{ $kpi['unit'] ?? '%' }}">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn-gold">حفظ التعديلات</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

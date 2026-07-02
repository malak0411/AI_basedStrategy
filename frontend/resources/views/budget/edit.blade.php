@extends('layouts.app')

@section('title', 'تعديل بند الميزانية')

@section('content')
<div class="container-fluid px-4">
    <a href="{{ route('budget.show', $item['id']) }}" class="btn btn-outline-secondary mb-3">
        <i class="fas fa-arrow-right"></i> العودة
    </a>

    <div class="card-custom">
        <h4 class="mb-4"><i class="fas fa-edit ml-2"></i>تعديل بند الميزانية</h4>

        <form method="POST" action="{{ route('budget.update', $item['id']) }}">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col-md-8 mb-3">
                    <label class="form-label">اسم البند</label>
                    <input type="text" name="name" class="form-control" value="{{ $item['name'] ?? '' }}" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">المبلغ المخصص</label>
                    <input type="number" name="allocated_amount" class="form-control" step="0.01" value="{{ $item['allocated_amount'] ?? '' }}" required>
                </div>
                <div class="col-12 mb-3">
                    <label class="form-label">الوصف</label>
                    <textarea name="description" class="form-control" rows="3">{{ $item['description'] ?? '' }}</textarea>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn-gold">حفظ التعديلات</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

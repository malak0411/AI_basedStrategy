@extends('layouts.app')

@section('title', 'تعديل الخطر')

@section('content')
<div class="container-fluid px-4">
    <a href="{{ route('risks.show', $risk['id']) }}" class="btn btn-outline-secondary mb-3">
        <i class="fas fa-arrow-right"></i> العودة
    </a>

    <div class="card-custom">
        <h4 class="mb-4"><i class="fas fa-edit ml-2"></i>تعديل الخطر</h4>

        <form method="POST" action="{{ route('risks.update', $risk['id']) }}">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col-md-8 mb-3">
                    <label class="form-label">اسم الخطر</label>
                    <input type="text" name="name" class="form-control" value="{{ $risk['name'] ?? '' }}" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">المستوى</label>
                    <select name="level" class="form-control" required>
                        <option value="low" {{ ($risk['level'] ?? '') == 'low' ? 'selected' : '' }}>منخفض</option>
                        <option value="medium" {{ ($risk['level'] ?? '') == 'medium' ? 'selected' : '' }}>متوسط</option>
                        <option value="high" {{ ($risk['level'] ?? '') == 'high' ? 'selected' : '' }}>عالي</option>
                        <option value="critical" {{ ($risk['level'] ?? '') == 'critical' ? 'selected' : '' }}>حرج</option>
                    </select>
                </div>
                <div class="col-12 mb-3">
                    <label class="form-label">الوصف</label>
                    <textarea name="description" class="form-control" rows="4" required>{{ $risk['description'] ?? '' }}</textarea>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn-gold">حفظ التعديلات</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@extends('layouts.app')

@section('title', 'تسجيل خطر جديد')

@section('content')
<div class="container-fluid px-4">
    <a href="{{ route('risks.index') }}" class="btn btn-outline-secondary mb-3">
        <i class="fas fa-arrow-right"></i> العودة
    </a>

    <div class="card-custom">
        <h4 class="mb-4"><i class="fas fa-plus-circle ml-2"></i>تسجيل خطر جديد</h4>

        <form method="POST" action="{{ route('risks.store') }}">
            @csrf
            <div class="row">
                <div class="col-md-8 mb-3">
                    <label class="form-label">اسم الخطر</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">المستوى</label>
                    <select name="level" class="form-control" required>
                        <option value="low">منخفض</option>
                        <option value="medium" selected>متوسط</option>
                        <option value="high">عالي</option>
                        <option value="critical">حرج</option>
                    </select>
                </div>
                <div class="col-12 mb-3">
                    <label class="form-label">الوصف</label>
                    <textarea name="description" class="form-control" rows="4" required></textarea>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">الاحتمالية</label>
                    <select name="probability" class="form-control">
                        <option value="low">منخفضة</option>
                        <option value="medium">متوسطة</option>
                        <option value="high">عالية</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">التأثير</label>
                    <select name="impact" class="form-control">
                        <option value="low">منخفض</option>
                        <option value="medium">متوسط</option>
                        <option value="high">عالي</option>
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn-gold">تسجيل الخطر</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

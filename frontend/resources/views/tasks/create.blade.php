@extends('layouts.app')

@section('title', 'إنشاء مهمة جديدة')

@section('content')
<div class="container-fluid px-4">
    <a href="{{ route('tasks.index') }}" class="btn btn-outline-secondary mb-3">
        <i class="fas fa-arrow-right"></i> العودة للقائمة
    </a>

    <div class="card-custom">
        <h4 class="mb-4"><i class="fas fa-plus-circle ml-2"></i>إنشاء مهمة جديدة</h4>

        <form method="POST" action="{{ route('tasks.store') }}">
            @csrf
            <div class="row">
                <div class="col-md-8 mb-3">
                    <label class="form-label">اسم المهمة</label>
                    <input type="text" name="task_name" class="form-control" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">تاريخ التسليم</label>
                    <input type="date" name="due_date" class="form-control" required>
                </div>
                <div class="col-12 mb-3">
                    <label class="form-label">الوصف</label>
                    <textarea name="description" class="form-control" rows="4"></textarea>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">الأولوية</label>
                    <select name="priority" class="form-control">
                        <option value="low">منخفضة</option>
                        <option value="medium" selected>متوسطة</option>
                        <option value="high">عالية</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">تعيين إلى</label>
                    <select name="assigned_to" class="form-control">
                        <option value="">اختر موظفاً</option>
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn-gold">إنشاء المهمة</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

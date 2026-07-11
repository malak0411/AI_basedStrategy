@extends('layouts.app')

@section('title', 'تعديل المهمة')

@section('content')
<div class="container-fluid px-4">
    <a href="{{ route('tasks.show', $task['id'] ?? 0) }}" class="btn btn-outline-secondary mb-3">
        <i class="fas fa-arrow-right"></i> العودة للتفاصيل
    </a>

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card-custom">
        <h4 class="mb-4"><i class="fas fa-edit ml-2"></i>تعديل المهمة</h4>

        <form method="POST" action="{{ route('tasks.update', $task['id'] ?? 0) }}">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col-md-8 mb-3">
                    <label class="form-label">اسم المهمة</label>
                    <input type="text" name="title" class="form-control" value="{{ $task['task_name'] ?? $task['title'] ?? '' }}" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">الحالة</label>
                    <select name="status_id" class="form-control">
                        <option value="5" {{ ($task['status'] ?? '') == 5 ? 'selected' : '' }}>معلق</option>
                        <option value="6" {{ ($task['status'] ?? '') == 6 ? 'selected' : '' }}>جاري العمل</option>
                        <option value="8" {{ ($task['status'] ?? '') == 8 ? 'selected' : '' }}>مكتمل</option>
                        <option value="7" {{ ($task['status'] ?? '') == 7 ? 'selected' : '' }}>متأخر</option>
                    </select>
                </div>
                <div class="col-12 mb-3">
                    <label class="form-label">الوصف</label>
                    <textarea name="description" class="form-control" rows="4">{{ $task['description'] ?? '' }}</textarea>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">الأولوية</label>
                    <select name="priority_id" class="form-control">
                        <option value="1" {{ ($task['priority'] ?? '') == 1 ? 'selected' : '' }}>منخفضة</option>
                        <option value="2" {{ ($task['priority'] ?? '') == 2 ? 'selected' : '' }}>متوسطة</option>
                        <option value="3" {{ ($task['priority'] ?? '') == 3 ? 'selected' : '' }}>عالية</option>
                        <option value="4" {{ ($task['priority'] ?? '') == 4 ? 'selected' : '' }}>حرجة</option>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">الإدارة</label>
                    <select name="department_id" class="form-control">
                        <option value="">اختر الإدارة</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept['department_id'] ?? '' }}" {{ ($task['department_id'] ?? '') == ($dept['department_id'] ?? '') ? 'selected' : '' }}>
                                {{ $dept['name'] ?? '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">الساعات المقدرة</label>
                    <input type="number" name="estimated_hours" class="form-control" value="{{ $task['estimated_hours'] ?? 0 }}" step="0.5">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">الساعات الفعلية</label>
                    <input type="number" name="actual_hours" class="form-control" value="{{ $task['actual_hours'] ?? 0 }}" step="0.5">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">تاريخ البداية</label>
                    <input type="date" name="start_date" class="form-control" value="{{ $task['start_date'] ?? '' }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">تاريخ التسليم</label>
                    <input type="date" name="end_date" class="form-control" value="{{ $task['due_date'] ?? $task['end_date'] ?? '' }}">
                </div>
                <div class="col-12 mb-3">
                    <div class="form-check">
                        <input type="checkbox" name="is_cross_functional" class="form-check-input" value="1" {{ ($task['is_cross_functional'] ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label">مهمة مشتركة بين الإدارات</label>
                    </div>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn-gold"><i class="fas fa-save ml-1"></i> حفظ التعديلات</button>
                    <a href="{{ route('tasks.show', $task['id'] ?? 0) }}" class="btn btn-outline-secondary mr-2">إلغاء</a>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

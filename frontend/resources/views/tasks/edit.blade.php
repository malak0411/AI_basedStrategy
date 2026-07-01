@extends('layouts.app')

@section('title', 'تعديل المهمة')

@section('content')
<div class="container-fluid px-4">
    <a href="{{ route('tasks.show', $task['id'] ?? 0) }}" class="btn btn-outline-secondary mb-3">
        <i class="fas fa-arrow-right"></i> العودة للتفاصيل
    </a>

    <div class="card-custom">
        <h4 class="mb-4"><i class="fas fa-edit ml-2"></i>تعديل المهمة</h4>

        <form method="POST" action="{{ route('tasks.update', $task['id'] ?? 0) }}">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col-md-8 mb-3">
                    <label class="form-label">اسم المهمة</label>
                    <input type="text" name="task_name" class="form-control" value="{{ $task['task_name'] ?? '' }}" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">تاريخ التسليم</label>
                    <input type="date" name="due_date" class="form-control" value="{{ $task['due_date'] ?? '' }}" required>
                </div>
                <div class="col-12 mb-3">
                    <label class="form-label">الوصف</label>
                    <textarea name="description" class="form-control" rows="4">{{ $task['description'] ?? '' }}</textarea>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">الحالة</label>
                    <select name="status" class="form-control">
                        <option value="pending" {{ ($task['status'] ?? '') === 'pending' ? 'selected' : '' }}>معلق</option>
                        <option value="in_progress" {{ ($task['status'] ?? '') === 'in_progress' ? 'selected' : '' }}>قيد التنفيذ</option>
                        <option value="completed" {{ ($task['status'] ?? '') === 'completed' ? 'selected' : '' }}>مكتمل</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">نسبة التقدم</label>
                    <input type="number" name="progress" class="form-control" min="0" max="100" value="{{ $task['progress'] ?? 0 }}">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn-gold">حفظ التعديلات</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

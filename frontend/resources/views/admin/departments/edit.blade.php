@extends('layouts.app')

@section('title', 'تعديل الإدارة')

@section('content')
<div class="container-fluid px-4">
    <a href="{{ route('admin.departments.show', $department['department_id']) }}" class="btn btn-outline-secondary mb-3"><i class="fas fa-arrow-right"></i> العودة</a>

    <div class="card-custom">
        <h4 class="mb-4"><i class="fas fa-edit ml-2"></i>تعديل الإدارة</h4>

        <form method="POST" action="{{ route('admin.departments.update', $department['department_id']) }}">
            @csrf @method('PUT')
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">اسم الإدارة</label>
                    <input type="text" name="name" class="form-control" value="{{ $department['name'] ?? '' }}" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">الكود</label>
                    <input type="text" name="code" class="form-control" value="{{ $department['code'] ?? '' }}">
                </div>
                <div class="col-12 mb-3">
                    <label class="form-label">الوصف</label>
                    <textarea name="description" class="form-control" rows="3">{{ $department['description'] ?? '' }}</textarea>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">الإدارة الأم</label>
                    <select name="parent_department_id" class="form-control">
                        <option value="">لا يوجد</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept['department_id'] }}" {{ ($department['parent_department_id'] ?? '') == $dept['department_id'] ? 'selected' : '' }}>{{ $dept['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">المدير</label>
                    <select name="manager_employee_id" class="form-control">
                        <option value="">اختر المدير</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp['employee_id'] }}" {{ ($department['manager_employee_id'] ?? '') == $emp['employee_id'] ? 'selected' : '' }}>{{ $emp['full_name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">المستوى</label>
                    <input type="number" name="level" class="form-control" value="{{ $department['level'] ?? 1 }}" min="1">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">الحالة</label>
                    <div class="form-check mt-2">
                        <input type="checkbox" name="is_active" class="form-check-input" value="1" {{ ($department['is_active'] ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label">نشطة</label>
                    </div>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn-gold"><i class="fas fa-save ml-1"></i> حفظ التعديلات</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

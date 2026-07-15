@extends('layouts.app')

@section('title', 'تعديل بيانات الموظف')

@section('content')
<div class="container-fluid px-4">
    <a href="{{ route('admin.employees.show', $employee['employee_id'] ?? 0) }}" class="btn btn-outline-secondary mb-3">
        <i class="fas fa-arrow-right"></i> العودة للتفاصيل
    </a>

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card-custom">
        <h4 class="mb-4"><i class="fas fa-edit ml-2"></i>تعديل بيانات الموظف</h4>

        <form method="POST" action="{{ route('admin.employees.update', $employee['employee_id'] ?? 0) }}">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">الاسم الكامل</label>
                    <input type="text" name="full_name" class="form-control" value="{{ $employee['full_name'] ?? '' }}" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">البريد الإلكتروني</label>
                    <input type="email" name="email" class="form-control" value="{{ $employee['email'] ?? '' }}" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">رقم الهاتف</label>
                    <input type="text" name="phone_number" class="form-control" value="{{ $employee['phone_number'] ?? '' }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">المسمى الوظيفي</label>
                    <input type="text" name="job_title" class="form-control" value="{{ $employee['job_title'] ?? '' }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">الإدارة</label>
                    <select name="department_id" class="form-control">
                        <option value="">اختر الإدارة</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept['department_id'] ?? '' }}" {{ ($employee['department_id'] ?? '') == ($dept['department_id'] ?? '') ? 'selected' : '' }}>
                                {{ $dept['name'] ?? '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">الحالة</label>
                    <div class="form-check mt-2">
                        <input type="checkbox" name="is_active" class="form-check-input" value="1" {{ ($employee['is_active'] ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label">نشط</label>
                    </div>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn-gold"><i class="fas fa-save ml-1"></i> حفظ التعديلات</button>
                    <a href="{{ route('admin.employees.show', $employee['employee_id'] ?? 0) }}" class="btn btn-outline-secondary mr-2">إلغاء</a>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@extends('layouts.app')

@section('title', 'إنشاء إدارة جديدة')

@section('content')
<div class="container-fluid px-4">
    <a href="{{ route('admin.departments.index') }}" class="btn btn-outline-secondary mb-3"><i class="fas fa-arrow-right"></i> العودة</a>

    <div class="card-custom">
        <h4 class="mb-4"><i class="fas fa-plus-circle ml-2"></i>إنشاء إدارة جديدة</h4>

        <form method="POST" action="{{ route('admin.departments.store') }}">
            @csrf
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">اسم الإدارة <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">الكود</label>
                    <input type="text" name="code" class="form-control">
                </div>
                <div class="col-12 mb-3">
                    <label class="form-label">الوصف</label>
                    <textarea name="description" class="form-control" rows="3"></textarea>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">الإدارة الأم</label>
                    <select name="parent_department_id" class="form-control">
                        <option value="">لا يوجد</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept['department_id'] }}">{{ $dept['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">المدير</label>
                    <select name="manager_employee_id" class="form-control">
                        <option value="">اختر المدير</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp['employee_id'] }}">{{ $emp['full_name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">المستوى</label>
                    <input type="number" name="level" class="form-control" value="1" min="1" max="5">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn-gold"><i class="fas fa-save ml-1"></i> إنشاء الإدارة</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

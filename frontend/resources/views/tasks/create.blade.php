@extends('layouts.app')

@section('title', 'إنشاء مهمة جديدة')

@section('content')
<div class="container-fluid px-4">
    <a href="{{ route('tasks.index') }}" class="btn btn-outline-secondary mb-3">
        <i class="fas fa-arrow-right"></i> العودة للقائمة
    </a>

    @if ($errors->any())
        <div class="alert alert-danger">
            @foreach ($errors->all() as $error)
                <p class="mb-0">{{ $error }}</p>
            @endforeach
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card-custom">
        <h4 class="mb-4"><i class="fas fa-plus-circle ml-2"></i>إنشاء مهمة جديدة</h4>

        <form method="POST" action="{{ route('tasks.store') }}">
            @csrf
            <div class="row">
                <div class="col-md-8 mb-3">
                    <label class="form-label">اسم المهمة <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" placeholder="أدخل اسم المهمة" value="{{ old('title') }}" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">المهمة الرئيسية <span class="text-danger">*</span></label>
                    <select name="major_task_id" class="form-control" required>
                        <option value="">اختر المهمة الرئيسية</option>
                        @foreach($majorTasks as $mt)
                            <option value="{{ $mt['id'] ?? '' }}" {{ old('major_task_id') == ($mt['id'] ?? '') ? 'selected' : '' }}>
                                {{ $mt['name'] ?? $mt['title'] ?? '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 mb-3">
                    <label class="form-label">الوصف</label>
                    <textarea name="description" class="form-control" rows="4" placeholder="أدخل وصف المهمة">{{ old('description') }}</textarea>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">الإدارة <span class="text-danger">*</span></label>
                    <select name="department_id" class="form-control" required>
                        <option value="">اختر الإدارة</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept['department_id'] ?? '' }}" {{ old('department_id') == ($dept['department_id'] ?? '') ? 'selected' : '' }}>
                                {{ $dept['name'] ?? '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">الأولوية</label>
                    <select name="priority_id" class="form-control">
                        <option value="1" {{ old('priority_id') == '1' ? 'selected' : '' }}>منخفضة</option>
                        <option value="2" {{ old('priority_id') == '2' ? 'selected' : '' }}>متوسطة</option>
                        <option value="3" {{ old('priority_id') == '3' ? 'selected' : '' }}>عالية</option>
                        <option value="4" {{ old('priority_id') == '4' ? 'selected' : '' }}>حرجة</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">الساعات المقدرة</label>
                    <input type="number" name="estimated_hours" class="form-control" value="{{ old('estimated_hours', 0) }}" step="0.5">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">تاريخ البداية</label>
                    <input type="date" name="start_date" class="form-control" value="{{ old('start_date') }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">تاريخ التسليم</label>
                    <input type="date" name="end_date" class="form-control" value="{{ old('end_date') }}">
                </div>
                <div class="col-12 mb-3">
                    <div class="form-check">
                        <input type="checkbox" name="is_cross_functional" class="form-check-input" value="1" {{ old('is_cross_functional') ? 'checked' : '' }}>
                        <label class="form-check-label">مهمة مشتركة بين الإدارات</label>
                    </div>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn-gold"><i class="fas fa-save ml-1"></i> إنشاء المهمة</button>
                    <a href="{{ route('tasks.index') }}" class="btn btn-outline-secondary mr-2">إلغاء</a>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

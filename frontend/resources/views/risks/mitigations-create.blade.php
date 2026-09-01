@extends('layouts.app')

@section('title', 'إضافة إجراء معالجة')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3><i class="fas fa-plus ml-2"></i>إضافة إجراء معالجة</h3>
            <p class="text-muted mb-0">#{{ $risk['risk_id'] ?? '' }} - {{ $risk['name'] ?? '' }}</p>
        </div>
        <a href="{{ route('risks.mitigations', $risk['risk_id']) }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-right"></i> العودة
        </a>
    </div>

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="card-custom">
        <div class="card-body">
            <form action="{{ route('risks.mitigations.store', $risk['risk_id']) }}" method="POST">
                @csrf

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">الخطر</label>
                            <input type="text" class="form-control" value="#{{ $risk['risk_id'] }} - {{ $risk['name'] }}" disabled>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">إجراء المعالجة <span class="text-danger">*</span></label>
                            <textarea name="action" class="form-control @error('action') is-invalid @enderror" 
                                rows="3" placeholder="وصف إجراء المعالجة" required>{{ old('action') }}</textarea>
                            @error('action')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">المهمة المرتبطة</label>
                            <select name="task_id" class="form-control @error('task_id') is-invalid @enderror">
                                <option value="">اختر مهمة</option>
                                @foreach(($options['tasks'] ?? []) as $task)
                                <option value="{{ $task['task_id'] }}" {{ old('task_id') == $task['task_id'] ? 'selected' : '' }}>
                                    {{ $task['title'] }}
                                </option>
                                @endforeach
                            </select>
                            @error('task_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">المسؤول</label>
                            <select name="assigned_to" class="form-control @error('assigned_to') is-invalid @enderror">
                                <option value="">اختر موظفاً</option>
                                @foreach(($options['employees'] ?? []) as $employee)
                                <option value="{{ $employee['employee_id'] }}" {{ old('assigned_to') == $employee['employee_id'] ? 'selected' : '' }}>
                                    {{ $employee['full_name'] }}
                                </option>
                                @endforeach
                            </select>
                            @error('assigned_to')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">الحالة</label>
                            <select name="status_id" class="form-control @error('status_id') is-invalid @enderror">
                                <option value="">اختر الحالة</option>
                                @foreach(($options['statuses'] ?? []) as $status)
                                <option value="{{ $status['status_id'] }}" {{ old('status_id') == $status['status_id'] ? 'selected' : '' }}>
                                    {{ $status['name_ar'] }}
                                </option>
                                @endforeach
                            </select>
                            @error('status_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">تاريخ الاستحقاق</label>
                            <input type="date" name="due_date" class="form-control @error('due_date') is-invalid @enderror" 
                                value="{{ old('due_date') }}">
                            @error('due_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">الملاحظات</label>
                            <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" 
                                rows="3" placeholder="ملاحظات إضافية">{{ old('notes') }}</textarea>
                            @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="border-top pt-3 mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> حفظ الإجراء
                    </button>
                    <a href="{{ route('risks.mitigations', $risk['risk_id']) }}" class="btn btn-outline-secondary">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

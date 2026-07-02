@extends('layouts.app')

@section('title', 'الملف الشخصي')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3><i class="fas fa-user-circle ml-2"></i>الملف الشخصي</h3>
        <a href="{{ route('profile.edit') }}" class="btn-gold">
            <i class="fas fa-edit"></i> تعديل
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row">
        <div class="col-lg-4">
            <div class="card-custom text-center">
                <div class="avatar-lg bg-gold rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" 
                     style="width: 100px; height: 100px; font-size: 40px;">
                    {{ mb_substr(session('user_name', 'م'), 0, 1) }}
                </div>
                <h4>{{ $employee['full_name'] ?? session('user_name') }}</h4>
                <p class="text-muted">{{ $employee['job_title'] ?? '' }}</p>
                <span class="badge bg-{{ ($employee['is_active'] ?? true) ? 'success' : 'danger' }}">
                    {{ ($employee['is_active'] ?? true) ? 'نشط' : 'غير نشط' }}
                </span>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card-custom">
                <h5 class="mb-4">المعلومات الشخصية</h5>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <strong>البريد الإلكتروني:</strong>
                        <p>{{ $employee['email'] ?? session('user_email') }}</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>رقم الهاتف:</strong>
                        <p>{{ $employee['phone_number'] ?? '' }}</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>الإدارة:</strong>
                        <p>{{ $employee['department_name'] ?? session('user_department') }}</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>تاريخ التوظيف:</strong>
                        <p>{{ $employee['hire_date'] ?? '' }}</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>الرقم الوظيفي:</strong>
                        <p>{{ $employee['employee_number'] ?? session('user_id') }}</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>آخر تسجيل دخول:</strong>
                        <p>{{ $employee['last_login'] ?? '' }}</p>
                    </div>
                </div>
            </div>

            {{-- المهام الحالية --}}
            @if(!empty($employee['tasks']))
            <div class="card-custom mt-4">
                <h5 class="mb-3">المهام الحالية</h5>
                @foreach($employee['tasks'] as $task)
                <div class="border rounded p-3 mb-2">
                    <div class="d-flex justify-content-between">
                        <strong>{{ $task['task_name'] ?? '' }}</strong>
                        <span class="badge bg-info">{{ $task['progress'] ?? 0 }}%</span>
                    </div>
                    <small class="text-muted">{{ $task['due_date'] ?? '' }}</small>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

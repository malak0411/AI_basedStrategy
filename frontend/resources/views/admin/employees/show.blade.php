@extends('layouts.app')

@section('title', 'تفاصيل الموظف')

@section('content')
<div class="container-fluid px-4">
    <a href="{{ route('admin.employees.index') }}" class="btn btn-outline-secondary mb-3">
        <i class="fas fa-arrow-right"></i> العودة للقائمة
    </a>

    @if(empty($employee))
        <div class="alert alert-info">الموظف غير موجود</div>
    @else
        <div class="card-custom">
            <div class="d-flex align-items-center gap-3 mb-4">
                <div class="avatar-lg bg-gold rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; font-size: 24px;">
                    {{ mb_substr($employee['full_name'] ?? 'م', 0, 1) }}
                </div>
                <div>
                    <h4>{{ $employee['full_name'] ?? '' }}</h4>
                    <span class="text-muted">{{ $employee['job_title'] ?? '' }}</span>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <strong>البريد الإلكتروني:</strong> {{ $employee['email'] ?? '' }}
                </div>
                <div class="col-md-6 mb-3">
                    <strong>رقم الهاتف:</strong> {{ $employee['phone_number'] ?? '' }}
                </div>
                <div class="col-md-6 mb-3">
                    <strong>الإدارة:</strong> {{ $employee['department_name'] ?? '' }}
                </div>
                <div class="col-md-6 mb-3">
                    <strong>تاريخ التوظيف:</strong> {{ $employee['hire_date'] ?? '' }}
                </div>
                <div class="col-md-6 mb-3">
                    <strong>الحالة:</strong>
                    <span class="badge bg-{{ ($employee['is_active'] ?? false) ? 'success' : 'danger' }}">
                        {{ ($employee['is_active'] ?? false) ? 'نشط' : 'غير نشط' }}
                    </span>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

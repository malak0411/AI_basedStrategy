@extends('layouts.app')

@section('title', 'تفاصيل الموظف')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="{{ route('admin.employees.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-right"></i> العودة للقائمة
        </a>
        <a href="{{ route('admin.employees.edit', $employee['employee_id'] ?? 0) }}" class="btn btn-outline-primary">
            <i class="fas fa-edit"></i> تعديل
        </a>
    </div>

    @if(empty($employee))
        <div class="alert alert-info">الموظف غير موجود</div>
    @else
        <div class="row">
            <div class="col-lg-4">
                <div class="card-custom text-center mb-4">
                    <div class="avatar-lg bg-gold rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" 
                         style="width: 100px; height: 100px; font-size: 40px;">
                        {{ mb_substr($employee['full_name'] ?? 'م', 0, 1) }}
                    </div>
                    <h4>{{ $employee['full_name'] ?? '' }}</h4>
                    <p class="text-muted">{{ $employee['job_title'] ?? '' }}</p>
                    <span class="badge bg-{{ ($employee['is_active'] ?? false) ? 'success' : 'danger' }} fs-6">
                        {{ ($employee['is_active'] ?? false) ? 'نشط' : 'غير نشط' }}
                    </span>
                </div>

                <div class="card-custom mb-4">
                    <h5>معلومات الاتصال</h5>
                    <hr>
                    <p><i class="fas fa-envelope ml-2"></i> {{ $employee['email'] ?? '' }}</p>
                    <p><i class="fas fa-phone ml-2"></i> {{ $employee['phone_number'] ?? '' }}</p>
                    <p><i class="fas fa-building ml-2"></i> {{ $employee['department_name'] ?? '' }}</p>
                    <p><i class="fas fa-id-card ml-2"></i> {{ $employee['employee_number'] ?? '' }}</p>
                    <p><i class="fas fa-calendar ml-2"></i> تاريخ التوظيف: {{ $employee['hire_date'] ?? '' }}</p>
                </div>

                {{-- الأدوار --}}
                <div class="card-custom">
                    <h5>الأدوار</h5>
                    <hr>
                    @if(!empty($employeeRoles))
                        @foreach($employeeRoles as $er)
                            <span class="badge bg-info me-1 mb-1">{{ $er['role_name'] ?? '' }}</span>
                        @endforeach
                    @else
                        <p class="text-muted">لا توجد أدوار مرتبطة</p>
                    @endif
                </div>
            </div>

            <div class="col-lg-8">
                {{-- تبويبات --}}
                <ul class="nav nav-tabs mb-4" id="empTabs">
                    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tasks">المهام</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#activity">سجل النشاط</a></li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="tasks">
                        <div class="card-custom">
                            <h5>المهام الحالية</h5>
                            <p class="text-muted">يتم تحميل المهام من API...</p>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="activity">
                        <div class="card-custom">
                            <h5>سجل النشاط</h5>
                            <p class="text-muted">آخر نشاط: {{ $employee['last_login'] ?? 'غير معروف' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

@extends('layouts.app')

@section('title', 'إضافة موظف جديد')

@push('styles')
<style>
    .required-star { color: #dc3545; }
    .form-section-title {
        font-size: 13px; font-weight: 700; color: #4a5568;
        padding-bottom: 6px; border-bottom: 2px solid #edf2f7;
        margin-bottom: 16px;
    }
    .help-text { font-size: 11px; color: #a0aec0; margin-top: 4px; }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <a href="{{ route('admin.employees.index') }}" class="btn btn-outline-secondary mb-3">
        <i class="fas fa-arrow-right"></i> العودة
    </a>

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <strong><i class="fas fa-exclamation-circle"></i> يوجد أخطاء في النموذج:</strong>
            <ul class="mb-0 mt-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card-custom">
        <h4 class="mb-4">
            <i class="fas fa-user-plus ml-2 text-primary"></i>إضافة موظف جديد
        </h4>

        <form method="POST" action="{{ route('admin.employees.store') }}" id="createEmployeeForm">
            @csrf

            {{-- البيانات الأساسية --}}
            <div class="form-section-title">
                <i class="fas fa-id-card"></i> البيانات الأساسية
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">
                        الاسم الكامل <span class="required-star">*</span>
                    </label>
                    <input type="text" name="full_name" class="form-control @error('full_name') is-invalid @enderror"
                           value="{{ old('full_name') }}" required maxlength="255"
                           placeholder="مثال: أحمد محمد علي">
                    @error('full_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">
                        البريد الإلكتروني <span class="required-star">*</span>
                    </label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                           value="{{ old('email') }}" required maxlength="191"
                           placeholder="name@example.com">
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div class="help-text"><i class="fas fa-info-circle"></i> يُستخدم لتسجيل الدخول</div>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">
                        كلمة المرور <span class="required-star">*</span>
                    </label>
                    <input type="password" name="password" id="password"
                           class="form-control @error('password') is-invalid @enderror"
                           required minlength="6" placeholder="6 أحرف على الأقل">
                    @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">
                        تأكيد كلمة المرور <span class="required-star">*</span>
                    </label>
                    <input type="password" name="password_confirmation" id="password_confirmation"
                           class="form-control" required minlength="6"
                           placeholder="أعد إدخال كلمة المرور">
                    <div class="help-text" id="passwordMatchMsg"></div>
                </div>
            </div>

            {{-- الوظيفة --}}
            <div class="form-section-title mt-2">
                <i class="fas fa-briefcase"></i> الوظيفة والإدارة
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">
                        الإدارة <span class="required-star">*</span>
                    </label>
                    <select name="department_id" class="form-control @error('department_id') is-invalid @enderror" required>
                        <option value="">-- اختر الإدارة --</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept['department_id'] }}"
                                {{ old('department_id') == $dept['department_id'] ? 'selected' : '' }}>
                                {{ $dept['name'] }}
                            </option>
                        @endforeach
                    </select>
                    @error('department_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">المسمى الوظيفي</label>
                    <input type="text" name="job_title" class="form-control @error('job_title') is-invalid @enderror"
                           value="{{ old('job_title') }}" maxlength="150"
                           placeholder="مثال: مهندس برمجيات">
                    @error('job_title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">الرقم الوظيفي</label>
                    <input type="text" name="employee_number"
                           class="form-control @error('employee_number') is-invalid @enderror"
                           value="{{ old('employee_number') }}" maxlength="50"
                           placeholder="اختياري">
                    @error('employee_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">رقم الهاتف</label>
                    <input type="text" name="phone_number"
                           class="form-control @error('phone_number') is-invalid @enderror"
                           value="{{ old('phone_number') }}" maxlength="30"
                           placeholder="+967...">
                    @error('phone_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">تاريخ التعيين</label>
                    <input type="date" name="hire_date"
                           class="form-control @error('hire_date') is-invalid @enderror"
                           value="{{ old('hire_date') }}">
                    @error('hire_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            {{-- الإعدادات --}}
            <div class="form-section-title mt-2">
                <i class="fas fa-cog"></i> الإعدادات
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="form-check form-switch">
                        <input type="checkbox" name="is_active" id="is_active"
                               class="form-check-input" value="1"
                               {{ old('is_active', true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">
                            <strong>موظف نشط</strong>
                            <div class="help-text">غير النشط لا يمكنه تسجيل الدخول</div>
                        </label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-check form-switch">
                        <input type="checkbox" name="gps_enabled" id="gps_enabled"
                               class="form-check-input" value="1"
                               {{ old('gps_enabled', true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="gps_enabled">
                            <strong>تفعيل تتبع GPS</strong>
                            <div class="help-text">يُسجّل موقع الموظف أثناء المهام الميدانية</div>
                        </label>
                    </div>
                </div>
            </div>

            <hr class="my-4">

            <div class="d-flex gap-2">
                <button type="submit" class="btn-gold" id="submitBtn">
                    <i class="fas fa-save ml-1"></i> إضافة الموظف
                </button>
                <a href="{{ route('admin.employees.index') }}" class="btn btn-secondary">
                    <i class="fas fa-times"></i> إلغاء
                </a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    var pw = document.getElementById('password');
    var pwc = document.getElementById('password_confirmation');
    var msg = document.getElementById('passwordMatchMsg');
    var form = document.getElementById('createEmployeeForm');
    var btn = document.getElementById('submitBtn');

    function checkMatch() {
        if (!pwc.value) { msg.textContent = ''; pwc.classList.remove('is-invalid', 'is-valid'); return; }
        if (pw.value === pwc.value) {
            msg.innerHTML = '<span style="color:#28a745;"><i class="fas fa-check-circle"></i> كلمتا المرور متطابقتان</span>';
            pwc.classList.remove('is-invalid'); pwc.classList.add('is-valid');
        } else {
            msg.innerHTML = '<span style="color:#dc3545;"><i class="fas fa-times-circle"></i> كلمتا المرور غير متطابقتين</span>';
            pwc.classList.remove('is-valid'); pwc.classList.add('is-invalid');
        }
    }

    if (pw && pwc) {
        pw.addEventListener('input', checkMatch);
        pwc.addEventListener('input', checkMatch);
    }

    if (form && btn) {
        form.addEventListener('submit', function (e) {
            if (pw.value !== pwc.value) {
                e.preventDefault();
                alert('كلمتا المرور غير متطابقتين');
                return false;
            }
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري الحفظ...';
        });
    }
})();
</script>
@endpush

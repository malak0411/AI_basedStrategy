@extends('layouts.app')

@section('title', 'تعديل الملف الشخصي')

@section('content')
<div class="container-fluid px-4">
    <a href="{{ route('profile.index') }}" class="btn btn-outline-secondary mb-3">
        <i class="fas fa-arrow-right"></i> العودة
    </a>

    <div class="card-custom">
        <h4 class="mb-4"><i class="fas fa-edit ml-2"></i>تعديل الملف الشخصي</h4>

        <form method="POST" action="{{ route('profile.update') }}">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">الاسم الكامل</label>
                    <input type="text" name="full_name" class="form-control" value="{{ $employee['full_name'] ?? '' }}" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">رقم الهاتف</label>
                    <input type="text" name="phone_number" class="form-control" value="{{ $employee['phone_number'] ?? '' }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">البريد الإلكتروني</label>
                    <input type="email" name="email" class="form-control" value="{{ $employee['email'] ?? '' }}" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">المسمى الوظيفي</label>
                    <input type="text" name="job_title" class="form-control" value="{{ $employee['job_title'] ?? '' }}">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn-gold">حفظ التعديلات</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@extends('layouts.app')

@section('title', 'لوحة تحكم الموظف')

@section('content')
    <div class="card-custom">
        <h3>مرحباً {{ session('user_name', 'مستخدم') }} 👋</h3>
        <p>تم تسجيل الدخول بنجاح.</p>
        <p>الصلاحية: <strong>{{ session('user_role', 'غير معروف') }}</strong></p>
        <p>الإيميل: {{ session('user_email', '') }}</p>
    </div>
@endsection

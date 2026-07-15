@extends('layouts.app')

@section('title', 'إنشاء دور جديد')

@section('content')
<div class="container-fluid px-4">
    <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary mb-3"><i class="fas fa-arrow-right"></i> العودة</a>
    <div class="card-custom">
        <h4 class="mb-4"><i class="fas fa-plus-circle ml-2"></i>إنشاء دور جديد</h4>
        <form method="POST" action="{{ route('admin.roles.store') }}">
            @csrf
            <div class="mb-3"><label class="form-label">اسم الدور</label><input type="text" name="name" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">الوصف</label><textarea name="description" class="form-control" rows="3"></textarea></div>
            <button type="submit" class="btn-gold">إنشاء</button>
        </form>
    </div>
</div>
@endsection

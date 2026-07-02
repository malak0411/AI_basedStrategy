@extends('layouts.app')

@section('title', 'تعديل الدور')

@section('content')
<div class="container-fluid px-4">
    <a href="{{ route('admin.roles.show', $role['id']) }}" class="btn btn-outline-secondary mb-3">
        <i class="fas fa-arrow-right"></i> العودة
    </a>

    <div class="card-custom">
        <h4 class="mb-4"><i class="fas fa-edit ml-2"></i>تعديل الدور</h4>

        <form method="POST" action="{{ route('admin.roles.update', $role['id']) }}">
            @csrf
            @method('PUT')
            <div class="mb-3">
                <label class="form-label">اسم الدور</label>
                <input type="text" name="name" class="form-control" value="{{ $role['name'] ?? '' }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">الوصف</label>
                <textarea name="description" class="form-control" rows="3">{{ $role['description'] ?? '' }}</textarea>
            </div>
            <button type="submit" class="btn-gold">حفظ التعديلات</button>
        </form>
    </div>
</div>
@endsection

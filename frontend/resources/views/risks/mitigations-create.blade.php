@extends('layouts.app')

@section('title', 'إضافة خطة تخفيف')

@section('content')
<div class="container-fluid px-4">
    <a href="{{ route('risks.mitigations', $id) }}" class="btn btn-outline-secondary mb-3">
        <i class="fas fa-arrow-right"></i> العودة
    </a>

    <div class="card-custom">
        <h4 class="mb-4"><i class="fas fa-plus-circle ml-2"></i>إضافة خطة تخفيف جديدة</h4>

        <form method="POST" action="{{ route('risks.mitigations.store', $id) }}">
            @csrf
            <div class="row">
                <div class="col-12 mb-3">
                    <label class="form-label">اسم الخطة</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="col-12 mb-3">
                    <label class="form-label">الوصف</label>
                    <textarea name="description" class="form-control" rows="4" required></textarea>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn-gold">إضافة الخطة</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

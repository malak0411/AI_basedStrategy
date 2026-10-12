@extends('layouts.app')


@section('title', 'تعديل الركيزة')


@section('content')
<div class="container-fluid px-4">


    <a href="{{ route('strategic.pillars.index') }}" class="btn btn-outline-secondary mb-3">
        <i class="fas fa-arrow-right"></i> العودة للركائز
    </a>


    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif


    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card-custom">
                <h4 class="mb-4"><i class="fas fa-edit text-warning ml-2"></i>تعديل الركيزة</h4>


                <form method="POST" action="{{ route('strategic.pillars.update', $pillar['id']) }}">
                    @csrf
                    @method('PUT')


                    <div class="mb-3">
                        <label class="form-label">اسم الركيزة <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $pillar['name'] ?? '') }}" required maxlength="255">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>


                    <div class="mb-3">
                        <label class="form-label">الوصف</label>
                        <textarea name="description" class="form-control" rows="4" maxlength="1000">{{ old('description', $pillar['description'] ?? '') }}</textarea>
                    </div>


                    <div class="mb-3">
                        <label class="form-label">الترتيب</label>
                        <input type="number" name="order_index" class="form-control"
                               value="{{ old('order_index', $pillar['order_index'] ?? 0) }}" min="0" max="100">
                        <small class="text-muted">الأصغر يأتي أولاً في العرض</small>
                    </div>


                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn-gold">
                            <i class="fas fa-save"></i> حفظ التعديلات
                        </button>
                        <a href="{{ route('strategic.pillars.show', $pillar['id']) }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> إلغاء
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>


</div>
@endsection

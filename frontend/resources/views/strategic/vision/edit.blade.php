@extends('layouts.app')


@section('title', 'تعديل الرؤية')


@section('content')
<div class="container-fluid px-4">


    <a href="{{ route('strategic.vision.index') }}" class="btn btn-outline-secondary mb-3">
        <i class="fas fa-arrow-right"></i> العودة للرؤية
    </a>


    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif


    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card-custom">
                <h4 class="mb-4"><i class="fas fa-edit text-warning ml-2"></i>تعديل الرؤية الاستراتيجية</h4>


                @if(!empty($vision['version']))
                    <div class="alert alert-info py-2">
                        <small><i class="fas fa-info-circle ml-1"></i> سيتم إنشاء إصدار جديد برقم {{ ($vision['version'] ?? 0) + 1 }}</small>
                    </div>
                @endif


                <form method="POST" action="{{ route('strategic.vision.update') }}">
                    @csrf
                    @method('PUT')


                    <div class="mb-3">
                        <label class="form-label">نص الرؤية <span class="text-danger">*</span></label>
                        <textarea name="text" class="form-control @error('text') is-invalid @enderror"
                                  rows="3" required maxlength="500"
                                  placeholder="مثال: أن نكون المؤسسة الرائدة في...">{{ old('text', $vision['text'] ?? '') }}</textarea>
                        @error('text')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <small class="text-muted">جملة واحدة موجزة (حتى 500 حرف)</small>
                    </div>


                    <div class="mb-3">
                        <label class="form-label">الوصف التفصيلي</label>
                        <textarea name="description" class="form-control"
                                  rows="5" maxlength="2000"
                                  placeholder="شرح تفصيلي للرؤية، الأهداف الكبرى، القيم...">{{ old('description', $vision['description'] ?? '') }}</textarea>
                    </div>


                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn-gold">
                            <i class="fas fa-save"></i> حفظ الرؤية
                        </button>
                        <a href="{{ route('strategic.vision.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> إلغاء
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>


</div>
@endsection

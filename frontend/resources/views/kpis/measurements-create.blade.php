@extends('layouts.app')

@section('title', 'إضافة قياس جديد')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3><i class="fas fa-plus ml-2"></i>إضافة قياس جديد</h3>
            <p class="text-muted mb-0">{{ $kpi['name'] ?? '' }}</p>
        </div>
        <a href="{{ route('kpis.measurements', $kpi['kpi_id']) }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-right"></i> العودة للقياسات
        </a>
    </div>

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="card-custom">
        <div class="card-body">
            <form action="{{ route('kpis.measurements.store', $kpi['kpi_id']) }}" method="POST">
                @csrf

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">المؤشر</label>
                            <input type="text" class="form-control" value="{{ $kpi['name'] ?? '' }}" disabled>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">الوحدة</label>
                            <input type="text" class="form-control" value="{{ $kpi['unit'] ?? '' }}" disabled>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">القيمة <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="value" class="form-control @error('value') is-invalid @enderror" 
                                value="{{ old('value') }}" placeholder="أدخل القيمة" required>
                            @error('value')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">أدخل القيمة الفعلية للمؤشر</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">تاريخ القياس</label>
                            <input type="date" name="measured_at" class="form-control @error('measured_at') is-invalid @enderror" 
                                value="{{ old('measured_at', date('Y-m-d')) }}">
                            @error('measured_at')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">الملاحظات</label>
                    <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" 
                        rows="3" placeholder="أي ملاحظات حول هذا القياس">{{ old('notes') }}</textarea>
                    @error('notes')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="border-top pt-3 mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> تسجيل القياس
                    </button>
                    <a href="{{ route('kpis.measurements', $kpi['kpi_id']) }}" class="btn btn-outline-secondary">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@extends('layouts.app')

@section('title', 'تحرير تحليل SWOT')

@section('content')
<div class="container-fluid px-4">
    <a href="{{ route('strategic.swot.index') }}" class="btn btn-outline-secondary mb-3">
        <i class="fas fa-arrow-right"></i> العودة للعرض
    </a>

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card-custom">
        <h4 class="mb-4"><i class="fas fa-edit ml-2"></i>تحرير تحليل SWOT</h4>

        <form method="POST" action="{{ route('strategic.swot.update') }}">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col-md-6 mb-4">
                    <label class="form-label fw-bold text-success">
                        <i class="fas fa-check-circle ml-1"></i> نقاط القوة (Strengths)
                    </label>
                    <textarea name="strengths" class="form-control" rows="6" 
                        placeholder="ما الذي نقوم به بشكل جيد؟&#10;ما هي مواردنا الفريدة؟&#10;ما هي ميزتنا التنافسية؟">{{ $swot['strengths'] ?? '' }}</textarea>
                </div>
                <div class="col-md-6 mb-4">
                    <label class="form-label fw-bold text-danger">
                        <i class="fas fa-exclamation-circle ml-1"></i> نقاط الضعف (Weaknesses)
                    </label>
                    <textarea name="weaknesses" class="form-control" rows="6"
                        placeholder="ما الذي يمكن تحسينه؟&#10;ما الذي يجب تجنبه؟&#10;أين تنقصنا الموارد؟">{{ $swot['weaknesses'] ?? '' }}</textarea>
                </div>
                <div class="col-md-6 mb-4">
                    <label class="form-label fw-bold text-info">
                        <i class="fas fa-lightbulb ml-1"></i> الفرص (Opportunities)
                    </label>
                    <textarea name="opportunities" class="form-control" rows="6"
                        placeholder="ما هي الفرص المتاحة؟&#10;ما هي التوجهات الجديدة؟&#10;كيف يمكننا التوسع؟">{{ $swot['opportunities'] ?? '' }}</textarea>
                </div>
                <div class="col-md-6 mb-4">
                    <label class="form-label fw-bold text-warning">
                        <i class="fas fa-exclamation-triangle ml-1"></i> التهديدات (Threats)
                    </label>
                    <textarea name="threats" class="form-control" rows="6"
                        placeholder="ما هي التهديدات المحتملة؟&#10;ماذا يفعل المنافسون؟&#10;ما هي العوائق الخارجية؟">{{ $swot['threats'] ?? '' }}</textarea>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn-gold btn-lg">
                        <i class="fas fa-save ml-1"></i> حفظ التحليل
                    </button>
                    <a href="{{ route('strategic.swot.index') }}" class="btn btn-outline-secondary btn-lg mr-3">إلغاء</a>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

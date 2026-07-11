@extends('layouts.app')

@section('title', 'تحرير تحليل SWOT')

@section('content')
<div class="container-fluid px-4">
    <a href="{{ route('strategic.swot.index') }}" class="btn btn-outline-secondary mb-3">
        <i class="fas fa-arrow-right"></i> العودة للعرض
    </a>

    <div class="card-custom">
        <h4 class="mb-4"><i class="fas fa-edit ml-2"></i>تحرير تحليل SWOT</h4>

        <form method="POST" action="{{ route('strategic.swot.update') }}">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label text-success fw-bold">نقاط القوة (Strengths)</label>
                    <textarea name="strengths" class="form-control" rows="5" placeholder="ما الذي نقوم به بشكل جيد؟">{{ $swot['strengths'] ?? '' }}</textarea>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label text-danger fw-bold">نقاط الضعف (Weaknesses)</label>
                    <textarea name="weaknesses" class="form-control" rows="5" placeholder="ما الذي يمكن تحسينه؟">{{ $swot['weaknesses'] ?? '' }}</textarea>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label text-info fw-bold">الفرص (Opportunities)</label>
                    <textarea name="opportunities" class="form-control" rows="5" placeholder="ما هي الفرص المتاحة؟">{{ $swot['opportunities'] ?? '' }}</textarea>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label text-warning fw-bold">التهديدات (Threats)</label>
                    <textarea name="threats" class="form-control" rows="5" placeholder="ما هي التهديدات المحتملة؟">{{ $swot['threats'] ?? '' }}</textarea>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn-gold"><i class="fas fa-save ml-1"></i> حفظ التحليل</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

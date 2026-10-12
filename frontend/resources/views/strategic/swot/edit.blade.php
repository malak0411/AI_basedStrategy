@extends('layouts.app')


@section('title', 'تعديل تحليل SWOT')


@push('styles')
<style>
    .swot-form-box {
        background: #fff; border-radius: 12px; padding: 20px;
        border: 1px solid #e2e8f0; height: 100%;
        border-top: 4px solid #e2e8f0;
    }
    .swot-form-box.strengths     { border-top-color: #38a169; }
    .swot-form-box.weaknesses    { border-top-color: #e53e3e; }
    .swot-form-box.opportunities { border-top-color: #3182ce; }
    .swot-form-box.threats       { border-top-color: #dd6b20; }


    .swot-form-title {
        font-size: 15px; font-weight: 700; margin-bottom: 12px;
        display: flex; align-items: center; gap: 8px;
    }
    .swot-form-box.strengths     .swot-form-title { color: #38a169; }
    .swot-form-box.weaknesses    .swot-form-title { color: #e53e3e; }
    .swot-form-box.opportunities .swot-form-title { color: #3182ce; }
    .swot-form-box.threats       .swot-form-title { color: #dd6b20; }
</style>
@endpush


@section('content')
<div class="container-fluid px-4">


    <a href="{{ route('strategic.swot.index') }}" class="btn btn-outline-secondary mb-3">
        <i class="fas fa-arrow-right"></i> العودة للتحليل
    </a>


    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif


    <div class="card-custom">
        <h4 class="mb-4"><i class="fas fa-edit text-warning ml-2"></i>تعديل تحليل SWOT</h4>


        <form method="POST" action="{{ route('strategic.swot.update') }}">
            @csrf
            @method('PUT')


            <div class="row g-3">


                <div class="col-md-6">
                    <div class="swot-form-box strengths">
                        <div class="swot-form-title">
                            <i class="fas fa-plus-circle"></i> نقاط القوة
                        </div>
                        <textarea name="strengths" class="form-control" rows="6"
                                  placeholder="اكتب كل نقطة في سطر منفصل...">{{ old('strengths', $swot['strengths'] ?? '') }}</textarea>
                    </div>
                </div>


                <div class="col-md-6">
                    <div class="swot-form-box weaknesses">
                        <div class="swot-form-title">
                            <i class="fas fa-minus-circle"></i> نقاط الضعف
                        </div>
                        <textarea name="weaknesses" class="form-control" rows="6"
                                  placeholder="اكتب كل نقطة في سطر منفصل...">{{ old('weaknesses', $swot['weaknesses'] ?? '') }}</textarea>
                    </div>
                </div>


                <div class="col-md-6">
                    <div class="swot-form-box opportunities">
                        <div class="swot-form-title">
                            <i class="fas fa-lightbulb"></i> الفرص
                        </div>
                        <textarea name="opportunities" class="form-control" rows="6"
                                  placeholder="اكتب كل فرصة في سطر منفصل...">{{ old('opportunities', $swot['opportunities'] ?? '') }}</textarea>
                    </div>
                </div>


                <div class="col-md-6">
                    <div class="swot-form-box threats">
                        <div class="swot-form-title">
                            <i class="fas fa-exclamation-triangle"></i> التهديدات
                        </div>
                        <textarea name="threats" class="form-control" rows="6"
                                  placeholder="اكتب كل تهديد في سطر منفصل...">{{ old('threats', $swot['threats'] ?? '') }}</textarea>
                    </div>
                </div>


            </div>


            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn-gold">
                    <i class="fas fa-save"></i> حفظ التحليل
                </button>
                <a href="{{ route('strategic.swot.index') }}" class="btn btn-secondary">
                    <i class="fas fa-times"></i> إلغاء
                </a>
            </div>
        </form>
    </div>


</div>
@endsection

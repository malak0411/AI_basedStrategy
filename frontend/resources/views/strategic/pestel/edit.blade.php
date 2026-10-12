@extends('layouts.app')


@section('title', 'تعديل تحليل PESTEL')


@push('styles')
<style>
    .pestel-form-box {
        background: #fff; border-radius: 12px; padding: 18px;
        border: 1px solid #e2e8f0; height: 100%;
        border-top: 4px solid #e2e8f0;
    }
    .pestel-form-box.political     { border-top-color: #805ad5; }
    .pestel-form-box.economic      { border-top-color: #38a169; }
    .pestel-form-box.social        { border-top-color: #3182ce; }
    .pestel-form-box.technological { border-top-color: #dd6b20; }
    .pestel-form-box.environmental { border-top-color: #38b2ac; }
    .pestel-form-box.legal         { border-top-color: #e53e3e; }


    .pestel-form-title {
        font-size: 14px; font-weight: 700; margin-bottom: 12px;
        display: flex; align-items: center; gap: 8px;
    }
    .pestel-form-box.political     .pestel-form-title { color: #805ad5; }
    .pestel-form-box.economic      .pestel-form-title { color: #38a169; }
    .pestel-form-box.social        .pestel-form-title { color: #3182ce; }
    .pestel-form-box.technological .pestel-form-title { color: #dd6b20; }
    .pestel-form-box.environmental .pestel-form-title { color: #38b2ac; }
    .pestel-form-box.legal         .pestel-form-title { color: #e53e3e; }
</style>
@endpush


@section('content')
<div class="container-fluid px-4">


    <a href="{{ route('strategic.pestel.index') }}" class="btn btn-outline-secondary mb-3">
        <i class="fas fa-arrow-right"></i> العودة للتحليل
    </a>


    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif


    <div class="card-custom">
        <h4 class="mb-4"><i class="fas fa-edit text-warning ml-2"></i>تعديل تحليل PESTEL</h4>


        <form method="POST" action="{{ route('strategic.pestel.update') }}">
            @csrf
            @method('PUT')


            <div class="row g-3">


                <div class="col-md-6">
                    <div class="pestel-form-box political">
                        <div class="pestel-form-title">
                            <i class="fas fa-landmark"></i> العوامل السياسية
                        </div>
                        <textarea name="political" class="form-control" rows="5"
                                  placeholder="اكتب كل عامل في سطر منفصل...">{{ old('political', $pestel['political'] ?? '') }}</textarea>
                    </div>
                </div>


                <div class="col-md-6">
                    <div class="pestel-form-box economic">
                        <div class="pestel-form-title">
                            <i class="fas fa-chart-line"></i> العوامل الاقتصادية
                        </div>
                        <textarea name="economic" class="form-control" rows="5"
                                  placeholder="اكتب كل عامل في سطر منفصل...">{{ old('economic', $pestel['economic'] ?? '') }}</textarea>
                    </div>
                </div>


                <div class="col-md-6">
                    <div class="pestel-form-box social">
                        <div class="pestel-form-title">
                            <i class="fas fa-users"></i> العوامل الاجتماعية
                        </div>
                        <textarea name="social" class="form-control" rows="5"
                                  placeholder="اكتب كل عامل في سطر منفصل...">{{ old('social', $pestel['social'] ?? '') }}</textarea>
                    </div>
                </div>


                <div class="col-md-6">
                    <div class="pestel-form-box technological">
                        <div class="pestel-form-title">
                            <i class="fas fa-microchip"></i> العوامل التقنية
                        </div>
                        <textarea name="technological" class="form-control" rows="5"
                                  placeholder="اكتب كل عامل في سطر منفصل...">{{ old('technological', $pestel['technological'] ?? '') }}</textarea>
                    </div>
                </div>


                <div class="col-md-6">
                    <div class="pestel-form-box environmental">
                        <div class="pestel-form-title">
                            <i class="fas fa-leaf"></i> العوامل البيئية
                        </div>
                        <textarea name="environmental" class="form-control" rows="5"
                                  placeholder="اكتب كل عامل في سطر منفصل...">{{ old('environmental', $pestel['environmental'] ?? '') }}</textarea>
                    </div>
                </div>


                <div class="col-md-6">
                    <div class="pestel-form-box legal">
                        <div class="pestel-form-title">
                            <i class="fas fa-balance-scale"></i> العوامل القانونية
                        </div>
                        <textarea name="legal" class="form-control" rows="5"
                                  placeholder="اكتب كل عامل في سطر منفصل...">{{ old('legal', $pestel['legal'] ?? '') }}</textarea>
                    </div>
                </div>


            </div>


            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn-gold">
                    <i class="fas fa-save"></i> حفظ التحليل
                </button>
                <a href="{{ route('strategic.pestel.index') }}" class="btn btn-secondary">
                    <i class="fas fa-times"></i> إلغاء
                </a>
            </div>
        </form>
    </div>


</div>
@endsection

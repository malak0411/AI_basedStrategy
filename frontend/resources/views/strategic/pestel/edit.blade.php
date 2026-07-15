@extends('layouts.app')

@section('title', 'تحرير تحليل PESTEL')

@section('content')
<div class="container-fluid px-4">
    <a href="{{ route('strategic.pestel.index') }}" class="btn btn-outline-secondary mb-3">
        <i class="fas fa-arrow-right"></i> العودة للعرض
    </a>

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card-custom">
        <h4 class="mb-4"><i class="fas fa-edit ml-2"></i>تحرير تحليل PESTEL</h4>

        <form method="POST" action="{{ route('strategic.pestel.update') }}">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col-md-6 mb-4">
                    <label class="form-label fw-bold">
                        <i class="fas fa-landmark text-secondary ml-1"></i> التحليل السياسي (Political)
                    </label>
                    <textarea name="political" class="form-control" rows="4"
                        placeholder="السياسات الحكومية، الاستقرار السياسي، القوانين واللوائح...">{{ $pestel['political'] ?? '' }}</textarea>
                </div>
                <div class="col-md-6 mb-4">
                    <label class="form-label fw-bold">
                        <i class="fas fa-chart-line text-success ml-1"></i> التحليل الاقتصادي (Economic)
                    </label>
                    <textarea name="economic" class="form-control" rows="4"
                        placeholder="النمو الاقتصادي، التضخم، أسعار الصرف، البطالة...">{{ $pestel['economic'] ?? '' }}</textarea>
                </div>
                <div class="col-md-6 mb-4">
                    <label class="form-label fw-bold">
                        <i class="fas fa-users text-info ml-1"></i> التحليل الاجتماعي (Social)
                    </label>
                    <textarea name="social" class="form-control" rows="4"
                        placeholder="الثقافة، التركيبة السكانية، التعليم، نمط الحياة...">{{ $pestel['social'] ?? '' }}</textarea>
                </div>
                <div class="col-md-6 mb-4">
                    <label class="form-label fw-bold">
                        <i class="fas fa-microchip text-purple ml-1"></i> التحليل التقني (Technological)
                    </label>
                    <textarea name="technological" class="form-control" rows="4"
                        placeholder="الابتكار، الأتمتة، البحث والتطوير، التحول الرقمي...">{{ $pestel['technological'] ?? '' }}</textarea>
                </div>
                <div class="col-md-6 mb-4">
                    <label class="form-label fw-bold">
                        <i class="fas fa-leaf text-success ml-1"></i> التحليل البيئي (Environmental)
                    </label>
                    <textarea name="environmental" class="form-control" rows="4"
                        placeholder="المناخ، الموارد الطبيعية، الاستدامة، التلوث...">{{ $pestel['environmental'] ?? '' }}</textarea>
                </div>
                <div class="col-md-6 mb-4">
                    <label class="form-label fw-bold">
                        <i class="fas fa-gavel text-warning ml-1"></i> التحليل القانوني (Legal)
                    </label>
                    <textarea name="legal" class="form-control" rows="4"
                        placeholder="القوانين، حقوق الملكية، العقود، الامتثال...">{{ $pestel['legal'] ?? '' }}</textarea>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn-gold btn-lg">
                        <i class="fas fa-save ml-1"></i> حفظ التحليل
                    </button>
                    <a href="{{ route('strategic.pestel.index') }}" class="btn btn-outline-secondary btn-lg mr-3">إلغاء</a>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

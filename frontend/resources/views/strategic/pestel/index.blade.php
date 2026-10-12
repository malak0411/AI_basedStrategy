@extends('layouts.app')


@section('title', 'تحليل PESTEL')


@push('styles')
<style>
    .pestel-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
    }
    .pestel-box {
        background: #fff; border-radius: 16px; padding: 22px;
        border: 1px solid #e2e8f0; min-height: 240px;
        position: relative; overflow: hidden;
        transition: all 0.3s;
    }
    .pestel-box:hover { box-shadow: 0 8px 25px rgba(0,0,0,0.08); transform: translateY(-2px); }


    .pestel-box.political     { border-top: 4px solid #805ad5; }
    .pestel-box.economic      { border-top: 4px solid #38a169; }
    .pestel-box.social        { border-top: 4px solid #3182ce; }
    .pestel-box.technological { border-top: 4px solid #dd6b20; }
    .pestel-box.environmental { border-top: 4px solid #38b2ac; }
    .pestel-box.legal         { border-top: 4px solid #e53e3e; }


    .pestel-title {
        font-size: 15px; font-weight: 700; margin-bottom: 14px;
        display: flex; align-items: center; gap: 10px;
    }
    .pestel-box.political     .pestel-title { color: #805ad5; }
    .pestel-box.economic      .pestel-title { color: #38a169; }
    .pestel-box.social        .pestel-title { color: #3182ce; }
    .pestel-box.technological .pestel-title { color: #dd6b20; }
    .pestel-box.environmental .pestel-title { color: #38b2ac; }
    .pestel-box.legal         .pestel-title { color: #e53e3e; }


    .pestel-icon {
        width: 38px; height: 38px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 18px;
    }
    .pestel-box.political     .pestel-icon { background: #e9d8fd; color: #553c9a; }
    .pestel-box.economic      .pestel-icon { background: #c6f6d5; color: #22543d; }
    .pestel-box.social        .pestel-icon { background: #bee3f8; color: #2c5282; }
    .pestel-box.technological .pestel-icon { background: #feebc8; color: #7b341e; }
    .pestel-box.environmental .pestel-icon { background: #b2f5ea; color: #234e52; }
    .pestel-box.legal         .pestel-icon { background: #fed7d7; color: #822727; }


    .pestel-content {
        color: #4a5568; line-height: 1.85; white-space: pre-line;
        font-size: 13px;
    }


    .pestel-empty {
        color: #a0aec0; font-style: italic; text-align: center;
        padding: 50px 15px; font-size: 12px;
    }


    .pestel-note {
        position: absolute; bottom: 10px; left: 12px;
        font-size: 10px; color: #cbd5e0; font-weight: 600;
        text-transform: uppercase; letter-spacing: 1.5px;
    }


    @media (max-width: 992px) {
        .pestel-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 576px) {
        .pestel-grid { grid-template-columns: 1fr; }
    }
</style>
@endpush


@section('content')
<div class="container-fluid px-4">


    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3><i class="fas fa-globe ml-2"></i>تحليل PESTEL</h3>
            <p class="text-muted mb-0">تحليل البيئة الخارجية السياسية والاقتصادية والاجتماعية والتقنية والبيئية والقانونية</p>
        </div>
        <a href="{{ route('strategic.pestel.edit') }}" class="btn-gold">
            <i class="fas fa-edit"></i> تعديل التحليل
        </a>
    </div>


    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif


    @if(!empty($pestel['updated_at']))
        <div class="mb-3 text-muted small">
            <i class="far fa-clock ml-1"></i> آخر تحديث: {{ \Carbon\Carbon::parse($pestel['updated_at'])->format('Y-m-d H:i') }}
        </div>
    @endif


    <div class="pestel-grid">


        <div class="pestel-box political">
            <div class="pestel-title">
                <div class="pestel-icon"><i class="fas fa-landmark"></i></div>
                العوامل السياسية
            </div>
            @if(!empty($pestel['political']))
                <div class="pestel-content">{{ $pestel['political'] }}</div>
            @else
                <div class="pestel-empty">لم يتم الإدخال</div>
            @endif
            <div class="pestel-note">Political</div>
        </div>


        <div class="pestel-box economic">
            <div class="pestel-title">
                <div class="pestel-icon"><i class="fas fa-chart-line"></i></div>
                العوامل الاقتصادية
            </div>
            @if(!empty($pestel['economic']))
                <div class="pestel-content">{{ $pestel['economic'] }}</div>
            @else
                <div class="pestel-empty">لم يتم الإدخال</div>
            @endif
            <div class="pestel-note">Economic</div>
        </div>


        <div class="pestel-box social">
            <div class="pestel-title">
                <div class="pestel-icon"><i class="fas fa-users"></i></div>
                العوامل الاجتماعية
            </div>
            @if(!empty($pestel['social']))
                <div class="pestel-content">{{ $pestel['social'] }}</div>
            @else
                <div class="pestel-empty">لم يتم الإدخال</div>
            @endif
            <div class="pestel-note">Social</div>
        </div>


        <div class="pestel-box technological">
            <div class="pestel-title">
                <div class="pestel-icon"><i class="fas fa-microchip"></i></div>
                العوامل التقنية
            </div>
            @if(!empty($pestel['technological']))
                <div class="pestel-content">{{ $pestel['technological'] }}</div>
            @else
                <div class="pestel-empty">لم يتم الإدخال</div>
            @endif
            <div class="pestel-note">Technological</div>
        </div>


        <div class="pestel-box environmental">
            <div class="pestel-title">
                <div class="pestel-icon"><i class="fas fa-leaf"></i></div>
                العوامل البيئية
            </div>
            @if(!empty($pestel['environmental']))
                <div class="pestel-content">{{ $pestel['environmental'] }}</div>
            @else
                <div class="pestel-empty">لم يتم الإدخال</div>
            @endif
            <div class="pestel-note">Environmental</div>
        </div>


        <div class="pestel-box legal">
            <div class="pestel-title">
                <div class="pestel-icon"><i class="fas fa-balance-scale"></i></div>
                العوامل القانونية
            </div>
            @if(!empty($pestel['legal']))
                <div class="pestel-content">{{ $pestel['legal'] }}</div>
            @else
                <div class="pestel-empty">لم يتم الإدخال</div>
            @endif
            <div class="pestel-note">Legal</div>
        </div>


    </div>


</div>
@endsection

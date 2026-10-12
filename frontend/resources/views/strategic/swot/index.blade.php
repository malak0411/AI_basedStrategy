@extends('layouts.app')


@section('title', 'تحليل SWOT')


@push('styles')
<style>
    .swot-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }
    .swot-box {
        background: #fff; border-radius: 16px; padding: 24px;
        border: 1px solid #e2e8f0; min-height: 280px;
        position: relative; overflow: hidden;
        transition: all 0.3s;
    }
    .swot-box:hover { box-shadow: 0 8px 25px rgba(0,0,0,0.08); }


    .swot-box.strengths     { border-top: 4px solid #38a169; }
    .swot-box.weaknesses    { border-top: 4px solid #e53e3e; }
    .swot-box.opportunities { border-top: 4px solid #3182ce; }
    .swot-box.threats       { border-top: 4px solid #dd6b20; }


    .swot-title {
        font-size: 16px; font-weight: 700; margin-bottom: 16px;
        display: flex; align-items: center; gap: 8px;
    }
    .swot-box.strengths     .swot-title { color: #38a169; }
    .swot-box.weaknesses    .swot-title { color: #e53e3e; }
    .swot-box.opportunities .swot-title { color: #3182ce; }
    .swot-box.threats       .swot-title { color: #dd6b20; }


    .swot-icon {
        width: 36px; height: 36px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 18px;
    }
    .swot-box.strengths     .swot-icon { background: #c6f6d5; color: #22543d; }
    .swot-box.weaknesses    .swot-icon { background: #fed7d7; color: #822727; }
    .swot-box.opportunities .swot-icon { background: #bee3f8; color: #2c5282; }
    .swot-box.threats       .swot-icon { background: #feebc8; color: #7b341e; }


    .swot-content {
        color: #4a5568; line-height: 1.9; white-space: pre-line;
        font-size: 14px;
    }


    .swot-empty {
        color: #a0aec0; font-style: italic; text-align: center;
        padding: 60px 20px; font-size: 13px;
    }


    .swot-note {
        position: absolute; bottom: 10px; left: 10px;
        font-size: 10px; color: #cbd5e0; font-weight: 600;
        text-transform: uppercase; letter-spacing: 1.5px;
    }


    @media (max-width: 768px) {
        .swot-grid { grid-template-columns: 1fr; }
    }
</style>
@endpush


@section('content')
<div class="container-fluid px-4">


    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3><i class="fas fa-chart-pie ml-2"></i>تحليل SWOT</h3>
            <p class="text-muted mb-0">تحليل نقاط القوة والضعف والفرص والتهديدات</p>
        </div>
        <a href="{{ route('strategic.swot.edit') }}" class="btn-gold">
            <i class="fas fa-edit"></i> تعديل التحليل
        </a>
    </div>


    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif


    @if(!empty($swot['updated_at']))
        <div class="mb-3 text-muted small">
            <i class="far fa-clock ml-1"></i> آخر تحديث: {{ \Carbon\Carbon::parse($swot['updated_at'])->format('Y-m-d H:i') }}
        </div>
    @endif


    <div class="swot-grid">


        <div class="swot-box strengths">
            <div class="swot-title">
                <div class="swot-icon"><i class="fas fa-plus-circle"></i></div>
                نقاط القوة
            </div>
            @if(!empty($swot['strengths']))
                <div class="swot-content">{{ $swot['strengths'] }}</div>
            @else
                <div class="swot-empty"><i class="fas fa-inbox" style="font-size: 32px; display: block; margin-bottom: 10px;"></i>لم يتم إدخال نقاط القوة بعد</div>
            @endif
            <div class="swot-note">Strengths</div>
        </div>


        <div class="swot-box weaknesses">
            <div class="swot-title">
                <div class="swot-icon"><i class="fas fa-minus-circle"></i></div>
                نقاط الضعف
            </div>
            @if(!empty($swot['weaknesses']))
                <div class="swot-content">{{ $swot['weaknesses'] }}</div>
            @else
                <div class="swot-empty"><i class="fas fa-inbox" style="font-size: 32px; display: block; margin-bottom: 10px;"></i>لم يتم إدخال نقاط الضعف بعد</div>
            @endif
            <div class="swot-note">Weaknesses</div>
        </div>


        <div class="swot-box opportunities">
            <div class="swot-title">
                <div class="swot-icon"><i class="fas fa-lightbulb"></i></div>
                الفرص
            </div>
            @if(!empty($swot['opportunities']))
                <div class="swot-content">{{ $swot['opportunities'] }}</div>
            @else
                <div class="swot-empty"><i class="fas fa-inbox" style="font-size: 32px; display: block; margin-bottom: 10px;"></i>لم يتم إدخال الفرص بعد</div>
            @endif
            <div class="swot-note">Opportunities</div>
        </div>


        <div class="swot-box threats">
            <div class="swot-title">
                <div class="swot-icon"><i class="fas fa-exclamation-triangle"></i></div>
                التهديدات
            </div>
            @if(!empty($swot['threats']))
                <div class="swot-content">{{ $swot['threats'] }}</div>
            @else
                <div class="swot-empty"><i class="fas fa-inbox" style="font-size: 32px; display: block; margin-bottom: 10px;"></i>لم يتم إدخال التهديدات بعد</div>
            @endif
            <div class="swot-note">Threats</div>
        </div>


    </div>


</div>
@endsection

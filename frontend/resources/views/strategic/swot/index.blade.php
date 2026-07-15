@extends('layouts.app')

@section('title', 'تحليل SWOT')

@push('styles')
<style>
    .swot-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .swot-card { border-radius: 16px; padding: 24px; min-height: 200px; color: #fff; position: relative; }
    .swot-card h4 { font-weight: 800; margin-bottom: 16px; font-size: 20px; }
    .swot-card h4 i { margin-left: 8px; }
    .swot-card p { line-height: 1.8; font-size: 15px; white-space: pre-wrap; }
    .swot-strengths { background: linear-gradient(135deg, #38a169, #2f855a); }
    .swot-weaknesses { background: linear-gradient(135deg, #e53e3e, #c53030); }
    .swot-opportunities { background: linear-gradient(135deg, #3182ce, #2b6cb0); }
    .swot-threats { background: linear-gradient(135deg, #dd6b20, #c05621); }
    @media (max-width: 768px) { .swot-grid { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3><i class="fas fa-chess-board ml-2"></i>تحليل SWOT</h3>
        <a href="{{ route('strategic.swot.edit') }}" class="btn-gold">
            <i class="fas fa-edit"></i> تعديل التحليل
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="swot-grid">
        <div class="swot-card swot-strengths">
            <h4><i class="fas fa-check-circle"></i> نقاط القوة (Strengths)</h4>
            <p>{{ !empty($swot['strengths']) ? $swot['strengths'] : 'لم يتم إضافة نقاط قوة بعد' }}</p>
        </div>
        <div class="swot-card swot-weaknesses">
            <h4><i class="fas fa-exclamation-circle"></i> نقاط الضعف (Weaknesses)</h4>
            <p>{{ !empty($swot['weaknesses']) ? $swot['weaknesses'] : 'لم يتم إضافة نقاط ضعف بعد' }}</p>
        </div>
        <div class="swot-card swot-opportunities">
            <h4><i class="fas fa-lightbulb"></i> الفرص (Opportunities)</h4>
            <p>{{ !empty($swot['opportunities']) ? $swot['opportunities'] : 'لم يتم إضافة فرص بعد' }}</p>
        </div>
        <div class="swot-card swot-threats">
            <h4><i class="fas fa-exclamation-triangle"></i> التهديدات (Threats)</h4>
            <p>{{ !empty($swot['threats']) ? $swot['threats'] : 'لم يتم إضافة تهديدات بعد' }}</p>
        </div>
    </div>
</div>
@endsection

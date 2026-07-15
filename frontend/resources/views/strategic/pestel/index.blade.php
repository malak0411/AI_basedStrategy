@extends('layouts.app')

@section('title', 'تحليل PESTEL')

@push('styles')
<style>
    .pestel-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
    .pestel-card { border-radius: 16px; padding: 24px; color: #fff; min-height: 180px; position: relative; }
    .pestel-card h4 { font-weight: 800; margin-bottom: 12px; font-size: 18px; }
    .pestel-card h4 i { margin-left: 8px; }
    .pestel-card p { line-height: 1.7; font-size: 14px; white-space: pre-wrap; }
    .pestel-political { background: linear-gradient(135deg, #4a5568, #2d3748); }
    .pestel-economic { background: linear-gradient(135deg, #38a169, #2f855a); }
    .pestel-social { background: linear-gradient(135deg, #3182ce, #2b6cb0); }
    .pestel-technological { background: linear-gradient(135deg, #805ad5, #6b46c1); }
    .pestel-environmental { background: linear-gradient(135deg, #38a169, #276749); }
    .pestel-legal { background: linear-gradient(135deg, #dd6b20, #c05621); }
    @media (max-width: 992px) { .pestel-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 768px) { .pestel-grid { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3><i class="fas fa-globe ml-2"></i>تحليل PESTEL</h3>
        <a href="{{ route('strategic.pestel.edit') }}" class="btn-gold">
            <i class="fas fa-edit"></i> تعديل التحليل
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @php
        $categories = [
            'political' => ['سياسي', 'fa-landmark', 'pestel-political'],
            'economic' => ['اقتصادي', 'fa-chart-line', 'pestel-economic'],
            'social' => ['اجتماعي', 'fa-users', 'pestel-social'],
            'technological' => ['تقني', 'fa-microchip', 'pestel-technological'],
            'environmental' => ['بيئي', 'fa-leaf', 'pestel-environmental'],
            'legal' => ['قانوني', 'fa-gavel', 'pestel-legal'],
        ];
    @endphp

    <div class="pestel-grid">
        @foreach($categories as $key => [$label, $icon, $class])
        <div class="pestel-card {{ $class }}">
            <h4><i class="fas {{ $icon }}"></i> {{ $label }}</h4>
            <p>{{ !empty($pestel[$key]) ? $pestel[$key] : 'لم يتم الإضافة بعد' }}</p>
        </div>
        @endforeach
    </div>
</div>
@endsection

@extends('layouts.app')

@section('title', 'توصيات الذكاء الاصطناعي')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3><i class="fas fa-lightbulb ml-2 text-warning"></i>توصيات الذكاء الاصطناعي</h3>
        <a href="{{ route('ai.dashboard') }}" class="btn btn-outline-secondary">
            <i class="fas fa-chart-bar"></i> لوحة AI
        </a>
    </div>

    @if(empty($recommendations))
        <div class="card-custom text-center py-5">
            <i class="fas fa-robot fa-3x text-muted mb-3"></i>
            <h5>لا توجد توصيات</h5>
            <p class="text-muted">ستظهر هنا التوصيات الذكية عند توفر بيانات كافية</p>
        </div>
    @else
        @foreach($recommendations as $rec)
        <div class="card-custom mb-3">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h5>
                        <a href="{{ route('ai.recommendations.show', $rec['id']) }}" class="text-decoration-none">
                            {{ $rec['title'] ?? 'توصية' }}
                        </a>
                    </h5>
                    <p class="text-muted">{{ Str::limit($rec['description'] ?? '', 150) }}</p>
                </div>
                <div>
                    <span class="badge bg-{{ ($rec['priority'] ?? 'medium') === 'high' ? 'danger' : 'info' }}">
                        {{ ($rec['priority'] ?? 'medium') === 'high' ? 'عاجل' : 'عادي' }}
                    </span>
                    <br>
                    <small class="text-muted">{{ $rec['category'] ?? '' }}</small>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-2">
                <small class="text-muted">{{ $rec['created_at'] ?? '' }}</small>
                <a href="{{ route('ai.recommendations.show', $rec['id']) }}" class="btn btn-sm btn-outline-info">
                    <i class="fas fa-eye"></i> التفاصيل
                </a>
            </div>
        </div>
        @endforeach
    @endif
</div>
@endsection

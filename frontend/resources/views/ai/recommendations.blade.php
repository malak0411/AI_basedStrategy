@extends('layouts.app')

@section('title', 'توصيات الذكاء الاصطناعي')

@section('content')
<div class="container-fluid px-4">
    <h3 class="mb-4"><i class="fas fa-robot ml-2"></i>توصيات الذكاء الاصطناعي</h3>

    @if(empty($recommendations))
        <div class="card-custom text-center py-5">
            <i class="fas fa-robot fa-3x text-muted mb-3"></i>
            <h5>لا توجد توصيات حالياً</h5>
            <p class="text-muted">ستظهر هنا التوصيات الذكية عند توفر بيانات كافية</p>
        </div>
    @else
        @foreach($recommendations as $rec)
        <div class="card-custom mb-3">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h5>{{ $rec['title'] ?? 'توصية' }}</h5>
                    <p class="text-muted">{{ $rec['description'] ?? '' }}</p>
                </div>
                <span class="badge bg-{{ ($rec['priority'] ?? 'medium') === 'high' ? 'danger' : 'info' }}">
                    {{ ($rec['priority'] ?? 'medium') === 'high' ? 'عاجل' : 'عادي' }}
                </span>
            </div>
        </div>
        @endforeach
    @endif
</div>
@endsection

@extends('layouts.app')

@section('title', 'الركائز الاستراتيجية')

@section('content')
<div class="container-fluid">
    <h3><i class="fas fa-chess-queen ml-2"></i>الركائز الاستراتيجية</h3>

    @if(empty($pillars))
        <div class="card-custom text-center py-5 mt-4">
            <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
            <p>لا توجد ركائز استراتيجية حالياً</p>
        </div>
    @else
        <div class="row mt-4">
            @foreach($pillars as $pillar)
                <div class="col-md-4 mb-3">
                    <div class="card-custom">
                        <h5>{{ $pillar['name'] ?? $pillar['title'] ?? 'ركيزة' }}</h5>
                        <p class="text-muted small">{{ $pillar['description'] ?? '' }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection

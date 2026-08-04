@extends('layouts.app')

@section('title', 'الركائز الاستراتيجية')

@push('styles')
<style>
    .pillar-card {
        background: #fff; border-radius: 16px; padding: 24px; height: 100%;
        border: 1px solid #e2e8f0; transition: all 0.3s ease; cursor: pointer;
        border-right: 5px solid #d4af37;
    }
    .pillar-card:hover { box-shadow: 0 8px 25px rgba(0,0,0,0.08); transform: translateY(-2px); }
    .pillar-card.inactive { opacity: 0.6; border-right-color: #ccc; }
    .pillar-number {
        width: 40px; height: 40px; border-radius: 50%; background: #0a2e5c; color: #d4af37;
        display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 18px;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3><i class="fas fa-chess-queen ml-2"></i>الركائز الاستراتيجية</h3>
            <p class="text-muted mb-0">اضغط على أي ركيزة لعرض أهدافها</p>
        </div>
        <button class="btn-gold" data-bs-toggle="modal" data-bs-target="#addPillarModal">
            <i class="fas fa-plus"></i> ركيزة جديدة
        </button>
    </div>

    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif

    @if(empty($pillars))
    <div class="card-custom text-center py-5">
        <i class="fas fa-chess-queen fa-4x text-muted mb-3"></i>
        <h5>لا توجد ركائز استراتيجية</h5>
        <button class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#addPillarModal">إضافة ركيزة</button>
    </div>
    @else
    <div class="row">
        @foreach($pillars as $pillar)
        @php $active = $pillar['is_active'] ?? true; @endphp
        <div class="col-md-4 mb-4">
            <div class="pillar-card {{ $active ? '' : 'inactive' }}" onclick="window.location='{{ route('strategic.pillars.show', $pillar['id']) }}'">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div class="pillar-number">{{ $pillar['order_index'] ?? $loop->iteration }}</div>
                    <span class="badge bg-{{ $active ? 'success' : 'secondary' }}">{{ $active ? 'نشط' : 'غير نشط' }}</span>
                </div>
                <h5 class="fw-bold mb-2">{{ $pillar['name'] ?? $pillar['title'] ?? '' }}</h5>
                <p class="text-muted small">{{ Str::limit($pillar['description'] ?? '', 120) }}</p>
            </div>
        </div>
        @endforeach
    </div>
    @endif

   {{-- @include('strategic.pillars._add_modal')--}}
</div>
@endsection

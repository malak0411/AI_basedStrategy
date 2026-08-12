@extends('layouts.app')

@section('title', 'المبادرات')

@push('styles')
<style>
    .initiative-card {
        background: #fff; border-radius: 16px; padding: 24px; height: 100%;
        border: 1px solid #e2e8f0; transition: all 0.3s ease; cursor: pointer;
        border-right: 5px solid #dd6b20;
    }
    .initiative-card:hover { box-shadow: 0 8px 25px rgba(0,0,0,0.08); transform: translateY(-2px); }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3><i class="fas fa-lightbulb ml-2"></i>المبادرات</h3>
            <p class="text-muted mb-0">اضغط على أي مبادرة لعرض مهامها الرئيسية</p>
        </div>
        <a href="{{ route('strategic.initiatives.create') }}" class="btn-gold">
            <i class="fas fa-plus"></i> مبادرة جديدة
        </a>
    </div>

    @if(empty($initiatives))
    <div class="card-custom text-center py-5"><h5>لا توجد مبادرات</h5></div>
    @else
    <div class="row">
        @foreach($initiatives as $initiative)
        <div class="col-md-4 mb-4">
            <div class="initiative-card" onclick="window.location='{{ route('strategic.initiatives.show', $initiative['id']) }}'">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <span class="badge bg-info mb-2">{{ $initiative['program_name'] ?? '' }}</span>
                    <div class="initiative-actions" onclick="event.stopPropagation()">
                        <button class="btn btn-sm btn-outline-primary" onclick="editInitiative({{ json_encode($initiative) }})" title="تعديل"><i class="fas fa-edit"></i></button>
                    </div>
                </div>
                <h5 class="fw-bold mb-2">{{ $initiative['name'] ?? $initiative['title'] ?? '' }}</h5>
                <p class="text-muted small">{{ Str::limit($initiative['description'] ?? '', 120) }}</p>
                <small class="text-muted">الأولوية: {{ $initiative['priority'] ?? '-' }} | {{ $initiative['start_date'] ?? '' }}</small>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>
@endsection

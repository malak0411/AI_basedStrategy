@extends('layouts.app')

@section('title', 'البرامج')

@push('styles')
<style>
    .program-card {
        background: #fff; border-radius: 16px; padding: 24px; height: 100%;
        border: 1px solid #e2e8f0; transition: all 0.3s ease; cursor: pointer;
        border-right: 5px solid #38a169;
    }
    .program-card:hover { box-shadow: 0 8px 25px rgba(0,0,0,0.08); transform: translateY(-2px); }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3><i class="fas fa-project-diagram ml-2"></i>البرامج</h3>
            <p class="text-muted mb-0">اضغط على أي برنامج لعرض مبادراته</p>
        </div>
        <a href="{{ route('strategic.programs.create') }}" class="btn-gold">
            <i class="fas fa-plus"></i> برنامج جديد
        </a>
    </div>

    @if(empty($programs))
    <div class="card-custom text-center py-5">
        <h5>لا توجد برامج</h5>
    </div>
    @else
    <div class="row">
        @foreach($programs as $program)
        <div class="col-md-4 mb-4">
            <div class="program-card" onclick="window.location='{{ route('strategic.programs.show', $program['id']) }}'">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <span class="badge bg-info mb-2">{{ $program['goal_name'] ?? '' }}</span>
                <div class="program-actions" onclick="event.stopPropagation()">
                    <button class="btn btn-sm btn-outline-primary" onclick="editProgram({{ json_encode($program) }})" title="تعديل"><i class="fas fa-edit"></i></button>
                </div>
            </div>
                <h5 class="fw-bold mb-2">{{ $program['name'] ?? $program['title'] ?? '' }}</h5>
                <p class="text-muted small">{{ Str::limit($program['description'] ?? '', 120) }}</p>
                <small class="text-muted">الميزانية: {{ number_format($program['budget'] ?? 0) }}</small>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>
@endsection

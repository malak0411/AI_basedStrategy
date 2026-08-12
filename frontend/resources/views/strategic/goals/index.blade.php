@extends('layouts.app')

@section('title', 'الأهداف الاستراتيجية')

@push('styles')
<style>
    .goal-card {
        background: #fff; border-radius: 16px; padding: 24px; height: 100%;
        border: 1px solid #e2e8f0; transition: all 0.3s ease; cursor: pointer;
        border-right: 5px solid #3182ce;
    }
    .goal-card:hover { box-shadow: 0 8px 25px rgba(0,0,0,0.08); transform: translateY(-2px); }
    .goal-card.inactive { opacity: 0.6; border-right-color: #ccc; }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3><i class="fas fa-bullseye ml-2"></i>الأهداف الاستراتيجية</h3>
            <p class="text-muted mb-0">اضغط على أي هدف لعرض برامجه</p>
        </div>
        <a href="{{ route('strategic.goals.create') }}" class="btn-gold">
            <i class="fas fa-plus"></i> هدف جديد
        </a>
    </div>

    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif

    @if(empty($goals))
    <div class="card-custom text-center py-5">
        <i class="fas fa-bullseye fa-4x text-muted mb-3"></i>
        <h5>لا توجد أهداف</h5>
        <a href="{{ route('strategic.goals.create') }}" class="btn btn-primary mt-3">إضافة هدف</a>
    </div>
    @else
    <div class="row">
        @foreach($goals as $goal)
        @php $active = ($goal['status'] ?? 'active') == 'active'; @endphp
        <div class="col-md-4 mb-4">
            <div class="goal-card {{ $active ? '' : 'inactive' }}" onclick="window.location='{{ route('strategic.goals.show', $goal['id']) }}'">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <span class="badge bg-info">{{ $goal['pillar_name'] ?? '' }}</span>
                    <span class="badge bg-{{ $active ? 'success' : 'secondary' }}">{{ $active ? 'نشط' : 'غير نشط' }}</span>
                    <div class="goal-actions" onclick="event.stopPropagation()">
                        <button class="btn btn-sm btn-outline-primary" onclick="window.location='{{ route('strategic.goals.edit', $goal['id']) }}'"title="تعديل"><i class="fas fa-edit"></i></button>
                    </div>
                </div>
                <h5 class="fw-bold mb-2">{{ $goal['name'] ?? $goal['title'] ?? '' }}</h5>
                <p class="text-muted small">{{ Str::limit($goal['description'] ?? '', 120) }}</p>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>
@endsection
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
    .status-badge { padding: 5px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; }
    .progress-bar-custom { height: 6px; background: #edf2f7; border-radius: 3px; overflow: hidden; }
    .progress-bar-fill { height: 100%; background: linear-gradient(90deg, #38a169, #48bb78); }
</style>
@endpush


@section('content')
<div class="container-fluid px-4">


    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3><i class="fas fa-lightbulb ml-2"></i>المبادرات</h3>
            <p class="text-muted mb-0">اضغط على أي مبادرة لعرض تفاصيلها ومهامها الرئيسية</p>
        </div>
        <a href="{{ route('strategic.initiatives.create') }}" class="btn-gold">
            <i class="fas fa-plus"></i> مبادرة جديدة
        </a>
    </div>


    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif


    @if(empty($initiatives))
        <div class="card-custom text-center py-5"><h5>لا توجد مبادرات</h5></div>
    @else
    <div class="row">
        @foreach($initiatives as $initiative)
        @php
            $statusName  = $initiative['status_name'] ?? 'غير محدد';
            $statusColor = $initiative['status_color'] ?? '#6c757d';
            $progress    = (int) ($initiative['progress'] ?? 0);
        @endphp
        <div class="col-md-4 mb-4">
            <div class="initiative-card" onclick="window.location='{{ route('strategic.initiatives.show', $initiative['id']) }}'">


                <div class="d-flex justify-content-between align-items-start mb-3">
                    <span class="badge bg-info">{{ $initiative['program_name'] ?? '—' }}</span>
                    <div class="d-flex align-items-center gap-2" onclick="event.stopPropagation()">
                        <span class="status-badge"
                              style="background: {{ $statusColor }}22; color: {{ $statusColor }}; border: 1px solid {{ $statusColor }}55;">
                            {{ $statusName }}
                        </span>
                        <a href="{{ route('strategic.initiatives.edit', $initiative['id']) }}"
                           class="btn btn-sm btn-outline-primary" title="تعديل">
                            <i class="fas fa-edit"></i>
                        </a>
                    </div>
                </div>


                <h5 class="fw-bold mb-2">{{ $initiative['name'] ?? $initiative['title'] ?? '' }}</h5>
                <p class="text-muted small mb-3">{{ Str::limit($initiative['description'] ?? '', 120) }}</p>


                <div class="small text-muted mb-3">
                    <div><i class="fas fa-flag ml-1 text-warning"></i>
                        الركيزة: <strong>{{ $initiative['pillar_name'] ?? '—' }}</strong>
                    </div>
                    <div><i class="fas fa-bullseye ml-1 text-danger"></i>
                        الهدف: <strong>{{ $initiative['goal_name'] ?? '—' }}</strong>
                    </div>
                </div>


                <div class="mb-3">
                    <div class="d-flex justify-content-between small mb-1">
                        <span class="text-muted">نسبة التقدم</span>
                        <strong class="text-success">{{ $progress }}%</strong>
                    </div>
                    <div class="progress-bar-custom">
                        <div class="progress-bar-fill" style="width: {{ $progress }}%"></div>
                    </div>
                </div>


                <div class="d-flex justify-content-between small text-muted border-top pt-2">
                    <span><i class="fas fa-coins text-warning ml-1"></i>
                        {{ number_format($initiative['budget_estimate'] ?? 0) }}
                    </span>
                    <span><i class="fas fa-tasks text-primary ml-1"></i>
                        {{ $initiative['major_tasks_count'] ?? 0 }} مهمة
                    </span>
                </div>


            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>
@endsection

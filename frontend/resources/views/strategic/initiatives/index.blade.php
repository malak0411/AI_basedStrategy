@extends('layouts.app')


@section('title', 'المبادرات')


@push('styles')
<style>
    .initiative-card {
        background: #fff; border-radius: 16px; padding: 24px; height: 100%;
        border: 1px solid #e2e8f0; transition: all 0.3s ease; cursor: pointer;
        border-right: 5px solid #dd6b20; position: relative; overflow: hidden;
    }
    .initiative-card:hover { box-shadow: 0 8px 25px rgba(0,0,0,0.08); transform: translateY(-2px); }


    .empty-overlay {
        position: absolute;
        inset: 0;
        background: rgba(255, 255, 255, 0.97);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 24px;
        z-index: 10;
        border-radius: 16px;
    }
    .empty-overlay i  { color: #cbd5e0; }
    .empty-overlay h5 { color: #2d3748; }
    .empty-overlay p  { color: #4a5568; }
    .empty-overlay small { color: #a0aec0; }


    .status-badge { padding: 5px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; }
    .progress-bar-custom { height: 6px; background: #edf2f7; border-radius: 3px; overflow: hidden; }
    .progress-bar-fill { height: 100%; background: linear-gradient(90deg, #38a169, #48bb78); }
</style>
@endpush


@section('content')
<div class="container-fluid px-4">


    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h3><i class="fas fa-lightbulb ml-2"></i>المبادرات</h3>
        <p class="text-muted mb-0">اضغط على أي مبادرة لعرض تفاصيلها ومهامها الرئيسية</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('strategic.initiatives.kanban') }}" class="btn btn-outline-primary">
            <i class="fas fa-columns"></i> إدارة الحالات (كانبان)
        </a>
        <a href="{{ route('strategic.initiatives.create') }}" class="btn-gold">
            <i class="fas fa-plus"></i> مبادرة جديدة
        </a>
    </div>
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
            $progress    = (int)($initiative['progress'] ?? 0);
        @endphp
        <div class="col-md-4 mb-4">
            <div class="initiative-card" onclick="window.location='{{ route('strategic.initiatives.show', $initiative['id']) }}'">


                @if((int)($initiative['major_tasks_count'] ?? 0) === 0)
                    <div class="empty-overlay" onclick="event.stopPropagation()">
                        <i class="fas fa-tasks fa-3x mb-3"></i>
                        <h5 class="fw-bold mb-2">{{ $initiative['name'] ?? $initiative['title'] ?? '' }}</h5>
                        <p class="mb-1 fw-semibold">لم يتم إضافة أي مهمة رئيسية إلى هذه المبادرة بعد</p>
                        <small class="mb-3 d-block">ابدأ بإضافة أول مهمة رئيسية لهذه المبادرة</small>
                        <a href="{{ route('strategic.initiatives.show', $initiative['id']) }}" class="btn btn-sm btn-gold">
                            <i class="fas fa-plus ml-1"></i> إضافة مهمة رئيسية
                        </a>
                    </div>
                @endif


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

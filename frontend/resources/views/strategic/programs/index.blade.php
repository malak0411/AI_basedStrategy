@extends('layouts.app')


@section('title', 'البرامج')


@push('styles')
<style>
    .program-card {
        background: #fff; border-radius: 16px; padding: 24px; height: 100%;
        border: 1px solid #e2e8f0; transition: all 0.3s ease; cursor: pointer;
        border-right: 5px solid #3182ce; position: relative; overflow: hidden;
    }
    .program-card:hover { box-shadow: 0 8px 25px rgba(0,0,0,0.08); transform: translateY(-2px); }


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
    .progress-bar-fill { height: 100%; background: linear-gradient(90deg, #3182ce, #4299e1); }
    .mini-stat {
        display: flex; align-items: center; gap: 6px;
        font-size: 11px; color: #4a5568; padding: 3px 0;
    }
</style>
@endpush


@section('content')
<div class="container-fluid px-4">


    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h3><i class="fas fa-project-diagram ml-2"></i>البرامج</h3>
        <p class="text-muted mb-0">اضغط على أي برنامج لعرض تفاصيله ومبادراته</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('strategic.programs.kanban') }}" class="btn btn-outline-primary">
            <i class="fas fa-columns"></i> إدارة الحالات (كانبان)
        </a>
        <a href="{{ route('strategic.programs.create') }}" class="btn-gold">
            <i class="fas fa-plus"></i> برنامج جديد
        </a>
    </div>
</div>



    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif


    @if(empty($programs))
        <div class="card-custom text-center py-5"><h5>لا توجد برامج</h5></div>
    @else
    <div class="row">
        @foreach($programs as $program)
        @php
            $statusName  = $program['status_name'] ?? 'غير محدد';
            $statusColor = $program['status_color'] ?? '#6c757d';
            $progress    = (int)($program['progress'] ?? 0);
            $statusesCount = $program['statuses_count'] ?? [];
        @endphp
        <div class="col-md-4 mb-4">
            <div class="program-card" onclick="window.location='{{ route('strategic.programs.show', $program['id']) }}'">


                @if((int)($program['initiatives_count'] ?? 0) === 0)
                    <div class="empty-overlay" onclick="event.stopPropagation()">
                        <i class="fas fa-lightbulb fa-3x mb-3"></i>
                        <h5 class="fw-bold mb-2">{{ $program['name'] ?? $program['title'] ?? '' }}</h5>
                        <p class="mb-1 fw-semibold">لم يتم إضافة أي مبادرة إلى هذا البرنامج بعد</p>
                        <small class="mb-3 d-block">ابدأ بإضافة أول مبادرة لهذا البرنامج</small>
                        <a href="{{ route('strategic.programs.show', $program['id']) }}" class="btn btn-sm btn-gold">
                            <i class="fas fa-plus ml-1"></i> إضافة مبادرة
                        </a>
                    </div>
                @endif


                <div class="d-flex justify-content-between align-items-start mb-3">
                    <span class="badge bg-primary">{{ $program['goal_name'] ?? '—' }}</span>
                    <div class="d-flex align-items-center gap-2" onclick="event.stopPropagation()">
                        <span class="status-badge"
                              style="background: {{ $statusColor }}22; color: {{ $statusColor }}; border: 1px solid {{ $statusColor }}55;">
                            {{ $statusName }}
                        </span>
                        <a href="{{ route('strategic.programs.edit', $program['id']) }}"
                           class="btn btn-sm btn-outline-primary" title="تعديل">
                            <i class="fas fa-edit"></i>
                        </a>
                    </div>
                </div>


                <h5 class="fw-bold mb-2">{{ $program['name'] ?? $program['title'] ?? '' }}</h5>
                <p class="text-muted small mb-3">{{ Str::limit($program['description'] ?? '', 120) }}</p>


                <div class="small text-muted mb-3">
                    <div><i class="fas fa-flag ml-1 text-warning"></i>
                        الركيزة: <strong>{{ $program['pillar_name'] ?? '—' }}</strong>
                    </div>
                    <div><i class="fas fa-bullseye ml-1 text-danger"></i>
                        الهدف: <strong>{{ $program['goal_name'] ?? '—' }}</strong>
                    </div>
                </div>


                <div class="mb-3">
                    <div class="d-flex justify-content-between small mb-1">
                        <span class="text-muted">نسبة التقدم</span>
                        <strong class="text-primary">{{ $progress }}%</strong>
                    </div>
                    <div class="progress-bar-custom">
                        <div class="progress-bar-fill" style="width: {{ $progress }}%"></div>
                    </div>
                </div>


                <div class="row small text-muted border-top pt-2 mb-2">
                    <div class="col-6">
                        <span><i class="fas fa-lightbulb text-info ml-1"></i>
                            {{ $program['initiatives_count'] ?? 0 }} مبادرة
                        </span>
                    </div>
                    <div class="col-6 text-end">
                        <span><i class="fas fa-tasks text-primary ml-1"></i>
                            {{ $program['major_tasks_count'] ?? 0 }} مهمة رئيسية
                        </span>
                    </div>
                </div>


                <div class="row small border-top pt-2">
                    <div class="col-6">
                        <span class="text-success"><i class="fas fa-check-circle ml-1"></i>
                            {{ $statusesCount['completed'] ?? 0 }} مكتملة
                        </span>
                    </div>
                    <div class="col-6 text-end">
                        <span class="text-info"><i class="fas fa-play-circle ml-1"></i>
                            {{ $statusesCount['in_progress'] ?? 0 }} قيد التنفيذ
                        </span>
                    </div>
                </div>


                <div class="d-flex justify-content-between small text-muted border-top pt-2 mt-2">
                    <span><i class="fas fa-coins text-warning ml-1"></i>
                        {{ number_format($program['budget'] ?? 0) }}
                    </span>
                    <span><i class="fas fa-cog text-secondary ml-1"></i>
                        {{ $program['total_operational_tasks'] ?? 0 }} تشغيلية
                    </span>
                </div>


            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>
@endsection

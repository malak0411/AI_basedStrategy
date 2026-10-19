@extends('layouts.app')


@section('title', 'الأهداف الاستراتيجية')


@push('styles')
<style>
    .goal-card {
        background: #fff; border-radius: 16px; padding: 24px; height: 100%;
        border: 1px solid #e2e8f0; transition: all 0.3s ease; cursor: pointer;
        border-right: 5px solid #e53e3e; position: relative; overflow: hidden;
    }
    .goal-card:hover { box-shadow: 0 8px 25px rgba(0,0,0,0.08); transform: translateY(-2px); }


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


    .progress-bar-custom { height: 6px; background: #edf2f7; border-radius: 3px; overflow: hidden; }
    .progress-bar-fill { height: 100%; background: linear-gradient(90deg, #e53e3e, #fc8181); }
    .progress-bar-kpi { height: 6px; background: #edf2f7; border-radius: 3px; overflow: hidden; }
    .progress-bar-kpi-fill { height: 100%; background: linear-gradient(90deg, #805ad5, #b794f4); }


    .kpi-badge {
        display: inline-block; padding: 2px 8px; border-radius: 10px;
        font-size: 10px; font-weight: 600; margin: 1px;
    }
    .kpi-achieved  { background: #c6f6d5; color: #22543d; }
    .kpi-on-track  { background: #bee3f8; color: #2c5282; }
    .kpi-at-risk   { background: #fefcbf; color: #744210; }
    .kpi-off-track { background: #fed7d7; color: #822727; }
    .kpi-no-data   { background: #e2e8f0; color: #4a5568; }


    .days-badge {
        display: inline-block; padding: 4px 10px; border-radius: 10px;
        font-size: 11px; font-weight: 600;
    }
    .days-ok      { background: #c6f6d5; color: #22543d; }
    .days-warning { background: #fefcbf; color: #744210; }
    .days-danger  { background: #fed7d7; color: #822727; }
</style>
@endpush


@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3><i class="fas fa-bullseye ml-2"></i>الأهداف الاستراتيجية</h3>
            <p class="text-muted mb-0">اضغط على أي هدف لعرض تفاصيله ومؤشراته وبرامجه</p>
        </div>
        <a href="{{ route('strategic.goals.create') }}" class="btn-gold">
            <i class="fas fa-plus"></i> هدف جديد
        </a>
    </div>


    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif


    @if(empty($goals))
        <div class="card-custom text-center py-5"><h5>لا توجد أهداف</h5></div>
    @else
    <div class="row">
        @foreach($goals as $goal)
        @php
            $progress    = (int)($goal['progress'] ?? 0);
            $kpiProgress = (int)($goal['kpi_progress'] ?? 0);
            $days        = $goal['days_remaining'] ?? null;
            $daysClass   = 'days-ok';
            if ($days !== null) {
                if ($days < 0)       $daysClass = 'days-danger';
                elseif ($days < 30)  $daysClass = 'days-warning';
            }
        @endphp
        <div class="col-md-4 mb-4">
            <div class="goal-card" onclick="window.location='{{ route('strategic.goals.show', $goal['id']) }}'">


                @if((int)($goal['programs_count'] ?? 0) === 0)
                    <div class="empty-overlay" onclick="event.stopPropagation()">
                        <i class="fas fa-folder-open fa-3x mb-3"></i>
                        <h5 class="fw-bold mb-2">{{ $goal['title'] ?? '' }}</h5>
                        <p class="mb-1 fw-semibold">لم يتم إضافة أي برنامج إلى هذا الهدف بعد</p>
                        <small class="mb-3 d-block">ابدأ بإضافة أول برنامج لهذا الهدف</small>
                        <a href="{{ route('strategic.goals.show', $goal['id']) }}" class="btn btn-sm btn-gold">
                            <i class="fas fa-plus ml-1"></i> إضافة برنامج
                        </a>
                    </div>
                @endif


                <div class="d-flex justify-content-between align-items-start mb-3">
                    <span class="badge bg-danger">{{ $goal['pillar_name'] ?? '—' }}</span>
                    <div onclick="event.stopPropagation()">
                        <a href="{{ route('strategic.goals.edit', $goal['id']) }}"
                           class="btn btn-sm btn-outline-primary" title="تعديل">
                            <i class="fas fa-edit"></i>
                        </a>
                    </div>
                </div>


                <h5 class="fw-bold mb-2">{{ $goal['title'] ?? '' }}</h5>
                <p class="text-muted small mb-3">{{ Str::limit($goal['description'] ?? '', 100) }}</p>


                @if($days !== null)
                    <div class="mb-3">
                        <span class="days-badge {{ $daysClass }}">
                            @if($days < 0)
                                <i class="fas fa-exclamation-circle ml-1"></i> متأخر بـ {{ abs($days) }} يوم
                            @else
                                <i class="far fa-clock ml-1"></i> متبقي {{ $days }} يوم
                            @endif
                        </span>
                    </div>
                @endif


                <div class="mb-3">
                    <div class="d-flex justify-content-between small mb-1">
                        <span class="text-muted">تقدم البرامج</span>
                        <strong class="text-danger">{{ $progress }}%</strong>
                    </div>
                    <div class="progress-bar-custom">
                        <div class="progress-bar-fill" style="width: {{ $progress }}%"></div>
                    </div>
                </div>


                <div class="mb-3">
                    <div class="d-flex justify-content-between small mb-1">
                        <span class="text-muted">تحقيق المؤشرات</span>
                        <strong style="color: #805ad5;">{{ $kpiProgress }}%</strong>
                    </div>
                    <div class="progress-bar-kpi">
                        <div class="progress-bar-kpi-fill" style="width: {{ min($kpiProgress, 100) }}%"></div>
                    </div>
                </div>


                <div class="mb-2">
                    @if(($goal['kpis_achieved'] ?? 0) > 0)
                        <span class="kpi-badge kpi-achieved">✓ {{ $goal['kpis_achieved'] }} محقق</span>
                    @endif
                    @if(($goal['kpis_at_risk'] ?? 0) > 0)
                        <span class="kpi-badge kpi-at-risk">⚠ {{ $goal['kpis_at_risk'] }} معرض للخطر</span>
                    @endif
                    @if(($goal['kpis_count'] ?? 0) === 0)
                        <span class="kpi-badge kpi-no-data">لا توجد مؤشرات</span>
                    @endif
                </div>


                <div class="row small text-muted border-top pt-2 mt-2">
                    <div class="col-4">
                        <i class="fas fa-project-diagram text-primary ml-1"></i>
                        {{ $goal['programs_count'] ?? 0 }} برنامج
                    </div>
                    <div class="col-4 text-center">
                        <i class="fas fa-lightbulb text-info ml-1"></i>
                        {{ $goal['initiatives_count'] ?? 0 }} مبادرة
                    </div>
                    <div class="col-4 text-end">
                        <i class="fas fa-chart-bar ml-1" style="color:#805ad5;"></i>
                        {{ $goal['kpis_count'] ?? 0 }} مؤشر
                    </div>
                </div>


            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>
@endsection

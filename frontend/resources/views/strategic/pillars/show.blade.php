@extends('layouts.app')


@section('title', 'تفاصيل الركيزة')


@push('styles')
<style>
    .goal-item {
        background: #fff; border-radius: 12px; padding: 14px; margin-bottom: 10px;
        border: 1px solid #e2e8f0; transition: all 0.2s; cursor: pointer;
        border-right: 4px solid #e53e3e;
    }
    .goal-item:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.06); transform: translateX(-2px); }


    .progress-bar-custom { height: 6px; background: #edf2f7; border-radius: 3px; overflow: hidden; }
    .progress-bar-fill { height: 100%; background: linear-gradient(90deg, #e53e3e, #fc8181); }
    .progress-bar-kpi { height: 6px; background: #edf2f7; border-radius: 3px; overflow: hidden; }
    .progress-bar-kpi-fill { height: 100%; background: linear-gradient(90deg, #805ad5, #b794f4); }


    .strategic-path {
        display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
        padding: 10px; background: #f7fafc; border-radius: 8px;
    }
    .strategic-path .node {
        padding: 5px 12px; border-radius: 8px; font-size: 12px;
        background: #fff; border: 1px solid #e2e8f0;
    }
    .strategic-path .node.pillar {
        border-color: #dd6b20; color: #dd6b20; background: #fffaf0;
        font-weight: 600;
    }


    .progress-circle {
        width: 100px; height: 100px; border-radius: 50%;
        background: conic-gradient(#dd6b20 var(--p), #edf2f7 0);
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto;
    }
    .progress-circle-inner {
        width: 78px; height: 78px; border-radius: 50%; background: #fff;
        display: flex; align-items: center; justify-content: center;
        font-size: 20px; font-weight: 700; color: #c05621;
    }


    .progress-circle-kpi {
        width: 100px; height: 100px; border-radius: 50%;
        background: conic-gradient(#805ad5 var(--p), #edf2f7 0);
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto;
    }
    .progress-circle-kpi .progress-circle-inner { color: #6b46c1; }


    .stat-box {
        background: #f7fafc; border-radius: 10px; padding: 12px; text-align: center;
        border: 1px solid #e2e8f0;
    }
    .stat-box .value { font-size: 20px; font-weight: 700; }
    .stat-box .label { font-size: 11px; color: #718096; margin-top: 4px; }


    .days-badge {
        display: inline-block; padding: 3px 9px; border-radius: 10px;
        font-size: 10px; font-weight: 600;
    }
    .days-ok      { background: #c6f6d5; color: #22543d; }
    .days-warning { background: #fefcbf; color: #744210; }
    .days-danger  { background: #fed7d7; color: #822727; }
</style>
@endpush


@section('content')
<div class="container-fluid px-4">


    <a href="{{ route('strategic.pillars.index') }}" class="btn btn-outline-secondary mb-3">
        <i class="fas fa-arrow-right"></i> العودة للركائز
    </a>


    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif


    @php
        $progress    = (int) ($pillar['progress'] ?? 0);
        $weightedProgress = (int) ($pillar['weighted_progress'] ?? 0);
        $kpiProgress = (int) ($pillar['kpi_progress'] ?? 0);
    @endphp


    <div class="card-custom mb-4">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div class="flex-grow-1">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge" style="background: #dd6b20; padding: 8px 14px; font-size: 14px;">
                        #{{ ($pillar['order_index'] ?? 0) + 1 }}
                    </span>
                    <h4 class="mb-0">{{ $pillar['name'] ?? '' }}</h4>
                </div>
                <p class="text-muted mb-0">{{ $pillar['description'] ?? '' }}</p>
            </div>


            <div class="d-flex gap-2">
                <a href="{{ route('strategic.pillars.edit', $pillar['id']) }}"
                   class="btn btn-outline-warning">
                    <i class="fas fa-edit"></i> تعديل
                </a>
            </div>
        </div>
    </div>


    <div class="row mb-4">
        <div class="col-md-3 mb-2">
            <div class="stat-box">
                <div class="value" style="color:#e53e3e;">{{ $pillar['goals_count'] ?? 0 }}</div>
                <div class="label"><i class="fas fa-bullseye ml-1"></i> الأهداف</div>
            </div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="stat-box">
                <div class="value text-primary">{{ $pillar['programs_count'] ?? 0 }}</div>
                <div class="label"><i class="fas fa-project-diagram ml-1"></i> البرامج</div>
            </div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="stat-box">
                <div class="value text-info">{{ $pillar['initiatives_count'] ?? 0 }}</div>
                <div class="label"><i class="fas fa-lightbulb ml-1"></i> المبادرات</div>
            </div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="stat-box">
                <div class="value" style="color:#805ad5;">{{ $pillar['kpis_count'] ?? 0 }}</div>
                <div class="label"><i class="fas fa-chart-bar ml-1"></i> مؤشرات الأداء</div>
            </div>
        </div>
    </div>


    <div class="row">
        <div class="col-lg-8">


            <div class="card-custom mb-4">
                <h6 class="mb-3"><i class="fas fa-sitemap text-primary ml-2"></i>الموقع الاستراتيجي</h6>
                <div class="strategic-path">
                    <span class="node pillar">
                        <i class="fas fa-layer-group"></i> {{ $pillar['name'] ?? '—' }}
                    </span>
                </div>
                <div class="row mt-3">
                    <div class="col-md-4">
                        <small class="text-muted d-block">الترتيب</small>
                        <strong>{{ ($pillar['order_index'] ?? 0) + 1 }}</strong>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">عدد المهام الرئيسية</small>
                        <strong>{{ $pillar['major_tasks_count'] ?? 0 }}</strong>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">المهام التشغيلية</small>
                        <strong>{{ $pillar['total_operational_completed'] ?? 0 }}/{{ $pillar['total_operational_tasks'] ?? 0 }}</strong>
                    </div>
                </div>
            </div>


            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">
                    <i class="fas fa-bullseye ml-2" style="color:#e53e3e;"></i>
                    الأهداف التابعة ({{ count($goals) }})
                </h5>
                <a href="{{ route('strategic.goals.create') }}?pillar_id={{ $pillar['id'] }}" class="btn-gold btn-sm">
                    <i class="fas fa-plus"></i> هدف جديد
                </a>
            </div>


            @if(empty($goals))
                <div class="card-custom text-center py-4">
                    <i class="fas fa-bullseye text-muted" style="font-size: 48px;"></i>
                    <h5 class="mt-2">لا توجد أهداف لهذه الركيزة</h5>
                    <p class="text-muted">يمكنك إنشاء هدف جديد من الزر أعلاه</p>
                </div>
            @else
                @foreach($goals as $goal)
                @php
                    $gProgress    = (int) ($goal['progress'] ?? 0);
                    $gKpiProgress = (int) ($goal['kpi_progress'] ?? 0);
                    $days         = $goal['days_remaining'] ?? null;
                    $daysClass    = 'days-ok';
                    if ($days !== null) {
                        if ($days < 0)       $daysClass = 'days-danger';
                        elseif ($days < 30)  $daysClass = 'days-warning';
                    }
                @endphp


                <div class="goal-item" onclick="window.location='{{ route('strategic.goals.show', $goal['id']) }}'">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                                <h6 class="mb-0">{{ $goal['title'] ?? '' }}</h6>
                                <span class="badge bg-secondary" style="font-size: 10px;">
                                    وزن: {{ $goal['weight'] ?? 1 }}
                                </span>
                                @if($days !== null)
                                    <span class="days-badge {{ $daysClass }}">
                                        @if($days < 0)
                                            متأخر {{ abs($days) }} يوم
                                        @else
                                            متبقي {{ $days }} يوم
                                        @endif
                                    </span>
                                @endif
                            </div>
                            <p class="text-muted small mb-2">{{ Str::limit($goal['description'] ?? '', 150) }}</p>


                            <div class="row small text-muted mb-2">
                                <div class="col-md-3">
                                    <i class="fas fa-project-diagram text-primary ml-1"></i>
                                    {{ $goal['programs_count'] ?? 0 }} برنامج
                                </div>
                                <div class="col-md-3">
                                    <i class="fas fa-lightbulb text-info ml-1"></i>
                                    {{ $goal['initiatives_count'] ?? 0 }} مبادرة
                                </div>
                                <div class="col-md-3">
                                    <i class="fas fa-cog ml-1"></i>
                                    {{ $goal['total_operational_completed'] ?? 0 }}/{{ $goal['total_operational_tasks'] ?? 0 }} تشغيلية
                                </div>
                                <div class="col-md-3">
                                    <i class="fas fa-chart-bar ml-1" style="color:#805ad5;"></i>
                                    {{ $goal['kpis_count'] ?? 0 }} مؤشر
                                </div>
                            </div>


                            <div class="row g-2">
                                <div class="col-md-6">
                                    <div class="d-flex justify-content-between small mb-1">
                                        <span class="text-muted">تقدم البرامج</span>
                                        <strong style="color:#e53e3e;">{{ $gProgress }}%</strong>
                                    </div>
                                    <div class="progress-bar-custom">
                                        <div class="progress-bar-fill" style="width: {{ $gProgress }}%"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex justify-content-between small mb-1">
                                        <span class="text-muted">تحقيق المؤشرات</span>
                                        <strong style="color:#805ad5;">{{ $gKpiProgress }}%</strong>
                                    </div>
                                    <div class="progress-bar-kpi">
                                        <div class="progress-bar-kpi-fill" style="width: {{ min($gKpiProgress, 100) }}%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>


                        <div class="ms-2">
                            <i class="fas fa-chevron-left text-muted"></i>
                        </div>
                    </div>
                </div>
                @endforeach
            @endif


        </div>


        <div class="col-lg-4">


            <div class="card-custom mb-4 text-center">
                <h6 class="mb-3">تقدم الأهداف</h6>
                <div class="progress-circle" style="--p: {{ $progress }}%">
                    <div class="progress-circle-inner">{{ $progress }}%</div>
                </div>
                <small class="text-muted d-block mt-2">
                    متوسط تقدم جميع الأهداف
                </small>
            </div>


            <div class="card-custom mb-4 text-center">
                <h6 class="mb-3">التقدم المرجّح</h6>
                <div class="progress-circle" style="--p: {{ $weightedProgress }}%; background: conic-gradient(#dd6b20 var(--p), #edf2f7 0);">
                    <div class="progress-circle-inner">{{ $weightedProgress }}%</div>
                </div>
                <small class="text-muted d-block mt-2">
                    مرجّح بأوزان الأهداف
                </small>
            </div>


            <div class="card-custom mb-4 text-center">
                <h6 class="mb-3">تحقيق المؤشرات</h6>
                <div class="progress-circle-kpi" style="--p: {{ min($kpiProgress, 100) }}%">
                    <div class="progress-circle-inner">{{ $kpiProgress }}%</div>
                </div>
                <small class="text-muted d-block mt-2">
                    متوسط تحقيق مؤشرات الأداء
                </small>
            </div>


            <div class="card-custom mb-4">
                <h6 class="mb-3"><i class="fas fa-info-circle text-primary ml-1"></i> معلومات سريعة</h6>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">الترتيب</span>
                    <strong>#{{ ($pillar['order_index'] ?? 0) + 1 }}</strong>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">الأهداف</span>
                    <strong>{{ $pillar['goals_count'] ?? 0 }}</strong>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">البرامج</span>
                    <strong>{{ $pillar['programs_count'] ?? 0 }}</strong>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">المبادرات</span>
                    <strong>{{ $pillar['initiatives_count'] ?? 0 }}</strong>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">المهام الرئيسية</span>
                    <strong>{{ $pillar['major_tasks_count'] ?? 0 }}</strong>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">المؤشرات المحققة</span>
                    <strong class="text-success">{{ $pillar['kpis_achieved'] ?? 0 }}</strong>
                </div>
                <div class="d-flex justify-content-between py-2">
                    <span class="text-muted">مؤشرات معرضة للخطر</span>
                    <strong class="text-warning">{{ $pillar['kpis_at_risk'] ?? 0 }}</strong>
                </div>
            </div>


        </div>
    </div>


</div>
@endsection

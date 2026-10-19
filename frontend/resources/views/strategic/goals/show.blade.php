@extends('layouts.app')


@section('title', 'تفاصيل الهدف')


@push('styles')
<style>
    .program-item {
        background: #fff; border-radius: 12px; padding: 14px; margin-bottom: 10px;
        border: 1px solid #e2e8f0; transition: all 0.2s; cursor: pointer;
    }
    .program-item:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.06); transform: translateX(-2px); }


    .kpi-card {
        background: #fff; border-radius: 12px; padding: 16px; margin-bottom: 10px;
        border: 1px solid #e2e8f0; border-right: 4px solid #805ad5;
        transition: all 0.2s; cursor: pointer;
    }
    .kpi-card:hover {
        box-shadow: 0 4px 14px rgba(0,0,0,0.08);
        transform: translateY(-2px);
    }
    .kpi-card.achieved  { border-right-color: #38a169; }
    .kpi-card.on_track  { border-right-color: #3182ce; }
    .kpi-card.at_risk   { border-right-color: #d69e2e; }
    .kpi-card.off_track { border-right-color: #e53e3e; }
    .kpi-card.no_data   { border-right-color: #a0aec0; }


    .status-badge { padding: 6px 14px; border-radius: 12px; font-size: 13px; font-weight: 600; }


    .strategic-path {
        display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
        padding: 10px; background: #f7fafc; border-radius: 8px;
    }
    .strategic-path .node {
        padding: 5px 12px; border-radius: 8px; font-size: 12px;
        background: #fff; border: 1px solid #e2e8f0;
    }
    .strategic-path .node.pillar { border-color: #dd6b20; color: #dd6b20; }
    .strategic-path .node.goal   { border-color: #e53e3e; color: #e53e3e; background: #fff5f5; font-weight: 600; }


    .progress-bar-custom {
        height: 8px; background: #edf2f7; border-radius: 10px; overflow: hidden;
        position: relative;
    }
    .progress-bar-fill {
        height: 100%; border-radius: 10px;
        transition: width 0.6s ease-in-out, background 0.3s ease;
        position: relative;
    }
    .progress-bar-fill::after {
        content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0;
        background: linear-gradient(90deg, rgba(255,255,255,0) 0%, rgba(255,255,255,0.35) 100%);
        border-radius: 10px;
    }


    .progress-circle {
        width: 110px; height: 110px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto;
        transition: background 0.3s ease;
        box-shadow: 0 4px 16px rgba(0,0,0,0.08);
    }
    .progress-circle-inner {
        width: 86px; height: 86px; border-radius: 50%; background: #fff;
        display: flex; align-items: center; justify-content: center;
        font-size: 22px; font-weight: 800;
        transition: color 0.3s ease;
    }


    .stat-box {
        background: #f7fafc; border-radius: 10px; padding: 12px; text-align: center;
        border: 1px solid #e2e8f0;
    }
    .stat-box .value { font-size: 22px; font-weight: 700; }
    .stat-box .label { font-size: 11px; color: #718096; margin-top: 4px; }


    .kpi-value-large {
        font-size: 22px; font-weight: 700; color: #1a202c;
    }
    .kpi-target {
        font-size: 12px; color: #718096;
    }


    .trend-icon { font-size: 14px; }
    .trend-up   { color: #38a169; }
    .trend-down { color: #e53e3e; }
    .trend-stable { color: #a0aec0; }


    .kpi-status-badge {
        display: inline-block; padding: 3px 10px; border-radius: 10px;
        font-size: 11px; font-weight: 600;
    }
    .badge-achieved  { background: #c6f6d5; color: #22543d; }
    .badge-on_track  { background: #bee3f8; color: #2c5282; }
    .badge-at_risk   { background: #fefcbf; color: #744210; }
    .badge-off_track { background: #fed7d7; color: #822727; }
    .badge-no_data   { background: #e2e8f0; color: #4a5568; }


    .days-badge {
        display: inline-block; padding: 3px 9px; border-radius: 10px;
        font-size: 10px; font-weight: 600;
    }
    .days-ok      { background: #c6f6d5; color: #22543d; }
    .days-warning { background: #fefcbf; color: #744210; }
    .days-danger  { background: #fed7d7; color: #822727; }


    .color-legend {
        display: flex; gap: 12px; justify-content: center; align-items: center;
        padding: 8px 14px; background: #fff; border-radius: 10px;
        border: 1px solid #e2e8f0; font-size: 11px; color: #4a5568;
        margin-bottom: 12px;
    }
    .color-legend .dot {
        width: 10px; height: 10px; border-radius: 50%; display: inline-block;
        margin-left: 5px; vertical-align: middle;
    }


    .kpi-arrow {
        opacity: 0.4;
        transition: all 0.2s;
    }
    .kpi-card:hover .kpi-arrow {
        opacity: 1;
        color: #805ad5 !important;
        transform: translateX(-4px);
    }
</style>
@endpush


@section('content')
@php
    function progressColor($p) {
        $p = (int) $p;
        if ($p < 20)  return '#e53e3e';
        if ($p < 40)  return '#dd6b20';
        if ($p < 60)  return '#d69e2e';
        if ($p < 80)  return '#48bb78';
        return '#38a169';
    }
    function progressLabel($p) {
        $p = (int) $p;
        if ($p < 20)  return 'منخفض جداً';
        if ($p < 40)  return 'منخفض';
        if ($p < 60)  return 'متوسط';
        if ($p < 80)  return 'جيد';
        return 'ممتاز';
    }
@endphp


<div class="container-fluid px-4">


    <a href="{{ route('strategic.goals.index') }}" class="btn btn-outline-secondary mb-3">
        <i class="fas fa-arrow-right"></i> العودة للأهداف
    </a>


    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif


    @php
        $progress    = (int) ($goal['progress'] ?? 0);
        $kpiProgress = (int) ($goal['kpi_progress'] ?? 0);
        $days        = $goal['days_remaining'] ?? null;
        $daysClass   = 'days-ok';
        if ($days !== null) {
            if ($days < 0)       $daysClass = 'days-danger';
            elseif ($days < 30)  $daysClass = 'days-warning';
        }
    @endphp


    <div class="card-custom mb-4">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div class="flex-grow-1">
                <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                    <h4 class="mb-0">{{ $goal['title'] ?? '' }}</h4>
                    @if($days !== null)
                        <span class="status-badge
                            @if($days < 0) bg-danger text-white
                            @elseif($days < 30) bg-warning text-dark
                            @else bg-success text-white @endif">
                            @if($days < 0)
                                متأخر بـ {{ abs($days) }} يوم
                            @else
                                متبقي {{ $days }} يوم
                            @endif
                        </span>
                    @endif
                </div>
                <p class="text-muted mb-0">{{ $goal['description'] ?? '' }}</p>
            </div>


            <div class="d-flex gap-2">
                <a href="{{ route('strategic.goals.edit', $goal['id']) }}"
                   class="btn btn-outline-warning">
                    <i class="fas fa-edit"></i> تعديل
                </a>
            </div>
        </div>
    </div>


    <div class="row mb-4">
        <div class="col-md-3 mb-2">
            <div class="stat-box">
                <div class="value text-primary">{{ $goal['programs_count'] ?? 0 }}</div>
                <div class="label"><i class="fas fa-project-diagram ml-1"></i> البرامج</div>
            </div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="stat-box">
                <div class="value text-info">{{ $goal['initiatives_count'] ?? 0 }}</div>
                <div class="label"><i class="fas fa-lightbulb ml-1"></i> المبادرات</div>
            </div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="stat-box">
                <div class="value" style="color: #805ad5;">{{ $goal['kpis_count'] ?? 0 }}</div>
                <div class="label"><i class="fas fa-chart-bar ml-1"></i> مؤشرات الأداء</div>
            </div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="stat-box">
                <div class="value text-success">{{ $goal['total_operational_completed'] ?? 0 }}/{{ $goal['total_operational_tasks'] ?? 0 }}</div>
                <div class="label"><i class="fas fa-cog ml-1"></i> المهام التشغيلية</div>
            </div>
        </div>
    </div>


    <div class="row">
        <div class="col-lg-8">


            <div class="card-custom mb-4">
                <h6 class="mb-3"><i class="fas fa-sitemap text-primary ml-2"></i>الخريطة الاستراتيجية</h6>
                <div class="strategic-path">
                    <span class="node pillar">
                        <i class="fas fa-layer-group"></i> {{ $goal['pillar_name'] ?? '—' }}
                    </span>
                    <i class="fas fa-arrow-left text-muted"></i>
                    <span class="node goal">
                        <i class="fas fa-bullseye"></i> {{ $goal['title'] ?? '—' }}
                    </span>
                </div>
                <div class="row mt-3">
                    <div class="col-md-3">
                        <small class="text-muted d-block">الوزن النسبي</small>
                        <strong>{{ $goal['weight'] ?? 0 }}</strong>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block">التاريخ المستهدف</small>
                        <strong>{{ $goal['target_date'] ?? '—' }}</strong>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block">صالح من</small>
                        <strong>{{ $goal['valid_from'] ?? '—' }}</strong>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block">صالح حتى</small>
                        <strong>{{ $goal['valid_until'] ?? '—' }}</strong>
                    </div>
                </div>
            </div>


            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">
                    <i class="fas fa-project-diagram text-primary ml-2"></i>
                    البرامج التابعة ({{ count($programs) }})
                </h5>
                <a href="{{ route('strategic.programs.create') }}?goal_id={{ $goal['id'] }}" class="btn-gold btn-sm">
                    <i class="fas fa-plus"></i> برنامج جديد
                </a>
            </div>


            @if(empty($programs))
                <div class="card-custom text-center py-4">
                    <i class="fas fa-project-diagram text-muted" style="font-size: 48px;"></i>
                    <h5 class="mt-2">لا توجد برامج لهذا الهدف</h5>
                    <p class="text-muted">يمكنك إنشاء برنامج جديد من الزر أعلاه</p>
                </div>
            @else
                @foreach($programs as $program)
                @php
                    $pStatusColor = $program['status_color'] ?? '#6c757d';
                    $pStatusName  = $program['status_name'] ?? 'غير محدد';
                    $pProgress    = (int) ($program['progress'] ?? 0);
                    $pBarColor    = progressColor($pProgress);
                @endphp


                <div class="program-item" onclick="window.location='{{ route('strategic.programs.show', $program['id']) }}'">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <h6 class="mb-0">{{ $program['name'] ?? '' }}</h6>
                                <span class="status-badge"
                                      style="background: {{ $pStatusColor }}22; color: {{ $pStatusColor }}; border: 1px solid {{ $pStatusColor }}55; font-size: 11px; padding: 3px 10px;">
                                    {{ $pStatusName }}
                                </span>
                            </div>
                            <p class="text-muted small mb-2">{{ Str::limit($program['description'] ?? '', 150) }}</p>


                            <div class="d-flex gap-3 flex-wrap mb-2">
                                <small class="text-muted">
                                    <i class="fas fa-lightbulb ml-1"></i>
                                    مبادرات: {{ $program['initiatives_count'] ?? 0 }}
                                </small>
                                <small class="text-muted">
                                    <i class="fas fa-cog ml-1"></i>
                                    تشغيلية: {{ $program['operational_completed'] ?? 0 }}/{{ $program['operational_count'] ?? 0 }}
                                </small>
                                <small class="text-muted">
                                    <i class="fas fa-coins ml-1"></i>
                                    {{ number_format($program['budget_estimate'] ?? 0) }}
                                </small>
                            </div>


                            <div class="d-flex justify-content-between align-items-center small mb-1">
                                <span class="text-muted">نسبة التقدم</span>
                                <strong style="color: {{ $pBarColor }};">
                                    {{ $pProgress }}%
                                    <small class="text-muted">({{ progressLabel($pProgress) }})</small>
                                </strong>
                            </div>
                            <div class="progress-bar-custom">
                                <div class="progress-bar-fill" style="width: {{ $pProgress }}%; background: {{ $pBarColor }};"></div>
                            </div>
                        </div>


                        <div class="ms-2">
                            <i class="fas fa-chevron-left text-muted"></i>
                        </div>
                    </div>
                </div>
                @endforeach
            @endif


            <h5 class="mb-3 mt-4">
                <i class="fas fa-chart-bar ml-2" style="color:#805ad5;"></i>
                مؤشرات الأداء ({{ count($kpis) }})
            </h5>


            @if(empty($kpis))
                <div class="card-custom text-center py-4">
                    <i class="fas fa-chart-bar text-muted" style="font-size: 48px;"></i>
                    <h5 class="mt-2">لا توجد مؤشرات أداء لهذا الهدف</h5>
                    <p class="text-muted">يمكنك إضافة مؤشرات الأداء من صفحة المؤشرات</p>
                </div>
            @else
                @foreach($kpis as $kpi)
                @php
                    $kpiStatus    = $kpi['status'] ?? 'no_data';
                    $achievement  = $kpi['achievement'];
                    $trend        = $kpi['trend'] ?? 'stable';
                    $kpiBarColor  = progressColor($achievement ?? 0);
                @endphp


                <div class="kpi-card {{ $kpiStatus }}"
                     onclick="window.location='{{ route('kpis.show', $kpi['kpi_id']) }}'">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <h6 class="mb-0">{{ $kpi['name'] ?? '' }}</h6>
                                <span class="kpi-status-badge badge-{{ $kpiStatus }}">
                                    @if($kpiStatus === 'achieved')  محقق
                                    @elseif($kpiStatus === 'on_track')  على المسار
                                    @elseif($kpiStatus === 'at_risk')  معرض للخطر
                                    @elseif($kpiStatus === 'off_track')  خارج المسار
                                    @else  لا توجد بيانات
                                    @endif
                                </span>
                            </div>
                            <small class="text-muted">{{ Str::limit($kpi['description'] ?? '', 120) }}</small>
                        </div>
                        <div class="ms-2">
                            <i class="fas fa-chevron-left kpi-arrow text-muted"></i>
                        </div>
                    </div>


                    <div class="row g-2 mt-2">
                        <div class="col-md-3 text-center">
                            <div class="kpi-value-large">
                                {{ $kpi['current_value'] !== null ? number_format($kpi['current_value']) : '—' }}
                            </div>
                            <small class="text-muted">
                                القيمة الحالية
                                @if($kpi['unit'])
                                    ({{ $kpi['unit'] }})
                                @endif
                            </small>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="kpi-value-large text-primary">
                                {{ $kpi['target_value'] !== null ? number_format($kpi['target_value']) : '—' }}
                            </div>
                            <small class="text-muted">المستهدفة</small>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="kpi-value-large text-secondary">
                                {{ $kpi['baseline_value'] !== null ? number_format($kpi['baseline_value']) : '—' }}
                            </div>
                            <small class="text-muted">الأساسية</small>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="kpi-value-large" style="color: {{ $kpiBarColor }};">
                                {{ $achievement !== null ? number_format($achievement, 1) . '%' : '—' }}
                                @if($trend === 'up')
                                    <i class="fas fa-arrow-up trend-icon trend-up"></i>
                                @elseif($trend === 'down')
                                    <i class="fas fa-arrow-down trend-icon trend-down"></i>
                                @else
                                    <i class="fas fa-minus trend-icon trend-stable"></i>
                                @endif
                            </div>
                            <small class="text-muted">نسبة التحقيق</small>
                        </div>
                    </div>


                    @if($achievement !== null)
                        <div class="progress-bar-custom mt-3">
                            <div class="progress-bar-fill"
                                 style="width: {{ min(max($achievement, 0), 100) }}%; background: {{ $kpiBarColor }};"></div>
                        </div>
                    @endif


                    <div class="d-flex justify-content-between mt-3 small text-muted">
                        <span>
                            <i class="fas fa-weight-hanging ml-1"></i>
                            الوزن: {{ $kpi['weight'] ?? 1 }}
                        </span>
                        <span>
                            <i class="fas fa-database ml-1"></i>
                            القياسات: {{ $kpi['measurements_count'] ?? 0 }}
                        </span>
                        @if($kpi['last_update'])
                            <span>
                                <i class="far fa-clock ml-1"></i>
                                آخر تحديث: {{ \Carbon\Carbon::parse($kpi['last_update'])->format('Y-m-d') }}
                            </span>
                        @endif
                    </div>
                </div>
                @endforeach
            @endif


        </div>


        <div class="col-lg-4">


            <div class="color-legend">
                <span><span class="dot" style="background: #e53e3e;"></span>0-19%</span>
                <span><span class="dot" style="background: #dd6b20;"></span>20-39%</span>
                <span><span class="dot" style="background: #d69e2e;"></span>40-59%</span>
                <span><span class="dot" style="background: #48bb78;"></span>60-79%</span>
                <span><span class="dot" style="background: #38a169;"></span>80-100%</span>
            </div>


            <div class="card-custom mb-4 text-center">
                <h6 class="mb-3">تقدم البرامج</h6>
                <div class="progress-circle"
                     style="background: conic-gradient({{ progressColor($progress) }} {{ $progress }}%, #edf2f7 0);">
                    <div class="progress-circle-inner" style="color: {{ progressColor($progress) }};">
                        {{ $progress }}%
                    </div>
                </div>
                <div class="mt-3">
                    <span class="badge py-2 px-3"
                          style="background: {{ progressColor($progress) }}22; color: {{ progressColor($progress) }}; border: 1px solid {{ progressColor($progress) }}55; font-size: 12px;">
                        <i class="fas fa-chart-line ml-1"></i>
                        {{ progressLabel($progress) }}
                    </span>
                </div>
                <small class="text-muted d-block mt-2">
                    متوسط تقدم البرامج التابعة
                </small>
            </div>


            <div class="card-custom mb-4 text-center">
                <h6 class="mb-3">تحقيق المؤشرات</h6>
                <div class="progress-circle"
                     style="background: conic-gradient({{ progressColor($kpiProgress) }} {{ min($kpiProgress, 100) }}%, #edf2f7 0);">
                    <div class="progress-circle-inner" style="color: {{ progressColor($kpiProgress) }};">
                        {{ $kpiProgress }}%
                    </div>
                </div>
                <div class="mt-3">
                    <span class="badge py-2 px-3"
                          style="background: {{ progressColor($kpiProgress) }}22; color: {{ progressColor($kpiProgress) }}; border: 1px solid {{ progressColor($kpiProgress) }}55; font-size: 12px;">
                        <i class="fas fa-bullseye ml-1"></i>
                        {{ progressLabel($kpiProgress) }}
                    </span>
                </div>
                <small class="text-muted d-block mt-2">
                    المتوسط المرجح لمؤشرات الأداء
                </small>
            </div>


            <div class="card-custom mb-4">
                <h6 class="mb-3"><i class="fas fa-info-circle text-primary ml-1"></i> معلومات سريعة</h6>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">الوزن النسبي</span>
                    <strong>{{ $goal['weight'] ?? 0 }}</strong>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">البرامج</span>
                    <strong>{{ $goal['programs_count'] ?? 0 }}</strong>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">المبادرات</span>
                    <strong>{{ $goal['initiatives_count'] ?? 0 }}</strong>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">المهام الرئيسية</span>
                    <strong>{{ $goal['major_tasks_count'] ?? 0 }}</strong>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">مؤشرات الأداء</span>
                    <strong>{{ $goal['kpis_count'] ?? 0 }}</strong>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">المؤشرات المحققة</span>
                    <strong class="text-success">{{ $goal['kpis_achieved'] ?? 0 }}</strong>
                </div>
                <div class="d-flex justify-content-between py-2">
                    <span class="text-muted">مؤشرات معرضة للخطر</span>
                    <strong class="text-warning">{{ $goal['kpis_at_risk'] ?? 0 }}</strong>
                </div>
            </div>


            @if($days !== null)
            <div class="card-custom mb-4">
                <h6 class="mb-3"><i class="fas fa-clock text-warning ml-1"></i> الجدول الزمني</h6>
                <div class="text-center py-3">
                    @if($days < 0)
                        <h2 class="text-danger mb-0">{{ abs($days) }}</h2>
                        <small class="text-muted">يوم متأخر عن الموعد المستهدف</small>
                    @elseif($days < 30)
                        <h2 class="text-warning mb-0">{{ $days }}</h2>
                        <small class="text-muted">يوم متبقي (اقتراب الموعد)</small>
                    @else
                        <h2 class="text-success mb-0">{{ $days }}</h2>
                        <small class="text-muted">يوم متبقي</small>
                    @endif
                </div>
                <hr>
                <div class="d-flex justify-content-between small">
                    <span class="text-muted">التاريخ المستهدف</span>
                    <strong>{{ $goal['target_date'] ?? '—' }}</strong>
                </div>
            </div>
            @endif


        </div>
    </div>


</div>
@endsection

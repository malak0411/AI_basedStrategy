@extends('layouts.app')


@section('title', 'تفاصيل البرنامج')


@push('styles')
<style>
    .initiative-item {
        background: #fff; border-radius: 12px; padding: 14px; margin-bottom: 10px;
        border: 1px solid #e2e8f0; transition: all 0.2s; cursor: pointer;
    }
    .initiative-item:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.06); transform: translateX(-2px); }


    .empty-state { text-align: center; padding: 60px 20px; }
    .empty-state i { font-size: 64px; color: #cbd5e0; margin-bottom: 16px; }


    .status-badge { padding: 6px 14px; border-radius: 12px; font-size: 13px; font-weight: 600; }


    .strategic-path {
        display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
        padding: 10px; background: #f7fafc; border-radius: 8px; margin-bottom: 8px;
    }
    .strategic-path .node {
        padding: 5px 12px; border-radius: 8px; font-size: 12px;
        background: #fff; border: 1px solid #e2e8f0;
    }
    .strategic-path .node.pillar { border-color: #dd6b20; color: #dd6b20; }
    .strategic-path .node.goal   { border-color: #e53e3e; color: #e53e3e; }
    .strategic-path .node.program{ border-color: #3182ce; color: #3182ce; background: #ebf8ff; font-weight: 600; }


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


    .progress-bar-kpi { height: 8px; background: #edf2f7; border-radius: 10px; overflow: hidden; }
    .progress-bar-kpi-fill {
        height: 100%; border-radius: 10px;
        transition: width 0.6s ease-in-out;
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
    .stat-box .value { font-size: 22px; font-weight: 700; color: #1a202c; }
    .stat-box .label { font-size: 11px; color: #718096; margin-top: 4px; }


    .budget-row {
        display: flex; justify-content: space-between; align-items: center;
        padding: 10px 12px; border-radius: 8px; margin-bottom: 6px;
        background: #f7fafc; border-right: 3px solid #3182ce;
    }
    .budget-row.program   { border-right-color: #3182ce; }
    .budget-row.initiative{ border-right-color: #dd6b20; }
    .budget-row.major     { border-right-color: #805ad5; }
    .budget-row.operation { border-right-color: #38a169; }
    .budget-row .name { font-weight: 600; font-size: 13px; }
    .budget-row .amounts { font-size: 12px; color: #4a5568; }
    .budget-row .amounts strong { color: #1a202c; }


    .tx-table th { font-size: 12px; color: #718096; font-weight: 600; }
    .tx-table td { font-size: 12px; vertical-align: middle; }
    .tx-type-badge {
        display: inline-block; padding: 2px 8px; border-radius: 10px;
        font-size: 10px; font-weight: 600;
    }
    .tx-in  { background: #c6f6d5; color: #22543d; }
    .tx-out { background: #fed7d7; color: #822727; }
    .tx-related {
        display: inline-block; padding: 2px 8px; border-radius: 10px;
        font-size: 10px; font-weight: 600; background: #e2e8f0; color: #4a5568;
    }


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


    <a href="javascript:history.back()" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-right"></i> العودة
    </a>


    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif


    @php
        $statusId    = $program['status_id'] ?? 46;
        $statusName  = $program['status_name'] ?? 'غير محدد';
        $statusColor = $program['status_color'] ?? '#6c757d';
        $progress    = (int) ($program['progress'] ?? 0);
        $userRole    = $userRole ?? session('user_role', 'employee');
        $canAddInit  = in_array($statusId, [46, 47, 48]);
        $budget      = $budgetDetails ?? [];
        $statusesCount = $program['statuses_count'] ?? [];
    @endphp


    <div class="card-custom mb-4">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div class="flex-grow-1">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <h4 class="mb-0">{{ $program['name'] ?? $program['title'] ?? '' }}</h4>
                    <span class="status-badge"
                          style="background: {{ $statusColor }}22; color: {{ $statusColor }}; border: 1px solid {{ $statusColor }}55;">
                        {{ $statusName }}
                    </span>
                </div>
                <p class="text-muted mb-0">{{ $program['description'] ?? '' }}</p>
            </div>


            <div class="d-flex gap-2 flex-wrap">


                @if($statusId === 46 && $userRole === 'manager')
                    <form action="{{ route('strategic.programs.change-status', $program['id']) }}" method="POST" class="d-inline"
                          onsubmit="return confirm('بدء تنفيذ البرنامج؟')">
                        @csrf
                        <input type="hidden" name="status_id" value="47">
                        <button type="submit" class="btn btn-info">
                            <i class="fas fa-play"></i> بدء التنفيذ
                        </button>
                    </form>
                @endif


                @if($statusId === 47 && $userRole === 'manager')
                    <form action="{{ route('strategic.programs.change-status', $program['id']) }}" method="POST" class="d-inline"
                          onsubmit="return confirm('تعليم البرنامج كمكتمل؟')">
                        @csrf
                        <input type="hidden" name="status_id" value="48">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-flag-checkered"></i> إكمال
                        </button>
                    </form>
                @endif


                @if(!in_array($statusId, [49, 48]) && $userRole === 'minister')
                    <form action="{{ route('strategic.programs.change-status', $program['id']) }}" method="POST" class="d-inline"
                          onsubmit="return confirm('هل أنت متأكد من إلغاء البرنامج؟')">
                        @csrf
                        <input type="hidden" name="status_id" value="49">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-times-circle"></i> إلغاء
                        </button>
                    </form>
                @endif


                <a href="{{ route('strategic.programs.edit', $program['id']) }}"
                   class="btn btn-outline-warning">
                    <i class="fas fa-edit"></i> تعديل
                </a>


            </div>
        </div>


        @if(in_array($statusId, [47, 48]))
            <div class="alert alert-warning mt-3 mb-0 py-2">
                <small>
                    <i class="fas fa-exclamation-triangle"></i>
                    أي تعديل على البرنامج سيعيد حالته إلى <strong>"معلق"</strong>.
                </small>
            </div>
        @endif
    </div>


    <div class="row mb-4">
        <div class="col-md-3 mb-2">
            <div class="stat-box">
                <div class="value text-primary">{{ $program['initiatives_count'] ?? 0 }}</div>
                <div class="label"><i class="fas fa-lightbulb ml-1"></i> المبادرات</div>
            </div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="stat-box">
                <div class="value" style="color: #805ad5;">{{ $program['major_tasks_count'] ?? 0 }}</div>
                <div class="label"><i class="fas fa-tasks ml-1"></i> المهام الرئيسية</div>
            </div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="stat-box">
                <div class="value text-success">{{ $program['total_operational_completed'] ?? 0 }}/{{ $program['total_operational_tasks'] ?? 0 }}</div>
                <div class="label"><i class="fas fa-cog ml-1"></i> المهام التشغيلية المكتملة</div>
            </div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="stat-box">
                <div class="value" style="color: #dd6b20;">{{ number_format($program['budget_estimate'] ?? 0) }}</div>
                <div class="label"><i class="fas fa-coins ml-1"></i> الميزانية التقديرية</div>
            </div>
        </div>
    </div>


    <div class="row">
        <div class="col-lg-8">


            <div class="card-custom mb-4">
                <h6 class="mb-3"><i class="fas fa-sitemap text-primary ml-2"></i>الخريطة الاستراتيجية</h6>
                <div class="strategic-path">
                    <span class="node pillar">
                        <i class="fas fa-layer-group"></i> {{ $program['pillar_name'] ?? '—' }}
                    </span>
                    <i class="fas fa-arrow-left text-muted"></i>
                    <span class="node goal">
                        <i class="fas fa-bullseye"></i> {{ $program['goal_name'] ?? '—' }}
                    </span>
                    <i class="fas fa-arrow-left text-muted"></i>
                    <span class="node program">
                        <i class="fas fa-project-diagram"></i> {{ $program['name'] ?? '—' }}
                    </span>
                </div>


                @if(!empty($program['goal_description']))
                    <div class="mt-3 p-3" style="background: #fef5e7; border-radius: 8px; border-right: 3px solid #dd6b20;">
                        <small class="text-muted d-block mb-1"><i class="fas fa-bullseye ml-1"></i> وصف الهدف الاستراتيجي</small>
                        <div style="font-size: 13px;">{{ $program['goal_description'] }}</div>
                        @if($program['goal_target_date'])
                            <small class="text-muted d-block mt-2">
                                <i class="far fa-calendar ml-1"></i>
                                التاريخ المستهدف: <strong>{{ $program['goal_target_date'] }}</strong>
                            </small>
                        @endif
                    </div>
                @endif


                <div class="row mt-3">
                    <div class="col-md-4">
                        <small class="text-muted d-block">تاريخ بداية البرنامج</small>
                        <strong>{{ $program['start_date'] ?? '—' }}</strong>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">تاريخ نهاية البرنامج</small>
                        <strong>{{ $program['end_date'] ?? '—' }}</strong>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">حالة المبادرات</small>
                        <div class="d-flex flex-wrap gap-1 mt-1">
                            @if(($statusesCount['draft'] ?? 0) > 0)
                                <span class="badge bg-secondary">مسودة: {{ $statusesCount['draft'] }}</span>
                            @endif
                            @if(($statusesCount['approved'] ?? 0) > 0)
                                <span class="badge bg-primary">معتمد: {{ $statusesCount['approved'] }}</span>
                            @endif
                            @if(($statusesCount['in_progress'] ?? 0) > 0)
                                <span class="badge bg-info">قيد التنفيذ: {{ $statusesCount['in_progress'] }}</span>
                            @endif
                            @if(($statusesCount['completed'] ?? 0) > 0)
                                <span class="badge bg-success">مكتمل: {{ $statusesCount['completed'] }}</span>
                            @endif
                            @if(($statusesCount['pending'] ?? 0) > 0)
                                <span class="badge bg-warning text-dark">معلق: {{ $statusesCount['pending'] }}</span>
                            @endif
                            @if(($statusesCount['cancelled'] ?? 0) > 0)
                                <span class="badge bg-danger">ملغي: {{ $statusesCount['cancelled'] }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>


            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">
                    <i class="fas fa-lightbulb ml-2"></i>
                    المبادرات التابعة للبرنامج ({{ count($initiatives) }})
                </h5>
                <div class="d-flex gap-2">
                    @if($canAddInit)
                        <a href="{{ route('strategic.initiatives.create') }}?program_id={{ $program['id'] }}" class="btn-gold btn-sm">
                            <i class="fas fa-plus"></i> مبادرة جديدة
                        </a>
                    @else
                        <span class="badge bg-secondary py-2 px-3">
                            <i class="fas fa-lock"></i>
                            لا يمكن إضافة مبادرات
                        </span>
                    @endif
                </div>
            </div>


            @if(empty($initiatives))
                <div class="empty-state card-custom">
                    <i class="fas fa-lightbulb"></i>
                    @if(in_array($statusId, [46, 47, 48]))
                        <h5>لا توجد مبادرات لهذا البرنامج</h5>
                        <p class="text-muted">يمكنك إنشاء مبادرة جديدة من الزر أعلاه</p>
                    @elseif($statusId === 49)
                        <h5>البرنامج ملغي</h5>
                        <p class="text-muted">لا يمكن إضافة مبادرات لبرنامج ملغي</p>
                    @else
                        <h5>لا توجد مبادرات لهذا البرنامج</h5>
                    @endif
                </div>
            @else
                @foreach($initiatives as $initiative)
                @php
                    $initStatusColor = $initiative['status_color'] ?? '#6c757d';
                    $initStatusName  = $initiative['status_name'] ?? 'غير محدد';
                    $initProgress    = (int) ($initiative['progress'] ?? 0);
                    $initBarColor    = progressColor($initProgress);
                @endphp


                <div class="initiative-item" onclick="window.location='{{ route('strategic.initiatives.show', $initiative['id']) }}'">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <h6 class="mb-0">{{ $initiative['name'] ?? '' }}</h6>
                                <span class="status-badge"
                                      style="background: {{ $initStatusColor }}22; color: {{ $initStatusColor }}; border: 1px solid {{ $initStatusColor }}55; font-size: 11px; padding: 3px 10px;">
                                    {{ $initStatusName }}
                                </span>
                            </div>
                            <p class="text-muted small mb-2">{{ Str::limit($initiative['description'] ?? '', 150) }}</p>


                            <div class="d-flex gap-3 flex-wrap mb-2">
                                <small class="text-muted">
                                    <i class="fas fa-tasks ml-1"></i>
                                    مهام رئيسية: {{ $initiative['major_tasks_count'] ?? 0 }}
                                </small>
                                <small class="text-muted">
                                    <i class="fas fa-cog ml-1"></i>
                                    تشغيلية: {{ $initiative['operational_completed'] ?? 0 }}/{{ $initiative['operational_count'] ?? 0 }}
                                </small>
                                <small class="text-muted">
                                    <i class="fas fa-coins ml-1"></i>
                                    {{ number_format($initiative['budget_estimate'] ?? 0) }}
                                </small>
                            </div>


                            <div class="d-flex justify-content-between align-items-center small mb-1">
                                <span class="text-muted">نسبة التقدم</span>
                                <strong style="color: {{ $initBarColor }};">
                                    {{ $initProgress }}%
                                    <small class="text-muted">({{ progressLabel($initProgress) }})</small>
                                </strong>
                            </div>
                            <div class="progress-bar-custom">
                                <div class="progress-bar-fill" style="width: {{ $initProgress }}%; background: {{ $initBarColor }};"></div>
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


            <div class="color-legend">
                <span><span class="dot" style="background: #e53e3e;"></span>0-19%</span>
                <span><span class="dot" style="background: #dd6b20;"></span>20-39%</span>
                <span><span class="dot" style="background: #d69e2e;"></span>40-59%</span>
                <span><span class="dot" style="background: #48bb78;"></span>60-79%</span>
                <span><span class="dot" style="background: #38a169;"></span>80-100%</span>
            </div>


            <div class="card-custom mb-4 text-center">
                <h6 class="mb-3">نسبة التقدم الكلية</h6>
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
                    متوسط تقدم المبادرات التابعة
                </small>
            </div>


            <div class="card-custom mb-4">
                <h6 class="mb-3"><i class="fas fa-wallet text-primary ml-1"></i> الميزانية التفصيلية</h6>


                @php
                    $bProgram = $budget['program'] ?? ['allocated'=>0,'spent'=>0,'remaining'=>0];
                    $bInit    = $budget['initiatives'] ?? ['allocated'=>0,'spent'=>0,'remaining'=>0,'count'=>0];
                    $bMajor   = $budget['major_tasks'] ?? ['allocated'=>0,'spent'=>0,'remaining'=>0,'count'=>0];
                    $bOps     = $budget['operations'] ?? ['allocated'=>0,'spent'=>0,'remaining'=>0,'count'=>0];
                    $bTotal   = $budget['total'] ?? ['allocated'=>0,'spent'=>0,'remaining'=>0];
                @endphp


                <div class="budget-row program">
                    <div>
                        <div class="name"><i class="fas fa-project-diagram ml-1"></i> البرنامج (مباشر)</div>
                    </div>
                    <div class="amounts text-end">
                        <div>مخصص: <strong>{{ number_format($bProgram['allocated']) }}</strong></div>
                        <div>منفق: <strong class="text-danger">{{ number_format($bProgram['spent']) }}</strong></div>
                    </div>
                </div>


                <div class="budget-row initiative">
                    <div>
                        <div class="name"><i class="fas fa-lightbulb ml-1"></i> المبادرات ({{ $bInit['count'] ?? 0 }})</div>
                    </div>
                    <div class="amounts text-end">
                        <div>مخصص: <strong>{{ number_format($bInit['allocated']) }}</strong></div>
                        <div>منفق: <strong class="text-danger">{{ number_format($bInit['spent']) }}</strong></div>
                    </div>
                </div>


                <div class="budget-row major">
                    <div>
                        <div class="name"><i class="fas fa-tasks ml-1"></i> المهام الرئيسية ({{ $bMajor['count'] ?? 0 }})</div>
                    </div>
                    <div class="amounts text-end">
                        <div>مخصص: <strong>{{ number_format($bMajor['allocated']) }}</strong></div>
                        <div>منفق: <strong class="text-danger">{{ number_format($bMajor['spent']) }}</strong></div>
                    </div>
                </div>


                <div class="budget-row operation">
                    <div>
                        <div class="name"><i class="fas fa-cog ml-1"></i> المهام التشغيلية ({{ $bOps['count'] ?? 0 }})</div>
                    </div>
                    <div class="amounts text-end">
                        <div>مخصص: <strong>{{ number_format($bOps['allocated']) }}</strong></div>
                        <div>منفق: <strong class="text-danger">{{ number_format($bOps['spent']) }}</strong></div>
                    </div>
                </div>


                <hr>


                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">إجمالي المخصص</span>
                    <strong class="text-primary">{{ number_format($bTotal['allocated'] ?? 0) }}</strong>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">إجمالي المنفق</span>
                    <strong class="text-danger">{{ number_format($bTotal['spent'] ?? 0) }}</strong>
                </div>
                <div class="d-flex justify-content-between py-2">
                    <span class="text-muted">المتبقي</span>
                    <strong class="text-success">{{ number_format($bTotal['remaining'] ?? 0) }}</strong>
                </div>
            </div>


            <div class="card-custom mb-4">
                <h6 class="mb-3">
                    <i class="fas fa-history text-warning ml-1"></i>
                    سجل الحركات المالية
                    @if(!empty($budget['transactions']))
                        <span class="badge bg-secondary">{{ count($budget['transactions']) }}</span>
                    @endif
                </h6>
                @if(!empty($budget['transactions']))
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-sm tx-table mb-0">
                            <thead class="table-light" style="position: sticky; top: 0; z-index: 1;">
                                <tr>
                                    <th>التاريخ</th>
                                    <th>النوع</th>
                                    <th>المبلغ</th>
                                    <th>مرتبط بـ</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($budget['transactions'] as $tx)
                                <tr>
                                    <td><small>{{ $tx['date'] ? \Carbon\Carbon::parse($tx['date'])->format('Y-m-d') : '—' }}</small></td>
                                    <td>
                                        @php
                                            $sign = $tx['type_sign'] ?? 1;
                                            $typeClass = ($sign > 0) ? 'tx-in' : 'tx-out';
                                        @endphp
                                        <span class="tx-type-badge {{ $typeClass }}">
                                            {{ $tx['type_name'] ?? '—' }}
                                        </span>
                                    </td>
                                    <td>
                                        <strong class="{{ $sign > 0 ? 'text-success' : 'text-danger' }}">
                                            {{ number_format($tx['amount'] ?? 0) }}
                                        </strong>
                                    </td>
                                    <td>
                                        @php
                                            $typeLabels = [
                                                'program' => 'برنامج',
                                                'initiative' => 'مبادرة',
                                                'major_task' => 'مهمة رئيسية',
                                                'operational_task' => 'مهمة تشغيلية',
                                            ];
                                            $typeLabel = $typeLabels[$tx['budgetable_type'] ?? ''] ?? '';
                                        @endphp
                                        @if($typeLabel)
                                            <div><span class="tx-related">{{ $typeLabel }}</span></div>
                                        @endif
                                        <small class="text-muted">{{ $tx['budgetable_name'] ?? '' }}</small>
                                    </td>
                                </tr>
                                @if(!empty($tx['description']))
                                <tr>
                                    <td colspan="4" class="border-0 pt-0">
                                        <small class="text-muted"><i class="fas fa-comment ml-1"></i>{{ $tx['description'] }}</small>
                                    </td>
                                </tr>
                                @endif
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-receipt text-muted" style="font-size: 32px;"></i>
                        <p class="text-muted small mt-2 mb-0">
                            لا توجد حركات مالية مسجلة على البرنامج أو عناصره التابعة
                        </p>
                        <small class="text-muted">
                            (المبادرات / المهام الرئيسية / المهام التشغيلية)
                        </small>
                    </div>
                @endif
            </div>


        </div>
    </div>


</div>
@endsection

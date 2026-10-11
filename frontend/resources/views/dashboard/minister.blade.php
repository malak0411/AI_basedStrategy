@extends('layouts.app')


@section('title', 'لوحة الوزير')


@push('styles')
<style>
    :root { --navy:#0D1B3E; --blue:#1e40af; --blue-l:#3b82f6; --gray:#5b6580; --line:#e6e9f2; }


    .min-hero {
        background: linear-gradient(135deg, #0D1B3E 0%, #1e40af 60%, #2563eb 100%);
        color: #fff; border-radius: 18px; padding: 28px 32px; margin-bottom: 24px;
    }
    .min-hero h4 { font-weight: 700; margin: 0 0 4px; }
    .min-hero .meta { color: #cdd6ee; font-size: .9rem; }
    .min-hero .index-ring {
        width: 140px; height: 140px; border-radius: 50%;
        border: 6px solid rgba(255,255,255,.15);
        display: flex; flex-direction: column;
        align-items: center; justify-content: center;
    }
    .min-hero .index-ring .v { font-size: 2.4rem; font-weight: 800; line-height: 1; }
    .min-hero .index-ring .l { font-size: .75rem; color: #cdd6ee; margin-top: 6px; }


    .min-tabs .nav-link {
        border: 0; color: var(--gray); font-weight: 600;
        padding: 12px 22px; border-radius: 10px;
        margin-inline-end: 6px; background: #f4f6fb;
        font-size: .88rem;
    }
    .min-tabs .nav-link.active { background: var(--navy); color: #fff; }


    .card-soft { background:#fff; border:1px solid var(--line); border-radius: 14px; padding: 20px; height:100%; }
    .card-soft .h { font-weight: 700; color: var(--navy); margin-bottom: 14px; font-size: .95rem; }


    .kpi-box {
        background: #fff; border: 1px solid var(--line); border-radius: 14px;
        padding: 16px; position: relative; height: 100%;
    }
    .kpi-box .icon-badge {
        width: 38px; height: 38px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: .95rem; margin-bottom: 10px;
    }
    .kpi-box .v { font-size: 1.6rem; font-weight: 800; color: var(--navy); line-height: 1; }
    .kpi-box .l { color: var(--gray); font-size: .76rem; margin-top: 4px; }


    .health-card {
        background: #fff; border: 1px solid var(--line); border-radius: 14px;
        padding: 18px; text-align: center; height: 100%;
    }
    .health-card .v { font-size: 2rem; font-weight: 800; line-height: 1; }
    .health-card .l { font-size: .82rem; color: var(--gray); margin-top: 6px; }
    .health-card.green { border-inline-start: 5px solid #4caf50; }
    .health-card.yellow{ border-inline-start: 5px solid #f59e0b; }
    .health-card.red   { border-inline-start: 5px solid #ef4444; }


    .pill { padding: 4px 11px; border-radius: 20px; font-size: .72rem; font-weight: 600; display:inline-block; }
    .pill.blue   { background:#eff6ff; color:#1d4ed8; }
    .pill.green  { background:#ecfdf5; color:#047857; }
    .pill.red    { background:#fef2f2; color:#b91c1c; }
    .pill.amber  { background:#fffbeb; color:#b45309; }
    .pill.purple { background:#f5f3ff; color:#6d28d9; }
    .pill.gray   { background:#f3f4f6; color:#4b5563; }
    .pill.cyan   { background:#ecfeff; color:#0e7490; }


    .tbl-clean { font-size: .85rem; margin-bottom: 0; }
    .tbl-clean thead th {
        background: #f7f9fd; color: var(--gray); font-weight: 600;
        font-size: .78rem; padding: 11px 12px;
        border-bottom: 1px solid var(--line); white-space: nowrap;
    }
    .tbl-clean tbody td { padding: 12px; vertical-align: middle; border-bottom: 1px solid #f1f4fa; }
    .tbl-clean tbody tr:hover { background: #fafbff; }


    .progress-thin { height: 7px; background: #eef1f7; border-radius: 5px; overflow: hidden; }
    .progress-thin > span {
        display: block; height: 100%;
        background: linear-gradient(90deg, var(--blue), var(--blue-l));
        border-radius: 5px;
    }
    .progress-thin.ok > span   { background: linear-gradient(90deg, #027a48, #4caf50); }
    .progress-thin.warn > span { background: linear-gradient(90deg, #b54708, #f59e0b); }
    .progress-thin.bad > span  { background: linear-gradient(90deg, #b42318, #ef4444); }


    .strategy-tree {
        padding: 20px; background: #fbfcff;
        border-radius: 12px; border: 1px solid var(--line);
    }
    .pillar-block {
        background: #fff; border: 1px solid var(--line);
        border-radius: 12px; padding: 16px; margin-bottom: 14px;
        border-inline-start: 5px solid var(--blue);
    }
    .pillar-block .pillar-title {
        font-weight: 800; color: var(--navy); font-size: 1rem;
        margin-bottom: 10px; display: flex; justify-content: space-between;
        align-items: center;
    }
    .pillar-link {
        text-decoration: none; color: inherit;
        display: block; transition: .15s;
    }
    .pillar-link:hover .pillar-title { color: var(--blue); }
    .pillar-link:hover { box-shadow: 0 4px 14px rgba(13,27,62,.06); }


    .goal-link { display: block; text-decoration: none; color: inherit; }
    .goal-line {
        padding: 10px 12px; background: #f7f9fd;
        border-radius: 8px; margin-bottom: 8px;
        border-inline-start: 3px solid #3b82f6;
        transition: .15s;
    }
    .goal-line:hover { background: #eff6ff; border-inline-start-color: #1d4ed8; }
    .goal-line.current-goal { border-inline-start-color: #4caf50; background: #f0fdf4; }
    .goal-line .goal-title { font-weight: 700; color: var(--navy); font-size: .88rem; }


    .program-line {
        padding: 8px 12px; margin-top: 6px; margin-inline-start: 14px;
        background: #fff; border-radius: 6px;
        border-inline-start: 3px solid #8b5cf6;
        font-size: .82rem;
        display: block; text-decoration: none; color: inherit;
        transition: .15s;
    }
    .program-line:hover { background: #f5f3ff; }


    .initiative-line {
        padding: 6px 10px; margin-top: 4px; margin-inline-start: 14px;
        background: #fafbff; border-radius: 6px;
        font-size: .78rem; display: flex;
        justify-content: space-between; align-items: center;
        border: 1px solid #eef1f7;
        text-decoration: none; color: inherit;
        transition: .15s;
    }
    .initiative-line:hover { background: #eff6ff; border-color: #bfdbfe; }
    .initiative-line .prog-bar { width: 100px; }


    .dept-rank-item {
        display: flex; align-items: center; gap: 12px;
        padding: 10px 0; border-bottom: 1px solid #f1f4fa;
    }
    .dept-rank-item:last-child { border-bottom: 0; }
    .rank-badge {
        width: 32px; height: 32px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-weight: 700; color: #fff; font-size: .82rem; flex-shrink: 0;
    }
    .rank-badge.gold   { background: linear-gradient(135deg, #f59e0b, #fbbf24); }
    .rank-badge.silver { background: linear-gradient(135deg, #6b7280, #9ca3af); }
    .rank-badge.bronze { background: linear-gradient(135deg, #92400e, #b45309); }
    .rank-badge.normal { background: #cbd5e1; color: #334155; }


    .perf-card {
        display: flex; align-items: center; gap: 12px;
        padding: 12px; background: #fafbff;
        border-radius: 10px; border: 1px solid #eef1f7;
        margin-bottom: 10px;
    }
    .perf-avatar {
        width: 44px; height: 44px; border-radius: 50%;
        background: linear-gradient(135deg, var(--navy), var(--blue-l));
        color: #fff; font-weight: 700; font-size: 1rem;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }


    .scope-box {
        background: #fff; border: 1px solid var(--line); border-radius: 12px;
        padding: 14px; text-align: center;
    }
    .scope-box .v { font-size: 1.6rem; font-weight: 800; color: var(--navy); line-height: 1; }
    .scope-box .l { font-size: .75rem; color: var(--gray); margin-top: 4px; }


    .chart-wrap { position: relative; height: 280px; }
    .chart-wrap-sm { position: relative; height: 220px; }


    .dept-full-card {
        border: 1px solid var(--line); border-radius: 14px;
        padding: 18px; background: #fff; margin-bottom: 16px;
    }
    .dept-full-card .dept-head {
        display: flex; justify-content: space-between; align-items: center;
        margin-bottom: 12px; padding-bottom: 10px;
        border-bottom: 2px solid #eef1f7;
    }
    .dept-full-card .dept-name { font-weight: 800; color: var(--navy); font-size: 1rem; }
    .dept-mini-kpi {
        background: #fafbff; border-radius: 8px; padding: 8px 10px;
        text-align: center;
    }
    .dept-mini-kpi .v { font-weight: 700; font-size: 1rem; color: var(--navy); }
    .dept-mini-kpi .l { font-size: .68rem; color: var(--gray); }


    .major-task-chip {
        padding: 8px 12px; background: #fafbff;
        border-radius: 8px; margin-bottom: 6px;
        border-inline-start: 3px solid #3b82f6;
        display: flex; justify-content: space-between; align-items: center;
        font-size: .82rem;
    }
</style>
@endpush


@section('content')
<div class="container-fluid" dir="rtl">


    @php
        $scope = $data['scope'] ?? [];
        $ov    = $data['overview'] ?? [];
        $map   = $data['strategy_map'] ?? [];
        $mp    = $data['ministerial_performance'] ?? [];
        $dp    = $data['departmental_performance'] ?? [];
        $ep    = $data['employee_performance'] ?? [];
        $ai    = $data['ai_insights'] ?? [];
        $health = $ov['kpi_health'] ?? [];
    @endphp


    @if(!empty($error))
        <div class="alert alert-danger">{{ $error }}</div>
    @else


    <div class="min-hero">
        <div class="row align-items-center g-4">
            <div class="col-md-8">
                <h4>لوحة الوزير الاستراتيجية</h4>
                <div class="meta mb-3">
                    {{ now()->translatedFormat('l، d F Y') }} • نظرة شاملة على الأداء الاستراتيجي
                </div>
                @if(!empty($ai))
                    @foreach($ai as $ins)
                        <div class="alert alert-{{ $ins['type'] == 'danger' ? 'danger' : ($ins['type'] == 'warning' ? 'warning' : 'success') }} py-2 px-3 mb-2 small" style="background:rgba(255,255,255,.1);color:#fff;border:0;">
                            {{ $ins['text'] }}
                        </div>
                    @endforeach
                @endif
            </div>
            <div class="col-md-4 text-center">
                <div class="index-ring mx-auto">
                    <div class="v">{{ $ov['overall_index'] ?? 0 }}%</div>
                    <div class="l">مؤشر الأداء العام</div>
                </div>
            </div>
        </div>
    </div>


    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="scope-box">
                <div class="v">{{ $scope['current_goals_count'] ?? 0 }}</div>
                <div class="l">أهداف سارية</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="scope-box">
                <div class="v">{{ $scope['current_programs_count'] ?? 0 }}</div>
                <div class="l">برامج مرتبطة</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="scope-box">
                <div class="v">{{ $scope['active_initiatives_count'] ?? 0 }}</div>
                <div class="l">مبادرات قيد التنفيذ</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="scope-box">
                <div class="v">{{ $scope['active_tasks_count'] ?? 0 }}</div>
                <div class="l">مهام تشغيلية نشطة</div>
            </div>
        </div>
    </div>


    <ul class="nav min-tabs mb-3 flex-wrap" role="tablist">
        <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-map" type="button">
                <i class="fas fa-project-diagram me-1"></i> خريطة الاستراتيجية
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-ministerial" type="button">
                <i class="fas fa-crown me-1"></i> أداء الوزارة
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-dept" type="button">
                <i class="fas fa-building me-1"></i> أداء الإدارات
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-emp" type="button">
                <i class="fas fa-users me-1"></i> أداء الموظفين
            </button>
        </li>
    </ul>


    <div class="tab-content">


        <div class="tab-pane fade show active" id="tab-map">


            <div class="row g-3 mb-4">
                <div class="col-md-3 col-6">
                    <div class="health-card green">
                        <div class="v text-success">{{ $health['green'] ?? 0 }}</div>
                        <div class="l">مؤشرات على الطريق الصحيح</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="health-card yellow">
                        <div class="v" style="color:#d97706;">{{ $health['yellow'] ?? 0 }}</div>
                        <div class="l">تحتاج متابعة</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="health-card red">
                        <div class="v text-danger">{{ $health['red'] ?? 0 }}</div>
                        <div class="l">حرجة / في خطر</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="health-card">
                        <div class="v" style="color:#1d4ed8;">{{ $health['total'] ?? 0 }}</div>
                        <div class="l">إجمالي المؤشرات</div>
                    </div>
                </div>
            </div>


            <div class="card-soft mb-4">
                <div class="h">
                    <i class="fas fa-sitemap text-primary me-2"></i> خريطة العلاقات الاستراتيجية
                    <small class="text-muted ms-2" style="font-size:.72rem;">
                        انقر على أي عنصر للانتقال إلى تفاصيله
                    </small>
                </div>
                <div class="strategy-tree">
                    @forelse($map['pillars'] ?? [] as $pillar)
                        <div class="pillar-block">
                            <a href="{{ route('strategic.pillars.show', $pillar['pillar_id']) }}"
                               class="pillar-link">
                                <div class="pillar-title">
                                    <span><i class="fas fa-flag me-2 text-primary"></i>{{ $pillar['name'] }}</span>
                                    <span class="pill blue">{{ $pillar['progress'] }}%</span>
                                </div>
                            </a>
                            @foreach($pillar['goals'] ?? [] as $goal)
                                <a href="{{ route('strategic.goals.show', $goal['goal_id']) }}"
                                   class="goal-link">
                                    <div class="goal-line {{ !empty($goal['is_current']) ? 'current-goal' : '' }}">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="goal-title">
                                                <i class="fas fa-bullseye text-success me-1"></i>{{ $goal['title'] }}
                                                @if(!empty($goal['is_current']))
                                                    <span class="pill green ms-1">ساري</span>
                                                @endif
                                            </span>
                                            <span class="pill green">{{ $goal['progress'] }}%</span>
                                        </div>
                                        @if(!empty($goal['target_date']))
                                            <small class="text-muted">الاستحقاق: {{ $goal['target_date'] }}</small>
                                        @endif
                                    </div>
                                </a>
                                @foreach($goal['programs'] ?? [] as $prog)
                                    <a href="{{ route('strategic.programs.show', $prog['program_id']) }}"
                                       class="program-line">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span>
                                                <i class="fas fa-layer-group text-purple me-1"></i>
                                                {{ $prog['name'] }}
                                                @if(!empty($prog['status_ar']))
                                                    <span class="pill ms-1"
                                                          style="background: {{ $prog['status_color'] }}22; color: {{ $prog['status_color'] }};">
                                                        {{ $prog['status_ar'] }}
                                                    </span>
                                                @endif
                                            </span>
                                            <span class="pill purple">{{ $prog['progress'] }}%</span>
                                        </div>
                                    </a>
                                    @foreach($prog['initiatives'] ?? [] as $ini)
                                        <a href="{{ route('strategic.initiatives.show', $ini['initiative_id']) }}"
                                           class="initiative-line">
                                            <span>
                                                <i class="fas fa-rocket text-info me-1"></i>
                                                {{ $ini['name'] }}
                                                @if(!empty($ini['status_ar']))
                                                    <span class="pill ms-1"
                                                          style="background: {{ $ini['status_color'] }}22; color: {{ $ini['status_color'] }};">
                                                        {{ $ini['status_ar'] }}
                                                    </span>
                                                @endif
                                                @if(!empty($ini['is_delayed']))
                                                    <span class="pill red ms-1">متأخرة</span>
                                                @endif
                                            </span>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="progress-thin prog-bar">
                                                    <span style="width: {{ $ini['progress'] }}%"></span>
                                                </div>
                                                <small class="text-muted">{{ $ini['progress'] }}%</small>
                                            </div>
                                        </a>
                                    @endforeach
                                @endforeach
                            @endforeach
                        </div>
                    @empty
                        <div class="text-center text-muted py-4">لا توجد بيانات استراتيجية.</div>
                    @endforelse
                </div>
            </div>


            <div class="row g-3">
                <div class="col-lg-4">
                    <div class="card-soft">
                        <div class="h"><i class="fas fa-chart-pie text-success me-2"></i> إحصائيات المبادرات قيد التنفيذ</div>
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted">إجمالي المبادرات النشطة</span>
                            <strong>{{ $ov['programs']['total'] ?? 0 }}</strong>
                        </div>
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted">مكتملة</span>
                            <strong class="text-success">{{ $ov['programs']['completed'] ?? 0 }}</strong>
                        </div>
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted">قيد التنفيذ</span>
                            <strong class="text-primary">{{ $ov['programs']['active'] ?? 0 }}</strong>
                        </div>
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted">متأخرة</span>
                            <strong class="text-danger">{{ $ov['programs']['delayed'] ?? 0 }}</strong>
                        </div>
                        <div class="mt-3">
                            <div class="progress-thin ok"><span style="width: {{ $ov['programs']['completion_pct'] ?? 0 }}%"></span></div>
                            <small class="text-muted">نسبة الإنجاز: {{ $ov['programs']['completion_pct'] ?? 0 }}%</small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card-soft">
                        <div class="h"><i class="fas fa-coins text-warning me-2"></i> الملخص المالي الاستراتيجي</div>
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted">إجمالي الميزانية المرصودة</span>
                            <strong>{{ number_format($ov['financial']['allocated'] ?? 0, 0) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted">الصرف الفعلي</span>
                            <strong class="text-primary">{{ number_format($ov['financial']['spent'] ?? 0, 0) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted">المتبقي</span>
                            <strong class="text-success">{{ number_format($ov['financial']['remaining'] ?? 0, 0) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between py-2">
                            <span class="text-muted">نسبة الصرف</span>
                            <strong>{{ $ov['financial']['spent_pct'] ?? 0 }}%</strong>
                        </div>
                        <div class="mt-3">
                            <div class="progress-thin"><span style="width: {{ $ov['financial']['spent_pct'] ?? 0 }}%"></span></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card-soft">
                        <div class="h"><i class="fas fa-heartbeat text-danger me-2"></i> صحة المؤشرات</div>
                        <div class="chart-wrap-sm"><canvas id="chartKpiHealth"></canvas></div>
                    </div>
                </div>
            </div>
        </div>


        <div class="tab-pane fade" id="tab-ministerial">


            <div class="card-soft mb-4">
                <div class="h"><i class="fas fa-bullseye text-success me-2"></i> مؤشرات الأثر والنتائج</div>
                <div class="table-responsive">
                    <table class="table tbl-clean align-middle">
                        <thead>
                            <tr>
                                <th>المؤشر</th>
                                <th>التصنيف</th>
                                <th>الهدف</th>
                                <th>القيمة الحالية</th>
                                <th>نسبة التحقيق</th>
                                <th>الاتجاه</th>
                                <th>الصحة</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($mp['impact_kpis'] ?? [] as $k)
                            @php
                                $h = $k['health'] ?? 'red';
                                $hCls = $h === 'green' ? 'green' : ($h === 'yellow' ? 'amber' : 'red');
                                $tCls = ($k['trend'] ?? 'flat') === 'up' ? 'green' : (($k['trend'] ?? 'flat') === 'down' ? 'red' : 'gray');
                            @endphp
                            <tr>
                                <td class="fw-semibold">{{ $k['name'] }}</td>
                                <td><span class="pill gray">{{ $k['category'] ?? '—' }}</span></td>
                                <td>{{ $k['target'] }} {{ $k['unit'] ?? '' }}</td>
                                <td>{{ $k['current'] }} {{ $k['unit'] ?? '' }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress-thin {{ $h === 'green' ? 'ok' : ($h === 'yellow' ? 'warn' : 'bad') }} flex-grow-1">
                                            <span style="width: {{ min($k['achievement'], 100) }}%"></span>
                                        </div>
                                        <small class="fw-semibold" style="min-width:50px;">{{ $k['achievement'] }}%</small>
                                    </div>
                                </td>
                                <td>
                                    <span class="pill {{ $tCls }}">
                                        <i class="fas fa-arrow-{{ $k['trend'] === 'up' ? 'up' : ($k['trend'] === 'down' ? 'down' : 'right') }}"></i>
                                        {{ $k['trend'] }}
                                    </span>
                                </td>
                                <td><span class="pill {{ $hCls }}">{{ $h === 'green' ? 'جيد' : ($h === 'yellow' ? 'متابعة' : 'حرج') }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">لا توجد مؤشرات أثر.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>


            <div class="card-soft mb-4">
                <div class="h"><i class="fas fa-rocket text-primary me-2"></i> متابعة المبادرات التحولية</div>
                <div class="table-responsive">
                    <table class="table tbl-clean align-middle">
                        <thead>
                            <tr>
                                <th>المبادرة</th>
                                <th>الركيزة</th>
                                <th>الهدف</th>
                                <th>الأولوية</th>
                                <th>التقدم</th>
                                <th>الاستحقاق</th>
                                <th>التأخر</th>
                                <th>المهام</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($mp['transformational_initiatives'] ?? [] as $i)
                            <tr>
                                <td class="fw-semibold">
                                    <a href="{{ route('strategic.initiatives.show', $i['initiative_id']) }}"
                                       class="text-decoration-none" style="color:var(--navy);">
                                        {{ $i['name'] }}
                                    </a>
                                </td>
                                <td class="text-muted small">{{ $i['pillar_name'] ?? '—' }}</td>
                                <td class="text-muted small">{{ $i['goal_name'] ?? '—' }}</td>
                                <td>
                                    @php
                                        $p = (int)($i['priority_id'] ?? 3);
                                        $pCls = $p === 1 ? 'red' : ($p === 2 ? 'amber' : 'gray');
                                        $pAr = $p === 1 ? 'عاجل' : ($p === 2 ? 'مرتفع' : 'متوسط');
                                    @endphp
                                    <span class="pill {{ $pCls }}">{{ $pAr }}</span>
                                </td>
                                <td style="min-width:150px;">
                                    <div class="progress-thin {{ ($i['progress'] ?? 0) >= 75 ? 'ok' : (($i['progress'] ?? 0) >= 40 ? '' : 'warn') }}">
                                        <span style="width: {{ $i['progress'] }}%"></span>
                                    </div>
                                    <small class="text-muted">{{ $i['progress'] }}%</small>
                                </td>
                                <td class="text-muted small">{{ $i['end_date'] ?? '—' }}</td>
                                <td>
                                    @if(($i['delay_days'] ?? 0) > 0)
                                        <span class="pill red">+{{ $i['delay_days'] }} يوم</span>
                                    @else
                                        <span class="pill green">في الوقت</span>
                                    @endif
                                </td>
                                <td><span class="pill blue">{{ $i['task_count'] ?? 0 }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">لا توجد مبادرات قيد التنفيذ.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>


            <div class="row g-3 mb-4">
                <div class="col-lg-7">
                    <div class="card-soft">
                        <div class="h"><i class="fas fa-chart-line text-primary me-2"></i> اتجاهات الأداء خلال السنة</div>
                        <div class="chart-wrap"><canvas id="chartKpiTrend"></canvas></div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="card-soft">
                        <div class="h"><i class="fas fa-tasks text-success me-2"></i> اتجاهات المهام خلال السنة</div>
                        <div class="chart-wrap"><canvas id="chartTaskTrend"></canvas></div>
                    </div>
                </div>
            </div>


            <div class="card-soft">
                <div class="h"><i class="fas fa-shield-alt text-danger me-2"></i> إدارة المخاطر الاستراتيجية</div>
                <div class="table-responsive">
                    <table class="table tbl-clean align-middle">
                        <thead>
                            <tr>
                                <th>الخطر</th>
                                <th>المبادرة</th>
                                <th>المهمة</th>
                                <th>الاحتمالية</th>
                                <th>الأثر</th>
                                <th>Score</th>
                                <th>المستوى</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($mp['strategic_risks'] ?? [] as $r)
                            <tr>
                                <td class="fw-semibold">{{ $r['name'] }}</td>
                                <td class="text-muted small">{{ $r['initiative'] ?? '—' }}</td>
                                <td class="text-muted small">{{ $r['task'] ?? '—' }}</td>
                                <td>{{ $r['probability'] }}/10</td>
                                <td>{{ $r['impact'] }}/10</td>
                                <td><strong style="color: {{ $r['level_color'] }};">{{ $r['score'] }}</strong></td>
                                <td>
                                    <span class="pill"
                                          style="background: {{ $r['level_color'] }}22; color: {{ $r['level_color'] }};">
                                        {{ $r['level_ar'] }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">لا توجد مخاطر استراتيجية.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>


        <div class="tab-pane fade" id="tab-dept">


            @forelse($dp['ranking'] ?? [] as $d)
                @php
                    $pct = (float)($d['completion_rate'] ?? 0);
                    $barCls = $pct >= 75 ? 'ok' : ($pct >= 40 ? '' : 'warn');
                    $hasMajors = ($d['major_tasks_count'] ?? 0) > 0;
                @endphp
                <div class="dept-full-card">
                    <div class="dept-head">
                        <div>
                            <div class="dept-name">
                                <i class="fas fa-building text-primary me-2"></i>
                                {{ $d['name'] }}
                            </div>
                            @if($hasMajors)
                                <small class="text-muted">
                                    {{ $d['major_lead_count'] }} مهام رئيسية مسؤولة •
                                    {{ $d['major_support_count'] }} مساندة
                                    @if(($d['major_co_lead_count'] ?? 0) > 0)
                                        • {{ $d['major_co_lead_count'] }} مشاركة
                                    @endif
                                </small>
                            @else
                                <small class="text-muted fst-italic">
                                    <i class="fas fa-info-circle"></i> لا توجد مهام رئيسية مسندة لهذه الإدارة ضمن المبادرات النشطة
                                </small>
                            @endif
                        </div>
                        <div>
                            <span class="pill {{ $pct >= 75 ? 'green' : ($pct >= 40 ? 'amber' : 'red') }}">
                                {{ $pct }}% إنجاز
                            </span>
                        </div>
                    </div>


                    <div class="row g-2 mb-3">
                        <div class="col-6 col-md-2">
                            <div class="dept-mini-kpi">
                                <div class="v">{{ $d['major_tasks_count'] }}</div>
                                <div class="l">مهام رئيسية</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-2">
                            <div class="dept-mini-kpi">
                                <div class="v">{{ $d['total_tasks'] }}</div>
                                <div class="l">مهام تشغيلية</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-2">
                            <div class="dept-mini-kpi">
                                <div class="v text-success">{{ $d['completed'] }}</div>
                                <div class="l">مكتملة</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-2">
                            <div class="dept-mini-kpi">
                                <div class="v text-primary">{{ $d['in_progress'] }}</div>
                                <div class="l">قيد التنفيذ</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-2">
                            <div class="dept-mini-kpi">
                                <div class="v text-danger">{{ $d['delayed'] }}</div>
                                <div class="l">متأخرة</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-2">
                            <div class="dept-mini-kpi">
                                <div class="v" style="color:#6d28d9;">{{ $d['review'] }}</div>
                                <div class="l">قيد المراجعة</div>
                            </div>
                        </div>
                    </div>


                    @if($hasMajors)
                        <div class="row g-3">
                            <div class="col-lg-6">
                                <div class="fw-bold small mb-2" style="color:var(--navy);">
                                    <i class="fas fa-layer-group text-primary me-1"></i> المهام الرئيسية المُسندة
                                </div>
                                @foreach($d['major_tasks_detail'] ?? [] as $mt)
                                    <a href="{{ url('/operational/major-task/' . $mt['major_task_id']) }}"
                                       class="major-task-chip text-decoration-none" style="color:inherit;">
                                        <div>
                                            <div class="fw-semibold" style="font-size:.82rem;">{{ $mt['name'] }}</div>
                                            <small class="text-muted" style="font-size:.7rem;">
                                                {{ $mt['initiative_name'] ?? '—' }} •
                                                {{ $mt['task_count'] }} مهمة •
                                                {{ $mt['completed'] }} مكتملة
                                            </small>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="pill {{ $mt['responsibility_ar'] === 'مسؤولة' ? 'green' : ($mt['responsibility_ar'] === 'مساندة' ? 'cyan' : 'purple') }}"
                                                  style="font-size:.65rem;">
                                                {{ $mt['responsibility_ar'] }}
                                            </span>
                                            <span class="fw-bold" style="font-size:.8rem;color:var(--navy);">{{ $mt['progress'] }}%</span>
                                        </div>
                                    </a>
                                @endforeach
                            </div>


                            <div class="col-lg-6">
                                <div class="fw-bold small mb-2" style="color:var(--navy);">
                                    <i class="fas fa-chart-line text-success me-1"></i> إحصائيات سير العمل والمخاطر
                                </div>
                                <div class="row g-2 mb-2">
                                    <div class="col-6">
                                        <div class="dept-mini-kpi">
                                            <div class="v text-success">{{ $d['avg_progress'] }}%</div>
                                            <div class="l">متوسط التقدم</div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="dept-mini-kpi">
                                            <div class="v" style="color:#0e7490;">{{ $d['lead_avg_progress'] }}%</div>
                                            <div class="l">تقدم المهام المسؤولة</div>
                                        </div>
                                    </div>
                                </div>


                                <div class="row g-2 mb-2">
                                    <div class="col-4">
                                        <div class="dept-mini-kpi">
                                            <div class="v text-danger">{{ $d['risk_total'] }}</div>
                                            <div class="l">مخاطر</div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="dept-mini-kpi">
                                            <div class="v" style="color:#b45309;">{{ $d['risk_high'] }}</div>
                                            <div class="l">عالية</div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="dept-mini-kpi">
                                            <div class="v" style="color:#b91c1c;">{{ $d['risk_critical'] }}</div>
                                            <div class="l">حرجة</div>
                                        </div>
                                    </div>
                                </div>


                                <div class="row g-2">
                                    <div class="col-4">
                                        <div class="dept-mini-kpi">
                                            <div class="v">{{ $d['mit_total'] }}</div>
                                            <div class="l">إجراءات</div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="dept-mini-kpi">
                                            <div class="v" style="color:#b45309;">{{ $d['mit_pending'] + $d['mit_active'] }}</div>
                                            <div class="l">جارية/بالانتظار</div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="dept-mini-kpi">
                                            <div class="v text-success">{{ $d['mit_completed'] }}</div>
                                            <div class="l">مكتملة</div>
                                        </div>
                                    </div>
                                </div>


                                @if($d['spent_pct'] > 0)
                                    <div class="mt-3">
                                        <div class="d-flex justify-content-between small text-muted mb-1">
                                            <span>استهلاك الميزانية</span>
                                            <span class="fw-bold">{{ $d['spent_pct'] }}%</span>
                                        </div>
                                        <div class="progress-thin">
                                            <span style="width: {{ min($d['spent_pct'], 100) }}%"></span>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @else
                        <div class="alert alert-light border-0 mb-0 small text-muted text-center py-3">
                            <i class="fas fa-inbox"></i>
                            لا توجد بيانات أداء لهذه الإدارة في المبادرات النشطة الحالية.
                        </div>
                    @endif
                </div>
            @empty
                <div class="text-center text-muted py-5">لا توجد إدارات نشطة.</div>
            @endforelse
        </div>


        <div class="tab-pane fade" id="tab-emp">


            <div class="row g-3 mb-4">
                <div class="col-md-3 col-6">
                    <div class="kpi-box">
                        <div class="icon-badge" style="background:#eff6ff;color:#1d4ed8;"><i class="fas fa-users"></i></div>
                        <div class="v">{{ $ep['productivity']['total_employees'] ?? 0 }}</div>
                        <div class="l">إجمالي الموظفين</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="kpi-box">
                        <div class="icon-badge" style="background:#ecfdf5;color:#047857;"><i class="fas fa-percentage"></i></div>
                        <div class="v text-success">{{ $ep['productivity']['avg_completion'] ?? 0 }}%</div>
                        <div class="l">متوسط الإنتاجية</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="kpi-box">
                        <div class="icon-badge" style="background:#f5f3ff;color:#6d28d9;"><i class="fas fa-tasks"></i></div>
                        <div class="v" style="color:#6d28d9;">{{ $ep['productivity']['total_tasks'] ?? 0 }}</div>
                        <div class="l">إجمالي المهام المسندة</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="kpi-box">
                        <div class="icon-badge" style="background:#fef2f2;color:#b91c1c;"><i class="fas fa-check-double"></i></div>
                        <div class="v text-success">{{ $ep['productivity']['strategic_completion'] ?? 0 }}%</div>
                        <div class="l">نسبة إنجاز المهام</div>
                    </div>
                </div>
            </div>


            <div class="row g-3 mb-4">
                <div class="col-lg-6">
                    <div class="card-soft">
                        <div class="h"><i class="fas fa-trophy text-warning me-2"></i> أفضل الكوادر</div>
                        @forelse($ep['top_performers'] ?? [] as $idx => $e)
                            <div class="perf-card">
                                <div class="perf-avatar">{{ mb_substr($e['full_name'] ?? '?', 0, 1, 'UTF-8') }}</div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="fw-bold" style="color:var(--navy);font-size:.9rem;">{{ $e['full_name'] }}</div>
                                            <small class="text-muted">{{ $e['job_title'] ?? '—' }} • {{ $e['department_name'] ?? '' }}</small>
                                        </div>
                                        <div class="text-end">
                                            <div class="fw-bold text-success">{{ $e['completion_rate'] }}%</div>
                                            <small class="text-muted">{{ $e['completed'] }} / {{ $e['total_tasks'] }}</small>
                                        </div>
                                    </div>
                                    <div class="progress-thin ok mt-2">
                                        <span style="width: {{ $e['completion_rate'] }}%"></span>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center text-muted py-4">لا توجد بيانات.</div>
                        @endforelse
                    </div>
                </div>


                <div class="col-lg-6">
                    <div class="card-soft">
                        <div class="h"><i class="fas fa-chart-pie text-success me-2"></i> توزيع الموظفين حسب الأداء</div>
                        <div class="chart-wrap mb-3"><canvas id="chartEmpDistribution"></canvas></div>
                    </div>
                </div>
            </div>


            <div class="card-soft mb-4">
                <div class="h">
                    <i class="fas fa-users text-primary me-2"></i>
                    جميع الموظفين في الوزارة ({{ count($ep['all_employees'] ?? []) }})
                </div>
                <div class="table-responsive">
                    <table class="table tbl-clean align-middle">
                        <thead>
                            <tr>
                                <th>الموظف</th>
                                <th>الإدارة</th>
                                <th>المهام</th>
                                <th>مكتملة</th>
                                <th>متأخرة</th>
                                <th>نسبة الإنجاز</th>
                                <th>متوسط التقدم</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($ep['all_employees'] ?? [] as $e)
                            @php
                                $pct = (float)($e['completion_rate'] ?? 0);
                                $barCls = $pct >= 75 ? 'ok' : ($pct >= 40 ? '' : 'warn');
                            @endphp
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="perf-avatar" style="width:34px;height:34px;font-size:.8rem;">
                                            {{ mb_substr($e['full_name'] ?? '?', 0, 1, 'UTF-8') }}
                                        </div>
                                        <div>
                                            <div class="fw-semibold" style="font-size:.85rem;">{{ $e['full_name'] }}</div>
                                            <small class="text-muted" style="font-size:.7rem;">{{ $e['job_title'] ?? '—' }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-muted small">{{ $e['department_name'] ?? '—' }}</td>
                                <td>{{ $e['total_tasks'] }}</td>
                                <td class="text-success fw-bold">{{ $e['completed'] }}</td>
                                <td class="text-danger fw-bold">{{ $e['delayed'] }}</td>
                                <td style="min-width:130px;">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress-thin {{ $barCls }} flex-grow-1">
                                            <span style="width: {{ $pct }}%"></span>
                                        </div>
                                        <small class="fw-semibold" style="min-width:42px;">{{ $pct }}%</small>
                                    </div>
                                </td>
                                <td><span class="pill gray">{{ $e['avg_progress'] }}%</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">لا يوجد موظفون.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>


            <div class="row g-3">
                <div class="col-lg-6">
                    <div class="card-soft">
                        <div class="h"><i class="fas fa-user-tie text-primary me-2"></i> أداء قيادات الصف الثاني والثالث</div>
                        <div class="table-responsive">
                            <table class="table tbl-clean align-middle">
                                <thead>
                                    <tr>
                                        <th>القائد</th>
                                        <th>المسمى الوظيفي</th>
                                        <th>المهام</th>
                                        <th>مكتملة</th>
                                        <th>الإنجاز</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @forelse($ep['leaders'] ?? [] as $l)
                                    <tr>
                                        <td class="fw-semibold">{{ $l['full_name'] }}</td>
                                        <td class="text-muted small">{{ $l['job_title'] ?? '—' }}</td>
                                        <td>{{ $l['total_tasks'] }}</td>
                                        <td class="text-success">{{ $l['completed'] }}</td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="progress-thin {{ $l['completion_rate'] >= 75 ? 'ok' : ($l['completion_rate'] >= 40 ? '' : 'warn') }} flex-grow-1">
                                                    <span style="width: {{ $l['completion_rate'] }}%"></span>
                                                </div>
                                                <small class="fw-semibold" style="min-width:45px;">{{ $l['completion_rate'] }}%</small>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted py-4">لا توجد بيانات.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>


                <div class="col-lg-6">
                    <div class="card-soft">
                        <div class="h"><i class="fas fa-chart-pie text-primary me-2"></i> توزيع المهام التشغيلية حسب الحالة</div>
                        <div class="chart-wrap"><canvas id="chartTaskStatus"></canvas></div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    @endif
</div>
@endsection


@push('scripts')
<script src="{{ asset('vendor/chartjs/chart.umd.min.js') }}"></script>
<script>
(function () {
    const kpiHealth = @json($health);
    const kpiTrend  = @json($mp['kpi_trend'] ?? []);
    const taskTrend = @json($mp['task_trend'] ?? []);
    const empDist   = @json($ep['distribution'] ?? []);
    const taskStatus = @json($data['task_status_distribution'] ?? []);


    const cd1 = document.getElementById('chartKpiHealth');
    if (cd1) {
        new Chart(cd1, {
            type: 'doughnut',
            data: {
                labels: ['صحيح (أخضر)', 'متابعة (أصفر)', 'حرج (أحمر)'],
                datasets: [{
                    data: [kpiHealth.green || 0, kpiHealth.yellow || 0, kpiHealth.red || 0],
                    backgroundColor: ['#4caf50', '#f59e0b', '#ef4444'],
                    borderWidth: 3,
                    borderColor: '#fff',
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
                cutout: '62%',
            }
        });
    }


    const cd2 = document.getElementById('chartKpiTrend');
    if (cd2 && kpiTrend.length) {
        new Chart(cd2, {
            type: 'line',
            data: {
                labels: kpiTrend.map(m => m.label),
                datasets: [{
                    label: 'متوسط قيم المؤشرات',
                    data: kpiTrend.map(m => m.value),
                    borderColor: '#1e40af',
                    backgroundColor: 'rgba(30,64,175,.1)',
                    tension: .35,
                    fill: true,
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
                scales: { y: { beginAtZero: true } }
            }
        });
    }


    const cd3 = document.getElementById('chartTaskTrend');
    if (cd3 && taskTrend.length) {
        new Chart(cd3, {
            type: 'bar',
            data: {
                labels: taskTrend.map(m => m.label),
                datasets: [
                    { label: 'مهام جديدة', data: taskTrend.map(m => m.created), backgroundColor: 'rgba(59,130,246,.7)', borderRadius: 5 },
                    { label: 'مكتملة', data: taskTrend.map(m => m.completed), backgroundColor: 'rgba(76,175,80,.7)', borderRadius: 5 },
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
                scales: { y: { beginAtZero: true } }
            }
        });
    }


    const cd6 = document.getElementById('chartEmpDistribution');
    if (cd6 && empDist) {
        new Chart(cd6, {
            type: 'doughnut',
            data: {
                labels: ['أداء ممتاز (≥75%)', 'أداء جيد (40-74%)', 'يحتاج دعمًا (<40%)'],
                datasets: [{
                    data: [empDist.excellent || 0, empDist.good || 0, empDist.low || 0],
                    backgroundColor: ['#4caf50', '#f59e0b', '#ef4444'],
                    borderWidth: 3,
                    borderColor: '#fff',
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
                cutout: '60%',
            }
        });
    }


    const cd7 = document.getElementById('chartTaskStatus');
    if (cd7 && taskStatus.length) {
        new Chart(cd7, {
            type: 'doughnut',
            data: {
                labels: taskStatus.map(s => s.label),
                datasets: [{
                    data: taskStatus.map(s => s.value),
                    backgroundColor: taskStatus.map(s => s.color),
                    borderWidth: 3,
                    borderColor: '#fff',
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
                cutout: '60%',
            }
        });
    }
})();
</script>
@endpush

@extends('layouts.app')


@section('title', 'لوحة المدير')


@push('styles')
<style>
    :root {
        --navy: #0D1B3E;
        --blue: #1e40af;
        --blue-light: #3b82f6;
        --gray: #5b6580;
        --line: #e6e9f2;
    }


    .mgr-hero {
        background: linear-gradient(135deg, #0D1B3E 0%, #1e40af 60%, #2563eb 100%);
        color: #fff;
        border-radius: 18px;
        padding: 26px 30px;
        margin-bottom: 24px;
        position: relative;
        overflow: hidden;
    }
    .mgr-hero::after {
        content: '';
        position: absolute;
        inset-inline-end: -40px;
        top: -40px;
        width: 200px;
        height: 200px;
        border-radius: 50%;
        background: rgba(255,255,255,.06);
    }
    .mgr-hero h4 { font-weight: 700; margin: 0 0 4px; }
    .mgr-hero .meta { color: #cdd6ee; font-size: .88rem; }
    .mgr-hero .hero-stat { text-align: center; padding: 0 12px; border-inline-start: 1px solid rgba(255,255,255,.15); }
    .mgr-hero .hero-stat:first-child { border-inline-start: none; }
    .mgr-hero .hero-stat .v { font-size: 1.6rem; font-weight: 800; line-height: 1; }
    .mgr-hero .hero-stat .l { font-size: .72rem; color: #cdd6ee; margin-top: 4px; }


    .kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 14px; margin-bottom: 22px; }
    .kpi {
        background: #fff;
        border-radius: 14px;
        padding: 16px;
        box-shadow: 0 2px 12px rgba(13,27,62,.05);
        border: 1px solid var(--line);
        position: relative;
    }
    .kpi .icon {
        position: absolute; top: 14px; inset-inline-end: 14px;
        width: 34px; height: 34px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: .9rem;
    }
    .kpi .icon.blue    { background:#eff6ff; color:#1d4ed8; }
    .kpi .icon.green   { background:#ecfdf5; color:#047857; }
    .kpi .icon.red     { background:#fef2f2; color:#b91c1c; }
    .kpi .icon.amber   { background:#fffbeb; color:#b45309; }
    .kpi .icon.purple  { background:#f5f3ff; color:#6d28d9; }
    .kpi .icon.gray    { background:#f3f4f6; color:#4b5563; }
    .kpi .v { font-size: 1.65rem; font-weight: 800; color: var(--navy); line-height: 1; }
    .kpi .l { color: var(--gray); font-size: .78rem; margin-top: 4px; }


    .mgr-tabs .nav-link {
        border: 0;
        color: var(--gray);
        font-weight: 600;
        padding: 11px 20px;
        border-radius: 10px;
        margin-inline-end: 6px;
        background: #f4f6fb;
        font-size: .88rem;
    }
    .mgr-tabs .nav-link.active {
        background: var(--navy);
        color: #fff;
        box-shadow: 0 4px 12px rgba(13,27,62,.2);
    }
    .mgr-tabs .nav-link .cnt {
        background: rgba(0,0,0,.08);
        color: inherit;
        padding: 1px 8px;
        border-radius: 10px;
        font-size: .7rem;
        margin-inline-start: 6px;
    }
    .mgr-tabs .nav-link.active .cnt { background: rgba(255,255,255,.25); }


    .card-soft { background:#fff; border:1px solid var(--line); border-radius: 14px; padding: 20px; height:100%; }
    .card-soft .h { font-weight: 700; color: var(--navy); margin-bottom: 14px; font-size: .95rem; }


    .major-card {
        background:#fff;
        border:1px solid var(--line);
        border-radius: 14px;
        padding: 18px;
        height: 100%;
        transition: .18s;
        display: block;
        text-decoration: none;
        color: inherit;
    }
    .major-card:hover {
        border-color: var(--blue-light);
        box-shadow: 0 8px 22px rgba(13,27,62,.08);
        transform: translateY(-2px);
    }
    .major-card .t { font-weight: 700; color: var(--navy); font-size: .95rem; margin-bottom: 6px; }
    .major-card .meta { color: var(--gray); font-size: .78rem; }


    .progress-thin { height: 7px; background: #eef1f7; border-radius: 5px; overflow: hidden; }
    .progress-thin > span {
        display:block; height:100%;
        background: linear-gradient(90deg, var(--blue), var(--blue-light));
        border-radius: 5px;
        transition: width .5s;
    }
    .progress-thin.ok > span { background: linear-gradient(90deg, #027a48, #4caf50); }
    .progress-thin.warn > span { background: linear-gradient(90deg, #b54708, #f59e0b); }
    .progress-thin.bad > span { background: linear-gradient(90deg, #b42318, #ef4444); }


    .tbl-clean { font-size: .85rem; margin-bottom: 0; }
    .tbl-clean thead th {
        background: #f7f9fd;
        color: var(--gray);
        font-weight: 600;
        font-size: .78rem;
        padding: 11px 12px;
        border-bottom: 1px solid var(--line);
        white-space: nowrap;
    }
    .tbl-clean tbody td { padding: 12px; vertical-align: middle; border-bottom: 1px solid #f1f4fa; }
    .tbl-clean tbody tr:hover { background: #fafbff; }


    .pill { padding: 4px 11px; border-radius: 20px; font-size: .72rem; font-weight: 600; display:inline-block; }
    .pill.blue   { background:#eff6ff; color:#1d4ed8; }
    .pill.green  { background:#ecfdf5; color:#047857; }
    .pill.red    { background:#fef2f2; color:#b91c1c; }
    .pill.amber  { background:#fffbeb; color:#b45309; }
    .pill.purple { background:#f5f3ff; color:#6d28d9; }
    .pill.gray   { background:#f3f4f6; color:#4b5563; }


    .avatar-inline {
        display:inline-flex; align-items:center; gap:6px;
        padding: 3px 10px 3px 4px; background:#f4f6fb; border-radius: 20px;
        font-size: .74rem; color: var(--navy); margin: 2px 2px 2px 0;
    }
    .avatar-inline .av {
        width: 20px; height: 20px; border-radius: 50%;
        background: linear-gradient(135deg, var(--blue), var(--blue-light));
        color: #fff; font-size: .6rem; display: inline-flex;
        align-items: center; justify-content: center; font-weight: 700;
    }


    .emp-card {
        background:#fff; border:1px solid var(--line); border-radius: 14px;
        padding: 18px; text-align: center;
        text-decoration: none; color: inherit;
        transition: .18s; display: block; height: 100%;
    }
    .emp-card:hover {
        border-color: var(--blue-light);
        box-shadow: 0 8px 22px rgba(13,27,62,.08);
        transform: translateY(-2px);
    }
    .emp-card .av-lg {
        width: 64px; height: 64px; border-radius: 50%;
        background: linear-gradient(135deg, var(--navy), var(--blue-light));
        color: #fff; font-size: 1.4rem; font-weight: 700;
        display: inline-flex; align-items: center; justify-content: center;
        margin-bottom: 10px;
    }
    .emp-card .name { font-weight: 700; color: var(--navy); font-size: .95rem; margin-bottom: 2px; }
    .emp-card .role { color: var(--gray); font-size: .78rem; margin-bottom: 12px; }
    .emp-card .mini-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px; }
    .emp-card .mini-stats .b {
        background: #f7f9fd; border-radius: 8px; padding: 6px 4px;
    }
    .emp-card .mini-stats .b .v { font-weight: 700; font-size: .95rem; color: var(--navy); }
    .emp-card .mini-stats .b .l { font-size: .65rem; color: var(--gray); }


    .chart-wrap { position: relative; height: 280px; }
</style>
@endpush


@section('content')
<div class="container-fluid" dir="rtl">


    @php
        $mgr = $data['manager'] ?? [];
        $k   = $data['kpis'] ?? [];
        $majorTasks = $data['major_tasks'] ?? [];
        $opTasks    = $data['operational_tasks'] ?? [];
        $reviewTasks = $data['review_tasks'] ?? [];
        $unassigned  = $data['unassigned_tasks'] ?? [];
        $employees   = $data['employees'] ?? [];
        $charts      = $data['charts'] ?? [];
    @endphp


    @if(!empty($error))
        <div class="alert alert-danger">{{ $error }}</div>
    @else


    {{-- HERO --}}
    <div class="mgr-hero">
        <div class="row g-3 align-items-center">
            <div class="col-md-5">
                <h4>{{ $mgr['full_name'] ?? session('user_name') }}</h4>
                <div class="meta">
                    {{ $mgr['job_title'] ?? '' }}
                    @if(!empty($mgr['department_name'])) • {{ $mgr['department_name'] }} @endif
                    • {{ now()->translatedFormat('l، d F Y') }}
                </div>
            </div>
            <div class="col-md-7">
                <div class="row g-0">
                    <div class="col hero-stat">
                        <div class="v">{{ $k['initiatives_count'] ?? 0 }}</div>
                        <div class="l">مبادرات قيد التنفيذ</div>
                    </div>
                    <div class="col hero-stat">
    <div class="v">{{ $k['major_tasks_count'] ?? 0 }}</div>
    <div class="l">
        مهام رئيسية
        <br>
        <small style="opacity:.75;font-size:.62rem;">
            {{ $k['major_lead_count'] ?? 0 }} مسؤولة • {{ $k['major_support_count'] ?? 0 }} مساندة
        </small>
    </div>
</div>

                    <div class="col hero-stat">
                        <div class="v">{{ $k['operational_tasks_count'] ?? 0 }}</div>
                        <div class="l">مهام تشغيلية</div>
                    </div>
                    <div class="col hero-stat">
                        <div class="v">{{ $k['employees_count'] ?? 0 }}</div>
                        <div class="l">موظفون</div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    {{-- KPIs --}}
    <div class="kpi-grid">
        <div class="kpi">
            <div class="icon blue"><i class="fas fa-tasks"></i></div>
            <div class="v">{{ $k['in_progress'] ?? 0 }}</div>
            <div class="l">قيد التنفيذ</div>
        </div>
        <div class="kpi">
            <div class="icon green"><i class="fas fa-check-circle"></i></div>
            <div class="v text-success">{{ $k['completed'] ?? 0 }}</div>
            <div class="l">مكتملة</div>
        </div>
        <div class="kpi">
            <div class="icon purple"><i class="fas fa-clipboard-check"></i></div>
            <div class="v" style="color:#6d28d9;">{{ $k['review'] ?? 0 }}</div>
            <div class="l">قيد المراجعة</div>
        </div>
        <div class="kpi">
            <div class="icon red"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="v text-danger">{{ $k['delayed'] ?? 0 }}</div>
            <div class="l">متأخرة فعليًا</div>
        </div>
        <div class="kpi">
            <div class="icon amber"><i class="fas fa-user-slash"></i></div>
            <div class="v" style="color:#b45309;">{{ $k['unassigned'] ?? 0 }}</div>
            <div class="l">لم تُوزَّع بعد</div>
        </div>
        <div class="kpi">
            <div class="icon green"><i class="fas fa-percentage"></i></div>
            <div class="v text-success">{{ $k['completion_rate'] ?? 0 }}%</div>
            <div class="l">نسبة الإنجاز</div>
        </div>
    </div>


    {{-- TABS --}}
    <ul class="nav mgr-tabs mb-3 flex-wrap" role="tablist">
        <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-major" type="button">
                <i class="fas fa-layer-group me-1"></i> المهام الرئيسية
                <span class="cnt">{{ count($majorTasks) }}</span>
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-op" type="button">
                <i class="fas fa-list-check me-1"></i> المهام التشغيلية
                <span class="cnt">{{ count($opTasks) }}</span>
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-review" type="button">
                <i class="fas fa-clipboard-check me-1"></i> قيد المراجعة
                <span class="cnt">{{ count($reviewTasks) }}</span>
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-unassigned" type="button">
                <i class="fas fa-user-slash me-1"></i> لم تُوزَّع
                <span class="cnt">{{ count($unassigned) }}</span>
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-emp" type="button">
                <i class="fas fa-users me-1"></i> الموظفون
                <span class="cnt">{{ count($employees) }}</span>
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-perf" type="button">
                <i class="fas fa-chart-pie me-1"></i> أداء الإدارة
            </button>
        </li>
    </ul>


    <div class="tab-content">


        {{-- ═══ المهام الرئيسية ═══ --}}
        <div class="tab-pane fade show active" id="tab-major">
            @if(empty($majorTasks))
                <div class="text-center text-muted py-5">لا توجد مهام رئيسية في نطاق الإدارة.</div>
            @else
                <div class="row g-3">
                    @foreach($majorTasks as $mt)
                        @php
                            $pct = (float)($mt['progress'] ?? 0);
                            $cls = $pct >= 75 ? 'ok' : ($pct >= 40 ? '' : 'warn');
                        @endphp
                        <div class="col-lg-4 col-md-6">
                            <a href="{{ url('/operational/major-task/' . $mt['major_task_id']) }}"
                               class="major-card">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="t">{{ $mt['name'] }}</div>
                                    @if(!empty($mt['is_cross_department']))
                                        <span class="pill purple">متعددة الإدارات</span>
                                    @endif
                                </div>
                                <div class="meta mb-2">
                                    <i class="fas fa-bullseye text-success me-1"></i>
                                    {{ $mt['initiative_name'] ?? '—' }}
                                </div>
                                <div class="d-flex justify-content-between mb-2 flex-wrap gap-1">
    @php
        $roleAr = $mt['responsibility_ar'] ?? null;
        $roleRaw = strtoupper($mt['responsibility_type'] ?? '');
        $roleCls = $roleRaw === 'LEAD' ? 'green' : ($roleRaw === 'SUPPORT' ? 'amber' : 'purple');
    @endphp
    @if($roleAr)
        <span class="pill {{ $roleCls }}">
            <i class="fas fa-{{ $roleRaw === 'LEAD' ? 'star' : 'hands-helping' }} me-1"></i>
            {{ $roleAr }}
        </span>
    @endif
    @if(!empty($mt['priority_name']))
        <span class="pill blue">{{ $mt['priority_name'] }}</span>
    @endif
    <span class="meta">
        {{ $mt['completed_tasks'] }} / {{ $mt['total_tasks'] }} مهمة
    </span>
</div>

                                <div class="progress-thin {{ $cls }}">
                                    <span style="width: {{ $pct }}%"></span>
                                </div>
                                <div class="d-flex justify-content-between mt-2">
                                    <span class="meta">التقدم</span>
                                    <strong style="color:#0D1B3E;">{{ $pct }}%</strong>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>


        {{-- ═══ المهام التشغيلية ═══ --}}
        <div class="tab-pane fade" id="tab-op">
            @if(empty($opTasks))
                <div class="text-center text-muted py-5">لا توجد مهام تشغيلية.</div>
            @else
                <div class="card-soft p-0">
                    <div class="table-responsive">
                        <table class="table tbl-clean align-middle">
                            <thead>
                                <tr>
                                    <th>المهمة</th>
                                    <th>المهمة الرئيسية</th>
                                    <th>المبادرة</th>
                                    <th>المسؤولون</th>
                                    <th>الأولوية</th>
                                    <th>الموعد</th>
                                    <th style="min-width:130px;">التقدم</th>
                                    <th>الحالة</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($opTasks as $t)
                                @php
                                    $pct = (float)($t['progress'] ?? 0);
                                    $cls = $pct >= 75 ? 'ok' : ($pct >= 40 ? '' : 'warn');
                                @endphp
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $t['title'] }}</div>
                                        @if(!empty($t['is_delayed']))
                                            <span class="pill red mt-1 d-inline-block">متأخرة</span>
                                        @endif
                                    </td>
                                    <td class="text-muted small">{{ $t['major_task_name'] ?? '—' }}</td>
                                    <td class="text-muted small">{{ $t['initiative_name'] ?? '—' }}</td>
                                    <td>
                                        @forelse(($t['assignees'] ?? []) as $name)
                                            <span class="avatar-inline">
                                                <span class="av">{{ mb_substr($name, 0, 1, 'UTF-8') }}</span>
                                                {{ Str::limit($name, 16) }}
                                            </span>
                                        @empty
                                            <span class="pill amber">غير موزعة</span>
                                        @endforelse
                                    </td>
                                    <td>
                                        @if(!empty($t['priority_name']))
                                            @php
                                                $pr = (int)($t['priority_id'] ?? 3);
                                                $prCls = $pr == 1 ? 'red' : ($pr == 2 ? 'amber' : 'gray');
                                            @endphp
                                            <span class="pill {{ $prCls }}">{{ $t['priority_name'] }}</span>
                                        @endif
                                    </td>
                                    <td class="text-muted small">{{ $t['end_date'] ?? '—' }}</td>
                                    <td>
                                        <div class="progress-thin {{ $cls }}">
                                            <span style="width: {{ $pct }}%"></span>
                                        </div>
                                        <small class="text-muted">{{ $pct }}%</small>
                                    </td>
                                    <td><span class="pill blue">{{ $t['status_name'] ?? '—' }}</span></td>
                                    <td>
                                        <a href="{{ url('/operational/task/' . $t['task_id']) }}"
                                           class="btn btn-sm btn-outline-primary">عرض</a>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>


        {{-- ═══ قيد المراجعة ═══ --}}
        <div class="tab-pane fade" id="tab-review">
            @if(empty($reviewTasks))
                <div class="text-center text-muted py-5">لا توجد مهام قيد المراجعة.</div>
            @else
                <div class="card-soft p-0">
                    <div class="table-responsive">
                        <table class="table tbl-clean align-middle">
                            <thead>
                                <tr>
                                    <th>المهمة</th>
                                    <th>المهمة الرئيسية</th>
                                    <th>المسؤولون</th>
                                    <th>الموعد</th>
                                    <th>التقدم</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($reviewTasks as $t)
                                <tr>
                                    <td class="fw-semibold">{{ $t['title'] }}</td>
                                    <td class="text-muted small">{{ $t['major_task_name'] ?? '—' }}</td>
                                    <td>
                                        @forelse(($t['assignees'] ?? []) as $name)
                                            <span class="avatar-inline">
                                                <span class="av">{{ mb_substr($name, 0, 1, 'UTF-8') }}</span>
                                                {{ Str::limit($name, 16) }}
                                            </span>
                                        @empty
                                            <span class="text-muted small">—</span>
                                        @endforelse
                                    </td>
                                    <td class="text-muted small">{{ $t['end_date'] ?? '—' }}</td>
                                    <td>
                                        <div class="progress-thin">
                                            <span style="width: {{ $t['progress'] ?? 0 }}%"></span>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="{{ url('/operational/task/' . $t['task_id']) }}"
                                           class="btn btn-sm btn-outline-primary">مراجعة</a>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>


        {{-- ═══ لم تُوزَّع ═══ --}}
        <div class="tab-pane fade" id="tab-unassigned">
            @if(empty($unassigned))
                <div class="text-center text-muted py-5">
                    <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                    <p>كل المهام موزّعة — لا شيء يحتاج إجراءً.</p>
                </div>
            @else
                <div class="card-soft p-0">
                    <div class="table-responsive">
                        <table class="table tbl-clean align-middle">
                            <thead>
                                <tr>
                                    <th>المهمة</th>
                                    <th>المهمة الرئيسية</th>
                                    <th>المبادرة</th>
                                    <th>الأولوية</th>
                                    <th>الموعد</th>
                                    <th>الساعات المقدرة</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($unassigned as $t)
                                <tr>
                                    <td class="fw-semibold">{{ $t['title'] }}</td>
                                    <td class="text-muted small">{{ $t['major_task_name'] ?? '—' }}</td>
                                    <td class="text-muted small">{{ $t['initiative_name'] ?? '—' }}</td>
                                    <td>
                                        @if(!empty($t['priority_name']))
                                            @php
                                                $pr = (int)($t['priority_id'] ?? 3);
                                                $prCls = $pr == 1 ? 'red' : ($pr == 2 ? 'amber' : 'gray');
                                            @endphp
                                            <span class="pill {{ $prCls }}">{{ $t['priority_name'] }}</span>
                                        @endif
                                    </td>
                                    <td class="text-muted small">{{ $t['end_date'] ?? '—' }}</td>
                                    <td>{{ $t['estimated_hours'] ?? 0 }} س</td>
                                    <td>
                                        <a href="{{ url('/operational/assign/' . $t['task_id']) }}"
                                           class="btn btn-sm btn-primary">
                                            <i class="fas fa-user-plus"></i> توزيع
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>


        {{-- ═══ الموظفون ═══ --}}
        <div class="tab-pane fade" id="tab-emp">
            @if(empty($employees))
                <div class="text-center text-muted py-5">لا يوجد موظفون.</div>
            @else
                <div class="row g-3">
                    @foreach($employees as $e)
                        <div class="col-lg-3 col-md-4 col-sm-6">
                            <a href="{{ route('dashboard.manager.employee-detail', $e['employee_id']) }}"
                               class="emp-card">
                                <div class="av-lg">{{ mb_substr($e['full_name'] ?? '?', 0, 1, 'UTF-8') }}</div>
                                <div class="name">{{ $e['full_name'] ?? '—' }}</div>
                                <div class="role">{{ $e['job_title'] ?? '—' }}</div>
                                <div class="mini-stats">
                                    <div class="b">
                                        <div class="v">{{ $e['total_assignments'] ?? 0 }}</div>
                                        <div class="l">مهمة</div>
                                    </div>
                                    <div class="b">
                                        <div class="v text-success">{{ $e['completed'] ?? 0 }}</div>
                                        <div class="l">مكتملة</div>
                                    </div>
                                    <div class="b">
                                        <div class="v text-danger">{{ $e['delayed'] ?? 0 }}</div>
                                        <div class="l">متأخرة</div>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <div class="progress-thin">
                                        <span style="width: {{ $e['completion_rate'] ?? 0 }}%"></span>
                                    </div>
                                    <small class="text-muted">{{ $e['completion_rate'] ?? 0 }}% إنجاز</small>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>


        {{-- ═══ أداء الإدارة ═══ --}}
        <div class="tab-pane fade" id="tab-perf">
            <div class="row g-3">
                <div class="col-lg-7">
                    <div class="card-soft">
                        <div class="h"><i class="fas fa-chart-pie text-primary me-2"></i> توزيع حالات المهام</div>
                        <div class="chart-wrap"><canvas id="chartStatusDist"></canvas></div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="card-soft">
                        <div class="h"><i class="fas fa-trophy text-warning me-2"></i> أعلى الموظفين أداءً</div>
                        <div class="chart-wrap"><canvas id="chartTopEmp"></canvas></div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="card-soft">
                        <div class="h"><i class="fas fa-chart-bar text-primary me-2"></i> مقارنة: نسبة الإنجاز vs متوسط التقدم</div>
                        <div class="chart-wrap" style="height:320px;"><canvas id="chartEmpCompare"></canvas></div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card-soft">
                        <div class="h"><i class="fas fa-info-circle text-info me-2"></i> ملخص الأداء</div>
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted">إجمالي المهام التشغيلية</span><strong>{{ $k['operational_tasks_count'] ?? 0 }}</strong>
                        </div>
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted">مكتملة</span><strong class="text-success">{{ $k['completed'] ?? 0 }}</strong>
                        </div>
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted">قيد التنفيذ</span><strong class="text-primary">{{ $k['in_progress'] ?? 0 }}</strong>
                        </div>
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted">قيد المراجعة</span><strong style="color:#6d28d9;">{{ $k['review'] ?? 0 }}</strong>
                        </div>
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted">متأخرة</span><strong class="text-danger">{{ $k['delayed'] ?? 0 }}</strong>
                        </div>
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted">لم تُوزَّع بعد</span><strong style="color:#b45309;">{{ $k['unassigned'] ?? 0 }}</strong>
                        </div>
                        <div class="d-flex justify-content-between py-2">
                            <span class="text-muted">متوسط التقدم</span><strong>{{ $k['avg_progress'] ?? 0 }}%</strong>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card-soft">
                        <div class="h"><i class="fas fa-users-cog text-secondary me-2"></i> توزيع الموظفين حسب الأداء</div>
                        <div class="chart-wrap"><canvas id="chartEmpDist"></canvas></div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    @endif
</div>
@endsection


@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
    const statusDist   = @json($charts['status_distribution'] ?? []);
    const topEmployees = @json($charts['top_employees'] ?? []);
    const allEmployees = @json($employees ?? []);


    const colors = ['#4caf50', '#3b82f6', '#8b5cf6', '#ef4444', '#9ca3af'];


    const cd1 = document.getElementById('chartStatusDist');
    if (cd1 && statusDist.length) {
        new Chart(cd1, {
            type: 'doughnut',
            data: {
                labels: statusDist.map(s => s.label),
                datasets: [{
                    data: statusDist.map(s => s.value),
                    backgroundColor: statusDist.map(s => s.color || colors[0]),
                    borderWidth: 3,
                    borderColor: '#fff',
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { padding: 14, font: { family: 'system-ui' } } },
                },
                cutout: '62%',
            }
        });
    }


    const cd2 = document.getElementById('chartTopEmp');
    if (cd2 && topEmployees.length) {
        new Chart(cd2, {
            type: 'bar',
            data: {
                labels: topEmployees.map(e => e.name),
                datasets: [{
                    label: 'نسبة الإنجاز',
                    data: topEmployees.map(e => e.completion_rate),
                    backgroundColor: 'rgba(59,130,246,.8)',
                    borderRadius: 6,
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { x: { beginAtZero: true, max: 100 } }
            }
        });
    }


    const cd3 = document.getElementById('chartEmpCompare');
    if (cd3 && allEmployees.length) {
        new Chart(cd3, {
            type: 'bar',
            data: {
                labels: allEmployees.map(e => e.full_name),
                datasets: [
                    {
                        label: 'نسبة الإنجاز %',
                        data: allEmployees.map(e => e.completion_rate),
                        backgroundColor: 'rgba(76,175,80,.75)',
                        borderRadius: 5,
                    },
                    {
                        label: 'متوسط التقدم %',
                        data: allEmployees.map(e => e.avg_progress),
                        backgroundColor: 'rgba(59,130,246,.75)',
                        borderRadius: 5,
                    }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
                scales: { y: { beginAtZero: true, max: 100 } }
            }
        });
    }


    const cd4 = document.getElementById('chartEmpDist');
    if (cd4 && allEmployees.length) {
        let high = 0, mid = 0, low = 0;
        allEmployees.forEach(e => {
            const r = e.completion_rate || 0;
            if (r >= 75) high++; else if (r >= 40) mid++; else low++;
        });
        new Chart(cd4, {
            type: 'polarArea',
            data: {
                labels: ['أداء عالٍ (≥75%)', 'أداء متوسط (40-74%)', 'يحتاج دعمًا (<40%)'],
                datasets: [{
                    data: [high, mid, low],
                    backgroundColor: ['rgba(76,175,80,.7)', 'rgba(245,158,11,.7)', 'rgba(239,68,68,.7)'],
                    borderColor: '#fff',
                    borderWidth: 2,
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }
})();
</script>
@endpush

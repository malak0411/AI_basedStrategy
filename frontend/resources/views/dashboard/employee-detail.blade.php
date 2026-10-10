@extends('layouts.app')


@section('title', 'تفاصيل الموظف')


@push('styles')
<style>
    .emp-hero {
        background: linear-gradient(135deg, #0D1B3E, #1e40af);
        color:#fff; border-radius:16px; padding:24px 28px; margin-bottom:20px;
    }
    .emp-hero .av-lg {
        width: 76px; height: 76px; border-radius: 50%;
        background: rgba(255,255,255,.15);
        display:inline-flex; align-items:center; justify-content:center;
        font-size: 1.8rem; font-weight: 700;
        border: 2px solid rgba(255,255,255,.3);
    }
    .kpi-grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-bottom: 20px; }
    .kpi { background:#fff; border:1px solid #e6e9f2; border-radius: 12px; padding: 14px; text-align:center; }
    .kpi .v { font-size: 1.5rem; font-weight: 800; color:#0D1B3E; line-height: 1; }
    .kpi .l { font-size: .75rem; color:#5b6580; margin-top: 4px; }


    .card-soft { background:#fff; border:1px solid #e6e9f2; border-radius: 12px; padding: 18px; }
    .card-soft .h { font-weight: 700; color:#0D1B3E; margin-bottom: 12px; font-size:.95rem; }


    .tbl-clean { font-size: .85rem; margin:0; }
    .tbl-clean thead th { background:#f7f9fd; color:#5b6580; font-size:.75rem; padding: 10px 12px; border-bottom:1px solid #e6e9f2; }
    .tbl-clean tbody td { padding: 10px 12px; vertical-align: middle; border-bottom: 1px solid #f1f4fa; }


    .pill { padding: 3px 10px; border-radius: 18px; font-size: .7rem; font-weight:600; display:inline-block; }
    .pill.blue { background:#eff6ff; color:#1d4ed8; }
    .pill.green { background:#ecfdf5; color:#047857; }
    .pill.red { background:#fef2f2; color:#b91c1c; }
    .pill.amber { background:#fffbeb; color:#b45309; }
    .pill.gray { background:#f3f4f6; color:#4b5563; }


    .progress-thin { height:6px; background:#eef1f7; border-radius:4px; overflow:hidden; }
    .progress-thin > span { display:block; height:100%; background: linear-gradient(90deg,#1e40af,#3b82f6); }
    .chart-wrap { position: relative; height: 260px; }
</style>
@endpush


@section('content')
<div class="container-fluid" dir="rtl">


    @php
        $emp = $data['employee'] ?? [];
        $k   = $data['kpis'] ?? [];
        $tasks = $data['tasks'] ?? [];
        $dist  = $data['status_distribution'] ?? [];
    @endphp


    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="{{ route('dashboard.manager') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-right"></i> العودة للوحة المدير
        </a>
    </div>


    @if(!empty($error))
        <div class="alert alert-danger">{{ $error }}</div>
    @else


    <div class="emp-hero">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <div class="av-lg">{{ mb_substr($emp['full_name'] ?? '?', 0, 1, 'UTF-8') }}</div>
            <div>
                <h4 class="mb-1">{{ $emp['full_name'] ?? '—' }}</h4>
                <div class="meta small opacity-75">
                    {{ $emp['job_title'] ?? '' }}
                    @if(!empty($emp['department_name'])) • {{ $emp['department_name'] }} @endif
                </div>
                <div class="meta small opacity-75 mt-1">
                    <i class="fas fa-envelope"></i> {{ $emp['email'] ?? '—' }}
                    @if(!empty($emp['phone_number']))
                        • <i class="fas fa-phone"></i> {{ $emp['phone_number'] }}
                    @endif
                </div>
            </div>
        </div>
    </div>


    {{-- KPIs --}}
    <div class="kpi-grid">
        <div class="kpi"><div class="v">{{ $k['total_assignments'] ?? 0 }}</div><div class="l">إجمالي الإسنادات</div></div>
        <div class="kpi"><div class="v text-success">{{ $k['completed'] ?? 0 }}</div><div class="l">مكتملة</div></div>
        <div class="kpi"><div class="v text-primary">{{ $k['in_progress'] ?? 0 }}</div><div class="l">قيد التنفيذ</div></div>
        <div class="kpi"><div class="v text-danger">{{ $k['delayed'] ?? 0 }}</div><div class="l">متأخرة</div></div>
        <div class="kpi"><div class="v text-success">{{ $k['accepted'] ?? 0 }}</div><div class="l">مقبولة</div></div>
        <div class="kpi"><div class="v" style="color:#b45309;">{{ $k['pending'] ?? 0 }}</div><div class="l">بانتظار القبول</div></div>
        <div class="kpi"><div class="v text-danger">{{ $k['rejected'] ?? 0 }}</div><div class="l">مرفوضة</div></div>
        <div class="kpi"><div class="v text-success">{{ $k['completion_rate'] ?? 0 }}%</div><div class="l">نسبة الإنجاز</div></div>
    </div>


    <div class="row g-3 mb-3">
        <div class="col-lg-4">
            <div class="card-soft h-100">
                <div class="h"><i class="fas fa-clock text-primary me-2"></i> الساعات</div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">الساعات المقدرة</span>
                    <strong>{{ $k['estimated_hours'] ?? 0 }}</strong>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">الساعات الفعلية</span>
                    <strong class="text-primary">{{ $k['actual_hours'] ?? 0 }}</strong>
                </div>
                <div class="d-flex justify-content-between py-2">
                    <span class="text-muted">الفرق</span>
                    @php $diff = (float)($k['hours_diff'] ?? 0); @endphp
                    <strong class="{{ $diff > 0 ? 'text-danger' : 'text-success' }}">
                        {{ $diff > 0 ? '+' : '' }}{{ $diff }}
                    </strong>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card-soft h-100">
                <div class="h"><i class="fas fa-chart-line text-success me-2"></i> متوسط التقدم</div>
                <div class="text-center py-3">
                    <div class="display-6 fw-bold text-success">{{ $k['avg_progress'] ?? 0 }}%</div>
                    <div class="progress-thin mt-3">
                        <span style="width: {{ $k['avg_progress'] ?? 0 }}%"></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card-soft h-100">
                <div class="h"><i class="fas fa-chart-pie text-secondary me-2"></i> توزيع الحالات</div>
                <div class="chart-wrap"><canvas id="empStatusChart"></canvas></div>
            </div>
        </div>
    </div>


    <div class="card-soft">
        <div class="h"><i class="fas fa-tasks text-primary me-2"></i> المهام المُسندة ({{ count($tasks) }})</div>
        <div class="table-responsive">
            <table class="table tbl-clean align-middle">
                <thead>
                    <tr>
                        <th>المهمة</th>
                        <th>المهمة الرئيسية</th>
                        <th>المبادرة</th>
                        <th>دور الموظف</th>
                        <th>الحالة</th>
                        <th>الموعد</th>
                        <th>التقدم</th>
                        <th>ساعاتي</th>
                        <th>الساعات الفعلية</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($tasks as $t)
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
                            @if(!empty($t['my_role']))
                                <span class="pill blue">{{ $t['my_role'] }}</span>
                            @endif
                        </td>
                        <td><span class="pill gray">{{ $t['status_name'] ?? '—' }}</span></td>
                        <td class="text-muted small">{{ $t['end_date'] ?? '—' }}</td>
                        <td style="min-width:110px;">
                            <div class="progress-thin"><span style="width: {{ $t['progress'] ?? 0 }}%"></span></div>
                            <small class="text-muted">{{ $t['progress'] ?? 0 }}%</small>
                        </td>
                        <td>{{ $t['my_estimated_hours'] ?? 0 }} س</td>
                        <td>{{ $t['actual_hours'] ?? 0 }} س</td>
                        <td>
                            <a href="{{ url('/operational/task-detail/' . $t['task_id']) }}"
                               class="btn btn-sm btn-outline-primary">عرض</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="text-center text-muted py-4">لا توجد مهام.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>


    @endif
</div>
@endsection


@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
    const dist = @json($dist ?? []);
    const el = document.getElementById('empStatusChart');
    if (!el || !dist.length) return;


    const colors = ['#4caf50','#3b82f6','#8b5cf6','#ef4444','#9ca3af','#f59e0b'];
    new Chart(el, {
        type: 'doughnut',
        data: {
            labels: dist.map(d => d.label),
            datasets: [{
                data: dist.map(d => d.value),
                backgroundColor: dist.map((_, i) => colors[i % colors.length]),
                borderWidth: 2, borderColor: '#fff',
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { padding: 10, font: { size: 11 } } } },
            cutout: '60%',
        }
    });
})();
</script>
@endpush

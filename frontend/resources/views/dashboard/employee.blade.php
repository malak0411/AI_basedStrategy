@extends('layouts.app')


@section('title', 'لوحة الموظف')


@push('styles')
<style>
    .emp-kpi {
        border: 0;
        border-radius: 14px;
        background: #fff;
        box-shadow: 0 2px 12px rgba(13,27,62,.06);
        padding: 18px;
        height: 100%;
        position: relative;
        overflow: hidden;
    }
    .emp-kpi::before {
        content: '';
        position: absolute;
        top: 0; right: 0;
        width: 4px; height: 100%;
        background: linear-gradient(180deg, #1e40af, #3b82f6);
    }
    .emp-kpi .kpi-label { color: #5b6580; font-size: .82rem; margin-bottom: 4px; }
    .emp-kpi .kpi-value { font-size: 1.75rem; font-weight: 800; color: #0D1B3E; line-height: 1; }
    .emp-kpi .kpi-sub   { color: #8792a8; font-size: .75rem; margin-top: 6px; }
    .emp-kpi.success::before { background: linear-gradient(180deg, #027a48, #4caf50); }
    .emp-kpi.danger::before  { background: linear-gradient(180deg, #b42318, #ef4444); }
    .emp-kpi.warn::before    { background: linear-gradient(180deg, #b54708, #f59e0b); }
    .emp-kpi.purple::before  { background: linear-gradient(180deg, #6d28d9, #8b5cf6); }
    .emp-kpi.gray::before    { background: linear-gradient(180deg, #4b5563, #9ca3af); }


    .emp-hero {
        background: linear-gradient(135deg, #0D1B3E 0%, #1e40af 100%);
        color: #fff;
        border-radius: 16px;
        padding: 22px 26px;
        margin-bottom: 24px;
    }
    .emp-hero h4 { margin: 0 0 4px; font-weight: 700; }
    .emp-hero .meta { color: #cdd6ee; font-size: .88rem; }


    .emp-tabs .nav-link {
        border: 0;
        color: #5b6580;
        font-weight: 600;
        padding: 11px 20px;
        border-radius: 10px;
        margin-inline-end: 6px;
        background: #f4f6fb;
        font-size: .88rem;
    }
    .emp-tabs .nav-link.active {
        background: #0D1B3E;
        color: #fff;
    }
    .emp-tabs .nav-link .cnt {
        background: rgba(0,0,0,.08);
        padding: 1px 8px;
        border-radius: 10px;
        font-size: .7rem;
        margin-inline-start: 6px;
    }
    .emp-tabs .nav-link.active .cnt { background: rgba(255,255,255,.25); }


    .emp-card { background:#fff; border:1px solid #e6e9f2; border-radius: 14px; padding: 20px; height: 100%; }
    .emp-card .h { font-weight: 700; color: #0D1B3E; margin-bottom: 14px; font-size: .95rem; }


    .tbl-clean { font-size: .85rem; margin-bottom: 0; }
    .tbl-clean thead th {
        background: #f7f9fd;
        color: #5b6580;
        font-weight: 600;
        font-size: .78rem;
        padding: 11px 12px;
        border-bottom: 1px solid #e6e9f2;
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


    .progress-thin { height: 7px; background: #eef1f7; border-radius: 5px; overflow: hidden; }
    .progress-thin > span {
        display: block;
        height: 100%;
        background: linear-gradient(90deg, #1e40af, #3b82f6);
        border-radius: 5px;
    }
    .progress-thin.success > span { background: linear-gradient(90deg, #027a48, #4caf50); }
    .progress-thin.warn > span    { background: linear-gradient(90deg, #b54708, #f59e0b); }
    .progress-thin.danger > span  { background: linear-gradient(90deg, #b42318, #ef4444); }


    .pending-card { border: 1px solid #e6e9f2; border-radius: 12px; padding: 16px; height: 100%; background:#fff; }
    .pending-card .title { font-weight: 700; color: #0D1B3E; font-size: .95rem; margin-bottom: 6px; }
    .pending-card .meta  { color: #5b6580; font-size: .78rem; margin-bottom: 4px; }


    .mit-card { border: 1px solid #e6e9f2; border-radius: 12px; padding: 16px; height: 100%; background:#fff; }
    .mit-card .risk  { font-weight: 700; color: #0D1B3E; font-size: .92rem; margin-bottom: 4px; }
    .mit-card .task  { color: #5b6580; font-size: .8rem; margin-bottom: 8px; }
    .mit-card .action { color: #1a2332; font-size: .85rem; margin-bottom: 10px; }
</style>
@endpush


@section('content')
<div class="container-fluid" dir="rtl">


    @php
        $emp = $data['employee'] ?? [];
        $k   = $data['kpis'] ?? [];
        $allTasks = $data['all_tasks'] ?? [];
        $pending  = $data['pending_assignments'] ?? [];
        $mits     = $data['my_mitigations'] ?? [];
    @endphp


    @if(!empty($error))
        <div class="alert alert-danger">{{ $error }}</div>
    @else


    <div class="emp-hero">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h4>{{ $emp['full_name'] ?? session('user_name') }}</h4>
                <div class="meta">
                    {{ $emp['job_title'] ?? '' }}
                    @if(!empty($emp['department'])) • {{ $emp['department'] }} @endif
                    • {{ now()->translatedFormat('l، d F Y') }}
                </div>
            </div>
            <div class="text-end">
                <div class="small opacity-75 mb-1">نسبة الإنجاز</div>
                <div class="fs-3 fw-bold">{{ $k['completion_rate'] ?? 0 }}%</div>
            </div>
        </div>
    </div>


    <div class="row g-3 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="emp-kpi">
                <div class="kpi-label">إجمالي المهام المقبولة</div>
                <div class="kpi-value">{{ $k['total_tasks'] ?? 0 }}</div>
                <div class="kpi-sub">
                    <span class="text-success fw-semibold">{{ $k['completed'] ?? 0 }}</span> مكتملة
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="emp-kpi success">
                <div class="kpi-label">الساعات المقدرة (المسندة إليّ)</div>
                <div class="kpi-value text-primary">{{ $k['estimated_hours'] ?? 0 }}</div>
                <div class="kpi-sub">مجموع task_assignments</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="emp-kpi purple">
                <div class="kpi-label">الساعات الفعلية (للمكتملة)</div>
                <div class="kpi-value" style="color:#6d28d9;">{{ $k['actual_hours'] ?? 0 }}</div>
                <div class="kpi-sub">مجموع actual_hours للمهام المكتملة</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="emp-kpi {{ ($k['hours_diff'] ?? 0) > 0 ? 'danger' : 'success' }}">
                <div class="kpi-label">الفرق (فعلي − مقدر)</div>
                <div class="kpi-value {{ ($k['hours_diff'] ?? 0) > 0 ? 'text-danger' : 'text-success' }}">
                    {{ ($k['hours_diff'] ?? 0) > 0 ? '+' : '' }}{{ $k['hours_diff'] ?? 0 }}
                </div>
                <div class="kpi-sub">على المهام المكتملة</div>
            </div>
        </div>
    </div>


    <div class="row g-3 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="emp-kpi">
                <div class="kpi-label">قيد التنفيذ</div>
                <div class="kpi-value text-primary">{{ $k['in_progress'] ?? 0 }}</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="emp-kpi purple">
                <div class="kpi-label">قيد المراجعة</div>
                <div class="kpi-value" style="color:#6d28d9;">{{ $k['review'] ?? 0 }}</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="emp-kpi warn">
                <div class="kpi-label">معلقة / جاهزة</div>
                <div class="kpi-value" style="color:#b45309;">
                    {{ ($k['on_hold'] ?? 0) + ($k['pending_status'] ?? 0) + ($k['ready'] ?? 0) }}
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="emp-kpi danger">
                <div class="kpi-label">متأخرة فعليًا</div>
                <div class="kpi-value text-danger">{{ $k['delayed'] ?? 0 }}</div>
            </div>
        </div>
    </div>


    <ul class="nav emp-tabs mb-3 flex-wrap" role="tablist">
        <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-tasks" type="button">
                <i class="fas fa-tasks me-1"></i> جميع مهامي
                <span class="cnt">{{ count($allTasks) }}</span>
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-pending" type="button">
                <i class="fas fa-hourglass-half me-1"></i> بانتظار القبول
                <span class="cnt">{{ count($pending) }}</span>
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-mits" type="button">
                <i class="fas fa-shield-alt me-1"></i> إجراءات المخاطر المسندة
                <span class="cnt">{{ count($mits) }}</span>
            </button>
        </li>
    </ul>


    <div class="tab-content">


        <div class="tab-pane fade show active" id="tab-tasks">
            <div class="emp-card p-0">
                <div class="d-flex justify-content-between align-items-center p-3 border-bottom">
                    <div class="h mb-0"><i class="fas fa-list-check text-primary me-2"></i> جميع المهام</div>
                    <div class="d-flex gap-2 flex-wrap">
                        <span class="pill green">مقبولة: {{ $k['accepted_count'] ?? 0 }}</span>
                        <span class="pill amber">بانتظار القبول: {{ $k['pending_acceptance'] ?? 0 }}</span>
                        <span class="pill red">مرفوضة: {{ $k['rejected_count'] ?? 0 }}</span>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table tbl-clean align-middle">
                        <thead>
                            <tr>
                                <th>المهمة</th>
                                <th>المبادرة</th>
                                <th>المهمة الرئيسية</th>
                                <th>دوري</th>
                                <th>الحالة</th>
                                <th>القبول</th>
                                <th>الموعد</th>
                                <th>ساعاتي</th>
                                <th>الفعلي</th>
                                <th>الإجراء</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($allTasks as $t)
                            @php
                                $acc = $t['acceptance_status'] ?? '';
                                $accCls = $acc === 'accepted' ? 'green' : ($acc === 'pending' ? 'amber' : 'red');
                                $accAr  = $acc === 'accepted' ? 'مقبولة' : ($acc === 'pending' ? 'بانتظار' : 'مرفوضة');
                            @endphp
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $t['title'] }}</div>
                                    @if(!empty($t['is_delayed']))
                                        <span class="pill red mt-1 d-inline-block">متأخرة</span>
                                    @endif
                                </td>
                                <td class="text-muted small">{{ $t['initiative'] ?? '—' }}</td>
                                <td class="text-muted small">{{ $t['major_task'] ?? '—' }}</td>
                                <td>
                                    @if(!empty($t['my_role']))
                                        <span class="pill blue">{{ $t['my_role'] }}</span>
                                    @endif
                                </td>
                                <td><span class="pill gray">{{ $t['status_ar'] ?? '—' }}</span></td>
                                <td><span class="pill {{ $accCls }}">{{ $accAr }}</span></td>
                                <td class="text-muted small">{{ $t['end_date'] ?? '—' }}</td>
                                <td>{{ $t['estimated_hours'] ?? 0 }} س</td>
                                <td>
                                    @if(($t['status_id'] ?? 0) == 19)
                                        <span class="fw-semibold" style="color:#6d28d9;">{{ $t['actual_hours'] ?? 0 }} س</span>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('operational.task-detail', $t['task_id']) }}"
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="text-center text-muted py-4">لا توجد مهام.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>


        <div class="tab-pane fade" id="tab-pending">
            @if(empty($pending))
                <div class="text-center text-muted py-5">
                    <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                    <p>لا توجد مهام بانتظار قبولك.</p>
                </div>
            @else
                <div class="row g-3">
                    @foreach($pending as $p)
                        <div class="col-lg-6">
                            <div class="pending-card">
                                <div class="title">{{ $p['title'] }}</div>
                                <div class="meta">
                                    <span class="pill blue">{{ $p['role_type'] ?? '—' }}</span>
                                    <span class="ms-2">الاستحقاق: {{ $p['estimated_hours'] }} ساعة</span>
                                </div>
                                <div class="meta">المبادرة: {{ $p['initiative'] ?? '—' }}</div>
                                <div class="meta">المهمة الرئيسية: {{ $p['major_task'] ?? '—' }}</div>
                                @if(!empty($p['note']))
                                    <div class="alert alert-light border small mb-2 mt-2">{{ $p['note'] }}</div>
                                @endif
                                <div class="d-flex gap-2 mt-3">
                                    <form method="POST"
                                          action="{{ route('employee.assignments.accept', $p['assignment_id']) }}"
                                          class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success">
                                            <i class="fas fa-check"></i> قبول
                                        </button>
                                    </form>
                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                            data-bs-toggle="modal" data-bs-target="#rejectModal"
                                            data-assignment="{{ $p['assignment_id'] }}"
                                            data-task-title="{{ $p['title'] }}">
                                        <i class="fas fa-times"></i> رفض
                                    </button>
                                    <a href="{{ route('operational.task-detail', $p['task_id']) }}"
                                       class="btn btn-sm btn-outline-secondary ms-auto">
                                        <i class="fas fa-eye"></i> تفاصيل
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>


        <div class="tab-pane fade" id="tab-mits">
            @if(empty($mits))
                <div class="text-center text-muted py-5">
                    <i class="fas fa-shield-alt fa-2x text-muted mb-2"></i>
                    <p>لا توجد إجراءات مخاطر مسندة إليك.</p>
                </div>
            @else
                <div class="row g-3">
                    @foreach($mits as $m)
                        <div class="col-lg-4 col-md-6">
                            <div class="mit-card">
                                <div class="risk">
                                    <i class="fas fa-exclamation-triangle text-danger me-1"></i>
                                    {{ $m['risk_name'] ?? '—' }}
                                </div>
                                @if(!empty($m['task_title']))
                                    <div class="task">
                                        <i class="fas fa-link me-1"></i>
                                        {{ $m['task_title'] }}
                                    </div>
                                @endif
                                <div class="action">{{ $m['action'] ?? '' }}</div>
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <span class="pill"
                                          style="background: {{ $m['status_color'] ?? '#6c757d' }}22; color: {{ $m['status_color'] ?? '#6c757d' }};">
                                        {{ $m['status_ar'] ?? '—' }}
                                    </span>
                                    @if(!empty($m['due_date']))
                                        <small class="text-muted">
                                            <i class="fas fa-calendar"></i> {{ $m['due_date'] }}
                                        </small>
                                    @endif
                                </div>
                                @if(!empty($m['completed_at']))
                                    <div class="mt-2 small text-success">
                                        <i class="fas fa-check-circle"></i>
                                        اكتمل: {{ $m['completed_at'] }}
                                    </div>
                                @endif
                                @if(!empty($m['notes']))
                                    <div class="mt-2 small text-muted fst-italic">{{ $m['notes'] }}</div>
                                @endif
                                @if(!empty($m['task_id']))
                                    <div class="mt-3">
                                        <a href="{{ route('operational.task-detail', $m['task_id']) }}"
                                           class="btn btn-sm btn-outline-primary w-100">
                                            <i class="fas fa-eye"></i> عرض المهمة
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>


    @endif
</div>


<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="rejectForm" action="">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title mb-0">رفض الإسناد</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2 small text-muted" id="rejectTaskTitle"></div>
                    <label class="form-label">سبب الرفض <span class="text-danger">*</span></label>
                    <textarea name="rejection_reason" class="form-control" rows="3"
                              required minlength="3" maxlength="500"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-danger">تأكيد الرفض</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection


@push('scripts')
<script>
(function () {
    const rejectModal = document.getElementById('rejectModal');
    if (!rejectModal) return;


    rejectModal.addEventListener('show.bs.modal', function (e) {
        const trigger = e.relatedTarget;
        const id    = trigger.getAttribute('data-assignment');
        const title = trigger.getAttribute('data-task-title');
        document.getElementById('rejectForm').setAttribute('action',
            `{{ url('/dashboard/employee/assignments') }}/${id}/reject`);
        document.getElementById('rejectTaskTitle').textContent = title || '';
    });
})();
</script>
@endpush

@extends('layouts.app')


@section('title', 'لوحة الموظف')


@push('styles')
<style>
  .emp-kpi { border:0; border-radius:12px; background:#fff; box-shadow:0 2px 10px rgba(13,27,62,.06); padding:16px; height:100%; }
  .emp-kpi .kpi-value { font-size:1.6rem; font-weight:800; color:#0D1B3E; }
  .emp-kpi .kpi-label { color:#5b6580; font-size:.82rem; }
  .emp-kpi .kpi-sub   { color:#8792a8; font-size:.75rem; }
  .emp-task-card { border:1px solid #e6e9f2; border-radius:12px; background:#fff; padding:16px; height:100%; transition:.15s; }
  .emp-task-card:hover { box-shadow:0 6px 20px rgba(13,27,62,.08); border-color:#c7d2fe; }
  .emp-task-card .task-title { font-weight:700; color:#0D1B3E; }
  .emp-task-card .meta { color:#5b6580; font-size:.82rem; }
  .badge-delayed-actual { background:#F44336; color:#fff; }
  .pending-card { border:1px solid #e6e9f2; border-radius:12px; background:#fff; padding:16px; }
  .stat-hours-bar { height:8px; background:#eef1f7; border-radius:6px; overflow:hidden; }
  .stat-hours-bar > span { display:block; height:100%; background:linear-gradient(90deg,#1e40af,#3b82f6); }
  .stat-hours-bar.over > span { background:linear-gradient(90deg,#b91c1c,#ef4444); }
</style>
@endpush


@section('content')
<div class="container-fluid" dir="rtl">


  @php
    $emp      = $data['employee']  ?? [];
    $kpis     = $data['kpis']      ?? [];
    $incomplete = $data['incomplete_tasks']    ?? [];
    $pending    = $data['pending_assignments'] ?? [];
    $estH  = (float)($kpis['estimated_hours'] ?? 0);
    $actH  = (float)($kpis['actual_hours'] ?? 0);
    $diff  = (float)($kpis['hours_diff'] ?? 0);
    $hoursPct = $estH > 0 ? min(100, round(($actH / $estH) * 100)) : 0;
  @endphp


  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="mb-1 fw-bold" style="color:#0D1B3E;">{{ $emp['full_name'] ?? session('user_name') }}</h4>
      <div class="text-muted small">
        {{ $emp['job_title'] ?? '' }}
        @if(!empty($emp['department'])) • {{ $emp['department'] }} @endif
        • {{ now()->translatedFormat('l، d F Y') }}
      </div>
    </div>
  </div>


  @if(!empty($error))
    <div class="alert alert-danger">تعذر تحميل البيانات: {{ $error }}</div>
  @else


  <div class="row g-3 mb-4">
    <div class="col-md-3 col-sm-6">
      <div class="emp-kpi">
        <div class="kpi-label">إجمالي المهام</div>
        <div class="kpi-value">{{ $kpis['total_tasks'] ?? 0 }}</div>
      </div>
    </div>
    <div class="col-md-3 col-sm-6">
      <div class="emp-kpi">
        <div class="kpi-label">نسبة الإنجاز</div>
        <div class="kpi-value text-success">{{ $kpis['completion_rate'] ?? 0 }}%</div>
        <div class="stat-hours-bar mt-2"><span style="width: {{ $kpis['completion_rate'] ?? 0 }}%"></span></div>
      </div>
    </div>
    <div class="col-md-3 col-sm-6">
      <div class="emp-kpi">
        <div class="kpi-label">مكتملة</div>
        <div class="kpi-value text-success">{{ $kpis['completed'] ?? 0 }}</div>
        <div class="kpi-sub">قيد التنفيذ: {{ $kpis['in_progress'] ?? 0 }}</div>
      </div>
    </div>
    <div class="col-md-3 col-sm-6">
      <div class="emp-kpi">
        <div class="kpi-label">مهام متأخرة فعليًا</div>
        <div class="kpi-value {{ ($kpis['delayed'] ?? 0) > 0 ? 'text-danger' : '' }}">
          {{ $kpis['delayed'] ?? 0 }}
        </div>
      </div>
    </div>
  </div>


  <div class="row g-3 mb-4">
    <div class="col-md-4 col-sm-6">
      <div class="emp-kpi">
        <div class="kpi-label">الساعات المقدرة</div>
        <div class="kpi-value">{{ number_format($estH, 1) }}</div>
      </div>
    </div>
    <div class="col-md-4 col-sm-6">
      <div class="emp-kpi">
        <div class="kpi-label">الساعات الفعلية</div>
        <div class="kpi-value text-primary">{{ number_format($actH, 1) }}</div>
      </div>
    </div>
    <div class="col-md-4 col-sm-6">
      <div class="emp-kpi">
        <div class="kpi-label">الفرق (فعلي − مقدر)</div>
        <div class="kpi-value {{ $diff > 0 ? 'text-danger' : 'text-success' }}">
          {{ $diff > 0 ? '+' : '' }}{{ number_format($diff, 1) }}
        </div>
        <div class="stat-hours-bar mt-2 {{ $actH > $estH ? 'over' : '' }}">
          <span style="width: {{ $hoursPct }}%"></span>
        </div>
      </div>
    </div>
  </div>


  <div class="row g-3 mb-4">
    <div class="col-md-3 col-sm-6">
      <div class="emp-kpi">
        <div class="kpi-label">إجراءات المخاطر المسندة إليّ</div>
        <div class="kpi-value text-warning">{{ $kpis['my_mitigations_total'] ?? 0 }}</div>
      </div>
    </div>
    <div class="col-md-3 col-sm-6">
      <div class="emp-kpi">
        <div class="kpi-label">معلّقة مؤقتًا</div>
        <div class="kpi-value text-secondary">{{ $kpis['on_hold'] ?? 0 }}</div>
      </div>
    </div>
    <div class="col-md-3 col-sm-6">
      <div class="emp-kpi">
        <div class="kpi-label">قيد المراجعة</div>
        <div class="kpi-value text-info">{{ $kpis['review'] ?? 0 }}</div>
      </div>
    </div>
    <div class="col-md-3 col-sm-6">
      <div class="emp-kpi">
        <div class="kpi-label">بانتظار قبولك</div>
        <div class="kpi-value text-warning">{{ count($pending) }}</div>
      </div>
    </div>
  </div>


  @if(!empty($pending))
    <div class="mb-4">
      <h6 class="fw-bold mb-3" style="color:#0D1B3E;">
        مهام بانتظار قبولك ({{ count($pending) }})
      </h6>
      <div class="row g-3">
        @foreach($pending as $p)
          <div class="col-lg-6">
            <div class="pending-card">
              <div class="task-title mb-1">{{ $p['title'] }}</div>
              <div class="meta mb-1">
                <span class="badge bg-light text-dark border">{{ $p['role_type'] ?? '—' }}</span>
                <span class="ms-2">الاستحقاق: {{ $p['estimated_hours'] }} ساعة</span>
              </div>
              <div class="meta mb-1">المبادرة: {{ $p['initiative'] ?? '—' }}</div>
              <div class="meta mb-2">المهمة الكبرى: {{ $p['major_task'] ?? '—' }}</div>
              @if(!empty($p['note']))
                <div class="alert alert-light border small mb-2">{{ $p['note'] }}</div>
              @endif
              <div class="d-flex gap-2">
                <form method="POST"
                      action="{{ route('employee.assignments.accept', $p['assignment_id']) }}"
                      class="d-inline">
                  @csrf
                  <button type="submit" class="btn btn-sm btn-success">قبول</button>
                </form>
                <button type="button" class="btn btn-sm btn-outline-danger"
                        data-bs-toggle="modal" data-bs-target="#rejectModal"
                        data-assignment="{{ $p['assignment_id'] }}"
                        data-task-title="{{ $p['title'] }}">
                  رفض
                </button>
                <a href="{{ url('/tasks/' . $p['task_id']) }}"
                   class="btn btn-sm btn-outline-secondary ms-auto">تفاصيل</a>
              </div>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  @endif


  <div class="mb-4">
    <h6 class="fw-bold mb-3" style="color:#0D1B3E;">
      مهامي غير المكتملة ({{ count($incomplete) }})
    </h6>
    <div class="row g-3">
      @forelse($incomplete as $t)
        <div class="col-lg-4 col-md-6">
          <a href="{{ url('/operational/task/' . $t['task_id']) }}" class="text-decoration-none">
            <div class="emp-task-card">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <div class="task-title">{{ $t['title'] }}</div>
                @if(!empty($t['is_delayed']))
                  <span class="badge badge-delayed-actual">متأخرة فعليًا</span>
                @endif
              </div>
              <div class="meta mb-1">
                <span class="badge bg-light text-dark border">{{ $t['my_role'] ?? '—' }}</span>
                <span class="badge bg-light text-dark border ms-1">{{ $t['status_ar'] ?? '—' }}</span>
              </div>
              <div class="meta mb-1">المبادرة: {{ $t['initiative'] ?? '—' }}</div>
              <div class="meta mb-1">المهمة الكبرى: {{ $t['major_task'] ?? '—' }}</div>
              <div class="meta mb-2">موعد الانتهاء: {{ $t['end_date'] ?? '—' }}</div>
              <div class="d-flex justify-content-between">
                <div class="meta">
                  <span class="text-muted">ساعات مقدرة:</span>
                  <strong>{{ $t['estimated_hours'] }}</strong>
                </div>
                <div class="meta">
                  <span class="text-muted">مخاطر:</span>
                  <strong>{{ $t['risks_count'] }}</strong>
                </div>
                <div class="meta">
                  <span class="text-muted">إجراءات مسندة:</span>
                  <strong>{{ $t['my_mitigations_count'] }}</strong>
                </div>
              </div>
            </div>
          </a>
        </div>
      @empty
        <div class="col-12">
          <div class="text-center text-muted py-4">لا توجد مهام غير مكتملة.</div>
        </div>
      @endforelse
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

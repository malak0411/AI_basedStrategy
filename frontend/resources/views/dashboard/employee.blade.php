@extends('layouts.app')


@section('title', 'لوحة الموظف')


@push('styles')
<style>
  .dirigo-kpi-card { border:0; border-radius:12px; background:#fff; box-shadow:0 2px 10px rgba(13,27,62,.06); }
  .dirigo-kpi-card .kpi-value { font-size:1.75rem; font-weight:800; color:#0D1B3E; }
  .dirigo-kpi-card .kpi-label { color:#5b6580; font-size:.85rem; }
  .dirigo-attention-item { border-inline-start:4px solid #e6e9f2; padding:12px 14px; border-radius:8px; background:#fbfcfe; margin-bottom:10px; }
  .dirigo-attention-item.risk      { border-inline-start-color:#F44336; background:#fff5f5; }
  .dirigo-attention-item.warn      { border-inline-start-color:#FF9800; background:#fffaf0; }
  .dirigo-attention-item.info      { border-inline-start-color:#2196F3; background:#f2f8ff; }
  .dirigo-attention-item.success   { border-inline-start-color:#4CAF50; background:#f4fbf4; }
  .badge-delayed-actual { background:#F44336; color:#fff; }
  .task-progress-bar { height:6px; background:#eef1f7; border-radius:4px; overflow:hidden; }
  .task-progress-bar > span { display:block; height:100%; background:linear-gradient(90deg,#1e40af,#3b82f6); }
  .chart-wrap { height:280px; }
  .pending-card { border:1px solid #e6e9f2; border-radius:10px; padding:14px; background:#fff; }
  .ai-pill { display:inline-flex; align-items:center; gap:6px; padding:4px 10px; border-radius:20px; font-size:.75rem; font-weight:600; }
  .ai-pill.danger { background:#fde8e8; color:#b42318; }
  .ai-pill.warn   { background:#fff4e5; color:#b54708; }
  .ai-pill.ok     { background:#e7f6ec; color:#027a48; }
</style>
@endpush


@section('content')
<div class="container-fluid" dir="rtl">


  @php
    $emp      = $data['employee']  ?? [];
    $kpis     = $data['kpis']      ?? [];
    $chart    = $data['chart']     ?? [];
    $attn     = $data['needs_attention'] ?? [];
    $tasks    = $data['tasks']     ?? [];
    $pending  = $data['pending_assignments'] ?? [];
    $insights = $data['ai_insights'] ?? [];
  @endphp


  @if(!empty($error))
    <div class="alert alert-danger">تعذر تحميل البيانات: {{ $error }}</div>
  @else


  {{-- Header --}}
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="mb-1 fw-bold" style="color:#0D1B3E;">{{ $emp['full_name'] ?? session('user_name') }}</h4>
      <div class="text-muted small">
        {{ $emp['job_title'] ?? '' }}
        @if(!empty($emp['department'])) • {{ $emp['department'] }} @endif
        • {{ now()->translatedFormat('l، d F Y') }}
      </div>
    </div>
    <div class="d-flex align-items-center gap-2">
      <span class="badge rounded-pill bg-primary">{{ count($notifications) }} إشعار</span>
    </div>
  </div>


  {{-- Performance Cards --}}
  <div class="row g-3 mb-4">
    <div class="col-md-3 col-sm-6">
      <a href="{{ url('/tasks') }}" class="text-decoration-none">
        <div class="dirigo-kpi-card p-3 h-100">
          <div class="kpi-label">إجمالي المهام</div>
          <div class="kpi-value">{{ $kpis['total_tasks'] ?? 0 }}</div>
        </div>
      </a>
    </div>
    <div class="col-md-3 col-sm-6">
      <div class="dirigo-kpi-card p-3 h-100">
        <div class="kpi-label">نسبة الإنجاز</div>
        <div class="kpi-value text-success">{{ $kpis['completion_rate'] ?? 0 }}%</div>
        <div class="task-progress-bar mt-2">
          <span style="width: {{ $kpis['completion_rate'] ?? 0 }}%"></span>
        </div>
      </div>
    </div>
    <div class="col-md-3 col-sm-6">
      <div class="dirigo-kpi-card p-3 h-100">
        <div class="kpi-label">الساعات الفعلية / المقدرة</div>
        <div class="kpi-value">
          {{ $kpis['actual_hours'] ?? 0 }}
          <span class="text-muted fs-6">/ {{ $kpis['estimated_hours'] ?? 0 }}</span>
        </div>
      </div>
    </div>
    <div class="col-md-3 col-sm-6">
      <div class="dirigo-kpi-card p-3 h-100">
        <div class="kpi-label">الإجراءات المنفذة</div>
        <div class="kpi-value text-primary">{{ $kpis['actions_count'] ?? 0 }}</div>
      </div>
    </div>
  </div>


  {{-- AI Insights --}}
  @if(($insights['high_delay_risk_count'] ?? 0) + ($insights['high_risk_count'] ?? 0) + ($insights['pending_mitigations'] ?? 0) > 0)
    <div class="dirigo-kpi-card p-3 mb-4 border-start border-4 border-primary">
      <div class="d-flex align-items-center gap-2 mb-2">
        <strong style="color:#0D1B3E;">AI Insights</strong>
      </div>
      <div class="row g-2">
        <div class="col-md-3 col-6">
          <div class="small text-muted">مهام باحتمال تأخر مرتفع</div>
          <div class="fw-bold text-danger">{{ $insights['high_delay_risk_count'] ?? 0 }}</div>
        </div>
        <div class="col-md-3 col-6">
          <div class="small text-muted">مخاطر مرتفعة</div>
          <div class="fw-bold text-warning">{{ $insights['high_risk_count'] ?? 0 }}</div>
        </div>
        <div class="col-md-3 col-6">
          <div class="small text-muted">إجراءات معالجة تحتاج متابعة</div>
          <div class="fw-bold text-info">{{ $insights['pending_mitigations'] ?? 0 }}</div>
        </div>
        <div class="col-md-3 col-6">
          <div class="small text-muted">مهام متأخرة فعليًا</div>
          <div class="fw-bold text-danger">{{ $insights['actually_delayed'] ?? 0 }}</div>
        </div>
      </div>
    </div>
  @endif


  {{-- Performance Chart --}}
  <div class="dirigo-kpi-card p-3 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <strong style="color:#0D1B3E;">الأداء الشهري</strong>
      <div class="btn-group btn-group-sm" role="group">
        <button class="btn btn-outline-primary active" data-range="monthly" type="button">شهري</button>
        <button class="btn btn-outline-primary" data-range="yearly" type="button">سنوي</button>
      </div>
    </div>
    <div class="chart-wrap"><canvas id="performanceChart"></canvas></div>
  </div>


  {{-- يحتاج إلى انتباهك --}}
  <div class="row g-3 mb-4">
    <div class="col-lg-8">
      <div class="dirigo-kpi-card p-3 h-100">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <strong style="color:#0D1B3E;">يحتاج إلى انتباهك</strong>
          <span class="badge bg-secondary">{{ count($attn) }}</span>
        </div>


        @php
          $typeStyles = [
            'ai_delay_risk'       => ['risk',  'تنبؤ AI'],
            'risk_detected'       => ['risk',  'خطر مكتشف'],
            'actually_delayed'    => ['risk',  'متأخرة فعليًا'],
            'pending_acceptance'  => ['info',  'بانتظار القبول'],
            'mitigation_assigned' => ['warn',  'إجراء معالجة'],
            'returned_for_review' => ['warn',  'معاد للمراجعة'],
          ];
        @endphp


        @forelse($attn as $item)
          @php $meta = $typeStyles[$item['type']] ?? ['info', 'متابعة']; @endphp
          <div class="dirigo-attention-item {{ $meta[0] }}">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                  <span class="badge bg-light text-dark border">{{ $meta[1] }}</span>
                  @if(!empty($item['task_title']))
                    <span class="fw-bold">{{ $item['task_title'] }}</span>
                  @endif
                </div>
                <div class="small text-muted">{{ $item['message'] ?? '' }}</div>
              </div>
              <div class="text-end">
                @if(!empty($item['task_id']))
                  <a href="{{ url('/tasks/' . $item['task_id']) }}"
                     class="btn btn-sm btn-outline-primary">تفاصيل</a>
                @endif
              </div>
            </div>
          </div>
        @empty
          <div class="text-center text-muted py-3">لا توجد عناصر تحتاج انتباهك.</div>
        @endforelse
      </div>
    </div>


    {{-- Pending Assignments --}}
    <div class="col-lg-4">
      <div class="dirigo-kpi-card p-3 h-100">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <strong style="color:#0D1B3E;">مهام تنتظر قبولك</strong>
          <span class="badge bg-warning text-dark">{{ count($pending) }}</span>
        </div>


        @forelse($pending as $p)
          <div class="pending-card mb-2">
            <div class="fw-bold">{{ $p['task_title'] }}</div>
            <div class="small text-muted mb-2">
              {{ $p['initiative'] ?? '' }}
              @if(!empty($p['role_type'])) • دورك: {{ $p['role_type'] }} @endif
            </div>
            <div class="d-flex gap-2">
              <form method="POST" action="{{ route('employee.assignments.accept', $p['assignment_id']) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-sm btn-success">قبول</button>
              </form>
              <button type="button" class="btn btn-sm btn-outline-danger"
                      data-bs-toggle="modal" data-bs-target="#rejectModal"
                      data-assignment="{{ $p['assignment_id'] }}"
                      data-task-title="{{ $p['task_title'] }}">
                رفض
              </button>
            </div>
          </div>
        @empty
          <div class="text-center text-muted py-3">لا توجد مهام بانتظار القبول.</div>
        @endforelse
      </div>
    </div>
  </div>


  {{-- My Tasks --}}
  <div class="dirigo-kpi-card mb-4">
    <div class="p-3 d-flex justify-content-between align-items-center">
      <strong style="color:#0D1B3E;">مهامي</strong>
      <a href="{{ url('/tasks') }}" class="btn btn-sm btn-outline-primary">عرض الكل</a>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>المهمة</th>
            <th>دوري</th>
            <th>المبادرة</th>
            <th>المهمة الرئيسية</th>
            <th>الموعد</th>
            <th style="min-width:120px;">التقدم</th>
            <th>الحالة</th>
            <th>AI Risk</th>
            <th>إجراء</th>
          </tr>
        </thead>
        <tbody>
        @forelse($tasks as $t)
          <tr>
            <td>
              <div class="fw-semibold">{{ $t['title'] }}</div>
              @if(!empty($t['is_delayed']))
                <span class="badge badge-delayed-actual mt-1">متأخرة فعليًا</span>
              @endif
            </td>
            <td class="small">{{ $t['my_role'] ?? '—' }}</td>
            <td class="small text-muted">{{ $t['initiative'] ?? '—' }}</td>
            <td class="small text-muted">{{ $t['major_task'] ?? '—' }}</td>
            <td class="small">{{ $t['end_date'] ?? '—' }}</td>
            <td>
              <div class="task-progress-bar"><span style="width: {{ $t['progress'] ?? 0 }}%"></span></div>
              <small class="text-muted">{{ $t['progress'] ?? 0 }}%</small>
            </td>
            <td>
              <span class="badge bg-light text-dark border">{{ $t['status_ar'] ?? '—' }}</span>
            </td>
            <td>
              @if(!empty($t['ai_risk']))
                @php $level = $t['ai_risk']['level']; $p = round(($t['ai_risk']['probability'] ?? 0) * 100); @endphp
                <span class="ai-pill {{ $level === 'high' ? 'danger' : 'ok' }}">
                  {{ $p }}% تأخر
                </span>
              @else
                <span class="text-muted small">—</span>
              @endif
            </td>
            <td>
              <a href="{{ url('/tasks/' . $t['task_id']) }}" class="btn btn-sm btn-outline-primary">تفاصيل</a>
            </td>
          </tr>
        @empty
          <tr><td colspan="9" class="text-center text-muted py-4">لا توجد مهام متاحة.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>


  @endif
</div>


{{-- Modal: رفض الإسناد --}}
<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" id="rejectForm" action="">
      @csrf
      <div class="modal-content">
        <div class="modal-header">
          <h6 class="modal-title">رفض الإسناد</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-2 small text-muted" id="rejectTaskTitle"></div>
          <label class="form-label">سبب الرفض <span class="text-danger">*</span></label>
          <textarea name="rejection_reason" class="form-control" rows="3" required minlength="3"></textarea>
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
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
  const chartData = @json($chart ?? []);


  const ctx = document.getElementById('performanceChart');
  if (!ctx || !chartData.labels) return;


  const chart = new Chart(ctx, {
    type: 'line',
    data: {
      labels: chartData.labels,
      datasets: [
        { label: 'المهام',         data: chartData.tasks || [],           borderColor: '#0D1B3E', backgroundColor: 'rgba(13,27,62,.08)',  yAxisID: 'y',  tension: .35, fill: true },
        { label: 'نسبة الإنجاز',    data: chartData.completion_rate || [], borderColor: '#4CAF50', backgroundColor: 'rgba(76,175,80,.08)', yAxisID: 'y1', tension: .35 },
        { label: 'ساعات العمل',    data: chartData.work_hours || [],      borderColor: '#2196F3', backgroundColor: 'rgba(33,150,243,.08)', yAxisID: 'y',  tension: .35 },
        { label: 'الإجراءات',       data: chartData.actions || [],         borderColor: '#FF9800', backgroundColor: 'rgba(255,152,0,.08)',  yAxisID: 'y',  tension: .35, borderDash: [4,4] },
      ],
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      plugins: { legend: { position: 'bottom', labels: { font: { family: 'system-ui' } } } },
      scales: {
        y:  { beginAtZero: true, position: 'right' },
        y1: { beginAtZero: true, position: 'left', max: 100, grid: { drawOnChartArea: false } },
      },
    },
  });


  document.querySelectorAll('[data-range]').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('[data-range]').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      // التبديل بين شهري/سنوي يعرض نفس البيانات حاليًا؛ البيانات السنوية تُضاف عند توفر endpoint.
    });
  });


  const rejectModal = document.getElementById('rejectModal');
  if (rejectModal) {
    rejectModal.addEventListener('show.bs.modal', e => {
      const trigger = e.relatedTarget;
      const id    = trigger.getAttribute('data-assignment');
      const title = trigger.getAttribute('data-task-title');
      document.getElementById('rejectForm').setAttribute('action',
        `{{ url('/dashboard/employee/assignments') }}/${id}/reject`);
      document.getElementById('rejectTaskTitle').textContent = title || '';
    });
  }
})();
</script>
@endpush

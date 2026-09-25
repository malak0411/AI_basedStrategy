@extends('layouts.app')


@section('title', 'لوحة المدير')


@section('content')
<div class="container-fluid" dir="rtl">


  @php
    $isManager   = in_array($role, ['manager','general_manager','deputy','minister','super_admin']);
    $isExecutive = in_array($role, ['general_manager','deputy','minister','super_admin']);
    $isStrategic = in_array($role, ['minister','deputy','super_admin']);


    $personal   = $data['personal']   ?? [];
    $dept       = $data['department'] ?? [];
    $tasks      = $data['tasks']      ?? [];
    $employees  = $data['employees']  ?? [];
    $risks      = $data['risks']      ?? [];
    $mitig      = $data['mitigations']?? [];
    $aiPreds    = $data['ai_predictions'] ?? [];


    $pEmp = $personal['employee'] ?? [];
    $pKpi = $personal['kpis']     ?? [];
  @endphp


  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="mb-1">{{ $pEmp['full_name'] ?? session('user_name') }}</h4>
      <div class="text-muted small">
        {{ $pEmp['job_title'] ?? '' }}
        @if(!empty($dept['department_name'])) • {{ $dept['department_name'] }} @endif
        • {{ now()->translatedFormat('l، d F Y') }}
      </div>
    </div>
  </div>


  <ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item">
      <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-personal">عملي الشخصي</button>
    </li>
    <li class="nav-item">
      <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-dept">إدارة القسم</button>
    </li>
    @if($isManager)
      <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-ai">AI Predictions</button>
      </li>
    @endif
  </ul>


  <div class="tab-content">


    <div class="tab-pane fade show active" id="tab-personal">
      <div class="row g-3 mb-4">
        <div class="col-md-3 col-sm-6">
          <div class="card shadow-sm"><div class="card-body">
            <div class="text-muted small">إجمالي مهامي</div>
            <div class="fs-3 fw-bold text-primary">{{ $pKpi['total_tasks'] ?? 0 }}</div>
          </div></div>
        </div>
        <div class="col-md-3 col-sm-6">
          <div class="card shadow-sm"><div class="card-body">
            <div class="text-muted small">قيد التنفيذ</div>
            <div class="fs-3 fw-bold text-info">{{ $pKpi['in_progress'] ?? 0 }}</div>
          </div></div>
        </div>
        <div class="col-md-3 col-sm-6">
          <div class="card shadow-sm"><div class="card-body">
            <div class="text-muted small">متأخرة</div>
            <div class="fs-3 fw-bold text-danger">{{ $pKpi['delayed'] ?? 0 }}</div>
          </div></div>
        </div>
        <div class="col-md-3 col-sm-6">
          <div class="card shadow-sm"><div class="card-body">
            <div class="text-muted small">مكتملة</div>
            <div class="fs-3 fw-bold text-success">{{ $pKpi['completed'] ?? 0 }}</div>
          </div></div>
        </div>
      </div>


      @if(!empty($personal['ai_alerts']))
        <div class="card border-warning shadow-sm mb-4">
          <div class="card-header bg-warning bg-opacity-10">
            <strong>تنبيهات الذكاء الاصطناعي على مهامي</strong>
          </div>
          <div class="card-body">
            @foreach($personal['ai_alerts'] as $a)
              <div class="d-flex align-items-start mb-3">
                <span class="badge bg-danger me-2">{{ round(($a['probability'] ?? 0) * 100) }}%</span>
                <div>
                  <div class="fw-bold">{{ $a['task_title'] ?? '—' }}</div>
                  <div class="small text-muted">احتمال تأخر هذه المهمة (ثقة {{ round(($a['confidence'] ?? 0) * 100) }}%).</div>
                </div>
              </div>
            @endforeach
          </div>
        </div>
      @endif


      @if(!empty($personal['needs_attention']))
        <div class="card border-danger shadow-sm mb-4">
          <div class="card-header bg-danger bg-opacity-10">
            <strong class="text-danger">تحتاج انتباهك ({{ count($personal['needs_attention']) }})</strong>
          </div>
          <ul class="list-group list-group-flush">
            @foreach($personal['needs_attention'] as $a)
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <div>
                  <a href="{{ url('/tasks/' . ($a['task_id'] ?? '')) }}" class="fw-bold text-decoration-none">
                    {{ $a['title'] ?? '—' }}
                  </a>
                  <div class="small">
                    @foreach(($a['reasons'] ?? []) as $r)
                      <span class="badge bg-light text-dark border me-1">{{ $r }}</span>
                    @endforeach
                  </div>
                </div>
                <div class="text-end small text-muted">
                  {{ $a['progress'] ?? 0 }}% • {{ $a['end_date'] ?? '—' }}
                </div>
              </li>
            @endforeach
          </ul>
        </div>
      @endif
    </div>


    <div class="tab-pane fade" id="tab-dept">
      <div class="row g-3 mb-4">
        <div class="col-md-2 col-6">
          <div class="card text-center shadow-sm"><div class="card-body">
            <div class="small text-muted">الموظفون</div>
            <div class="fs-4 fw-bold">{{ $dept['employees_count'] ?? 0 }}</div>
          </div></div>
        </div>
        <div class="col-md-2 col-6">
          <div class="card text-center shadow-sm"><div class="card-body">
            <div class="small text-muted">إجمالي المهام</div>
            <div class="fs-4 fw-bold">{{ $dept['total_tasks'] ?? 0 }}</div>
          </div></div>
        </div>
        <div class="col-md-2 col-6">
          <div class="card text-center shadow-sm"><div class="card-body">
            <div class="small text-muted">قيد التنفيذ</div>
            <div class="fs-4 fw-bold text-info">{{ $dept['in_progress'] ?? 0 }}</div>
          </div></div>
        </div>
        <div class="col-md-2 col-6">
          <div class="card text-center shadow-sm"><div class="card-body">
            <div class="small text-muted">متأخرة</div>
            <div class="fs-4 fw-bold text-danger">{{ $dept['delayed'] ?? 0 }}</div>
          </div></div>
        </div>
        <div class="col-md-2 col-6">
          <div class="card text-center shadow-sm"><div class="card-body">
            <div class="small text-muted">مكتملة</div>
            <div class="fs-4 fw-bold text-success">{{ $dept['completed'] ?? 0 }}</div>
          </div></div>
        </div>
        <div class="col-md-2 col-6">
          <div class="card text-center shadow-sm"><div class="card-body">
            <div class="small text-muted">مخاطر عالية</div>
            <div class="fs-4 fw-bold text-danger">{{ $dept['high_risks'] ?? 0 }}</div>
          </div></div>
        </div>
      </div>


      <div class="card shadow-sm mb-4">
        <div class="card-header"><strong>مهام الإدارة</strong></div>
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th>المهمة</th>
                <th>الموظف المسؤول</th>
                <th>الأولوية</th>
                <th>الموعد</th>
                <th>الإنجاز</th>
                <th>الحالة</th>
                <th>الإجراء</th>
              </tr>
            </thead>
            <tbody>
            @forelse($tasks as $t)
              <tr>
                <td>{{ $t['title'] ?? '—' }}</td>
                <td class="small">{{ !empty($t['assignees']) ? implode('، ', $t['assignees']) : '—' }}</td>
                <td>
                  @php $p = $t['priority_id'] ?? 0; @endphp
                  <span class="badge bg-{{ $p == 1 ? 'danger' : ($p == 2 ? 'warning' : 'secondary') }}">
                    {{ $p == 1 ? 'عاجل' : ($p == 2 ? 'مرتفع' : 'متوسط') }}
                  </span>
                </td>
                <td>{{ $t['end_date'] ?? '—' }}</td>
                <td style="min-width:120px">
                  <div class="progress" style="height:6px">
                    <div class="progress-bar" style="width: {{ $t['progress'] ?? 0 }}%"></div>
                  </div>
                  <small class="text-muted">{{ $t['progress'] ?? 0 }}%</small>
                </td>
                <td>
                  @php $sc = $t['status_code'] ?? ''; @endphp
                  <span class="badge bg-{{ $sc == 'completed' ? 'success' : ($sc == 'delayed' ? 'danger' : 'info') }}">
                    {{ $sc ?: '—' }}
                  </span>
                </td>
                <td>
                  <a href="{{ url('/tasks/' . ($t['task_id'] ?? '')) }}" class="btn btn-sm btn-outline-secondary">عرض</a>
                  @if($isManager)
                    <a href="{{ url('/tasks/' . ($t['task_id'] ?? '') . '/edit') }}" class="btn btn-sm btn-outline-primary">تعديل</a>
                  @endif
                  @if($isExecutive)
                    <a href="{{ url('/tasks/' . ($t['task_id'] ?? '') . '/assign') }}" class="btn btn-sm btn-outline-success">إسناد</a>
                  @endif
                </td>
              </tr>
            @empty
              <tr><td colspan="7" class="text-center text-muted py-4">لا توجد بيانات متاحة.</td></tr>
            @endforelse
            </tbody>
          </table>
        </div>
      </div>


      <div class="card shadow-sm mb-4">
        <div class="card-header"><strong>أداء الموظفين</strong></div>
        <div class="table-responsive">
          <table class="table table-sm mb-0">
            <thead class="table-light">
              <tr>
                <th>الموظف</th>
                <th>الإجمالي</th>
                <th>مكتملة</th>
                <th>متأخرة</th>
                <th>نسبة الإنجاز</th>
                <th>متوسط التقدم</th>
              </tr>
            </thead>
            <tbody>
            @forelse($employees as $e)
              <tr>
                <td>
                  {{ $e['full_name'] ?? '—' }}
                  <div class="small text-muted">{{ $e['job_title'] ?? '' }}</div>
                </td>
                <td>{{ $e['total'] ?? 0 }}</td>
                <td class="text-success">{{ $e['completed'] ?? 0 }}</td>
                <td class="text-danger">{{ $e['delayed'] ?? 0 }}</td>
                <td>{{ $e['completion_rate'] ?? 0 }}%</td>
                <td>{{ $e['avg_progress'] ?? 0 }}%</td>
              </tr>
            @empty
              <tr><td colspan="6" class="text-center text-muted py-4">لا توجد بيانات متاحة.</td></tr>
            @endforelse
            </tbody>
          </table>
        </div>
      </div>


      <div class="row g-3">
        <div class="col-md-6">
          <div class="card shadow-sm h-100">
            <div class="card-header"><strong>مخاطر الإدارة</strong></div>
            <div class="table-responsive">
              <table class="table table-sm mb-0">
                <thead class="table-light">
                  <tr>
                    <th>الخطر</th>
                    <th>المهمة</th>
                    <th>الاحتمالية</th>
                    <th>الأثر</th>
                    <th>Score</th>
                  </tr>
                </thead>
                <tbody>
                @forelse($risks as $r)
                  <tr>
                    <td>{{ $r['name'] ?? '—' }}</td>
                    <td class="small text-muted">{{ $r['task_title'] ?? '—' }}</td>
                    <td>{{ $r['probability'] ?? 0 }}/10</td>
                    <td>{{ $r['impact'] ?? 0 }}/10</td>
                    <td><strong>{{ $r['score'] ?? 0 }}</strong></td>
                  </tr>
                @empty
                  <tr><td colspan="5" class="text-center text-muted py-3">لا توجد بيانات متاحة.</td></tr>
                @endforelse
                </tbody>
              </table>
            </div>
          </div>
        </div>


        <div class="col-md-6">
          <div class="card shadow-sm h-100">
            <div class="card-header"><strong>إجراءات التخفيف</strong></div>
            <div class="table-responsive">
              <table class="table table-sm mb-0">
                <thead class="table-light">
                  <tr>
                    <th>الإجراء</th>
                    <th>المسؤول</th>
                    <th>الاستحقاق</th>
                    <th>الحالة</th>
                  </tr>
                </thead>
                <tbody>
                @forelse($mitig as $m)
                  <tr>
                    <td>{{ $m['action'] ?? '—' }}</td>
                    <td>{{ $m['assigned_to'] ?? '—' }}</td>
                    <td>{{ $m['due_date'] ?? '—' }}</td>
                    <td><span class="badge bg-secondary">{{ $m['status_ar'] ?? '—' }}</span></td>
                  </tr>
                @empty
                  <tr><td colspan="4" class="text-center text-muted py-3">لا توجد بيانات متاحة.</td></tr>
                @endforelse
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>


    @if($isManager)
      <div class="tab-pane fade" id="tab-ai">
        <div class="card shadow-sm">
          <div class="card-header"><strong>توقعات الذكاء الاصطناعي</strong></div>
          <div class="table-responsive">
            <table class="table table-sm mb-0">
              <thead class="table-light">
                <tr>
                  <th>المهمة</th>
                  <th>الموظف</th>
                  <th>احتمالية التأخر</th>
                  <th>الثقة</th>
                  <th>أهم العوامل</th>
                </tr>
              </thead>
              <tbody>
              @forelse($aiPreds as $p)
                <tr>
                  <td>{{ $p['task_title'] ?? '—' }}</td>
                  <td>{{ $p['employee'] ?? '—' }}</td>
                  <td>
                    @php $prob = $p['probability'] ?? 0; @endphp
                    <span class="badge bg-{{ $prob >= 0.9 ? 'danger' : ($prob >= 0.7 ? 'warning' : 'secondary') }}">
                      {{ round($prob * 100) }}%
                    </span>
                  </td>
                  <td>{{ round(($p['confidence'] ?? 0) * 100) }}%</td>
                  <td class="small text-muted">{{ $p['text'] ?? '—' }}</td>
                </tr>
              @empty
                <tr><td colspan="5" class="text-center text-muted py-4">لا توجد بيانات متاحة.</td></tr>
              @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    @endif


  </div>
</div>
@endsection

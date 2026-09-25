@extends('layouts.app')


@section('title', 'لوحة الوزير')


@section('content')
<div class="container-fluid" dir="rtl">


  @php
    $isStrategic = in_array($role, ['minister','deputy','super_admin']);


    $s          = $data['strategic']             ?? [];
    $hierarchy  = $data['hierarchy']             ?? [];
    $kpis       = $data['kpis']                  ?? [];
    $attention  = $data['initiatives_attention'] ?? [];
    $departments= $data['departments']           ?? [];
    $stRisks    = $data['strategic_risks']       ?? [];
    $insights   = $data['ai_insights']           ?? [];
  @endphp


  <div class="mb-4">
    <h4 class="mb-1">اللوحة الاستراتيجية</h4>
    <div class="text-muted small">{{ now()->translatedFormat('l، d F Y') }}</div>
  </div>


  <div class="row g-3 mb-4">
    <div class="col-md-2 col-6">
      <div class="card text-center shadow-sm"><div class="card-body">
        <div class="small text-muted">الأهداف الاستراتيجية</div>
        <div class="fs-4 fw-bold">{{ $s['goals_count'] ?? 0 }}</div>
      </div></div>
    </div>
    <div class="col-md-2 col-6">
      <div class="card text-center shadow-sm"><div class="card-body">
        <div class="small text-muted">المبادرات</div>
        <div class="fs-4 fw-bold">{{ $s['initiatives_count'] ?? 0 }}</div>
      </div></div>
    </div>
    <div class="col-md-2 col-6">
      <div class="card text-center shadow-sm border-primary"><div class="card-body">
        <div class="small text-muted">الإنجاز الاستراتيجي</div>
        <div class="fs-4 fw-bold text-primary">{{ $s['strategic_achievement'] ?? 0 }}%</div>
      </div></div>
    </div>
    <div class="col-md-2 col-6">
      <div class="card text-center shadow-sm"><div class="card-body">
        <div class="small text-muted">مؤشرات الأداء</div>
        <div class="fs-4 fw-bold">{{ count($kpis) }}</div>
      </div></div>
    </div>
    <div class="col-md-2 col-6">
      <div class="card text-center shadow-sm border-warning"><div class="card-body">
        <div class="small text-muted">مبادرات متأخرة</div>
        <div class="fs-4 fw-bold text-warning">{{ $s['delayed_initiatives'] ?? 0 }}</div>
      </div></div>
    </div>
    <div class="col-md-2 col-6">
      <div class="card text-center shadow-sm border-danger"><div class="card-body">
        <div class="small text-muted">مخاطر حرجة</div>
        <div class="fs-4 fw-bold text-danger">{{ $s['critical_risks'] ?? 0 }}</div>
      </div></div>
    </div>
  </div>


  @if(!empty($insights))
    <div class="card border-info shadow-sm mb-4">
      <div class="card-header bg-info bg-opacity-10"><strong>AI Strategic Insights</strong></div>
      <div class="card-body">
        @foreach($insights as $ins)
          <div class="alert alert-{{ ($ins['type'] ?? 'info') == 'danger' ? 'danger' : 'warning' }} mb-2 small">
            {{ $ins['text'] ?? '' }}
          </div>
        @endforeach
      </div>
    </div>
  @endif


  <div class="card shadow-sm mb-4">
    <div class="card-header"><strong>التسلسل الاستراتيجي</strong></div>
    <div class="accordion accordion-flush" id="hierarchyAccordion">
      @foreach($hierarchy as $i => $p)
        <div class="accordion-item">
          <h2 class="accordion-header">
            <button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#pillar{{ $i }}">
              <strong>{{ $p['name'] ?? '—' }}</strong>
              <span class="badge bg-secondary ms-2">{{ count($p['goals'] ?? []) }} هدف</span>
            </button>
          </h2>
          <div id="pillar{{ $i }}" class="accordion-collapse collapse" data-bs-parent="#hierarchyAccordion">
            <div class="accordion-body">
              @forelse($p['goals'] ?? [] as $g)
                <div class="mb-3">
                  <div class="fw-bold text-primary">{{ $g['title'] ?? '—' }}</div>
                  <div class="small text-muted mb-2">الاستحقاق: {{ $g['target_date'] ?? '—' }}</div>
                  @foreach($g['programs'] ?? [] as $pr)
                    <div class="ms-3 mb-2">
                      <div class="small fw-bold">↳ {{ $pr['name'] ?? '—' }}</div>
                      @foreach($pr['initiatives'] ?? [] as $ini)
                        <div class="ms-3 d-flex justify-content-between align-items-center mb-1">
                          <span class="small">{{ $ini['name'] ?? '—' }}</span>
                          <div class="d-flex align-items-center" style="width:200px">
                            <div class="progress flex-grow-1 me-2" style="height:5px">
                              <div class="progress-bar" style="width: {{ $ini['progress'] ?? 0 }}%"></div>
                            </div>
                            <small class="text-muted">{{ $ini['progress'] ?? 0 }}%</small>
                          </div>
                        </div>
                      @endforeach
                    </div>
                  @endforeach
                </div>
              @empty
                <div class="text-muted small">لا توجد أهداف.</div>
              @endforelse
            </div>
          </div>
        </div>
      @endforeach
    </div>
  </div>


  <div class="card shadow-sm mb-4">
    <div class="card-header"><strong>أداء مؤشرات الأداء (KPIs)</strong></div>
    <div class="table-responsive">
      <table class="table table-sm mb-0">
        <thead class="table-light">
          <tr>
            <th>المؤشر</th>
            <th>الهدف</th>
            <th>القيمة الحالية</th>
            <th>نسبة التحقيق</th>
            <th>الاتجاه</th>
          </tr>
        </thead>
        <tbody>
        @forelse($kpis as $k)
          <tr>
            <td>{{ $k['name'] ?? '—' }}</td>
            <td>{{ $k['target'] ?? 0 }} {{ $k['unit'] ?? '' }}</td>
            <td>{{ $k['current'] ?? 0 }} {{ $k['unit'] ?? '' }}</td>
            <td style="min-width:150px">
              <div class="progress" style="height:6px">
                <div class="progress-bar" style="width: {{ min($k['achievement'] ?? 0, 100) }}%"></div>
              </div>
              <small>{{ $k['achievement'] ?? 0 }}%</small>
            </td>
            <td>
              @php $tr = $k['trend'] ?? 'flat'; @endphp
              <span class="badge bg-{{ $tr == 'up' ? 'success' : ($tr == 'down' ? 'danger' : 'secondary') }}">
                {{ $tr }}
              </span>
            </td>
          </tr>
        @empty
          <tr><td colspan="5" class="text-center text-muted py-4">لا توجد بيانات متاحة.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>


  @if(!empty($attention))
    <div class="card shadow-sm mb-4 border-warning">
      <div class="card-header bg-warning bg-opacity-10"><strong>مبادرات تحتاج انتباهًا</strong></div>
      <div class="table-responsive">
        <table class="table table-sm mb-0">
          <thead class="table-light">
            <tr>
              <th>المبادرة</th>
              <th>الهدف الاستراتيجي</th>
              <th>الإنجاز</th>
              <th>الاستحقاق المخطط</th>
              <th>التأخر</th>
            </tr>
          </thead>
          <tbody>
          @foreach($attention as $a)
            <tr>
              <td>{{ $a['name'] ?? '—' }}</td>
              <td class="small">{{ $a['goal'] ?? '—' }}</td>
              <td>{{ $a['progress'] ?? 0 }}%</td>
              <td>{{ $a['end_date'] ?? '—' }}</td>
              <td>
                @if(!empty($a['delay_days']))
                  <span class="badge bg-danger">{{ $a['delay_days'] }} يوم</span>
                @endif
              </td>
            </tr>
          @endforeach
          </tbody>
        </table>
      </div>
    </div>
  @endif


  <div class="card shadow-sm mb-4">
    <div class="card-header"><strong>أداء الإدارات</strong></div>
    <div class="table-responsive">
      <table class="table table-sm mb-0">
        <thead class="table-light">
          <tr>
            <th>الإدارة</th>
            <th>المهام</th>
            <th>مكتملة</th>
            <th>متأخرة</th>
            <th>نسبة الإنجاز</th>
            <th>مخاطر عالية</th>
          </tr>
        </thead>
        <tbody>
        @forelse($departments as $d)
          <tr>
            <td>{{ $d['name'] ?? '—' }}</td>
            <td>{{ $d['total_tasks'] ?? 0 }}</td>
            <td class="text-success">{{ $d['completed'] ?? 0 }}</td>
            <td class="text-danger">{{ $d['delayed'] ?? 0 }}</td>
            <td>{{ $d['completion_rate'] ?? 0 }}%</td>
            <td><span class="badge bg-danger">{{ $d['high_risks'] ?? 0 }}</span></td>
          </tr>
        @empty
          <tr><td colspan="6" class="text-center text-muted py-4">لا توجد بيانات متاحة.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>


  <div class="card shadow-sm">
    <div class="card-header"><strong>المخاطر الاستراتيجية</strong></div>
    <div class="table-responsive">
      <table class="table table-sm mb-0">
        <thead class="table-light">
          <tr>
            <th>الخطر</th>
            <th>المبادرة</th>
            <th>المهمة</th>
            <th>Probability</th>
            <th>Impact</th>
            <th>Score</th>
          </tr>
        </thead>
        <tbody>
        @forelse($stRisks as $r)
          <tr>
            <td>{{ $r['name'] ?? '—' }}</td>
            <td class="small">{{ $r['initiative'] ?? '—' }}</td>
            <td class="small text-muted">{{ $r['task'] ?? '—' }}</td>
            <td>{{ $r['probability'] ?? 0 }}/10</td>
            <td>{{ $r['impact'] ?? 0 }}/10</td>
            <td>
              @php $sc = $r['score'] ?? 0; @endphp
              <span class="badge bg-{{ $sc >= 80 ? 'danger' : ($sc >= 60 ? 'warning' : 'secondary') }}">
                {{ $sc }}
              </span>
            </td>
          </tr>
        @empty
          <tr><td colspan="6" class="text-center text-muted py-4">لا توجد بيانات متاحة.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>


</div>
@endsection

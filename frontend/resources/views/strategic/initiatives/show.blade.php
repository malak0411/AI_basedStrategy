@extends('layouts.app')


@section('title', 'تفاصيل المبادرة')


@push('styles')
<style>
    .task-card {
        background: #fff; border-radius: 12px; padding: 16px; margin-bottom: 12px;
        border: 1px solid #e2e8f0; transition: all 0.2s;
    }
    .task-card:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.06); }


    .dept-badge {
        display: inline-block; padding: 3px 10px; border-radius: 14px;
        font-size: 11px; margin: 2px; font-weight: 600;
    }
    .dept-lead { background: #d4af37; color: #1a1a2e; }
    .dept-support { background: #e2e8f0; color: #4a5568; }


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
    .strategic-path .node.program{ border-color: #3182ce; color: #3182ce; }


    .progress-bar-custom { height: 6px; background: #edf2f7; border-radius: 3px; overflow: hidden; }
    .progress-bar-fill { height: 100%; background: linear-gradient(90deg, #38a169, #48bb78); }


    .progress-circle {
        width: 100px; height: 100px; border-radius: 50%;
        background: conic-gradient(#48bb78 var(--p), #edf2f7 0);
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto;
    }
    .progress-circle-inner {
        width: 78px; height: 78px; border-radius: 50%; background: #fff;
        display: flex; align-items: center; justify-content: center;
        font-size: 20px; font-weight: 700; color: #2f855a;
    }
</style>
@endpush


@section('content')
<div class="container-fluid px-4">


    <a href="{{ route('strategic.initiatives.index') }}" class="btn btn-outline-secondary mb-3">
        <i class="fas fa-arrow-right"></i> العودة للمبادرات
    </a>


    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif


    @php
        $statusId    = $initiative['status_id'] ?? 1;
        $statusName  = $initiative['status_name'] ?? 'غير محدد';
        $statusColor = $initiative['status_color'] ?? '#6c757d';
        $progress    = (int) ($initiative['progress'] ?? 0);
        $userRole    = $userRole ?? session('user_role', 'employee');
        $canApprove  = ($userRole === 'minister') && !in_array($statusId, [17, 4]);
        $canAddTasks = in_array($statusId, [17, 2]);
    @endphp


    <div class="card-custom mb-4">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div class="flex-grow-1">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <h4 class="mb-0">{{ $initiative['name'] ?? $initiative['title'] ?? '' }}</h4>
                    <span class="status-badge"
                          style="background: {{ $statusColor }}22; color: {{ $statusColor }}; border: 1px solid {{ $statusColor }}55;">
                        {{ $statusName }}
                    </span>
                </div>
                <p class="text-muted mb-0">{{ $initiative['description'] ?? '' }}</p>
            </div>


            <div class="d-flex gap-2 flex-wrap">


                @if($statusId === 1 && $userRole === 'minister')
                    <form action="{{ route('strategic.initiatives.change-status', $initiative['id']) }}" method="POST" class="d-inline"
                          onsubmit="return confirm('اعتماد المبادرة؟')">
                        @csrf
                        <input type="hidden" name="status_id" value="17">
                        <button type="submit" class="btn-gold">
                            <i class="fas fa-check-circle"></i> اعتماد
                        </button>
                    </form>
                @endif


                @if($statusId === 17 && $userRole === 'manager')
                    <form action="{{ route('strategic.initiatives.change-status', $initiative['id']) }}" method="POST" class="d-inline"
                          onsubmit="return confirm('بدء تنفيذ المبادرة؟')">
                        @csrf
                        <input type="hidden" name="status_id" value="2">
                        <button type="submit" class="btn btn-info">
                            <i class="fas fa-play"></i> بدء التنفيذ
                        </button>
                    </form>
                @endif


                @if($statusId === 2 && $userRole === 'manager')
                    <form action="{{ route('strategic.initiatives.change-status', $initiative['id']) }}" method="POST" class="d-inline"
                          onsubmit="return confirm('تعليم المبادرة كمكتملة؟')">
                        @csrf
                        <input type="hidden" name="status_id" value="3">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-flag-checkered"></i> إكمال
                        </button>
                    </form>
                @endif


                @if(!in_array($statusId, [4, 3]) && $userRole === 'minister')
                    <form action="{{ route('strategic.initiatives.change-status', $initiative['id']) }}" method="POST" class="d-inline"
                          onsubmit="return confirm('هل أنت متأكد من إلغاء المبادرة؟')">
                        @csrf
                        <input type="hidden" name="status_id" value="4">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-times-circle"></i> إلغاء
                        </button>
                    </form>
                @endif


                <a href="{{ route('strategic.initiatives.edit', $initiative['id']) }}"
                   class="btn btn-outline-warning">
                    <i class="fas fa-edit"></i> تعديل
                </a>


            </div>
        </div>


        @if(in_array($statusId, [17, 2, 3]))
            <div class="alert alert-warning mt-3 mb-0 py-2">
                <small>
                    <i class="fas fa-exclamation-triangle"></i>
                    أي تعديل على المبادرة سيعيد حالتها إلى <strong>"معلق"</strong> وتحتاج لإعادة اعتماد الوزير.
                </small>
            </div>
        @endif
    </div>


    <div class="row">
        <div class="col-lg-8">


            <div class="card-custom mb-4">
                <h6 class="mb-3"><i class="fas fa-sitemap text-primary ml-2"></i>الخريطة الاستراتيجية</h6>
                <div class="strategic-path">
                    <span class="node pillar">
                        <i class="fas fa-layer-group"></i> {{ $initiative['pillar_name'] ?? '—' }}
                    </span>
                    <i class="fas fa-arrow-left text-muted"></i>
                    <span class="node goal">
                        <i class="fas fa-bullseye"></i> {{ $initiative['goal_name'] ?? '—' }}
                    </span>
                    <i class="fas fa-arrow-left text-muted"></i>
                    <span class="node program">
                        <i class="fas fa-project-diagram"></i> {{ $initiative['program_name'] ?? '—' }}
                    </span>
                </div>
                <div class="row mt-3">
                    <div class="col-md-3">
                        <small class="text-muted d-block">الأولوية</small>
                        <strong>{{ $initiative['priority_id'] ?? '—' }}</strong>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block">تاريخ البداية</small>
                        <strong>{{ $initiative['start_date'] ?? '—' }}</strong>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block">تاريخ النهاية</small>
                        <strong>{{ $initiative['end_date'] ?? '—' }}</strong>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block">الميزانية</small>
                        <strong>{{ number_format($initiative['budget_estimate'] ?? 0) }}</strong>
                    </div>
                </div>
            </div>


            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">
                    <i class="fas fa-tasks ml-2"></i>
                    المهام الرئيسية ({{ count($majorTasks) }})
                </h5>
                <div class="d-flex gap-2">
                    @if($canAddTasks)
                        <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addTaskModal">
                            <i class="fas fa-plus"></i> إضافة مهمة يدوياً
                        </button>
                        <a href="{{ route('ai.strategic.index') }}?initiative_id={{ $initiative['id'] }}" class="btn-gold btn-sm">
                            <i class="fas fa-robot"></i> توليد بالذكاء الاصطناعي
                        </a>
                    @else
                        <span class="badge bg-secondary py-2 px-3">
                            <i class="fas fa-lock"></i>
                            يجب اعتماد المبادرة أولاً لإنشاء المهام
                        </span>
                    @endif
                </div>
            </div>


            @if(empty($majorTasks))
                <div class="empty-state card-custom">
                    <i class="fas fa-tasks"></i>
                    @if($statusId === 17 || $statusId === 2)
                        <h5>لا توجد مهام رئيسية لهذه المبادرة</h5>
                        <p class="text-muted">يمكنك إضافة المهام يدوياً أو استخدام الذكاء الاصطناعي</p>
                    @else
                        <h5>🔒 لا يمكن إنشاء مهام رئيسية قبل اعتماد المبادرة</h5>
                        <p class="text-muted">يرجى اعتماد المبادرة من قِبل الوزير أولاً</p>
                    @endif
                </div>
            @else
                @foreach($majorTasks as $task)
                @php
                    $responsibleDepts = $task['responsible_departments'] ?? [];
                    $supportingDepts  = $task['supporting_departments'] ?? [];
                    $isShared         = $task['is_cross_department'] ?? false;
                    $taskProgress     = (int) ($task['progress'] ?? 0);
                @endphp


                <div class="task-card" id="task-{{ $task['id'] }}">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <h6 class="mb-0">{{ $task['name'] ?? '' }}</h6>
                                @if($isShared)
                                    <span class="badge bg-info">مشتركة</span>
                                @else
                                    <span class="badge bg-secondary">غير مشتركة</span>
                                @endif
                            </div>
                            <p class="text-muted small mb-2">{{ Str::limit($task['description'] ?? '', 150) }}</p>


                            @if(count($responsibleDepts) > 0)
                                <div class="mb-1">
                                    <small class="text-muted">الإدارة المسؤولة:</small>
                                    @foreach($responsibleDepts as $d)
                                        <span class="dept-badge dept-lead">
                                            👑 {{ $d['department_name'] ?? '' }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif


                            @if(count($supportingDepts) > 0)
                                <div class="mb-1">
                                    <small class="text-muted">الإدارات المساندة:</small>
                                    @foreach($supportingDepts as $d)
                                        <span class="dept-badge dept-support">
                                            🤝 {{ $d['department_name'] ?? '' }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif


                            <div class="d-flex gap-3 flex-wrap mt-2">
                                <small class="text-muted">
                                    <i class="far fa-clock ml-1"></i>
                                    {{ $task['estimated_duration_days'] ?? 0 }} يوم
                                </small>
                                <small class="text-muted">
                                    <i class="fas fa-cog ml-1"></i>
                                    مهام تشغيلية: {{ $task['operational_completed'] ?? 0 }}/{{ $task['operational_count'] ?? 0 }}
                                </small>
                                <small class="text-success">
                                    <i class="fas fa-chart-line ml-1"></i>
                                    التقدم: {{ $taskProgress }}%
                                </small>
                            </div>


                            <div class="progress-bar-custom mt-2">
                                <div class="progress-bar-fill" style="width: {{ $taskProgress }}%"></div>
                            </div>
                        </div>


                        <div class="d-flex gap-1 ms-2">
                            <button class="btn btn-sm btn-outline-primary"
                                    onclick="editTask({{ json_encode($task) }})" title="تعديل">
                                <i class="fas fa-edit"></i>
                            </button>
                        </div>
                    </div>
                </div>
                @endforeach
            @endif


        </div>


        <div class="col-lg-4">


            <div class="card-custom mb-4 text-center">
                <h6 class="mb-3">نسبة التقدم الكلية</h6>
                <div class="progress-circle" style="--p: {{ $progress }}%">
                    <div class="progress-circle-inner">{{ $progress }}%</div>
                </div>
                <small class="text-muted d-block mt-2">
                    محسوبة من المهام التشغيلية التابعة
                </small>
            </div>


            <div class="card-custom mb-4">
                <h6 class="mb-3"><i class="fas fa-info-circle text-primary ml-1"></i> معلومات سريعة</h6>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">عدد المهام الرئيسية</span>
                    <strong>{{ count($majorTasks) }}</strong>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">إجمالي المهام التشغيلية</span>
                    <strong>{{ $initiative['total_operational_tasks'] ?? 0 }}</strong>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">الميزانية</span>
                    <strong>{{ number_format($initiative['budget_estimate'] ?? 0) }}</strong>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">الحالة</span>
                    <span class="status-badge"
                          style="background: {{ $statusColor }}22; color: {{ $statusColor }}; border: 1px solid {{ $statusColor }}55;">
                        {{ $statusName }}
                    </span>
                </div>
                <div class="d-flex justify-content-between py-2">
                    <span class="text-muted">الأولوية</span>
                    <strong>{{ $initiative['priority_id'] ?? '—' }}</strong>
                </div>
            </div>


            <div class="card-custom mb-4">
                <h6 class="mb-3"><i class="fas fa-coins text-warning ml-1"></i> سجل الميزانية</h6>
                @if(!empty($initiative['budget_history']))
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>التاريخ</th>
                                    <th>المبلغ</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($initiative['budget_history'] as $tx)
                                <tr>
                                    <td><small>{{ $tx['date'] ?? '—' }}</small></td>
                                    <td><small>{{ number_format($tx['amount'] ?? 0) }}</small></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted text-center small mb-0 py-3">لا يوجد سجل ميزانية بعد</p>
                @endif
            </div>


        </div>
    </div>


    @if($canAddTasks)
    <div class="modal fade" id="addTaskModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="mb-0">إضافة مهمة رئيسية</h5>
                    <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('strategic.initiatives.major-tasks.store', $initiative['id']) }}">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">اسم المهمة <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">الوصف</label>
                            <textarea name="description" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">المدة (أيام)</label>
                                <input type="number" name="estimated_duration_days" class="form-control" value="60">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">الأولوية</label>
                                <select name="priority_id" class="form-control">
                                    @foreach($priorities as $p)
                                        <option value="{{ $p['priority_id'] ?? $p['id'] ?? '' }}">{{ $p['name_ar'] ?? $p['name'] ?? '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>


                        <hr>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label mb-0">
                                الإدارات المشاركة <span class="text-danger">*</span>
                            </label>
                            <small class="text-muted">يجب تحديد إدارة مسؤولة واحدة على الأقل</small>
                        </div>
                        <div id="deptContainer">
                            <div class="row mb-2 dept-row">
                                <div class="col-5">
                                    <select name="departments[0][department_id]" class="form-control">
                                        <option value="">اختر الإدارة</option>
                                        @foreach($departments as $d)
                                            <option value="{{ $d['department_id'] ?? $d['id'] }}">{{ $d['name'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-3">
                                    <select name="departments[0][responsibility_type]" class="form-control">
                                        <option value="LEAD">مسؤولة</option>
                                        <option value="SUPPORT">داعمة</option>
                                    </select>
                                </div>
                                <div class="col-3">
                                    <input type="text" name="departments[0][notes]" class="form-control" placeholder="ملاحظة">
                                </div>
                                <div class="col-1">
                                    <button type="button" class="btn btn-sm btn-danger" onclick="this.closest('.dept-row').remove()">×</button>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary mt-1" onclick="addDeptRow()">
                            + إضافة إدارة
                        </button>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> حفظ المهمة
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif


</div>
@endsection


@push('scripts')
<script>
let deptCount = 1;
function addDeptRow() {
    const container = document.getElementById('deptContainer');
    const row = document.createElement('div');
    row.className = 'row mb-2 dept-row';
    row.innerHTML = `
        <div class="col-5">
            <select name="departments[${deptCount}][department_id]" class="form-control">
                <option value="">اختر الإدارة</option>
                @foreach($departments as $d)
                    <option value="{{ $d['department_id'] ?? $d['id'] }}">{{ $d['name'] }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-3">
            <select name="departments[${deptCount}][responsibility_type]" class="form-control">
                <option value="LEAD">مسؤولة</option>
                <option value="SUPPORT">داعمة</option>
            </select>
        </div>
        <div class="col-3">
            <input type="text" name="departments[${deptCount}][notes]" class="form-control" placeholder="ملاحظة">
        </div>
        <div class="col-1">
            <button type="button" class="btn btn-sm btn-danger" onclick="this.closest('.dept-row').remove()">×</button>
        </div>
    `;
    container.appendChild(row);
    deptCount++;
}


function editTask(task) {
    console.log('Edit task:', task);
}


document.querySelector('form[action*="major-tasks"]')?.addEventListener('submit', function(e) {
    const types = this.querySelectorAll('select[name*="[responsibility_type]"]');
    let hasLead = false;
    types.forEach(sel => {
        if (sel.value === 'LEAD' && sel.closest('.dept-row').querySelector('select[name*="[department_id]"]').value) {
            hasLead = true;
        }
    });
    if (!hasLead) {
        e.preventDefault();
        alert('⚠️ يجب تحديد إدارة مسؤولة واحدة على الأقل');
    }
});
</script>
@endpush

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
    .strategic-path .node.initiative{ border-color: #d4af37; color: #b8932a; background: #fdf8e7; font-weight: 600; }


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


    .task-status-bar {
        height: 6px; background: #edf2f7; border-radius: 10px; overflow: hidden;
        margin-top: 8px;
    }
    .task-status-bar .fill {
        height: 100%; border-radius: 10px;
        transition: width 0.6s ease-in-out;
    }


    .section-title {
        font-size: 13px;
        font-weight: 700;
        color: #4a5568;
        padding-bottom: 4px;
        border-bottom: 2px solid #edf2f7;
    }
    .dept-row {
        padding: 10px;
        border-radius: 10px;
        border: 1px solid #e2e8f0;
        background: #fff;
        transition: all 0.2s;
    }
    .dept-lead-row {
        border-right: 4px solid #d4af37;
        background: linear-gradient(90deg, #fdf8e7 0%, #fff 40%);
    }
    .dept-support-row {
        border-right: 4px solid #a0aec0;
    }
    .dept-lead-icon {
        background: #d4af37; color: #fff; border: none;
    }
    .dept-support-icon {
        background: #718096; color: #fff; border: none;
    }
    .shared-indicator .badge { padding: 6px 12px; font-size: 12px; }
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
        $statusId    = $initiative['status_id'] ?? 1;
        $statusName  = $initiative['status_name'] ?? 'غير محدد';
        $statusColor = $initiative['status_color'] ?? '#6c757d';
        $progress    = (int)($initiative['progress'] ?? 0);
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
                    <i class="fas fa-arrow-left text-muted"></i>
                    <span class="node initiative">
                        <i class="fas fa-lightbulb"></i> {{ $initiative['name'] ?? '—' }}
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
                        <a href="{{ route('strategic.generate', $initiative['id']) }}" class="btn-gold">
                            <i class="fas fa-robot"></i> توليد المهام الرئيسية بالذكاء الاصطناعي
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
                    $taskProgress     = (int)($task['progress'] ?? 0);
                    $taskBarColor     = progressColor($taskProgress);
                    $taskId           = $task['id'] ?? $task['major_task_id'] ?? null;
                    $initiativeId     = $initiative['id'] ?? $initiative['initiative_id'] ?? null;
                @endphp


                <div class="task-card" id="task-{{ $taskId }}">
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


                            <div class="d-flex gap-3 flex-wrap mt-2 mb-2">
                                <small class="text-muted">
                                    <i class="far fa-clock ml-1"></i>
                                    {{ $task['estimated_duration_days'] ?? 0 }} يوم
                                </small>
                                <small class="text-muted">
                                    <i class="fas fa-cog ml-1"></i>
                                    مهام تشغيلية: {{ $task['operational_completed'] ?? 0 }}/{{ $task['operational_count'] ?? 0 }}
                                </small>
                            </div>


                            <div class="d-flex justify-content-between align-items-center small mb-1">
                                <span class="text-muted">نسبة التقدم</span>
                                <strong style="color: {{ $taskBarColor }};">
                                    {{ $taskProgress }}%
                                    <small class="text-muted">({{ progressLabel($taskProgress) }})</small>
                                </strong>
                            </div>
                            <div class="progress-bar-custom">
                                <div class="progress-bar-fill" style="width: {{ $taskProgress }}%; background: {{ $taskBarColor }};"></div>
                            </div>
                        </div>


                        <div class="d-flex gap-1 ms-2">
                            @if($taskId && $initiativeId)
                                <button class="btn btn-sm btn-outline-primary"
                                        data-task="{{ json_encode($task, JSON_HEX_APOS | JSON_HEX_QUOT) }}"
                                        data-update-url="{{ url('/strategic/initiatives/'.$initiativeId.'/major-tasks/'.$taskId) }}"
                                        onclick="editTask(this)" title="تعديل">
                                    <i class="fas fa-edit"></i>
                                </button>
                            @else
                                <button class="btn btn-sm btn-outline-secondary" disabled title="معرف المهمة غير متوفر">
                                    <i class="fas fa-edit"></i>
                                </button>
                            @endif
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
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header text-white" style="background: linear-gradient(135deg,#d4af37,#b8932a);">
                    <div>
                        <h5 class="mb-0"><i class="fas fa-plus-circle ml-1"></i> إضافة مهمة رئيسية</h5>
                        <small class="opacity-75">املأ الحقول الأساسية وحدد الإدارات المشاركة</small>
                    </div>
                    <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>


                <form method="POST"
                      action="{{ route('strategic.initiatives.major-tasks.store', $initiative['id']) }}"
                      id="addTaskForm" class="task-form" data-context="add">
                    @csrf
                    <div class="modal-body">


                        <div class="section-title mb-2">
                            <i class="fas fa-info-circle"></i> المعلومات الأساسية
                        </div>
                        <div class="mb-3">
                            <label class="form-label">اسم المهمة <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required
                                   placeholder="مثال: تطوير خطة التحول الرقمي">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">الوصف</label>
                            <textarea name="description" class="form-control" rows="2"
                                      placeholder="وصف موجز للمهمة..."></textarea>
                        </div>


                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">المدة (أيام) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="far fa-clock"></i></span>
                                    <input type="number" name="estimated_duration_days" class="form-control"
                                           value="60" min="1" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">الأولوية <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-flag"></i></span>
                                    <select name="priority_id" class="form-control" required>
                                        @foreach($priorities as $p)
                                            <option value="{{ $p['priority_id'] ?? $p['id'] ?? '' }}">
                                                {{ $p['name_ar'] ?? $p['name'] ?? '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>


                        <hr>


                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="section-title mb-0">
                                <i class="fas fa-sitemap"></i> الإدارات المشاركة
                            </div>
                            <div class="shared-indicator" data-context="add">
                                <span class="badge bg-secondary">
                                    <i class="fas fa-users"></i> مشتركة: <strong class="shared-value">لا</strong>
                                </span>
                            </div>
                        </div>


                        <div class="alert alert-info py-2 mb-2">
                            <small>
                                <i class="fas fa-info-circle"></i>
                                <strong>ملاحظة:</strong> يجب تحديد <strong>إدارة مسؤولة واحدة</strong> إجبارياً.
                                باقي الإدارات تُعتبر <strong>مساندة</strong>. عند إضافة أكثر من إدارة تصبح المهمة <strong>مشتركة</strong> تلقائياً.
                            </small>
                        </div>


                        <div id="addDeptContainer">
                            <div class="dept-row dept-lead-row mb-2" data-type="LEAD">
                                <div class="row g-2 align-items-center">
                                    <div class="col-md-5">
                                        <div class="input-group">
                                            <span class="input-group-text dept-lead-icon">
                                                <i class="fas fa-crown"></i>
                                            </span>
                                            <select name="departments[0][department_id]" class="form-control" required>
                                                <option value="">اختر الإدارة المسؤولة *</option>
                                                @foreach($departments as $d)
                                                    <option value="{{ $d['department_id'] ?? $d['id'] }}">{{ $d['name'] }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <input type="hidden" name="departments[0][responsibility_type]" value="LEAD">
                                        <small class="text-warning fw-bold">
                                            <i class="fas fa-crown"></i> إدارة مسؤولة (إجبارية)
                                        </small>
                                    </div>
                                    <div class="col-md-5">
                                        <input type="text" name="departments[0][notes]" class="form-control"
                                               placeholder="ملاحظة (اختياري)">
                                    </div>
                                    <div class="col-md-2 text-center">
                                        <span class="badge bg-warning text-dark">مسؤولة</span>
                                    </div>
                                </div>
                            </div>
                        </div>


                        <button type="button" class="btn btn-sm btn-outline-primary mt-1"
                                onclick="addDeptRow('add')">
                            <i class="fas fa-plus"></i> إضافة إدارة مساندة
                        </button>
                    </div>


                    <div class="modal-footer bg-light">
                        <button type="submit" class="btn-gold">
                            <i class="fas fa-save"></i> حفظ المهمة
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <div class="modal fade" id="editTaskModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header text-white" style="background: linear-gradient(135deg,#3182ce,#2b6cb0);">
                    <div>
                        <h5 class="mb-0"><i class="fas fa-edit ml-1"></i> تعديل المهمة الرئيسية</h5>
                        <small class="opacity-75">عدّل البيانات والإدارات المشاركة</small>
                    </div>
                    <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>


                <form method="POST" id="editTaskForm" action="" class="task-form" data-context="edit">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">


                        <div class="section-title mb-2">
                            <i class="fas fa-info-circle"></i> المعلومات الأساسية
                        </div>
                        <div class="mb-3">
                            <label class="form-label">اسم المهمة <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">الوصف</label>
                            <textarea name="description" class="form-control" rows="2"></textarea>
                        </div>


                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">المدة (أيام) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="far fa-clock"></i></span>
                                    <input type="number" name="estimated_duration_days" class="form-control"
                                           min="1" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">الأولوية <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-flag"></i></span>
                                    <select name="priority_id" class="form-control" required>
                                        @foreach($priorities as $p)
                                            <option value="{{ $p['priority_id'] ?? $p['id'] ?? '' }}">
                                                {{ $p['name_ar'] ?? $p['name'] ?? '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>


                        <hr>


                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="section-title mb-0">
                                <i class="fas fa-sitemap"></i> الإدارات المشاركة
                            </div>
                            <div class="shared-indicator" data-context="edit">
                                <span class="badge bg-secondary">
                                    <i class="fas fa-users"></i> مشتركة: <strong class="shared-value">لا</strong>
                                </span>
                            </div>
                        </div>


                        <div class="alert alert-info py-2 mb-2">
                            <small>
                                <i class="fas fa-info-circle"></i>
                                يجب إبقاء <strong>إدارة مسؤولة واحدة</strong> فقط، والباقي مساندة.
                            </small>
                        </div>


                        <div id="editDeptContainer"></div>


                        <button type="button" class="btn btn-sm btn-outline-primary mt-1"
                                onclick="addDeptRow('edit')">
                            <i class="fas fa-plus"></i> إضافة إدارة مساندة
                        </button>
                    </div>


                    <div class="modal-footer bg-light">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> حفظ التعديلات
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
const DEPARTMENTS = @json($departments);


function addDeptRow(context) {
    const containerId = context === 'edit' ? 'editDeptContainer' : 'addDeptContainer';
    const container = document.getElementById(containerId);
    const idx = Date.now() + Math.floor(Math.random() * 1000);


    const options = DEPARTMENTS.map(d => {
        const id = d.department_id ?? d.id;
        return `<option value="${id}">${d.name}</option>`;
    }).join('');


    const row = document.createElement('div');
    row.className = 'dept-row dept-support-row mb-2';
    row.dataset.type = 'SUPPORT';
    row.innerHTML = `
        <div class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text dept-support-icon">
                        <i class="fas fa-hands-helping"></i>
                    </span>
                    <select name="departments[${idx}][department_id]" class="form-control">
                        <option value="">اختر إدارة مساندة</option>
                        ${options}
                    </select>
                </div>
                <input type="hidden" name="departments[${idx}][responsibility_type]" value="SUPPORT">
                <small class="text-muted"><i class="fas fa-hands-helping"></i> إدارة مساندة</small>
            </div>
            <div class="col-md-5">
                <input type="text" name="departments[${idx}][notes]" class="form-control"
                       placeholder="ملاحظة (اختياري)">
            </div>
            <div class="col-md-2 text-center">
                <button type="button" class="btn btn-sm btn-outline-danger"
                        onclick="removeDeptRow(this)">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `;
    container.appendChild(row);
    refreshSharedBadge(context);
}


function removeDeptRow(btn) {
    const row = btn.closest('.dept-row');
    const context = btn.closest('.task-form')?.dataset.context || 'add';
    row.remove();
    refreshSharedBadge(context);
}


function refreshSharedBadge(context) {
    const containerId = context === 'edit' ? 'editDeptContainer' : 'addDeptContainer';
    const container = document.getElementById(containerId);
    if (!container) return;
    const rows = container.querySelectorAll('.dept-row');
    let selectedCount = 0;
    rows.forEach(r => {
        const sel = r.querySelector('select[name*="[department_id]"]');
        if (sel && sel.value) selectedCount++;
    });
    const badge = document.querySelector(`.shared-indicator[data-context="${context}"]`);
    if (!badge) return;
    const badgeEl = badge.querySelector('.badge');
    const valueEl = badge.querySelector('.shared-value');
    if (selectedCount > 1) {
        valueEl.textContent = 'نعم';
        badgeEl.className = 'badge bg-info';
    } else {
        valueEl.textContent = 'لا';
        badgeEl.className = 'badge bg-secondary';
    }
}


function editTask(btn) {
    const task = JSON.parse(btn.dataset.task);
    const updateUrl = btn.dataset.updateUrl;
    const form = document.getElementById('editTaskForm');
    form.action = updateUrl;


    form.querySelector('[name="name"]').value = task.name || '';
    form.querySelector('[name="description"]').value = task.description || '';
    form.querySelector('[name="estimated_duration_days"]').value = task.estimated_duration_days || 60;
    form.querySelector('[name="priority_id"]').value = task.priority_id || 2;


    const container = document.getElementById('editDeptContainer');
    container.innerHTML = '';


    const options = DEPARTMENTS.map(d => {
        const id = d.department_id ?? d.id;
        return `<option value="${id}">${d.name}</option>`;
    }).join('');


    const responsible = task.responsible_departments || [];
    const supporting   = task.supporting_departments  || [];


    responsible.forEach((d, i) => {
        const idx = 'r' + i;
        const deptId = d.department_id ?? d.id ?? '';
        const row = document.createElement('div');
        row.className = 'dept-row dept-lead-row mb-2';
        row.dataset.type = 'LEAD';
        row.innerHTML = `
            <div class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text dept-lead-icon"><i class="fas fa-crown"></i></span>
                        <select name="departments[${idx}][department_id]" class="form-control" required>
                            <option value="">اختر الإدارة المسؤولة *</option>
                            ${options}
                        </select>
                    </div>
                    <input type="hidden" name="departments[${idx}][responsibility_type]" value="LEAD">
                    <small class="text-warning fw-bold"><i class="fas fa-crown"></i> إدارة مسؤولة (إجبارية)</small>
                </div>
                <div class="col-md-5">
                    <input type="text" name="departments[${idx}][notes]" class="form-control"
                           value="${(d.notes || '').replace(/"/g,'&quot;')}"
                           placeholder="ملاحظة (اختياري)">
                </div>
                <div class="col-md-2 text-center">
                    <span class="badge bg-warning text-dark">مسؤولة</span>
                </div>
            </div>
        `;
        container.appendChild(row);
        row.querySelector('select').value = deptId;
    });


    supporting.forEach((d, i) => {
        const idx = 's' + i;
        const deptId = d.department_id ?? d.id ?? '';
        const row = document.createElement('div');
        row.className = 'dept-row dept-support-row mb-2';
        row.dataset.type = 'SUPPORT';
        row.innerHTML = `
            <div class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text dept-support-icon"><i class="fas fa-hands-helping"></i></span>
                        <select name="departments[${idx}][department_id]" class="form-control">
                            <option value="">اختر إدارة مساندة</option>
                            ${options}
                        </select>
                    </div>
                    <input type="hidden" name="departments[${idx}][responsibility_type]" value="SUPPORT">
                </div>
                <div class="col-md-5">
                    <input type="text" name="departments[${idx}][notes]" class="form-control"
                           value="${(d.notes || '').replace(/"/g,'&quot;')}"
                           placeholder="ملاحظة (اختياري)">
                </div>
                <div class="col-md-2 text-center">
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeDeptRow(this)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
        container.appendChild(row);
        row.querySelector('select').value = deptId;
    });


    refreshSharedBadge('edit');
    new bootstrap.Modal(document.getElementById('editTaskModal')).show();
}


document.addEventListener('change', function(e) {
    if (e.target.matches('select[name*="[department_id]"]')) {
        const form = e.target.closest('.task-form');
        if (form) refreshSharedBadge(form.dataset.context);
    }
});


document.querySelectorAll('.task-form').forEach(form => {
    form.addEventListener('submit', function(e) {
        const leadRows = this.querySelectorAll('.dept-lead-row');
        let leadOk = false;
        leadRows.forEach(r => {
            const sel = r.querySelector('select[name*="[department_id]"]');
            if (sel && sel.value) leadOk = true;
        });
        if (!leadOk) {
            e.preventDefault();
            alert('⚠️ يجب تحديد الإدارة المسؤولة قبل الحفظ');
        }
    });
});
</script>
@endpush

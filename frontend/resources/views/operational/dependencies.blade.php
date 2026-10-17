@extends('layouts.app')


@section('title', 'إدارة تبعيات المهام التشغيلية')


@push('styles')
<style>
    .view-toggle { display: flex; gap: 10px; margin-bottom: 16px; flex-wrap: wrap; }
    .view-toggle .btn { border-radius: 20px; padding: 6px 20px; font-weight: 600; }
    .view-toggle .btn.active { background: #d4af37; color: #1a1a2e; border-color: #d4af37; }
    .task-dependency-item { background: #fff; border-radius: 12px; padding: 16px; margin-bottom: 12px; border: 1px solid #e2e8f0; transition: all 0.2s; }
    .task-dependency-item:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
    .task-dependency-item .task-title { font-weight: 600; font-size: 15px; margin-bottom: 4px; }
    .task-dependency-item .task-meta { font-size: 12px; color: #718096; display: flex; gap: 16px; flex-wrap: wrap; }
    .dependency-list { margin-top: 8px; padding-top: 8px; border-top: 1px dashed #e2e8f0; }
    .dependency-item { display: flex; align-items: center; gap: 8px; padding: 6px 12px; font-size: 13px; flex-wrap: wrap; background: #f8f9fa; border-radius: 6px; margin-bottom: 4px; }
    .dependency-item .dep-type { background: #e2e8f0; padding: 2px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; color: #4a5568; }
    .dependency-item .dep-arrow { color: #d4af37; font-weight: 700; margin: 0 4px; }
    .dependency-item .btn { padding: 2px 8px; font-size: 12px; line-height: 1.5; }
    .empty-state { text-align: center; padding: 60px 20px; }
    .empty-state i { font-size: 64px; color: #cbd5e0; margin-bottom: 16px; }
    .flow-container { background: #fafbfc; border-radius: 12px; padding: 20px; border: 1px solid #e2e8f0; min-height: 500px; overflow: auto; }
    .flow-container .empty-flow { text-align: center; padding: 60px 20px; }
    .flow-container .empty-flow i { font-size: 64px; color: #cbd5e0; margin-bottom: 16px; }
    .task-status-badge { padding: 2px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; }
    .task-status-badge.status-16 { background: #e2e8f0; color: #4a5568; }
    .task-status-badge.status-26 { background: #bee3f8; color: #2a69ac; }
    .task-status-badge.status-6 { background: #81e6d9; color: #234e52; }
    .task-status-badge.status-5 { background: #fbd38d; color: #7b341e; }
    .task-status-badge.status-8 { background: #d6bcfa; color: #553c9a; }
    .task-status-badge.status-19 { background: #9ae6b4; color: #22543d; }
    .task-status-badge.status-20 { background: #feb2b2; color: #742a2a; }
    .btn-gold { background: #d4af37; color: #1a1a2e; border: none; padding: 6px 16px; border-radius: 8px; font-weight: 600; transition: all 0.3s; font-size: 14px; }
    .btn-gold:hover { background: #c5a234; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(212, 175, 55, 0.3); color: #1a1a2e; }
    .btn-gold i { margin-left: 6px; }
    .btn-kanban { background: #9f7aea; color: #fff; border: none; padding: 6px 16px; border-radius: 8px; font-weight: 600; transition: all 0.3s; font-size: 14px; }
    .btn-kanban:hover { background: #805ad5; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(159, 122, 234, 0.3); color: #fff; }
    .btn-kanban i { margin-left: 6px; }
    .cpm-card { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 20px; height: 100%; }
    .cpm-card .legend-line { display: flex; align-items: center; gap: 8px; padding: 4px 0; font-size: 12px; }
    .cpm-card .legend-swatch { display: inline-block; width: 30px; height: 3px; }
    .legend-fs { background: #2563eb; }
    .legend-ss { background: repeating-linear-gradient(90deg, #16a34a 0 4px, transparent 4px 8px); }
    .legend-ff { background: #ea580c; height: 5px; }
    .legend-sf { background: repeating-linear-gradient(90deg, #9333ea 0 2px, transparent 2px 6px); }
    .critical-item { padding: 6px 10px; background: #fef3c7; border-right: 3px solid #d97706; border-radius: 6px; margin-bottom: 6px; font-size: 12px; }
    .critical-item .badge { font-size: 10px; }
    .duration-display { font-size: 28px; font-weight: 700; color: #d97706; line-height: 1; }
    #mermaidContainer svg { max-width: 100%; }
</style>
@endpush


@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <a href="javascript:history.back()" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-right"></i> العودة
            </a>
        </div>
        <div>
            <a href="{{ route('operational.kanban', $majorTaskId) }}" class="btn-kanban">
                <i class="fas fa-columns"></i> لوحة كانبان
            </a>
        </div>
    </div>


    <div class="card-custom mb-4">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <h4><i class="fas fa-sitemap ml-2"></i> إدارة تبعيات المهام التشغيلية</h4>
                <p class="text-muted">{{ $majorTaskTitle }} ({{ $taskCount }} مهمة تشغيلية)</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn-gold" data-bs-toggle="modal" data-bs-target="#addDependencyModal">
                    <i class="fas fa-plus"></i> إضافة تبعية
                </button>
            </div>
        </div>
    </div>


    <div class="view-toggle">
        <button class="btn btn-outline-secondary active" id="listViewBtn" onclick="switchView('list')">
            <i class="fas fa-list"></i> عرض القائمة
        </button>
        <button class="btn btn-outline-secondary" id="flowViewBtn" onclick="switchView('flow')">
            <i class="fas fa-project-diagram"></i> عرض المخطط
        </button>
        <button class="btn btn-outline-secondary" onclick="refreshFlowChart()">
            <i class="fas fa-sync"></i> تحديث المخطط
        </button>
    </div>


    <div id="listView">
        @if(empty($tasks))
        <div class="empty-state">
            <i class="fas fa-sitemap"></i>
            <h5>لا توجد مهام تشغيلية</h5>
            <p class="text-muted">لا توجد مهام تشغيلية تابعة لهذه المهمة الرئيسية</p>
        </div>
        @else
        @foreach($tasks as $task)
        <div class="task-dependency-item" data-task-id="{{ $task['task_id'] }}">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="task-title">{{ $task['title'] }}</div>
                    <div class="task-meta">
                        <span><i class="fas fa-user ml-1"></i> {{ $task['assigned_to_name'] ?? 'غير معين' }}</span>
                        <span><i class="fas fa-calendar-alt ml-1"></i> {{ $task['end_date'] ?? 'غير محدد' }}</span>
                        <span>
                            <span class="task-status-badge status-{{ $task['status_id'] ?? 16 }}">
                                {{ $task['status_name'] ?? 'معلق مؤقتاً' }}
                            </span>
                        </span>
                    </div>
                </div>
                <div>
                    <span class="badge bg-secondary">{{ count($task['dependencies'] ?? []) }} تبعية</span>
                </div>
            </div>


            @if(!empty($task['dependencies']))
            <div class="dependency-list">
                <small class="text-muted fw-bold">تعتمد على:</small>
                @foreach($task['dependencies'] as $dep)
                <div class="dependency-item">
                    <span class="dep-type">{{ $dep['dependency_type'] }}</span>
                    <span class="dep-arrow">→</span>
                    <span>{{ $dep['depends_on_task_title'] ?? 'مهمة غير موجودة' }}</span>
                    @if($dep['lag_days'] > 0)
                    <span class="text-muted small">(+{{ $dep['lag_days'] }} يوم)</span>
                    @endif
                    <button class="btn btn-sm btn-outline-primary" onclick="editDependency({{ $dep['dependency_id'] }}, '{{ $dep['dependency_type'] }}', {{ $dep['lag_days'] ?? 0 }})" title="تعديل التبعية">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="removeDependency({{ $dep['dependency_id'] }})" title="حذف التبعية">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
                @endforeach
            </div>
            @endif
        </div>
        @endforeach
        @endif
    </div>


    <div id="flowView" style="display:none;">
        <div class="row g-3">
            <div class="col-lg-8">
                <div class="flow-container" id="mermaidContainer">
                    <div class="mermaid" id="mermaidGraph"></div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="cpm-card">
                    <h6 class="fw-bold mb-3">
                        <i class="fas fa-stopwatch text-warning"></i> المسار الحرج (Critical Path)
                    </h6>
                    <div class="mb-3">
                        <div class="small text-muted">مدة المشروع الإجمالية</div>
                        <div class="duration-display" id="projectDuration">—</div>
                        <div class="small text-muted">يوم</div>
                    </div>
                    <hr>
                    <div class="small fw-bold mb-2">المهام الحرجة:</div>
                    <div id="criticalPathList" style="max-height:280px; overflow-y:auto;"></div>
                    <hr>
                    <div class="small fw-bold mb-2">دليل الرموز:</div>
                    <div class="legend-line"><span class="legend-swatch legend-fs"></span> FS (انتهاء → بدء)</div>
                    <div class="legend-line"><span class="legend-swatch legend-ss"></span> SS (بدء → بدء)</div>
                    <div class="legend-line"><span class="legend-swatch legend-ff"></span> FF (انتهاء → انتهاء)</div>
                    <div class="legend-line"><span class="legend-swatch legend-sf"></span> SF (بدء → انتهاء)</div>
                    <div class="mt-2 small">
                        <span style="background:#fde68a; padding:2px 8px; border-radius:4px; border:1px solid #d97706;">
                            أصفر = مهمة حرجة
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="addDependencyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-link ml-2"></i> إضافة تبعية جديدة</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="addDependencyForm" onsubmit="return submitDependency()">
                @csrf
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label class="fw-bold">المهمة <span class="text-danger">*</span></label>
                        <select name="task_id" id="depTaskId" class="form-control" required>
                            <option value="">-- اختر المهمة --</option>
                            @foreach($tasks as $task)
                            <option value="{{ $task['task_id'] }}">{{ $task['title'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label class="fw-bold">تعتمد على <span class="text-danger">*</span></label>
                        <select name="depends_on_task_id" id="depDependsOn" class="form-control" required>
                            <option value="">-- اختر المهمة --</option>
                            @foreach($tasks as $task)
                            <option value="{{ $task['task_id'] }}">{{ $task['title'] }}</option>
                            @endforeach
                        </select>
                        <div class="text-danger" id="cycleError" style="font-size:12px;display:none;">هذه التبعية ستؤدي إلى دورة مغلقة (loop)</div>
                    </div>
                    <div class="form-group mb-3">
                        <label class="fw-bold">نوع التبعية <span class="text-danger">*</span></label>
                        <select name="dependency_type" id="depType" class="form-control" required>
                            @foreach($dependencyTypes as $type)
                            <option value="{{ $type['value'] }}">{{ $type['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label class="fw-bold">أيام التأخير</label>
                        <input type="number" name="lag_days" id="depLagDays" class="form-control" value="0" min="0">
                        <small class="text-muted">عدد أيام التأخير بين انتهاء المهمة الأولى وبدء الثانية</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary" id="depSubmitBtn">إضافة التبعية</button>
                </div>
            </form>
        </div>
    </div>
</div>


<div class="modal fade" id="editDependencyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title"><i class="fas fa-edit ml-2"></i> تعديل التبعية</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editDependencyForm" onsubmit="return submitEditDependency()">
                @csrf
                @method('PUT')
                <input type="hidden" id="editDependencyId">
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label class="fw-bold">نوع التبعية <span class="text-danger">*</span></label>
                        <select name="dependency_type" id="editDepType" class="form-control" required>
                            @foreach($dependencyTypes as $type)
                            <option value="{{ $type['value'] }}">{{ $type['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label class="fw-bold">أيام التأخير</label>
                        <input type="number" name="lag_days" id="editDepLagDays" class="form-control" value="0" min="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-warning" id="editDepSubmitBtn">تحديث التبعية</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection


@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/mermaid@10.9.0/dist/mermaid.min.js"></script>
<script>
var dependencyData = @json($tasks);
var majorTaskId = {{ $majorTaskId }};
var viewMode = 'list';


mermaid.initialize({
    startOnLoad: false,
    theme: 'base',
    themeVariables: {
        fontFamily: 'Tajawal, sans-serif',
        fontSize: '13px',
        primaryColor: '#ffffff',
        primaryBorderColor: '#cbd5e0',
        lineColor: '#a0aec0'
    },
    flowchart: { curve: 'basis', useMaxWidth: true, htmlLabels: true }
});


function switchView(mode) {
    viewMode = mode;
    document.getElementById('listView').style.display = mode === 'list' ? 'block' : 'none';
    document.getElementById('flowView').style.display = mode === 'flow' ? 'block' : 'none';
    document.getElementById('listViewBtn').classList.toggle('active', mode === 'list');
    document.getElementById('flowViewBtn').classList.toggle('active', mode === 'flow');
    if (mode === 'flow') renderFlowChart();
}


function refreshFlowChart() {
    if (viewMode === 'flow') renderFlowChart();
}


function topoSort(tasks) {
    var indeg = {}, adj = {};
    tasks.forEach(function(t) { indeg[t.task_id] = 0; adj[t.task_id] = []; });
    tasks.forEach(function(t) {
        (t.dependencies || []).forEach(function(dep) {
            if (adj[dep.depends_on_task_id]) {
                adj[dep.depends_on_task_id].push(t.task_id);
                indeg[t.task_id]++;
            }
        });
    });
    var queue = tasks.filter(function(t) { return indeg[t.task_id] === 0; }).map(function(t) { return t.task_id; });
    var order = [];
    while (queue.length) {
        var tid = queue.shift();
        order.push(tid);
        adj[tid].forEach(function(s) { if (--indeg[s] === 0) queue.push(s); });
    }
    return order;
}


function getTaskDuration(task) {
    if (task.start_date && task.end_date) {
        var s = new Date(task.start_date);
        var e = new Date(task.end_date);
        return Math.max(1, Math.round((e - s) / 86400000));
    }
    return task.estimated_duration || 1;
}


function calculateCPM(tasks) {
    var taskMap = {}, dur = {}, ES = {}, EF = {}, LS = {}, LF = {}, slack = {}, successors = {};
    tasks.forEach(function(t) {
        taskMap[t.task_id] = t;
        dur[t.task_id] = getTaskDuration(t);
        if (!successors[t.task_id]) successors[t.task_id] = [];
    });
    tasks.forEach(function(t) {
        (t.dependencies || []).forEach(function(dep) {
            var p = dep.depends_on_task_id;
            if (!successors[p]) successors[p] = [];
            successors[p].push({ succ_id: t.task_id, type: dep.dependency_type, lag: dep.lag_days || 0 });
        });
    });


    var topo = topoSort(tasks);


    topo.forEach(function(tid) {
        var es = 0;
        (taskMap[tid].dependencies || []).forEach(function(dep) {
            var p = dep.depends_on_task_id;
            if (ES[p] === undefined) return;
            var L = dep.lag_days || 0;
            var T = dep.dependency_type;
            if (T === 'FS')       es = Math.max(es, EF[p] + L);
            else if (T === 'SS')  es = Math.max(es, ES[p] + L);
            else if (T === 'FF')  es = Math.max(es, EF[p] + L - dur[tid]);
            else if (T === 'SF')  es = Math.max(es, ES[p] + L - dur[tid]);
        });
        ES[tid] = es;
        EF[tid] = es + dur[tid];
    });


    var projectEnd = 0;
    Object.values(EF).forEach(function(v) { if (v > projectEnd) projectEnd = v; });


    topo.slice().reverse().forEach(function(tid) {
        var succs = successors[tid] || [];
        var lf;
        if (succs.length === 0) {
            lf = projectEnd;
        } else {
            lf = Infinity;
            succs.forEach(function(s) {
                var L = s.lag;
                var T = s.type;
                if (T === 'FS')       lf = Math.min(lf, LS[s.succ_id] - L);
                else if (T === 'SS')  lf = Math.min(lf, LS[s.succ_id] - L + dur[tid]);
                else if (T === 'FF')  lf = Math.min(lf, LF[s.succ_id] - L);
                else if (T === 'SF')  lf = Math.min(lf, LF[s.succ_id] - L + dur[tid]);
            });
        }
        LF[tid] = lf;
        LS[tid] = lf - dur[tid];
        slack[tid] = LS[tid] - ES[tid];
    });


    var criticalPath = topo.filter(function(tid) { return Math.abs(slack[tid]) < 0.001; });
    return { ES: ES, EF: EF, LS: LS, LF: LF, slack: slack, dur: dur, criticalPath: criticalPath, projectEnd: projectEnd };
}


function renderFlowChart() {
    var container = document.getElementById('mermaidGraph');
    var empty = document.getElementById('flowEmpty');


    if (!dependencyData || dependencyData.length === 0) {
        container.innerHTML = '<div class="empty-flow"><i class="fas fa-project-diagram"></i><h5>لا توجد مهام لعرضها</h5></div>';
        document.getElementById('projectDuration').innerText = '—';
        document.getElementById('criticalPathList').innerHTML = '';
        return;
    }


    var cpm = calculateCPM(dependencyData);


    var arrowMap = { 'FS': '-->', 'SS': '-.->', 'FF': '==>', 'SF': '-..->' };
    var code = 'graph TB\n';


    dependencyData.forEach(function(t) {
        var isCritical = cpm.criticalPath.indexOf(t.task_id) !== -1;
        var es = cpm.ES[t.task_id] || 0;
        var ef = cpm.EF[t.task_id] || 0;
        var safeTitle = String(t.title).replace(/"/g, "'").substring(0, 40);
        var label = safeTitle + '<br/><small>' + es + '→' + ef + 'd</small>';
        code += '  T' + t.task_id + '["' + label + '"]' + (isCritical ? ':::critical' : '') + '\n';
    });


    dependencyData.forEach(function(t) {
        (t.dependencies || []).forEach(function(dep) {
            var arrow = arrowMap[dep.dependency_type] || '-->';
            var label = dep.lag_days > 0 ? (dep.dependency_type + '+' + dep.lag_days + 'd') : dep.dependency_type;
            code += '  T' + dep.depends_on_task_id + ' ' + arrow + '|' + label + '| T' + t.task_id + '\n';
        });
    });


    code += '  classDef critical fill:#fde68a,stroke:#d97706,stroke-width:3px,color:#000\n';
    code += '  classDef normal fill:#ffffff,stroke:#cbd5e0,color:#000\n';


    container.innerHTML = code;
    container.removeAttribute('data-processed');
    mermaid.run({ nodes: [container] });


    document.getElementById('projectDuration').innerText = cpm.projectEnd;
    var list = document.getElementById('criticalPathList');
    list.innerHTML = '';
    cpm.criticalPath.forEach(function(tid) {
        var t = null;
        for (var i = 0; i < dependencyData.length; i++) {
            if (dependencyData[i].task_id === tid) { t = dependencyData[i]; break; }
        }
        var item = document.createElement('div');
        item.className = 'critical-item';
        item.innerHTML = '<span class="badge bg-warning text-dark">' + cpm.ES[tid] + '→' + cpm.EF[tid] + '</span> ' + (t ? t.title : '—');
        list.appendChild(item);
    });
}


function editDependencyFromFlow(taskId) {
    document.getElementById('depTaskId').value = taskId;
    var modal = new bootstrap.Modal(document.getElementById('addDependencyModal'));
    modal.show();
}


function editDependency(dependencyId, depType, lagDays) {
    document.getElementById('editDependencyId').value = dependencyId;
    document.getElementById('editDepType').value = depType;
    document.getElementById('editDepLagDays').value = lagDays || 0;
    var modal = new bootstrap.Modal(document.getElementById('editDependencyModal'));
    modal.show();
}


function removeDependency(dependencyId) {
    if (!confirm('هل أنت متأكد من حذف هذه التبعية؟')) return;
    fetch("{{ route('operational.delete-dependency', 'dependencyId') }}/".replace('dependencyId', dependencyId), {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            showToast(data.message || 'تم حذف التبعية بنجاح', 'success');
            setTimeout(function() { location.reload(); }, 500);
        } else {
            showToast(data.message || 'فشل حذف التبعية', 'error');
        }
    })
    .catch(function() { showToast('حدث خطأ أثناء حذف التبعية', 'error'); });
}


function submitDependency() {
    var taskId = document.getElementById('depTaskId').value;
    var dependsOn = document.getElementById('depDependsOn').value;
    var depType = document.getElementById('depType').value;
    var lagDays = document.getElementById('depLagDays').value || 0;
    var submitBtn = document.getElementById('depSubmitBtn');


    if (!taskId || !dependsOn) { showToast('الرجاء اختيار المهمتين', 'warning'); return false; }
    if (taskId === dependsOn) { showToast('لا يمكن للمهمة أن تعتمد على نفسها', 'warning'); return false; }
    if (document.getElementById('cycleError').style.display === 'block') {
        showToast('لا يمكن إضافة تبعية تؤدي إلى دورة مغلقة', 'warning');
        return false;
    }


    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري...';


    fetch("{{ route('operational.add-dependency') }}", {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({
            task_id: taskId,
            depends_on_task_id: dependsOn,
            dependency_type: depType,
            lag_days: parseInt(lagDays)
        })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            showToast(data.message || 'تم إضافة التبعية بنجاح', 'success');
            var modal = bootstrap.Modal.getInstance(document.getElementById('addDependencyModal'));
            if (modal) modal.hide();
            setTimeout(function() { location.reload(); }, 500);
        } else {
            showToast(data.message || 'فشل إضافة التبعية', 'error');
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'إضافة التبعية';
        }
    })
    .catch(function() {
        showToast('حدث خطأ أثناء إضافة التبعية', 'error');
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'إضافة التبعية';
    });


    return false;
}


function submitEditDependency() {
    var dependencyId = document.getElementById('editDependencyId').value;
    var depType = document.getElementById('editDepType').value;
    var lagDays = document.getElementById('editDepLagDays').value || 0;
    var submitBtn = document.getElementById('editDepSubmitBtn');


    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري...';


    fetch("{{ route('operational.update-dependency','$dependencyId') }}/".replace('$dependencyId', dependencyId), {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ dependency_type: depType, lag_days: parseInt(lagDays) })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            showToast(data.message || 'تم تحديث التبعية بنجاح', 'success');
            var modal = bootstrap.Modal.getInstance(document.getElementById('editDependencyModal'));
            if (modal) modal.hide();
            setTimeout(function() { location.reload(); }, 500);
        } else {
            showToast(data.message || 'فشل تحديث التبعية', 'error');
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'تحديث التبعية';
        }
    })
    .catch(function() {
        showToast('حدث خطأ أثناء تحديث التبعية', 'error');
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'تحديث التبعية';
    });


    return false;
}


function checkCycle() {
    var taskId = document.getElementById('depTaskId').value;
    var dependsOn = document.getElementById('depDependsOn').value;
    var cycleError = document.getElementById('cycleError');
    var submitBtn = document.getElementById('depSubmitBtn');


    if (!taskId || !dependsOn || taskId === dependsOn) {
        cycleError.style.display = 'none';
        submitBtn.disabled = false;
        return;
    }


    fetch("{{ route('operational.check-cycle') }}", {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ task_id: taskId, depends_on_task_id: dependsOn })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.would_create_cycle) {
            cycleError.style.display = 'block';
            submitBtn.disabled = true;
        } else {
            cycleError.style.display = 'none';
            submitBtn.disabled = false;
        }
    })
    .catch(function() {
        cycleError.style.display = 'none';
        submitBtn.disabled = false;
    });
}


function showToast(message, type) {
    if (typeof toastr !== 'undefined') toastr[type](message);
    else alert(message);
}


document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('depTaskId').addEventListener('change', checkCycle);
    document.getElementById('depDependsOn').addEventListener('change', checkCycle);


    document.getElementById('addDependencyModal').addEventListener('hidden.bs.modal', function() {
        document.getElementById('addDependencyForm').reset();
        document.getElementById('depSubmitBtn').disabled = false;
        document.getElementById('depSubmitBtn').innerHTML = 'إضافة التبعية';
        document.getElementById('cycleError').style.display = 'none';
    });


    document.getElementById('editDependencyModal').addEventListener('hidden.bs.modal', function() {
        document.getElementById('editDependencyForm').reset();
        document.getElementById('editDepSubmitBtn').disabled = false;
        document.getElementById('editDepSubmitBtn').innerHTML = 'تحديث التبعية';
    });
});
</script>
@endpush

@extends('layouts.app')

@section('title', 'إدارة تبعيات المهام التشغيلية')

@push('styles')
<style>
    .view-toggle {
        display: flex;
        gap: 10px;
        margin-bottom: 16px;
        flex-wrap: wrap;
    }
    .view-toggle .btn {
        border-radius: 20px;
        padding: 6px 20px;
        font-weight: 600;
    }
    .view-toggle .btn.active {
        background: #d4af37;
        color: #1a1a2e;
        border-color: #d4af37;
    }
    .task-dependency-item {
        background: #fff;
        border-radius: 12px;
        padding: 16px;
        margin-bottom: 12px;
        border: 1px solid #e2e8f0;
        transition: all 0.2s;
    }
    .task-dependency-item:hover {
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    }
    .task-dependency-item .task-title {
        font-weight: 600;
        font-size: 15px;
        margin-bottom: 4px;
    }
    .task-dependency-item .task-meta {
        font-size: 12px;
        color: #718096;
        display: flex;
        gap: 16px;
        flex-wrap: wrap;
    }
    .dependency-list {
        margin-top: 8px;
        padding-top: 8px;
        border-top: 1px dashed #e2e8f0;
    }
    .dependency-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 6px 12px;
        font-size: 13px;
        flex-wrap: wrap;
        background: #f8f9fa;
        border-radius: 6px;
        margin-bottom: 4px;
    }
    .dependency-item .dep-type {
        background: #e2e8f0;
        padding: 2px 10px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
        color: #4a5568;
    }
    .dependency-item .dep-arrow {
        color: #d4af37;
        font-weight: 700;
        margin: 0 4px;
    }
    .dependency-item .btn {
        padding: 2px 8px;
        font-size: 12px;
        line-height: 1.5;
    }
    .empty-state {
        text-align: center;
        padding: 60px 20px;
    }
    .empty-state i {
        font-size: 64px;
        color: #cbd5e0;
        margin-bottom: 16px;
    }
    .flow-container {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 20px;
        border: 1px solid #e2e8f0;
        min-height: 400px;
        overflow: auto;
        display: flex;
        flex-wrap: wrap;
        gap: 16px;
        justify-content: center;
        align-items: flex-start;
    }
    .flow-card {
        background: #fff;
        border-radius: 12px;
        padding: 16px;
        min-width: 200px;
        max-width: 280px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        position: relative;
        flex: 1 1 auto;
    }
    .flow-card .card-title {
        font-weight: 600;
        font-size: 14px;
        margin-bottom: 6px;
        color: #2d3748;
    }
    .flow-card .card-status {
        font-size: 11px;
        padding: 2px 10px;
        border-radius: 12px;
        display: inline-block;
        font-weight: 600;
    }
    .flow-card .card-meta {
        font-size: 11px;
        color: #718096;
        margin-top: 4px;
    }
    .flow-card .card-deps {
        margin-top: 8px;
        padding-top: 8px;
        border-top: 1px dashed #e2e8f0;
        font-size: 12px;
    }
    .flow-card .card-deps .dep-item {
        background: #f1f5f9;
        padding: 2px 8px;
        border-radius: 4px;
        display: inline-block;
        margin: 2px 4px 2px 0;
        font-size: 11px;
    }
    .flow-card .card-deps .dep-arrow-flow {
        color: #d4af37;
        font-weight: 700;
        margin: 0 2px;
    }
    .flow-card .card-actions {
        margin-top: 8px;
        display: flex;
        gap: 4px;
        flex-wrap: wrap;
    }
    .flow-card .card-actions .btn {
        padding: 1px 6px;
        font-size: 11px;
    }
    .flow-card .depends-on-me {
        background: #ebf8ff;
        border-left: 3px solid #3182ce;
    }
    .flow-card .depends-on-others {
        background: #fefcbf;
        border-left: 3px solid #d69e2e;
    }
    .flow-card .no-deps {
        background: #f0fff4;
        border-left: 3px solid #38a169;
    }
    .flow-card .has-cycle {
        border: 2px solid #e53e3e;
    }
    .flow-arrow {
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        color: #a0aec0;
        min-width: 40px;
    }
    .flow-row {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
        width: 100%;
        justify-content: center;
    }
    .task-status-badge {
        padding: 2px 10px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
    }
    .task-status-badge.status-16 { background: #e2e8f0; color: #4a5568; }
    .task-status-badge.status-26 { background: #bee3f8; color: #2a69ac; }
    .task-status-badge.status-6 { background: #81e6d9; color: #234e52; }
    .task-status-badge.status-5 { background: #fbd38d; color: #7b341e; }
    .task-status-badge.status-8 { background: #d6bcfa; color: #553c9a; }
    .task-status-badge.status-19 { background: #9ae6b4; color: #22543d; }
    .task-status-badge.status-20 { background: #feb2b2; color: #742a2a; }
    .btn-gold {
        background: #d4af37;
        color: #1a1a2e;
        border: none;
        padding: 6px 16px;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s;
        font-size: 14px;
    }
    .btn-gold:hover {
        background: #c5a234;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(212, 175, 55, 0.3);
        color: #1a1a2e;
    }
    .btn-gold i {
        margin-left: 6px;
    }
    .btn-kanban {
        background: #9f7aea;
        color: #fff;
        border: none;
        padding: 6px 16px;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s;
        font-size: 14px;
    }
    .btn-kanban:hover {
        background: #805ad5;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(159, 122, 234, 0.3);
        color: #fff;
    }
    .btn-kanban i {
        margin-left: 6px;
    }
    .flow-container .empty-flow {
        text-align: center;
        padding: 60px 20px;
        width: 100%;
    }
    .flow-container .empty-flow i {
        font-size: 64px;
        color: #cbd5e0;
        margin-bottom: 16px;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <a href="{{ route('operational.show-major-task', $majorTaskId) }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-right"></i> العودة للمهمة الرئيسية
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
                        <span>
                            <i class="fas fa-user ml-1"></i>
                            {{ $task['assigned_to_name'] ?? 'غير معين' }}
                        </span>
                        <span>
                            <i class="fas fa-calendar-alt ml-1"></i>
                            {{ $task['end_date'] ?? 'غير محدد' }}
                        </span>
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
        <div class="flow-container" id="flowContainer">
            <div class="empty-flow" id="flowEmpty">
                <i class="fas fa-project-diagram"></i>
                <h5>لا توجد مهام لعرضها</h5>
                <p class="text-muted">قم بإضافة مهام تشغيلية أو تبعيات لعرض المخطط</p>
            </div>
            <div id="flowCards" style="display:none; width:100%;">
                <div class="flow-row" id="flowRow"></div>
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
                        <small class="text-muted">عدد أيام التأخير بين انتهاء المهمة الأولى وبدء الثانية</small>
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
<script>
    var dependencyData = @json($tasks);
    var majorTaskId = {{ $majorTaskId }};
    var viewMode = 'list';

    function switchView(mode) {
        viewMode = mode;
        document.getElementById('listView').style.display = mode === 'list' ? 'block' : 'none';
        document.getElementById('flowView').style.display = mode === 'flow' ? 'block' : 'none';

        document.getElementById('listViewBtn').classList.toggle('active', mode === 'list');
        document.getElementById('flowViewBtn').classList.toggle('active', mode === 'flow');

        if (mode === 'flow') {
            renderFlowChart();
        }
    }

    function refreshFlowChart() {
        if (viewMode === 'flow') {
            renderFlowChart();
        }
    }

    function renderFlowChart() {
        var container = document.getElementById('flowRow');
        var empty = document.getElementById('flowEmpty');
        var cards = document.getElementById('flowCards');

        if (!dependencyData || dependencyData.length === 0) {
            empty.style.display = 'block';
            cards.style.display = 'none';
            return;
        }

        empty.style.display = 'none';
        cards.style.display = 'block';
        container.innerHTML = '';

        var hasDeps = false;

        dependencyData.forEach(function(task) {
            var hasDependencies = task.dependencies && task.dependencies.length > 0;
            var isDependedOn = task.depended_by && task.depended_by.length > 0;

            if (hasDependencies || isDependedOn) {
                hasDeps = true;
            }

            var card = document.createElement('div');
            card.className = 'flow-card';

            if (hasDependencies && isDependedOn) {
                card.classList.add('depends-on-others');
            } else if (hasDependencies) {
                card.classList.add('depends-on-others');
            } else if (isDependedOn) {
                card.classList.add('depends-on-me');
            } else {
                card.classList.add('no-deps');
            }

            var statusClass = 'status-' + (task.status_id || 16);
            var statusName = task.status_name || 'معلق مؤقتاً';

            var depsHtml = '';
            if (hasDependencies) {
                depsHtml += '<div class="card-deps"><strong>تعتمد على:</strong><br>';
                task.dependencies.forEach(function(dep) {
                    depsHtml += '<span class="dep-item">' + dep.dependency_type + ' → ' + (dep.depends_on_task_title || 'مهمة غير موجودة');
                    if (dep.lag_days > 0) {
                        depsHtml += ' (+' + dep.lag_days + 'd)';
                    }
                    depsHtml += '</span> ';
                });
                depsHtml += '</div>';
            }

            var dependedByHtml = '';
            if (isDependedOn) {
                dependedByHtml += '<div class="card-deps"><strong>تعتمد عليه:</strong><br>';
                task.depended_by.forEach(function(dep) {
                    var taskTitle = dependencyData.find(function(t) { return t.task_id === dep.task_id; });
                    dependedByHtml += '<span class="dep-item">' + (taskTitle ? taskTitle.title : 'مهمة غير موجودة') + ' → ' + dep.dependency_type;
                    if (dep.lag_days > 0) {
                        dependedByHtml += ' (+' + dep.lag_days + 'd)';
                    }
                    dependedByHtml += '</span> ';
                });
                dependedByHtml += '</div>';
            }

            card.innerHTML = `
                <div class="card-title">${task.title}</div>
                <div>
                    <span class="card-status ${statusClass}">${statusName}</span>
                </div>
                <div class="card-meta">
                    <i class="fas fa-user"></i> ${task.assigned_to_name || 'غير معين'}
                    ${task.end_date ? ' | <i class="fas fa-calendar-alt"></i> ' + task.end_date : ''}
                </div>
                ${depsHtml}
                ${dependedByHtml}
                <div class="card-actions">
                    <button class="btn btn-sm btn-outline-primary" onclick="editDependencyFromFlow(${task.task_id})">
                        <i class="fas fa-plus"></i> تبعية
                    </button>
                </div>
            `;

            container.appendChild(card);
        });

        if (!hasDeps) {
            var noDepsCard = document.createElement('div');
            noDepsCard.className = 'flow-card no-deps';
            noDepsCard.style.width = '100%';
            noDepsCard.style.textAlign = 'center';
            noDepsCard.innerHTML = `
                <div style="padding: 20px;">
                    <i class="fas fa-info-circle fa-2x text-muted mb-2"></i>
                    <p class="text-muted">لا توجد تبعيات بين المهام. قم بإضافة تبعية لرؤية العلاقات.</p>
                </div>
            `;
            container.appendChild(noDepsCard);
        }
    }

    function editDependencyFromFlow(taskId) {
        document.getElementById('depTaskId').value = taskId;
        document.getElementById('addDependencyModal').querySelector('.modal-title').innerHTML = '<i class="fas fa-link ml-2"></i> إضافة تبعية للمهمة';
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

        fetch("{{ route('operational.delete-dependency', 'dependencyId') }}/" + dependencyId, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                showToast(data.message || 'تم حذف التبعية بنجاح', 'success');
                setTimeout(function() { location.reload(); }, 500);
            } else {
                showToast(data.message || 'فشل حذف التبعية', 'error');
            }
        })
        .catch(function() {
            showToast('حدث خطأ أثناء حذف التبعية', 'error');
        });
    }

    function submitDependency() {
        var taskId = document.getElementById('depTaskId').value;
        var dependsOn = document.getElementById('depDependsOn').value;
        var depType = document.getElementById('depType').value;
        var lagDays = document.getElementById('depLagDays').value || 0;
        var submitBtn = document.getElementById('depSubmitBtn');

        if (!taskId || !dependsOn) {
            showToast('الرجاء اختيار المهمتين', 'warning');
            return false;
        }

        if (taskId === dependsOn) {
            showToast('لا يمكن للمهمة أن تعتمد على نفسها', 'warning');
            return false;
        }

        if (document.getElementById('cycleError').style.display === 'block') {
            showToast('لا يمكن إضافة تبعية تؤدي إلى دورة مغلقة', 'warning');
            return false;
        }

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري...';

        fetch("{{ route('operational.add-dependency') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                task_id: taskId,
                depends_on_task_id: dependsOn,
                dependency_type: depType,
                lag_days: parseInt(lagDays)
            })
        })
        .then(function(response) { return response.json(); })
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

        fetch("{{ route('operational.update-dependency', 'dependencyId') }}/" + dependencyId, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                dependency_type: depType,
                lag_days: parseInt(lagDays)
            })
        })
        .then(function(response) { return response.json(); })
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
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                task_id: taskId,
                depends_on_task_id: dependsOn
            })
        })
        .then(function(response) { return response.json(); })
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
        if (typeof toastr !== 'undefined') {
            toastr[type](message);
        } else {
            alert(message);
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('depTaskId').addEventListener('change', checkCycle);
        document.getElementById('depDependsOn').addEventListener('change', checkCycle);

        var addModal = document.getElementById('addDependencyModal');
        addModal.addEventListener('hidden.bs.modal', function() {
            document.getElementById('addDependencyForm').reset();
            document.getElementById('depSubmitBtn').disabled = false;
            document.getElementById('depSubmitBtn').innerHTML = 'إضافة التبعية';
            document.getElementById('cycleError').style.display = 'none';
            document.getElementById('addDependencyModal').querySelector('.modal-title').innerHTML = '<i class="fas fa-link ml-2"></i> إضافة تبعية جديدة';
        });

        var editModal = document.getElementById('editDependencyModal');
        editModal.addEventListener('hidden.bs.modal', function() {
            document.getElementById('editDependencyForm').reset();
            document.getElementById('editDepSubmitBtn').disabled = false;
            document.getElementById('editDepSubmitBtn').innerHTML = 'تحديث التبعية';
        });
    });
</script>
@endpush

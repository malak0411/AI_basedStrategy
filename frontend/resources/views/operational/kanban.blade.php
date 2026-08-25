@extends('layouts.app')

@section('title', 'لوحة إدارة المهام التشغيلية')

@push('styles')
<style>
    .kanban-container {
        display: flex;
        gap: 16px;
        overflow-x: auto;
        padding-bottom: 20px;
        min-height: 600px;
        align-items: flex-start;
    }
    .kanban-column {
        background: #f1f5f9;
        border-radius: 16px;
        min-width: 260px;
        max-width: 300px;
        flex: 1;
        padding: 16px;
        display: flex;
        flex-direction: column;
    }
    .kanban-column-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
        padding-bottom: 8px;
        border-bottom: 2px solid #e2e8f0;
        flex-shrink: 0;
    }
    .kanban-column-header h6 {
        font-weight: 700;
        margin: 0;
        font-size: 14px;
    }
    .kanban-column-header .task-count {
        background: #fff;
        padding: 2px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    .drop-zone {
        min-height: 80px;
        border: 2px dashed transparent;
        border-radius: 8px;
        transition: all 0.2s;
        padding: 4px;
        flex: 1;
    }
    .drop-zone.active {
        border-color: #d4af37;
        background: #fffbeb;
    }
    .drop-zone .empty-message {
        color: #a0aec0;
        text-align: center;
        padding: 20px 0;
        font-size: 13px;
    }
    .kanban-task {
        background: #fff;
        border-radius: 12px;
        padding: 14px;
        margin-bottom: 10px;
        transition: all 0.2s;
        border: 1px solid #e2e8f0;
        position: relative;
        cursor: pointer;
    }
    .kanban-task.draggable {
        cursor: grab;
    }
    .kanban-task.draggable:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        transform: translateY(-2px);
    }
    .kanban-task.dragging {
        opacity: 0.5;
        cursor: grabbing;
    }
    .kanban-task .task-title {
        font-size: 14px;
        font-weight: 600;
        margin-bottom: 4px;
        color: #2d3748;
    }
    .kanban-task .task-meta {
        font-size: 11px;
        color: #718096;
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }
    .kanban-task .task-meta span {
        display: flex;
        align-items: center;
        gap: 4px;
    }
    .kanban-task .task-badge {
        font-size: 9px;
        padding: 1px 8px;
        border-radius: 10px;
        font-weight: 600;
        display: inline-block;
        margin-right: 4px;
    }
    .badge-priority-high {
        background: #fed7d7;
        color: #c53030;
    }
    .badge-priority-medium {
        background: #fefcbf;
        color: #975a16;
    }
    .badge-priority-low {
        background: #c6f6d5;
        color: #276749;
    }
    .column-color {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        display: inline-block;
        margin-left: 6px;
    }
    .color-unassigned {
        background: #a0aec0;
    }
    .color-ready {
        background: #4299e1;
    }
    .color-progress {
        background: #38b2ac;
    }
    .color-hold {
        background: #ed8936;
    }
    .color-review {
        background: #9f7aea;
    }
    .color-accepted {
        background: #48bb78;
    }
    .color-rejected {
        background: #fc8181;
    }
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255,255,255,0.7);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 9999;
    }
    .loading-overlay.show {
        display: flex;
    }
    .task-info-bar {
        background: #fff;
        border-radius: 16px;
        padding: 16px 20px;
        border: 1px solid #e2e8f0;
        margin-bottom: 20px;
    }
    .task-info-bar .task-title {
        font-size: 17px;
        font-weight: 700;
    }
    .task-info-bar .task-meta {
        display: flex;
        gap: 16px;
        flex-wrap: wrap;
        margin-top: 6px;
    }
    .task-info-bar .task-meta .item {
        font-size: 12px;
        color: #4a5568;
    }
    .task-info-bar .task-meta .item strong {
        color: #2d3748;
    }
    .kanban-task .task-assignee {
        font-size: 11px;
        color: #4a5568;
        display: flex;
        align-items: center;
        gap: 4px;
        margin-top: 4px;
    }
    .kanban-task .task-assignee .avatar {
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: #e2e8f0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 9px;
        font-weight: 700;
        color: #4a5568;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3><i class="fas fa-tasks ml-2"></i>لوحة إدارة المهام التشغيلية</h3>
            <p class="text-muted mb-0">اسحب وأفلت المهام بين الأعمدة لتغيير حالتها</p>
        </div>
        <div>
            <a href="{{ route('operational.show-major-task', $majorTaskId ?? 0) }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-right"></i> العودة للمهمة الرئيسية
            </a>
        </div>
    </div>

    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif

    <div class="task-info-bar">
    <div class="d-flex justify-content-between align-items-start flex-wrap">
        <div>
            <div class="task-title">{{ $majorTaskTitle ?? 'المهمة الرئيسية' }}</div>
            <div class="task-meta">
                <span class="item">
                    <i class="fas fa-folder-open ml-1"></i> 
                    المبادرة: 
                    <strong>{{ $initiativeName ?? 'غير محدد' }}</strong>
                </span>
                <span class="item">
                    <i class="far fa-calendar-alt ml-1"></i> 
                    بداية المبادرة: 
                    <strong>
                        @if(isset($initiativeStart) && $initiativeStart)
                            {{ \Carbon\Carbon::parse($initiativeStart)->format('Y-m-d') }}
                        @else
                            غير محدد
                        @endif
                    </strong>
                </span>
                <span class="item">
                    <i class="far fa-calendar-check ml-1"></i> 
                    نهاية المبادرة: 
                    <strong>
                        @if(isset($initiativeEnd) && $initiativeEnd)
                            {{ \Carbon\Carbon::parse($initiativeEnd)->format('Y-m-d') }}
                        @else
                            غير محدد
                        @endif
                    </strong>
                </span>
                <span class="item">
                    <i class="fas fa-clock ml-1"></i> 
                    المدة المتوقعة: 
                    <strong>{{ $estimatedDays ?? 0 }} يوم</strong>
                </span>
                <span class="item">
                    <i class="fas fa-tasks ml-1"></i> 
                    مهام إدارتك: 
                    <strong>{{ count($allTasks ?? []) }}</strong>
                </span>
            </div>
        </div>
        <div>
            <span class="badge bg-{{ ($majorTaskIsActive ?? true) ? 'success' : 'secondary' }} fs-6">
                {{ ($majorTaskIsActive ?? true) ? 'نشط' : 'غير نشط' }}
            </span>
        </div>
    </div>
</div>



    <div class="kanban-container">
        <div class="kanban-column" data-status="16">
            <div class="kanban-column-header">
                <h6><span class="column-color color-unassigned"></span>جاهزة للتوزيع</h6>
                <span class="task-count">{{ count($unassignedTasks ?? []) }}</span>
            </div>
            <div class="drop-zone" id="drop-16">
                @forelse($unassignedTasks ?? [] as $task)
                @php $taskId = $task['task_id'] ?? $task['id'] ?? 0; @endphp
                <div class="kanban-task" data-task-id="{{ $taskId }}" onclick="goToAssign({{ $taskId }})">
                    <div class="task-title">{{ $task['title'] ?? 'غير محدد' }}</div>
                    <div class="task-meta">
                        <span><i class="far fa-clock ml-1"></i> {{ $task['end_date'] ?? 'غير محدد' }}</span>
                        <span class="task-badge badge-priority-{{ $task['priority'] ?? 'medium' }}">
                            {{ ($task['priority'] ?? 'medium') == 'high' ? 'عالي' : (($task['priority'] ?? 'medium') == 'medium' ? 'متوسط' : 'منخفض') }}
                        </span>
                    </div>
                </div>
                @empty
                <div class="empty-message">لا توجد مهام غير موزعة</div>
                @endforelse
            </div>
        </div>

        <div class="kanban-column" data-status="26">
            <div class="kanban-column-header">
                <h6><span class="column-color color-ready"></span>جاهزة للتنفيذ</h6>
                <span class="task-count">{{ count($readyTasks ?? []) }}</span>
            </div>
            <div class="drop-zone" id="drop-26">
                @forelse($readyTasks ?? [] as $task)
                @php $taskId = $task['task_id'] ?? $task['id'] ?? 0; @endphp
                <div class="kanban-task draggable" draggable="true" data-task-id="{{ $taskId }}" onclick="goToTaskDetail({{ $taskId }})">
                    <div class="task-title">{{ $task['title'] ?? 'غير محدد' }}</div>
                    <div class="task-meta">
                        <span><i class="far fa-calendar-alt ml-1"></i> {{ $task['end_date'] ?? 'غير محدد' }}</span>
                        <span class="task-badge badge-priority-{{ $task['priority'] ?? 'medium' }}">
                            {{ ($task['priority'] ?? 'medium') == 'high' ? 'عالي' : (($task['priority'] ?? 'medium') == 'medium' ? 'متوسط' : 'منخفض') }}
                        </span>
                    </div>
                    <div class="task-assignee">
                        <span class="avatar">{{ mb_substr($task['assigned_to_name'] ?? 'م', 0, 1, 'UTF-8') }}</span>
                        {{ $task['assigned_to_name'] ?? 'غير معين' }}
                    </div>
                </div>
                @empty
                <div class="empty-message">لا توجد مهام جاهزة للتنفيذ</div>
                @endforelse
            </div>
        </div>

        <div class="kanban-column" data-status="6">
            <div class="kanban-column-header">
                <h6><span class="column-color color-progress"></span>قيد التنفيذ</h6>
                <span class="task-count">{{ count($inProgressTasks ?? []) }}</span>
            </div>
            <div class="drop-zone" id="drop-6">
                @forelse($inProgressTasks ?? [] as $task)
                @php $taskId = $task['task_id'] ?? $task['id'] ?? 0; @endphp
                <div class="kanban-task draggable" draggable="true" data-task-id="{{ $taskId }}" onclick="goToTaskDetail({{ $taskId }})">
                    <div class="task-title">{{ $task['title'] ?? 'غير محدد' }}</div>
                    <div class="task-meta">
                        <span><i class="far fa-calendar-alt ml-1"></i> {{ $task['end_date'] ?? 'غير محدد' }}</span>
                        <span class="task-badge badge-priority-{{ $task['priority'] ?? 'medium' }}">
                            {{ ($task['priority'] ?? 'medium') == 'high' ? 'عالي' : (($task['priority'] ?? 'medium') == 'medium' ? 'متوسط' : 'منخفض') }}
                        </span>
                    </div>
                    <div class="task-assignee">
                        <span class="avatar">{{ mb_substr($task['assigned_to_name'] ?? 'م', 0, 1, 'UTF-8') }}</span>
                        {{ $task['assigned_to_name'] ?? 'غير معين' }}
                    </div>
                </div>
                @empty
                <div class="empty-message">لا توجد مهام قيد التنفيذ</div>
                @endforelse
            </div>
        </div>

        <div class="kanban-column" data-status="5">
            <div class="kanban-column-header">
                <h6><span class="column-color color-hold"></span>موقفة</h6>
                <span class="task-count">{{ count($holdTasks ?? []) }}</span>
            </div>
            <div class="drop-zone" id="drop-5">
                @forelse($holdTasks ?? [] as $task)
                @php $taskId = $task['task_id'] ?? $task['id'] ?? 0; @endphp
                <div class="kanban-task draggable" draggable="true" data-task-id="{{ $taskId }}" onclick="goToTaskDetail({{ $taskId }})">
                    <div class="task-title">{{ $task['title'] ?? 'غير محدد' }}</div>
                    <div class="task-meta">
                        <span><i class="far fa-calendar-alt ml-1"></i> {{ $task['end_date'] ?? 'غير محدد' }}</span>
                        <span class="task-badge badge-priority-{{ $task['priority'] ?? 'medium' }}">
                            {{ ($task['priority'] ?? 'medium') == 'high' ? 'عالي' : (($task['priority'] ?? 'medium') == 'medium' ? 'متوسط' : 'منخفض') }}
                        </span>
                    </div>
                    <div class="task-assignee">
                        <span class="avatar">{{ mb_substr($task['assigned_to_name'] ?? 'م', 0, 1, 'UTF-8') }}</span>
                        {{ $task['assigned_to_name'] ?? 'غير معين' }}
                    </div>
                </div>
                @empty
                <div class="empty-message">لا توجد مهام موقفة</div>
                @endforelse
            </div>
        </div>

        <div class="kanban-column" data-status="8">
            <div class="kanban-column-header">
                <h6><span class="column-color color-review"></span>قيد المراجعة</h6>
                <span class="task-count">{{ count($reviewTasks ?? []) }}</span>
            </div>
            <div class="drop-zone" id="drop-8">
                @forelse($reviewTasks ?? [] as $task)
                @php $taskId = $task['task_id'] ?? $task['id'] ?? 0; @endphp
                <div class="kanban-task draggable" draggable="true" data-task-id="{{ $taskId }}" onclick="goToTaskDetail({{ $taskId }})">
                    <div class="task-title">{{ $task['title'] ?? 'غير محدد' }}</div>
                    <div class="task-meta">
                        <span><i class="far fa-calendar-alt ml-1"></i> {{ $task['end_date'] ?? 'غير محدد' }}</span>
                        <span class="task-badge badge-priority-{{ $task['priority'] ?? 'medium' }}">
                            {{ ($task['priority'] ?? 'medium') == 'high' ? 'عالي' : (($task['priority'] ?? 'medium') == 'medium' ? 'متوسط' : 'منخفض') }}
                        </span>
                    </div>
                    <div class="task-assignee">
                        <span class="avatar">{{ mb_substr($task['assigned_to_name'] ?? 'م', 0, 1, 'UTF-8') }}</span>
                        {{ $task['assigned_to_name'] ?? 'غير معين' }}
                    </div>
                </div>
                @empty
                <div class="empty-message">لا توجد مهام قيد المراجعة</div>
                @endforelse
            </div>
        </div>

        <div class="kanban-column" data-status="19">
            <div class="kanban-column-header">
                <h6><span class="column-color color-accepted"></span>مقبولة</h6>
                <span class="task-count">{{ count($acceptedTasks ?? []) }}</span>
            </div>
            <div class="drop-zone" id="drop-19">
                @forelse($acceptedTasks ?? [] as $task)
                @php $taskId = $task['task_id'] ?? $task['id'] ?? 0; @endphp
                <div class="kanban-task" data-task-id="{{ $taskId }}" onclick="goToTaskDetail({{ $taskId }})">
                    <div class="task-title">{{ $task['title'] ?? 'غير محدد' }}</div>
                    <div class="task-meta">
                        <span><i class="far fa-calendar-alt ml-1"></i> {{ $task['end_date'] ?? 'غير محدد' }}</span>
                        <span class="task-badge badge-priority-{{ $task['priority'] ?? 'medium' }}">
                            {{ ($task['priority'] ?? 'medium') == 'high' ? 'عالي' : (($task['priority'] ?? 'medium') == 'medium' ? 'متوسط' : 'منخفض') }}
                        </span>
                    </div>
                    <div class="task-assignee">
                        <span class="avatar">{{ mb_substr($task['assigned_to_name'] ?? 'م', 0, 1, 'UTF-8') }}</span>
                        {{ $task['assigned_to_name'] ?? 'غير معين' }}
                    </div>
                </div>
                @empty
                <div class="empty-message">لا توجد مهام مقبولة</div>
                @endforelse
            </div>
        </div>

        <div class="kanban-column" data-status="20">
            <div class="kanban-column-header">
                <h6><span class="column-color color-rejected"></span>مرفوضة</h6>
                <span class="task-count">{{ count($rejectedTasks ?? []) }}</span>
            </div>
            <div class="drop-zone" id="drop-20">
                @forelse($rejectedTasks ?? [] as $task)
                @php $taskId = $task['task_id'] ?? $task['id'] ?? 0; @endphp
                <div class="kanban-task" data-task-id="{{ $taskId }}" onclick="goToTaskDetail({{ $taskId }})">
                    <div class="task-title">{{ $task['title'] ?? 'غير محدد' }}</div>
                    <div class="task-meta">
                        <span><i class="far fa-calendar-alt ml-1"></i> {{ $task['end_date'] ?? 'غير محدد' }}</span>
                        <span class="task-badge badge-priority-{{ $task['priority'] ?? 'medium' }}">
                            {{ ($task['priority'] ?? 'medium') == 'high' ? 'عالي' : (($task['priority'] ?? 'medium') == 'medium' ? 'متوسط' : 'منخفض') }}
                        </span>
                    </div>
                    <div class="task-assignee">
                        <span class="avatar">{{ mb_substr($task['assigned_to_name'] ?? 'م', 0, 1, 'UTF-8') }}</span>
                        {{ $task['assigned_to_name'] ?? 'غير معين' }}
                    </div>
                </div>
                @empty
                <div class="empty-message">لا توجد مهام مرفوضة</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="commentModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="commentModalTitle">إضافة تعليق</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info" id="actionInfo"></div>
                <div class="mb-3">
                    <label class="form-label fw-bold">التعليق <span class="text-danger">*</span></label>
                    <textarea id="taskComment" class="form-control" rows="3" placeholder="أدخل تعليقاً على تغيير الحالة..."></textarea>
                    <div class="text-danger" id="commentError" style="font-size:12px;display:none;">التعليق مطلوب</div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button class="btn btn-primary" onclick="confirmStatusChange()">تأكيد</button>
            </div>
        </div>
    </div>
</div>

<div class="loading-overlay" id="loadingOverlay">
    <div class="spinner-border text-primary" role="status"><span class="visually-hidden">جاري...</span></div>
</div>
@endsection

@push('scripts')
<script>
    var draggedTaskId = null;
    var targetStatusId = null;
    var draggedFromColumn = null;

    document.querySelectorAll('.kanban-task.draggable').forEach(function(task) {
        task.addEventListener('dragstart', function(e) {
            draggedTaskId = this.getAttribute('data-task-id');
            draggedFromColumn = this.closest('.kanban-column').getAttribute('data-status');
            this.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
        });
        task.addEventListener('dragend', function() {
            this.classList.remove('dragging');
        });
    });

    document.querySelectorAll('.drop-zone').forEach(function(zone) {
        zone.addEventListener('dragover', function(e) {
            e.preventDefault();
            var column = this.closest('.kanban-column');
            var statusId = parseInt(column.getAttribute('data-status'));
            if (canDrop(draggedFromColumn, statusId, draggedTaskId)) {
                this.classList.add('active');
            }
        });
        zone.addEventListener('dragleave', function() {
            this.classList.remove('active');
        });
        zone.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('active');
            if (!draggedTaskId) return;
            var column = this.closest('.kanban-column');
            targetStatusId = parseInt(column.getAttribute('data-status'));
            if (!canDrop(draggedFromColumn, targetStatusId, draggedTaskId)) {
                showToast('لا يمكن نقل المهمة إلى هذا العمود', 'warning');
                return;
            }
            var taskElement = document.querySelector('.kanban-task[data-task-id="' + draggedTaskId + '"]');
            if (taskElement) {
                this.appendChild(taskElement);
            }
            if (targetStatusId === 5) {
                showCommentModal('إيقاف المهمة مؤقتاً', 'يجب إدخال سبب الإيقاف');
                return;
            }
            if (targetStatusId === 6 && parseInt(draggedFromColumn) === 5) {
                showCommentModal('إعادة المهمة للعمل', 'إدخال سبب إعادة التشغيل');
                return;
            }
            if (targetStatusId === 8) {
                showCommentModal('نقل للمراجعة', 'إدخال ملاحظات المراجعة');
                return;
            }
            if (targetStatusId === 19 || targetStatusId === 20) {
                showCommentModal(targetStatusId === 19 ? 'قبول المهمة' : 'رفض المهمة', 'إدخال سبب ' + (targetStatusId === 19 ? 'القبول' : 'الرفض'));
                return;
            }
            performStatusChange(draggedTaskId, targetStatusId, null);
        });
    });

    function canDrop(fromStatus, toStatus, taskId) {
        if (!fromStatus || !toStatus) return false;
        if (parseInt(fromStatus) === 16) {
            showToast('المهام غير الموزعة لا يمكن نقلها', 'warning');
            return false;
        }
        if (parseInt(fromStatus) === 19 || parseInt(fromStatus) === 20) {
            showToast('لا يمكن نقل المهام المقبولة أو المرفوضة', 'warning');
            return false;
        }
        var rules = {
            26: [6],
            6: [5, 8],
            5: [6],
            8: [6, 19, 20],
            19: [],
            20: []
        };
        var allowed = rules[parseInt(fromStatus)] || [];
        return allowed.includes(parseInt(toStatus));
    }

    function showCommentModal(title, info) {
        document.getElementById('commentModalTitle').textContent = title;
        document.getElementById('actionInfo').textContent = info;
        document.getElementById('taskComment').value = '';
        document.getElementById('commentError').style.display = 'none';
        new bootstrap.Modal(document.getElementById('commentModal')).show();
    }

    function confirmStatusChange() {
        var comment = document.getElementById('taskComment').value.trim();
        if (!comment) {
            document.getElementById('commentError').style.display = 'block';
            return;
        }
        document.getElementById('commentError').style.display = 'none';
        bootstrap.Modal.getInstance(document.getElementById('commentModal')).hide();
        performStatusChange(draggedTaskId, targetStatusId, comment);
    }

    function performStatusChange(taskId, statusId, comment) {
        document.getElementById('loadingOverlay').classList.add('show');
        fetch("{{ route('operational.update-status') }}", {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                task_id: taskId,
                status_id: statusId,
                comment: comment || ''
            })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            document.getElementById('loadingOverlay').classList.remove('show');
            if (data.success) {
                showToast('تم تغيير الحالة بنجاح', 'success');
                setTimeout(function() { location.reload(); }, 500);
            } else {
                showToast(data.message || 'فشل تغيير الحالة', 'error');
                location.reload();
            }
        })
        .catch(function() {
            document.getElementById('loadingOverlay').classList.remove('show');
            showToast('حدث خطأ', 'error');
            location.reload();
        });
    }

    function goToAssign(taskId) {
        if (taskId) {
            window.location.href = '/operational/assign/' + taskId;
        }
    }

    function goToTaskDetail(taskId) {
        if (taskId) {
            window.location.href = '/operational/task/' + taskId;
        }
    }

    function showToast(message, type) {
        if (typeof toastr !== 'undefined') {
            toastr[type](message);
        } else {
            alert(message);
        }
    }

    @if(session('success'))
        $(document).ready(function() { showToast('{{ session('success') }}', 'success'); });
    @endif

    @if(session('error'))
        $(document).ready(function() { showToast('{{ session('error') }}', 'error'); });
    @endif
</script>
@endpush

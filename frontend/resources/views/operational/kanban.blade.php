@extends('layouts.app')

@section('title', 'لوحة المهام التشغيلية')

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
                        المبادرة: <strong>{{ $initiativeName ?? 'غير محدد' }}</strong>
                    </span>
                    <span class="item">
                        <i class="far fa-calendar-alt ml-1"></i>
                        بداية المبادرة: <strong>{{ isset($initiativeStart) && $initiativeStart ? \Carbon\Carbon::parse($initiativeStart)->format('Y-m-d') : 'غير محدد' }}</strong>
                    </span>
                    <span class="item">
                        <i class="far fa-calendar-check ml-1"></i>
                        نهاية المبادرة: <strong>{{ isset($initiativeEnd) && $initiativeEnd ? \Carbon\Carbon::parse($initiativeEnd)->format('Y-m-d') : 'غير محدد' }}</strong>
                    </span>
                    <span class="item">
                        <i class="fas fa-clock ml-1"></i>
                        المدة المتوقعة: <strong>{{ $estimatedDays ?? 0 }} يوم</strong>
                    </span>
                    <span class="item">
                        <i class="fas fa-tasks ml-1"></i>
                        مهام إدارتك: <strong>{{ count($allTasks ?? []) }}</strong>
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
                    <div class="task-progress">
                        <div class="progress-bar" style="width: {{ $task['progress_percent'] ?? 0 }}%;">
                            {{ $task['progress_percent'] ?? 0 }}%
                        </div>
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
                    <div class="task-progress">
                        <div class="progress-bar" style="width: {{ $task['progress_percent'] ?? 0 }}%;">
                            {{ $task['progress_percent'] ?? 0 }}%
                        </div>
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
                    <div class="task-progress">
                        <div class="progress-bar" style="width: {{ $task['progress_percent'] ?? 0 }}%;">
                            {{ $task['progress_percent'] ?? 0 }}%
                        </div>
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
                    <div class="task-progress">
                        <div class="progress-bar" style="width: {{ $task['progress_percent'] ?? 0 }}%;">
                            {{ $task['progress_percent'] ?? 0 }}%
                        </div>
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
                    <div class="task-progress">
                        <div class="progress-bar bg-success" style="width: 100%;">100%</div>
                    </div>
                </div>
                @empty
                <div class="empty-message">لا توجد مهام مقبولة</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="statusModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="statusModalTitle">تغيير حالة المهمة</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info" id="actionInfo"></div>
                <div class="mb-3">
                    <label class="form-label fw-bold">التعليق <span class="text-danger">*</span></label>
                    <textarea id="taskComment" class="form-control" rows="3" placeholder="أدخل تعليقاً على تغيير الحالة..."></textarea>
                    <div class="text-danger" id="commentError" style="font-size:12px;display:none;">التعليق مطلوب</div>
                </div>
                <div class="mb-3" id="progressContainer" style="display:none;">
                    <label class="form-label fw-bold">نسبة الإنجاز <span class="text-danger">*</span></label>
                    <div class="d-flex align-items-center gap-3">
                        <input type="range" id="taskProgress" class="form-range" min="0" max="100" value="50" style="flex:1;">
                        <input type="number" id="taskProgressValue" class="form-control" style="width:80px;" min="0" max="100" value="50">
                        <span class="fw-bold">%</span>
                    </div>
                    <div class="text-muted small" id="progressHint">أدخل نسبة الإنجاز</div>
                    <div class="text-danger" id="progressError" style="font-size:12px;display:none;"></div>
                </div>
                <input type="hidden" id="targetStatusId" value="">
                <input type="hidden" id="targetTaskId" value="">
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button class="btn btn-primary" onclick="confirmStatusChange()">تأكيد التغيير</button>
            </div>
        </div>
    </div>
</div>

<div class="loading-overlay" id="loadingOverlay">
    <div class="spinner-border text-primary" role="status"><span class="visually-hidden">جاري...</span></div>
</div>
@endsection

@push('styles')
<style>
    .task-info-bar {
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        border-radius: 12px;
        padding: 20px 24px;
        margin-bottom: 24px;
        border: 1px solid #dee2e6;
    }

    .task-info-bar .task-title {
        font-size: 18px;
        font-weight: 600;
        color: #2d3748;
        margin-bottom: 8px;
    }

    .task-info-bar .task-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 16px 24px;
        font-size: 14px;
        color: #6c757d;
    }

    .task-info-bar .task-meta .item {
        display: inline-flex;
        align-items: center;
    }

    .task-info-bar .task-meta .item i {
        margin-left: 6px;
        width: 16px;
        text-align: center;
    }

    .task-info-bar .task-meta .item strong {
        color: #2d3748;
    }

    .kanban-container {
        display: grid;
        grid-template-columns: repeat(6, 1fr);
        gap: 16px;
        overflow-x: auto;
        min-height: 500px;
        padding-bottom: 16px;
    }

    .kanban-column {
        background: #f8f9fa;
        border-radius: 12px;
        border: 1px solid #e9ecef;
        min-width: 220px;
        display: flex;
        flex-direction: column;
        max-height: 70vh;
    }

    .kanban-column-header {
        background: #fff;
        border-radius: 12px 12px 0 0;
        padding: 12px 16px;
        border-bottom: 2px solid #e9ecef;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-shrink: 0;
    }

    .kanban-column-header h6 {
        margin: 0;
        font-size: 14px;
        font-weight: 600;
        color: #2d3748;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .column-color {
        display: inline-block;
        width: 12px;
        height: 12px;
        border-radius: 4px;
        margin-left: 6px;
    }

    .color-unassigned { background: #6c757d; }
    .color-ready { background: #17a2b8; }
    .color-progress { background: #007bff; }
    .color-hold { background: #ffc107; }
    .color-review { background: #fd7e14; }
    .color-accepted { background: #28a745; }

    .task-count {
        background: #e9ecef;
        padding: 2px 10px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
        color: #495057;
    }

    .drop-zone {
        flex: 1;
        padding: 8px;
        overflow-y: auto;
        min-height: 80px;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .drop-zone.drag-over {
        background: rgba(0, 123, 255, 0.05);
        border: 2px dashed #007bff;
        border-radius: 8px;
    }

    .kanban-task {
        background: #fff;
        border-radius: 8px;
        padding: 12px 14px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        border: 1px solid #e9ecef;
        cursor: pointer;
        transition: all 0.2s ease;
        position: relative;
    }

    .kanban-task:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        transform: translateY(-1px);
        border-color: #007bff;
    }

    .kanban-task.draggable {
        cursor: grab;
    }

    .kanban-task.draggable:active {
        cursor: grabbing;
    }

    .kanban-task .task-title {
        font-size: 14px;
        font-weight: 500;
        color: #2d3748;
        margin-bottom: 6px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .kanban-task .task-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 12px;
        color: #6c757d;
        flex-wrap: wrap;
        gap: 4px;
    }

    .kanban-task .task-meta span {
        display: inline-flex;
        align-items: center;
    }

    .kanban-task .task-meta i {
        margin-left: 4px;
        font-size: 11px;
    }

    .task-badge {
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 10px;
        font-weight: 600;
    }

    .badge-priority-high {
        background: #dc3545;
        color: #fff;
    }

    .badge-priority-medium {
        background: #ffc107;
        color: #212529;
    }

    .badge-priority-low {
        background: #28a745;
        color: #fff;
    }

    .task-assignee {
        display: flex;
        align-items: center;
        gap: 6px;
        margin-top: 8px;
        font-size: 12px;
        color: #495057;
        padding-top: 6px;
        border-top: 1px solid #f1f3f5;
    }

    .task-assignee .avatar {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background: linear-gradient(135deg, #6c5ce7, #0984e3);
        color: #fff;
        font-size: 11px;
        font-weight: 600;
        flex-shrink: 0;
    }

    .task-progress {
        margin-top: 8px;
        background: #e9ecef;
        border-radius: 4px;
        height: 20px;
        overflow: hidden;
        position: relative;
    }

    .task-progress .progress-bar {
        height: 100%;
        background: linear-gradient(90deg, #17a2b8, #007bff);
        border-radius: 4px;
        transition: width 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        font-weight: 600;
        color: #fff;
        white-space: nowrap;
    }

    .task-progress .progress-bar.bg-success {
        background: linear-gradient(90deg, #28a745, #20c997);
    }

    .empty-message {
        text-align: center;
        padding: 24px 8px;
        color: #adb5bd;
        font-size: 13px;
        background: #f8f9fa;
        border-radius: 8px;
        border: 1px dashed #dee2e6;
    }

    .loading-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255,255,255,0.7);
        z-index: 9999;
        align-items: center;
        justify-content: center;
    }

    .loading-overlay .spinner-border {
        width: 48px;
        height: 48px;
    }

    @media (max-width: 1400px) {
        .kanban-container {
            grid-template-columns: repeat(4, 1fr);
        }
    }

    @media (max-width: 992px) {
        .kanban-container {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    @media (max-width: 768px) {
        .kanban-container {
            grid-template-columns: repeat(2, 1fr);
        }

        .task-info-bar .task-meta {
            flex-direction: column;
            gap: 4px;
        }

        .kanban-column {
            min-width: 160px;
        }
    }

    @media (max-width: 480px) {
        .kanban-container {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    var currentTaskId = null;
    var currentStatusId = null;

    document.addEventListener('DOMContentLoaded', function() {
        var range = document.getElementById('taskProgress');
        var input = document.getElementById('taskProgressValue');

        if (range && input) {
            range.addEventListener('input', function() {
                input.value = this.value;
            });

            input.addEventListener('input', function() {
                var val = parseInt(this.value) || 0;
                var min = parseInt(range.min) || 0;
                var max = parseInt(range.max) || 100;
                if (val < min) val = min;
                if (val > max) val = max;
                range.value = val;
                this.value = val;
            });
        }

        document.querySelectorAll('.drop-zone').forEach(function(zone) {
            zone.addEventListener('dragover', dragOverHandler);
            zone.addEventListener('drop', dropHandler);
        });

        document.querySelectorAll('.kanban-task.draggable').forEach(function(task) {
            task.addEventListener('dragstart', dragStartHandler);
        });
    });

    function getStatusName(statusId) {
        var names = {
            16: 'جاهزة للتوزيع',
            26: 'جاهزة للتنفيذ',
            6: 'قيد التنفيذ',
            5: 'موقفة',
            7: 'متأخرة',
            8: 'قيد المراجعة',
            19: 'مقبولة'
        };
        return names[statusId] || 'غير معروفة';
    }

    function openStatusModal(taskId, statusId) {
        currentTaskId = taskId;
        currentStatusId = statusId;

        document.getElementById('targetTaskId').value = taskId;
        document.getElementById('targetStatusId').value = statusId;

        document.getElementById('taskComment').value = '';
        document.getElementById('taskProgress').value = 50;
        document.getElementById('taskProgressValue').value = 50;
        document.getElementById('commentError').style.display = 'none';
        document.getElementById('progressError').style.display = 'none';

        var requiresProgress = [6, 8].indexOf(statusId) !== -1;

        document.getElementById('progressContainer').style.display = requiresProgress ? 'block' : 'none';

        var infoText = 'نقل المهمة إلى حالة: ' + getStatusName(statusId);
        var progressRange = document.getElementById('taskProgress');

        if (statusId === 6) {
            infoText += ' (نسبة الإنجاز: 20% - 70%)';
            document.getElementById('progressHint').textContent = 'نسبة الإنجاز يجب أن تكون بين 20% و 70%';
            progressRange.min = 20;
            progressRange.max = 70;
            progressRange.value = 45;
            document.getElementById('taskProgressValue').value = 45;
        } else if (statusId === 8) {
            infoText += ' (نسبة الإنجاز: 0% - 85%)';
            document.getElementById('progressHint').textContent = 'نسبة الإنجاز لا يمكن أن تتجاوز 85%';
            progressRange.min = 0;
            progressRange.max = 85;
            progressRange.value = 50;
            document.getElementById('taskProgressValue').value = 50;
        } else if (statusId === 5 || statusId === 7) {
            infoText += ' (نسبة الإنجاز ثابتة)';
        } else if (statusId === 19) {
            infoText += ' (نسبة الإنجاز: 100% تلقائياً)';
        } else if (statusId === 26) {
            infoText += ' (نسبة الإنجاز: 0% تلقائياً)';
        }

        document.getElementById('actionInfo').textContent = infoText;
        document.getElementById('statusModalTitle').textContent = 'تغيير حالة المهمة #' + taskId;

        var modal = new bootstrap.Modal(document.getElementById('statusModal'));
        modal.show();
    }

    function confirmStatusChange() {
        var taskId = document.getElementById('targetTaskId').value;
        var statusId = parseInt(document.getElementById('targetStatusId').value);
        var comment = document.getElementById('taskComment').value.trim();
        var progressPercent = parseInt(document.getElementById('taskProgressValue').value) || 0;

        if (!comment) {
            document.getElementById('commentError').style.display = 'block';
            return;
        }
        document.getElementById('commentError').style.display = 'none';

        var requiresProgress = [6, 8].indexOf(statusId) !== -1;
        if (requiresProgress) {
            var min = parseInt(document.getElementById('taskProgress').min) || 0;
            var max = parseInt(document.getElementById('taskProgress').max) || 100;
            if (progressPercent < min || progressPercent > max) {
                document.getElementById('progressError').textContent = 'نسبة الإنجاز يجب أن تكون بين ' + min + '% و ' + max + '%';
                document.getElementById('progressError').style.display = 'block';
                return;
            }
        }
        document.getElementById('progressError').style.display = 'none';

        document.getElementById('loadingOverlay').style.display = 'flex';

        var data = {
            task_id: parseInt(taskId),
            status_id: statusId,
            comment: comment,
            progress_percent: requiresProgress ? progressPercent : null
        };

        var token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        fetch('/operational/update-status', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token
            },
            body: JSON.stringify(data)
        })
        .then(function(response) {
            return response.json().then(function(data) {
                return { status: response.status, body: data };
            });
        })
        .then(function(result) {
            document.getElementById('loadingOverlay').style.display = 'none';
            if (result.body.success) {
                var modalElement = document.getElementById('statusModal');
                var modal = bootstrap.Modal.getInstance(modalElement);
                if (modal) modal.hide();
                location.reload();
            } else {
                alert('خطأ: ' + (result.body.error || result.body.detail || 'فشل التحديث'));
            }
        })
        .catch(function(error) {
            document.getElementById('loadingOverlay').style.display = 'none';
            alert('خطأ: ' + error);
        });
    }

    function dragStartHandler(e) {
        var task = e.target.closest('.kanban-task');
        if (task) {
            var taskId = task.dataset.taskId;
            if (taskId) {
                e.dataTransfer.setData('text/plain', taskId);
                e.dataTransfer.effectAllowed = 'move';
            }
        }
    }

    function dragOverHandler(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        var zone = e.currentTarget;
        if (zone.closest('.kanban-column')) {
            zone.classList.add('drag-over');
        }
    }

    function dropHandler(e) {
        e.preventDefault();
        var zone = e.currentTarget;
        zone.classList.remove('drag-over');

        var targetColumn = zone.closest('.kanban-column');
        if (!targetColumn) return;

        var targetStatus = parseInt(targetColumn.dataset.status);
        var taskId = e.dataTransfer.getData('text/plain');

        if (taskId && targetStatus) {
            var dragElement = document.querySelector('[data-task-id="' + taskId + '"]');
            if (dragElement) {
                var currentColumn = dragElement.closest('.kanban-column');
                if (currentColumn) {
                    var currentStatus = parseInt(currentColumn.dataset.status);
                    if (currentStatus === targetStatus) {
                        return;
                    }
                }
            }
            openStatusModal(taskId, targetStatus);
        }
    }

    function goToAssign(taskId) {
        if (taskId > 0) {
            window.location.href = '/operational/assign/' + taskId;
        }
    }

    function goToTaskDetail(taskId) {
        if (taskId > 0) {
            window.location.href = '/operational/task/' + taskId;
        }
    }
</script>
@endpush

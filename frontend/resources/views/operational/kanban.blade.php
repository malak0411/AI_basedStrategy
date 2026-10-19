@extends('layouts.app')


@section('title', 'لوحة المهام التشغيلية')


@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h3 class="mb-1"><i class="fas fa-tasks ml-2"></i>لوحة إدارة المهام التشغيلية</h3>
            <p class="text-muted mb-0">اسحب وأفلت المهام بين الأعمدة لتغيير حالتها</p>
        </div>
        <div>
            <a href="javascript:history.back()" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-right"></i> العودة
            </a>
        </div>
    </div>


    @if(session('success')) <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div> @endif
    @if(session('error')) <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button class="btn-close" data-bs-dismiss="alert"></button></div> @endif


    <div class="task-info-bar">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div class="flex-grow-1">
                <div class="task-title">
                    <i class="fas fa-clipboard-list ml-2"></i>
                    {{ $majorTaskTitle ?? 'المهمة الرئيسية' }}
                </div>
                <div class="task-meta">
                    <span class="item">
                        <i class="fas fa-folder-open"></i>
                        <span class="lbl">المبادرة:</span>
                        <strong>{{ $initiativeName ?? 'غير محدد' }}</strong>
                    </span>
                    <span class="item">
                        <i class="far fa-calendar-alt"></i>
                        <span class="lbl">بداية المبادرة:</span>
                        <strong>{{ isset($initiativeStart) && $initiativeStart ? \Carbon\Carbon::parse($initiativeStart)->format('Y-m-d') : 'غير محدد' }}</strong>
                    </span>
                    <span class="item">
                        <i class="far fa-calendar-check"></i>
                        <span class="lbl">نهاية المبادرة:</span>
                        <strong>{{ isset($initiativeEnd) && $initiativeEnd ? \Carbon\Carbon::parse($initiativeEnd)->format('Y-m-d') : 'غير محدد' }}</strong>
                    </span>
                    <span class="item">
                        <i class="fas fa-clock"></i>
                        <span class="lbl">المدة المتوقعة:</span>
                        <strong>{{ $estimatedDays ?? 0 }} يوم</strong>
                    </span>
                    <span class="item">
                        <i class="fas fa-layer-group"></i>
                        <span class="lbl">مهام إدارتك:</span>
                        <strong>{{ count($allTasks ?? []) }}</strong>
                    </span>
                </div>
            </div>
            <div>
                <span class="status-pill {{ ($majorTaskIsActive ?? true) ? 'active' : 'inactive' }}">
                    <i class="fas fa-circle"></i>
                    {{ ($majorTaskIsActive ?? true) ? 'نشط' : 'غير نشط' }}
                </span>
            </div>
        </div>
    </div>


    <div class="kanban-container">
        <div class="kanban-column" data-status="16">
            <div class="kanban-column-header status-unassigned">
                <h6><span class="column-color color-unassigned"></span>معلقة مؤقتاً (غير موزعة)</h6>
                <span class="task-count">{{ count($unassignedTasks ?? []) }}</span>
            </div>
            <div class="drop-zone" id="drop-16">
                @forelse($unassignedTasks ?? [] as $task)
                @php
                    $taskId = $task['task_id'] ?? $task['id'] ?? 0;
                    $priority = $task['priority'] ?? 'medium';
                @endphp
                <div class="kanban-task" data-task-id="{{ $taskId }}" onclick="goToAssign({{ $taskId }})">
                    <div class="task-title">{{ $task['title'] ?? 'غير محدد' }}</div>
                    <div class="task-meta">
                        <span><i class="far fa-clock ml-1"></i> {{ $task['end_date'] ?? 'غير محدد' }}</span>
                        <span class="task-badge badge-priority-{{ $priority }}">
                            {{ $priority=='high'?'عالي':($priority == 'medium' ? 'متوسط' : 'منخفض') }}
                        </span>
                    </div>
                </div>
                @empty
                <div class="empty-message">
                    <i class="fas fa-inbox"></i>
                    <span>لا توجد مهام معلقة</span>
                </div>
                @endforelse
            </div>
        </div>


        <div class="kanban-column" data-status="26">
            <div class="kanban-column-header status-ready">
                <h6><span class="column-color color-ready"></span>جاهزة للعمل</h6>
                <span class="task-count">{{ count($readyTasks ?? []) }}</span>
            </div>
            <div class="drop-zone" id="drop-26">
                @forelse($readyTasks ?? [] as $task)
                @php
                    $taskId = $task['task_id'] ?? $task['id'] ?? 0;
                    $priority = $task['priority'] ?? 'medium';
                @endphp
                <div class="kanban-task draggable" draggable="true" data-task-id="{{ $taskId }}" onclick="goToTaskDetail({{ $taskId }})">
                    <div class="task-title">{{ $task['title'] ?? 'غير محدد' }}</div>
                    <div class="task-meta">
                        <span><i class="far fa-calendar-alt ml-1"></i> {{ $task['end_date'] ?? 'غير محدد' }}</span>
                        <span class="task-badge badge-priority-{{ $priority }}">
                            {{ $priority=='high'?'عالي':($priority == 'medium' ? 'متوسط' : 'منخفض') }}
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
                <div class="empty-message">
                    <i class="fas fa-inbox"></i>
                    <span>لا توجد مهام جاهزة للعمل</span>
                </div>
                @endforelse
            </div>
        </div>


        <div class="kanban-column" data-status="6">
            <div class="kanban-column-header status-progress">
                <h6><span class="column-color color-progress"></span>قيد التنفيذ</h6>
                <span class="task-count">{{ count($inProgressTasks ?? []) }}</span>
            </div>
            <div class="drop-zone" id="drop-6">
                @forelse($inProgressTasks ?? [] as $task)
                @php
                    $taskId = $task['task_id'] ?? $task['id'] ?? 0;
                    $priority = $task['priority'] ?? 'medium';
                @endphp
                <div class="kanban-task draggable" draggable="true" data-task-id="{{ $taskId }}" onclick="goToTaskDetail({{ $taskId }})">
                    <div class="task-title">{{ $task['title'] ?? 'غير محدد' }}</div>
                    <div class="task-meta">
                        <span><i class="far fa-calendar-alt ml-1"></i> {{ $task['end_date'] ?? 'غير محدد' }}</span>
                        <span class="task-badge badge-priority-{{ $priority }}">
                            {{ $priority=='high'?'عالي':($priority == 'medium' ? 'متوسط' : 'منخفض') }}
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
                <div class="empty-message">
                    <i class="fas fa-inbox"></i>
                    <span>لا توجد مهام قيد التنفيذ</span>
                </div>
                @endforelse
            </div>
        </div>


        <div class="kanban-column" data-status="5">
            <div class="kanban-column-header status-hold">
                <h6><span class="column-color color-hold"></span>معلق</h6>
                <span class="task-count">{{ count($holdTasks ?? []) }}</span>
            </div>
            <div class="drop-zone" id="drop-5">
                @forelse($holdTasks ?? [] as $task)
                @php
                    $taskId = $task['task_id'] ?? $task['id'] ?? 0;
                    $priority = $task['priority'] ?? 'medium';
                @endphp
                <div class="kanban-task draggable" draggable="true" data-task-id="{{ $taskId }}" onclick="goToTaskDetail({{ $taskId }})">
                    <div class="task-title">{{ $task['title'] ?? 'غير محدد' }}</div>
                    <div class="task-meta">
                        <span><i class="far fa-calendar-alt ml-1"></i> {{ $task['end_date'] ?? 'غير محدد' }}</span>
                        <span class="task-badge badge-priority-{{ $priority }}">
                            {{ $priority=='high'?'عالي':($priority == 'medium' ? 'متوسط' : 'منخفض') }}
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
                <div class="empty-message">
                    <i class="fas fa-inbox"></i>
                    <span>لا توجد مهام معلقة</span>
                </div>
                @endforelse
            </div>
        </div>


        <div class="kanban-column" data-status="8">
            <div class="kanban-column-header status-review">
                <h6><span class="column-color color-review"></span>مراجعة</h6>
                <span class="task-count">{{ count($reviewTasks ?? []) }}</span>
            </div>
            <div class="drop-zone" id="drop-8">
                @forelse($reviewTasks ?? [] as $task)
                @php
                    $taskId = $task['task_id'] ?? $task['id'] ?? 0;
                    $priority = $task['priority'] ?? 'medium';
                @endphp
                <div class="kanban-task draggable" draggable="true" data-task-id="{{ $taskId }}" onclick="goToTaskDetail({{ $taskId }})">
                    <div class="task-title">{{ $task['title'] ?? 'غير محدد' }}</div>
                    <div class="task-meta">
                        <span><i class="far fa-calendar-alt ml-1"></i> {{ $task['end_date'] ?? 'غير محدد' }}</span>
                        <span class="task-badge badge-priority-{{ $priority }}">
                            {{ $priority=='high'?'عالي':($priority == 'medium' ? 'متوسط' : 'منخفض') }}
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
                <div class="empty-message">
                    <i class="fas fa-inbox"></i>
                    <span>لا توجد مهام قيد المراجعة</span>
                </div>
                @endforelse
            </div>
        </div>


        <div class="kanban-column" data-status="19">
            <div class="kanban-column-header status-accepted">
                <h6><span class="column-color color-accepted"></span>مكتملة</h6>
                <span class="task-count">{{ count($acceptedTasks ?? []) }}</span>
            </div>
            <div class="drop-zone" id="drop-19">
                @forelse($acceptedTasks ?? [] as $task)
                @php
                    $taskId = $task['task_id'] ?? $task['id'] ?? 0;
                    $priority = $task['priority'] ?? 'medium';
                @endphp
                <div class="kanban-task" data-task-id="{{ $taskId }}" onclick="goToTaskDetail({{ $taskId }})">
                    <div class="task-title">{{ $task['title'] ?? 'غير محدد' }}</div>
                    <div class="task-meta">
                        <span><i class="far fa-calendar-alt ml-1"></i> {{ $task['end_date'] ?? 'غير محدد' }}</span>
                        <span class="task-badge badge-priority-{{ $priority }}">
                            {{ $priority=='high'?'عالي':($priority == 'medium' ? 'متوسط' : 'منخفض') }}
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
                <div class="empty-message">
                    <i class="fas fa-check-circle"></i>
                    <span>لا توجد مهام مكتملة</span>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="statusModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
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
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        border-radius: 16px;
        padding: 22px 26px;
        margin-bottom: 24px;
        border: 1px solid #e5eaf1;
        box-shadow: 0 2px 12px rgba(10, 46, 92, 0.05);
        position: relative;
        overflow: hidden;
    }
    .task-info-bar::before {
        content: '';
        position: absolute;
        top: 0; right: 0;
        width: 6px; height: 100%;
        background: linear-gradient(180deg, #d4af37 0%, #b8932a 100%);
    }
    .task-info-bar .task-title {
        font-size: 18px;
        font-weight: 700;
        color: #0a2e5c;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
    }
    .task-info-bar .task-title i {
        color: #d4af37;
        font-size: 20px;
    }
    .task-info-bar .task-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 10px 24px;
        font-size: 13px;
        color: #64748b;
    }
    .task-info-bar .task-meta .item {
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .task-info-bar .task-meta .item i {
        color: #d4af37;
        font-size: 13px;
        width: 16px;
        text-align: center;
    }
    .task-info-bar .task-meta .item .lbl {
        color: #94a3b8;
    }
    .task-info-bar .task-meta .item strong {
        color: #1a2332;
        font-weight: 600;
    }
    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    .status-pill i { font-size: 8px; }
    .status-pill.active {
        background: linear-gradient(135deg, #c6f6d5 0%, #9ae6b4 100%);
        color: #22543d;
    }
    .status-pill.inactive {
        background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e0 100%);
        color: #4a5568;
    }


    .kanban-container {
        display: grid;
        grid-template-columns: repeat(6, 1fr);
        gap: 14px;
        overflow-x: auto;
        min-height: 500px;
        padding-bottom: 16px;
    }
    .kanban-container::-webkit-scrollbar { height: 8px; }
    .kanban-container::-webkit-scrollbar-thumb {
        background: linear-gradient(90deg, #cbd5e0, #a0aec0);
        border-radius: 10px;
    }


    .kanban-column {
        background: #f7fafc;
        border-radius: 14px;
        border: 1px solid #e5eaf1;
        min-width: 240px;
        display: flex;
        flex-direction: column;
        max-height: 72vh;
        transition: all 0.25s ease;
        overflow: hidden;
    }
    .kanban-column:hover {
        box-shadow: 0 4px 18px rgba(10, 46, 92, 0.07);
    }


    .kanban-column-header {
        padding: 14px 16px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-shrink: 0;
        position: relative;
        overflow: hidden;
    }
    .kanban-column-header::after {
        content: '';
        position: absolute;
        bottom: 0; left: 0; right: 0;
        height: 3px;
        opacity: 0.4;
    }
    .kanban-column-header h6 {
        margin: 0;
        font-size: 13.5px;
        font-weight: 700;
        color: #fff;
        display: flex;
        align-items: center;
        gap: 8px;
        text-shadow: 0 1px 2px rgba(0,0,0,0.15);
    }
    .column-color {
        display: inline-block;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        border: 2px solid rgba(255,255,255,0.7);
        box-shadow: 0 0 0 2px rgba(255,255,255,0.3);
    }
    .task-count {
        background: rgba(255,255,255,0.3);
        color: #fff;
        padding: 2px 10px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 700;
        backdrop-filter: blur(4px);
        border: 1px solid rgba(255,255,255,0.4);
    }


    .status-unassigned { background: linear-gradient(135deg, #718096 0%, #4a5568 100%); }
    .status-ready      { background: linear-gradient(135deg, #38b2ac 0%, #2c7a7b 100%); }
    .status-progress   { background: linear-gradient(135deg, #4299e1 0%, #2b6cb0 100%); }
    .status-hold       { background: linear-gradient(135deg, #ecc94b 0%, #b7791f 100%); }
    .status-review     { background: linear-gradient(135deg, #ed8936 0%, #c05621 100%); }
    .status-accepted   { background: linear-gradient(135deg, #48bb78 0%, #2f855a 100%); }


    .color-unassigned { background: #cbd5e0; }
    .color-ready      { background: #81e6d9; }
    .color-progress   { background: #90cdf4; }
    .color-hold       { background: #faf089; }
    .color-review     { background: #fbd38d; }
    .color-accepted   { background: #9ae6b4; }


    .drop-zone {
        flex: 1;
        padding: 10px;
        overflow-y: auto;
        min-height: 80px;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .drop-zone::-webkit-scrollbar { width: 4px; }
    .drop-zone::-webkit-scrollbar-thumb {
        background: #cbd5e0;
        border-radius: 4px;
    }
    .drop-zone.drag-over {
        background: linear-gradient(135deg, #ebf8ff 0%, #bee3f8 100%);
        border: 2px dashed #4299e1;
        border-radius: 10px;
    }


    .kanban-task {
        background: #fff;
        border-radius: 10px;
        padding: 13px 14px;
        box-shadow: 0 1px 3px rgba(10, 46, 92, 0.06);
        border: 1px solid #e5eaf1;
        cursor: pointer;
        transition: all 0.2s ease;
        position: relative;
        border-right: 3px solid transparent;
    }
    .kanban-task:hover {
        box-shadow: 0 6px 18px rgba(10, 46, 92, 0.12);
        transform: translateY(-2px);
        border-right-color: #d4af37;
    }
    .kanban-task.draggable { cursor: grab; }
    .kanban-task.draggable:active { cursor: grabbing; }


    .kanban-task .task-title {
        font-size: 13.5px;
        font-weight: 600;
        color: #1a2332;
        margin-bottom: 8px;
        line-height: 1.4;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .kanban-task .task-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 11.5px;
        color: #64748b;
        flex-wrap: wrap;
        gap: 6px;
    }
    .kanban-task .task-meta span {
        display: inline-flex;
        align-items: center;
    }
    .kanban-task .task-meta i {
        color: #94a3b8;
        margin-left: 4px;
        font-size: 11px;
    }


    .task-badge {
        padding: 3px 9px;
        border-radius: 8px;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.3px;
    }
    .badge-priority-high {
        background: linear-gradient(135deg, #fed7d7 0%, #feb2b2 100%);
        color: #822727;
        border: 1px solid #fc8181;
    }
    .badge-priority-medium {
        background: linear-gradient(135deg, #fefcbf 0%, #faf089 100%);
        color: #744210;
        border: 1px solid #ecc94b;
    }
    .badge-priority-low {
        background: linear-gradient(135deg, #c6f6d5 0%, #9ae6b4 100%);
        color: #22543d;
        border: 1px solid #68d391;
    }


    .task-assignee {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-top: 10px;
        font-size: 12px;
        color: #4a5568;
        padding-top: 10px;
        border-top: 1px dashed #e5eaf1;
        font-weight: 500;
    }
    .task-assignee .avatar {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 26px;
        height: 26px;
        border-radius: 8px;
        background: linear-gradient(135deg, #0a2e5c 0%, #2a6ab0 100%);
        color: #d4af37;
        font-size: 11px;
        font-weight: 700;
        flex-shrink: 0;
        box-shadow: 0 2px 6px rgba(10, 46, 92, 0.2);
    }


    .task-progress {
        margin-top: 10px;
        background: #edf2f7;
        border-radius: 10px;
        height: 22px;
        overflow: hidden;
        position: relative;
    }
    .task-progress .progress-bar {
        height: 100%;
        background: linear-gradient(90deg, #4299e1 0%, #3182ce 100%);
        border-radius: 10px;
        transition: width 0.4s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        font-weight: 700;
        color: #fff;
        white-space: nowrap;
        box-shadow: inset 0 -1px 0 rgba(0,0,0,0.08);
    }
    .task-progress .progress-bar.bg-success {
        background: linear-gradient(90deg, #48bb78 0%, #38a169 100%);
    }


    .empty-message {
        text-align: center;
        padding: 28px 12px;
        color: #cbd5e0;
        font-size: 12px;
        background: #fff;
        border-radius: 10px;
        border: 2px dashed #e5eaf1;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
    }
    .empty-message i {
        font-size: 28px;
        opacity: 0.5;
    }
    .empty-message span {
        font-weight: 500;
    }


    .loading-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255,255,255,0.85);
        backdrop-filter: blur(3px);
        z-index: 9999;
        align-items: center;
        justify-content: center;
    }
    .loading-overlay .spinner-border {
        width: 48px;
        height: 48px;
    }


    #statusModal .modal-content {
        border-radius: 16px;
        border: none;
        box-shadow: 0 20px 60px rgba(10, 46, 92, 0.25);
    }
    #statusModal .modal-header {
        border-radius: 16px 16px 0 0;
        padding: 16px 22px;
        background: linear-gradient(135deg, #0a2e5c 0%, #2a6ab0 100%) !important;
    }
    #statusModal .modal-body { padding: 22px; }
    #statusModal .modal-footer {
        padding: 14px 22px;
        border-top: 1px solid #e5eaf1;
    }


    @media (max-width: 1400px) {
        .kanban-container { grid-template-columns: repeat(4, 1fr); }
    }
    @media (max-width: 992px) {
        .kanban-container { grid-template-columns: repeat(3, 1fr); }
    }
    @media (max-width: 768px) {
        .kanban-container { grid-template-columns: repeat(2, 1fr); }
        .task-info-bar .task-meta {
            flex-direction: column;
            gap: 6px;
        }
        .kanban-column { min-width: 180px; }
    }
    @media (max-width: 480px) {
        .kanban-container { grid-template-columns: 1fr; }
    }
</style>
@endpush


@push('scripts')
<script>
    var currentTaskId = null;
    var currentStatusId = null;


    document.addEventListener('DOMContentLoaded', function() {
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
            16: 'معلقة مؤقتاً',
            26: 'جاهزة للعمل',
            6: 'قيد التنفيذ',
            5: 'معلق',
            7: 'متأخرة',
            8: 'مراجعة',
            19: 'مكتملة'
        };
        return names[statusId] || 'غير معروفة';
    }


    function openStatusModal(taskId, statusId) {
        currentTaskId = taskId;
        currentStatusId = statusId;


        document.getElementById('targetTaskId').value = taskId;
        document.getElementById('targetStatusId').value = statusId;
        document.getElementById('taskComment').value = '';
        document.getElementById('commentError').style.display = 'none';


        var infoText = 'نقل المهمة إلى حالة: ' + getStatusName(statusId);
        if (statusId === 6) {
            infoText += ' (ستصبح نسبة التقدم تلقائياً: 25%)';
        } else if (statusId === 26) {
            infoText += ' (ستصبح نسبة التقدم تلقائياً: 10%)';
        } else if (statusId === 16) {
            infoText += ' (ستصبح نسبة التقدم تلقائياً: 0%)';
        } else if (statusId === 5 || statusId === 7) {
            infoText += ' (ستحتفظ بنسبة التقدم الحالية)';
        } else if (statusId === 8) {
            infoText += ' (ستصبح نسبة التقدم تلقائياً: 80%)';
        } else if (statusId === 19) {
            infoText += ' (ستصبح نسبة التقدم تلقائياً: 100%)';
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


        if (!comment) {
            document.getElementById('commentError').style.display = 'block';
            return;
        }
        document.getElementById('commentError').style.display = 'none';


        document.getElementById('loadingOverlay').style.display = 'flex';


        var data = {
            task_id: parseInt(taskId),
            status_id: statusId,
            comment: comment
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

@extends('layouts.app')

@section('title', 'لوحة إدارة المهام')

@push('styles')
<style>
    .kanban-container {
        display: flex;
        gap: 16px;
        overflow-x: auto;
        padding-bottom: 20px;
        min-height: 600px;
    }
    .kanban-column {
        background: #f1f5f9;
        border-radius: 16px;
        min-width: 300px;
        max-width: 320px;
        flex: 1;
        padding: 16px;
    }
    .kanban-column-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
        padding-bottom: 8px;
        border-bottom: 2px solid #e2e8f0;
    }
    .kanban-column-header h6 {
        font-weight: 700;
        margin: 0;
    }
    .task-count {
        background: #fff;
        padding: 2px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    .kanban-task {
        background: #fff;
        border-radius: 12px;
        padding: 14px;
        margin-bottom: 10px;
        cursor: grab;
        transition: all 0.2s;
        border: 1px solid #e2e8f0;
    }
    .kanban-task:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        transform: translateY(-2px);
    }
    .kanban-task.dragging {
        opacity: 0.5;
        cursor: grabbing;
    }
    .kanban-task h6 {
        font-size: 14px;
        margin-bottom: 6px;
    }
    .kanban-task .meta {
        font-size: 11px;
        color: #718096;
    }
    .drop-zone {
        min-height: 80px;
        border: 2px dashed transparent;
        border-radius: 8px;
        transition: all 0.2s;
    }
    .drop-zone.active {
        border-color: #d4af37;
        background: #fffbeb;
    }
    .column-color {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        display: inline-block;
        margin-left: 6px;
    }
    .color-todo { background: #e2e8f0; }
    .color-progress { background: #3182ce; }
    .color-pending { background: #e53e3e; }
    .color-review { background: #d4af37; }
    .color-completed { background: #38a169; }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3><i class="fas fa-tasks ml-2"></i>لوحة إدارة المهام</h3>
            <p class="text-muted mb-0">اسحب وأفلت المهام بين الأعمدة لتغيير حالتها</p>
        </div>
        <a href="{{ route('operational.major-tasks') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-right"></i> المهام الرئيسية
        </a>
    </div>

    <div class="kanban-container">
        {{-- عمود جاهزة للتوزيع --}}
        <div class="kanban-column" data-status="16">
            <div class="kanban-column-header">
                <h6><span class="column-color color-todo"></span>جاهزة للتوزيع</h6>
                <span class="task-count">{{ count($todoTasks) }}</span>
            </div>
            <div class="drop-zone" id="drop-16">
                @foreach($todoTasks as $task)
                <div class="kanban-task" draggable="true" data-task-id="{{ $task['id'] }}">
                    <h6>{{ $task['task_name'] ?? $task['title'] ?? '' }}</h6>
                    <div class="meta">
                        <div>{{ $task['assigned_to_name'] ?? 'غير معين' }}</div>
                        <div><i class="far fa-clock ml-1"></i> {{ $task['end_date'] ?? 'غير محدد' }}</div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- عمود قيد التنفيذ --}}
        <div class="kanban-column" data-status="6">
            <div class="kanban-column-header">
                <h6><span class="column-color color-progress"></span>قيد التنفيذ</h6>
                <span class="task-count">{{ count($inProgressTasks) }}</span>
            </div>
            <div class="drop-zone" id="drop-6">
                @foreach($inProgressTasks as $task)
                <div class="kanban-task" draggable="true" data-task-id="{{ $task['id'] }}">
                    <h6>{{ $task['task_name'] ?? $task['title'] ?? '' }}</h6>
                    <div class="meta">
                        <div>{{ $task['assigned_to_name'] ?? 'غير معين' }}</div>
                        <div><i class="far fa-clock ml-1"></i> {{ $task['end_date'] ?? 'غير محدد' }}</div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- عمود معلق مؤقتاً --}}
        <div class="kanban-column" data-status="16">
            <div class="kanban-column-header">
                <h6><span class="column-color color-pending"></span>معلق مؤقتاً</h6>
                <span class="task-count">{{ count($pendingTasks) }}</span>
            </div>
            <div class="drop-zone" id="drop-7">
                @foreach($pendingTasks as $task)
                <div class="kanban-task" draggable="true" data-task-id="{{ $task['id'] }}">
                    <h6>{{ $task['task_name'] ?? $task['title'] ?? '' }}</h6>
                    <div class="meta">
                        <div>{{ $task['assigned_to_name'] ?? 'غير معين' }}</div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- عمود المراجعة --}}
        <div class="kanban-column" data-status="8">
            <div class="kanban-column-header">
                <h6><span class="column-color color-review"></span>قيد المراجعة</h6>
                <span class="task-count">{{ count($reviewTasks) }}</span>
            </div>
            <div class="drop-zone" id="drop-8">
                @foreach($reviewTasks as $task)
                <div class="kanban-task" draggable="true" data-task-id="{{ $task['id'] }}">
                    <h6>{{ $task['task_name'] ?? $task['title'] ?? '' }}</h6>
                    <div class="meta">
                        <div>{{ $task['assigned_to_name'] ?? 'غير معين' }}</div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- عمود مكتمل --}}
        <div class="kanban-column" data-status="4">
            <div class="kanban-column-header">
                <h6><span class="column-color color-completed"></span>مكتمل</h6>
                <span class="task-count">{{ count($completedTasks) }}</span>
            </div>
            <div class="drop-zone" id="drop-4">
                @foreach($completedTasks as $task)
                <div class="kanban-task" draggable="true" data-task-id="{{ $task['id'] }}">
                    <h6>{{ $task['task_name'] ?? $task['title'] ?? '' }}</h6>
                    <div class="meta">
                        <div>{{ $task['assigned_to_name'] ?? 'غير معين' }}</div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

{{-- Modal لإضافة تعليق --}}
<div class="modal fade" id="commentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">إضافة تعليق</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label">التعليق</label>
                <textarea id="taskComment" class="form-control" rows="3" placeholder="أدخل تعليقاً على تغيير الحالة..."></textarea>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button class="btn btn-primary" onclick="confirmStatusChange()">تأكيد</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let draggedTaskId = null;
let targetStatusId = null;

document.querySelectorAll('.kanban-task').forEach(task => {
    task.addEventListener('dragstart', function() {
        draggedTaskId = this.getAttribute('data-task-id');
        this.classList.add('dragging');
    });
    task.addEventListener('dragend', function() {
        this.classList.remove('dragging');
    });
    
    task.addEventListener('click', function(e) {
        if (this.classList.contains('dragging')) return;
        
        const taskId = this.getAttribute('data-task-id');
        window.location.href = '/operational/task/' + taskId;
    });
    
    task.style.cursor = 'pointer';
});


document.querySelectorAll('.drop-zone').forEach(zone => {
    zone.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.classList.add('active');
    });
    zone.addEventListener('dragleave', function() {
        this.classList.remove('active');
    });
    zone.addEventListener('drop', function(e) {
        e.preventDefault();
        this.classList.remove('active');
        targetStatusId = this.id.replace('drop-', '');
        const taskElement = document.querySelector(`[data-task-id="${draggedTaskId}"]`);
        if (taskElement) {
            this.appendChild(taskElement);
            showCommentModal();
        }
    });
});

function showCommentModal() {
    new bootstrap.Modal(document.getElementById('commentModal')).show();
}

function confirmStatusChange() {
    const comment = document.getElementById('taskComment').value;
    const token = document.querySelector('meta[name="csrf-token"]').content;

    fetch(`/operational/update-status/${draggedTaskId}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token
        },
        body: JSON.stringify({
            status_id: parseInt(targetStatusId),
            comment: comment
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('فشل تغيير الحالة: ' + (data.detail || 'خطأ'));
        }
    })
    .catch(() => alert('خطأ في الاتصال'));

    bootstrap.Modal.getInstance(document.getElementById('commentModal')).hide();
    document.getElementById('taskComment').value = '';
}
</script>
@endpush

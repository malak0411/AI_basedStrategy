@extends('layouts.app')

@section('title', 'توزيع المهام على الموظفين')

@push('styles')
<style>
    .assign-container {
        display: flex;
        gap: 20px;
        min-height: 600px;
    }
    .tasks-section {
        flex: 2;
    }
    .employees-section {
        flex: 1;
        background: #f8fafc;
        border-radius: 16px;
        padding: 16px;
    }
    .task-card {
        background: #fff;
        border-radius: 12px;
        padding: 14px;
        margin-bottom: 10px;
        border: 1px solid #e2e8f0;
        cursor: pointer;
        transition: all 0.2s;
    }
    .task-card:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
    .task-card.selected { border: 2px solid #d4af37; background: #fffbeb; }
    .employee-card {
        background: #fff;
        border-radius: 12px;
        padding: 14px;
        margin-bottom: 10px;
        border: 1px solid #e2e8f0;
        cursor: grab;
        transition: all 0.2s;
    }
    .employee-card:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
    .employee-card.dragging { opacity: 0.5; }
    .assigned-employee {
        background: #ebf8ff;
        border-radius: 8px;
        padding: 8px 12px;
        margin-bottom: 6px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .drop-zone {
        min-height: 60px;
        border: 2px dashed #e2e8f0;
        border-radius: 8px;
        padding: 8px;
        margin-top: 10px;
    }
    .drop-zone.active { border-color: #d4af37; background: #fffbeb; }
    .lead-badge { background: #d4af37; color: #1a1a2e; padding: 2px 8px; border-radius: 10px; font-size: 10px; }
    .support-badge { background: #e2e8f0; color: #4a5568; padding: 2px 8px; border-radius: 10px; font-size: 10px; }
    .workload-bar { height: 6px; border-radius: 3px; background: #e2e8f0; overflow: hidden; }
    .workload-fill { height: 100%; border-radius: 3px; transition: width 0.3s; }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3><i class="fas fa-user-check ml-2"></i>توزيع المهام على الموظفين</h3>
            <p class="text-muted mb-0">{{ $majorTask['name'] ?? '' }}</p>
        </div>
        <a href="{{ route('operational.show-major-task', $majorTask['id'] ?? 0) }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-right"></i> العودة
        </a>
    </div>

    <div class="assign-container">
        <div class="tasks-section">
            <h5>المهام التشغيلية</h5>
            <p class="text-muted small">اختر مهمة لعرض الموظفين المسندين إليها</p>
            
            @foreach($tasks as $task)
            <div class="task-card" data-task-id="{{ $task['id'] }}" onclick="selectTask(this, {{ $task['id'] }})">
                <h6>{{ $task['task_name'] ?? $task['title'] ?? '' }}</h6>
                <p class="text-muted small mb-2">{{ Str::limit($task['description'] ?? '', 80) }}</p>
                <div class="d-flex gap-3">
                    <small><i class="far fa-clock ml-1"></i> {{ $task['estimated_hours'] ?? 0 }} ساعة</small>
                    <small><i class="far fa-calendar-alt ml-1"></i> {{ $task['end_date'] ?? '' }}</small>
                </div>
                <div class="drop-zone" id="drop-{{ $task['id'] }}">
                    @php
                        $taskAssignments = $task['assignments'] ?? [];
                    @endphp
                    @foreach($taskAssignments as $assign)
                    <div class="assigned-employee">
                        <div>
                            <strong>{{ $assign['employee_name'] ?? '' }}</strong>
                            <br><small>{{ $assign['role_name'] ?? '' }}</small>
                        </div>
                        <button class="btn btn-sm btn-outline-danger" onclick="removeAssignment({{ $task['id'] }}, {{ $assign['employee_id'] }})">&times;</button>
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>

        <div class="employees-section">
            <h5>موظفي الإدارة</h5>
            <p class="text-muted small">اسحب الموظف إلى مهمة</p>
            
            @foreach($employees as $emp)
            @php
                $workload = $emp['workload_percent'] ?? 0;
                $assignedTasks = $emp['assigned_tasks'] ?? 0;
                $assignedHours = $emp['assigned_hours'] ?? 0;
            @endphp
            <div class="employee-card" draggable="true" data-employee-id="{{ $emp['employee_id'] }}">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <strong>{{ $emp['full_name'] }}</strong>
                        <br><small class="text-muted">{{ $emp['job_title'] ?? '' }}</small>
                    </div>
                    <span class="badge bg-info">{{ $assignedTasks }} مهام</span>
                </div>
                <div class="mb-2">
                    <div class="d-flex justify-content-between small mb-1">
                        <span>{{ $assignedHours }} ساعة</span>
                        <span>{{ $workload }}%</span>
                    </div>
                    <div class="workload-bar">
                        <div class="workload-fill bg-{{ $workload > 80 ? 'danger' : ($workload > 50 ? 'warning' : 'success') }}" 
                             style="width: {{ $workload }}%"></div>
                    </div>
                </div>
                <div class="d-none" id="role-select-{{ $emp['employee_id'] }}">
                    <label class="small">الدور عند الإسناد:</label>
                    <select class="form-select form-select-sm" id="role-type-{{ $emp['employee_id'] }}">
                        @foreach($roleTypes as $rt)
                        <option value="{{ $rt['role_type_id'] }}">{{ $rt['name_ar'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let selectedTaskId = null;
let draggedEmployeeId = null;

function selectTask(el, taskId) {
    document.querySelectorAll('.task-card').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');
    selectedTaskId = taskId;
}

document.querySelectorAll('.employee-card').forEach(card => {
    card.addEventListener('dragstart', function() {
        draggedEmployeeId = this.getAttribute('data-employee-id');
        this.classList.add('dragging');
    });
    card.addEventListener('dragend', function() {
        this.classList.remove('dragging');
    });
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
        const taskId = this.id.replace('drop-', '');
        assignEmployee(taskId, draggedEmployeeId);
    });
});

function assignEmployee(taskId, employeeId) {
    const roleTypeId = document.getElementById('role-type-' + employeeId)?.value || 1;
    const token = document.querySelector('meta[name="csrf-token"]').content;
    
    fetch('/api/tasks/' + taskId + '/assign', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': token},
        body: JSON.stringify({employee_id: parseInt(employeeId), role_type_id: parseInt(roleTypeId)})
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) location.reload();
        else alert('فشل الإسناد');
    });
}

function removeAssignment(taskId, employeeId) {
    if (!confirm('إزالة الموظف من المهمة؟')) return;
    const token = document.querySelector('meta[name="csrf-token"]').content;
    fetch('/api/tasks/' + taskId + '/assign/' + employeeId, {
        method: 'DELETE',
        headers: {'X-CSRF-TOKEN': token}
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) location.reload();
        else alert('فشل الإزالة');
    });
}
</script>
@endpush

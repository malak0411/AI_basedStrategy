@extends('layouts.app')

@section('title', 'توزيع المهمة التشغيلية')

@push('styles')
<style>
    .assign-container {
        display: flex;
        gap: 16px;
        overflow-x: auto;
        padding-bottom: 20px;
        min-height: 600px;
    }
    .assign-column {
        background: #f1f5f9;
        border-radius: 16px;
        min-width: 300px;
        max-width: 340px;
        flex: 1;
        padding: 16px;
    }
    .assign-column-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
        padding-bottom: 8px;
        border-bottom: 2px solid #e2e8f0;
    }
    .assign-column-header h6 {
        font-weight: 700;
        margin: 0;
    }
    .assign-column-header h6 .color-dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        display: inline-block;
        margin-left: 6px;
    }
    .color-unassigned { background: #a0aec0; }
    .color-responsible { background: #d4af37; }
    .color-members { background: #3182ce; }
    .count-badge {
        background: #fff;
        padding: 2px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    .drop-zone {
        min-height: 100px;
        border: 2px dashed transparent;
        border-radius: 8px;
        transition: all 0.2s;
        padding: 4px;
    }
    .drop-zone.active {
        border-color: #d4af37;
        background: #fffbeb;
    }
    .drop-zone .empty-message {
        color: #a0aec0;
        text-align: center;
        padding: 30px 0;
        font-size: 14px;
    }
    .employee-card {
        background: #fff;
        border-radius: 12px;
        padding: 12px 14px;
        margin-bottom: 10px;
        cursor: grab;
        transition: all 0.2s;
        border: 1px solid #e2e8f0;
    }
    .employee-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        transform: translateY(-2px);
    }
    .employee-card.dragging {
        opacity: 0.5;
        cursor: grabbing;
    }
    .employee-card .card-body {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .employee-card .avatar {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        background: #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 16px;
        color: #4a5568;
        flex-shrink: 0;
    }
    .employee-card .info {
        flex: 1;
        min-width: 0;
    }
    .employee-card .info .name {
        font-weight: 600;
        font-size: 14px;
        color: #2d3748;
    }
    .employee-card .info .title {
        font-size: 12px;
        color: #718096;
    }
    .employee-card .stats {
        display: flex;
        gap: 8px;
        margin-top: 4px;
        flex-wrap: wrap;
    }
    .employee-card .stats span {
        background: #f1f5f9;
        padding: 1px 8px;
        border-radius: 12px;
        font-size: 10px;
        color: #4a5568;
    }
    .employee-card .badge-role {
        padding: 2px 10px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 600;
        flex-shrink: 0;
    }
    .badge-accountable {
        background: #d4af37;
        color: #1a1a2e;
    }
    .badge-responsible {
        background: #3182ce;
        color: #fff;
    }
    .badge-consulted {
        background: #9c27b0;
        color: #fff;
    }
    .badge-informed {
        background: #38a169;
        color: #fff;
    }
    .badge-approver {
        background: #ed8936;
        color: #fff;
    }
    .employee-card .actions {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-shrink: 0;
    }
    .employee-card .actions select {
        padding: 2px 4px;
        font-size: 11px;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        background: #fff;
        max-width: 90px;
    }
    .employee-card .actions select:focus {
        border-color: #d4af37;
        outline: none;
    }
    .employee-card .actions .hours-input {
        width: 55px;
        padding: 2px 4px;
        font-size: 11px;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        text-align: center;
    }
    .employee-card .actions .hours-input:focus {
        border-color: #d4af37;
        outline: none;
    }
    .employee-card .actions .remove-btn {
        color: #e53e3e;
        cursor: pointer;
        padding: 2px 6px;
        border-radius: 4px;
        transition: all 0.2s;
    }
    .employee-card .actions .remove-btn:hover {
        background: #fed7d7;
    }
    .task-info-bar {
        background: #fff;
        border-radius: 16px;
        padding: 20px 24px;
        border: 1px solid #e2e8f0;
        margin-bottom: 24px;
    }
    .task-info-bar .task-title {
        font-size: 18px;
        font-weight: 700;
    }
    .task-info-bar .task-meta {
        display: flex;
        gap: 24px;
        flex-wrap: wrap;
        margin-top: 8px;
    }
    .task-info-bar .task-meta .item {
        font-size: 13px;
        color: #4a5568;
    }
    .task-info-bar .task-meta .item strong {
        color: #2d3748;
    }
    .task-info-bar .badge-status {
        padding: 4px 14px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 600;
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
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3><i class="fas fa-user-plus ml-2"></i>توزيع المهمة التشغيلية</h3>
            <p class="text-muted mb-0">اسحب وأفلت الموظفين لتوزيعهم حسب الأدوار</p>
        </div>
        <div>
            <a href="{{ route('operational.show-major-task', $task['major_task_id'] ?? 0) }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-right"></i> العودة
            </a>
        </div>
    </div>

    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif

    <div class="task-info-bar">
        <div class="d-flex justify-content-between align-items-start flex-wrap">
            <div>
                <div class="task-title">{{ $task['title'] ?? 'غير محدد' }}</div>
                <div class="task-meta">
                    <span class="item"><i class="far fa-calendar-alt ml-1"></i> البداية: <strong>{{ $task['start_date'] ?? 'غير محدد' }}</strong></span>
                    <span class="item"><i class="far fa-calendar-check ml-1"></i> التسليم: <strong>{{ $task['end_date'] ?? 'غير محدد' }}</strong></span>
                    <span class="item"><i class="fas fa-clock ml-1"></i> الساعات: <strong>{{ $task['estimated_hours'] ?? 0 }}</strong></span>
                    <span class="item"><i class="fas fa-users ml-1"></i> الموزعين: <strong>{{ count($currentAssignments ?? []) }}</strong></span>
                </div>
            </div>
            <div>
                <span class="badge-status bg-info text-white">{{ $task['status_name'] ?? 'معلق مؤقتاً' }}</span>
                <span class="badge-status bg-warning text-dark ms-1">{{ $task['priority_name'] ?? 'متوسطة' }}</span>
            </div>
        </div>
    </div>

    <div class="assign-container">
        {{-- عمود غير موزعين --}}
        <div class="assign-column">
            <div class="assign-column-header">
                <h6><span class="color-dot color-unassigned"></span>غير موزعين</h6>
                <span class="count-badge">{{ count($unassignedEmployees) }}</span>
            </div>
            <div class="drop-zone" id="drop-unassigned" ondrop="dropHandler(event)" ondragover="dragOverHandler(event)">
                @forelse($unassignedEmployees as $emp)
                <div class="employee-card" draggable="true" data-employee-id="{{ $emp['employee_id'] ?? 0 }}" data-employee-name="{{ $emp['full_name'] ?? '' }}" data-employee-title="{{ $emp['job_title'] ?? '' }}" data-total-tasks="{{ $emp['total_tasks'] ?? 0 }}" data-total-hours="{{ $emp['total_hours'] ?? 0 }}" data-current-tasks="{{ $emp['current_tasks'] ?? 0 }}" data-current-hours="{{ $emp['current_hours'] ?? 0 }}" ondragstart="dragStartHandler(event)">
                    <div class="card-body">
                        <div class="avatar">{{ mb_substr($emp['full_name'] ?? 'م', 0, 1, 'UTF-8') }}</div>
                        <div class="info">
                            <div class="name">{{ $emp['full_name'] ?? 'غير محدد' }}</div>
                            <div class="title">{{ $emp['job_title'] ?? 'موظف' }}</div>
                            <div class="stats">
                                <span><i class="fas fa-tasks"></i> {{ $emp['total_tasks'] ?? 0 }}</span>
                                <span><i class="fas fa-clock"></i> {{ number_format($emp['total_hours'] ?? 0) }}</span>
                                <span><i class="fas fa-spinner"></i> {{ $emp['current_tasks'] ?? 0 }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                @empty
                <div class="empty-message">جميع الموظفين موزعين</div>
                @endforelse
            </div>
        </div>

        {{-- عمود المسؤول النهائي --}}
        <div class="assign-column">
            <div class="assign-column-header">
                <h6><span class="color-dot color-responsible"></span>المسؤول النهائي</h6>
                <span class="count-badge">{{ count($responsibleAssignments) }}</span>
            </div>
            <div class="drop-zone" id="drop-responsible" ondrop="dropHandler(event)" ondragover="dragOverHandler(event)">
                @forelse($responsibleAssignments as $assign)
                @php $emp = $assign['employee'] ?? []; @endphp
                <div class="employee-card" draggable="true" data-employee-id="{{ $emp['employee_id'] ?? 0 }}" data-assignment-id="{{ $assign['assignment_id'] ?? 0 }}" data-role-type-id="2" ondragstart="dragStartHandler(event)">
                    <div class="card-body">
                        <div class="avatar">{{ mb_substr($emp['full_name'] ?? 'م', 0, 1, 'UTF-8') }}</div>
                        <div class="info">
                            <div class="name">{{ $emp['full_name'] ?? 'غير محدد' }}</div>
                            <div class="title">{{ $emp['job_title'] ?? 'موظف' }}</div>
                            <div class="stats">
                                <span><i class="fas fa-tasks"></i> {{ $emp['total_tasks'] ?? 0 }}</span>
                                <span><i class="fas fa-clock"></i> {{ number_format($emp['total_hours'] ?? 0) }}</span>
                            </div>
                        </div>
                        <div class="actions">
                            <span class="badge-role badge-accountable">مسؤول نهائي</span>
                            <span class="remove-btn" onclick="removeAssignment({{ $assign['assignment_id'] ?? 0 }})"><i class="fas fa-times"></i></span>
                        </div>
                    </div>
                </div>
                @empty
                <div class="empty-message">اسحب موظف هنا كمسؤول نهائي</div>
                @endforelse
            </div>
        </div>

        {{-- عمود أعضاء الفريق --}}
        <div class="assign-column">
            <div class="assign-column-header">
                <h6><span class="color-dot color-members"></span>أعضاء الفريق</h6>
                <span class="count-badge">{{ count($memberAssignments) }}</span>
            </div>
            <div class="drop-zone" id="drop-members" ondrop="dropMemberHandler(event)" ondragover="dragOverHandler(event)">
                @forelse($memberAssignments as $assign)
                @php $emp = $assign['employee'] ?? []; @endphp
                <div class="employee-card" draggable="true" data-employee-id="{{ $emp['employee_id'] ?? 0 }}" data-assignment-id="{{ $assign['assignment_id'] ?? 0 }}" data-role-type-id="{{ $assign['role_type_id'] ?? 1 }}" ondragstart="dragStartHandler(event)">
                    <div class="card-body">
                        <div class="avatar">{{ mb_substr($emp['full_name'] ?? 'م', 0, 1, 'UTF-8') }}</div>
                        <div class="info">
                            <div class="name">{{ $emp['full_name'] ?? 'غير محدد' }}</div>
                            <div class="title">{{ $emp['job_title'] ?? 'موظف' }}</div>
                            <div class="stats">
                                <span><i class="fas fa-tasks"></i> {{ $emp['total_tasks'] ?? 0 }}</span>
                                <span><i class="fas fa-clock"></i> {{ number_format($emp['total_hours'] ?? 0) }}</span>
                            </div>
                        </div>
                        <div class="actions">
                            <select class="role-select" data-assignment-id="{{ $assign['assignment_id'] ?? 0 }}" data-employee-id="{{ $emp['employee_id'] ?? 0 }}" onchange="updateRole(this)">
                                @foreach($roleTypes as $role)
                                    <option value="{{ $role['role_type_id'] }}" {{ ($role['role_type_id'] == ($assign['role_type_id'] ?? 1)) ? 'selected' : '' }} {{ $role['role_type_id'] == 2 ? 'disabled' : '' }}>
                                        {{ $role['name_ar'] }}
                                    </option>
                                @endforeach
                            </select>
                            <input type="number" class="hours-input" data-assignment-id="{{ $assign['assignment_id'] ?? 0 }}" value="{{ $assign['estimated_hours'] ?? 0 }}" min="1" max="720" onchange="updateHours(this)" placeholder="س">
                            <span class="remove-btn" onclick="removeAssignment({{ $assign['assignment_id'] ?? 0 }})"><i class="fas fa-times"></i></span>
                        </div>
                    </div>
                </div>
                @empty
                <div class="empty-message">اسحب موظف هنا كعضو</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="loading-overlay" id="loadingOverlay">
    <div class="spinner-border text-primary" role="status"><span class="visually-hidden">جاري...</span></div>
</div>

{{-- Modal اختيار الدور والساعات --}}
<div class="modal fade" id="assignModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-user-plus ml-2"></i>توزيع الموظف</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="fw-bold">الدور</label>
                    <select id="modalRoleType" class="form-control">
                        @foreach($roleTypes as $role)
                            <option value="{{ $role['role_type_id'] }}" {{ $role['role_type_id'] == 1 ? 'selected' : '' }} {{ $role['role_type_id'] == 2 ? 'disabled' : '' }}>
                                {{ $role['name_ar'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="fw-bold">الساعات المقدرة</label>
                    <input type="number" id="modalEstimatedHours" class="form-control" value="{{ $task['estimated_hours'] ?? 40 }}" min="1" max="720">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-primary" onclick="confirmAssign({{ $task['task_id'] ?? $task['id'] ?? 0 }})">توزيع</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let draggedTaskId = null;
    let draggedEmployeeId = null;
    let targetDropZone = null;

    // ============================================================
    // Drag & Drop Handlers
    // ============================================================
    function dragStartHandler(event) {
        draggedTaskId = event.target.closest('.employee-card').getAttribute('data-employee-id');
        event.target.closest('.employee-card').classList.add('dragging');
        event.dataTransfer.effectAllowed = 'move';
    }

    function dragOverHandler(event) {
        event.preventDefault();
        event.target.closest('.drop-zone').classList.add('active');
    }

    document.querySelectorAll('.drop-zone').forEach(zone => {
        zone.addEventListener('dragleave', function() {
            this.classList.remove('active');
        });
        zone.addEventListener('dragend', function() {
            this.classList.remove('active');
            document.querySelectorAll('.employee-card').forEach(el => el.classList.remove('dragging'));
        });
    });

    function dropHandler(event) {
        event.preventDefault();
        const dropZone = event.target.closest('.drop-zone');
        dropZone.classList.remove('active');

        const employeeId = draggedTaskId;
        if (!employeeId) return;

        const column = dropZone.closest('.assign-column');
        const isResponsible = column.querySelector('.assign-column-header h6').innerText.includes('المسؤول النهائي');
        const isUnassigned = column.querySelector('.assign-column-header h6').innerText.includes('غير موزعين');

        if (isUnassigned) {
            const card = document.querySelector(`.employee-card[data-employee-id="${employeeId}"]`);
            const assignmentId = card ? card.dataset.assignmentId : null;
            if (assignmentId) {
                removeAssignment(assignmentId);
            }
            return;
        }

        if (isResponsible) {
            const existing = document.querySelector('#drop-responsible .employee-card');
            if (existing) {
                showToast('يوجد مسؤول نهائي بالفعل', 'warning');
                return;
            }
            assignEmployee(employeeId, 2);
        }
    }

    function dropMemberHandler(event) {
        event.preventDefault();
        const dropZone = event.target.closest('.drop-zone');
        dropZone.classList.remove('active');

        const employeeId = draggedTaskId;
        if (!employeeId) return;

        const card = document.querySelector(`.employee-card[data-employee-id="${employeeId}"]`);
        if (card && card.dataset.assignmentId) {
            // موجود بالفعل في عضو - تحديث الدور
            showAssignModal(employeeId);
            return;
        }

        showAssignModal(employeeId);
    }

    // ============================================================
    // Assign Functions
    // ============================================================
    let pendingEmployeeId = null;

    function showAssignModal(employeeId) {
        pendingEmployeeId = employeeId;
        document.getElementById('modalEstimatedHours').value = {{ $task['estimated_hours'] ?? 40 }};
        new bootstrap.Modal(document.getElementById('assignModal')).show();
    }

    function confirmAssign(taskId) {
        const employeeId = pendingEmployeeId;
        const roleTypeId = document.getElementById('modalRoleType').value;
        const estimatedHours = document.getElementById('modalEstimatedHours').value;

        if (!estimatedHours || estimatedHours < 1) {
            showToast('الرجاء إدخال الساعات', 'warning');
            return;
        }

        bootstrap.Modal.getInstance(document.getElementById('assignModal')).hide();

        assignEmployeeWithHours(employeeId, roleTypeId, estimatedHours);
    }

    function assignEmployee(employeeId, roleTypeId) {
        const estimatedHours = {{ $task['estimated_hours'] ?? 40 }};
        assignEmployeeWithHours(employeeId, roleTypeId, estimatedHours);
    }

    function assignEmployeeWithHours(employeeId, roleTypeId, estimatedHours) {
        const taskId = {{ $task['task_id'] ?? $task['id'] ?? 0 }};
        if (!taskId) { showToast('معرف المهمة غير صحيح', 'error'); return; }

        document.getElementById('loadingOverlay').classList.add('show');

        fetch("{{ route('operational.assign-task-to-employee') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                task_id: taskId,
                employee_id: employeeId,
                role_type_id: roleTypeId,
                estimated_hours: estimatedHours
            })
        })
        .then(r => r.json())
        .then(data => {
            document.getElementById('loadingOverlay').classList.remove('show');
            if (data.success) {
                showToast('تم التوزيع بنجاح', 'success');
                setTimeout(() => location.reload(), 500);
            } else {
                showToast(data.message || 'فشل التوزيع', 'error');
            }
        })
        .catch(() => {
            document.getElementById('loadingOverlay').classList.remove('show');
            showToast('حدث خطأ', 'error');
        });
    }

    function updateRole(select) {
        const employeeId = select.dataset.employeeId;
        const newRole = select.value;
        if (newRole == 2) {
            showToast('لا يمكن تعيين مسؤول نهائي هنا', 'warning');
            select.value = select.dataset.oldValue || 1;
            return;
        }
        assignEmployee(employeeId, newRole);
    }

    function updateHours(input) {
        const assignmentId = input.dataset.assignmentId;
        const hours = input.value;
        if (!hours || hours < 1) {
            showToast('الساعات غير صحيحة', 'warning');
            return;
        }
        updateAssignmentHours(assignmentId, hours);
    }

    function updateAssignmentHours(assignmentId, hours) {
        document.getElementById('loadingOverlay').classList.add('show');

        fetch("{{ route('operational.update-assignment-hours') }}", {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                assignment_id: assignmentId,
                estimated_hours: hours
            })
        })
        .then(r => r.json())
        .then(data => {
            document.getElementById('loadingOverlay').classList.remove('show');
            if (data.success) {
                showToast('تم تحديث الساعات', 'success');
            } else {
                showToast(data.message || 'فشل التحديث', 'error');
            }
        })
        .catch(() => {
            document.getElementById('loadingOverlay').classList.remove('show');
            showToast('حدث خطأ', 'error');
        });
    }

    function removeAssignment(assignmentId) {
        if (!assignmentId) return;
        if (!confirm('هل أنت متأكد من إلغاء توزيع هذا الموظف؟')) return;

        document.getElementById('loadingOverlay').classList.add('show');

        fetch("{{ route('operational.remove-assignment') }}", {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ assignment_id: assignmentId })
        })
        .then(r => r.json())
        .then(data => {
            document.getElementById('loadingOverlay').classList.remove('show');
            if (data.success) {
                showToast('تم إلغاء التوزيع', 'success');
                setTimeout(() => location.reload(), 500);
            } else {
                showToast(data.message || 'فشل الإلغاء', 'error');
            }
        })
        .catch(() => {
            document.getElementById('loadingOverlay').classList.remove('show');
            showToast('حدث خطأ', 'error');
        });
    }

    function showToast(message, type) {
        if (typeof toastr !== 'undefined') {
            toastr[type](message);
        } else {
            alert(message);
        }
    }

    document.querySelectorAll('.employee-card').forEach(card => {
        card.addEventListener('dragend', function() {
            this.classList.remove('dragging');
        });
    });

    @if(session('success'))
        $(document).ready(() => showToast('{{ session('success') }}', 'success'));
    @endif

    @if(session('error'))
        $(document).ready(() => showToast('{{ session('error') }}', 'error'));
    @endif
</script>
@endpush

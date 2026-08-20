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
        min-width: 280px;
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
        font-size: 14px;
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
    .color-rejected { background: #e53e3e; }
    .count-badge {
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
    }
    .drop-zone.active {
        border-color: #d4af37;
        background: #fffbeb;
    }
    .drop-zone .empty-message {
        color: #a0aec0;
        text-align: center;
        padding: 25px 0;
        font-size: 13px;
    }
    .employee-card {
        background: #fff;
        border-radius: 12px;
        padding: 10px 12px;
        margin-bottom: 8px;
        cursor: grab;
        transition: all 0.2s;
        border: 1px solid #e2e8f0;
        position: relative;
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
        gap: 10px;
    }
    .employee-card .avatar {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background: #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 14px;
        color: #4a5568;
        flex-shrink: 0;
    }
    .employee-card .info {
        flex: 1;
        min-width: 0;
    }
    .employee-card .info .name {
        font-weight: 600;
        font-size: 13px;
        color: #2d3748;
    }
    .employee-card .info .title {
        font-size: 11px;
        color: #718096;
    }
    .employee-card .stats {
        display: flex;
        gap: 6px;
        margin-top: 3px;
        flex-wrap: wrap;
    }
    .employee-card .stats span {
        background: #f1f5f9;
        padding: 1px 6px;
        border-radius: 10px;
        font-size: 9px;
        color: #4a5568;
    }
    .employee-card .badge-role {
        padding: 2px 8px;
        border-radius: 20px;
        font-size: 9px;
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
    .badge-pending {
        background: #ecc94b;
        color: #1a1a2e;
    }
    .badge-accepted {
        background: #38a169;
        color: #fff;
    }
    .badge-rejected {
        background: #e53e3e;
        color: #fff;
    }
    .employee-card .actions {
        display: flex;
        align-items: center;
        gap: 4px;
        flex-shrink: 0;
        flex-wrap: wrap;
    }
    .employee-card .actions select {
        padding: 1px 4px;
        font-size: 10px;
        border: 1px solid #e2e8f0;
        border-radius: 4px;
        background: #fff;
        max-width: 80px;
    }
    .employee-card .actions select:focus {
        border-color: #d4af37;
        outline: none;
    }
    .employee-card .actions .hours-input {
        width: 45px;
        padding: 1px 4px;
        font-size: 10px;
        border: 1px solid #e2e8f0;
        border-radius: 4px;
        text-align: center;
    }
    .employee-card .actions .hours-input:focus {
        border-color: #d4af37;
        outline: none;
    }
    .employee-card .actions .remove-btn {
        color: #e53e3e;
        cursor: pointer;
        padding: 2px 4px;
        border-radius: 4px;
        transition: all 0.2s;
        font-size: 12px;
    }
    .employee-card .actions .remove-btn:hover {
        background: #fed7d7;
    }
    .employee-card .status-badge {
        font-size: 9px;
        padding: 1px 8px;
        border-radius: 10px;
        font-weight: 600;
    }
    .status-pending {
        background: #ecc94b;
        color: #1a1a2e;
    }
    .status-accepted {
        background: #38a169;
        color: #fff;
    }
    .status-rejected {
        background: #e53e3e;
        color: #fff;
    }
    .rejected-reason-text {
        font-size: 10px;
        color: #e53e3e;
        background: #fff5f5;
        padding: 2px 6px;
        border-radius: 4px;
        margin-top: 2px;
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
    .task-info-bar .badge-status {
        padding: 3px 12px;
        border-radius: 20px;
        font-size: 12px;
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
    .btn-finalize {
        background: #38a169;
        color: #fff;
        border: none;
        padding: 6px 16px;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s;
    }
    .btn-finalize:hover {
        background: #2f855a;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(56,161,105,0.3);
        color: #fff;
    }
    .btn-finalize:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        transform: none;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4><i class="fas fa-user-plus ml-2"></i>توزيع المهمة التشغيلية</h4>
            <p class="text-muted mb-0">اسحب وأفلت الموظفين لتوزيعهم حسب الأدوار</p>
        </div>
        <div class="d-flex gap-2">
            @php
                $hasPending = count($pendingAssignments ?? []) > 0;
                $hasResponsible = $hasResponsible ?? false;
                $disableFinalize = $hasPending || !$hasResponsible;
            @endphp
            <button class="btn-finalize" onclick="finalizeTask()" id="finalizeBtn" {{ $disableFinalize ? 'disabled' : '' }}>
                <i class="fas fa-check-circle"></i> إنهاء التوزيع
            </button>
            <a href="{{ route('operational.show-major-task', $majorTaskId ?? 0) }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-right"></i> العودة
            </a>
        </div>
    </div>

    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif

    @if($hasPending)
        <div class="alert alert-warning">
            <i class="fas fa-clock"></i>
            يوجد {{ count($pendingAssignments) }} توزيع(ات) في حالة انتظار. لا يمكن إنهاء التوزيع حتى يتم قبول أو رفض جميع التوزيعات.
        </div>
    @endif

    @if(!$hasResponsible)
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i>
            يجب تعيين مسؤول نهائي للمهمة قبل إنهاء التوزيع.
        </div>
    @endif

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
                @php
                    $taskStatusId = $task['status_id'] ?? $task['status'] ?? 16;
                @endphp
                <span class="badge-status bg-{{ $taskStatusId == 26 ? 'success' : 'secondary' }} text-white ms-1">
                    {{ $taskStatusId == 26 ? 'مكتمل التوزيع' : 'قيد التوزيع' }}
                </span>
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
                        
                        <div class="actions">
                            <span class="badge-role badge-accountable">مسؤول نهائي</span>
                            <span class="status-badge status-{{ $assign['acceptance_status'] ?? 'pending' }}">
                                {{ ($assign['acceptance_status'] ?? 'pending') == 'pending' ? 'قيد الانتظار' : (($assign['acceptance_status'] ?? '') == 'accepted' ? 'مقبول' : 'مرفوض') }}
                            </span>
                            @if(!empty($assign['rejection_reason']))
                                <div class="rejected-reason-text">{{ $assign['rejection_reason'] }}</div>
                            @endif
                            <span class="remove-btn" onclick="removeAssignment({{ $assign['assignment_id'] ?? 0 }})"><i class="fas fa-times"></i></span>
                        </div>
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
                        
                        <div class="actions">
                            <select class="role-select" data-assignment-id="{{ $assign['assignment_id'] ?? 0 }}" data-employee-id="{{ $emp['employee_id'] ?? 0 }}" onchange="updateRole(this)">
                                @foreach($roleTypes as $role)
                                    <option value="{{ $role['role_type_id'] }}" {{ ($role['role_type_id'] == ($assign['role_type_id'] ?? 1)) ? 'selected' : '' }} {{ $role['role_type_id'] == 2 ? 'disabled' : '' }}>
                                        {{ $role['name_ar'] }}
                                    </option>
                                @endforeach
                            </select>
                            <input type="number" class="hours-input" data-assignment-id="{{ $assign['assignment_id'] ?? 0 }}" value="{{ $assign['estimated_hours'] ?? 0 }}" min="1" max="720" onchange="updateHours(this)" placeholder="س">
                            <span class="status-badge status-{{ $assign['acceptance_status'] ?? 'pending' }}">
                                {{ ($assign['acceptance_status'] ?? 'pending') == 'pending' ? 'قيد الانتظار' : (($assign['acceptance_status'] ?? '') == 'accepted' ? 'مقبول' : 'مرفوض') }}
                            </span>
                            @if(!empty($assign['rejection_reason']))
                                <div class="rejected-reason-text">{{ $assign['rejection_reason'] }}</div>
                            @endif
                            <span class="remove-btn" onclick="removeAssignment({{ $assign['assignment_id'] ?? 0 }})"><i class="fas fa-times"></i></span>
                        </div>
                        </div>
                    </div>
                </div>
                @empty
                <div class="empty-message">اسحب موظف هنا كعضو</div>
                @endforelse
            </div>
        </div>

        {{-- عمود التوزيع المرفوض --}}
        <div class="assign-column">
            <div class="assign-column-header">
                <h6><span class="color-dot color-rejected"></span>مرفوض</h6>
                <span class="count-badge">{{ count($rejectedAssignments) }}</span>
            </div>
            <div class="drop-zone" id="drop-rejected" ondrop="dropHandler(event)" ondragover="dragOverHandler(event)">
                @forelse($rejectedAssignments as $assign)
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
                            <span class="badge-role badge-{{ $assign['role_type_id'] == 2 ? 'accountable' : 'responsible' }}">{{ $assign['role_type_id'] == 2 ? 'مسؤول نهائي' : 'عضو' }}</span>
                            <span class="status-badge status-rejected">مرفوض</span>
                            @if(!empty($assign['rejection_reason']))
                                <div class="rejected-reason-text">{{ $assign['rejection_reason'] }}</div>
                            @endif
                            <span class="remove-btn" onclick="removeAssignment({{ $assign['assignment_id'] ?? 0 }})"><i class="fas fa-times"></i></span>
                        </div>
                    </div>
                </div>
                @empty
                <div class="empty-message">لا توجد توزيعات مرفوضة</div>
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
    let pendingEmployeeId = null;

    function dragStartHandler(event) {
        const card = event.target.closest('.employee-card');
        if (!card) return;
        draggedTaskId = card.dataset.employeeId;
        card.classList.add('dragging');
        event.dataTransfer.effectAllowed = 'move';
    }

    function dragOverHandler(event) {
        event.preventDefault();
        const zone = event.target.closest('.drop-zone');
        if (zone) zone.classList.add('active');
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
        if (!dropZone) return;
        dropZone.classList.remove('active');

        const employeeId = draggedTaskId;
        if (!employeeId) return;

        const column = dropZone.closest('.assign-column');
        const headerText = column.querySelector('.assign-column-header h6').innerText;

        const isUnassigned = headerText.includes('غير موزعين');
        const isResponsible = headerText.includes('المسؤول النهائي');
        const isRejected = headerText.includes('مرفوض');

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
            return;
        }

        if (isRejected) {
            showToast('لا يمكن التوزيع إلى عمود المرفوض', 'warning');
            return;
        }
    }

    function dropMemberHandler(event) {
        event.preventDefault();
        const dropZone = event.target.closest('.drop-zone');
        if (!dropZone) return;
        dropZone.classList.remove('active');

        const employeeId = draggedTaskId;
        if (!employeeId) return;

        const card = document.querySelector(`.employee-card[data-employee-id="${employeeId}"]`);
        if (card && card.dataset.assignmentId) {
            showAssignModal(employeeId);
            return;
        }

        showAssignModal(employeeId);
    }

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

    function finalizeTask() {
        const hasResponsible = {{ $hasResponsible ? 'true' : 'false' }};
        if (!hasResponsible) {
            showToast('يجب تعيين مسؤول نهائي أولاً', 'warning');
            return;
        }

        const taskId = {{ $task['task_id'] ?? $task['id'] ?? 0 }};
        if (!taskId) { showToast('معرف المهمة غير صحيح', 'error'); return; }

        if (!confirm('هل أنت متأكد من إنهاء توزيع المهمة؟')) return;

        document.getElementById('loadingOverlay').classList.add('show');

        fetch("{{ route('operational.finalize-task') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ task_id: taskId })
        })
        .then(r => r.json())
        .then(data => {
            document.getElementById('loadingOverlay').classList.remove('show');
            if (data.success) {
                showToast('تم إنهاء توزيع المهمة بنجاح', 'success');
                setTimeout(() => location.reload(), 500);
            } else {
                showToast(data.message || 'فشل إنهاء التوزيع', 'error');
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

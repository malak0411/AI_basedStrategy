@extends('layouts.app')


@section('title', 'توزيع المهمة التشغيلية')


@push('styles')
<style>
    .assign-container {
        display: flex;
        gap: 16px;
        overflow-x: auto;
        padding-bottom: 20px;
        min-height: 500px;
    }
    .assign-column {
        background: #f1f5f9;
        border-radius: 16px;
        min-width: 280px;
        max-width: 340px;
        flex: 1;
        padding: 16px;
    }
    .unassigned-section {
        background: #f1f5f9;
        border-radius: 16px;
        padding: 16px;
        margin-bottom: 16px;
    }
    .unassigned-section .assign-column-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
        padding-bottom: 8px;
        border-bottom: 2px solid #e2e8f0;
    }
    .unassigned-section .assign-column-header h6 {
        font-weight: 700;
        margin: 0;
        font-size: 14px;
    }
    .horizontal-zone {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        min-height: 90px;
        border: 2px dashed transparent;
        border-radius: 8px;
        transition: all 0.2s;
        padding: 6px;
    }
    .horizontal-zone.active {
        border-color: #d4af37;
        background: #fffbeb;
    }
    .horizontal-zone .employee-card {
        min-width: 230px;
        max-width: 270px;
        margin-bottom: 0;
        flex: 0 0 auto;
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
    .employee-card .actions {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
        margin-top: 8px;
        padding-top: 8px;
        border-top: 1px solid #f1f5f9;
    }
    .employee-card .actions .hours-input {
        width: 50px;
        padding: 2px 5px;
        font-size: 11px;
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
        padding: 2px 6px;
        border-radius: 4px;
        transition: all 0.2s;
        font-size: 12px;
        margin-right: auto;
    }
    .employee-card .actions .remove-btn:hover {
        background: #fed7d7;
    }
    .badge-role {
        padding: 2px 8px;
        border-radius: 20px;
        font-size: 9px;
        font-weight: 600;
        flex-shrink: 0;
    }
    .badge-accountable { background: #d4af37; color: #1a1a2e; }
    .badge-responsible { background: #3182ce; color: #fff; }
    .badge-consulted   { background: #9c27b0; color: #fff; }
    .badge-informed    { background: #38a169; color: #fff; }
    .badge-approver    { background: #ed8936; color: #fff; }
    .status-badge {
        font-size: 9px;
        padding: 2px 8px;
        border-radius: 10px;
        font-weight: 600;
    }
    .status-pending  { background: #ecc94b; color: #1a1a2e; }
    .status-accepted { background: #38a169; color: #fff; }
    .status-rejected { background: #e53e3e; color: #fff; }
    .rejected-reason-text {
        font-size: 10px;
        color: #e53e3e;
        background: #fff5f5;
        padding: 2px 6px;
        border-radius: 4px;
        margin-top: 4px;
        width: 100%;
    }
    .btn-force-assign {
        background: #38a169;
        color: #fff;
        border: none;
        padding: 3px 10px;
        border-radius: 6px;
        font-size: 10px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .btn-force-assign:hover {
        background: #2f855a;
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(56,161,105,0.3);
    }
    .btn-force-assign:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }
    .task-info-bar {
        background: #fff;
        border-radius: 16px;
        padding: 16px 20px;
        border: 1px solid #e2e8f0;
        margin-bottom: 20px;
    }
    .task-info-bar .task-title { font-size: 17px; font-weight: 700; }
    .task-info-bar .task-meta {
        display: flex;
        gap: 16px;
        flex-wrap: wrap;
        margin-top: 6px;
    }
    .task-info-bar .task-meta .item { font-size: 12px; color: #4a5568; }
    .task-info-bar .task-meta .item strong { color: #2d3748; }
    .task-info-bar .badge-status {
        padding: 3px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    .loading-overlay {
        position: fixed;
        inset: 0;
        background: rgba(255,255,255,0.7);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 9999;
    }
    .loading-overlay.show { display: flex; }
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
    .btn-finalize:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }
</style>
@endpush


@section('content')
@php
    $taskEstimatedHours = (float)($task['estimated_hours'] ?? 0);
    $memberRoles = collect($roleTypes)->where('role_type_id', '!=', 2);


    $roleBadgeMap = [];
    foreach ($roleTypes as $r) {
        $roleBadgeMap[$r['role_type_id']] = $r['name_ar'];
    }
@endphp


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
            <a href="javascript:history.back()" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-right"></i> العودة
            </a>
        </div>
    </div>


    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if(session('error'))   <div class="alert alert-danger">{{ session('error') }}</div> @endif


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
                    <span class="item"><i class="fas fa-clock ml-1"></i> ساعات المهمة: <strong>{{ $taskEstimatedHours }}</strong></span>
                    <span class="item"><i class="fas fa-users ml-1"></i> الموزعين: <strong>{{ count($currentAssignments ?? []) }}</strong></span>
                </div>
            </div>
            <div>
                @php $taskStatusId = $task['status_id'] ?? $task['status'] ?? 16; @endphp
                <span class="badge-status bg-info text-white">{{ $task['status_name'] ?? 'معلق مؤقتاً' }}</span>
                <span class="badge-status bg-warning text-dark ms-1">{{ $task['priority_name'] ?? 'متوسطة' }}</span>
                <span class="badge-status bg-{{ $taskStatusId == 26 ? 'success' : 'secondary' }} text-white ms-1">
                    {{ $taskStatusId == 26 ? 'مكتمل التوزيع' : 'قيد التوزيع' }}
                </span>
            </div>
        </div>
    </div>


    <div class="unassigned-section">
        <div class="assign-column-header">
            <h6><span class="color-dot color-unassigned"></span>موظفين لم تسند إليهم المهمة</h6>
            <span class="count-badge">{{ count($unassignedEmployees ?? []) }}</span>
        </div>
        <div class="drop-zone horizontal-zone" id="drop-unassigned" data-zone-type="unassigned"
             ondrop="dropHandler(event)" ondragover="dragOverHandler(event)">
            @forelse(($unassignedEmployees ?? []) as $emp)
                <div class="employee-card" draggable="true"
                     data-employee-id="{{ $emp['employee_id'] ?? $emp['id'] ?? 0 }}"
                     ondragstart="dragStartHandler(event)">
                    <div class="card-body">
                        <div class="avatar">{{ mb_substr($emp['full_name'] ?? $emp['name'] ?? 'م', 0, 1, 'UTF-8') }}</div>
                        <div class="info">
                            <div class="name">{{ $emp['full_name'] ?? $emp['name'] ?? 'غير محدد' }}</div>
                            <div class="title">{{ $emp['job_title'] ?? $emp['title'] ?? 'موظف' }}</div>
                            <div class="stats">
                                <span><i class="fas fa-tasks"></i> {{ $emp['total_tasks'] ?? 0 }}</span>
                                <span><i class="fas fa-clock"></i> {{ number_format($emp['total_hours'] ?? 0) }}</span>
                                <span><i class="fas fa-spinner"></i> {{ $emp['current_tasks'] ?? 0 }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="empty-message w-100">جميع الموظفين موزعين</div>
            @endforelse
        </div>
    </div>


    <div class="assign-container">


        <div class="assign-column">
            <div class="assign-column-header">
                <h6><span class="color-dot color-responsible"></span>المسؤول النهائي</h6>
                <span class="count-badge">{{ count($responsibleAssignments ?? []) }}</span>
            </div>
            <div class="drop-zone" id="drop-responsible" data-zone-type="responsible"
                 ondrop="dropHandler(event)" ondragover="dragOverHandler(event)">
                @forelse(($responsibleAssignments ?? []) as $assign)
                    @php
                        $emp       = $assign['employee'] ?? $assign['emp'] ?? [];
                        $assignId  = $assign['assignment_id'] ?? $assign['id'] ?? 0;
                        $empId     = $emp['employee_id'] ?? $emp['id'] ?? $assign['employee_id'] ?? 0;
                        $empName   = $emp['full_name'] ?? $emp['name'] ?? $assign['employee_name'] ?? 'غير محدد';
                        $empTitle  = $emp['job_title'] ?? $emp['title'] ?? $assign['employee_title'] ?? 'موظف';
                        $hours     = $assign['estimated_hours'] ?? 0;
                        $status    = $assign['acceptance_status'] ?? 'pending';
                    @endphp
                    <div class="employee-card" draggable="true"
                         data-employee-id="{{ $empId }}"
                         data-assignment-id="{{ $assignId }}"
                         data-role-type-id="2"
                         ondragstart="dragStartHandler(event)">
                        <div class="card-body">
                            <div class="avatar">{{ mb_substr($empName, 0, 1, 'UTF-8') }}</div>
                            <div class="info">
                                <div class="name">{{ $empName }}</div>
                                <div class="title">{{ $empTitle }}</div>
                                <div class="stats">
                                    <span><i class="fas fa-tasks"></i> {{ $emp['total_tasks'] ?? 0 }}</span>
                                    <span><i class="fas fa-clock"></i> {{ number_format($emp['total_hours'] ?? 0) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="actions">
                            <span class="badge-role badge-accountable">مسؤول نهائي</span>
                            <input type="number" class="hours-input"
                                   data-assignment-id="{{ $assignId }}"
                                   data-old-hours="{{ $hours }}"
                                   value="{{ $hours }}"
                                   min="1" max="{{ $taskEstimatedHours }}"
                                   onchange="updateHours(this)">
                            <span class="status-badge status-{{ $status }}">
                                {{ $status == 'pending' ? 'قيد الانتظار' : ($status == 'accepted' ? 'مقبول' : 'مرفوض') }}
                            </span>
                            <span class="remove-btn" onclick="removeAssignment({{ $assignId }})">
                                <i class="fas fa-times"></i>
                            </span>
                            @if(!empty($assign['rejection_reason']))
                                <div class="rejected-reason-text">{{ $assign['rejection_reason'] }}</div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="empty-message">اسحب موظف هنا كمسؤول نهائي</div>
                @endforelse
            </div>
        </div>


        <div class="assign-column">
            <div class="assign-column-header">
                <h6><span class="color-dot color-members"></span>أعضاء الفريق</h6>
                <span class="count-badge">{{ count($memberAssignments ?? []) }}</span>
            </div>
            <div class="drop-zone" id="drop-members" data-zone-type="members"
                 ondrop="dropMemberHandler(event)" ondragover="dragOverHandler(event)">
                @forelse(($memberAssignments ?? []) as $assign)
                    @php
                        $emp       = $assign['employee'] ?? $assign['emp'] ?? [];
                        $assignId  = $assign['assignment_id'] ?? $assign['id'] ?? 0;
                        $empId     = $emp['employee_id'] ?? $emp['id'] ?? $assign['employee_id'] ?? 0;
                        $empName   = $emp['full_name'] ?? $emp['name'] ?? $assign['employee_name'] ?? 'غير محدد';
                        $empTitle  = $emp['job_title'] ?? $emp['title'] ?? $assign['employee_title'] ?? 'موظف';
                        $roleId    = $assign['role_type_id'] ?? 1;
                        $roleName  = $roleBadgeMap[$roleId] ?? 'عضو';
                        $hours     = $assign['estimated_hours'] ?? 0;
                        $status    = $assign['acceptance_status'] ?? 'pending';
                    @endphp
                    <div class="employee-card" draggable="true"
                         data-employee-id="{{ $empId }}"
                         data-assignment-id="{{ $assignId }}"
                         data-role-type-id="{{ $roleId }}"
                         ondragstart="dragStartHandler(event)">
                        <div class="card-body">
                            <div class="avatar">{{ mb_substr($empName, 0, 1, 'UTF-8') }}</div>
                            <div class="info">
                                <div class="name">{{ $empName }}</div>
                                <div class="title">{{ $empTitle }}</div>
                                <div class="stats">
                                    <span><i class="fas fa-tasks"></i> {{ $emp['total_tasks'] ?? 0 }}</span>
                                    <span><i class="fas fa-clock"></i> {{ number_format($emp['total_hours'] ?? 0) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="actions">
                            <span class="badge-role badge-responsible">{{ $roleName }}</span>
                            <input type="number" class="hours-input"
                                   data-assignment-id="{{ $assignId }}"
                                   data-old-hours="{{ $hours }}"
                                   value="{{ $hours }}"
                                   min="1" max="{{ $taskEstimatedHours }}"
                                   onchange="updateHours(this)">
                            <span class="status-badge status-{{ $status }}">
                                {{ $status == 'pending' ? 'قيد الانتظار' : ($status == 'accepted' ? 'مقبول' : 'مرفوض') }}
                            </span>
                            <span class="remove-btn" onclick="removeAssignment({{ $assignId }})">
                                <i class="fas fa-times"></i>
                            </span>
                            @if(!empty($assign['rejection_reason']))
                                <div class="rejected-reason-text">{{ $assign['rejection_reason'] }}</div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="empty-message">اسحب موظف هنا كعضو</div>
                @endforelse
            </div>
        </div>


        <div class="assign-column">
            <div class="assign-column-header">
                <h6><span class="color-dot color-rejected"></span>مرفوض</h6>
                <span class="count-badge">{{ count($rejectedAssignments ?? []) }}</span>
            </div>
            <div class="drop-zone" id="drop-rejected" data-zone-type="rejected"
                 ondrop="dropHandler(event)" ondragover="dragOverHandler(event)">
                @forelse(($rejectedAssignments ?? []) as $assign)
                    @php
                        $emp       = $assign['employee'] ?? $assign['emp'] ?? [];
                        $assignId  = $assign['assignment_id'] ?? $assign['id'] ?? 0;
                        $empId     = $emp['employee_id'] ?? $emp['id'] ?? $assign['employee_id'] ?? 0;
                        $empName   = $emp['full_name'] ?? $emp['name'] ?? $assign['employee_name'] ?? 'غير محدد';
                        $empTitle  = $emp['job_title'] ?? $emp['title'] ?? $assign['employee_title'] ?? 'موظف';
                        $roleId    = $assign['role_type_id'] ?? 1;
                        $roleName  = $roleId == 2 ? 'مسؤول نهائي' : ($roleBadgeMap[$roleId] ?? 'عضو');
                        $rejectWhy = $assign['rejection_reason'] ?? '';
                    @endphp
                    <div class="employee-card" draggable="true"
                         data-employee-id="{{ $empId }}"
                         data-assignment-id="{{ $assignId }}"
                         data-role-type-id="{{ $roleId }}"
                         ondragstart="dragStartHandler(event)">
                        <div class="card-body">
                            <div class="avatar">{{ mb_substr($empName, 0, 1, 'UTF-8') }}</div>
                            <div class="info">
                                <div class="name">{{ $empName }}</div>
                                <div class="title">{{ $empTitle }}</div>
                                <div class="stats">
                                    <span><i class="fas fa-tasks"></i> {{ $emp['total_tasks'] ?? 0 }}</span>
                                    <span><i class="fas fa-clock"></i> {{ number_format($emp['total_hours'] ?? 0) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="actions">
                            <span class="badge-role badge-{{ $roleId == 2 ? 'accountable' : 'responsible' }}">
                                {{ $roleName }}
                            </span>
                            <span class="status-badge status-rejected">مرفوض</span>
                            <button type="button" class="btn-force-assign"
                                    data-assignment-id="{{ $assignId }}"
                                    onclick="forceAssign(this)">
                                <i class="fas fa-bolt"></i> إسناد إجباري
                            </button>
                            <span class="remove-btn" onclick="removeAssignment({{ $assignId }})">
                                <i class="fas fa-times"></i>
                            </span>
                            @if($rejectWhy)
                                <div class="rejected-reason-text">{{ $rejectWhy }}</div>
                            @endif
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


<div class="modal fade" id="assignModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-user-plus ml-2"></i>توزيع الموظف</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="fw-bold">الموظف</label>
                    <input type="text" id="modalEmployeeName" class="form-control" readonly>
                </div>
                <div class="mb-3" id="modalRoleWrapper">
                    <label class="fw-bold">الدور</label>
                    <select id="modalRoleType" class="form-control">
                        @foreach($memberRoles as $role)
                            <option value="{{ $role['role_type_id'] }}">{{ $role['name_ar'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3 d-none" id="modalRoleFixedWrapper">
                    <label class="fw-bold">الدور</label>
                    <input type="text" id="modalRoleFixed" class="form-control" value="مسؤول نهائي" readonly>
                </div>
                <div class="mb-3">
                    <label class="fw-bold">الساعات المقدرة</label>
                    <input type="number" id="modalEstimatedHours" class="form-control"
                           value="{{ $taskEstimatedHours }}" min="1" max="{{ $taskEstimatedHours }}">
                    <small class="text-muted">
                        الحد الأقصى للساعات: <strong>{{ $taskEstimatedHours }}</strong> (ساعات المهمة)
                    </small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-primary" onclick="confirmAssign()">توزيع</button>
            </div>
        </div>
    </div>
</div>
@endsection


@push('scripts')
<script>
    const taskId = {{ $task['task_id'] ?? $task['id'] ?? 0 }};
    const taskEstimatedHours = {{ $taskEstimatedHours }};
    let draggedTaskId = null;
    let pendingEmployeeId = null;
    let pendingRoleTypeId = 1;


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
        zone.addEventListener('dragleave', function () { this.classList.remove('active'); });
    });


    document.querySelectorAll('.employee-card').forEach(card => {
        card.addEventListener('dragend', function () {
            this.classList.remove('dragging');
            document.querySelectorAll('.drop-zone').forEach(z => z.classList.remove('active'));
        });
    });


    function dropHandler(event) {
        event.preventDefault();
        const dropZone = event.target.closest('.drop-zone');
        if (!dropZone) return;
        dropZone.classList.remove('active');


        const employeeId = draggedTaskId;
        if (!employeeId) return;


        const zoneType = dropZone.dataset.zoneType;


        if (zoneType === 'unassigned') {
            const card = document.querySelector(`.employee-card[data-employee-id="${employeeId}"]`);
            const assignmentId = card?.dataset.assignmentId;
            if (assignmentId) removeAssignment(assignmentId);
            return;
        }


        if (zoneType === 'responsible') {
            const existing = document.querySelector('#drop-responsible .employee-card');
            if (existing && existing.dataset.employeeId != employeeId) {
                showToast('يوجد مسؤول نهائي بالفعل. أزل الحالي أولاً.', 'warning');
                return;
            }
            showAssignModal(employeeId, 2);
            return;
        }


        if (zoneType === 'rejected') {
            showToast('لا يمكن التوزيع المباشر إلى عمود المرفوض', 'warning');
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
        if (card && card.dataset.assignmentId && card.closest('#drop-members')) {
            return;
        }
        showAssignModal(employeeId, 1);
    }


    function showAssignModal(employeeId, roleTypeId) {
        pendingEmployeeId = employeeId;
        pendingRoleTypeId = roleTypeId;


        const card = document.querySelector(`.employee-card[data-employee-id="${employeeId}"]`);
        const employeeName = card?.querySelector('.info .name')?.innerText || '';
        document.getElementById('modalEmployeeName').value = employeeName;


        const roleWrapper = document.getElementById('modalRoleWrapper');
        const roleFixedWrapper = document.getElementById('modalRoleFixedWrapper');


        if (roleTypeId == 2) {
            roleWrapper.classList.add('d-none');
            roleFixedWrapper.classList.remove('d-none');
        } else {
            roleWrapper.classList.remove('d-none');
            roleFixedWrapper.classList.add('d-none');
            document.getElementById('modalRoleType').value = 1;
        }


        const hoursInput = document.getElementById('modalEstimatedHours');
        hoursInput.max = taskEstimatedHours;
        hoursInput.value = taskEstimatedHours;


        new bootstrap.Modal(document.getElementById('assignModal')).show();
    }


    function confirmAssign() {
        const hours = parseFloat(document.getElementById('modalEstimatedHours').value);
        const roleTypeId = pendingRoleTypeId == 2
            ? 2
            : parseInt(document.getElementById('modalRoleType').value);


        if (!hours || hours < 1) {
            showToast('الرجاء إدخال ساعات صحيحة', 'warning');
            return;
        }
        if (hours > taskEstimatedHours) {
            showToast(`لا يمكن أن تتجاوز الساعات ${taskEstimatedHours} ساعة`, 'warning');
            return;
        }


        bootstrap.Modal.getInstance(document.getElementById('assignModal')).hide();
        assignEmployeeWithHours(pendingEmployeeId, roleTypeId, hours);
    }


    function assignEmployeeWithHours(employeeId, roleTypeId, estimatedHours) {
        if (!taskId) { showToast('معرف المهمة غير صحيح', 'error'); return; }
        document.getElementById('loadingOverlay').classList.add('show');


        fetch("{{ route('operational.assign-task-to-employee') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                task_id: parseInt(taskId),
                employee_id: parseInt(employeeId),
                role_type_id: parseInt(roleTypeId),
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
                showToast(data.message || data.error || 'فشل التوزيع', 'error');
            }
        })
        .catch(() => {
            document.getElementById('loadingOverlay').classList.remove('show');
            showToast('حدث خطأ في الاتصال', 'error');
        });
    }


    function updateHours(input) {
        const assignmentId = input.dataset.assignmentId;
        const oldHours = parseFloat(input.dataset.oldHours || 0);
        const newHours = parseFloat(input.value);


        if (!newHours || newHours < 1) {
            showToast('الساعات غير صحيحة', 'warning');
            input.value = oldHours;
            return;
        }


        if (newHours > taskEstimatedHours) {
            showToast(`لا يمكن أن تتجاوز الساعات ${taskEstimatedHours} ساعة`, 'warning');
            input.value = oldHours;
            return;
        }


        updateAssignmentHours(assignmentId, newHours, oldHours, input);
    }


    function updateAssignmentHours(assignmentId, hours, oldHours, inputEl) {
        document.getElementById('loadingOverlay').classList.add('show');


        fetch("{{ route('operational.update-assignment-hours') }}", {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                assignment_id: parseInt(assignmentId),
                estimated_hours: hours
            })
        })
        .then(r => r.json())
        .then(data => {
            document.getElementById('loadingOverlay').classList.remove('show');
            if (data.success) {
                showToast('تم تحديث الساعات', 'success');
                inputEl.dataset.oldHours = hours;
            } else {
                showToast(data.message || data.error || 'فشل التحديث', 'error');
                inputEl.value = oldHours;
            }
        })
        .catch(() => {
            document.getElementById('loadingOverlay').classList.remove('show');
            showToast('حدث خطأ', 'error');
            inputEl.value = oldHours;
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
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ assignment_id: parseInt(assignmentId) })
        })
        .then(r => r.json())
        .then(data => {
            document.getElementById('loadingOverlay').classList.remove('show');
            if (data.success) {
                showToast('تم إلغاء التوزيع', 'success');
                setTimeout(() => location.reload(), 500);
            } else {
                showToast(data.message || data.error || 'فشل الإلغاء', 'error');
            }
        })
        .catch(() => {
            document.getElementById('loadingOverlay').classList.remove('show');
            showToast('حدث خطأ', 'error');
        });
    }


    function forceAssign(btn) {
        const assignmentId = btn.dataset.assignmentId;
        if (!assignmentId || assignmentId == 0) {
            showToast('معرف التوزيع غير صحيح', 'warning');
            return;
        }
        if (!confirm('سيتم إسناد الموظف إجبارياً وتغيير حالته إلى "مقبولة". متابعة؟')) return;
        forceAssignment(assignmentId, btn);
    }


    function forceAssignment(assignmentId, btn) {
        document.getElementById('loadingOverlay').classList.add('show');
        btn.disabled = true;


        fetch("{{ route('operational.force-assign') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ assignment_id: parseInt(assignmentId) })
        })
        .then(async r => {
            const text = await r.text();
            if (!r.ok) {
                console.error('Status:', r.status, 'Body:', text);
                throw new Error(`HTTP ${r.status}`);
            }
            return JSON.parse(text);
        })
        .then(data => {
            document.getElementById('loadingOverlay').classList.remove('show');
            if (data.success) {
                showToast('تم الإسناد الإجباري بنجاح', 'success');
                setTimeout(() => location.reload(), 500);
            } else {
                showToast(data.message || data.error || 'فشل الإسناد', 'error');
                btn.disabled = false;
            }
        })
        .catch(err => {
            document.getElementById('loadingOverlay').classList.remove('show');
            btn.disabled = false;
            showToast('حدث خطأ: ' + err.message, 'error');
        });
    }


    function finalizeTask() {
        const hasResponsible = {{ $hasResponsible ? 'true' : 'false' }};
        if (!hasResponsible) { showToast('يجب تعيين مسؤول نهائي أولاً', 'warning'); return; }
        if (!taskId) { showToast('معرف المهمة غير صحيح', 'error'); return; }
        if (!confirm('هل أنت متأكد من إنهاء توزيع المهمة؟')) return;


        document.getElementById('loadingOverlay').classList.add('show');
        fetch("{{ route('operational.finalize-task') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ task_id: parseInt(taskId) })
        })
        .then(r => r.json())
        .then(data => {
            document.getElementById('loadingOverlay').classList.remove('show');
            if (data.success) {
                showToast('تم إنهاء توزيع المهمة بنجاح', 'success');
                setTimeout(() => location.reload(), 500);
            } else {
                showToast(data.message || data.error || 'فشل إنهاء التوزيع', 'error');
            }
        })
        .catch(() => {
            document.getElementById('loadingOverlay').classList.remove('show');
            showToast('حدث خطأ', 'error');
        });
    }


    function showToast(message, type) {
        if (typeof toastr !== 'undefined') toastr[type](message);
        else alert(message);
    }


    @if(session('success'))
        $(document).ready(() => showToast('{{ session('success') }}', 'success'));
    @endif
    @if(session('error'))
        $(document).ready(() => showToast('{{ session('error') }}', 'error'));
    @endif
</script>
@endpush

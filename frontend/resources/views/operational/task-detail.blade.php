@extends('layouts.app')


@section('title', 'تفاصيل المهمة التشغيلية')

@section('content')
@php
    $currentUserId = session('user_id') ?? session('employee_id') ?? 0;
    $userRole      = session('user_role', 'employee');
    $isManager     = in_array($userRole, ['manager','general_manager','deputy','minister','super_admin']);


    $comments = [];
    try {
        $token  = session('jwt_token');
        $client = app(\App\Services\ApiClient::class);
        $taskId = isset($task['task_id']) ? $task['task_id'] : (isset($task['id']) ? $task['id'] : 0);
        $response = $client->get("/api/tasks/{$taskId}/comments", $token);
        if (isset($response['data']) && is_array($response['data'])) {
            $comments = $response['data'];
        } elseif (is_array($response)) {
            $comments = $response;
        }
        $comments = array_filter($comments, fn($i) => is_array($i) && isset($i['comment_id']));
        $comments = array_values($comments);
    } catch (\Exception $e) {
        $comments = [];
    }
    $commentsCount = count($comments);
    $logsCount     = is_array($logs ?? null) ? count($logs) : 0;
    $risksCount    = is_array($risks ?? null) ? count($risks) : 0;


    $participantsList = [];
    if (!empty($participants) && is_array($participants)) {
        $participantsList = $participants;
    } elseif (!empty($task['participants']) && is_array($task['participants'])) {
        $participantsList = $task['participants'];
    } elseif (!empty($task['assigned_to_name'])) {
        $participantsList = [[
            'employee_id'  => $task['assigned_to'] ?? 0,
            'full_name'    => $task['assigned_to_name'],
            'role_type_id' => $task['assigned_to_role_id'] ?? null,
            'role_type'    => $task['assigned_to_role']    ?? null,
            'job_title'    => null,
            'acceptance_status' => 'accepted',
        ]];
    }


    $isTaskResponsible = false;
    foreach ($participantsList as $p) {
        $pid    = (int) ($p['employee_id']  ?? 0);
        $roleId = (int) ($p['role_type_id'] ?? 0);
        $acc    = $p['acceptance_status'] ?? 'accepted';
        if ($pid === (int)$currentUserId && in_array($roleId, [1, 2]) && $acc === 'accepted') {
            $isTaskResponsible = true;
            break;
        }
    }


    $taskStatusId    = (int) ($task['status_id'] ?? ($task['status'] ?? 0));
    $canSendToReview = $isTaskResponsible && $taskStatusId === 6;


    $progress = (float) ($task['progress_percent'] ?? 0);
    if ($progress <= 0 && !empty($logs) && is_array($logs)) {
        $maxLogProgress = 0;
        foreach ($logs as $log) {
            if (is_array($log) && isset($log['progress_percent'])) {
                $lp = (float) $log['progress_percent'];
                if ($lp > $maxLogProgress) $maxLogProgress = $lp;
            }
        }
        $progress = $maxLogProgress;
    }
    $progress = min(100, max(0, $progress));


    $allMitigations = [];
    if (!empty($risks) && is_array($risks)) {
        foreach ($risks as $risk) {
            foreach (($risk['mitigations'] ?? []) as $mit) {
                $mit['risk_name']  = $risk['name'] ?? '';
                $mit['risk_id']    = $risk['risk_id'] ?? 0;
                $mit['risk_level'] = $risk['risk_level'] ?? [];
                $allMitigations[] = $mit;
            }
        }
    }
    $mitigationsCount = count($allMitigations);
@endphp


<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <a href="javascript:history.back()" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-right"></i> العودة
            </a>
        <div class="d-flex gap-2 flex-wrap">
            @if($canSendToReview)
                <button type="button" class="btn btn-warning"
                        onclick="sendTaskToReview({{ $taskId }})">
                    <i class="fas fa-clipboard-check"></i> إرسال للمراجعة
                </button>
            @endif
            <a href="{{ route('task.attachments.index', $taskId ?? 0) }}" class="btn btn-outline-info">
                <i class="fas fa-paperclip"></i> المرفقات
                @if(!empty($attachments))
                    <span class="badge bg-primary ms-1">{{ count($attachments) }}</span>
                @endif
            </a>
        </div>
    </div>


    @if(empty($task))
    <div class="alert alert-info text-center py-5">
        <i class="fas fa-exclamation-circle fa-2x mb-3 d-block"></i>
        <h5>المهمة غير موجودة</h5>
    </div>
    @else
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="task-detail-card mb-4">
                <div class="task-header">
                    <h3 class="task-title">
                        {{ $task['task_name'] ?? ($task['title'] ?? '') }}
                    </h3>
                    <span class="task-status-badge status-{{ $task['status'] ?? 0 }}">
                        {{ $task['status_name'] ?? '' }}
                    </span>
                </div>
                <p class="task-description">{{ $task['description'] ?? '' }}</p>


                <div class="task-parent-info mb-3">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <div class="parent-info-item">
                                <i class="fas fa-layer-group text-primary"></i>
                                <div>
                                    <small class="text-muted d-block">المهمة الرئيسية</small>
                                    <strong>{{ $task['major_task_name'] ?? ($task['major_task']['name'] ?? 'غير محدد') }}</strong>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="parent-info-item">
                                <i class="fas fa-bullseye text-success"></i>
                                <div>
                                    <small class="text-muted d-block">المبادرة</small>
                                    <strong>{{ $task['initiative_name'] ?? ($task['initiative']['name'] ?? 'غير محدد') }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


                <div class="participants-block mb-3">
                    <div class="d-flex align-items-center mb-2">
                        <i class="fas fa-users text-info me-2"></i>
                        <strong>المشاركون ({{ count($participantsList) }})</strong>
                    </div>
                    @if(empty($participantsList))
                        <small class="text-muted">لا يوجد مشاركون بعد</small>
                    @else
                        <div class="participants-list">
                            @foreach($participantsList as $p)
                                @php
                                    $roleName = $p['role_type'] ?? null;
                                    if (is_numeric($roleName) || !$roleName) {
                                        $roleMap  = [1=>'منفذ', 2=>'مسؤول نهائي', 3=>'استشاري', 4=>'مطلع', 5=>'معتمد'];
                                        $roleName = $roleMap[(int)($p['role_type_id'] ?? $roleName)] ?? '—';
                                    }
                                @endphp
                                <div class="participant-chip">
                                    <div class="participant-avatar">{{ mb_substr($p['full_name'] ?? '?', 0, 1, 'UTF-8') }}</div>
                                    <div class="participant-info">
                                        <div class="participant-name">{{ $p['full_name'] ?? '—' }}</div>
                                        <div class="participant-role">
                                            <span class="badge bg-primary">{{ $roleName ?: '—' }}</span>
                                            @if(!empty($p['job_title']))
                                                <small class="text-muted ms-1">{{ $p['job_title'] }}</small>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>


                <div class="task-meta-grid">
                    <div class="meta-item">
                        <span class="meta-label"><i class="fas fa-calendar-alt"></i> تاريخ البداية</span>
                        <span class="meta-value">{{ $task['start_date'] ?? 'غير محدد' }}</span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label"><i class="fas fa-calendar-check"></i> تاريخ التسليم</span>
                        <span class="meta-value">{{ $task['due_date'] ?? ($task['end_date'] ?? 'غير محدد') }}</span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label"><i class="fas fa-clock"></i> الساعات المقدرة للمهمة</span>
                        <span class="meta-value">{{ $task['estimated_hours'] ?? 0 }} ساعة</span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label"><i class="fas fa-hourglass-half"></i> الساعات الفعلية</span>
                        <span class="meta-value">{{ $task['actual_hours'] ?? 0 }} ساعة</span>
                    </div>
                </div>


                <div class="progress-container">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="progress-label"><i class="fas fa-chart-line"></i> نسبة الإنجاز</span>
                        <span class="progress-value">{{ number_format($progress, 1) }}%</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar dirigo-progress-bar" role="progressbar"
                             style="width: {{ $progress }}%; min-width: 4px;"
                             aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100"></div>

                    </div>
                </div>
            </div>


            <div class="activity-section">
                <ul class="nav nav-tabs" id="activityTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="logs-tab" data-bs-toggle="tab"
                                data-bs-target="#logs-pane" type="button" role="tab">
                            <i class="fas fa-history me-1"></i>
                            سجل التقدم
                            <span class="badge bg-secondary ms-1">{{ $logsCount }}</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="mitigations-tab" data-bs-toggle="tab"
                                data-bs-target="#mitigations-pane" type="button" role="tab">
                            <i class="fas fa-tasks me-1"></i>
                            الإجراءات
                            <span class="badge bg-info ms-1">{{ $mitigationsCount }}</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="comments-tab" data-bs-toggle="tab"
                                data-bs-target="#comments-pane" type="button" role="tab">
                            <i class="fas fa-comments me-1"></i>
                            التعليقات
                            <span class="badge bg-secondary ms-1" id="comments-count">{{ $commentsCount }}</span>
                        </button>
                    </li>
                </ul>


                <div class="tab-content">
                    <div class="tab-pane fade show active" id="logs-pane" role="tabpanel">
                        @if(empty($logs) || !is_array($logs))
                            <div class="empty-state">
                                <i class="fas fa-history fa-2x text-muted"></i>
                                <p class="text-muted mb-0">لا توجد سجلات تقدم</p>
                            </div>
                        @else
                            <div class="timeline">
                                @foreach($logs as $log)
                                    @if(is_array($log))
                                        <div class="timeline-item">
                                            <div class="timeline-marker"></div>
                                            <div class="timeline-content">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <strong>{{ $log['employee_name'] ?? '' }}</strong>
                                                    <small class="text-muted">{{ $log['log_time'] ?? '' }}</small>
                                                </div>
                                                <p class="timeline-text">{{ $log['notes'] ?? '' }}</p>
                                                <small class="text-muted">
                                                    <i class="fas fa-chart-line"></i>
                                                    التقدم: {{ $log['progress_percent'] ?? 0 }}%
                                                </small>
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    </div>


                    <div class="tab-pane fade" id="mitigations-pane" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0 text-muted">
                                <i class="fas fa-list-check"></i>
                                إجراءات المعالجة ({{ $mitigationsCount }})
                            </h6>
                            @if($isManager && $risksCount > 0)
                                <button class="btn btn-sm btn-info text-white"
                                        onclick="showAddMitigationGeneral()">
                                    <i class="fas fa-plus"></i> إضافة إجراء
                                </button>
                            @endif
                        </div>


                        @if(empty($allMitigations))
                            <div class="empty-state">
                                <i class="fas fa-tasks fa-2x text-muted"></i>
                                <p class="text-muted mb-0">لا توجد إجراءات معالجة</p>
                            </div>
                        @else
                            <div class="mitigations-table-container">
                                <table class="table table-hover align-middle mitigations-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="40">#</th>
                                            <th>الإجراء والملاحظات</th>
                                            <th width="140">الخطر</th>
                                            <th width="120">المسؤول</th>
                                            <th width="105">الاستحقاق</th>
                                            <th width="140">الحالة</th>
                                            <th width="150">الإجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($allMitigations as $index => $mit)
                                        @php
                                            $mStatus         = $mit['status'] ?? [];
                                            $mStatusColor    = $mStatus['color_hex'] ?? '#6c757d';
                                            $mStatusId       = (int) ($mStatus['status_id'] ?? ($mit['status_id'] ?? 0));
                                            $mRiskLevel      = $mit['risk_level'] ?? [];
                                            $mRiskLevelColor = $mRiskLevel['color_hex'] ?? '#6c757d';
                                            $isCompleted     = !empty($mit['completed_at']) || $mStatusId == 44;


                                            $assignedToId   = $mit['assigned_to']['employee_id'] ?? null;
                                            $assignedName   = $mit['assigned_to']['full_name'] ?? null;
                                            $isMyMitigation = $assignedToId && (int)$assignedToId === (int)$currentUserId;


                                            $canStart   = $isMyMitigation && $mStatusId == 41;
                                            $canSubmit  = $isMyMitigation && $mStatusId == 42;
                                            $canApprove = $isManager && $mStatusId == 43;
                                            $canDelete  = $isManager;
                                        @endphp
                                        <tr class="{{ $isCompleted ? 'mitigation-completed' : '' }}">
                                            <td><span class="text-muted">{{ $index + 1 }}</span></td>


                                            <td>
                                                <div class="mitigation-action-text">{{ $mit['action'] ?? '' }}</div>
                                                @if(!empty($mit['notes']))
                                                    <small class="text-muted d-block mt-1">
                                                        <i class="fas fa-sticky-note"></i> {{ $mit['notes'] }}
                                                    </small>
                                                @endif
                                                @if($isCompleted && !empty($mit['completed_at']))
                                                    <small class="text-success d-block mt-1">
                                                        <i class="fas fa-check-circle"></i>
                                                        اكتمل: {{ \Carbon\Carbon::parse($mit['completed_at'])->format('Y-m-d') }}
                                                    </small>
                                                @endif
                                            </td>


                                            <td>
                                                <span class="badge"
                                                      style="background-color: {{ $mRiskLevelColor }}; color: #fff; font-size: 9px;">
                                                    {{ $mRiskLevel['name_ar'] ?? '' }}
                                                </span>
                                                <div class="risk-ref-name">{{ Str::limit($mit['risk_name'] ?? '', 30) }}</div>
                                            </td>


                                            <td>
                                                @if($assignedName)
                                                    <div class="d-flex align-items-center gap-1">
                                                        <div class="assignee-avatar">{{ mb_substr($assignedName, 0, 1, 'UTF-8') }}</div>
                                                        <small>{{ Str::limit($assignedName, 15) }}</small>
                                                    </div>
                                                @else
                                                    <small class="text-muted">غير معين</small>
                                                @endif
                                            </td>


                                            <td><small>{{ $mit['due_date'] ?? '--' }}</small></td>


                                            <td>
                                                <span class="badge"
                                                      style="background-color: {{ $mStatusColor }}; color: #fff; font-size: 10px;">
                                                    {{ $mStatus['name_ar'] ?? 'غير محدد' }}
                                                </span>
                                            </td>


                                            <td>
                                                @if($canStart)
                                                    <button class="btn btn-sm btn-primary w-100 mb-1"
                                                            onclick="updateMitigationStatus({{ $mit['mitigation_id'] }}, 42)">
                                                        <i class="fas fa-play"></i> بدء التنفيذ
                                                    </button>
                                                @elseif($canSubmit)
                                                    <button class="btn btn-sm btn-success w-100 mb-1"
                                                            onclick="updateMitigationStatus({{ $mit['mitigation_id'] }}, 43)">
                                                        <i class="fas fa-paper-plane"></i> إرسال للتحقق
                                                    </button>
                                                @elseif($canApprove)
                                                    <button class="btn btn-sm btn-success w-100 mb-1"
                                                            onclick="updateMitigationStatus({{ $mit['mitigation_id'] }}, 44)">
                                                        <i class="fas fa-check-double"></i> اعتماد كمكتمل
                                                    </button>
                                                @else
                                                    <small class="text-muted d-block text-center">—</small>
                                                @endif


                                                @if($canDelete)
                                                    <button class="btn btn-sm btn-outline-danger w-100"
                                                            onclick="deleteMitigation({{ $mit['mitigation_id'] }})">
                                                        <i class="fas fa-trash"></i> حذف
                                                    </button>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>


                    <div class="tab-pane fade" id="comments-pane" role="tabpanel">
                        <div class="comments-section" id="commentsSection">
                            @if($commentsCount == 0)
                                <div class="empty-state">
                                    <i class="fas fa-comments fa-2x text-muted"></i>
                                    <p class="text-muted mb-0">لا توجد تعليقات</p>
                                </div>
                            @else
                                @foreach($comments as $comment)
                                    @if(is_array($comment))
                                        @php
                                            $commentId      = $comment['comment_id'] ?? 0;
                                            $employeeName   = $comment['employee_name'] ?? '';
                                            $employeeId     = $comment['employee_id'] ?? 0;
                                            $commentText    = $comment['comment'] ?? '';
                                            $createdAt      = $comment['created_at'] ?? '';
                                            $replies        = is_array($comment['replies'] ?? null) ? $comment['replies'] : [];
                                            $currentUserIdC = session('user_id') ?? session('employee_id') ?? 0;
                                        @endphp
                                        <div class="comment-item" id="comment-{{ $commentId }}">
                                            <div class="comment-header">
                                                <div class="comment-user">
                                                    <div class="comment-avatar">{{ mb_substr($employeeName, 0, 1, 'UTF-8') }}</div>
                                                    <div>
                                                        <strong>{{ $employeeName }}</strong>
                                                        @if(count($replies) > 0)
                                                            <span class="badge bg-info ms-1">
                                                                <i class="fas fa-reply"></i> {{ count($replies) }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="comment-actions">
                                                    <small class="text-muted">
                                                        {{ !empty($createdAt) ? \Carbon\Carbon::parse($createdAt)->diffForHumans() : '' }}
                                                    </small>
                                                    @if($employeeId == $currentUserIdC && count($replies) == 0)
                                                        <button class="btn btn-sm btn-outline-danger"
                                                                onclick="deleteComment({{ $commentId }})">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
                                            <p class="comment-text">{{ $commentText }}</p>
                                            <button class="btn btn-sm btn-outline-primary reply-btn"
                                                    onclick="showReplyForm({{ $commentId }}, '{{ addslashes($employeeName) }}')">
                                                <i class="fas fa-reply"></i> رد
                                            </button>
                                            <div class="reply-form-container" id="reply-form-{{ $commentId }}" style="display:none;">
                                                <form onsubmit="submitReply(event, {{ $taskId }}, {{ $commentId }})">
                                                    @csrf
                                                    <div class="input-group">
                                                        <input type="text" class="form-control"
                                                               placeholder="أضف رداً على {{ $employeeName }}..." required>
                                                        <button class="btn btn-primary" type="submit">
                                                            <i class="fas fa-paper-plane"></i>
                                                        </button>
                                                        <button class="btn btn-secondary" type="button"
                                                                onclick="hideReplyForm({{ $commentId }})">إلغاء</button>
                                                    </div>
                                                </form>
                                            </div>


                                            @if(count($replies) > 0)
                                                <div class="replies-container">
                                                    @foreach($replies as $reply)
                                                        @if(is_array($reply))
                                                            @php
                                                                $replyId           = $reply['comment_id'] ?? 0;
                                                                $replyEmployeeName = $reply['employee_name'] ?? '';
                                                                $replyEmployeeId   = $reply['employee_id'] ?? 0;
                                                                $replyText         = $reply['comment'] ?? '';
                                                                $replyCreatedAt    = $reply['created_at'] ?? '';
                                                            @endphp
                                                            <div class="reply-item" id="comment-{{ $replyId }}">
                                                                <div class="comment-header">
                                                                    <div class="comment-user">
                                                                        <div class="comment-avatar reply-avatar">
                                                                            {{ mb_substr($replyEmployeeName, 0, 1, 'UTF-8') }}
                                                                        </div>
                                                                        <div>
                                                                            <strong>{{ $replyEmployeeName }}</strong>
                                                                            <span class="badge bg-secondary ms-1">رد</span>
                                                                        </div>
                                                                    </div>
                                                                    <div class="comment-actions">
                                                                        <small class="text-muted">
                                                                            {{ !empty($replyCreatedAt) ? \Carbon\Carbon::parse($replyCreatedAt)->diffForHumans() : '' }}
                                                                        </small>
                                                                        @if($replyEmployeeId == $currentUserIdC)
                                                                            <button class="btn btn-sm btn-outline-danger"
                                                                                    onclick="deleteComment({{ $replyId }})">
                                                                                <i class="fas fa-trash"></i>
                                                                            </button>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                                <p class="comment-text">{{ $replyText }}</p>
                                                            </div>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                @endforeach
                            @endif
                        </div>


                        <div class="comment-form">
                            <form id="commentForm" onsubmit="submitComment(event, {{ $taskId }})">
                                @csrf
                                <div class="input-group">
                                    <input type="text" id="commentInput" class="form-control"
                                           placeholder="أضف تعليقاً..." required>
                                    <button class="btn btn-primary" type="submit">
                                        <i class="fas fa-paper-plane"></i> إرسال
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <div class="col-lg-4">
            <div class="sidebar-card mb-4">
                <div class="sidebar-card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-brain text-primary me-1"></i>
                        التنبؤ الذكي
                    </h6>
                    @if($isManager)
                        <button class="btn btn-sm btn-primary" onclick="runPrediction()" id="predictBtn">
                            <i class="fas fa-magic"></i>
                        </button>
                    @endif
                </div>
                <div class="sidebar-card-body" id="predictionContent">
                    @if(!empty($prediction))
                        @php
                            $probPercent = $prediction['probability_percent'] ?? 0;
                            $probColor   = $probPercent >= 75 ? 'danger' : ($probPercent >= 50 ? 'warning' : ($probPercent >= 25 ? 'info' : 'success'));
                            $probLabel   = $probPercent >= 75 ? 'مخاطر عالية' : ($probPercent >= 50 ? 'مخاطر متوسطة' : ($probPercent >= 25 ? 'مخاطر منخفضة' : 'طبيعي'));
                        @endphp
                        <div class="text-center mb-3">
                            <div class="prediction-circle border-{{ $probColor }}"
                                 style="width:110px;height:110px;border-width:5px;">
                                <div class="prediction-value text-{{ $probColor }}" style="font-size:28px;">
                                    {{ $probPercent }}%
                                </div>
                                <small class="text-muted" style="font-size:10px;">احتمال التأخير</small>
                            </div>
                            <span class="badge bg-{{ $probColor }} mt-2">{{ $probLabel }}</span>
                        </div>


                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <div class="prediction-stat">
                                    <small class="text-muted d-block">الثقة</small>
                                    <strong>{{ round(($prediction['confidence'] ?? 0) * 100, 1) }}%</strong>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="prediction-stat">
                                    <small class="text-muted d-block">آخر تحديث</small>
                                    <strong style="font-size:11px;">
                                        {{ isset($prediction['created_at']) ? \Carbon\Carbon::parse($prediction['created_at'])->diffForHumans() : 'غير محدد' }}
                                    </strong>
                                </div>
                            </div>
                        </div>


                        @if(!empty($prediction['top_factors']))
                            <div>
                                <small class="text-muted d-block mb-2">العوامل الرئيسية:</small>
                                @foreach(array_slice($prediction['top_factors'], 0, 3) as $factor)
                                    <div class="factor-item">
                                        <i class="fas fa-exclamation-circle text-warning"></i>
                                        <span style="flex:1;font-size:12px;">{{ $factor['feature'] ?? '' }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    @else
                        <div class="text-center py-3 text-muted">
                            <i class="fas fa-brain fa-2x d-block mb-2 opacity-25"></i>
                            <p class="mb-1 small">لا يوجد تنبؤ بعد</p>
                            @if($isManager)
                                <small style="font-size:11px;">
                                    اضغط <i class="fas fa-magic"></i> لتحليل المهمة
                                </small>
                            @endif
                        </div>
                    @endif
                </div>
            </div>


            <div class="sidebar-card mb-4">
    <div class="sidebar-card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">
            <i class="fas fa-exclamation-triangle text-danger me-1"></i>
            المخاطر
            <span class="badge bg-danger ms-1">{{ $risksCount }}</span>
        </h6>
        @if($isManager)
    <a href="{{ route('risks.create') }}"
       class="btn btn-sm btn-outline-danger"
       style="padding:2px 8px;font-size:11px;">
        <i class="fas fa-plus"></i> إضافة
    </a>
@endif
    </div>
    <div class="sidebar-card-body">
        @if(empty($risks))
            <div class="text-center py-3 text-muted">
                <i class="fas fa-shield-alt fa-2x d-block mb-2 opacity-25"></i>
                <p class="mb-1 small">لا توجد مخاطر</p>
                @if($isManager)
                    <small style="font-size:11px;">شغّل التنبؤ لكشف المخاطر أو أضف يدوياً</small>
                @endif
            </div>
        @else
            @foreach($risks as $risk)
                @php
                    $riskId      = $risk['risk_id'] ?? 0;
                    $level       = $risk['risk_level'] ?? [];
                    $levelColor  = $level['color_hex'] ?? '#6c757d';
                    $status      = $risk['status'] ?? [];
                    $statusColor = $status['color_hex'] ?? '#6c757d';
                    $mitigations = $risk['mitigations'] ?? [];
                    $aiRecs      = $risk['ai_recommendations'] ?? [];
                    $score       = (int)($risk['risk_score'] ?? 0);
                    $scoreColor  = $score >= 60 ? 'danger' : ($score >= 40 ? 'warning' : 'success');
                @endphp


                <div class="risk-sidebar-item mb-3"
                     id="risk-{{ $riskId }}"
                     @if($isManager)
                     onclick="goToRiskDetails({{ $riskId }})"
                     @endif
                     style="cursor:pointer; transition: all 0.2s;"
                     onmouseover="this.style.transform='translateX(-3px)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)';"
                     onmouseout="this.style.transform='translateX(0)'; this.style.boxShadow='none';">


                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div class="flex-grow-1">
                            <span class="badge mb-1"
                                  style="background-color: {{ $levelColor }}; color: #fff; font-size:10px;">
                                {{ $level['name_ar'] ?? 'غير محدد' }}
                            </span>
                            <div class="risk-title d-flex justify-content-between align-items-center">
                                <span>{{ $risk['name'] ?? '' }}</span>
                                <i class="fas fa-chevron-left text-muted" style="font-size:10px;"></i>
                            </div>
                        </div>
                    </div>


                    <div class="risk-stats-row">
                        <div class="risk-stat-box">
                            <small>احتمال</small>
                            <strong>{{ $risk['probability'] ?? 0 }}</strong>
                        </div>
                        <div class="risk-stat-box">
                            <small>تأثير</small>
                            <strong>{{ $risk['impact'] ?? 0 }}</strong>
                        </div>
                        <div class="risk-stat-box">
                            <small>درجة</small>
                            <strong class="text-{{ $scoreColor }}">{{ $score }}</strong>
                        </div>
                    </div>


                    <div class="mt-2">
                        <span class="badge"
                              style="background-color: {{ $statusColor }}; color: #fff; font-size:10px;">
                            {{ $status['name_ar'] ?? 'غير محدد' }}
                        </span>
                    </div>


                    <div class="mt-3" onclick="event.stopPropagation();">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <small class="text-muted">
                                <i class="fas fa-robot"></i> التوصيات
                                <span class="badge bg-secondary" style="font-size:9px;">{{ count($aiRecs) }}</span>
                            </small>
                            @if($isManager)
                                <button class="btn btn-xs btn-outline-primary"
                                        onclick="generateRecommendations({{ $riskId }})"
                                        id="genRecsBtn-{{ $riskId }}"
                                        style="padding:2px 6px;font-size:10px;">
                                    <i class="fas fa-magic"></i> توليد
                                </button>
                            @endif
                        </div>
                        <div class="ai-recs-container-sidebar" id="aiRecs-{{ $riskId }}">
                            @if(!empty($aiRecs))
                                @foreach($aiRecs as $rec)
                                    <div class="ai-rec-item">
                                        <div style="font-size:11px;font-weight:500;">{{ $rec['text'] ?? '' }}</div>
                                    </div>
                                @endforeach
                            @else
                                <div class="text-muted text-center py-1" style="font-size:10px;">
                                    لا توجد توصيات
                                </div>
                            @endif
                        </div>
                    </div>


                    <div class="mt-3" onclick="event.stopPropagation();">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <small class="text-muted">
                                <i class="fas fa-tasks"></i> الإجراءات
                                <span class="badge bg-secondary" style="font-size:9px;">{{ count($mitigations) }}</span>
                            </small>
                            @if($isManager)
                                <button class="btn btn-xs btn-outline-info"
                                        onclick="showAddMitigation({{ $riskId }})"
                                        style="padding:2px 6px;font-size:10px;">
                                    <i class="fas fa-plus"></i>
                                </button>
                            @endif
                        </div>
                        <div class="mitigations-container-sidebar">
                            @if(!empty($mitigations))
                                @foreach($mitigations as $mit)
                                    @php
                                        $mStatus      = $mit['status'] ?? [];
                                        $mStatusColor = $mStatus['color_hex'] ?? '#6c757d';
                                    @endphp
                                    <div class="mitigation-item-sidebar">
                                        <div style="font-size:11px;">{{ $mit['action'] ?? '' }}</div>
                                        @if(!empty($mit['assigned_to']['full_name']))
                                            <div class="text-muted" style="font-size:10px;">
                                                <i class="fas fa-user"></i>
                                                {{ $mit['assigned_to']['full_name'] }}
                                            </div>
                                        @endif
                                        <div class="mt-1">
                                            <span class="badge"
                                                  style="background-color: {{ $mStatusColor }}; color: #fff; font-size:9px;">
                                                {{ $mStatus['name_ar'] ?? '' }}
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="text-muted text-center py-1" style="font-size:10px;">
                                    لا توجد إجراءات
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
    </div>
</div>
        </div>
    </div>
    @endif
</div>


<div class="modal fade" id="addMitigationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fas fa-plus me-2"></i>إضافة إجراء معالجة</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3" id="riskSelectorWrapper">
                    <label class="form-label fw-bold">الخطر المرتبط <span class="text-danger">*</span></label>
                    <select id="mitigationRiskId" class="form-control">
                        <option value="">اختر الخطر</option>
                        @foreach(($risks ?? []) as $riskItem)
                            <option value="{{ $riskItem['risk_id'] ?? 0 }}">
                                {{ $riskItem['name'] ?? '' }} (درجة: {{ $riskItem['risk_score'] ?? 0 }})
                            </option>
                        @endforeach
                    </select>
                </div>


                <div class="mb-3">
                    <label class="form-label fw-bold">الإجراء <span class="text-danger">*</span></label>
                    <textarea id="mitigationAction" class="form-control" rows="3" placeholder="وصف الإجراء"></textarea>
                </div>


                <div class="mb-3">
                    <label class="form-label fw-bold">الموظف المسؤول</label>
                    <select id="mitigationAssignedTo" class="form-control">
                        <option value="">اختر موظفاً</option>
                        @foreach(($employees ?? []) as $emp)
                            <option value="{{ $emp['employee_id'] ?? 0 }}">{{ $emp['full_name'] ?? '' }}</option>
                        @endforeach
                    </select>
                </div>


                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">تاريخ الاستحقاق</label>
                        <input type="date" id="mitigationDueDate" class="form-control">
                    </div>
                    <div class="col-md-6 mb-3 d-flex align-items-end">
                        <div class="alert alert-info py-2 px-3 mb-0 small w-100">
                            <i class="fas fa-info-circle"></i>
                            الحالة الافتراضية: <strong>قيد الانتظار</strong>
                        </div>
                    </div>
                </div>


                <div class="mb-3">
                    <label class="form-label fw-bold">ملاحظات</label>
                    <textarea id="mitigationNotes" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button class="btn btn-info text-white" onclick="saveMitigation()">
                    <i class="fas fa-save"></i> حفظ
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Modal إضافة خطر جديد --}}
@if($isManager)
<div class="modal fade" id="addRiskModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-1"></i> إضافة خطر جديد
                </h5>
                <button type="button" class="btn-close btn-close-white"
                        data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <form id="addRiskForm" onsubmit="submitAddRisk(event)">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="major_task_id" value="{{ $majorTask->id ?? '' }}">


                    <div class="mb-3">
                        <label class="form-label">اسم الخطر <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required maxlength="255">
                    </div>


                    <div class="mb-3">
                        <label class="form-label">الوصف</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>


                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">الاحتمال (1-5) <span class="text-danger">*</span></label>
                            <input type="number" name="probability" class="form-control"
                                   min="1" max="5" value="3" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">التأثير (1-5) <span class="text-danger">*</span></label>
                            <input type="number" name="impact" class="form-control"
                                   min="1" max="5" value="3" required>
                        </div>
                    </div>


                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">مستوى الخطر</label>
                            <select name="risk_level_id" class="form-select">
                                <option value="">-- اختر --</option>
                                @foreach($riskLevels ?? [] as $lvl)
                                    <option value="{{ $lvl->id }}">{{ $lvl->name_ar }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">الحالة</label>
                            <select name="status_id" class="form-select">
                                <option value="">-- اختر --</option>
                                @foreach($statuses ?? [] as $st)
                                    <option value="{{ $st->id }}">{{ $st->name_ar }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>


                    <div id="addRiskError" class="alert alert-danger d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-danger" id="addRiskSubmitBtn">
                        <i class="fas fa-save"></i> حفظ
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif


<div class="modal fade" id="confirmDeleteModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h6 class="modal-title"><i class="fas fa-trash me-2"></i>تأكيد الحذف</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">هل أنت متأكد من حذف هذا الإجراء؟</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">إلغاء</button>
                <button class="btn btn-danger btn-sm" onclick="confirmDeleteMitigation()">
                    <i class="fas fa-trash"></i> حذف
                </button>
            </div>
        </div>
    </div>
</div>
@endsection


@push('styles')
<style>
    .task-detail-card {
        background: #fff;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.06);
        border: 1px solid #e9ecef;
    }


    .task-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
        flex-wrap: wrap;
        margin-bottom: 12px;
    }


    .task-title {
        font-size: 22px;
        font-weight: 600;
        color: #1a2332;
        margin: 0;
    }


    .task-status-badge {
        padding: 6px 16px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 500;
        white-space: nowrap;
    }


    .status-1  { background: #e9ecef; color: #495057; }
    .status-2  { background: #cfe2ff; color: #0d6efd; }
    .status-3  { background: #d1e7dd; color: #198754; }
    .status-4  { background: #f8d7da; color: #dc3545; }
    .status-5  { background: #fff3cd; color: #ffc107; }
    .status-6  { background: #cfe2ff; color: #0d6efd; }
    .status-7  { background: #f8d7da; color: #dc3545; }
    .status-8  { background: #e2d9f5; color: #6f42c1; }
    .status-16 { background: #e9ecef; color: #6c757d; }
    .status-19 { background: #d1e7dd; color: #198754; }
    .status-26 { background: #cfe2ff; color: #0d6efd; }


    .task-description {
        color: #495057;
        font-size: 15px;
        margin-bottom: 20px;
        padding: 12px 16px;
        background: #f8f9fa;
        border-radius: 8px;
        border-left: 4px solid #007bff;
    }


    .task-parent-info .parent-info-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        background: #f8f9fa;
        border-radius: 8px;
        height: 100%;
    }


    .task-parent-info .parent-info-item i { font-size: 20px; }


    .participants-block {
        background: #f8f9fa;
        padding: 12px;
        border-radius: 8px;
    }


    .participants-list {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }


    .participant-chip {
        display: flex;
        align-items: center;
        gap: 8px;
        background: #fff;
        border: 1px solid #e9ecef;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
    }


    .participant-avatar {
        width: 26px;
        height: 26px;
        border-radius: 50%;
        background: linear-gradient(135deg, #6c5ce7, #0984e3);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 600;
    }


    .participant-name { font-weight: 600; color: #1a2332; }
    .participant-role { display: flex; align-items: center; }


    .task-meta-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 16px;
        margin-bottom: 20px;
    }


    .meta-item { display: flex; flex-direction: column; gap: 2px; }
    .meta-label { font-size: 12px; color: #6c757d; font-weight: 500; }
    .meta-value { font-size: 15px; font-weight: 500; color: #1a2332; }


    .progress-container {
        background: #f8f9fa;
        padding: 16px;
        border-radius: 8px;
    }


    .progress-label { font-size: 13px; font-weight: 500; color: #495057; }
    .progress-value { font-size: 14px; font-weight: 600; color: #007bff; }
    .progress { height: 8px; border-radius: 4px; background: #e9ecef; }
    .progress-bar.dirigo-progress-bar {
        background: linear-gradient(90deg, #007bff, #20c997) !important;
        border-radius: 4px;
        transition: width 0.6s ease;
    }



    .activity-section {
        background: #fff;
        border-radius: 16px;
        border: 1px solid #e9ecef;
        overflow: hidden;
        box-shadow: 0 2px 12px rgba(0,0,0,0.04);
    }


    .nav-tabs {
        padding: 0;
        border-bottom: 2px solid #f1f3f5;
        gap: 0;
        background: #fafbfc;
    }


    .nav-tabs .nav-item { margin-bottom: 0; }


    .nav-tabs .nav-link {
        border: none;
        padding: 14px 24px;
        border-radius: 0;
        color: #6c757d;
        font-weight: 500;
        font-size: 14px;
        transition: all 0.2s ease;
        position: relative;
    }


    .nav-tabs .nav-link:hover { background: #f8f9fa; color: #1a2332; }


    .nav-tabs .nav-link.active {
        background: #fff;
        color: #007bff;
        border-bottom: 2px solid #007bff;
        margin-bottom: -2px;
    }


    .nav-tabs .nav-link .badge { font-size: 11px; padding: 2px 8px; }


    .tab-content { padding: 24px; }


    .timeline {
        position: relative;
        padding-left: 28px;
        max-height: 500px;
        overflow-y: auto;
    }


    .timeline::before {
        content: '';
        position: absolute;
        left: 8px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #e9ecef;
    }


    .timeline-item { position: relative; margin-bottom: 20px; padding-left: 16px; }
    .timeline-item:last-child { margin-bottom: 0; }


    .timeline-marker {
        position: absolute;
        left: -24px;
        top: 4px;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: #007bff;
        border: 2px solid #fff;
        box-shadow: 0 0 0 2px #007bff;
    }


    .timeline-content { background: #f8f9fa; padding: 12px 16px; border-radius: 8px; }


    .timeline-text { margin: 4px 0 0 0; font-size: 14px; color: #495057; }


    .mitigations-table-container {
        max-height: 550px;
        overflow-y: auto;
        border-radius: 8px;
        border: 1px solid #e9ecef;
    }


    .mitigations-table { margin-bottom: 0; font-size: 13px; }


    .mitigations-table thead {
        position: sticky;
        top: 0;
        z-index: 2;
        background: #f8f9fa;
    }


    .mitigations-table th {
        font-weight: 600;
        font-size: 12px;
        color: #495057;
        border-top: none;
        padding: 10px 8px;
        vertical-align: middle;
    }


    .mitigations-table td { padding: 10px 8px; vertical-align: middle; }


    .mitigations-table tr.mitigation-completed { background: #f0f9f4; }
    .mitigations-table tr.mitigation-completed td { opacity: 0.75; }


    .mitigation-action-text { font-weight: 500; color: #1a2332; line-height: 1.4; }


    .risk-ref-name { font-size: 11px; color: #2d3748; margin-top: 3px; font-weight: 500; }


    .assignee-avatar {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background: linear-gradient(135deg, #6c5ce7, #0984e3);
        color: #fff;
        font-size: 11px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }


    .comments-section { max-height: 500px; overflow-y: auto; margin-bottom: 16px; }


    .comment-item { padding: 16px 0; border-bottom: 1px solid #f1f3f5; }
    .comment-item:last-child { border-bottom: none; }


    .comment-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
        margin-bottom: 6px;
    }


    .comment-user { display: flex; align-items: center; gap: 10px; }


    .comment-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: linear-gradient(135deg, #6c5ce7, #0984e3);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        font-weight: 600;
        flex-shrink: 0;
    }


    .reply-avatar {
        width: 28px;
        height: 28px;
        font-size: 12px;
        background: linear-gradient(135deg, #fd7e14, #ffc107);
    }


    .comment-actions { display: flex; align-items: center; gap: 8px; }


    .comment-text {
        margin: 0 0 0 42px;
        font-size: 14px;
        color: #2d3748;
        line-height: 1.6;
    }


    .reply-btn { margin: 4px 0 0 42px; font-size: 12px; }


    .reply-form-container { margin: 8px 0 0 42px; }


    .replies-container {
        margin-top: 12px;
        padding-left: 42px;
        border-left: 2px solid #e9ecef;
    }


    .reply-item { padding: 12px 0; border-bottom: 1px solid #f1f3f5; }
    .reply-item:last-child { border-bottom: none; }


    .comment-form { padding-top: 16px; border-top: 2px solid #f1f3f5; }


    .comment-form .input-group,
    .reply-form-container .input-group {
        border-radius: 8px;
        overflow: hidden;
    }


    .comment-form .form-control,
    .reply-form-container .form-control {
        border: 1px solid #e9ecef;
        border-radius: 8px 0 0 8px;
        padding: 10px 16px;
        font-size: 14px;
    }


    .comment-form .btn-primary,
    .reply-form-container .btn-primary { border-radius: 0; padding: 10px 24px; }


    .reply-form-container .btn-secondary { border-radius: 0 8px 8px 0; }


    .empty-state { text-align: center; padding: 40px 20px; color: #6c757d; }
    .empty-state i { display: block; margin-bottom: 12px; opacity: 0.5; }


    .sidebar-card {
        background: #fff;
        border-radius: 14px;
        border: 1px solid #e9ecef;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        overflow: hidden;
    }


    .sidebar-card-header {
        padding: 14px 18px;
        border-bottom: 1px solid #f1f3f5;
        background: #fafbfc;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }


    .sidebar-card-header h6 { font-size: 14px; font-weight: 600; color: #1a2332; }


    .sidebar-card-body { padding: 18px; }


    .prediction-circle {
        border-radius: 50%;
        border-style: solid;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        margin: 0 auto;
        background: #fff;
    }


    .prediction-value { font-weight: 700; line-height: 1; margin-bottom: 4px; }


    .prediction-stat {
        background: #f8f9fa;
        padding: 8px 10px;
        border-radius: 6px;
        text-align: center;
    }


    .prediction-stat strong { font-size: 13px; }


    .factor-item { display: flex; align-items: center; gap: 8px; padding: 4px 0; font-size: 12px; }
    .factor-item i { font-size: 11px; }


    .risk-sidebar-item {
        padding: 12px;
        background: #fafbfc;
        border-radius: 10px;
        border: 1px solid #f1f3f5;
        transition: all 0.2s;
    }


    .risk-sidebar-item:hover { background: #f8f9fa; border-color: #cfe2ff; }


    .risk-title { font-size: 13px; font-weight: 600; color: #1a2332; line-height: 1.3; }


    .risk-stats-row {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 6px;
        margin-top: 8px;
    }


    .risk-stat-box {
        background: #fff;
        border: 1px solid #e9ecef;
        border-radius: 6px;
        padding: 6px 4px;
        text-align: center;
    }


    .risk-stat-box small { display: block; font-size: 9px; color: #6c757d; }
    .risk-stat-box strong { font-size: 14px; display: block; line-height: 1.2; }


    .ai-recs-container-sidebar,
    .mitigations-container-sidebar {
        max-height: 240px;
        overflow-y: auto;
    }


    .ai-rec-item {
        background: #fff;
        border-left: 3px solid #007bff;
        padding: 6px 8px;
        border-radius: 4px;
        margin-bottom: 4px;
    }


    .mitigation-item-sidebar {
        background: #fff;
        padding: 6px 8px;
        border-radius: 4px;
        margin-bottom: 4px;
        border: 1px solid #e9ecef;
    }


    .btn-xs { padding: 2px 6px; font-size: 10px; border-radius: 4px; }


    .ai-recs-container-sidebar::-webkit-scrollbar,
    .mitigations-container-sidebar::-webkit-scrollbar,
    .comments-section::-webkit-scrollbar,
    .timeline::-webkit-scrollbar,
    .mitigations-table-container::-webkit-scrollbar {
        width: 6px;
    }


    .ai-recs-container-sidebar::-webkit-scrollbar-track,
    .mitigations-container-sidebar::-webkit-scrollbar-track,
    .comments-section::-webkit-scrollbar-track,
    .timeline::-webkit-scrollbar-track,
    .mitigations-table-container::-webkit-scrollbar-track {
        background: #f1f3f5;
        border-radius: 3px;
    }


    .ai-recs-container-sidebar::-webkit-scrollbar-thumb,
    .mitigations-container-sidebar::-webkit-scrollbar-thumb,
    .comments-section::-webkit-scrollbar-thumb,
    .timeline::-webkit-scrollbar-thumb,
    .mitigations-table-container::-webkit-scrollbar-thumb {
        background: #ced4da;
        border-radius: 3px;
    }


    @media (max-width: 992px) {
        .task-meta-grid { grid-template-columns: 1fr 1fr; }
    }


    @media (max-width: 576px) {
        .task-meta-grid { grid-template-columns: 1fr; }
        .nav-tabs .nav-link { padding: 10px 14px; font-size: 13px; }
        .comment-text { margin-left: 0; }
        .reply-btn, .reply-form-container { margin-left: 0; }
        .replies-container { padding-left: 20px; }
    }
</style>
@endpush


@push('scripts')
<script>
var currentTaskId = {{ $taskId ?? 0 }};
var currentMitigationIdToDelete = null;


function sendTaskToReview(taskId) {
    if (!confirm('هل أنت متأكد من إرسال المهمة إلى مرحلة المراجعة؟')) return;


    var token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');


    fetch('/operational/task/' + taskId + '/status', {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token,
            'Accept': 'application/json'
        },
        credentials: 'same-origin',
        body: JSON.stringify({ status_id: 8 })
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        if (res.success) location.reload();
        else alert('خطأ: ' + (res.error || res.detail || 'فشل التحديث'));
    })
    .catch(function(err) { alert('حدث خطأ: ' + err); });
}


function runPrediction() {
    var btn = document.getElementById('predictBtn');
    if (!btn) return;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';


    var token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');


    fetch('/operational/task/' + currentTaskId + '/predict', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token,
            'Accept': 'application/json'
        },
        credentials: 'same-origin'
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-magic"></i>';
        if (res.success) location.reload();
        else alert('خطأ: ' + (res.error || 'فشل التنبؤ'));
    })
    .catch(function(err) {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-magic"></i>';
        alert('حدث خطأ: ' + err);
    });
}


function generateRecommendations(riskId) {
    var btn       = document.getElementById('genRecsBtn-' + riskId);
    var container = document.getElementById('aiRecs-' + riskId);


    btn.disabled = true;
    container.innerHTML = '<div class="text-center py-2"><div class="spinner-border spinner-border-sm text-primary"></div></div>';


    var token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');


    fetch('/operational/risks/' + riskId + '/recommendations', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token,
            'Accept': 'application/json'
        },
        credentials: 'same-origin'
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        btn.disabled = false;
        if (res.success) location.reload();
        else alert('خطأ: ' + (res.error || 'فشل التوليد'));
    })
    .catch(function(err) {
        btn.disabled = false;
        alert('حدث خطأ: ' + err);
    });
}


function showAddMitigation(riskId) {
    document.getElementById('mitigationRiskId').value        = riskId;
    document.getElementById('riskSelectorWrapper').style.display = 'none';
    document.getElementById('mitigationAction').value        = '';
    document.getElementById('mitigationAssignedTo').value    = '';
    document.getElementById('mitigationDueDate').value       = '';
    document.getElementById('mitigationNotes').value         = '';
    new bootstrap.Modal(document.getElementById('addMitigationModal')).show();
}


function showAddMitigationGeneral() {
    document.getElementById('mitigationRiskId').value        = '';
    document.getElementById('riskSelectorWrapper').style.display = 'block';
    document.getElementById('mitigationAction').value        = '';
    document.getElementById('mitigationAssignedTo').value    = '';
    document.getElementById('mitigationDueDate').value       = '';
    document.getElementById('mitigationNotes').value         = '';
    new bootstrap.Modal(document.getElementById('addMitigationModal')).show();
}


function saveMitigation() {
    var riskId = document.getElementById('mitigationRiskId').value;
    var action = document.getElementById('mitigationAction').value.trim();


    if (!riskId) { alert('الرجاء اختيار الخطر المرتبط'); return; }
    if (!action) { alert('الرجاء إدخال الإجراء'); return; }


    var token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');


    var data = {
        action:      action,
        assigned_to: document.getElementById('mitigationAssignedTo').value || null,
        due_date:    document.getElementById('mitigationDueDate').value || null,
        status_id:   41,
        notes:       document.getElementById('mitigationNotes').value || null
    };


    fetch('/operational/risks/' + riskId + '/mitigations', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token,
            'Accept': 'application/json'
        },
        credentials: 'same-origin',
        body: JSON.stringify(data)
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        if (res.success) location.reload();
        else alert('خطأ: ' + (res.error || 'فشل الإضافة'));
    })
    .catch(function(err) { alert('حدث خطأ: ' + err); });
}


function updateMitigationStatus(mitigationId, statusId) {
    if (!statusId) return;


    var token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');


    fetch('/operational/risks/mitigations/' + mitigationId, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token,
            'Accept': 'application/json'
        },
        credentials: 'same-origin',
        body: JSON.stringify({ status_id: parseInt(statusId) })
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        if (res.success) location.reload();
        else alert('خطأ: ' + (res.error || 'فشل التحديث'));
    })
    .catch(function(err) { alert('حدث خطأ: ' + err); });
}


function deleteMitigation(mitigationId) {
    currentMitigationIdToDelete = mitigationId;
    new bootstrap.Modal(document.getElementById('confirmDeleteModal')).show();
}


function confirmDeleteMitigation() {
    if (!currentMitigationIdToDelete) return;


    var token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');


    fetch('/operational/risks/mitigations/' + currentMitigationIdToDelete, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token,
            'Accept': 'application/json'
        },
        credentials: 'same-origin'
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        if (res.success) location.reload();
        else alert('خطأ: ' + (res.error || 'فشل الحذف'));
    })
    .catch(function(err) { alert('حدث خطأ: ' + err); });
}


function submitComment(event, taskId) {
    event.preventDefault();
    var commentInput = document.getElementById('commentInput');
    var comment      = commentInput.value.trim();
    if (!comment) { alert('الرجاء إدخال تعليق'); return; }


    var token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    fetch('/operational/task/' + taskId + '/comment', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
        body: JSON.stringify({ comment: comment, parent_comment_id: null })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) { commentInput.value = ''; location.reload(); }
        else alert('خطأ: ' + (data.error || 'فشل'));
    })
    .catch(function(error) { alert('خطأ: ' + error); });
}


function submitReply(event, taskId, parentCommentId) {
    event.preventDefault();
    var form    = event.target;
    var input   = form.querySelector('input[type="text"]');
    var comment = input.value.trim();
    if (!comment) { alert('الرجاء إدخال رد'); return; }


    var token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    fetch('/operational/task/' + taskId + '/comment', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
        body: JSON.stringify({ comment: comment, parent_comment_id: parentCommentId })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) { if (data.success) location.reload(); else alert('خطأ'); })
    .catch(function(error) { alert('خطأ: ' + error); });
}


function showReplyForm(commentId, employeeName) {
    var formContainer = document.getElementById('reply-form-' + commentId);
    if (formContainer) {
        document.querySelectorAll('.reply-form-container').forEach(function(el) {
            if (el.id !== 'reply-form-' + commentId) el.style.display = 'none';
        });
        formContainer.style.display = 'block';
        var input = formContainer.querySelector('input[type="text"]');
        if (input) {
            input.focus();
            input.placeholder = 'أضف رداً على ' + employeeName + '...';
        }
    }
}


function hideReplyForm(commentId) {
    var formContainer = document.getElementById('reply-form-' + commentId);
    if (formContainer) formContainer.style.display = 'none';
}


function deleteComment(commentId) {
    if (!confirm('هل أنت متأكد من حذف هذا التعليق؟')) return;


    var token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    fetch('/operational/comment/' + commentId, {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            var el = document.getElementById('comment-' + commentId);
            if (el) el.remove();
        } else alert('خطأ');
    })
    .catch(function(error) { alert('خطأ: ' + error); });
}

function goToRiskDetails(riskId) {
    if (!riskId) return;
    window.location.href = `/risks/${riskId}`;
}
</script>
@endpush

@extends('layouts.app')


@section('title', 'تفاصيل المهمة التشغيلية')


@section('content')
@php
    $comments = [];
    try {
        $token = session('jwt_token');
        $client = app(\App\Services\ApiClient::class);
        $taskId = isset($task['task_id']) ? $task['task_id'] : (isset($task['id']) ? $task['id'] : 0);
        $response = $client->get("/api/tasks/{$taskId}/comments", $token);


        if (isset($response['data']) && is_array($response['data'])) {
            $comments = $response['data'];
        } elseif (is_array($response)) {
            $comments = $response;
        }


        $comments = array_filter($comments, function($item) {
            return is_array($item) && isset($item['comment_id']);
        });
        $comments = array_values($comments);
    } catch (\Exception $e) {
        $comments = [];
    }
    $commentsCount = count($comments);
    $logsCount = is_array($logs ?? null) ? count($logs) : 0;
    $risksCount = is_array($risks ?? null) ? count($risks) : 0;


    $allMitigations = [];
    if (!empty($risks) && is_array($risks)) {
        foreach ($risks as $risk) {
            $riskMits = $risk['mitigations'] ?? [];
            foreach ($riskMits as $mit) {
                $mit['risk_name'] = $risk['name'] ?? '';
                $mit['risk_id'] = $risk['risk_id'] ?? 0;
                $mit['risk_level'] = $risk['risk_level'] ?? [];
                $allMitigations[] = $mit;
            }
        }
    }
    $mitigationsCount = count($allMitigations);
@endphp


<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="{{ route('operational.kanban') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-right"></i> العودة للوحة المهام
        </a>
        <div>
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
        {{-- ============ العمود الرئيسي (يمين) ============ --}}
        <div class="col-lg-8">
            {{-- بطاقة معلومات المهمة --}}
            <div class="task-detail-card mb-4">
                <div class="task-header">
                    <h3 class="task-title">{{ isset($task['task_name']) ? $task['task_name'] : (isset($task['title']) ? $task['title'] : '') }}</h3>
                    <span class="task-status-badge status-{{ isset($task['status']) ? $task['status'] : 0 }}">
                        {{ isset($task['status_name']) ? $task['status_name'] : '' }}
                    </span>
                </div>
                <p class="task-description">{{ isset($task['description']) ? $task['description'] : '' }}</p>


                <div class="task-meta-grid">
                    <div class="meta-item">
                        <span class="meta-label"><i class="fas fa-user"></i> المسؤول</span>
                        <span class="meta-value">{{ isset($task['assigned_to_name']) ? $task['assigned_to_name'] : 'غير معين' }}</span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label"><i class="fas fa-calendar-alt"></i> تاريخ البداية</span>
                        <span class="meta-value">{{ isset($task['start_date']) ? $task['start_date'] : 'غير محدد' }}</span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label"><i class="fas fa-calendar-check"></i> تاريخ التسليم</span>
                        <span class="meta-value">{{ isset($task['due_date']) ? $task['due_date'] : (isset($task['end_date']) ? $task['end_date'] : 'غير محدد') }}</span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label"><i class="fas fa-clock"></i> الساعات المقدرة</span>
                        <span class="meta-value">{{ isset($task['estimated_hours']) ? $task['estimated_hours'] : 0 }} ساعة</span>
                    </div>
                </div>


                <div class="progress-container">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="progress-label"><i class="fas fa-chart-line"></i> نسبة الإنجاز</span>
                        <span class="progress-value">
                            @php
                                $progress = 0;
                                if (!empty($logs) && is_array($logs) && count($logs) > 0) {
                                    $firstLog = isset($logs[0]) ? $logs[0] : array();
                                    $progress = is_array($firstLog) && isset($firstLog['progress_percent']) ? $firstLog['progress_percent'] : 0;
                                }
                            @endphp
                            {{ $progress }}%
                        </span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar bg-gradient" role="progressbar" style="width: {{ $progress }}%;" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            </div>


            {{-- ============ التبويبات الثلاث ============ --}}
            <div class="activity-section">
                <ul class="nav nav-tabs" id="activityTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="logs-tab" data-bs-toggle="tab" data-bs-target="#logs-pane" type="button" role="tab">
                            <i class="fas fa-history me-1"></i>
                            سجل التقدم
                            <span class="badge bg-secondary ms-1">{{ $logsCount }}</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="mitigations-tab" data-bs-toggle="tab" data-bs-target="#mitigations-pane" type="button" role="tab">
                            <i class="fas fa-tasks me-1"></i>
                            الإجراءات
                            <span class="badge bg-info ms-1">{{ $mitigationsCount }}</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="comments-tab" data-bs-toggle="tab" data-bs-target="#comments-pane" type="button" role="tab">
                            <i class="fas fa-comments me-1"></i>
                            التعليقات
                            <span class="badge bg-secondary ms-1" id="comments-count">{{ $commentsCount }}</span>
                        </button>
                    </li>
                </ul>


                <div class="tab-content">
                    {{-- ===== تبويب سجل التقدم ===== --}}
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
                                        <strong>{{ isset($log['employee_name']) ? $log['employee_name'] : '' }}</strong>
                                        <small class="text-muted">{{ isset($log['log_time']) ? $log['log_time'] : '' }}</small>
                                    </div>
                                    <p class="timeline-text">{{ isset($log['notes']) ? $log['notes'] : '' }}</p>
                                    <small class="text-muted"><i class="fas fa-chart-line"></i> التقدم: {{ isset($log['progress_percent']) ? $log['progress_percent'] : 0 }}%</small>
                                </div>
                            </div>
                            @endif
                            @endforeach
                        </div>
                        @endif
                    </div>


                    {{-- ===== تبويب الإجراءات (جديد) ===== --}}
                    <div class="tab-pane fade" id="mitigations-pane" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0 text-muted">
                                <i class="fas fa-list-check"></i>
                                جميع إجراءات المعالجة ({{ $mitigationsCount }})
                            </h6>
                            @if($risksCount > 0)
                            <button class="btn btn-sm btn-info text-white" onclick="showAddMitigationGeneral()">
                                <i class="fas fa-plus"></i> إضافة إجراء جديد
                            </button>
                            @endif
                        </div>


                        @if(empty($allMitigations))
                        <div class="empty-state">
                            <i class="fas fa-tasks fa-2x text-muted"></i>
                            <p class="text-muted mb-0">لا توجد إجراءات معالجة</p>
                            @if($risksCount > 0)
                            <small class="text-muted">اضغط "إضافة إجراء جديد" لإنشاء أول إجراء</small>
                            @else
                            <small class="text-muted">يجب كشف المخاطر أولاً قبل إضافة الإجراءات</small>
                            @endif
                        </div>
                        @else
                        <div class="mitigations-table-container">
                            <table class="table table-hover align-middle mitigations-table">
                                <thead class="table-light">
                                    <tr>
                                        <th width="40">#</th>
                                        <th>الإجراء</th>
                                        <th width="140">الخطر المرتبط</th>
                                        <th width="120">المسؤول</th>
                                        <th width="110">الاستحقاق</th>
                                        <th width="140">الحالة</th>
                                        <th width="80" class="text-center">إجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($allMitigations as $index => $mit)
                                    @php
                                        $mStatus = $mit['status'] ?? [];
                                        $mStatusColor = $mStatus['color_hex'] ?? '#6c757d';
                                        $mRiskLevel = $mit['risk_level'] ?? [];
                                        $mRiskLevelColor = $mRiskLevel['color_hex'] ?? '#6c757d';
                                        $isCompleted = !empty($mit['completed_at']);
                                    @endphp
                                    <tr class="{{ $isCompleted ? 'mitigation-completed' : '' }}">
                                        <td><span class="text-muted">{{ $index + 1 }}</span></td>
                                        <td>
                                            <div class="mitigation-action-text">{{ $mit['action'] ?? '' }}</div>
                                            @if(!empty($mit['notes']))
                                            <small class="text-muted d-block mt-1">
                                                <i class="fas fa-sticky-note"></i> {{ Str::limit($mit['notes'], 60) }}
                                            </small>
                                            @endif
                                            @if($isCompleted)
                                            <small class="text-success d-block mt-1">
                                                <i class="fas fa-check-circle"></i> اكتمل: {{ \Carbon\Carbon::parse($mit['completed_at'])->format('Y-m-d') }}
                                            </small>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="#risk-{{ $mit['risk_id'] }}" class="risk-ref-link" onclick="highlightRisk({{ $mit['risk_id'] }}); return false;">
                                                <span class="badge" style="background-color: {{ $mRiskLevelColor }}; color: #fff; font-size: 9px; margin-bottom: 2px;">
                                                    {{ $mRiskLevel['name_ar'] ?? '' }}
                                                </span>
                                                <div class="risk-ref-name">{{ Str::limit($mit['risk_name'] ?? '', 30) }}</div>
                                            </a>
                                        </td>
                                        <td>
                                            @if(!empty($mit['assigned_to']['full_name']))
                                            <div class="d-flex align-items-center gap-1">
                                                <div class="assignee-avatar">{{ mb_substr($mit['assigned_to']['full_name'], 0, 1, 'UTF-8') }}</div>
                                                <small>{{ Str::limit($mit['assigned_to']['full_name'], 15) }}</small>
                                            </div>
                                            @else
                                            <small class="text-muted">غير معين</small>
                                            @endif
                                        </td>
                                        <td>
                                            @if(!empty($mit['due_date']))
                                            <small>{{ $mit['due_date'] }}</small>
                                            @else
                                            <small class="text-muted">--</small>
                                            @endif
                                        </td>
                                        <td>
                                            <select class="form-select form-select-sm mitigation-status-select"
                                                    data-mitigation-id="{{ $mit['mitigation_id'] }}"
                                                    onchange="updateMitigationStatus(this)"
                                                    style="font-size: 11px;">
                                                <option value="">-- تغيير --</option>
                                                @foreach(($riskOptions['mitigation_statuses'] ?? []) as $st)
                                                <option value="{{ $st['status_id'] }}"
                                                        {{ ($mStatus['status_id'] ?? 0) == $st['status_id'] ? 'selected' : '' }}>
                                                    {{ $st['name_ar'] }}
                                                </option>
                                                @endforeach
                                            </select>
                                            <span class="badge mt-1 d-block" style="background-color: {{ $mStatusColor }}; color: #fff; font-size: 9px;">
                                                {{ $mStatus['name_ar'] ?? 'غير محدد' }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteMitigation({{ $mit['mitigation_id'] }})" title="حذف">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif
                    </div>


                    {{-- ===== تبويب التعليقات ===== --}}
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
                                $commentId = isset($comment['comment_id']) ? $comment['comment_id'] : 0;
                                $employeeName = isset($comment['employee_name']) ? $comment['employee_name'] : '';
                                $employeeId = isset($comment['employee_id']) ? $comment['employee_id'] : 0;
                                $commentText = isset($comment['comment']) ? $comment['comment'] : '';
                                $createdAt = isset($comment['created_at']) ? $comment['created_at'] : '';
                                $replies = isset($comment['replies']) && is_array($comment['replies']) ? $comment['replies'] : array();
                            @endphp
                            <div class="comment-item" id="comment-{{ $commentId }}">
                                <div class="comment-header">
                                    <div class="comment-user">
                                        <div class="comment-avatar">{{ mb_substr($employeeName, 0, 1, 'UTF-8') }}</div>
                                        <div>
                                            <strong>{{ $employeeName }}</strong>
                                            @if(count($replies) > 0)
                                            <span class="badge bg-info ms-1"><i class="fas fa-reply"></i> {{ count($replies) }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="comment-actions">
                                        <small class="text-muted">{{ !empty($createdAt) ? \Carbon\Carbon::parse($createdAt)->diffForHumans() : '' }}</small>
                                        @php
                                            $currentUserId = session('user_id') ?? session('employee_id') ?? 0;
                                        @endphp
                                        @if($employeeId == $currentUserId && count($replies) == 0)
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteComment({{ $commentId }})" title="حذف">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        @endif
                                    </div>
                                </div>
                                <p class="comment-text">{{ $commentText }}</p>
                                <button class="btn btn-sm btn-outline-primary reply-btn" onclick="showReplyForm({{ $commentId }}, '{{ addslashes($employeeName) }}')">
                                    <i class="fas fa-reply"></i> رد
                                </button>
                                <div class="reply-form-container" id="reply-form-{{ $commentId }}" style="display:none;">
                                    <form onsubmit="submitReply(event, {{ $taskId }}, {{ $commentId }})">
                                        @csrf
                                        <div class="input-group">
                                            <input type="text" class="form-control" placeholder="أضف رداً على {{ $employeeName }}..." required>
                                            <button class="btn btn-primary" type="submit"><i class="fas fa-paper-plane"></i></button>
                                            <button class="btn btn-secondary" type="button" onclick="hideReplyForm({{ $commentId }})">إلغاء</button>
                                        </div>
                                    </form>
                                </div>


                                @if(count($replies) > 0)
                                <div class="replies-container">
                                    @foreach($replies as $reply)
                                    @if(is_array($reply))
                                    @php
                                        $replyId = isset($reply['comment_id']) ? $reply['comment_id'] : 0;
                                        $replyEmployeeName = isset($reply['employee_name']) ? $reply['employee_name'] : '';
                                        $replyEmployeeId = isset($reply['employee_id']) ? $reply['employee_id'] : 0;
                                        $replyText = isset($reply['comment']) ? $reply['comment'] : '';
                                        $replyCreatedAt = isset($reply['created_at']) ? $reply['created_at'] : '';
                                    @endphp
                                    <div class="reply-item" id="comment-{{ $replyId }}">
                                        <div class="comment-header">
                                            <div class="comment-user">
                                                <div class="comment-avatar reply-avatar">{{ mb_substr($replyEmployeeName, 0, 1, 'UTF-8') }}</div>
                                                <div>
                                                    <strong>{{ $replyEmployeeName }}</strong>
                                                    <span class="badge bg-secondary ms-1">رد</span>
                                                </div>
                                            </div>
                                            <div class="comment-actions">
                                                <small class="text-muted">{{ !empty($replyCreatedAt) ? \Carbon\Carbon::parse($replyCreatedAt)->diffForHumans() : '' }}</small>
                                                @if($replyEmployeeId == $currentUserId)
                                                <button class="btn btn-sm btn-outline-danger" onclick="deleteComment({{ $replyId }})">
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
                                    <input type="text" id="commentInput" class="form-control" placeholder="أضف تعليقاً..." required>
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


        {{-- ============ الشريط الجانبي (يسار) ============ --}}
        <div class="col-lg-4">
            {{-- بطاقة التنبؤ --}}
            <div class="sidebar-card-header">
    <h6 class="mb-0">
        <i class="fas fa-brain text-primary me-1"></i>
        التنبؤ الذكي
    </h6>
    <button class="btn btn-sm btn-primary" onclick="runPrediction()" id="predictBtn">
        <i class="fas fa-magic"></i> تحليل شامل
    </button>
</div>

                <div class="sidebar-card-body" id="predictionContent">
                    @if(!empty($prediction))
                    @php
                        $probPercent = $prediction['probability_percent'] ?? 0;
                        $probColor = $probPercent >= 75 ? 'danger' : ($probPercent >= 50 ? 'warning' : ($probPercent >= 25 ? 'info' : 'success'));
                        $probLabel = $probPercent >= 75 ? 'مخاطر عالية' : ($probPercent >= 50 ? 'مخاطر متوسطة' : ($probPercent >= 25 ? 'مخاطر منخفضة' : 'طبيعي'));
                    @endphp
                    <div class="text-center mb-3">
                        <div class="prediction-circle border-{{ $probColor }}" style="width:110px;height:110px;border-width:5px;">
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
                                <strong style="font-size:11px;">{{ isset($prediction['created_at']) ? \Carbon\Carbon::parse($prediction['created_at'])->diffForHumans() : 'غير محدد' }}</strong>
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
                        <small style="font-size:11px;">اضغط <i class="fas fa-magic"></i> لتحليل المهمة</small>
                    </div>
                    @endif
                </div>
            </div>


            {{-- بطاقة المخاطر --}}
            <div class="sidebar-card mb-4">
                <div class="sidebar-card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-exclamation-triangle text-danger me-1"></i>
                        المخاطر
                        <span class="badge bg-danger ms-1">{{ $risksCount }}</span>
                    </h6>
                </div>
                <div class="sidebar-card-body">
                    @if(empty($risks))
                    <div class="text-center py-3 text-muted">
                        <i class="fas fa-shield-alt fa-2x d-block mb-2 opacity-25"></i>
                        <p class="mb-1 small">لا توجد مخاطر</p>
                        <small style="font-size:11px;">شغّل التنبؤ لكشف المخاطر</small>
                    </div>
                    @else
                    @foreach($risks as $risk)
                    @php
                        $riskId = $risk['risk_id'] ?? 0;
                        $level = $risk['risk_level'] ?? [];
                        $levelColor = $level['color_hex'] ?? '#6c757d';
                        $status = $risk['status'] ?? [];
                        $statusColor = $status['color_hex'] ?? '#6c757d';
                        $mitigations = $risk['mitigations'] ?? [];
                        $aiRecs = $risk['ai_recommendations'] ?? [];
                        $score = (int) ($risk['risk_score'] ?? 0);
                        $scoreColor = $score >= 60 ? 'danger' : ($score >= 40 ? 'warning' : 'success');
                    @endphp
                    <div class="risk-sidebar-item mb-3" id="risk-{{ $riskId }}">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="flex-grow-1">
                                <span class="badge mb-1" style="background-color: {{ $levelColor }}; color: #fff; font-size:10px;">
                                    {{ $level['name_ar'] ?? 'غير محدد' }}
                                </span>
                                <div class="risk-title">{{ $risk['name'] ?? '' }}</div>
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
                            <span class="badge" style="background-color: {{ $statusColor }}; color: #fff; font-size:10px;">
                                {{ $status['name_ar'] ?? 'غير محدد' }}
                            </span>
                        </div>


                        {{-- التوصيات --}}
                        <div class="mt-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <small class="text-muted">
                                    <i class="fas fa-robot"></i> التوصيات
                                    <span class="badge bg-secondary" style="font-size:9px;">{{ count($aiRecs) }}</span>
                                </small>
                                <button class="btn btn-xs btn-outline-primary"
                                        onclick="generateRecommendations({{ $riskId }})"
                                        id="genRecsBtn-{{ $riskId }}"
                                        style="padding:2px 6px;font-size:10px;">
                                    <i class="fas fa-magic"></i> توليد
                                </button>
                            </div>
                            <div class="ai-recs-container-sidebar" id="aiRecs-{{ $riskId }}">
                                @if(!empty($aiRecs))
                                @foreach(array_slice($aiRecs, 0, 3) as $rec)
                                <div class="ai-rec-item">
                                    <div style="font-size:11px;font-weight:500;">{{ $rec['text'] ?? '' }}</div>
                                </div>
                                @endforeach
                                @else
                                <div class="text-muted text-center py-1" style="font-size:10px;">لا توجد توصيات</div>
                                @endif
                            </div>
                        </div>


                        {{-- الإجراءات المصغرة --}}
                        <div class="mt-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <small class="text-muted">
                                    <i class="fas fa-tasks"></i> الإجراءات
                                    <span class="badge bg-secondary" style="font-size:9px;">{{ count($mitigations) }}</span>
                                </small>
                                <button class="btn btn-xs btn-outline-info"
                                        onclick="showAddMitigation({{ $riskId }})"
                                        style="padding:2px 6px;font-size:10px;">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                            <div class="mitigations-container-sidebar">
                                @if(!empty($mitigations))
                                @foreach($mitigations as $mit)
                                @php
                                    $mStatus = $mit['status'] ?? [];
                                    $mStatusColor = $mStatus['color_hex'] ?? '#6c757d';
                                @endphp
                                <div class="mitigation-item-sidebar">
                                    <div style="font-size:11px;">{{ $mit['action'] ?? '' }}</div>
                                    @if(!empty($mit['assigned_to']['full_name']))
                                    <div class="text-muted" style="font-size:10px;">
                                        <i class="fas fa-user"></i> {{ $mit['assigned_to']['full_name'] }}
                                    </div>
                                    @endif
                                    <div class="d-flex justify-content-between align-items-center gap-1 mt-1">
                                        <select class="form-select form-select-sm"
                                                data-mitigation-id="{{ $mit['mitigation_id'] }}"
                                                onchange="updateMitigationStatus(this)"
                                                style="font-size:10px;padding:2px 4px;">
                                            <option value="">-- تغيير --</option>
                                            @foreach(($riskOptions['mitigation_statuses'] ?? []) as $st)
                                            <option value="{{ $st['status_id'] }}"
                                                    {{ ($mStatus['status_id'] ?? 0) == $st['status_id'] ? 'selected' : '' }}>
                                                {{ $st['name_ar'] }}
                                            </option>
                                            @endforeach
                                        </select>
                                        <span class="badge" style="background-color: {{ $mStatusColor }}; color: #fff; font-size:9px;white-space:nowrap;">
                                            {{ $mStatus['name_ar'] ?? '' }}
                                        </span>
                                    </div>
                                </div>
                                @endforeach
                                @else
                                <div class="text-muted text-center py-1" style="font-size:10px;">لا توجد إجراءات</div>
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


{{-- Modal إضافة إجراء (مع اختيار الخطر) --}}
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
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">الحالة</label>
                        <select id="mitigationStatus" class="form-control">
                            <option value="">اختر الحالة</option>
                            @foreach(($riskOptions['mitigation_statuses'] ?? []) as $st)
                            <option value="{{ $st['status_id'] }}">{{ $st['name_ar'] }}</option>
                            @endforeach
                        </select>
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


{{-- Modal تأكيد الحذف --}}
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
    /* ==================== البطاقة الرئيسية ==================== */
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


    .status-1 { background: #e9ecef; color: #495057; }
    .status-2 { background: #cfe2ff; color: #0d6efd; }
    .status-3 { background: #d1e7dd; color: #198754; }
    .status-4 { background: #f8d7da; color: #dc3545; }
    .status-5 { background: #fff3cd; color: #ffc107; }
    .status-6 { background: #cfe2ff; color: #0d6efd; }
    .status-7 { background: #f8d7da; color: #dc3545; }
    .status-8 { background: #e2d9f5; color: #6f42c1; }
    .status-19 { background: #d1e7dd; color: #198754; }
    .status-26 { background: #cfe2ff; color: #0d6efd; }
    .status-16 { background: #e9ecef; color: #6c757d; }


    .task-description {
        color: #495057;
        font-size: 15px;
        margin-bottom: 20px;
        padding: 12px 16px;
        background: #f8f9fa;
        border-radius: 8px;
        border-left: 4px solid #007bff;
    }


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
    .progress-bar.bg-gradient {
        background: linear-gradient(90deg, #007bff, #20c997);
        border-radius: 4px;
        transition: width 0.6s ease;
    }


    /* ==================== التبويبات ==================== */
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


    .nav-tabs .nav-link:hover {
        background: #f8f9fa;
        color: #1a2332;
    }


    .nav-tabs .nav-link.active {
        background: #fff;
        color: #007bff;
        border-bottom: 2px solid #007bff;
        margin-bottom: -2px;
    }


    .nav-tabs .nav-link .badge {
        font-size: 11px;
        padding: 2px 8px;
    }


    .tab-content { padding: 24px; }


    /* ==================== سجل التقدم ==================== */
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


    .timeline-item {
        position: relative;
        margin-bottom: 20px;
        padding-left: 16px;
    }


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


    .timeline-content {
        background: #f8f9fa;
        padding: 12px 16px;
        border-radius: 8px;
    }


    .timeline-text {
        margin: 4px 0 0 0;
        font-size: 14px;
        color: #495057;
    }


    /* ==================== جدول الإجراءات ==================== */
    .mitigations-table-container {
        max-height: 550px;
        overflow-y: auto;
        border-radius: 8px;
        border: 1px solid #e9ecef;
    }


    .mitigations-table {
        margin-bottom: 0;
        font-size: 13px;
    }


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


    .mitigations-table td {
        padding: 10px 8px;
        vertical-align: middle;
    }


    .mitigations-table tr.mitigation-completed {
        background: #f0f9f4;
    }


    .mitigations-table tr.mitigation-completed td {
        opacity: 0.75;
    }


    .mitigation-action-text {
        font-weight: 500;
        color: #1a2332;
        line-height: 1.4;
    }


    .risk-ref-link {
        text-decoration: none;
        display: inline-block;
        line-height: 1.3;
    }


    .risk-ref-link:hover .risk-ref-name {
        color: #007bff;
        text-decoration: underline;
    }


    .risk-ref-name {
        font-size: 11px;
        color: #2d3748;
        margin-top: 3px;
        font-weight: 500;
    }


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


    .mitigation-status-select {
        font-size: 11px;
        padding: 3px 6px;
    }


    /* ==================== التعليقات ==================== */
    .comments-section {
        max-height: 500px;
        overflow-y: auto;
        margin-bottom: 16px;
    }


    .comment-item {
        padding: 16px 0;
        border-bottom: 1px solid #f1f3f5;
    }


    .comment-item:last-child { border-bottom: none; }


    .comment-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
        margin-bottom: 6px;
    }


    .comment-user {
        display: flex;
        align-items: center;
        gap: 10px;
    }


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


    .comment-actions {
        display: flex;
        align-items: center;
        gap: 8px;
    }


    .comment-text {
        margin: 0 0 0 42px;
        font-size: 14px;
        color: #2d3748;
        line-height: 1.6;
    }


    .reply-btn {
        margin: 4px 0 0 42px;
        font-size: 12px;
    }


    .reply-form-container { margin: 8px 0 0 42px; }


    .replies-container {
        margin-top: 12px;
        padding-left: 42px;
        border-left: 2px solid #e9ecef;
    }


    .reply-item {
        padding: 12px 0;
        border-bottom: 1px solid #f1f3f5;
    }


    .reply-item:last-child { border-bottom: none; }


    .comment-form {
        padding-top: 16px;
        border-top: 2px solid #f1f3f5;
    }


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
    .reply-form-container .btn-primary {
        border-radius: 0;
        padding: 10px 24px;
    }


    .reply-form-container .btn-secondary { border-radius: 0 8px 8px 0; }


    .empty-state {
        text-align: center;
        padding: 40px 20px;
        color: #6c757d;
    }


    .empty-state i {
        display: block;
        margin-bottom: 12px;
        opacity: 0.5;
    }


    /* ==================== الشريط الجانبي ==================== */
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


    .sidebar-card-header h6 {
        font-size: 14px;
        font-weight: 600;
        color: #1a2332;
    }


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


    .prediction-value {
        font-weight: 700;
        line-height: 1;
        margin-bottom: 4px;
    }


    .prediction-stat {
        background: #f8f9fa;
        padding: 8px 10px;
        border-radius: 6px;
        text-align: center;
    }


    .prediction-stat strong { font-size: 13px; }


    .factor-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 4px 0;
        font-size: 12px;
    }


    .factor-item i { font-size: 11px; }


    /* ==================== المخاطر في الشريط الجانبي ==================== */
    .risk-sidebar-item {
        padding: 12px;
        background: #fafbfc;
        border-radius: 10px;
        border: 1px solid #f1f3f5;
        transition: all 0.2s;
    }


    .risk-sidebar-item:hover {
        background: #f8f9fa;
        border-color: #cfe2ff;
    }


    .risk-sidebar-item.highlighted {
        animation: pulse-highlight 2s ease;
        border-color: #007bff;
        background: #e7f1ff;
    }


    @keyframes pulse-highlight {
        0%, 100% { background: #e7f1ff; }
        50% { background: #cfe2ff; }
    }


    .risk-title {
        font-size: 13px;
        font-weight: 600;
        color: #1a2332;
        line-height: 1.3;
    }


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


    .risk-stat-box small {
        display: block;
        font-size: 9px;
        color: #6c757d;
    }


    .risk-stat-box strong {
        font-size: 14px;
        display: block;
        line-height: 1.2;
    }


    .ai-recs-container-sidebar,
    .mitigations-container-sidebar {
        max-height: 160px;
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


    .btn-xs {
        padding: 2px 6px;
        font-size: 10px;
        border-radius: 4px;
    }


    /* ==================== Scrollbars ==================== */
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


    /* ==================== Responsive ==================== */
    @media (max-width: 992px) {
        .task-meta-grid {
            grid-template-columns: 1fr 1fr;
        }
    }


    @media (max-width: 576px) {
        .task-meta-grid {
            grid-template-columns: 1fr;
        }


        .nav-tabs .nav-link {
            padding: 10px 14px;
            font-size: 13px;
        }


        .comment-text {
            margin-left: 0;
        }


        .reply-btn,
        .reply-form-container {
            margin-left: 0;
        }


        .replies-container {
            padding-left: 20px;
        }
    }
</style>
@endpush


@push('scripts')
<script>
var currentTaskId = {{ $taskId ?? 0 }};
var currentMitigationIdToDelete = null;


function runPrediction() {
    var btn = document.getElementById('predictBtn');
    var originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جارٍ التحليل...';


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
        btn.innerHTML = originalHtml;


        if (res.success) {
            var data = res.data || {};
            var msg = 'اكتمل التحليل:\n';
            msg += '• احتمال التأخير: ' + Math.round((data.delay_probability || 0) * 100) + '%\n';
            msg += '• مستوى الخطر: ' + (data.risk_level || 'غير محدد') + '\n';


            if (data.is_currently_delayed) {
                msg += '• المهمة متأخرة: ' + (data.days_overdue || 0) + ' يوم\n';
            }


            if (data.risk_detection && data.risk_detection.detected) {
                var action = data.risk_detection.action === 'created' ? 'تم إنشاء خطر جديد' : 'تم تحديث الخطر';
                msg += '• ' + action + '\n';
            }


            if (data.recommendations_result && data.recommendations_result.success) {
                msg += '• تم توليد التوصيات الذكية';
            }


            alert(msg);
            location.reload();
        } else {
            alert('خطأ: ' + (res.error || 'فشل التحليل'));
        }
    })
    .catch(function(err) {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
        alert('حدث خطأ: ' + err);
    });
}


function generateRecommendations(riskId) {
    var btn = document.getElementById('genRecsBtn-' + riskId);
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
        if (res.success) {
            location.reload();
        } else {
            alert('خطأ: ' + (res.error || 'فشل التوليد'));
        }
    })
    .catch(function(err) {
        btn.disabled = false;
        alert('حدث خطأ: ' + err);
    });
}


function showAddMitigation(riskId) {
    document.getElementById('mitigationRiskId').value = riskId;
    document.getElementById('riskSelectorWrapper').style.display = 'none';
    document.getElementById('mitigationAction').value = '';
    document.getElementById('mitigationAssignedTo').value = '';
    document.getElementById('mitigationDueDate').value = '';
    document.getElementById('mitigationStatus').value = '';
    document.getElementById('mitigationNotes').value = '';


    var modal = new bootstrap.Modal(document.getElementById('addMitigationModal'));
    modal.show();
}


function showAddMitigationGeneral() {
    document.getElementById('mitigationRiskId').value = '';
    document.getElementById('riskSelectorWrapper').style.display = 'block';
    document.getElementById('mitigationAction').value = '';
    document.getElementById('mitigationAssignedTo').value = '';
    document.getElementById('mitigationDueDate').value = '';
    document.getElementById('mitigationStatus').value = '';
    document.getElementById('mitigationNotes').value = '';


    var modal = new bootstrap.Modal(document.getElementById('addMitigationModal'));
    modal.show();
}


function saveMitigation() {
    var riskId = document.getElementById('mitigationRiskId').value;
    var action = document.getElementById('mitigationAction').value.trim();


    if (!riskId) {
        alert('الرجاء اختيار الخطر المرتبط');
        return;
    }


    if (!action) {
        alert('الرجاء إدخال الإجراء');
        return;
    }


    var token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');


    var data = {
        action: action,
        assigned_to: document.getElementById('mitigationAssignedTo').value || null,
        due_date: document.getElementById('mitigationDueDate').value || null,
        status_id: document.getElementById('mitigationStatus').value || null,
        notes: document.getElementById('mitigationNotes').value || null
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
        if (res.success) {
            location.reload();
        } else {
            alert('خطأ: ' + (res.error || 'فشل الإضافة'));
        }
    })
    .catch(function(err) {
        alert('حدث خطأ: ' + err);
    });
}


function updateMitigationStatus(selectEl) {
    var mitigationId = selectEl.getAttribute('data-mitigation-id');
    var statusId = selectEl.value;


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
        if (res.success) {
            location.reload();
        } else {
            alert('خطأ: ' + (res.error || 'فشل التحديث'));
        }
    })
    .catch(function(err) {
        alert('حدث خطأ: ' + err);
    });
}


function deleteMitigation(mitigationId) {
    currentMitigationIdToDelete = mitigationId;
    var modal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
    modal.show();
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
        if (res.success) {
            location.reload();
        } else {
            alert('خطأ: ' + (res.error || 'فشل الحذف'));
        }
    })
    .catch(function(err) {
        alert('حدث خطأ: ' + err);
    });
}


function highlightRisk(riskId) {
    var riskElement = document.getElementById('risk-' + riskId);
    if (riskElement) {
        riskElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
        riskElement.classList.add('highlighted');
        setTimeout(function() {
            riskElement.classList.remove('highlighted');
        }, 2000);
    }
}


function submitComment(event, taskId) {
    event.preventDefault();
    var commentInput = document.getElementById('commentInput');
    var comment = commentInput.value.trim();
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
        else { alert('خطأ: ' + (data.error || 'فشل')); }
    })
    .catch(function(error) { alert('خطأ: ' + error); });
}


function submitReply(event, taskId, parentCommentId) {
    event.preventDefault();
    var form = event.target;
    var input = form.querySelector('input[type="text"]');
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
        } else { alert('خطأ'); }
    })
    .catch(function(error) { alert('خطأ: ' + error); });
}
</script>
@endpush

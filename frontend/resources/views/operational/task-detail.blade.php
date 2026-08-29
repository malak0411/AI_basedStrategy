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
@endphp

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="{{ route('operational.kanban') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-right"></i> العودة للوحة المهام
        </a>
        <div>
            @php
                $taskId = isset($task['task_id']) ? $task['task_id'] : (isset($task['id']) ? $task['id'] : 0);
            @endphp
            <a href="{{ route('task.attachments.index', $taskId) }}" class="btn btn-outline-info">
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
    <div class="row">
        <div class="col-lg-8">
            <div class="task-detail-card">
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

            <div class="activity-section">
                <ul class="nav nav-tabs" id="activityTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="logs-tab" data-bs-toggle="tab" data-bs-target="#logs" type="button" role="tab">
                            <i class="fas fa-history"></i> سجل التقدم
                            <span class="badge bg-secondary ms-1">{{ is_array($logs) ? count($logs) : 0 }}</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="comments-tab" data-bs-toggle="tab" data-bs-target="#comments" type="button" role="tab">
                            <i class="fas fa-comments"></i> التعليقات
                            <span class="badge bg-secondary ms-1" id="comments-count">{{ $commentsCount }}</span>
                        </button>
                    </li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="logs" role="tabpanel">
                        @if(empty($logs) || !is_array($logs))
                        <div class="empty-state">
                            <i class="fas fa-history fa-2x text-muted"></i>
                            <p class="text-muted">لا توجد سجلات تقدم</p>
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

                    <div class="tab-pane fade" id="comments" role="tabpanel">
                        <div class="comments-section" id="commentsSection">
                            @if($commentsCount == 0)
                            <div class="empty-state">
                                <i class="fas fa-comments fa-2x text-muted"></i>
                                <p class="text-muted">لا توجد تعليقات</p>
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
                                $level = 0;
                            @endphp
                            <div class="comment-item" id="comment-{{ $commentId }}" data-level="{{ $level }}">
                                <div class="comment-header">
                                    <div class="comment-user">
                                        <div class="comment-avatar">{{ substr($employeeName, 0, 1) }}</div>
                                        <div>
                                            <strong>{{ $employeeName }}</strong>
                                            @if(count($replies) > 0)
                                            <span class="badge bg-info ms-1"><i class="fas fa-reply"></i> {{ count($replies) }} ردود</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="comment-actions">
                                        <small class="text-muted">{{ !empty($createdAt) ? \Carbon\Carbon::parse($createdAt)->diffForHumans() : '' }}</small>
                                        @php
                                            $currentUserId = session('user_id') ?? session('employee_id') ?? 0;
                                        @endphp
                                        @if($employeeId == $currentUserId)
                                            @if(count($replies) == 0)
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteComment({{ $commentId }})" title="حذف التعليق">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            @else
                                            <span class="btn btn-sm btn-outline-secondary" title="لا يمكن حذف تعليق يحتوي على ردود">
                                                <i class="fas fa-lock"></i>
                                            </span>
                                            @endif
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
                                <div class="replies-container level-1">
                                    @foreach($replies as $reply)
                                    @if(is_array($reply))
                                    @php
                                        $replyId = isset($reply['comment_id']) ? $reply['comment_id'] : 0;
                                        $replyEmployeeName = isset($reply['employee_name']) ? $reply['employee_name'] : '';
                                        $replyEmployeeId = isset($reply['employee_id']) ? $reply['employee_id'] : 0;
                                        $replyText = isset($reply['comment']) ? $reply['comment'] : '';
                                        $replyCreatedAt = isset($reply['created_at']) ? $reply['created_at'] : '';
                                        $replyReplies = isset($reply['replies']) && is_array($reply['replies']) ? $reply['replies'] : array();
                                    @endphp
                                    <div class="reply-item" id="comment-{{ $replyId }}" data-level="1">
                                        <div class="comment-header">
                                            <div class="comment-user">
                                                <div class="comment-avatar reply-avatar">{{ substr($replyEmployeeName, 0, 1) }}</div>
                                                <div>
                                                    <strong>{{ $replyEmployeeName }}</strong>
                                                    <span class="badge bg-secondary ms-1">رد</span>
                                                    @if(count($replyReplies) > 0)
                                                    <span class="badge bg-info ms-1"><i class="fas fa-reply"></i> {{ count($replyReplies) }} ردود</span>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="comment-actions">
                                                <small class="text-muted">{{ !empty($replyCreatedAt) ? \Carbon\Carbon::parse($replyCreatedAt)->diffForHumans() : '' }}</small>
                                                @if($replyEmployeeId == $currentUserId)
                                                    @if(count($replyReplies) == 0)
                                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteComment({{ $replyId }})" title="حذف الرد">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                    @else
                                                    <span class="btn btn-sm btn-outline-secondary" title="لا يمكن حذف رد يحتوي على ردود">
                                                        <i class="fas fa-lock"></i>
                                                    </span>
                                                    @endif
                                                @endif
                                            </div>
                                        </div>
                                        <p class="comment-text">{{ $replyText }}</p>
                                        <button class="btn btn-sm btn-outline-primary reply-btn" onclick="showReplyForm({{ $replyId }}, '{{ addslashes($replyEmployeeName) }}')">
                                            <i class="fas fa-reply"></i> رد
                                        </button>
                                        <div class="reply-form-container" id="reply-form-{{ $replyId }}" style="display:none;">
                                            <form onsubmit="submitReply(event, {{ $taskId }}, {{ $replyId }})">
                                                @csrf
                                                <div class="input-group">
                                                    <input type="text" class="form-control" placeholder="أضف رداً على {{ $replyEmployeeName }}..." required>
                                                    <button class="btn btn-primary" type="submit"><i class="fas fa-paper-plane"></i></button>
                                                    <button class="btn btn-secondary" type="button" onclick="hideReplyForm({{ $replyId }})">إلغاء</button>
                                                </div>
                                            </form>
                                        </div>
                                        
                                        @if(count($replyReplies) > 0)
                                        <div class="replies-container level-2">
                                            @foreach($replyReplies as $subReply)
                                            @if(is_array($subReply))
                                            @php
                                                $subReplyId = isset($subReply['comment_id']) ? $subReply['comment_id'] : 0;
                                                $subReplyEmployeeName = isset($subReply['employee_name']) ? $subReply['employee_name'] : '';
                                                $subReplyEmployeeId = isset($subReply['employee_id']) ? $subReply['employee_id'] : 0;
                                                $subReplyText = isset($subReply['comment']) ? $subReply['comment'] : '';
                                                $subReplyCreatedAt = isset($subReply['created_at']) ? $subReply['created_at'] : '';
                                                $subReplyReplies = isset($subReply['replies']) && is_array($subReply['replies']) ? $subReply['replies'] : array();
                                            @endphp
                                            <div class="reply-item" id="comment-{{ $subReplyId }}" data-level="2">
                                                <div class="comment-header">
                                                    <div class="comment-user">
                                                        <div class="comment-avatar reply-avatar level-2-avatar">{{ substr($subReplyEmployeeName, 0, 1) }}</div>
                                                        <div>
                                                            <strong>{{ $subReplyEmployeeName }}</strong>
                                                            <span class="badge bg-secondary ms-1">رد</span>
                                                            @if(count($subReplyReplies) > 0)
                                                            <span class="badge bg-info ms-1"><i class="fas fa-reply"></i> {{ count($subReplyReplies) }} ردود</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    <div class="comment-actions">
                                                        <small class="text-muted">{{ !empty($subReplyCreatedAt) ? \Carbon\Carbon::parse($subReplyCreatedAt)->diffForHumans() : '' }}</small>
                                                        @if($subReplyEmployeeId == $currentUserId)
                                                            @if(count($subReplyReplies) == 0)
                                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteComment({{ $subReplyId }})" title="حذف الرد">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                            @else
                                                            <span class="btn btn-sm btn-outline-secondary" title="لا يمكن حذف رد يحتوي على ردود">
                                                                <i class="fas fa-lock"></i>
                                                            </span>
                                                            @endif
                                                        @endif
                                                    </div>
                                                </div>
                                                <p class="comment-text">{{ $subReplyText }}</p>
                                                <button class="btn btn-sm btn-outline-primary reply-btn" onclick="showReplyForm({{ $subReplyId }}, '{{ addslashes($subReplyEmployeeName) }}')">
                                                    <i class="fas fa-reply"></i> رد
                                                </button>
                                                <div class="reply-form-container" id="reply-form-{{ $subReplyId }}" style="display:none;">
                                                    <form onsubmit="submitReply(event, {{ $taskId }}, {{ $subReplyId }})">
                                                        @csrf
                                                        <div class="input-group">
                                                            <input type="text" class="form-control" placeholder="أضف رداً على {{ $subReplyEmployeeName }}..." required>
                                                            <button class="btn btn-primary" type="submit"><i class="fas fa-paper-plane"></i></button>
                                                            <button class="btn btn-secondary" type="button" onclick="hideReplyForm({{ $subReplyId }})">إلغاء</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                            @endif
                                            @endforeach
                                        </div>
                                        @endif
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
    </div>
    @endif
</div>
@endsection

@push('styles')
<style>
    .task-detail-card {
        background: #fff;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.06);
        margin-bottom: 24px;
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
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 16px;
        margin-bottom: 20px;
    }

    .meta-item {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .meta-label {
        font-size: 12px;
        color: #6c757d;
        font-weight: 500;
    }

    .meta-label i {
        margin-left: 4px;
        width: 16px;
    }

    .meta-value {
        font-size: 15px;
        font-weight: 500;
        color: #1a2332;
    }

    .progress-container {
        background: #f8f9fa;
        padding: 16px;
        border-radius: 8px;
    }

    .progress-label {
        font-size: 13px;
        font-weight: 500;
        color: #495057;
    }

    .progress-value {
        font-size: 14px;
        font-weight: 600;
        color: #007bff;
    }

    .progress {
        height: 8px;
        border-radius: 4px;
        background: #e9ecef;
    }

    .progress-bar.bg-gradient {
        background: linear-gradient(90deg, #007bff, #20c997);
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
        padding: 16px 20px 0 20px;
        border-bottom: 2px solid #f1f3f5;
        gap: 4px;
    }

    .nav-tabs .nav-link {
        border: none;
        padding: 10px 20px;
        border-radius: 8px 8px 0 0;
        color: #6c757d;
        font-weight: 500;
        font-size: 14px;
        transition: all 0.2s ease;
    }

    .nav-tabs .nav-link:hover {
        background: #f8f9fa;
        color: #1a2332;
    }

    .nav-tabs .nav-link.active {
        background: #007bff;
        color: #fff;
    }

    .nav-tabs .nav-link .badge {
        font-size: 11px;
    }

    .nav-tabs .nav-link.active .badge {
        background: rgba(255,255,255,0.3);
        color: #fff;
    }

    .tab-content {
        padding: 20px;
    }

    .timeline {
        position: relative;
        padding-left: 28px;
        max-height: 350px;
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

    .timeline-item:last-child {
        margin-bottom: 0;
    }

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

    .comments-section {
        max-height: 350px;
        overflow-y: auto;
        margin-bottom: 16px;
    }

    .comment-item {
        padding: 16px 0;
        border-bottom: 1px solid #f1f3f5;
    }

    .comment-item:last-child {
        border-bottom: none;
    }

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

    .level-2-avatar {
        width: 24px;
        height: 24px;
        font-size: 10px;
        background: linear-gradient(135deg, #20c997, #17a2b8);
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

    .reply-form-container {
        margin: 8px 0 0 42px;
    }

    .reply-form-container .input-group {
        max-width: 450px;
    }

    .reply-form-container .input-group .form-control {
        font-size: 13px;
        border-radius: 8px 0 0 8px;
    }

    .reply-form-container .input-group .btn {
        border-radius: 0;
        font-size: 13px;
    }

    .reply-form-container .input-group .btn-secondary {
        border-radius: 0 8px 8px 0;
    }

    .replies-container {
        margin-top: 12px;
        padding-left: 42px;
        border-left: 2px solid #e9ecef;
    }

    .replies-container.level-1 {
        border-left-color: #fd7e14;
    }

    .replies-container.level-2 {
        padding-left: 35px;
        border-left-color: #20c997;
    }

    .reply-item {
        padding: 12px 0;
        border-bottom: 1px solid #f1f3f5;
    }

    .reply-item:last-child {
        border-bottom: none;
    }

    .comment-form {
        padding-top: 16px;
        border-top: 2px solid #f1f3f5;
    }

    .comment-form .input-group {
        border-radius: 8px;
        overflow: hidden;
    }

    .comment-form .form-control {
        border: 1px solid #e9ecef;
        border-radius: 8px 0 0 8px;
        padding: 10px 16px;
        font-size: 14px;
        transition: all 0.2s ease;
    }

    .comment-form .form-control:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.15);
    }

    .comment-form .btn-primary {
        border-radius: 0 8px 8px 0;
        padding: 10px 24px;
    }

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

    .empty-state p {
        margin: 0;
        font-size: 15px;
    }

    @media (max-width: 768px) {
        .task-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .task-status-badge {
            align-self: flex-start;
        }

        .task-meta-grid {
            grid-template-columns: 1fr 1fr;
        }

        .comment-text {
            margin-left: 0;
        }

        .reply-btn {
            margin-left: 0;
        }

        .reply-form-container {
            margin-left: 0;
        }

        .replies-container {
            padding-left: 20px;
        }

        .replies-container.level-2 {
            padding-left: 15px;
        }
    }

    @media (max-width: 480px) {
        .task-meta-grid {
            grid-template-columns: 1fr;
        }

        .nav-tabs .nav-link {
            font-size: 12px;
            padding: 8px 12px;
        }

        .replies-container {
            padding-left: 10px;
        }

        .replies-container.level-2 {
            padding-left: 5px;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    function submitComment(event, taskId) {
        event.preventDefault();

        var commentInput = document.getElementById('commentInput');
        var comment = commentInput.value.trim();

        if (!comment) {
            alert('الرجاء إدخال تعليق');
            return;
        }

        var token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        fetch('/operational/task/' + taskId + '/comment', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token
            },
            body: JSON.stringify({
                comment: comment,
                parent_comment_id: null
            })
        })
        .then(function(response) {
            return response.json();
        })
        .then(function(data) {
            if (data.success) {
                commentInput.value = '';
                location.reload();
            } else {
                alert('خطأ: ' + (data.error || 'فشل إضافة التعليق'));
            }
        })
        .catch(function(error) {
            alert('خطأ: ' + error);
        });
    }

    function submitReply(event, taskId, parentCommentId) {
        event.preventDefault();

        var form = event.target;
        var input = form.querySelector('input[type="text"]');
        var comment = input.value.trim();

        if (!comment) {
            alert('الرجاء إدخال رد');
            return;
        }

        var token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        fetch('/operational/task/' + taskId + '/comment', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token
            },
            body: JSON.stringify({
                comment: comment,
                parent_comment_id: parentCommentId
            })
        })
        .then(function(response) {
            return response.json();
        })
        .then(function(data) {
            if (data.success) {
                location.reload();
            } else {
                alert('خطأ: ' + (data.error || 'فشل إضافة الرد'));
            }
        })
        .catch(function(error) {
            alert('خطأ: ' + error);
        });
    }

    function showReplyForm(commentId, employeeName) {
        var formContainer = document.getElementById('reply-form-' + commentId);
        if (formContainer) {
            var allForms = document.querySelectorAll('.reply-form-container');
            allForms.forEach(function(el) {
                if (el.id !== 'reply-form-' + commentId) {
                    el.style.display = 'none';
                }
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
        if (formContainer) {
            formContainer.style.display = 'none';
        }
    }

    function deleteComment(commentId) {
    if (!confirm('هل أنت متأكد من حذف هذا التعليق؟')) {
        return;
    }

    var token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    fetch('/operational/comment/' + commentId, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token
        }
    })
    .then(function(response) {
        return response.json();
    })
    .then(function(data) {
        if (data.success) {
            var commentElement = document.getElementById('comment-' + commentId);
            if (commentElement) {
                commentElement.remove();
            }
            var commentsSection = document.getElementById('commentsSection');
            var emptyState = commentsSection.querySelector('.empty-state');
            var items = commentsSection.querySelectorAll('.comment-item');
            if (emptyState && items.length === 0) {
                commentsSection.innerHTML = '<div class="empty-state"><i class="fas fa-comments fa-2x text-muted"></i><p class="text-muted">لا توجد تعليقات</p></div>';
            }
            var badge = document.getElementById('comments-count');
            if (badge) {
                var current = parseInt(badge.textContent) || 0;
                badge.textContent = current > 0 ? current - 1 : 0;
            }
        } else {
            alert('خطأ: ' + (data.error || 'فشل حذف التعليق'));
        }
    })
    .catch(function(error) {
        alert('خطأ: ' + error);
    });
}

</script>
@endpush

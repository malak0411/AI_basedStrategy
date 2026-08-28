@extends('layouts.app')

@section('title', 'تفاصيل المهمة التشغيلية')

@section('content')

<div class="container-fluid px-4">
    <a href="{{ route('operational.kanban') }}" class="btn btn-outline-secondary mb-3">
        <i class="fas fa-arrow-right"></i> العودة للوحة المهام
    </a>
    

    @if(empty($task))
    <div class="alert alert-info">المهمة غير موجودة</div>
    @else
    <div class="row">
        <div class="col-lg-8">
            <div class="card-custom mb-4">
                <h4>{{ $task['task_name'] ?? $task['title'] ?? '' }}</h4>
                <p class="text-muted">{{ $task['description'] ?? '' }}</p>
                <div class="row mt-3">
                    <div class="col-md-3">
                        <small class="text-muted">الحالة</small>
                        <div><span class="badge bg-{{ ($task['status'] ?? 0) == 4 ? 'success' : 'warning' }}">{{ $task['status_name'] ?? '' }}</span></div>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted">المسؤول</small>
                        <div>{{ $task['assigned_to_name'] ?? 'غير معين' }}</div>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted">تاريخ البداية</small>
                        <div>{{ $task['start_date'] ?? 'غير محدد' }}</div>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted">تاريخ التسليم</small>
                        <div>{{ $task['due_date'] ?? $task['end_date'] ?? 'غير محدد' }}</div>
                    </div>
                </div>
            </div>

            <div class="mt-3">
                <a href="{{ route('task.attachments.index', $task['task_id'] ?? $task['id'] ?? 0) }}" class="btn btn-outline-info btn-sm">
                <i class="fas fa-paperclip"></i> إدارة المرفقات
                @if(!empty($attachments))
                    <span class="badge bg-primary ms-1">{{ count($attachments) }}</span>
                @endif
                </a>
            </div>
                            <br>

            <div class="card-custom">
                <h5><i class="fas fa-history ml-2"></i>سجل التقدم</h5>
                @if(empty($logs))
                <p class="text-muted text-center py-3">لا توجد سجلات تقدم</p>
                @else
                @foreach($logs as $log)
                <div class="border-bottom py-2">
                    <div class="d-flex justify-content-between">
                        <strong>{{ $log['employee_name'] ?? '' }}</strong>
                        <small class="text-muted">{{ $log['log_time'] ?? '' }}</small>
                    </div>
                    <p class="mb-0 small">{{ $log['notes'] ?? '' }}</p>
                    <small class="text-muted">التقدم: {{ $log['progress_percent'] ?? 0 }}%</small>
                </div>
                @endforeach
                @endif
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

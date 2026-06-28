@extends('layouts.app')

@section('title', 'تفاصيل المهمة')

@section('content')
<div class="container-fluid px-4">
    <a href="{{ route('tasks.index') }}" class="btn btn-outline-secondary mb-3">
        <i class="fas fa-arrow-right"></i> العودة للقائمة
    </a>

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if(empty($task))
        <div class="alert alert-info">المهمة غير متوفرة.</div>
    @else
        <div class="card-custom">
            <div class="d-flex justify-content-between align-items-start mb-4">
                <h4>{{ $task['task_name'] ?? $task['title'] ?? 'اسم المهمة' }}</h4>
                @php
                    $status = $task['status'] ?? 'pending';
                    $labels = [
                        'completed' => 'مكتمل',
                        'in_progress' => 'قيد التنفيذ',
                        'pending' => 'معلق',
                        'delayed' => 'متأخر',
                    ];
                    $colors = [
                        'completed' => 'success',
                        'in_progress' => 'info',
                        'pending' => 'warning',
                        'delayed' => 'danger',
                    ];
                @endphp
                <span class="badge bg-{{ $colors[$status] ?? 'secondary' }} fs-6">
                    {{ $labels[$status] ?? $status }}
                </span>
            </div>

            <p class="text-muted">{{ $task['description'] ?? 'لا يوجد وصف' }}</p>

            <div class="row mt-4">
                <div class="col-md-4">
                    <strong>تاريخ البداية:</strong> {{ $task['start_date'] ?? 'غير محدد' }}
                </div>
                <div class="col-md-4">
                    <strong>تاريخ التسليم:</strong> {{ $task['due_date'] ?? 'غير محدد' }}
                </div>
                <div class="col-md-4">
                    <strong>المسؤول:</strong> {{ $task['assigned_to_name'] ?? session('user_name') }}
                </div>
            </div>

            <div class="mt-4">
                <strong>التقدم:</strong>
                <div class="progress mt-2" style="height: 12px;">
                    <div class="progress-bar bg-{{ ($task['progress'] ?? 0) >= 80 ? 'success' : 'info' }}" 
                         style="width: {{ $task['progress'] ?? 0 }}%">
                    </div>
                </div>
                <small class="text-muted">{{ $task['progress'] ?? 0 }}% مكتمل</small>
            </div>

            {{-- ملاحظات أو تعليقات (إن وجدت) --}}
            @if(!empty($task['comments']))
                <hr>
                <h5>التعليقات</h5>
                @foreach($task['comments'] as $comment)
                    <div class="border p-2 mb-2 rounded">
                        <strong>{{ $comment['user'] ?? 'مستخدم' }}</strong>
                        <small class="text-muted">- {{ $comment['created_at'] ?? '' }}</small>
                        <p class="mb-0">{{ $comment['content'] ?? '' }}</p>
                    </div>
                @endforeach
            @endif
        </div>
    @endif
</div>
@endsection

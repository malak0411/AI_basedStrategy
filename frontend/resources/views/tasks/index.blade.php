@extends('layouts.app')

@section('title', 'قائمة المهام')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3><i class="fas fa-tasks ml-2"></i>قائمة المهام</h3>
        <span class="text-muted">{{ session('user_name') }}</span>
    </div>

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if(isset($error))
        <div class="alert alert-warning">{{ $error }}</div>
    @endif

    @if(empty($tasks))
        <div class="card-custom text-center py-5">
            <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
            <h5>لا توجد مهام</h5>
            <p class="text-muted">لا توجد مهام معلقة حالياً.</p>
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-hover card-custom">
                <thead>
                    <tr>
                        <th>المهمة</th>
                        <th>الحالة</th>
                        <th>التقدم</th>
                        <th>تاريخ التسليم</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tasks as $task)
                    <tr>
                        <td>
                            <a href="{{ route('tasks.show', $task['id'] ?? 0) }}" class="text-decoration-none fw-bold">
                                {{ $task['task_name'] ?? $task['title'] ?? 'غير معروف' }}
                            </a>
                            <br>
                            <small class="text-muted">{{ Str::limit($task['description'] ?? '', 60) }}</small>
                        </td>
                        <td>
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
                            <span class="badge bg-{{ $colors[$status] ?? 'secondary' }}">
                                {{ $labels[$status] ?? $status }}
                            </span>
                        </td>
                        <td>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-{{ ($task['progress'] ?? 0) >= 80 ? 'success' : 'info' }}" 
                                     style="width: {{ $task['progress'] ?? 0 }}%">
                                </div>
                            </div>
                            <small>{{ $task['progress'] ?? 0 }}%</small>
                        </td>
                        <td>{{ $task['due_date'] ?? 'غير محدد' }}</td>
                        <td>
                            <a href="{{ route('tasks.show', $task['id'] ?? 0) }}" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection

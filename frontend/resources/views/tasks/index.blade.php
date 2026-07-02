@extends('layouts.app')

@section('title', 'قائمة المهام')

@push('styles')
<style>
    .task-row { transition: all 0.2s ease; }
    .task-row:hover { background: #f8fafc !important; }
    .progress-thin { height: 6px; border-radius: 3px; }
    .badge-status { font-size: 0.8rem; padding: 0.4em 0.8em; }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3><i class="fas fa-tasks ml-2"></i>قائمة المهام</h3>
            <p class="text-muted mb-0">{{ session('user_name') }} - {{ session('user_role') }}</p>
        </div>
        <a href="{{ route('tasks.create') }}" class="btn-gold">
            <i class="fas fa-plus"></i> مهمة جديدة
        </a>
    </div>

    {{-- رسائل --}}
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- فلترة --}}
    <div class="card-custom mb-4">
        <div class="row">
            <div class="col-md-3">
                <select class="form-control" onchange="filterTasks()" id="statusFilter">
                    <option value="all">جميع الحالات</option>
                    <option value="1">معلق</option>
                    <option value="2">قيد التنفيذ</option>
                    <option value="3">متأخر</option>
                    <option value="4">مكتمل</option>
                </select>
            </div>
        </div>
    </div>

    @if(empty($tasks))
        <div class="card-custom text-center py-5">
            <i class="fas fa-clipboard-list fa-4x text-muted mb-3"></i>
            <h5>لا توجد مهام</h5>
            <p class="text-muted">لا توجد مهام معلقة حالياً</p>
            <a href="{{ route('tasks.create') }}" class="btn btn-primary mt-2">
                <i class="fas fa-plus"></i> إنشاء مهمة جديدة
            </a>
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-hover card-custom">
                <thead class="bg-light">
                    <tr>
                        <th style="width: 40px;">#</th>
                        <th>المهمة</th>
                        <th>الحالة</th>
                        <th>الأولوية</th>
                        <th>التقدم</th>
                        <th>تاريخ التسليم</th>
                        <th style="width: 100px;">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tasks as $task)
                    <tr class="task-row">
                        <td>{{ $task['id'] ?? $loop->iteration }}</td>
                        <td>
                            <a href="{{ route('tasks.show', $task['id']) }}" class="text-decoration-none fw-bold">
                                {{ $task['task_name'] ?? $task['title'] ?? 'غير معروف' }}
                            </a>
                            @if(!empty($task['description']))
                                <br><small class="text-muted">{{ Str::limit($task['description'], 80) }}</small>
                            @endif
                        </td>
                        <td>
                            @php
                                $status = $task['status'] ?? 1;
                                $statusLabels = [1 => 'معلق', 2 => 'قيد التنفيذ', 3 => 'متأخر', 4 => 'مكتمل', 5 => 'ملغي'];
                                $statusColors = [1 => 'warning', 2 => 'info', 3 => 'danger', 4 => 'success', 5 => 'secondary'];
                                $label = $statusLabels[$status] ?? $task['status_name'] ?? 'غير معروف';
                                $color = $statusColors[$status] ?? 'secondary';
                            @endphp
                            <span class="badge bg-{{ $color }} badge-status">
                                <i class="fas fa-circle me-1" style="font-size: 6px;"></i>
                                {{ $label }}
                            </span>
                        </td>
                        <td>
                            @php
                                $priority = $task['priority'] ?? 2;
                                $priorityLabels = [1 => 'منخفضة', 2 => 'متوسطة', 3 => 'عالية', 4 => 'حرجة'];
                                $priorityColors = [1 => 'success', 2 => 'info', 3 => 'warning', 4 => 'danger'];
                                $pLabel = $priorityLabels[$priority] ?? $task['priority_name'] ?? 'متوسطة';
                                $pColor = $priorityColors[$priority] ?? 'info';
                            @endphp
                            <span class="badge bg-{{ $pColor }} badge-status">{{ $pLabel }}</span>
                        </td>
                        <td style="width: 130px;">
                            @php $progress = $task['progress'] ?? 0; @endphp
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress flex-grow-1 progress-thin">
                                    <div class="progress-bar bg-{{ $progress >= 80 ? 'success' : ($progress >= 40 ? 'info' : 'warning') }}" 
                                         style="width: {{ $progress }}%"></div>
                                </div>
                                <small class="fw-bold">{{ $progress }}%</small>
                            </div>
                        </td>
                        <td>
                            @php
                                $dueDate = $task['due_date'] ?? $task['end_date'] ?? null;
                            @endphp
                            @if($dueDate)
                                <small class="{{ strtotime($dueDate) < time() && ($task['status'] ?? 1) != 4 ? 'text-danger fw-bold' : 'text-muted' }}">
                                    <i class="far fa-calendar-alt ml-1"></i>
                                    {{ $dueDate }}
                                </small>
                            @else
                                <small class="text-muted">غير محدد</small>
                            @endif
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('tasks.show', $task['id']) }}" class="btn btn-outline-info" title="عرض">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('tasks.edit', $task['id']) }}" class="btn btn-outline-primary" title="تعديل">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        {{-- إحصائية سريعة --}}
        <div class="mt-3 text-muted small">
            <i class="fas fa-info-circle ml-1"></i>
            إجمالي المهام: <strong>{{ count($tasks) }}</strong>
            @php
                $completed = collect($tasks)->where('status', 4)->count();
                $delayed = collect($tasks)->where('status', 3)->count();
            @endphp
            | مكتملة: <strong class="text-success">{{ $completed }}</strong>
            | متأخرة: <strong class="text-danger">{{ $delayed }}</strong>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    function filterTasks() {
        const status = document.getElementById('statusFilter').value;
        window.location.href = '{{ route("tasks.index") }}?status=' + status;
    }
    
    // تعيين قيمة الفلتر من URL
    const urlParams = new URLSearchParams(window.location.search);
    const statusParam = urlParams.get('status');
    if (statusParam) {
        document.getElementById('statusFilter').value = statusParam;
    }
</script>
@endpush

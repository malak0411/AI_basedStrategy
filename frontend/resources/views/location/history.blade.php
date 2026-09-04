@extends('layouts.app')

@section('title', 'سجل المواقع')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3><i class="fas fa-history ml-2"></i>سجل المواقع</h3>
            <p class="text-muted mb-0">سجل تتبع مواقع الموظفين</p>
        </div>
        <a href="{{ route('location.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-right"></i> العودة
        </a>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="card-custom">
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-3">
                    <select id="employeeFilter" class="form-control">
                        <option value="">جميع الموظفين</option>
                        @foreach($employees ?? [] as $emp)
                        <option value="{{ $emp['employee_id'] }}" {{ request('employee_id') == $emp['employee_id'] ? 'selected' : '' }}>
                            {{ $emp['full_name'] }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select id="taskFilter" class="form-control">
                        <option value="">جميع المهام</option>
                        @foreach($tasks ?? [] as $task)
                        <option value="{{ $task['task_id'] }}" {{ request('task_id') == $task['task_id'] ? 'selected' : '' }}>
                            {{ $task['title'] }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" id="dateFrom" class="form-control" value="{{ request('date_from') }}" placeholder="من">
                </div>
                <div class="col-md-2">
                    <input type="date" id="dateTo" class="form-control" value="{{ request('date_to') }}" placeholder="إلى">
                </div>
                <div class="col-md-2">
                    <button id="filterBtn" class="btn btn-primary w-100"><i class="fas fa-filter"></i> تصفية</button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>الموظف</th>
                            <th>المهمة</th>
                            <th>خط العرض</th>
                            <th>خط الطول</th>
                            <th>الدقة</th>
                            <th>المصدر</th>
                            <th>وقت التسجيل</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($locations as $location)
                        <tr>
                            <td>{{ $location['location_id'] ?? '' }}</td>
                            <td>
                                <strong>{{ $location['employee']['full_name'] ?? 'غير معروف' }}</strong>
                                <br><small class="text-muted">{{ $location['employee']['employee_number'] ?? '' }}</small>
                            </td>
                            <td>{{ $location['task']['title'] ?? 'غير مرتبط' }}</td>
                            <td>{{ number_format($location['latitude'] ?? 0, 6) }}</td>
                            <td>{{ number_format($location['longitude'] ?? 0, 6) }}</td>
                            <td>{{ isset($location['accuracy']) ? number_format($location['accuracy'], 2) . ' م' : 'غير متاحة' }}</td>
                            <td>{{ $location['source'] ?? 'غير محدد' }}</td>
                            <td>{{ isset($location['recorded_at']) ? \Carbon\Carbon::parse($location['recorded_at'])->format('Y-m-d H:i:s') : '' }}</td>
                            <td>
                                <a href="{{ route('location.employee', $location['employee_id']) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-user"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-4">
                                <i class="fas fa-history fa-2x text-muted d-block mb-2"></i>
                                <p class="text-muted">لا توجد سجلات مواقع</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if(isset($pagination['total_pages']) && $pagination['total_pages'] > 1)
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div>
                    عرض {{ ($pagination['page'] - 1) * $pagination['per_page'] + 1 }} - {{ min($pagination['page'] * $pagination['per_page'], $pagination['total']) }} من {{ $pagination['total'] }}
                </div>
                <nav>
                    <ul class="pagination">
                        @for($i = 1; $i <= $pagination['total_pages']; $i++)
                        <li class="page-item {{ $i == $pagination['page'] ? 'active' : '' }}">
                            <a class="page-link" href="?page={{ $i }}&per_page={{ $pagination['per_page'] }}">{{ $i }}</a>
                        </li>
                        @endfor
                    </ul>
                </nav>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var employeeFilter = document.getElementById('employeeFilter');
    var taskFilter = document.getElementById('taskFilter');
    var dateFrom = document.getElementById('dateFrom');
    var dateTo = document.getElementById('dateTo');
    var filterBtn = document.getElementById('filterBtn');

    function applyFilters() {
        var url = new URL(window.location.href);
        var employee = employeeFilter.value;
        var task = taskFilter.value;
        var from = dateFrom.value;
        var to = dateTo.value;

        if (employee) { url.searchParams.set('employee_id', employee); } else { url.searchParams.delete('employee_id'); }
        if (task) { url.searchParams.set('task_id', task); } else { url.searchParams.delete('task_id'); }
        if (from) { url.searchParams.set('date_from', from); } else { url.searchParams.delete('date_from'); }
        if (to) { url.searchParams.set('date_to', to); } else { url.searchParams.delete('date_to'); }

        window.location.href = url.toString();
    }

    filterBtn.addEventListener('click', applyFilters);
});
</script>
@endpush

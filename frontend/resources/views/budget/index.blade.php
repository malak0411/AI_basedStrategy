@extends('layouts.app')

@section('title', 'إدارة الميزانية')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3><i class="fas fa-coins ml-2"></i>إدارة الميزانية</h3>
            <p class="text-muted mb-0">متابعة الميزانيات المخصصة والمصروفات والعمليات المالية المرتبطة بالبرامج والمبادرات والمهام</p>
        </div>
        <div>
            <a href="{{ route('budget.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> إضافة ميزانية
            </a>
        </div>
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

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card-custom text-center">
                <div class="card-body">
                    <h6 class="text-muted">إجمالي الميزانيات</h6>
                    <h2 class="text-primary">{{ $totalBudget ?? 0 }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-custom text-center">
                <div class="card-body">
                    <h6 class="text-muted">إجمالي المخصص</h6>
                    <h2 class="text-success">{{ number_format($totalAllocated ?? 0, 2) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-custom text-center">
                <div class="card-body">
                    <h6 class="text-muted">إجمالي المصروف</h6>
                    <h2 class="text-warning">{{ number_format($totalSpent ?? 0, 2) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-custom text-center">
                <div class="card-body">
                    <h6 class="text-muted">الرصيد المتبقي</h6>
                    <h2 class="text-info">{{ number_format($totalRemaining ?? 0, 2) }}</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card-custom">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">نسبة التنفيذ المالي</h6>
                        <span class="fw-bold {{ $executionPercentage > 100 ? 'text-danger' : 'text-success' }}">
                            {{ number_format($executionPercentage, 2) }}%
                        </span>
                    </div>
                    <div class="progress" style="height:25px;">
                        <div class="progress-bar {{ $executionPercentage > 100 ? 'bg-danger' : 'bg-success' }}" 
                             role="progressbar" 
                             style="width: {{ min($executionPercentage, 100) }}%;" 
                             aria-valuenow="{{ $executionPercentage }}" 
                             aria-valuemin="0" 
                             aria-valuemax="100">
                            {{ number_format($executionPercentage, 2) }}%
                        </div>
                    </div>
                    @if($overspentCount > 0)
                    <small class="text-danger mt-1 d-block">
                        <i class="fas fa-exclamation-triangle"></i> يوجد {{ $overspentCount }} ميزانية(ات) متجاوزة
                    </small>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card-custom">
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <h6 class="text-muted">المخصص</h6>
                            <h5 class="text-success">{{ number_format($totalAllocated ?? 0, 2) }}</h5>
                        </div>
                        <div class="col-6">
                            <h6 class="text-muted">المصروف</h6>
                            <h5 class="text-warning">{{ number_format($totalSpent ?? 0, 2) }}</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card-custom">
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-3">
                    <input type="text" id="searchInput" class="form-control" placeholder="بحث..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <select id="fiscalYearFilter" class="form-control">
                        <option value="">جميع السنوات</option>
                        @foreach(($options['fiscal_years'] ?? []) as $year)
                        <option value="{{ $year }}" {{ request('fiscal_year') == $year ? 'selected' : '' }}>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select id="typeFilter" class="form-control">
                        <option value="">جميع الأنواع</option>
                        @foreach(($options['budget_types'] ?? []) as $type)
                        <option value="{{ $type }}" {{ request('budgetable_type') == $type ? 'selected' : '' }}>
                            {{ $type == 'program' ? 'برنامج' : ($type == 'initiative' ? 'مبادرة' : ($type == 'major_task' ? 'مهمة رئيسية' : 'مهمة تشغيلية')) }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select id="departmentFilter" class="form-control">
                        <option value="">جميع الأقسام</option>
                        @foreach(($options['departments'] ?? []) as $dept)
                        <option value="{{ $dept['id'] }}" {{ request('department_id') == $dept['id'] ? 'selected' : '' }}>
                            {{ $dept['name'] }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button id="filterBtn" class="btn btn-primary w-100"><i class="fas fa-filter"></i> تصفية</button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>نوع الميزانية</th>
                            <th>العنصر المرتبط</th>
                            <th>القسم</th>
                            <th>السنة المالية</th>
                            <th>المخصص</th>
                            <th>المصروف</th>
                            <th>المتبقي</th>
                            <th>نسبة التنفيذ</th>
                            <th>الحالة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($budgetLines as $line)
                        @php
                            $typeLabels = [
                                'program' => 'برنامج',
                                'initiative' => 'مبادرة',
                                'major_task' => 'مهمة رئيسية',
                                'operational_task' => 'مهمة تشغيلية'
                            ];
                            $typeLabel = $typeLabels[$line['budgetable_type'] ?? ''] ?? $line['budgetable_type'] ?? '';
                            
                            $status = $line['status'] ?? 'بدون ميزانية';
                            $statusClass = $status == 'ضمن الميزانية' ? 'success' : 
                                         ($status == 'مكتملة' ? 'info' : 
                                         ($status == 'متجاوزة' ? 'danger' : 'secondary'));
                            
                            $execution = $line['execution_percentage'] ?? 0;
                            $progressClass = $execution > 100 ? 'bg-danger' : 
                                            ($execution >= 80 ? 'bg-success' : 
                                            ($execution >= 50 ? 'bg-warning' : 'bg-info'));
                        @endphp
                        <tr>
                            <td>
                                <span class="badge bg-secondary">{{ $typeLabel }}</span>
                            </td>
                            <td>
                                <strong>{{ $line['budgetable_name'] ?? 'غير معروف' }}</strong>
                            </td>
                            <td>{{ $line['department']['name'] ?? 'غير محدد' }}</td>
                            <td>{{ $line['fiscal_year'] ?? '' }}</td>
                            <td class="text-success fw-bold">{{ number_format($line['allocated_amount'] ?? 0, 2) }}</td>
                            <td class="text-warning">{{ number_format($line['spent_amount'] ?? 0, 2) }}</td>
                            <td class="{{ ($line['remaining_amount'] ?? 0) < 0 ? 'text-danger' : 'text-info' }}">
                                {{ number_format($line['remaining_amount'] ?? 0, 2) }}
                            </td>
                            <td>
                                <div class="progress" style="height:20px;width:80px;">
                                    <div class="progress-bar {{ $progressClass }}" 
                                         role="progressbar" 
                                         style="width: {{ min($execution, 100) }}%;" 
                                         aria-valuenow="{{ $execution }}" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                        {{ number_format($execution, 2) }}%
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-{{ $statusClass }}">{{ $status }}</span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="{{ route('budget.show', $line['budget_id']) }}" class="btn btn-sm btn-outline-info" title="عرض التفاصيل">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('budget.edit', $line['budget_id']) }}" class="btn btn-sm btn-outline-primary" title="تعديل">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="{{ route('budget.transactions', $line['budget_id']) }}" class="btn btn-sm btn-outline-secondary" title="العمليات">
                                        <i class="fas fa-list"></i>
                                    </a>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteBudget({{ $line['budget_id'] }})" title="حذف">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center py-4">
                                <i class="fas fa-coins fa-2x text-muted d-block mb-2"></i>
                                <p class="text-muted">لا توجد ميزانيات مسجلة</p>
                                <a href="{{ route('budget.create') }}" class="btn btn-primary btn-sm">إضافة ميزانية</a>
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

<form id="deleteForm" method="POST" style="display:none;">
    @csrf
    @method('DELETE')
</form>
@endsection

@push('scripts')
<script>
    function deleteBudget(id) {
        if (confirm('هل أنت متأكد من حذف هذه الميزانية؟')) {
            var form = document.getElementById('deleteForm');
            form.action = '/budget/' + id;
            form.submit();
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        var searchInput = document.getElementById('searchInput');
        var fiscalYearFilter = document.getElementById('fiscalYearFilter');
        var typeFilter = document.getElementById('typeFilter');
        var departmentFilter = document.getElementById('departmentFilter');
        var filterBtn = document.getElementById('filterBtn');

        function applyFilters() {
            var url = new URL(window.location.href);
            var search = searchInput.value.trim();
            var fiscalYear = fiscalYearFilter.value;
            var type = typeFilter.value;
            var department = departmentFilter.value;

            if (search) { url.searchParams.set('search', search); } else { url.searchParams.delete('search'); }
            if (fiscalYear) { url.searchParams.set('fiscal_year', fiscalYear); } else { url.searchParams.delete('fiscal_year'); }
            if (type) { url.searchParams.set('budgetable_type', type); } else { url.searchParams.delete('budgetable_type'); }
            if (department) { url.searchParams.set('department_id', department); } else { url.searchParams.delete('department_id'); }

            window.location.href = url.toString();
        }

        filterBtn.addEventListener('click', applyFilters);
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') applyFilters();
        });
    });
</script>
@endpush

@extends('layouts.app')

@section('title', 'إدارة المخاطر')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3><i class="fas fa-shield-alt ml-2"></i>إدارة المخاطر</h3>
            <p class="text-muted mb-0">متابعة المخاطر المرتبطة بالمهام التشغيلية وإجراءات المعالجة</p>
        </div>
        <div>
            <a href="{{ route('risks.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> إضافة خطر
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
                    <h6 class="text-muted">إجمالي المخاطر</h6>
                    <h2 class="text-primary">{{ $totalRisks ?? 0 }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-custom text-center">
                <div class="card-body">
                    <h6 class="text-muted">مخاطر مفتوحة</h6>
                    <h2 class="text-warning">{{ $openRisks ?? 0 }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-custom text-center">
                <div class="card-body">
                    <h6 class="text-muted">مخاطر متأخرة</h6>
                    <h2 class="text-danger">{{ $overdueRisks ?? 0 }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-custom text-center">
                <div class="card-body">
                    <h6 class="text-muted">متوسط درجة المخاطرة</h6>
                    <h2 class="text-info">
                        @php
                            $avgScore = 0;
                            $count = 0;
                            foreach($risks as $risk) {
                                if(isset($risk['risk_score'])) {
                                    $avgScore += $risk['risk_score'];
                                    $count++;
                                }
                            }
                            $avgScore = $count > 0 ? round($avgScore / $count) : 0;
                        @endphp
                        {{ $avgScore }}
                    </h2>
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
                    <select id="riskLevelFilter" class="form-control">
                        <option value="">جميع المستويات</option>
                        @foreach(($options['risk_levels'] ?? []) as $level)
                        <option value="{{ $level['risk_level_id'] }}" {{ request('risk_level_id') == $level['risk_level_id'] ? 'selected' : '' }}>
                            {{ $level['name_ar'] }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select id="statusFilter" class="form-control">
                        <option value="">جميع الحالات</option>
                        @foreach(($options['statuses'] ?? []) as $status)
                        <option value="{{ $status['status_id'] }}" {{ request('status_id') == $status['status_id'] ? 'selected' : '' }}>
                            {{ $status['name_ar'] }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select id="taskFilter" class="form-control">
                        <option value="">جميع المهام</option>
                        @foreach(($options['tasks'] ?? []) as $task)
                        <option value="{{ $task['task_id'] }}" {{ request('task_id') == $task['task_id'] ? 'selected' : '' }}>
                            {{ $task['title'] }}
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
                            <th>#</th>
                            <th>اسم الخطر</th>
                            <th>المهمة التشغيلية</th>
                            <th>المستوى</th>
                            <th>الاحتمال</th>
                            <th>التأثير</th>
                            <th>الدرجة</th>
                            <th>الحالة</th>
                            <th>تاريخ الاستهداف</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($risks as $risk)
                        <tr>
                            <td>{{ $risk['risk_id'] ?? '' }}</td>
                            <td>
                                <strong>{{ $risk['name'] ?? '' }}</strong>
                                @if(!empty($risk['description']))
                                <br><small class="text-muted">{{ Str::limit($risk['description'], 50) }}</small>
                                @endif
                            </td>
                            <td>{{ $risk['task']['title'] ?? 'غير مرتبط' }}</td>
                            <td>
                                @php
                                    $level = $risk['risk_level'] ?? [];
                                    $color = $level['color_hex'] ?? '#6c757d';
                                @endphp
                                <span class="badge" style="background-color: {{ $color }}; color: #fff;">
                                    {{ $level['name_ar'] ?? 'غير محدد' }}
                                </span>
                            </td>
                            <td>{{ $risk['probability'] ?? 0 }}</td>
                            <td>{{ $risk['impact'] ?? 0 }}</td>
                            <td>
                                <span class="fw-bold {{ ($risk['risk_score'] ?? 0) > 15 ? 'text-danger' : (($risk['risk_score'] ?? 0) > 8 ? 'text-warning' : 'text-success') }}">
                                    {{ $risk['risk_score'] ?? 0 }}
                                </span>
                            </td>
                            <td>
                                @php
                                    $status = $risk['status'] ?? [];
                                    $statusColor = $status['color_hex'] ?? '#6c757d';
                                @endphp
                                <span class="badge" style="background-color: {{ $statusColor }}; color: #fff;">
                                    {{ $status['name_ar'] ?? 'غير محدد' }}
                                </span>
                            </td>
                            <td>{{ isset($risk['target_date']) ? \Carbon\Carbon::parse($risk['target_date'])->format('Y-m-d') : 'غير محدد' }}</td>
                            <td>
                                <div class="btn-group">
                                    <a href="{{ route('risks.show', $risk['risk_id']) }}" class="btn btn-sm btn-outline-info" title="عرض التفاصيل">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('risks.edit', $risk['risk_id']) }}" class="btn btn-sm btn-outline-primary" title="تعديل">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="{{ route('risks.mitigations', $risk['risk_id']) }}" class="btn btn-sm btn-outline-secondary" title="إجراءات المعالجة">
                                        <i class="fas fa-tasks"></i>
                                        <span class="badge bg-primary ms-1">{{ $risk['mitigations_count'] ?? 0 }}</span>
                                    </a>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteRisk({{ $risk['risk_id'] }})" title="حذف">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center py-4">
                                <i class="fas fa-shield-alt fa-2x text-muted d-block mb-2"></i>
                                <p class="text-muted">لا توجد مخاطر مسجلة</p>
                                <a href="{{ route('risks.create') }}" class="btn btn-primary btn-sm">إضافة خطر</a>
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
    function deleteRisk(id) {
        if (confirm('هل أنت متأكد من حذف هذا الخطر؟')) {
            var form = document.getElementById('deleteForm');
            form.action = '/risks/' + id;
            form.submit();
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        var searchInput = document.getElementById('searchInput');
        var riskLevelFilter = document.getElementById('riskLevelFilter');
        var statusFilter = document.getElementById('statusFilter');
        var taskFilter = document.getElementById('taskFilter');
        var filterBtn = document.getElementById('filterBtn');

        function applyFilters() {
            var url = new URL(window.location.href);
            var search = searchInput.value.trim();
            var riskLevel = riskLevelFilter.value;
            var status = statusFilter.value;
            var task = taskFilter.value;

            if (search) { url.searchParams.set('search', search); } else { url.searchParams.delete('search'); }
            if (riskLevel) { url.searchParams.set('risk_level_id', riskLevel); } else { url.searchParams.delete('risk_level_id'); }
            if (status) { url.searchParams.set('status_id', status); } else { url.searchParams.delete('status_id'); }
            if (task) { url.searchParams.set('task_id', task); } else { url.searchParams.delete('task_id'); }

            window.location.href = url.toString();
        }

        filterBtn.addEventListener('click', applyFilters);
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') applyFilters();
        });
    });
</script>
@endpush

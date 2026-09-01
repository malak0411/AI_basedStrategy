@extends('layouts.app')

@section('title', 'إجراءات المعالجة')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3><i class="fas fa-tasks ml-2"></i>إجراءات المعالجة</h3>
            <p class="text-muted mb-0">#{{ $risk['risk_id'] ?? '' }} - {{ $risk['name'] ?? '' }}</p>
        </div>
        <div>
            <a href="{{ route('risks.mitigations.create', $risk['risk_id']) }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> إضافة إجراء
            </a>
            <a href="{{ route('risks.show', $risk['risk_id']) }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-right"></i> العودة
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

    <div class="card-custom">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>إجراء المعالجة</th>
                            <th>المهمة</th>
                            <th>المسؤول</th>
                            <th>الحالة</th>
                            <th>تاريخ الاستحقاق</th>
                            <th>تاريخ الإكمال</th>
                            <th>الملاحظات</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($mitigations as $mitigation)
                        <tr>
                            <td>{{ $mitigation['mitigation_id'] ?? '' }}</td>
                            <td>{{ $mitigation['action'] ?? '' }}</td>
                            <td>{{ $mitigation['task']['title'] ?? 'غير مرتبط' }}</td>
                            <td>{{ $mitigation['assigned_to']['full_name'] ?? 'غير معين' }}</td>
                            <td>
                                @php
                                    $mStatus = $mitigation['status'] ?? [];
                                    $mStatusColor = $mStatus['color_hex'] ?? '#6c757d';
                                @endphp
                                <span class="badge" style="background-color: {{ $mStatusColor }}; color: #fff;">
                                    {{ $mStatus['name_ar'] ?? 'غير محدد' }}
                                </span>
                            </td>
                            <td>{{ isset($mitigation['due_date']) ? \Carbon\Carbon::parse($mitigation['due_date'])->format('Y-m-d') : 'غير محدد' }}</td>
                            <td>{{ isset($mitigation['completed_at']) ? \Carbon\Carbon::parse($mitigation['completed_at'])->format('Y-m-d H:i') : 'غير مكتمل' }}</td>
                            <td>{{ Str::limit($mitigation['notes'] ?? '', 30) }}</td>
                            <td>
                                <div class="btn-group">
                                    <a href="{{ route('risks.mitigations.edit', $mitigation['mitigation_id']) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteMitigation({{ $mitigation['mitigation_id'] }})">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-4">
                                <i class="fas fa-tasks fa-2x text-muted d-block mb-2"></i>
                                <p class="text-muted">لا توجد إجراءات معالجة</p>
                                <a href="{{ route('risks.mitigations.create', $risk['risk_id']) }}" class="btn btn-primary btn-sm">إضافة إجراء</a>
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

<form id="deleteMitigationForm" method="POST" style="display:none;">
    @csrf
    @method('DELETE')
</form>
@endsection

@push('scripts')
<script>
    function deleteMitigation(id) {
        if (confirm('هل أنت متأكد من حذف هذا الإجراء؟')) {
            var form = document.getElementById('deleteMitigationForm');
            form.action = '/risks/mitigations/' + id;
            form.submit();
        }
    }
</script>
@endpush

@extends('layouts.app')

@section('title', 'تفاصيل الخطر')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3><i class="fas fa-shield-alt ml-2"></i>تفاصيل الخطر</h3>
            <p class="text-muted mb-0">#{{ $risk['risk_id'] ?? '' }} - {{ $risk['name'] ?? '' }}</p>
        </div>
        <div>
            <a href="{{ route('risks.edit', $risk['risk_id']) }}" class="btn btn-outline-primary">
                <i class="fas fa-edit"></i> تعديل
            </a>
            <a href="{{ route('risks.mitigations.create', $risk['risk_id']) }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> إضافة إجراء معالجة
            </a>
            <a href="{{ route('risks.mitigations', $risk['risk_id']) }}" class="btn btn-outline-secondary">
                <i class="fas fa-tasks"></i> إجراءات المعالجة
            </a>
            <a href="{{ route('risks.index') }}" class="btn btn-outline-secondary">
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

    <div class="row">
        <div class="col-md-8">
            <div class="card-custom">
                <div class="card-body">
                    <h5><i class="fas fa-info-circle text-primary me-2"></i>معلومات الخطر</h5>
                    <table class="table table-borderless">
                        <tr>
                            <td class="fw-bold" style="width:150px;">اسم الخطر</td>
                            <td>{{ $risk['name'] ?? '' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">الوصف</td>
                            <td>{{ $risk['description'] ?? 'لا يوجد وصف' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">المهمة التشغيلية</td>
                            <td>{{ $risk['task']['title'] ?? 'غير مرتبط' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">تاريخ الاستهداف</td>
                            <td>{{ isset($risk['target_date']) ? \Carbon\Carbon::parse($risk['target_date'])->format('Y-m-d') : 'غير محدد' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">تم التعريف بواسطة</td>
                            <td>{{ $risk['identified_by']['full_name'] ?? 'غير معروف' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">تاريخ التعريف</td>
                            <td>{{ isset($risk['identified_at']) ? \Carbon\Carbon::parse($risk['identified_at'])->format('Y-m-d H:i') : 'غير محدد' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">آخر تحديث</td>
                            <td>{{ isset($risk['updated_at']) ? \Carbon\Carbon::parse($risk['updated_at'])->format('Y-m-d H:i') : 'غير محدد' }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card-custom">
                <div class="card-body">
                    <h5><i class="fas fa-calculator text-success me-2"></i>حساب درجة الخطر</h5>
                    <div class="text-center">
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted">الاحتمال</small>
                                <h3 class="text-primary">{{ $risk['probability'] ?? 0 }}</h3>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">التأثير</small>
                                <h3 class="text-primary">{{ $risk['impact'] ?? 0 }}</h3>
                            </div>
                        </div>
                        <div class="mt-2">
                            <small class="text-muted">درجة الخطر</small>
                            <h2 class="fw-bold {{ ($risk['risk_score'] ?? 0) > 15 ? 'text-danger' : (($risk['risk_score'] ?? 0) > 8 ? 'text-warning' : 'text-success') }}">
                                {{ $risk['risk_score'] ?? 0 }}
                            </h2>
                        </div>
                        <div class="mt-2">
                            <small class="text-muted">مستوى الخطر</small>
                            @php
                                $level = $risk['risk_level'] ?? [];
                                $color = $level['color_hex'] ?? '#6c757d';
                            @endphp
                            <h3>
                                <span class="badge" style="background-color: {{ $color }}; color: #fff; font-size: 18px;">
                                    {{ $level['name_ar'] ?? 'غير محدد' }}
                                </span>
                            </h3>
                        </div>
                        <div class="mt-2">
                            <small class="text-muted">الحالة</small>
                            <h4>
                                @php
                                    $status = $risk['status'] ?? [];
                                    $statusColor = $status['color_hex'] ?? '#6c757d';
                                @endphp
                                <span class="badge" style="background-color: {{ $statusColor }}; color: #fff; font-size: 16px;">
                                    {{ $status['name_ar'] ?? 'غير محدد' }}
                                </span>
                            </h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card-custom mt-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5><i class="fas fa-tasks me-2"></i>إجراءات المعالجة</h5>
                <a href="{{ route('risks.mitigations.create', $risk['risk_id']) }}" class="btn btn-sm btn-primary">
                    <i class="fas fa-plus"></i> إضافة
                </a>
            </div>
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
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(array_slice($risk['mitigations'] ?? [], 0, 5) as $mitigation)
                        <tr>
                            <td>{{ $mitigation['mitigation_id'] ?? '' }}</td>
                            <td>{{ Str::limit($mitigation['action'] ?? '', 50) }}</td>
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
                            <td>
                                <a href="{{ route('risks.mitigations.edit', $mitigation['mitigation_id']) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteMitigation({{ $mitigation['mitigation_id'] }})">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-3">لا توجد إجراءات معالجة</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
                @if(count($risk['mitigations'] ?? []) > 5)
                <div class="text-center mt-2">
                    <a href="{{ route('risks.mitigations', $risk['risk_id']) }}" class="btn btn-sm btn-outline-primary">
                        عرض جميع الإجراءات ({{ count($risk['mitigations'] ?? []) }})
                    </a>
                </div>
                @endif
            </div>
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

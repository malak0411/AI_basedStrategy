@extends('layouts.app')

@section('title', 'تفاصيل المهمة الرئيسية')

@push('styles')
<style>
    .task-card {
        background: #fff;
        border-radius: 12px;
        padding: 16px;
        margin-bottom: 12px;
        border: 1px solid #e2e8f0;
        transition: all 0.2s;
    }
    .task-card:hover {
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    }
    .empty-state {
        text-align: center;
        padding: 60px 20px;
    }
    .empty-state i {
        font-size: 64px;
        color: #cbd5e0;
        margin-bottom: 16px;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <a href="{{ route('operational.major-tasks') }}" class="btn btn-outline-secondary mb-3">
        <i class="fas fa-arrow-right"></i> العودة للمهام الرئيسية
    </a>

    @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @php $tid = $majorTask['id'] ?? 0; @endphp

    <div class="card-custom mb-4">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <h4>{{ $majorTask['name'] ?? '' }}</h4>
                <p class="text-muted">{{ $majorTask['description'] ?? '' }}</p>
            </div>
            <span class="badge bg-{{ ($majorTask['is_active'] ?? true) ? 'success' : 'secondary' }} fs-6">
                {{ ($majorTask['is_active'] ?? true) ? 'نشط' : 'غير نشط' }}
            </span>
        </div>
        <div class="row mt-3">
            <div class="col-md-3">
                <small class="text-muted">المدة</small>
                <div><strong>{{ $majorTask['estimated_duration_days'] ?? 0 }} يوم</strong></div>
            </div>
            <div class="col-md-3">
                <small class="text-muted">الأولوية</small>
                <div><strong>{{ $majorTask['priority_id'] ?? 2 }}</strong></div>
            </div>
            <div class="col-md-3">
                <small class="text-muted">مشتركة بين الإدارات</small>
                <div><strong>{{ ($majorTask['is_cross_department'] ?? false) ? 'نعم' : 'لا' }}</strong></div>
            </div>
            <div class="col-md-3">
                <small class="text-muted">المبادرة</small>
                <div><strong>{{ $majorTask['initiative_name'] ?? '' }}</strong></div>
            </div>
        </div>
        @if(!empty($majorTask['departments']))
        <div class="mt-3">
            <small class="text-muted">الإدارات المشاركة:</small>
            <div class="mt-1">
                @foreach($majorTask['departments'] as $dept)
                <span class="badge {{ ($dept['responsibility_type'] ?? '') == 'LEAD' ? 'bg-warning text-dark' : 'bg-light text-dark' }} me-1 mb-1">
                    {{ ($dept['responsibility_type'] ?? '') == 'LEAD' ? '👑' : '🤝' }}
                    {{ $dept['department_name'] ?? '' }}
                </span>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5><i class="fas fa-list-check ml-2"></i>المهام التشغيلية ({{ count($operationalTasks ?? []) }})</h5>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="fas fa-plus"></i> إضافة مهمة يدوياً
            </button>
            <a href="{{ route('operational.generate', $tid) }}" class="btn-gold btn-sm">
                <i class="fas fa-robot"></i> توليد بالذكاء الاصطناعي
            </a>
        </div>
    </div>

    @if(empty($operationalTasks))
    <div class="empty-state">
        <i class="fas fa-robot"></i>
        <h5>لا توجد مهام تشغيلية</h5>
        <p class="text-muted">يمكنك إضافة المهام يدوياً أو استخدام الذكاء الاصطناعي لتوليدها</p>
        <div class="mt-3">
            <button class="btn btn-outline-primary btn-lg me-2" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="fas fa-plus"></i> إضافة يدوياً
            </button>
            <a href="{{ route('operational.generate', $tid) }}" class="btn-gold btn-lg">
                <i class="fas fa-robot"></i> توليد بالذكاء الاصطناعي
            </a>
        </div>
    </div>
    @else
    @foreach($operationalTasks as $task)
    <div class="task-card">
        <div class="d-flex justify-content-between align-items-start">
            <div class="flex-grow-1">
                <h6>{{ $task['task_name'] ?? $task['title'] ?? '' }}</h6>
                <p class="text-muted small mb-2">{{ Str::limit($task['description'] ?? '', 150) }}</p>
                <div class="d-flex gap-3 flex-wrap align-items-center">
                    <span class="badge bg-{{ ($task['status'] ?? 5) == 8 ? 'success' : (($task['status'] ?? 5) == 7 ? 'danger' : 'warning') }}">
                        {{ $task['status_name'] ?? $task['status'] ?? 'معلق' }}
                    </span>
                    <small><i class="far fa-clock ml-1"></i> {{ $task['end_date'] ?? 'غير محدد' }}</small>
                    <small><i class="fas fa-user ml-1"></i> {{ $task['assigned_to_name'] ?? 'غير معين' }}</small>
                </div>
            </div>
            <div class="d-flex gap-1">
                <button class="btn btn-sm btn-outline-primary" onclick="editTask({{ json_encode($task) }})" title="تعديل">
                    <i class="fas fa-edit"></i>
                </button>
                <form action="{{ route('operational.destroy', $task['id']) }}" method="POST" onsubmit="return confirm('متأكد من حذف هذه المهمة؟')">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger" title="حذف">
                        <i class="fas fa-trash"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
    @endforeach
    @endif

    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-plus-circle ml-2"></i>إضافة مهمة تشغيلية</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('operational.store') }}">
                    @csrf
                    <input type="hidden" name="major_task_id" value="{{ $tid }}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">اسم المهمة <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">الوصف</label>
                            <textarea name="description" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">الإدارة</label>
                                <select name="department_id" class="form-control">
                                    <option value="">اختر الإدارة</option>
                                    @foreach($departments as $d)
                                    <option value="{{ $d['department_id'] }}">{{ $d['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">الأولوية</label>
                                <select name="priority_id" class="form-control">
                                    <option value="1">منخفضة</option>
                                    <option value="2" selected>متوسطة</option>
                                    <option value="3">عالية</option>
                                    <option value="4">حرجة</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">الساعات المقدرة</label>
                                <input type="number" name="estimated_hours" class="form-control" value="40" min="1">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">تاريخ البداية</label>
                                <input type="date" name="start_date" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">تاريخ التسليم</label>
                                <input type="date" name="end_date" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-primary">إضافة المهمة</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title"><i class="fas fa-edit ml-2"></i>تعديل مهمة تشغيلية</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">اسم المهمة</label>
                            <input type="text" name="title" id="e_title" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">الوصف</label>
                            <textarea name="description" id="e_desc" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">الحالة</label>
                            <select name="status_id" id="e_status" class="form-control">
                                <option value="5">معلق</option>
                                <option value="6">جاري العمل</option>
                                <option value="8">مكتمل</option>
                                <option value="7">متأخر</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">تاريخ التسليم</label>
                            <input type="date" name="end_date" id="e_end" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-warning">حفظ التعديلات</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function editTask(task) {
    document.getElementById('editForm').action = '/operational/update/' + task.id;
    document.getElementById('e_title').value = task.task_name || task.title || '';
    document.getElementById('e_desc').value = task.description || '';
    document.getElementById('e_status').value = task.status || 5;
    document.getElementById('e_end').value = task.due_date || task.end_date || '';
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>
@endpush

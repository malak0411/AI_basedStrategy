@extends('layouts.app')

@section('title', 'تفاصيل المبادرة')

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
    .task-card:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
    .dept-badge {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 14px;
        font-size: 11px;
        margin: 2px;
        font-weight: 600;
    }
    .dept-lead { background: #d4af37; color: #1a1a2e; }
    .dept-support { background: #e2e8f0; color: #4a5568; }
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
    <a href="{{ route('strategic.initiatives.index') }}" class="btn btn-outline-secondary mb-3">
        <i class="fas fa-arrow-right"></i> العودة للمبادرات
    </a>

    <div class="row">
        <div class="col-lg-8">
            <div class="card-custom mb-4">
                <h4>{{ $initiative['name'] ?? $initiative['title'] ?? '' }}</h4>
                <p class="text-muted">{{ $initiative['description'] ?? '' }}</p>
                <div class="row mt-3">
                    <div class="col-md-3"><small class="text-muted">البرنامج</small><div>{{ $initiative['program_name'] ?? '' }}</div></div>
                    <div class="col-md-3"><small class="text-muted">الأولوية</small><div>{{ $initiative['priority'] ?? '' }}</div></div>
                    <div class="col-md-3"><small class="text-muted">تاريخ البداية</small><div>{{ $initiative['start_date'] ?? '' }}</div></div>
                    <div class="col-md-3"><small class="text-muted">تاريخ النهاية</small><div>{{ $initiative['end_date'] ?? '' }}</div></div>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5><i class="fas fa-tasks ml-2"></i>المهام الرئيسية ({{ count($majorTasks) }})</h5>
                <div>
                    <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addTaskModal">
                        <i class="fas fa-plus"></i> إضافة مهمة يدوياً
                    </button>
                    <a href="{{ route('ai.strategic.index') }}?initiative_id={{ $initiative['id'] }}" class="btn-gold btn-sm mr-2">
                        <i class="fas fa-robot"></i> توليد بالذكاء الاصطناعي
                    </a>
                </div>
            </div>

            @if(empty($majorTasks))
                <div class="empty-state">
                    <i class="fas fa-robot"></i>
                    <h5>لا توجد مهام رئيسية لهذه المبادرة</h5>
                    <p class="text-muted">يمكنك إضافة المهام يدوياً أو استخدام الذكاء الاصطناعي لتوليدها</p>
                    <a href="{{ route('ai.strategic.index') }}?initiative_id={{ $initiative['id'] }}" class="btn-gold btn-lg mt-2">
                        <i class="fas fa-robot"></i> توليد المهام بالذكاء الاصطناعي
                    </a>
                </div>
            @else
                @foreach($majorTasks as $task)
                <div class="task-card" id="task-{{ $task['id'] }}">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <h6>{{ $task['name'] ?? '' }}</h6>
                            <p class="text-muted small mb-2">{{ Str::limit($task['description'] ?? '', 150) }}</p>
                            <div class="d-flex gap-3 flex-wrap">
                                <small><i class="far fa-clock ml-1"></i> {{ $task['estimated_duration_days'] ?? 0 }} يوم</small>
                                <small><i class="fas fa-flag ml-1"></i> أولوية: {{ $task['priority_id'] ?? 2 }}</small>
                                @if($task['is_cross_department'] ?? false)
                                    <small><i class="fas fa-users ml-1"></i> مشتركة بين الإدارات</small>
                                @endif
                            </div>
                            @if(!empty($task['departments']))
                            <div class="mt-2">
                                @foreach($task['departments'] as $dept)
                                <span class="dept-badge {{ ($dept['responsibility_type'] ?? '') == 'LEAD' ? 'dept-lead' : 'dept-support' }}">
                                    {{ ($dept['responsibility_type'] ?? '') == 'LEAD' ? '👑' : '🤝' }}
                                    {{ $dept['department_name'] ?? '' }}
                                </span>
                                @endforeach
                            </div>
                            @endif
                        </div>
                        <div class="d-flex gap-1">
                            <button class="btn btn-sm btn-outline-primary" onclick="editTask({{ json_encode($task) }})" title="تعديل">
                                <i class="fas fa-edit"></i>
                            </button>
                            <form action="/api/strategic/major-tasks/{{ $task['id'] }}" method="POST" class="d-inline" onsubmit="return confirm('متأكد من حذف المهمة؟')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" title="حذف"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </div>
                </div>
                @endforeach
            @endif
        </div>

        <div class="col-lg-4">
            <div class="card-custom mb-4">
                <h6>معلومات سريعة</h6>
                <hr>
                <p><strong>عدد المهام:</strong> {{ count($majorTasks) }}</p>
                <p><strong>الميزانية:</strong> {{ number_format($initiative['budget_estimate'] ?? 0) }}</p>
                <a href="{{ route('ai.strategic.index') }}?initiative_id={{ $initiative['id'] }}" class="btn btn-outline-info btn-sm w-100 mt-2">
                    <i class="fas fa-robot"></i> توليد مهام جديدة بالذكاء الاصطناعي
                </a>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addTaskModal" tabindex="-1">
        <div class="modal-dialog modal-lg"><div class="modal-content">
            <div class="modal-header bg-primary text-white"><h5>إضافة مهمة رئيسية</h5><button class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <form method="POST" action="/api/strategic/initiatives/{{ $initiative['id'] }}/major-tasks">
                @csrf
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">اسم المهمة</label><input type="text" name="name" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">الوصف</label><textarea name="description" class="form-control" rows="2"></textarea></div>
                    <div class="row">
                        <div class="col-md-4"><label class="form-label">المدة (أيام)</label><input type="number" name="estimated_duration_days" class="form-control" value="60"></div>
                        <div class="col-md-4"><label class="form-label">الأولوية</label>
                            <select name="priority_id" class="form-control">
                                @foreach($priorities as $p)
                                    <option value="{{ $p['priority_id'] }}">{{ $p['name_ar'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4"><label class="form-label">مشتركة</label><div class="form-check mt-2"><input type="checkbox" name="is_cross_department" class="form-check-input" value="1"></div></div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label">الإدارات</label>
                        <div id="deptContainer">
                            <div class="row mb-2 dept-row">
                                <div class="col-5"><select name="departments[0][department_id]" class="form-control"><option value="">اختر الإدارة</option>@foreach($departments as $d)<option value="{{ $d['department_id'] }}">{{ $d['name'] }}</option>@endforeach</select></div>
                                <div class="col-3"><select name="departments[0][responsibility_type]" class="form-control"><option value="LEAD">مسؤولة</option><option value="SUPPORT">داعمة</option></select></div>
                                <div class="col-3"><input type="text" name="departments[0][notes]" class="form-control" placeholder="ملاحظة"></div>
                                <div class="col-1"><button type="button" class="btn btn-sm btn-danger" onclick="this.closest('.dept-row').remove()">&times;</button></div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary mt-1" onclick="addDeptRow()">+ إضافة إدارة</button>
                    </div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-primary">إضافة</button></div>
            </form>
        </div></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let deptCount = 1;
function addDeptRow() {
    const container = document.getElementById('deptContainer');
    const row = document.createElement('div');
    row.className = 'row mb-2 dept-row';
    row.innerHTML = `
        <div class="col-5"><select name="departments[${deptCount}][department_id]" class="form-control"><option value="">اختر الإدارة</option>@foreach($departments as $d)<option value="{{ $d['department_id'] }}">{{ $d['name'] }}</option>@endforeach</select></div>
        <div class="col-3"><select name="departments[${deptCount}][responsibility_type]" class="form-control"><option value="LEAD">مسؤولة</option><option value="SUPPORT">داعمة</option></select></div>
        <div class="col-3"><input type="text" name="departments[${deptCount}][notes]" class="form-control" placeholder="ملاحظة"></div>
        <div class="col-1"><button type="button" class="btn btn-sm btn-danger" onclick="this.closest('.dept-row').remove()">&times;</button></div>
    `;
    container.appendChild(row);
    deptCount++;
}
</script>
@endpush

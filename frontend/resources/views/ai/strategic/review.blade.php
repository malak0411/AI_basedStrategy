@extends('layouts.app')

@section('title', 'مراجعة واعتماد المهام الرئيسية')

@push('styles')
<style>
    .task-review-card {
        background: #fff;
        border-radius: 16px;
        padding: 24px;
        margin-bottom: 20px;
        border: 1px solid #e2e8f0;
        transition: all 0.3s ease;
    }
    .task-review-card:hover {
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
    }
    .task-number {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #1a4a8a;
        color: #d4af37;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 18px;
        flex-shrink: 0;
    }
    .dept-row {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 0;
        border-bottom: 1px solid #f0f0f0;
    }
    .dept-row:last-child {
        border-bottom: none;
    }
    .dept-select {
        min-width: 200px;
    }
    .dept-lead {
        background: #d4af37;
        color: #1a1a2e;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 700;
    }
    .dept-support {
        background: #e2e8f0;
        color: #4a5568;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 700;
    }
    .deliverable-tag {
        display: inline-block;
        background: #ebf8ff;
        color: #2b6cb0;
        padding: 4px 12px;
        border-radius: 16px;
        font-size: 12px;
        margin: 2px;
    }
    .prompt-edit-box {
        background: #f8fafc;
        border: 2px dashed #d4af37;
        border-radius: 12px;
        padding: 16px;
        margin-bottom: 20px;
    }
    .editable-field {
        border: 1px solid transparent;
        padding: 6px 10px;
        border-radius: 6px;
        transition: all 0.2s;
        width: 100%;
    }
    .editable-field:hover {
        border-color: #d4af37;
        background: #fffbeb;
    }
    .editable-field:focus {
        border-color: #d4af37;
        background: #fff;
        outline: none;
        box-shadow: 0 0 0 2px rgba(212,175,55,0.2);
    }
    .add-dept-btn {
        font-size: 12px;
        padding: 4px 12px;
    }
    .remove-dept-btn {
        font-size: 12px;
        padding: 2px 8px;
        color: #e53e3e;
        cursor: pointer;
        background: none;
        border: 1px solid #e53e3e;
        border-radius: 50%;
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .remove-dept-btn:hover {
        background: #e53e3e;
        color: #fff;
    }
    .domain-badge {
        font-size: 10px;
        padding: 2px 8px;
        border-radius: 10px;
        background: #edf2f7;
        color: #4a5568;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3>مراجعة واعتماد المهام الرئيسية</h3>
            <p class="text-muted mb-0">
                المبادرة: <strong>{{ $initiative['name'] ?? $initiative['title'] ?? 'غير محدد' }}</strong>
                | رقم المهمة #{{ $jobId }}
            </p>
        </div>
        <span class="badge bg-warning fs-6">بانتظار الاعتماد</span>
    </div>

    <div class="prompt-edit-box">
        <h6>تعديل جميع المهام باستخدام الذكاء الاصطناعي</h6>
        <div class="row">
            <div class="col-md-9">
                <input type="text" id="editInstruction" class="form-control" placeholder="مثال: اجعل المهمة الأولى مدتها 45 يوم وأضف إدارة المالية كداعم">
            </div>
            <div class="col-md-3">
                <button class="btn btn-info w-100" onclick="editWithPrompt()" id="editBtn">
                    تعديل بالذكاء الاصطناعي
                </button>
            </div>
        </div>
    </div>

    <div id="tasksContainer">
        @forelse($tasks as $index => $task)
        <div class="task-review-card" id="task-{{ $index }}" data-index="{{ $index }}">
            <div class="d-flex gap-3">
                <div class="task-number">{{ $index + 1 }}</div>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <input type="text" class="editable-field fw-bold fs-5" data-field="name" data-index="{{ $index }}" value="{{ $task['name'] ?? '' }}" placeholder="اسم المهمة">
                        <div class="d-flex gap-2 align-items-center">
                            <span class="domain-badge">{{ $task['domain'] ?? 'عام' }}</span>
                            <select class="form-select form-select-sm" data-field="priority" data-index="{{ $index }}" style="width:120px;">
                                <option value="High" {{ ($task['priority'] ?? '') == 'High' ? 'selected' : '' }}>عالية</option>
                                <option value="Medium" {{ ($task['priority'] ?? '') == 'Medium' ? 'selected' : '' }}>متوسطة</option>
                                <option value="Low" {{ ($task['priority'] ?? '') == 'Low' ? 'selected' : '' }}>منخفضة</option>
                            </select>
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteTask({{ $index }})" title="حذف المهمة">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small text-muted">الوصف</label>
                        <textarea class="editable-field form-control" data-field="description" data-index="{{ $index }}" rows="2" placeholder="وصف المهمة">{{ $task['description'] ?? '' }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small text-muted">الهدف</label>
                        <input type="text" class="editable-field" data-field="objective" data-index="{{ $index }}" value="{{ $task['objective'] ?? '' }}" placeholder="ما الذي تهدف إليه هذه المهمة">
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label small text-muted">المدة (بالأيام)</label>
                            <input type="number" class="editable-field form-control" data-field="estimated_duration_days" data-index="{{ $index }}" value="{{ $task['estimated_duration_days'] ?? 60 }}" min="10" max="180">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-muted">مهمة مشتركة بين الإدارات</label>
                            <div class="form-check mt-2">
                                <input type="checkbox" class="form-check-input" data-field="is_cross_department" data-index="{{ $index }}" {{ ($task['is_cross_department'] ?? false) ? 'checked' : '' }}>
                                <label class="form-check-label">نعم</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label small text-muted mb-0">الإدارات المسؤولة</label>
                            <button class="btn btn-sm btn-outline-primary add-dept-btn" onclick="addDepartment({{ $index }})">
                                <i class="fas fa-plus"></i> إضافة إدارة
                            </button>
                        </div>
                        <div class="departments-container" id="depts-{{ $index }}">
                            @php $deptList = $task['departments'] ?? []; @endphp
                            @forelse($deptList as $di => $dept)
                            <div class="dept-row" data-dept-index="{{ $di }}">
                                <select class="form-select form-select-sm dept-select" data-field="department_id" data-index="{{ $index }}" data-dept-index="{{ $di }}">
                                    <option value="">اختر الإدارة</option>
                                    @foreach($departments as $d)
                                        <option value="{{ $d['department_id'] }}" {{ ($dept['department_id'] ?? '') == $d['department_id'] ? 'selected' : '' }}>
                                            {{ $d['name'] }}
                                        </option>
                                    @endforeach
                                </select>
                                <select class="form-select form-select-sm" data-field="responsibility_type" data-index="{{ $index }}" data-dept-index="{{ $di }}" style="width:110px;">
                                    <option value="LEAD" {{ ($dept['responsibility_type'] ?? '') == 'LEAD' ? 'selected' : '' }}>مسؤولة</option>
                                    <option value="SUPPORT" {{ ($dept['responsibility_type'] ?? '') == 'SUPPORT' ? 'selected' : '' }}>داعمة</option>
                                </select>
                                <input type="text" class="form-control form-control-sm" data-field="notes" data-index="{{ $index }}" data-dept-index="{{ $di }}" value="{{ $dept['notes'] ?? '' }}" placeholder="وصف دور الإدارة">
                                <button class="remove-dept-btn" onclick="removeDepartment(this, {{ $index }})" title="حذف الإدارة">&times;</button>
                            </div>
                            @empty
                            <div class="dept-row" data-dept-index="0">
                                <select class="form-select form-select-sm dept-select" data-field="department_id" data-index="{{ $index }}" data-dept-index="0">
                                    <option value="">اختر الإدارة</option>
                                    @foreach($departments as $d)
                                        <option value="{{ $d['department_id'] }}">{{ $d['name'] }}</option>
                                    @endforeach
                                </select>
                                <select class="form-select form-select-sm" data-field="responsibility_type" data-index="{{ $index }}" data-dept-index="0" style="width:110px;">
                                    <option value="LEAD">مسؤولة</option>
                                    <option value="SUPPORT">داعمة</option>
                                </select>
                                <input type="text" class="form-control form-control-sm" data-field="notes" data-index="{{ $index }}" data-dept-index="0" value="" placeholder="وصف دور الإدارة">
                                <button class="remove-dept-btn" onclick="removeDepartment(this, {{ $index }})" title="حذف الإدارة">&times;</button>
                            </div>
                            @endforelse
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small text-muted">المخرجات</label>
                        <div id="deliverables-{{ $index }}">
                            @php $deliverables = $task['deliverables'] ?? []; @endphp
                            @forelse($deliverables as $di => $dv)
                            <div class="input-group input-group-sm mb-1" data-del-index="{{ $di }}">
                                <input type="text" class="form-control" data-field="deliverables" data-index="{{ $index }}" data-del-index="{{ $di }}" value="{{ $dv }}" placeholder="مخرج">
                                <button class="btn btn-outline-danger" type="button" onclick="this.parentElement.remove()">&times;</button>
                            </div>
                            @empty
                            <div class="input-group input-group-sm mb-1" data-del-index="0">
                                <input type="text" class="form-control" data-field="deliverables" data-index="{{ $index }}" data-del-index="0" placeholder="مخرج">
                                <button class="btn btn-outline-danger" type="button" onclick="this.parentElement.remove()">&times;</button>
                            </div>
                            @endforelse
                        </div>
                        <button class="btn btn-sm btn-outline-secondary mt-1" onclick="addDeliverable({{ $index }})">
                            <i class="fas fa-plus"></i> إضافة مخرج
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="card-custom text-center py-5">
            <h5>لا توجد مهام مولدة</h5>
            <p class="text-muted">لم يتم توليد أي مهام لهذه المبادرة</p>
            <a href="{{ route('ai.strategic.index') }}" class="btn btn-primary">العودة لتوليد المهام</a>
        </div>
        @endforelse
    </div>

    @if(!empty($tasks))
    <div class="text-center mt-4">
        <form method="POST" action="{{ route('ai.strategic.approve') }}" id="approveForm">
            @csrf
            <input type="hidden" name="job_id" value="{{ $jobId }}">
            <input type="hidden" name="initiative_id" value="{{ $initiativeId }}">
            <input type="hidden" name="tasks_data" id="tasksDataInput">
            <button type="submit" class="btn-gold btn-lg" onclick="prepareSubmit(event)">
                اعتماد الخطة وحفظ جميع المهام
            </button>
        </form>
        <a href="{{ route('ai.strategic.index') }}" class="btn btn-outline-secondary btn-lg mr-3">إلغاء</a>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    const allDepartments = @json($departments);

    function addDepartment(taskIndex) {
        const container = document.getElementById('depts-' + taskIndex);
        const deptCount = container.querySelectorAll('.dept-row').length;
        const row = document.createElement('div');
        row.className = 'dept-row';
        row.setAttribute('data-dept-index', deptCount);
        row.innerHTML = `
            <select class="form-select form-select-sm dept-select" data-field="department_id" data-index="${taskIndex}" data-dept-index="${deptCount}">
                <option value="">اختر الإدارة</option>
                ${allDepartments.map(d => `<option value="${d.department_id}">${d.name}</option>`).join('')}
            </select>
            <select class="form-select form-select-sm" data-field="responsibility_type" data-index="${taskIndex}" data-dept-index="${deptCount}" style="width:110px;">
                <option value="LEAD">مسؤولة</option>
                <option value="SUPPORT">داعمة</option>
            </select>
            <input type="text" class="form-control form-control-sm" data-field="notes" data-index="${taskIndex}" data-dept-index="${deptCount}" value="" placeholder="وصف دور الإدارة">
            <button class="remove-dept-btn" onclick="removeDepartment(this, ${taskIndex})" title="حذف الإدارة">&times;</button>
        `;
        container.appendChild(row);
    }

    function removeDepartment(btn, taskIndex) {
        const container = document.getElementById('depts-' + taskIndex);
        if (container.querySelectorAll('.dept-row').length > 1) {
            btn.closest('.dept-row').remove();
        }
    }

    function addDeliverable(taskIndex) {
        const container = document.getElementById('deliverables-' + taskIndex);
        const delCount = container.querySelectorAll('.input-group').length;
        const div = document.createElement('div');
        div.className = 'input-group input-group-sm mb-1';
        div.setAttribute('data-del-index', delCount);
        div.innerHTML = `
            <input type="text" class="form-control" data-field="deliverables" data-index="${taskIndex}" data-del-index="${delCount}" placeholder="مخرج">
            <button class="btn btn-outline-danger" type="button" onclick="this.parentElement.remove()">&times;</button>
        `;
        container.appendChild(div);
    }

    function deleteTask(taskIndex) {
        if (!confirm('هل أنت متأكد من حذف هذه المهمة؟')) return;
        document.getElementById('task-' + taskIndex).remove();
    }

    function prepareSubmit(event) {
        const tasks = [];
        document.querySelectorAll('.task-review-card').forEach(card => {
            const index = card.getAttribute('data-index');
            const task = {
                name: '',
                description: '',
                objective: '',
                priority: 'Medium',
                estimated_duration_days: 60,
                is_cross_department: false,
                departments: [],
                deliverables: []
            };

            card.querySelectorAll('[data-field]').forEach(el => {
                const field = el.getAttribute('data-field');
                const elIndex = el.getAttribute('data-index');
                const deptIndex = el.getAttribute('data-dept-index');
                const delIndex = el.getAttribute('data-del-index');

                if (elIndex !== index) return;

                if (field === 'department_id' || field === 'responsibility_type' || field === 'notes') {
                    if (deptIndex !== null) {
                        if (!task.departments[deptIndex]) task.departments[deptIndex] = {};
                        task.departments[deptIndex][field] = el.type === 'select-one' ? el.value : el.value;
                    }
                } else if (field === 'deliverables') {
                    if (delIndex !== null) {
                        task.deliverables[delIndex] = el.value;
                    }
                } else if (field === 'is_cross_department') {
                    task[field] = el.checked;
                } else if (field === 'estimated_duration_days') {
                    task[field] = parseInt(el.value) || 60;
                } else {
                    task[field] = el.value;
                }
            });

            task.departments = task.departments.filter(d => d && d.department_id);
            task.deliverables = task.deliverables.filter(d => d && d.trim());
            tasks.push(task);
        });

        document.getElementById('tasksDataInput').value = JSON.stringify(tasks);
    }

    async function editWithPrompt() {
    const instruction = document.getElementById('editInstruction').value;
    if (!instruction) return alert('أدخل تعليمات التعديل');

    const btn = document.getElementById('editBtn');
    btn.disabled = true;
    btn.innerHTML = 'جاري التعديل...';

    try {
        const response = await fetch('{{ route("ai.strategic.edit-plan") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ job_id: '{{ $jobId }}', instruction })
        });
        const data = await response.json();
        
        if (data.success) {
            btn.innerHTML = 'جاري التعديل... انتظر';
            checkEditStatus();
        } else {
            alert('فشل بدء التعديل: ' + (data.detail || 'خطأ'));
            btn.disabled = false;
            btn.innerHTML = 'تعديل بالذكاء الاصطناعي';
        }
    } catch (e) {
        alert('خطأ في الاتصال');
        btn.disabled = false;
        btn.innerHTML = 'تعديل بالذكاء الاصطناعي';
    }
}

function checkEditStatus() {
    fetch('/api/ai/jobs/{{ $jobId }}')
        .then(r => r.json())
        .then(data => {
            if (data.success && data.data) {
                const status = data.data.status;
                if (status === 'review') {
                    window.location.reload();
                } else if (status === 'editing') {
                    setTimeout(checkEditStatus, 3000);
                } else if (status === 'failed') {
                    alert('فشل التعديل');
                    window.location.reload();
                }
            }
        })
        .catch(() => {
            setTimeout(checkEditStatus, 3000);
        });
}

</script>
@endpush

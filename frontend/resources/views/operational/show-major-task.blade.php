@extends('layouts.app')

@section('title', 'تفاصيل المهمة الرئيسية')

@push('styles')
<style>
    .task-card { background: #fff; border-radius: 12px; padding: 16px; margin-bottom: 12px; border: 1px solid #e2e8f0; transition: all 0.2s; }
    .task-card:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
    .empty-state { text-align: center; padding: 60px 20px; }
    .empty-state i { font-size: 64px; color: #cbd5e0; margin-bottom: 16px; }
    .dept-badge { display: inline-block; padding: 3px 10px; border-radius: 14px; font-size: 11px; margin: 2px; font-weight: 600; }
    .dept-lead { background: #d4af37; color: #1a1a2e; }
    .dept-support { background: #e2e8f0; color: #4a5568; }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <a href="{{ route('operational.major-tasks') }}" class="btn btn-outline-secondary mb-3">
        <i class="fas fa-arrow-right"></i> العودة للمهام الرئيسية
    </a>

    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif

    @php
        $tid = $majorTask['id'] ?? 0;
        $taskEndDate = $majorTask['end_date'] ?? date('Y-m-d', strtotime('+30 days'));
        $isActive = $majorTask['is_active'] ?? true;
    @endphp

    <div class="card-custom mb-4">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <h4>{{ $majorTask['name'] ?? '' }}</h4>
                <p class="text-muted">{{ $majorTask['description'] ?? '' }}</p>
            </div>
            <span class="badge bg-{{ $isActive ? 'success' : 'secondary' }} fs-6">
                {{ $isActive ? 'نشط' : 'غير نشط' }}
            </span>
        </div>
        <div class="row mt-3">
            <div class="col-md-3">
                <small class="text-muted">المدة</small>
                <div><strong>{{ $majorTask['estimated_duration_days'] ?? 0 }} يوم</strong></div>
            </div>
            <div class="col-md-3">
                <small class="text-muted">تاريخ النهاية</small>
                <div><strong>{{ $taskEndDate }}</strong></div>
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
                <span class="dept-badge {{ ($dept['responsibility_type'] ?? '') == 'LEAD' ? 'dept-lead' : 'dept-support' }}">
                    {{ ($dept['responsibility_type'] ?? '') == 'LEAD' ?  'رئيسية' : 'مساندة' }} -
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
                    <span class="badge bg-{{ ($task['status'] ?? 16) == 8 ? 'success' : (($task['status'] ?? 16) == 7 ? 'danger' : 'warning') }}">
                        {{ $task['status_name'] ?? 'معلق مؤقتاً' }}
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
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger" title="حذف"><i class="fas fa-trash"></i></button>
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
                <form method="POST" action="{{ route('operational.store') }}" id="addForm" onsubmit="return validateAddForm()">
                    @csrf
                    <input type="hidden" name="major_task_id" value="{{ $tid }}">
                    <input type="hidden" name="department_id" value="{{ $departmentId }}">
                    <input type="hidden" id="taskEndDate" value="{{ $taskEndDate }}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">اسم المهمة <span class="text-danger">*</span></label>
                            <input type="text" name="title" id="addTitle" class="form-control" required maxlength="255">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">الوصف</label>
                            <textarea name="description" id="addDesc" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">الأولوية</label>
                                <select name="priority_id" class="form-control">
                                    @foreach($priorities as $p)
                                    <option value="{{ $p['priority_id'] }}" {{ ($p['level'] ?? 0) == 2 ? 'selected' : '' }}>{{ $p['name_ar'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">الساعات المقدرة</label>
                                <input type="number" name="estimated_hours" id="estimatedHours" class="form-control" value="40" min="1" max="500">
                                <small class="text-danger d-none" id="hoursError">الساعات غير منطقية للفترة المختارة</small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">الحالة</label>
                                <input type="text" class="form-control" value="معلق مؤقتاً" disabled>
                                <small class="text-muted">سيتم تعيينها كمعلق مؤقتاً تلقائياً</small>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">تاريخ البداية</label>
                                <input type="date" name="start_date" id="startDate" class="form-control" onchange="validateDates()">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">تاريخ التسليم</label>
                                <input type="date" name="end_date" id="endDate" class="form-control" onchange="validateDates()">
                                <small class="text-danger d-none" id="dateError">تاريخ التسليم يجب أن يكون بعد البداية وقبل نهاية المهمة الرئيسية</small>
                            </div>
                        </div>
                        <hr>
                        <div class="mb-3">
                            <label class="form-label fw-bold">الموظفين المسؤولين عن المهمة</label>
                            <p class="text-muted small mb-2">اختر موظفاً واحداً على الأقل. إذا اخترت أكثر من موظف تصبح المهمة مشتركة تلقائياً.</p>
                            <div id="employeesList">
                                <p class="text-muted small">جاري تحميل الموظفين...</p>
                            </div>
                            <small class="text-muted mt-2 d-block" id="employeesCount">تم اختيار 0 موظفين</small>
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
                    @csrf @method('PUT')
                    <div class="modal-body">
                        <div class="mb-3"><label class="form-label">اسم المهمة</label><input type="text" name="title" id="e_title" class="form-control" required></div>
                        <div class="mb-3"><label class="form-label">الوصف</label><textarea name="description" id="e_desc" class="form-control" rows="2"></textarea></div>
                        <div class="mb-3"><label class="form-label">الحالة</label><select name="status_id" id="e_status" class="form-control"><option value="16">معلق مؤقتاً</option><option value="5">معلق</option><option value="6">جاري العمل</option><option value="8">مكتمل</option><option value="7">متأخر</option></select></div>
                        <div class="mb-3"><label class="form-label">تاريخ التسليم</label><input type="date" name="end_date" id="e_end" class="form-control"></div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button><button type="submit" class="btn btn-warning">حفظ التعديلات</button></div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const roleTypes = @json($roleTypes ?? []);
let deptId = {{ $departmentId ?? 0 }};

document.addEventListener('DOMContentLoaded', function() {
    loadEmployees();
});

function loadEmployees() {
    const container = document.getElementById('employeesList');
    
    fetch('/api/auth/me')
        .then(r => r.json())
        .then(userData => {
            if (userData.data && userData.data.department_id) {
                deptId = userData.data.department_id;
                return fetch('/api/employees/department/' + deptId);
            }
            throw new Error('No department_id');
        })
        .then(r => r.json())
        .then(data => {
            if (data.data && data.data.length > 0) {
                let html = '';
                data.data.forEach(emp => {
                    html += `
                    <div class="card mb-2 p-2">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="form-check">
                                <input type="checkbox" name="assigned_employees[]" value="${emp.employee_id}" class="form-check-input emp-checkbox" id="emp_${emp.employee_id}" onchange="toggleRole('${emp.employee_id}'); updateEmployeeCount();">
                                <label class="form-check-label" for="emp_${emp.employee_id}">
                                    <strong>${emp.full_name}</strong>
                                    <br><small class="text-muted">${emp.job_title || ''}</small>
                                </label>
                            </div>
                            <div class="d-none" id="role_${emp.employee_id}">
                                <label class="small">الدور:</label>
                                <select name="employee_roles[${emp.employee_id}]" class="form-select form-select-sm" style="width:160px;">
                                    ${roleTypes.map(r => `<option value="${r.role_type_id}">${r.name_ar}</option>`).join('')}
                                </select>
                            </div>
                        </div>
                    </div>`;
                });
                container.innerHTML = html;
            } else {
                container.innerHTML = '<p class="text-danger small">لا يوجد موظفين في إدارتك</p>';
            }
        })
        .catch(err => {
            container.innerHTML = '<p class="text-danger small">خطأ في تحميل الموظفين: ' + err.message + '</p>';
        });
}

function toggleRole(empId) {
    const roleDiv = document.getElementById('role_' + empId);
    const checkbox = document.getElementById('emp_' + empId);
    if (roleDiv) roleDiv.classList.toggle('d-none', !checkbox.checked);
}

function updateEmployeeCount() {
    const count = document.querySelectorAll('.emp-checkbox:checked').length;
    const msg = document.getElementById('employeesCount');
    msg.textContent = 'تم اختيار ' + count + ' موظفين';
    if (count > 1) msg.textContent += ' (مهمة مشتركة)';
}

function validateAddForm() {
    const title = document.getElementById('addTitle').value.trim();
    if (!title) { alert('اسم المهمة مطلوب'); return false; }
    const checkedCount = document.querySelectorAll('.emp-checkbox:checked').length;
    if (checkedCount === 0) { alert('يجب اختيار موظف واحد على الأقل'); return false; }
    return validateDates() && validateHours();
}


function loadEmployees() {
    const container = document.getElementById('employeesList');
    container.innerHTML = '<p class="text-muted small">جاري تحميل الموظفين...</p>';
    fetch('/api/employees/department/' + deptId)
        .then(r => r.json())
        .then(data => {
            if (data.data && data.data.length > 0) {
                let html = '';
                data.data.forEach(emp => {
                    html += `
                    <div class="card mb-2 p-2">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="form-check">
                                <input type="checkbox" name="assigned_employees[]" value="${emp.employee_id}" class="form-check-input emp-checkbox" id="emp_${emp.employee_id}" onchange="toggleRole('${emp.employee_id}'); updateEmployeeCount();">
                                <label class="form-check-label" for="emp_${emp.employee_id}">
                                    <strong>${emp.full_name}</strong>
                                    <br><small class="text-muted">${emp.job_title || ''}</small>
                                </label>
                            </div>
                            <div class="d-none" id="role_${emp.employee_id}">
                                <label class="small">الدور:</label>
                                <select name="employee_roles[${emp.employee_id}]" class="form-select form-select-sm" style="width:160px;">
                                    ${roleTypes.map(r => `<option value="${r.role_type_id}">${r.name_ar}</option>`).join('')}
                                </select>
                            </div>
                        </div>
                    </div>`;
                });
                container.innerHTML = html;
            } else {
                container.innerHTML = '<p class="text-danger small">لا يوجد موظفين في إدارتك</p>';
            }
        })
        .catch(err => { container.innerHTML = '<p class="text-danger small">خطأ في تحميل الموظفين</p>'; });
}

function toggleRole(empId) {
    const roleDiv = document.getElementById('role_' + empId);
    const checkbox = document.getElementById('emp_' + empId);
    if (roleDiv) roleDiv.classList.toggle('d-none', !checkbox.checked);
}

function updateEmployeeCount() {
    const count = document.querySelectorAll('.emp-checkbox:checked').length;
    const msg = document.getElementById('employeesCount');
    msg.textContent = 'تم اختيار ' + count + ' موظفين';
    if (count > 1) msg.textContent += ' (مهمة مشتركة)';
}

function validateDates() {
    const start = document.getElementById('startDate').value;
    const end = document.getElementById('endDate').value;
    const taskEnd = document.getElementById('taskEndDate').value;
    const dateError = document.getElementById('dateError');
    dateError.classList.add('d-none');
    if (start && end) {
        if (start >= end) { dateError.textContent = 'تاريخ التسليم يجب أن يكون بعد تاريخ البداية'; dateError.classList.remove('d-none'); return false; }
        if (end > taskEnd) { dateError.textContent = 'تاريخ التسليم يجب أن يكون قبل ' + taskEnd; dateError.classList.remove('d-none'); return false; }
    }
    validateHours();
    return true;
}

function validateHours() {
    const start = document.getElementById('startDate').value;
    const end = document.getElementById('endDate').value;
    const hours = parseInt(document.getElementById('estimatedHours').value) || 0;
    const hoursError = document.getElementById('hoursError');
    hoursError.classList.add('d-none');
    if (start && end && hours > 0) {
        const days = Math.ceil((new Date(end) - new Date(start)) / (1000 * 60 * 60 * 24)) + 1;
        if (hours > days * 8) { hoursError.textContent = 'الساعات غير منطقية (الحد الأقصى ' + (days * 8) + ' ساعة)'; hoursError.classList.remove('d-none'); return false; }
    }
    return true;
}

function validateAddForm() {
    const title = document.getElementById('addTitle').value.trim();
    if (!title) { alert('اسم المهمة مطلوب'); return false; }
    const checkedCount = document.querySelectorAll('.emp-checkbox:checked').length;
    if (checkedCount === 0) { alert('يجب اختيار موظف واحد على الأقل'); return false; }
    return validateDates() && validateHours();
}

document.getElementById('estimatedHours').addEventListener('change', validateHours);
document.getElementById('startDate').addEventListener('change', validateDates);
document.getElementById('endDate').addEventListener('change', validateDates);
</script>
@endpush

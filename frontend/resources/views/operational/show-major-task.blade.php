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
    .employee-checkbox-item { cursor: pointer; transition: all 0.2s ease; border: 1px solid transparent; }
    .employee-checkbox-item:hover { background: #e9ecef !important; border-color: #dee2e6; }
    .employee-checkbox-item.bg-primary.bg-opacity-10 { border-color: #0d6efd; }
    .employee-checkbox-item .employee-name { font-size: 14px; font-weight: 500; }
    .employee-checkbox-item .employee-title { font-size: 11px; }
    .modal-dialog-scrollable .modal-body { max-height: calc(100vh - 200px); }
    #durationInfo .alert { background: #f0f4ff; border-color: #cfe2ff; }
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
        $tid = is_array($majorTask) ? ($majorTask['id'] ?? 0) : ($majorTask->id ?? 0);
        $taskEndDate = is_array($majorTask) ? ($majorTask['end_date'] ?? date('Y-m-d', strtotime('+30 days'))) : ($majorTask->end_date ?? date('Y-m-d', strtotime('+30 days')));
        $isActive = is_array($majorTask) ? ($majorTask['is_active'] ?? true) : ($majorTask->is_active ?? true);
        $taskTitle = is_array($majorTask) ? ($majorTask['name'] ?? $majorTask['title'] ?? 'غير محدد') : ($majorTask->name ?? $majorTask->title ?? 'غير محدد');
        $taskDescription = is_array($majorTask) ? ($majorTask['description'] ?? '') : ($majorTask->description ?? '');
        $estimatedDays = is_array($majorTask) ? ($majorTask['estimated_duration_days'] ?? 0) : ($majorTask->estimated_duration_days ?? 0);
        $isCrossDept = is_array($majorTask) ? ($majorTask['is_cross_department'] ?? false) : ($majorTask->is_cross_department ?? false);
        $initiativeName = is_array($majorTask) ? ($majorTask['initiative_name'] ?? 'غير محدد') : ($majorTask->initiative_name ?? 'غير محدد');
        $departments = is_array($majorTask) ? ($majorTask['departments'] ?? []) : ($majorTask->departments ?? []);
    @endphp

    <div class="card-custom mb-4">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <h4>{{ $taskTitle }}</h4>
                <p class="text-muted">{{ $taskDescription }}</p>
            </div>
            <span class="badge bg-{{ $isActive ? 'success' : 'secondary' }} fs-6">
                {{ $isActive ? 'نشط' : 'غير نشط' }}
            </span>
        </div>
        <div class="row mt-3">
            <div class="col-md-3">
                <small class="text-muted">المدة</small>
                <div><strong>{{ $estimatedDays }} يوم</strong></div>
            </div>
            <div class="col-md-3">
                <small class="text-muted">تاريخ النهاية</small>
                <div><strong>{{ $taskEndDate }}</strong></div>
            </div>
            <div class="col-md-3">
                <small class="text-muted">مشتركة بين الإدارات</small>
                <div><strong>{{ $isCrossDept ? 'نعم' : 'لا' }}</strong></div>
            </div>
            <div class="col-md-3">
                <small class="text-muted">المبادرة</small>
                <div><strong>{{ $initiativeName }}</strong></div>
            </div>
        </div>
        @if(!empty($departments))
        <div class="mt-3">
            <small class="text-muted">الإدارات المشاركة:</small>
            <div class="mt-1">
                @foreach($departments as $dept)
                <span class="dept-badge {{ ($dept['responsibility_type'] ?? '') == 'LEAD' ? 'dept-lead' : 'dept-support' }}">
                    {{ ($dept['responsibility_type'] ?? '') == 'LEAD' ? 'رئيسية' : 'مساندة' }} -
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
            <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addOperationalModal">
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
    </div>
    @else
    @foreach($operationalTasks as $task)
    <div class="task-card">
        <div class="d-flex justify-content-between align-items-start">
            <div class="flex-grow-1">
                <h6>{{ $task['task_name'] ?? $task['title'] ?? '' }}</h6>
                <p class="text-muted small mb-2">{{ Str::limit($task['description'] ?? '', 150) }}</p>
                <div class="d-flex gap-3 flex-wrap align-items-center">
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

    <div class="modal fade" id="addOperationalModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-gradient-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle ml-2"></i>
                        إضافة مهمة تشغيلية جديدة
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
               
                <form id="addOperationalForm" method="POST" action="{{ route('operational.store') }}">
                    @csrf
                   
                    @php
                        $majorTaskId = is_array($majorTask) ? ($majorTask['id'] ?? 0) : ($majorTask->id ?? 0);
                        $departmentId = is_array($majorTask) ? ($majorTask['department_id'] ?? 0) : ($majorTask->department_id ?? 0);
                        $initiativeStart = is_array($initiative ?? null) ? ($initiative['start_date'] ?? '') : ($initiative->start_date ?? '');
                        $initiativeEnd = is_array($initiative ?? null) ? ($initiative['end_date'] ?? '') : ($initiative->end_date ?? '');
                        $expectedDays = is_array($majorTask) ? ($majorTask['expected_days'] ?? 30) : ($majorTask->expected_days ?? 30);
                        $majorTaskTitle = is_array($majorTask) ? ($majorTask['title'] ?? 'غير محدد') : ($majorTask->title ?? 'غير محدد');
                        $departmentName = is_array($majorTask) ? ($majorTask['department']['name_ar'] ?? 'غير محدد') : ($majorTask->department->name_ar ?? 'غير محدد');
                        $majorTaskStartDate = is_array($majorTask) ? ($majorTask['start_date'] ?? '') : ($majorTask->start_date ?? '');
                        $majorTaskEndDate = is_array($majorTask) ? ($majorTask['end_date'] ?? '') : ($majorTask->end_date ?? '');
                    @endphp
                   
                    <input type="hidden" name="major_task_id" value="{{ $majorTaskId }}">
                    <input type="hidden" name="department_id" value="{{ $departmentId }}">
                    <input type="hidden" name="created_by" value="{{ auth()->user()->employee_id ?? 0 }}">
                    <input type="hidden" name="status_id" value="16">
                   
                    <input type="hidden" id="initiativeStartDate" value="{{ $initiativeStart }}">
                    <input type="hidden" id="initiativeEndDate" value="{{ $initiativeEnd }}">
                    <input type="hidden" id="majorTaskExpectedDays" value="{{ $expectedDays }}">
                   
                    <div class="modal-body">
                        <div class="alert alert-info alert-dismissible fade show">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-info-circle fa-2x ml-3"></i>
                                <div>
                                    <strong>المهمة الرئيسية:</strong> {{ $majorTaskTitle }}
                                    <br>
                                    <small>
                                        <i class="fas fa-building"></i> {{ $departmentName }} |
                                        <i class="fas fa-calendar-alt"></i>
                                        {{ $majorTaskStartDate ? \Carbon\Carbon::parse($majorTaskStartDate)->format('Y-m-d') : 'غير محدد' }}
                                        →
                                        {{ $majorTaskEndDate ? \Carbon\Carbon::parse($majorTaskEndDate)->format('Y-m-d') : 'غير محدد' }}
                                        @if($expectedDays)
                                            | <i class="fas fa-clock"></i> المدة المتوقعة: {{ $expectedDays }} يوم
                                        @endif
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="fw-bold">
                                عنوان المهمة <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="title" id="taskTitle" class="form-control form-control-lg"
                                   placeholder="أدخل عنوان المهمة التشغيلية..."
                                   maxlength="255" required>
                            <div class="error-feedback" id="titleFeedback">الرجاء إدخال عنوان المهمة (3 أحرف على الأقل)</div>
                            <small class="text-muted" id="titleCount">0/255 حرف</small>
                        </div>

                        <div class="form-group">
                            <label class="fw-bold">
                                الوصف <span class="text-danger">*</span>
                            </label>
                            <textarea name="description" id="taskDescription" class="form-control" rows="3"
                                      placeholder="أدخل وصفاً تفصيلياً للمهمة..."
                                      maxlength="1000" required></textarea>
                            <div class="error-feedback" id="descFeedback">الرجاء إدخال وصف للمهمة</div>
                            <small class="text-muted" id="descCount">0/1000 حرف</small>
                        </div>

                        <div class="row">
                            <div class="col-md-4 form-group">
                                <label class="fw-bold">
                                    الأولوية <span class="text-danger">*</span>
                                </label>
                                <select name="priority_id" id="taskPriority" class="form-select" required>
                                    <option value="">-- اختر الأولوية --</option>
                                    @foreach($priorities as $p)
                                        <option value="{{ $p['priority_id'] }}"
                                            {{ ($p['level'] ?? 0) == 2 ? 'selected' : '' }}>
                                            {{ $p['name_ar'] }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="error-feedback" id="priorityFeedback">الرجاء اختيار الأولوية</div>
                            </div>

                            <div class="col-md-4 form-group">
                                <label class="fw-bold">
                                    الساعات المقدرة <span class="text-danger">*</span>
                                </label>
                                <input type="number" name="estimated_hours" id="estimatedHours"
                                       class="form-control" value="40"
                                       min="1" max="720" required>
                                <small class="text-muted">(1-720 ساعة)</small>
                                <div class="error-feedback" id="hoursFeedback"></div>
                            </div>

                            <div class="col-md-4 form-group">
                                <label class="fw-bold">الحالة</label>
                                <input type="text" class="form-control bg-light text-muted" value="معلق مؤقتاً" disabled>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="fw-bold">
                                    تاريخ البداية <span class="text-danger">*</span>
                                </label>
                                <input type="date" name="start_date" id="startDate"
                                       class="form-control" required>
                                <small class="text-muted" id="startDateHelp">
                                    <i class="fas fa-info-circle"></i>
                                    الحد الأدنى: <span id="minStartDate">{{ $initiativeStart ?? 'غير محدد' }}</span>
                                </small>
                                <div class="error-feedback" id="startDateFeedback"></div>
                            </div>

                            <div class="col-md-6 form-group">
                                <label class="fw-bold">
                                    تاريخ التسليم <span class="text-danger">*</span>
                                </label>
                                <input type="date" name="end_date" id="endDate"
                                       class="form-control" required>
                                <small class="text-muted" id="endDateHelp">
                                    <i class="fas fa-info-circle"></i>
                                    الحد الأعلى: <span id="maxEndDate">{{ $initiativeEnd ?? 'غير محدد' }}</span>
                                </small>
                                <div class="error-feedback" id="endDateFeedback"></div>
                            </div>
                        </div>

                        <div class="row mb-3" id="durationInfo" style="display:none;">
                            <div class="col-12">
                                <div class="alert alert-secondary">
                                    <div class="row text-center">
                                        <div class="col-md-4">
                                            <h6>المدة المتوقعة</h6>
                                            <span id="expectedDaysDisplay" class="badge bg-info">0 يوم</span>
                                        </div>
                                        <div class="col-md-4">
                                            <h6>المدة المختارة</h6>
                                            <span id="selectedDaysDisplay" class="badge bg-primary">0 يوم</span>
                                        </div>
                                        <div class="col-md-4">
                                            <h6>المتبقي</h6>
                                            <span id="remainingDaysDisplay" class="badge bg-success">0 يوم</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-2">
                            <div class="col-md-6 text-md-end">
                                <small class="text-muted">
                                    <i class="fas fa-clock"></i>
                                    تاريخ الإنشاء: <strong>{{ now()->format('Y-m-d H:i') }}</strong>
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> إلغاء
                        </button>
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="fas fa-save"></i> إضافة المهمة
                        </button>
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
                        <div class="form-group"><label class="fw-bold">اسم المهمة</label><input type="text" name="title" id="e_title" class="form-control" required></div>
                        <div class="form-group"><label class="fw-bold">الوصف</label><textarea name="description" id="e_desc" class="form-control" rows="2" required></textarea></div>
                        <div class="form-group"><label class="fw-bold">الحالة</label><select name="status_id" id="e_status" class="form-control"><option value="16">معلق مؤقتاً</option><option value="5">معلق</option><option value="6">جاري العمل</option><option value="8">مكتمل</option><option value="7">متأخر</option></select></div>
                        <div class="form-group"><label class="fw-bold">تاريخ التسليم</label><input type="date" name="end_date" id="e_end" class="form-control"></div>
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
$(document).ready(function() {
    initializeDates();
   
    $('#taskTitle').on('input', function() {
        const count = $(this).val().length;
        $('#titleCount').text(count + '/255');
        validateTitle();
    });
   
    $('#taskDescription').on('input', function() {
        const count = $(this).val().length;
        $('#descCount').text(count + '/1000');
        validateDescription();
    });
   
    $('#taskPriority').on('change', function() {
        validatePriority();
    });
   
    $('#estimatedHours').on('input', function() {
        validateHours();
        validateDates();
    });
   
    $('#startDate, #endDate').on('change', function() {
        validateDates();
    });
});

function initializeDates() {
    const initiativeStart = $('#initiativeStartDate').val();
    const initiativeEnd = $('#initiativeEndDate').val();
    const today = new Date().toISOString().split('T')[0];
    const minStart = initiativeStart || today;
   
    $('#startDate').attr('min', minStart);
    $('#startDate').val(minStart);
    $('#minStartDate').text(minStart || 'غير محدد');
   
    if (initiativeEnd) {
        $('#endDate').attr('max', initiativeEnd);
        $('#maxEndDate').text(initiativeEnd);
    }
   
    const startDate = new Date(minStart);
    startDate.setDate(startDate.getDate() + 7);
    $('#endDate').val(startDate.toISOString().split('T')[0]);
   
    setTimeout(function() {
        validateDates();
    }, 100);
}

function validateTitle() {
    const value = $('#taskTitle').val().trim();
    const feedback = $('#titleFeedback');
   
    if (value.length < 3) {
        $('#taskTitle').addClass('is-invalid').removeClass('is-valid');
        feedback.text('العنوان يجب أن يكون 3 أحرف على الأقل').addClass('show');
        return false;
    }
    $('#taskTitle').removeClass('is-invalid').addClass('is-valid');
    feedback.removeClass('show');
    return true;
}

function validateDescription() {
    const value = $('#taskDescription').val().trim();
    const feedback = $('#descFeedback');
   
    if (value.length === 0) {
        $('#taskDescription').addClass('is-invalid').removeClass('is-valid');
        feedback.text('الرجاء إدخال وصف للمهمة').addClass('show');
        return false;
    }
    $('#taskDescription').removeClass('is-invalid').addClass('is-valid');
    feedback.removeClass('show');
    return true;
}

function validatePriority() {
    const value = $('#taskPriority').val();
    const feedback = $('#priorityFeedback');
   
    if (!value) {
        $('#taskPriority').addClass('is-invalid').removeClass('is-valid');
        feedback.addClass('show');
        return false;
    }
    $('#taskPriority').removeClass('is-invalid').addClass('is-valid');
    feedback.removeClass('show');
    return true;
}

function validateHours() {
    const hours = parseInt($('#estimatedHours').val());
    const feedback = $('#hoursFeedback');
   
    if (isNaN(hours) || hours < 1) {
        $('#estimatedHours').addClass('is-invalid').removeClass('is-valid');
        feedback.text('الرجاء إدخال عدد ساعات صحيح (1-720)').addClass('show');
        return false;
    }
   
    if (hours > 720) {
        $('#estimatedHours').addClass('is-invalid').removeClass('is-valid');
        feedback.text('الحد الأقصى للساعات هو 720').addClass('show');
        return false;
    }
   
    const startDate = $('#startDate').val();
    const endDate = $('#endDate').val();
   
    if (startDate && endDate) {
        const days = calculateWorkingDays(startDate, endDate);
        const maxHours = days * 8;
       
        if (hours > maxHours) {
            $('#estimatedHours').addClass('is-invalid').removeClass('is-valid');
            feedback.text(`الساعات المقدرة (${hours}) تتجاوز الحد الأقصى (${maxHours} ساعة) للفترة المحددة`).addClass('show');
            return false;
        }
    }
   
    $('#estimatedHours').removeClass('is-invalid').addClass('is-valid');
    feedback.removeClass('show');
    return true;
}

function validateDates() {
    const startDate = $('#startDate').val();
    const endDate = $('#endDate').val();
    const initiativeStart = $('#initiativeStartDate').val();
    const initiativeEnd = $('#initiativeEndDate').val();
    const expectedDays = parseInt($('#majorTaskExpectedDays').val()) || 30;
   
    const startFeedback = $('#startDateFeedback');
    const endFeedback = $('#endDateFeedback');
   
    let isValid = true;
   
    $('#startDate').removeClass('is-invalid is-valid');
    $('#endDate').removeClass('is-invalid is-valid');
    startFeedback.removeClass('show');
    endFeedback.removeClass('show');
   
    if (!startDate) {
        $('#startDate').addClass('is-invalid');
        startFeedback.text('الرجاء تحديد تاريخ البداية').addClass('show');
        isValid = false;
    } else if (initiativeStart && startDate < initiativeStart) {
        $('#startDate').addClass('is-invalid');
        startFeedback.text(`تاريخ البداية (${startDate}) يجب أن يكون بعد أو يساوي بداية المبادرة (${initiativeStart})`).addClass('show');
        isValid = false;
    } else if (startDate) {
        $('#startDate').addClass('is-valid');
    }
   
    if (!endDate) {
        $('#endDate').addClass('is-invalid');
        endFeedback.text('الرجاء تحديد تاريخ التسليم').addClass('show');
        isValid = false;
    } else if (startDate && endDate < startDate) {
        $('#endDate').addClass('is-invalid');
        endFeedback.text('تاريخ التسليم يجب أن يكون بعد تاريخ البداية').addClass('show');
        isValid = false;
    } else if (initiativeEnd && endDate > initiativeEnd) {
        $('#endDate').addClass('is-invalid');
        endFeedback.text(`تاريخ التسليم (${endDate}) يجب أن يكون قبل أو يساوي نهاية المبادرة (${initiativeEnd})`).addClass('show');
        isValid = false;
    } else if (endDate) {
        $('#endDate').addClass('is-valid');
    }
   
    if (startDate && endDate && isValid) {
        const days = calculateWorkingDays(startDate, endDate);
       
        if (days > expectedDays) {
            $('#endDate').addClass('is-invalid').removeClass('is-valid');
            endFeedback.text(`المدة المختارة (${days} يوم) تتجاوز المدة المتوقعة للمهمة الرئيسية (${expectedDays} يوم)`).addClass('show');
            isValid = false;
        } else {
            showDurationInfo(days, expectedDays);
        }
    }
   
    if (isValid) {
        updateExpectedHours(startDate, endDate);
        validateHours();
    }
   
    return isValid;
}

function calculateWorkingDays(startDate, endDate) {
    const start = new Date(startDate);
    const end = new Date(endDate);
    let workingDays = 0;
   
    while (start <= end) {
        const dayOfWeek = start.getDay();
        if (dayOfWeek !== 5 && dayOfWeek !== 6) {
            workingDays++;
        }
        start.setDate(start.getDate() + 1);
    }
   
    return workingDays;
}

function showDurationInfo(selectedDays, expectedDays) {
    $('#durationInfo').show();
    $('#expectedDaysDisplay').text(expectedDays + ' يوم');
    $('#selectedDaysDisplay').text(selectedDays + ' يوم');
   
    const remaining = expectedDays - selectedDays;
    if (remaining >= 0) {
        $('#remainingDaysDisplay').text(remaining + ' يوم');
        $('#remainingDaysDisplay').removeClass('bg-danger').addClass('bg-success');
    } else {
        $('#remainingDaysDisplay').text(Math.abs(remaining) + ' يوم (زيادة)');
        $('#remainingDaysDisplay').removeClass('bg-success').addClass('bg-danger');
    }
}

function updateExpectedHours(startDate, endDate) {
    if (!startDate || !endDate) return;
   
    const days = calculateWorkingDays(startDate, endDate);
    const maxHours = days * 8;
   
    const hoursInput = $('#estimatedHours');
    const currentHours = parseInt(hoursInput.val()) || 0;
   
    hoursInput.attr('max', maxHours);
   
    if (currentHours > maxHours) {
        hoursInput.val(maxHours);
        validateHours();
    }
}

function validateForm() {
    const isTitleValid = validateTitle();
    const isDescValid = validateDescription();
    const isPriorityValid = validatePriority();
    const isHoursValid = validateHours();
    const isDatesValid = validateDates();
   
    return isTitleValid && isDescValid && isPriorityValid && isHoursValid && isDatesValid;
}

function editTask(task) {
    $('#editForm').attr('action', '/operational/tasks/' + task.id);
    $('#e_title').val(task.title || task.task_name || '');
    $('#e_desc').val(task.description || '');
    $('#e_status').val(task.status_id || task.status || 16);
    $('#e_end').val(task.end_date || '');
    $('#editModal').modal('show');
}

function showToast(message, type) {
    if (typeof toastr !== 'undefined') {
        toastr[type](message);
    } else {
        alert(message);
    }
}

$('#addOperationalForm').on('submit', function(e) {
    if (!validateForm()) {
        e.preventDefault();
        showToast('الرجاء تصحيح جميع الأخطاء في النموذج', 'error');
        return false;
    }
});

@if(session('success'))
    $(document).ready(function() { showToast('{{ session('success') }}', 'success'); });
@endif

@if(session('error'))
    $(document).ready(function() { showToast('{{ session('error') }}', 'error'); });
@endif

@if($errors->any())
    $(document).ready(function() {
        @foreach($errors->all() as $error)
            showToast('{{ $error }}', 'error');
        @endforeach
    });
@endif
</script>
@endpush

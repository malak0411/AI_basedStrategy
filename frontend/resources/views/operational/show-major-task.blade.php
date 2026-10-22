@extends('layouts.app')


@section('title', 'تفاصيل المهمة الرئيسية')


@push('styles')
<style>
    .task-card {
        background: #fff; border-radius: 12px; padding: 16px; margin-bottom: 12px;
        border: 1px solid #e2e8f0; transition: all 0.2s; cursor: pointer;
    }
    .task-card:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.06); transform: translateY(-2px); }
    .empty-state { text-align: center; padding: 60px 20px; }
    .empty-state i { font-size: 64px; color: #cbd5e0; margin-bottom: 16px; }
    .dept-badge { display: inline-block; padding: 3px 10px; border-radius: 14px; font-size: 11px; margin: 2px; font-weight: 600; }
    .dept-lead { background: #d4af37; color: #1a1a2e; }
    .dept-support { background: #e2e8f0; color: #4a5568; }
    .modal-dialog-scrollable .modal-body { max-height: calc(100vh - 200px); }
    #durationInfo .alert, #e_durationInfo .alert { background: #f0f4ff; border-color: #cfe2ff; }
    .error-feedback { display: none; color: #dc3545; font-size: 0.875rem; margin-top: 0.25rem; }
    .error-feedback.show { display: block; }
    .is-invalid { border-color: #dc3545; }
    .is-valid { border-color: #28a745; }
    .task-card .task-actions { display: flex; gap: 5px; }
    .task-card .task-actions .btn { z-index: 2; position: relative; }
    .btn-kanban {
        background: #5412da; color: #fff; border: none; padding: 6px 16px;
        border-radius: 8px; font-weight: 600; transition: all 0.3s; font-size: 14px;
    }
    .btn-kanban:hover { background: #4a0fca; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(159, 122, 234, 0.3); color: #fff; }
    .btn-kanban i { margin-left: 6px; }
    .btn-gold {
        background: #d4af37; color: #1a1a2e; border: none; padding: 6px 16px;
        border-radius: 8px; font-weight: 600; transition: all 0.3s; font-size: 14px;
    }
    .btn-gold:hover { background: #c5a234; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(212, 175, 55, 0.3); color: #1a1a2e; }
    .btn-gold i { margin-left: 6px; }
</style>
@endpush


@section('content')
@php
    $operationalTasksArr = $operationalTasks instanceof \Illuminate\Support\Collection
        ? $operationalTasks->toArray()
        : (is_array($operationalTasks) ? $operationalTasks : []);
@endphp


<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <a href="javascript:history.back()" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-right"></i> العودة
            </a>
        </div>
        @if($isLeadDepartment ?? true)
            <div class="mt-2">
                <a href="{{ route('operational.dependencies', $tid) }}" class="btn btn-outline-info btn-sm">
                    <i class="fas fa-sitemap"></i> إدارة تبعيات المهام
                </a>
            </div>
        @endif
        <div>
            <a href="{{ route('operational.kanban', $tid ?? 0) }}" class="btn-kanban">
                <i class="fas fa-columns"></i> لوحة ادارة المهام
            </a>
        </div>
    </div>


    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif


    @php
        $tid = $majorTask['id'] ?? 0;
        $isActive = $majorTask['is_active'] ?? true;
        $taskTitle = $majorTask['name'] ?? $majorTask['title'] ?? 'غير محدد';
        $taskDescription = $majorTask['description'] ?? '';
        $estimatedDays = $estimatedDays ?? 0;
        $isCrossDept = $majorTask['is_cross_department'] ?? false;
        $initiativeName = $initiativeName ?? 'غير محدد';
        $departmentsList = $majorTask['departments'] ?? [];
        $initiativeStart = $initiativeStart ?? null;
        $initiativeEnd = $initiativeEnd ?? null;
        $departmentNotes = $departmentNotes ?? null;
        $majorTaskEndDate = $majorTaskEndDate ?? date('Y-m-d', strtotime('+30 days'));
        $calculatedEnd = $calculatedEnd ?? null;
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
                <small class="text-muted">المدة المتوقعة</small>
                <div><strong>{{ $estimatedDays }} يوم</strong></div>
            </div>
            <div class="col-md-3">
                <small class="text-muted">تاريخ النهاية (محسوب)</small>
                <div><strong>{{ $majorTaskEndDate }}</strong></div>
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
        <div class="row mt-2">
            <div class="col-md-4">
                <small class="text-muted">بداية المبادرة</small>
                <div><strong>{{ $initiativeStart ? \Carbon\Carbon::parse($initiativeStart)->format('Y-m-d') : 'غير محدد' }}</strong></div>
            </div>
            <div class="col-md-4">
                <small class="text-muted">نهاية المبادرة</small>
                <div><strong>{{ $initiativeEnd ? \Carbon\Carbon::parse($initiativeEnd)->format('Y-m-d') : 'غير محدد' }}</strong></div>
            </div>
        </div>
        @if(!empty($departmentsList))
            <div class="mt-3">
                <small class="text-muted">الإدارات المشاركة:</small>
                <div class="mt-1">
                    @foreach($departmentsList as $dept)
                        <span class="dept-badge {{ ($dept['responsibility_type'] ?? '') == 'LEAD' ? 'dept-lead' : 'dept-support' }}">
                            {{ ($dept['responsibility_type'] ?? '') == 'LEAD' ? 'رئيسية' : 'مساندة' }} -
                            {{ $dept['department_name'] ?? $dept['name'] ?? '' }}
                        </span>
                    @endforeach
                </div>
            </div>
        @endif
        @if(!empty($departmentNotes))
            <div class="alert alert-info mt-3 mb-0">
                <i class="fas fa-sticky-note ml-2"></i>
                <strong>ملاحظة خاصة بإدارتك:</strong> {{ $departmentNotes }}
            </div>
        @endif
    </div>


    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5><i class="fas fa-list-check ml-2"></i>المهام التشغيلية ({{ count($operationalTasksArr) }})</h5>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addOperationalModal">
                <i class="fas fa-plus"></i> إضافة مهمة يدوياً
            </button>
            <a href="{{ route('operational.generate', $tid) }}" class="btn-gold btn-sm">
                <i class="fas fa-robot"></i> توليد بالذكاء الاصطناعي
            </a>
        </div>
    </div>


    @if(empty($operationalTasksArr))
        <div class="empty-state">
            <i class="fas fa-robot"></i>
            <h5>لا توجد مهام تشغيلية تابعة لإدارتك</h5>
            <p class="text-muted">يمكنك إضافة المهام يدوياً أو استخدام الذكاء الاصطناعي لتوليدها</p>
        </div>
    @else
        @foreach($operationalTasksArr as $task)
            @php
                $taskIdForEdit = (int)($task['id'] ?? $task['task_id'] ?? 0);
                $taskNameForEdit = $task['title'] ?? $task['task_name'] ?? '';
            @endphp
            <div class="task-card" onclick="window.location='{{ route('operational.assign', $taskIdForEdit) }}'">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <h6>{{ $taskNameForEdit }}</h6>
                        <p class="text-muted small mb-2">{{ Str::limit($task['description'] ?? '', 150) }}</p>
                        <div class="d-flex gap-3 flex-wrap align-items-center">
                            @if(!empty($task['assigned_to_name']))
                                <small><i class="fas fa-user ml-1"></i> {{ $task['assigned_to_name'] }}</small>
                            @endif
                        </div>
                    </div>
                    <div class="task-actions" onclick="event.stopPropagation();">
                        <button type="button"
                                class="btn btn-sm btn-outline-primary"
                                onclick="event.stopPropagation(); fillEditForm({{ $taskIdForEdit }}); return false;"
                                title="تعديل">
                            <i class="fas fa-edit"></i>
                        </button>
                        <form action="{{ route('operational.destroy', $taskIdForEdit) }}" method="POST"
                              onsubmit="return confirm('متأكد من حذف هذه المهمة؟')" style="display:inline;">
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
                    <input type="hidden" name="major_task_id" value="{{ $tid }}">
                    <input type="hidden" name="department_id" value="{{ $departmentId ?? 0 }}">
                    <input type="hidden" name="created_by" value="{{ auth()->user()->employee_id ?? 0 }}">
                    <input type="hidden" name="status_id" value="16">
                    <input type="hidden" id="initiativeStartDate" value="{{ $initiativeStart ?? '' }}">
                    <input type="hidden" id="initiativeEndDate" value="{{ $initiativeEnd ?? '' }}">
                    <input type="hidden" id="majorTaskExpectedDays" value="{{ $estimatedDays }}">


                    <div class="modal-body">
                        <div class="alert alert-info alert-dismissible fade show">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-info-circle fa-2x ml-3"></i>
                                <div>
                                    <strong>المهمة الرئيسية:</strong> {{ $taskTitle }}
                                    <br>
                                    <small>
                                        <i class="fas fa-calendar-alt"></i>
                                        بداية المبادرة: {{ $initiativeStart ? \Carbon\Carbon::parse($initiativeStart)->format('Y-m-d') : 'غير محدد' }}
                                        <i class="fas fa-arrow-left mx-2"></i>
                                        نهاية المبادرة: {{ $initiativeEnd ? \Carbon\Carbon::parse($initiativeEnd)->format('Y-m-d') : 'غير محدد' }}
                                        @if($estimatedDays)
                                            | <i class="fas fa-clock"></i> المدة المتوقعة: {{ $estimatedDays }} يوم
                                        @endif
                                    </small>
                                </div>
                            </div>
                        </div>


                        <div class="form-group">
                            <label class="fw-bold">عنوان المهمة <span class="text-danger">*</span></label>
                            <input type="text" name="title" id="taskTitle" class="form-control form-control-lg"
                                   placeholder="أدخل عنوان المهمة التشغيلية..."
                                   maxlength="255" required>
                            <div class="error-feedback" id="titleFeedback">الرجاء إدخال عنوان المهمة (3 أحرف على الأقل)</div>
                            <small class="text-muted" id="titleCount">0/255 حرف</small>
                        </div>


                        <div class="form-group">
                            <label class="fw-bold">الوصف <span class="text-danger">*</span></label>
                            <textarea name="description" id="taskDescription" class="form-control" rows="3"
                                      placeholder="أدخل وصفاً تفصيلياً للمهمة..."
                                      maxlength="1000" required></textarea>
                            <div class="error-feedback" id="descFeedback">الرجاء إدخال وصف للمهمة</div>
                            <small class="text-muted" id="descCount">0/1000 حرف</small>
                        </div>


                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="fw-bold">الأولوية <span class="text-danger">*</span></label>
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
                            <div class="col-md-6 form-group">
                                <label class="fw-bold">الساعات المقدرة <span class="text-danger">*</span></label>
                                <input type="number" name="estimated_hours" id="estimatedHours"
                                       class="form-control" value="40"
                                       min="1" max="720" required>
                                <small class="text-muted">(1-720 ساعة)</small>
                                <div class="error-feedback" id="hoursFeedback"></div>
                            </div>
                        </div>


                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="fw-bold">تاريخ البداية <span class="text-danger">*</span></label>
                                <input type="date" name="start_date" id="startDate"
                                       class="form-control" required>
                                <small class="text-muted" id="startDateHelp">
                                    <i class="fas fa-info-circle"></i>
                                    الحد الأدنى: <span id="minStartDate">{{ $initiativeStart ?? 'غير محدد' }}</span>
                                </small>
                                <div class="error-feedback" id="startDateFeedback"></div>
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="fw-bold">تاريخ التسليم <span class="text-danger">*</span></label>
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


    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title">
                        <i class="fas fa-edit ml-2"></i>
                        تعديل مهمة تشغيلية
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>


                <form id="editForm" method="POST" action="">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="task_id" id="e_task_id">
                    <input type="hidden" name="major_task_id" value="{{ $tid }}">
                    <input type="hidden" name="department_id" value="{{ $departmentId ?? 0 }}">
                    <input type="hidden" name="updated_by" value="{{ auth()->user()->employee_id ?? 0 }}">
                    <input type="hidden" id="e_initiativeStartDate" value="{{ $initiativeStart ?? '' }}">
                    <input type="hidden" id="e_initiativeEndDate" value="{{ $initiativeEnd ?? '' }}">
                    <input type="hidden" id="e_majorTaskExpectedDays" value="{{ $estimatedDays }}">


                    <div class="modal-body">
                        <div class="alert alert-info alert-dismissible fade show">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-info-circle fa-2x ml-3"></i>
                                <div>
                                    <strong>المهمة الرئيسية:</strong> {{ $taskTitle }}
                                    <br>
                                    <small>
                                        <i class="fas fa-calendar-alt"></i>
                                        بداية المبادرة: {{ $initiativeStart ? \Carbon\Carbon::parse($initiativeStart)->format('Y-m-d') : 'غير محدد' }}
                                        <i class="fas fa-arrow-left mx-2"></i>
                                        نهاية المبادرة: {{ $initiativeEnd ? \Carbon\Carbon::parse($initiativeEnd)->format('Y-m-d') : 'غير محدد' }}
                                        @if($estimatedDays)
                                            | <i class="fas fa-clock"></i> المدة المتوقعة: {{ $estimatedDays }} يوم
                                        @endif
                                    </small>
                                </div>
                            </div>
                        </div>


                        <div class="form-group">
                            <label class="fw-bold">عنوان المهمة <span class="text-danger">*</span></label>
                            <input type="text" name="title" id="e_title" class="form-control form-control-lg"
                                   placeholder="أدخل عنوان المهمة التشغيلية..."
                                   maxlength="255" required>
                            <div class="error-feedback" id="e_titleFeedback">الرجاء إدخال عنوان المهمة (3 أحرف على الأقل)</div>
                            <small class="text-muted" id="e_titleCount">0/255 حرف</small>
                        </div>


                        <div class="form-group">
                            <label class="fw-bold">الوصف <span class="text-danger">*</span></label>
                            <textarea name="description" id="e_description" class="form-control" rows="3"
                                      placeholder="أدخل وصفاً تفصيلياً للمهمة..."
                                      maxlength="1000" required></textarea>
                            <div class="error-feedback" id="e_descFeedback">الرجاء إدخال وصف للمهمة</div>
                            <small class="text-muted" id="e_descCount">0/1000 حرف</small>
                        </div>


                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="fw-bold">الأولوية <span class="text-danger">*</span></label>
                                <select name="priority_id" id="e_priority" class="form-select" required>
                                    <option value="">-- اختر الأولوية --</option>
                                    @foreach($priorities as $p)
                                        <option value="{{ $p['priority_id'] }}">
                                            {{ $p['name_ar'] }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="error-feedback" id="e_priorityFeedback">الرجاء اختيار الأولوية</div>
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="fw-bold">الساعات المقدرة <span class="text-danger">*</span></label>
                                <input type="number" name="estimated_hours" id="e_estimatedHours"
                                       class="form-control" min="1" max="720" required>
                                <small class="text-muted">(1-720 ساعة)</small>
                                <div class="error-feedback" id="e_hoursFeedback"></div>
                            </div>
                        </div>


                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="fw-bold">تاريخ البداية <span class="text-danger">*</span></label>
                                <input type="date" name="start_date" id="e_startDate"
                                       class="form-control" required>
                                <small class="text-muted" id="e_startDateHelp">
                                    <i class="fas fa-info-circle"></i>
                                    الحد الأدنى: <span id="e_minStartDate">{{ $initiativeStart ?? 'غير محدد' }}</span>
                                </small>
                                <div class="error-feedback" id="e_startDateFeedback"></div>
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="fw-bold">تاريخ التسليم <span class="text-danger">*</span></label>
                                <input type="date" name="end_date" id="e_endDate"
                                       class="form-control" required>
                                <small class="text-muted" id="e_endDateHelp">
                                    <i class="fas fa-info-circle"></i>
                                    الحد الأعلى: <span id="e_maxEndDate">{{ $initiativeEnd ?? 'غير محدد' }}</span>
                                </small>
                                <div class="error-feedback" id="e_endDateFeedback"></div>
                            </div>
                        </div>


                        <div class="row mb-3" id="e_durationInfo" style="display:none;">
                            <div class="col-12">
                                <div class="alert alert-secondary">
                                    <div class="row text-center">
                                        <div class="col-md-4">
                                            <h6>المدة المتوقعة</h6>
                                            <span id="e_expectedDaysDisplay" class="badge bg-info">0 يوم</span>
                                        </div>
                                        <div class="col-md-4">
                                            <h6>المدة المختارة</h6>
                                            <span id="e_selectedDaysDisplay" class="badge bg-primary">0 يوم</span>
                                        </div>
                                        <div class="col-md-4">
                                            <h6>المتبقي</h6>
                                            <span id="e_remainingDaysDisplay" class="badge bg-success">0 يوم</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>


                        <div class="row mt-2">
                            <div class="col-md-6 text-md-end">
                                <small class="text-muted">
                                    <i class="fas fa-clock"></i>
                                    تاريخ التحديث: <strong>{{ now()->format('Y-m-d H:i') }}</strong>
                                </small>
                            </div>
                        </div>
                    </div>


                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> إلغاء
                        </button>
                        <button type="submit" class="btn btn-warning" id="e_submitBtn">
                            <i class="fas fa-save"></i> تحديث المهمة
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


<script type="application/json" id="operationalTasksData">{!! json_encode($operationalTasksArr, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}</script>
@endsection


@push('scripts')
<script>
(function () {
    'use strict';


    function byId(id) { return document.getElementById(id); }
    function val(id) { var el = byId(id); return el ? el.value : ''; }
    function setVal(id, v) { var el = byId(id); if (el) el.value = (v === null || v === undefined) ? '' : v; }
    function setText(id, v) { var el = byId(id); if (el) el.textContent = v; }
    function addClass(id, cls) { var el = byId(id); if (el) el.classList.add(cls); }
    function removeClass(id, cls) { var el = byId(id); if (el) el.classList.remove(cls); }
    function toggleClass(id, cls, on) { var el = byId(id); if (el) el.classList.toggle(cls, !!on); }


    function calculateWorkingDays(startDate, endDate) {
        var start = new Date(startDate);
        var end = new Date(endDate);
        var workingDays = 0;
        while (start <= end) {
            var d = start.getDay();
            if (d !== 5 && d !== 6) workingDays++;
            start.setDate(start.getDate() + 1);
        }
        return workingDays;
    }


    function initializeDates() {
        var initiativeStart = val('initiativeStartDate');
        var initiativeEnd = val('initiativeEndDate');
        var today = new Date().toISOString().split('T')[0];
        var minStart = initiativeStart || today;


        var startEl = byId('startDate');
        if (startEl) {
            startEl.setAttribute('min', minStart);
            if (!startEl.value) startEl.value = minStart;
        }
        setText('minStartDate', minStart || 'غير محدد');


        var endEl = byId('endDate');
        if (initiativeEnd) {
            if (endEl) endEl.setAttribute('max', initiativeEnd);
            setText('maxEndDate', initiativeEnd);
        }


        if (endEl && !endEl.value) {
            var s = new Date(minStart);
            s.setDate(s.getDate() + 7);
            var defaultEnd = s.toISOString().split('T')[0];
            endEl.value = defaultEnd;
            endEl.setAttribute('min', minStart);
            if (initiativeEnd && defaultEnd > initiativeEnd) endEl.value = initiativeEnd;
        }


        setTimeout(function () {
            validateDates();
            calculateDuration();
            updateEstimatedHours();
        }, 200);
    }


    function setValidity(id, isValid, feedbackId, message) {
        var el = byId(id);
        var fb = byId(feedbackId);
        if (!el) return;
        el.classList.remove('is-invalid', 'is-valid');
        if (isValid) el.classList.add('is-valid');
        else el.classList.add('is-invalid');
        if (fb) {
            if (message) fb.textContent = message;
            fb.classList.toggle('show', !isValid);
        }
    }


    function validateTitle() {
        var v = val('taskTitle').trim();
        setValidity('taskTitle', v.length >= 3, 'titleFeedback', 'العنوان يجب أن يكون 3 أحرف على الأقل');
        return v.length >= 3;
    }


    function validateDescription() {
        var v = val('taskDescription').trim();
        setValidity('taskDescription', v.length > 0, 'descFeedback', 'الرجاء إدخال وصف للمهمة');
        return v.length > 0;
    }


    function validatePriority() {
        var v = val('taskPriority');
        setValidity('taskPriority', !!v, 'priorityFeedback', 'الرجاء اختيار الأولوية');
        return !!v;
    }


    function validateHours() {
        var hours = parseInt(val('estimatedHours'));
        var startDate = val('startDate');
        var endDate = val('endDate');
        var ok = true, msg = '';


        if (isNaN(hours) || hours < 1) { ok = false; msg = 'الرجاء إدخال عدد ساعات صحيح (1-720)'; }
        else if (hours > 720) { ok = false; msg = 'الحد الأقصى للساعات هو 720'; }
        else if (startDate && endDate) {
            var maxHours = calculateWorkingDays(startDate, endDate) * 8;
            if (hours > maxHours) { ok = false; msg = 'الساعات المقدرة (' + hours + ') تتجاوز الحد الأقصى (' + maxHours + ' ساعة) للفترة المحددة'; }
        }
        setValidity('estimatedHours', ok, 'hoursFeedback', msg);
        return ok;
    }


    function validateDates() {
        var startDate = val('startDate');
        var endDate = val('endDate');
        var initiativeStart = val('initiativeStartDate');
        var initiativeEnd = val('initiativeEndDate');
        var expectedDays = parseInt(val('majorTaskExpectedDays')) || 30;


        var startEl = byId('startDate');
        var endEl = byId('endDate');
        var startFb = byId('startDateFeedback');
        var endFb = byId('endDateFeedback');


        if (startEl) startEl.classList.remove('is-invalid', 'is-valid');
        if (endEl) endEl.classList.remove('is-invalid', 'is-valid');
        if (startFb) startFb.classList.remove('show');
        if (endFb) endFb.classList.remove('show');


        var valid = true;


        if (!startDate) {
            if (startEl) startEl.classList.add('is-invalid');
            if (startFb) { startFb.textContent = 'الرجاء تحديد تاريخ البداية'; startFb.classList.add('show'); }
            valid = false;
        } else if (initiativeStart && startDate < initiativeStart) {
            if (startEl) startEl.classList.add('is-invalid');
            if (startFb) { startFb.textContent = 'تاريخ البداية يجب أن يكون بعد أو يساوي بداية المبادرة (' + initiativeStart + ')'; startFb.classList.add('show'); }
            valid = false;
        } else if (startEl) startEl.classList.add('is-valid');


        if (!endDate) {
            if (endEl) endEl.classList.add('is-invalid');
            if (endFb) { endFb.textContent = 'الرجاء تحديد تاريخ التسليم'; endFb.classList.add('show'); }
            valid = false;
        } else if (startDate && endDate < startDate) {
            if (endEl) endEl.classList.add('is-invalid');
            if (endFb) { endFb.textContent = 'تاريخ التسليم يجب أن يكون بعد تاريخ البداية'; endFb.classList.add('show'); }
            valid = false;
        } else if (initiativeEnd && endDate > initiativeEnd) {
            if (endEl) endEl.classList.add('is-invalid');
            if (endFb) { endFb.textContent = 'تاريخ التسليم يجب أن يكون قبل أو يساوي نهاية المبادرة (' + initiativeEnd + ')'; endFb.classList.add('show'); }
            valid = false;
        } else if (endEl) endEl.classList.add('is-valid');


        if (startDate && endDate && valid) {
            var days = calculateWorkingDays(startDate, endDate);
            if (days > expectedDays) {
                if (endEl) { endEl.classList.remove('is-valid'); endEl.classList.add('is-invalid'); }
                if (endFb) { endFb.textContent = 'المدة المختارة (' + days + ' يوم) تتجاوز المدة المتوقعة للمهمة الرئيسية (' + expectedDays + ' يوم)'; endFb.classList.add('show'); }
                valid = false;
            }
        }
        return valid;
    }


    function calculateDuration() {
        var startDate = val('startDate');
        var endDate = val('endDate');
        var expectedDays = parseInt(val('majorTaskExpectedDays')) || 30;
        var box = byId('durationInfo');
        if (!box) return;
        if (!startDate || !endDate) { box.style.display = 'none'; return; }
        var days = calculateWorkingDays(startDate, endDate);
        box.style.display = '';
        setText('expectedDaysDisplay', expectedDays + ' يوم');
        setText('selectedDaysDisplay', days + ' يوم');
        var rem = expectedDays - days;
        var remEl = byId('remainingDaysDisplay');
        if (remEl) {
            remEl.classList.remove('bg-success', 'bg-danger');
            if (rem >= 0) { remEl.textContent = rem + ' يوم'; remEl.classList.add('bg-success'); }
            else { remEl.textContent = Math.abs(rem) + ' يوم (زيادة)'; remEl.classList.add('bg-danger'); }
        }
    }


    function updateEstimatedHours() {
        var startDate = val('startDate');
        var endDate = val('endDate');
        if (!startDate || !endDate) return;
        var maxHours = calculateWorkingDays(startDate, endDate) * 8;
        var el = byId('estimatedHours');
        if (!el) return;
        el.setAttribute('max', maxHours);
        var cur = parseInt(el.value) || 0;
        if (cur > maxHours) el.value = maxHours;
        validateHours();
    }


    function validateForm() {
        var a = validateTitle();
        var b = validateDescription();
        var c = validatePriority();
        var d = validateHours();
        var e = validateDates();
        if (!a) { byId('taskTitle') && byId('taskTitle').focus(); return false; }
        if (!b) { byId('taskDescription') && byId('taskDescription').focus(); return false; }
        if (!c) { byId('taskPriority') && byId('taskPriority').focus(); return false; }
        if (!d) { byId('estimatedHours') && byId('estimatedHours').focus(); return false; }
        if (!e) {
            var s = byId('startDate');
            if (s && s.classList.contains('is-invalid')) s.focus();
            else byId('endDate') && byId('endDate').focus();
            return false;
        }
        return true;
    }


    function validateEditTitle() {
        var v = val('e_title').trim();
        setValidity('e_title', v.length >= 3, 'e_titleFeedback', 'العنوان يجب أن يكون 3 أحرف على الأقل');
        return v.length >= 3;
    }


    function validateEditDescription() {
        var v = val('e_description').trim();
        setValidity('e_description', v.length > 0, 'e_descFeedback', 'الرجاء إدخال وصف للمهمة');
        return v.length > 0;
    }


    function validateEditPriority() {
        var v = val('e_priority');
        setValidity('e_priority', !!v, 'e_priorityFeedback', 'الرجاء اختيار الأولوية');
        return !!v;
    }


    function validateEditHours() {
        var hours = parseInt(val('e_estimatedHours'));
        var startDate = val('e_startDate');
        var endDate = val('e_endDate');
        var ok = true, msg = '';
        if (isNaN(hours) || hours < 1) { ok = false; msg = 'الرجاء إدخال عدد ساعات صحيح (1-720)'; }
        else if (hours > 720) { ok = false; msg = 'الحد الأقصى للساعات هو 720'; }
        else if (startDate && endDate) {
            var maxHours = calculateWorkingDays(startDate, endDate) * 8;
            if (hours > maxHours) { ok = false; msg = 'الساعات المقدرة (' + hours + ') تتجاوز الحد الأقصى (' + maxHours + ' ساعة) للفترة المحددة'; }
        }
        setValidity('e_estimatedHours', ok, 'e_hoursFeedback', msg);
        return ok;
    }


    function validateEditDates() {
        var startDate = val('e_startDate');
        var endDate = val('e_endDate');
        var initiativeStart = val('e_initiativeStartDate');
        var initiativeEnd = val('e_initiativeEndDate');
        var expectedDays = parseInt(val('e_majorTaskExpectedDays')) || 30;


        var startEl = byId('e_startDate');
        var endEl = byId('e_endDate');
        var startFb = byId('e_startDateFeedback');
        var endFb = byId('e_endDateFeedback');


        if (startEl) startEl.classList.remove('is-invalid', 'is-valid');
        if (endEl) endEl.classList.remove('is-invalid', 'is-valid');
        if (startFb) startFb.classList.remove('show');
        if (endFb) endFb.classList.remove('show');


        var valid = true;


        if (!startDate) {
            if (startEl) startEl.classList.add('is-invalid');
            if (startFb) { startFb.textContent = 'الرجاء تحديد تاريخ البداية'; startFb.classList.add('show'); }
            valid = false;
        } else if (initiativeStart && startDate < initiativeStart) {
            if (startEl) startEl.classList.add('is-invalid');
            if (startFb) { startFb.textContent = 'تاريخ البداية يجب أن يكون بعد أو يساوي بداية المبادرة'; startFb.classList.add('show'); }
            valid = false;
        } else if (startEl) startEl.classList.add('is-valid');


        if (!endDate) {
            if (endEl) endEl.classList.add('is-invalid');
            if (endFb) { endFb.textContent = 'الرجاء تحديد تاريخ التسليم'; endFb.classList.add('show'); }
            valid = false;
        } else if (startDate && endDate < startDate) {
            if (endEl) endEl.classList.add('is-invalid');
            if (endFb) { endFb.textContent = 'تاريخ التسليم يجب أن يكون بعد تاريخ البداية'; endFb.classList.add('show'); }
            valid = false;
        } else if (initiativeEnd && endDate > initiativeEnd) {
            if (endEl) endEl.classList.add('is-invalid');
            if (endFb) { endFb.textContent = 'تاريخ التسليم يجب أن يكون قبل أو يساوي نهاية المبادرة'; endFb.classList.add('show'); }
            valid = false;
        } else if (endEl) endEl.classList.add('is-valid');


        if (startDate && endDate && valid) {
            var days = calculateWorkingDays(startDate, endDate);
            if (days > expectedDays) {
                if (endEl) { endEl.classList.remove('is-valid'); endEl.classList.add('is-invalid'); }
                if (endFb) { endFb.textContent = 'المدة المختارة (' + days + ' يوم) تتجاوز المدة المتوقعة (' + expectedDays + ' يوم)'; endFb.classList.add('show'); }
                valid = false;
            }
        }


        if (valid) {
            calculateEditDuration();
            updateEditEstimatedHours();
        }
        return valid;
    }


    function calculateEditDuration() {
        var startDate = val('e_startDate');
        var endDate = val('e_endDate');
        var expectedDays = parseInt(val('e_majorTaskExpectedDays')) || 30;
        var box = byId('e_durationInfo');
        if (!box) return;
        if (!startDate || !endDate) { box.style.display = 'none'; return; }
        var days = calculateWorkingDays(startDate, endDate);
        box.style.display = '';
        setText('e_expectedDaysDisplay', expectedDays + ' يوم');
        setText('e_selectedDaysDisplay', days + ' يوم');
        var rem = expectedDays - days;
        var remEl = byId('e_remainingDaysDisplay');
        if (remEl) {
            remEl.classList.remove('bg-success', 'bg-danger');
            if (rem >= 0) { remEl.textContent = rem + ' يوم'; remEl.classList.add('bg-success'); }
            else { remEl.textContent = Math.abs(rem) + ' يوم (زيادة)'; remEl.classList.add('bg-danger'); }
        }
    }


    function updateEditEstimatedHours() {
        var startDate = val('e_startDate');
        var endDate = val('e_endDate');
        if (!startDate || !endDate) return;
        var maxHours = calculateWorkingDays(startDate, endDate) * 8;
        var el = byId('e_estimatedHours');
        if (!el) return;
        el.setAttribute('max', maxHours);
        var cur = parseInt(el.value) || 0;
        if (cur > maxHours) el.value = maxHours;
        validateEditHours();
    }


    function validateEditForm() {
        var a = validateEditTitle();
        var b = validateEditDescription();
        var c = validateEditPriority();
        var d = validateEditHours();
        var e = validateEditDates();
        if (!a) { byId('e_title') && byId('e_title').focus(); return false; }
        if (!b) { byId('e_description') && byId('e_description').focus(); return false; }
        if (!c) { byId('e_priority') && byId('e_priority').focus(); return false; }
        if (!d) { byId('e_estimatedHours') && byId('e_estimatedHours').focus(); return false; }
        if (!e) {
            var s = byId('e_startDate');
            if (s && s.classList.contains('is-invalid')) s.focus();
            else byId('e_endDate') && byId('e_endDate').focus();
            return false;
        }
        return true;
    }


    window.fillEditForm = function (taskId) {
        console.log('[fillEditForm] START taskId=', taskId);
        try {
            var el = byId('operationalTasksData');
            if (!el) { alert('عنصر البيانات غير موجود'); return; }


            var raw = (el.textContent || el.innerHTML || '').trim();
            if (!raw) { alert('لا توجد بيانات مهام'); return; }


            var parsed;
            try { parsed = JSON.parse(raw); }
            catch (parseErr) {
                console.error('[fillEditForm] JSON.parse فشل:', parseErr, raw.substring(0, 300));
                alert('خطأ في قراءة بيانات المهام: ' + parseErr.message);
                return;
            }


            var tasks = Array.isArray(parsed) ? parsed : Object.values(parsed || {});
            var task = null;
            for (var i = 0; i < tasks.length; i++) {
                var t = tasks[i];
                if (!t) continue;
                var id = (t.id !== undefined && t.id !== null) ? t.id : t.task_id;
                if (String(id) === String(taskId)) { task = t; break; }
            }


            if (!task) {
                var ids = tasks.map(function (t) { return (t.id !== undefined && t.id !== null) ? t.id : t.task_id; });
                console.error('[fillEditForm] task not found. wanted=', taskId, 'available=', ids);
                alert('لم يتم العثور على المهمة رقم ' + taskId);
                return;
            }


            var resolvedId = (task.id !== undefined && task.id !== null) ? task.id : (task.task_id || 0);
            var form = byId('editForm');
            if (!form) { alert('نموذج التعديل غير موجود'); return; }


            var baseUrl = "{{ url('/operational/tasks') }}/" + resolvedId;
            form.setAttribute('action', baseUrl);
            console.log('[fillEditForm] form action=', baseUrl);


            setVal('e_task_id', resolvedId);
            setVal('e_title', task.title || task.task_name || '');
            setVal('e_description', task.description || '');
            setVal('e_priority', task.priority_id || task.priority || '');
            setVal('e_estimatedHours', task.estimated_hours || 40);
            setVal('e_startDate', task.start_date ? String(task.start_date).substring(0, 10) : '');
            setVal('e_endDate', task.end_date ? String(task.end_date).substring(0, 10) : '');


            setText('e_titleCount', (val('e_title') || '').length + '/255');
            setText('e_descCount', (val('e_description') || '').length + '/1000');


            var iS = val('e_initiativeStartDate');
            var iE = val('e_initiativeEndDate');
            if (iS && byId('e_startDate')) byId('e_startDate').setAttribute('min', iS);
            if (iE && byId('e_endDate')) byId('e_endDate').setAttribute('max', iE);


            var modalEl = byId('editModal');
            var shown = false;
            try {
                if (window.bootstrap && typeof window.bootstrap.Modal === 'function') {
                    var m = window.bootstrap.Modal.getInstance(modalEl) || new window.bootstrap.Modal(modalEl);
                    m.show();
                    shown = true;
                }
            } catch (errB) { console.warn('[fillEditForm] Bootstrap.Modal failed:', errB); }


            if (!shown) {
                console.error('[fillEditForm] Bootstrap JS غير محمّل');
                alert('Bootstrap JS غير محمّل. تعذّر فتح نافذة التعديل.');
                return;
            }


            setTimeout(function () {
                try {
                    validateEditTitle();
                    validateEditDescription();
                    validateEditPriority();
                    validateEditHours();
                    validateEditDates();
                    calculateEditDuration();
                    updateEditEstimatedHours();
                } catch (e2) { console.warn('[fillEditForm] validation:', e2); }
            }, 300);


        } catch (err) {
            console.error('[fillEditForm] FATAL:', err);
            alert('خطأ: ' + (err && err.message ? err.message : err));
        }
    };


    document.addEventListener('DOMContentLoaded', function () {
        initializeDates();


        var titleEl = byId('taskTitle');
        if (titleEl) titleEl.addEventListener('input', function () {
            setText('titleCount', this.value.length + '/255');
            validateTitle();
        });


        var descEl = byId('taskDescription');
        if (descEl) descEl.addEventListener('input', function () {
            setText('descCount', this.value.length + '/1000');
            validateDescription();
        });


        var priEl = byId('taskPriority');
        if (priEl) priEl.addEventListener('change', validatePriority);


        var hrsEl = byId('estimatedHours');
        if (hrsEl) hrsEl.addEventListener('input', function () {
            validateHours();
            calculateDuration();
        });


        var sDate = byId('startDate'), eDate = byId('endDate');
        if (sDate) sDate.addEventListener('change', function () {
            validateDates(); calculateDuration(); updateEstimatedHours();
        });
        if (eDate) eDate.addEventListener('change', function () {
            validateDates(); calculateDuration(); updateEstimatedHours();
        });


        var addModal = byId('addOperationalModal');
        if (addModal) addModal.addEventListener('shown.bs.modal', function () {
            initializeDates();
            validateDates();
            calculateDuration();
            updateEstimatedHours();
        });


        var addForm = byId('addOperationalForm');
        if (addForm) addForm.addEventListener('submit', function (ev) {
            if (!validateForm()) {
                ev.preventDefault();
                if (window.toastr) toastr.error('الرجاء تصحيح جميع الأخطاء في النموذج');
                return false;
            }
        });


        var eTitle = byId('e_title');
        if (eTitle) eTitle.addEventListener('input', function () {
            setText('e_titleCount', this.value.length + '/255');
            validateEditTitle();
        });


        var eDesc = byId('e_description');
        if (eDesc) eDesc.addEventListener('input', function () {
            setText('e_descCount', this.value.length + '/1000');
            validateEditDescription();
        });


        var ePri = byId('e_priority');
        if (ePri) ePri.addEventListener('change', validateEditPriority);


        var eHrs = byId('e_estimatedHours');
        if (eHrs) eHrs.addEventListener('input', function () {
            validateEditHours();
            calculateEditDuration();
        });


        var eS = byId('e_startDate'), eE = byId('e_endDate');
        if (eS) eS.addEventListener('change', function () {
            validateEditDates(); calculateEditDuration(); updateEditEstimatedHours();
        });
        if (eE) eE.addEventListener('change', function () {
            validateEditDates(); calculateEditDuration(); updateEditEstimatedHours();
        });


        var editForm = byId('editForm');
        if (editForm) editForm.addEventListener('submit', function (ev) {
            if (!validateEditForm()) {
                ev.preventDefault();
                if (window.toastr) toastr.error('الرجاء تصحيح جميع الأخطاء في النموذج');
                return false;
            }
        });


        var editModal = byId('editModal');
        if (editModal) {
            editModal.addEventListener('shown.bs.modal', function () {
                byId('e_title') && byId('e_title').focus();
            });
            editModal.addEventListener('hidden.bs.modal', function () {
                if (editForm) editForm.reset();
                var inv = editForm.querySelectorAll('.is-invalid, .is-valid');
                for (var i = 0; i < inv.length; i++) inv[i].classList.remove('is-invalid', 'is-valid');
                var fbs = editForm.querySelectorAll('.error-feedback');
                for (var j = 0; j < fbs.length; j++) fbs[j].classList.remove('show');
                var di = byId('e_durationInfo'); if (di) di.style.display = 'none';
            });
        }


        @if(session('success'))
            if (window.toastr) toastr.success(@json(session('success')));
        @endif
        @if(session('error'))
            if (window.toastr) toastr.error(@json(session('error')));
        @endif
        @if($errors->any())
            @foreach($errors->all() as $error)
                if (window.toastr) toastr.error(@json($error));
            @endforeach
        @endif
    });
})();
</script>
@endpush

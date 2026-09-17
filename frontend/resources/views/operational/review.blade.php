@extends('layouts.app')


@section('title', 'مراجعة واعتماد المهام التشغيلية')


@section('content')


<div class="container-fluid px-4 py-4"><div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-1">مراجعة واعتماد المهام التشغيلية</h3>
        <p class="text-muted mb-0">
            راجع المهام التشغيلية التي تم توليدها قبل اعتمادها وحفظها في النظام.
        </p>
    </div>


    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-primary" id="openAiEdit">
            تعديل باستخدام الذكاء الاصطناعي
        </button>
        <button type="button" class="btn btn-success" id="approveButton">
            اعتماد وحفظ المهام
        </button>
    </div>
</div>


<div id="pageAlert"></div>


<div class="card shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <div class="border rounded p-3 h-100">
                    <div class="text-muted small mb-1">رقم المهمة الرئيسية</div>
                    <div class="fw-bold">
                        {{ $job['result']['major_task_id'] ?? request('major_task_id') ?? '-' }}
                    </div>
                </div>
            </div>


            <div class="col-md-4">
                <div class="border rounded p-3 h-100">
                    <div class="text-muted small mb-1">رقم عملية الذكاء الاصطناعي</div>
                    <div class="fw-bold">
                        {{ $job['job_id'] ?? request('job_id') ?? '-' }}
                    </div>
                </div>
            </div>


            <div class="col-md-4">
                <div class="border rounded p-3 h-100">
                    <div class="text-muted small mb-1">عدد المهام التشغيلية</div>
                    <div class="fw-bold" id="taskCount">
                        {{ count($tasks ?? []) }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<form method="POST" action="{{ route('operational.approve') }}" id="approvalForm">
    @csrf


    <input type="hidden" name="job_id" value="{{ $job['job_id'] ?? request('job_id') }}">
    <input type="hidden" name="tasks_data" id="tasksData">


    <div id="tasksContainer">


        @forelse($tasks as $index => $task)


            <div class="card shadow-sm mb-4 task-card" data-index="{{ $index }}">


                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary task-number">
                            {{ $index + 1 }}
                        </span>
                        <span class="fw-bold">المهمة التشغيلية</span>
                    </div>


                    <button type="button" class="btn btn-sm btn-outline-danger remove-task">
                        حذف المهمة
                    </button>
                </div>


                <div class="card-body">


                    <div class="row g-3">


                        <div class="col-12">
                            <label class="form-label fw-semibold">عنوان المهمة</label>
                            <input
                                type="text"
                                class="form-control task-title"
                                value="{{ $task['title'] ?? $task['name'] ?? '' }}"
                                required
                            >
                        </div>


                        <div class="col-12">
                            <label class="form-label fw-semibold">وصف المهمة</label>
                            <textarea
                                class="form-control task-description"
                                rows="3"
                            >{{ $task['description'] ?? '' }}</textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">الأولوية</label>
                            <select class="form-select task-priority">
                                <option value="High" {{ ($task['priority'] ?? '') === 'High' ? 'selected' : '' }}>
                                    عالية
                                </option>
                                <option value="Medium" {{ ($task['priority'] ?? 'Medium') === 'Medium' ? 'selected' : '' }}>
                                    متوسطة
                                </option>
                                <option value="Low" {{ ($task['priority'] ?? '') === 'Low' ? 'selected' : '' }}>
                                    منخفضة
                                </option>
                            </select>
                        </div>


                        <div class="col-md-4">
                            <label class="form-label fw-semibold">الساعات المقدرة</label>
                            <input
                                type="number"
                                min="0.5"
                                step="0.5"
                                class="form-control task-hours"
                                value="{{ $task['estimated_hours'] ?? 8 }}"
                                required
                            >
                        </div>


                        <div class="col-md-6">
                            <label class="form-label fw-semibold">تاريخ البداية</label>
                            <input
                                type="date"
                                class="form-control task-start"
                                value="{{ $task['start_date'] ?? $task['start'] ?? '' }}"
                            >
                        </div>


                        <div class="col-md-6">
                            <label class="form-label fw-semibold">تاريخ النهاية</label>
                            <input
                                type="date"
                                class="form-control task-end"
                                value="{{ $task['end_date'] ?? $task['end'] ?? '' }}"
                            >
                        </div>


                    </div>


                </div>
            </div>


        @empty


            <div class="card shadow-sm">
                <div class="card-body text-center py-5">
                    <div class="mb-3">
                        <i class="bi bi-inbox fs-1 text-muted"></i>
                    </div>


                    <h5>لا توجد مهام تشغيلية</h5>


                    <p class="text-muted mb-0">
                        لم يتم العثور على مهام تشغيلية في نتيجة التوليد.
                    </p>
                </div>
            </div>


        @endforelse


    </div>


    <div class="d-flex justify-content-center mb-5">
        <button type="button" class="btn btn-outline-primary px-4" id="addTaskButton">
            إضافة مهمة تشغيلية
        </button>
    </div>


    <div class="card shadow-sm border-success mb-5">
        <div class="card-body d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-1">اعتماد المهام التشغيلية</h5>
                <p class="text-muted mb-0">
                    بعد الاعتماد سيتم حفظ المهام وربطها بالمهمة الرئيسية.
                </p>
            </div>


            <button type="submit" class="btn btn-success px-5" id="approveButton">
                اعتماد وحفظ
            </button>
        </div>
    </div>


</form>


</div><div class="modal fade" id="aiEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">        <div class="modal-header">
            <h5 class="modal-title">تعديل المهام باستخدام الذكاء الاصطناعي</h5>


            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="modal"
                aria-label="Close"
            ></button>
        </div>


        <div class="modal-body">


            <label class="form-label fw-semibold">
                اكتب التعديل المطلوب
            </label>


            <textarea
                id="aiInstruction"
                class="form-control"
                rows="5"
                placeholder="مثال: اجعل المهام أكثر تفصيلاً ووزع الساعات بشكل واقعي"
            ></textarea>


            <div id="aiEditAlert" class="mt-3"></div>


        </div>


        <div class="modal-footer">


            <button
                type="button"
                class="btn btn-secondary"
                data-bs-dismiss="modal"
            >
                إلغاء
            </button>


            <button
                type="button"
                class="btn btn-primary"
                id="sendAiEdit"
            >
                تنفيذ التعديل
            </button>


        </div>


    </div>
</div>


</div>
@endsection
@push('scripts')


<script>
document.addEventListener('DOMContentLoaded', function () {


    const tasksContainer = document.getElementById('tasksContainer');
    const addTaskButton = document.getElementById('addTaskButton');
    const approvalForm = document.getElementById('approvalForm');
    const tasksData = document.getElementById('tasksData');
    const taskCount = document.getElementById('taskCount');
    const pageAlert = document.getElementById('pageAlert');


    const aiEditModalElement = document.getElementById('aiEditModal');
    const aiInstruction = document.getElementById('aiInstruction');
    const aiEditAlert = document.getElementById('aiEditAlert');
    const sendAiEdit = document.getElementById('sendAiEdit');
    const openAiEdit = document.getElementById('openAiEdit');


    const jobId = @json($job['job_id'] ?? request('job_id'));


    let aiModal = null;


    if (aiEditModalElement && typeof bootstrap !== 'undefined') {
        aiModal = new bootstrap.Modal(aiEditModalElement);
    }


    function updateTaskNumbers() {
        const cards = tasksContainer.querySelectorAll('.task-card');


        cards.forEach((card, index) => {
            const number = card.querySelector('.task-number');


            if (number) {
                number.textContent = index + 1;
            }
        });


        taskCount.textContent = cards.length;
    }


    function createTaskCard() {
        const card = document.createElement('div');


        card.className = 'card shadow-sm mb-4 task-card';


        card.innerHTML = `
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary task-number"></span>
                    <span class="fw-bold">المهمة التشغيلية</span>
                </div>


                <button type="button" class="btn btn-sm btn-outline-danger remove-task">
                    حذف المهمة
                </button>
            </div>


            <div class="card-body">
                <div class="row g-3">


                    <div class="col-12">
                        <label class="form-label fw-semibold">عنوان المهمة</label>
                        <input type="text" class="form-control task-title" required>
                    </div>


                    <div class="col-12">
                        <label class="form-label fw-semibold">وصف المهمة</label>
                        <textarea class="form-control task-description" rows="3"></textarea>                
                    </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">الأولوية</label>
                    <select class="form-select task-priority">
                        <option value="High">عالية</option>
                        <option value="Medium" selected>متوسطة</option>
                        <option value="Low">منخفضة</option>
                    </select>
                </div>


                <div class="col-md-4">
                    <label class="form-label fw-semibold">الساعات المقدرة</label>
                    <input
                        type="number"
                        min="0.5"
                        step="0.5"
                        value="8"
                        class="form-control task-hours"
                        required
                    >
                </div>


                <div class="col-md-6">
                    <label class="form-label fw-semibold">تاريخ البداية</label>
                    <input type="date" class="form-control task-start">
                </div>


                <div class="col-md-6">
                    <label class="form-label fw-semibold">تاريخ النهاية</label>
                    <input type="date" class="form-control task-end">
                </div>


            </div>
        </div>
    `;


    return card;
}


addTaskButton.addEventListener('click', function () {
    const card = createTaskCard();


    tasksContainer.appendChild(card);


    updateTaskNumbers();


    card.scrollIntoView({
        behavior: 'smooth',
        block: 'center'
    });
});


tasksContainer.addEventListener('click', function (event) {


    const removeButton = event.target.closest('.remove-task');


    if (!removeButton) {
        return;
    }


    const cards = tasksContainer.querySelectorAll('.task-card');


    if (cards.length <= 1) {
        showAlert('يجب أن تحتوي الخطة على مهمة تشغيلية واحدة على الأقل.', 'warning');
        return;
    }


    const card = removeButton.closest('.task-card');


    if (card) {
        card.remove();
        updateTaskNumbers();
    }
});


function collectTasks() {
    const cards = tasksContainer.querySelectorAll('.task-card');
    const tasks = [];


    cards.forEach(card => {


        const title = card.querySelector('.task-title')?.value.trim() || '';
        const description = card.querySelector('.task-description')?.value.trim() || '';
        const departmentId = card.querySelector('.task-department')?.value || '';
        const priority = card.querySelector('.task-priority')?.value || 'Medium';
        const estimatedHours = card.querySelector('.task-hours')?.value || '';
        const startDate = card.querySelector('.task-start')?.value || '';
        const endDate = card.querySelector('.task-end')?.value || '';


        tasks.push({
            title: title,
            description: description,
            priority: priority,
            estimated_hours: estimatedHours ? Number(estimatedHours) : null,
            start_date: startDate || null,
            end_date: endDate || null
        });
    });


    return tasks;
}


function validateTasks(tasks) {


    if (!tasks.length) {
        showAlert('لا توجد مهام تشغيلية للاعتماد.', 'danger');
        return false;
    }


    for (let i = 0; i < tasks.length; i++) {


        if (!tasks[i].title) {
            showAlert(
                'يرجى إدخال عنوان المهمة رقم ' + (i + 1),
                'danger'
            );


            return false;
        }


        if (!tasks[i].estimated_hours) {
            showAlert(
                'يرجى إدخال الساعات المقدرة للمهمة رقم ' + (i + 1),
                'danger'
            );


            return false;
        }


        if (
            tasks[i].start_date &&
            tasks[i].end_date &&
            tasks[i].end_date < tasks[i].start_date
        ) {
            showAlert(
                'تاريخ النهاية يجب أن يكون بعد تاريخ البداية للمهمة رقم ' + (i + 1),
                'danger'
            );


            return false;
        }
    }


    return true;
}


approvalForm.addEventListener('submit', function (event) {


    event.preventDefault();


    const tasks = collectTasks();


    if (!validateTasks(tasks)) {
        return;
    }


    tasksData.value = JSON.stringify(tasks);


    const button = document.getElementById('approveButton');


    button.disabled = true;
    button.textContent = 'جاري الحفظ...';


    approvalForm.submit();
});


function showAlert(message, type) {


    pageAlert.innerHTML = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
                aria-label="Close"
            ></button>
        </div>
    `;


    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}


openAiEdit.addEventListener('click', function () {


    aiInstruction.value = '';
    aiEditAlert.innerHTML = '';


    if (aiModal) {
        aiModal.show();
    }
});


sendAiEdit.addEventListener('click', async function () {


    const instruction = aiInstruction.value.trim();


    if (!instruction) {
        aiEditAlert.innerHTML = `
            <div class="alert alert-warning">
                يرجى كتابة التعديل المطلوب.
            </div>
        `;


        return;
    }


    sendAiEdit.disabled = true;
    sendAiEdit.textContent = 'جاري التعديل...';


    aiEditAlert.innerHTML = '';


    try {


        const response = await fetch(
            @json(route('operational.edit-prompt')),
            {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': @json(csrf_token()),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    job_id: jobId,
                    instruction: instruction
                })
            }
        );


        const data = await response.json();


        if (!response.ok || !data.success) {
            throw new Error(
                data.detail ||
                data.message ||
                'فشل تنفيذ التعديل'
            );
        }


        aiEditAlert.innerHTML = `
            <div class="alert alert-success">
                تم إرسال التعديل بنجاح. سيتم تحديث نتيجة المهمة بعد انتهاء المعالجة.
            </div>
        `;


        setTimeout(function () {
            window.location.reload();
        }, 2000);


    } catch (error) {


        aiEditAlert.innerHTML = `
            <div class="alert alert-danger">
                ${error.message}
            </div>
        `;


    } finally {


        sendAiEdit.disabled = false;
        sendAiEdit.textContent = 'تنفيذ التعديل';
    }
});


updateTaskNumbers();


});
</script>
@endpush

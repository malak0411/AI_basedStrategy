@extends('layouts.app')

@section('title', 'مراجعة المهام التشغيلية')

@push('styles')
<style>
    .task-review-card {
        background: #fff;
        border-radius: 12px;
        padding: 16px;
        margin-bottom: 12px;
        border: 1px solid #e2e8f0;
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
        padding: 4px 8px;
        border-radius: 4px;
        width: 100%;
    }
    .editable-field:hover {
        border-color: #d4af37;
        background: #fffbeb;
    }
    .editable-field:focus {
        border-color: #d4af37;
        outline: none;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3>مراجعة المهام التشغيلية</h3>
            <p class="text-muted mb-0">Job #{{ $job['job_id'] ?? '' }}</p>
        </div>
        <span class="badge bg-warning fs-6">بانتظار الاعتماد</span>
    </div>

    <div class="prompt-edit-box">
        <h6>تعديل جميع المهام باستخدام الذكاء الاصطناعي</h6>
        <div class="row">
            <div class="col-md-9">
                <input type="text" id="editInstruction" class="form-control" placeholder="مثال: اجعل المهمة الأولى مدتها 20 ساعة وأضف مهمة للتدقيق...">
            </div>
            <div class="col-md-3">
                <button class="btn btn-info w-100" onclick="editWithPrompt()" id="editBtn">
                    تعديل بالذكاء الاصطناعي
                </button>
            </div>
        </div>
    </div>

    @if(empty($tasks))
    <div class="card-custom text-center py-5">
        <h5>لا توجد مهام للتوليد</h5>
    </div>
    @else
    @foreach($tasks as $index => $task)
    <div class="task-review-card" data-index="{{ $index }}">
        <div class="d-flex justify-content-between align-items-start mb-2">
            <input type="text" class="editable-field fw-bold" data-field="title" data-index="{{ $index }}" value="{{ $task['title'] ?? '' }}" placeholder="اسم المهمة">
            <button class="btn btn-sm btn-outline-danger" onclick="this.closest('.task-review-card').remove()" title="حذف">
                <i class="fas fa-trash"></i>
            </button>
        </div>
        <textarea class="editable-field form-control mb-2" data-field="description" data-index="{{ $index }}" rows="2" placeholder="الوصف">{{ $task['description'] ?? '' }}</textarea>
        <div class="row">
            <div class="col-md-3 mb-2">
                <label class="small text-muted">الإدارة</label>
                <select class="form-select form-select-sm" data-field="department_id" data-index="{{ $index }}">
                    <option value="">اختر الإدارة</option>
                    @foreach($departments as $d)
                    <option value="{{ $d['department_id'] }}" {{ ($task['department_id'] ?? '') == $d['department_id'] ? 'selected' : '' }}>
                        {{ $d['name'] }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 mb-2">
                <label class="small text-muted">الأولوية</label>
                <select class="form-select form-select-sm" data-field="priority_id" data-index="{{ $index }}">
                    <option value="1" {{ ($task['priority_id'] ?? 2) == 1 ? 'selected' : '' }}>منخفضة</option>
                    <option value="2" {{ ($task['priority_id'] ?? 2) == 2 ? 'selected' : '' }}>متوسطة</option>
                    <option value="3" {{ ($task['priority_id'] ?? 2) == 3 ? 'selected' : '' }}>عالية</option>
                    <option value="4" {{ ($task['priority_id'] ?? 2) == 4 ? 'selected' : '' }}>حرجة</option>
                </select>
            </div>
            <div class="col-md-2 mb-2">
                <label class="small text-muted">الساعات</label>
                <input type="number" class="form-control form-control-sm" data-field="estimated_hours" data-index="{{ $index }}" value="{{ $task['estimated_hours'] ?? 40 }}" min="1">
            </div>
            <div class="col-md-2 mb-2">
                <label class="small text-muted">البداية</label>
                <input type="date" class="form-control form-control-sm" data-field="start_date" data-index="{{ $index }}" value="{{ $task['start_date'] ?? '' }}">
            </div>
            <div class="col-md-3 mb-2">
                <label class="small text-muted">النهاية</label>
                <input type="date" class="form-control form-control-sm" data-field="end_date" data-index="{{ $index }}" value="{{ $task['end_date'] ?? '' }}">
            </div>
        </div>
    </div>
    @endforeach

    <div class="text-center mt-4">
        <form method="POST" action="{{ route('operational.approve') }}" id="approveForm">
            @csrf
            <input type="hidden" name="job_id" value="{{ $job['job_id'] ?? '' }}">
            <input type="hidden" name="tasks_data" id="tasksDataInput">
            <button type="submit" class="btn-gold btn-lg" onclick="prepareSubmit(event)">
                اعتماد وحفظ جميع المهام
            </button>
        </form>
        <a href="{{ route('operational.major-tasks') }}" class="btn btn-outline-secondary btn-lg mr-3">إلغاء</a>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
function prepareSubmit(event) {
    const tasks = [];
    document.querySelectorAll('.task-review-card').forEach(card => {
        const index = card.getAttribute('data-index');
        const task = {};
        card.querySelectorAll('[data-field][data-index="' + index + '"]').forEach(el => {
            task[el.getAttribute('data-field')] = el.value;
        });
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
        const response = await fetch('{{ route("operational.edit-prompt") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                job_id: '{{ $job["job_id"] ?? "" }}',
                instruction: instruction
            })
        });
        const data = await response.json();
        if (data.success) {
            window.location.reload();
        } else {
            alert('فشل التعديل: ' + (data.detail || 'خطأ غير معروف'));
        }
    } catch (e) {
        alert('خطأ في الاتصال');
    } finally {
        btn.disabled = false;
        btn.innerHTML = 'تعديل بالذكاء الاصطناعي';
    }
}
</script>
@endpush

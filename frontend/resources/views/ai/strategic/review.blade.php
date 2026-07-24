@extends('layouts.app')

@section('title', 'Review Generated Tasks')

@push('styles')
<style>
    .task-review-card {
        background: #fff; border-radius: 16px; padding: 24px;
        margin-bottom: 16px; border: 1px solid #e2e8f0;
        transition: all 0.3s ease; position: relative;
    }
    .task-review-card:hover { box-shadow: 0 4px 15px rgba(0,0,0,0.06); }
    .task-number {
        width: 36px; height: 36px; border-radius: 50%;
        background: #1a4a8a; color: #d4af37;
        display: flex; align-items: center; justify-content: center;
        font-weight: 800; font-size: 16px;
    }
    .dept-lead { background: #d4af37; color: #1a1a2e; padding: 4px 10px; border-radius: 20px; font-size: 11px; }
    .dept-support { background: #e2e8f0; color: #4a5568; padding: 4px 10px; border-radius: 20px; font-size: 11px; }
    .deliverable-tag { background: #38a169; color: #fff; padding: 3px 10px; border-radius: 12px; font-size: 11px; }
    .prompt-edit-box { background: #f8fafc; border: 2px dashed #d4af37; border-radius: 12px; padding: 16px; margin-bottom: 20px; }
    .editable-field { border: 1px dashed transparent; padding: 4px 8px; border-radius: 4px; transition: all 0.2s; }
    .editable-field:hover { border-color: #d4af37; background: #fffbeb; }
    .editable-field:focus { border-color: #d4af37; background: #fff; outline: none; }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3>Review Major Tasks</h3>
            <p class="text-muted mb-0">
                Initiative: <strong>{{ $initiative['name'] ?? $initiative['title'] ?? '' }}</strong>
                | Job #{{ $jobId }}
            </p>
        </div>
        <span class="badge bg-warning fs-6">Pending approval</span>
    </div>

    <div class="prompt-edit-box">
        <h6>Edit plan using AI prompt</h6>
        <div class="row">
            <div class="col-md-9">
                <input type="text" id="editInstruction" class="form-control" 
                    placeholder="Example: Make first task 30 days and add Planning department...">
            </div>
            <div class="col-md-3">
                <button class="btn btn-info w-100" onclick="editWithPrompt()" id="editBtn">
                    Edit with AI
                </button>
            </div>
        </div>
    </div>

    <div id="tasksContainer">
        @forelse($tasks as $index => $task)
        <div class="task-review-card" id="task-{{ $index }}">
            <div class="d-flex gap-3">
                <div class="task-number">{{ $index + 1 }}</div>
                <div class="flex-grow-1">
                    <h5 class="mb-2 editable-field" contenteditable="true" data-field="name" data-index="{{ $index }}">
                        {{ $task['name'] ?? '' }}
                    </h5>
                    <p class="text-muted small mb-3 editable-field" contenteditable="true" data-field="description" data-index="{{ $index }}">
                        {{ $task['description'] ?? '' }}
                    </p>

                    <div class="row mb-2">
                        <div class="col-md-3">
                            <small class="text-muted">Duration:</small>
                            <strong class="editable-field" contenteditable="true" data-field="estimated_duration_days" data-index="{{ $index }}">
                                {{ $task['estimated_duration_days'] ?? 0 }}
                            </strong> days
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted">Priority:</small>
                            <select class="form-select form-select-sm d-inline w-auto" data-field="priority" data-index="{{ $index }}" onchange="updateField(this)">
                                <option value="High" {{ ($task['priority'] ?? '') == 'High' ? 'selected' : '' }}>High</option>
                                <option value="Medium" {{ ($task['priority'] ?? '') == 'Medium' ? 'selected' : '' }}>Medium</option>
                                <option value="Low" {{ ($task['priority'] ?? '') == 'Low' ? 'selected' : '' }}>Low</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted">Cross-department:</small>
                            <input type="checkbox" data-field="is_cross_department" data-index="{{ $index }}" 
                                {{ ($task['is_cross_department'] ?? false) ? 'checked' : '' }} onchange="updateField(this)">
                        </div>
                    </div>

                    @if(!empty($task['departments']))
                    <div class="mb-2">
                        <small class="text-muted">Departments:</small>
                        @foreach($task['departments'] as $dept)
                            <span class="badge {{ ($dept['responsibility_type'] ?? '') == 'LEAD' ? 'dept-lead' : 'dept-support' }} me-1">
                                {{ ($dept['responsibility_type'] ?? '') == 'LEAD' ? 'LEAD' : 'SUPPORT' }}
                                {{ $dept['department_name'] ?? 'Dept #'.$dept['department_id'] }}
                            </span>
                        @endforeach
                    </div>
                    @endif

                    @if(!empty($task['deliverables']))
                    <div>
                        <small class="text-muted">Deliverables:</small>
                        @foreach($task['deliverables'] as $d)
                            <span class="deliverable-tag me-1">{{ $d }}</span>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="card-custom text-center py-5">
            <h5>No tasks generated</h5>
            <p class="text-muted">No tasks were generated for this initiative</p>
            <a href="{{ route('ai.strategic.index') }}" class="btn btn-primary">Back to generator</a>
        </div>
        @endforelse
    </div>

    @if(!empty($tasks))
    <div class="text-center mt-4">
        <form method="POST" action="{{ route('ai.strategic.approve') }}" style="display:inline;">
            @csrf
            <input type="hidden" name="job_id" value="{{ $jobId }}">
            <input type="hidden" name="initiative_id" value="{{ $initiativeId }}">
            <input type="hidden" name="tasks_data" id="tasksDataInput">
            <button type="submit" class="btn-gold btn-lg" onclick="prepareSubmit()">
                Approve Plan and Save
            </button>
        </form>
        <a href="{{ route('ai.strategic.index') }}" class="btn btn-outline-secondary btn-lg mr-3">Cancel</a>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    async function editWithPrompt() {
        const instruction = document.getElementById('editInstruction').value;
        if (!instruction) return alert('Enter edit instructions');

        const btn = document.getElementById('editBtn');
        btn.disabled = true;
        btn.innerHTML = 'Editing...';

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
            if (data.major_tasks) window.location.reload();
            else alert('Edit failed');
        } catch(e) { alert('Connection error'); }
        finally { btn.disabled = false; btn.innerHTML = 'Edit with AI'; }
    }

    function updateField(el) {
        el.style.borderColor = '#38a169';
        setTimeout(() => el.style.borderColor = 'transparent', 1000);
    }

    function prepareSubmit() {
        const tasks = [];
        document.querySelectorAll('.task-review-card').forEach(card => {
            const task = {};
            card.querySelectorAll('[data-field]').forEach(el => {
                const field = el.dataset.field;
                if (el.type === 'checkbox') task[field] = el.checked;
                else if (el.tagName === 'SELECT') task[field] = el.value;
                else task[field] = el.textContent.trim();
            });
            tasks.push(task);
        });
        document.getElementById('tasksDataInput').value = JSON.stringify(tasks);
    }
</script>
@endpush

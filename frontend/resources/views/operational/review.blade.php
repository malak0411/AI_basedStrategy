@extends('layouts.app')


@section('title', 'مراجعة المهام التشغيلية')


@push('styles')
<style>
    :root {
        --brand-primary: #1a4a8a;
        --brand-gold: #d4af37;
        --brand-dark: #0f172a;
        --brand-muted: #64748b;
        --brand-border: #e2e8f0;
    }


    .review-page { max-width: 1280px; margin: 0 auto; }


    .page-head {
        display: flex; justify-content: space-between; align-items: center;
        padding: 20px 0; border-bottom: 1px solid var(--brand-border); margin-bottom: 28px;
    }
    .page-head h3 {
        margin: 0; font-weight: 800; font-size: 22px; color: var(--brand-dark);
        display: flex; align-items: center; gap: 10px;
    }
    .page-head h3 i { color: var(--brand-gold); }


    .status-badge {
        padding: 8px 18px; border-radius: 20px; font-weight: 800;
        font-size: 12px; letter-spacing: .3px; text-transform: uppercase;
        display: inline-flex; align-items: center; gap: 6px;
    }
    .status-badge.review { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
    .status-badge.editing { background: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe; }
    .status-badge.completed { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
    .status-badge.failed { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
    .status-badge.pending { background: #f3f4f6; color: #374151; border: 1px solid #e5e7eb; }


    .section-card {
        background: #fff; border-radius: 16px;
        border: 1px solid var(--brand-border);
        box-shadow: 0 2px 12px rgba(0,0,0,.04);
        padding: 24px; margin-bottom: 24px;
    }
    .section-head {
        display: flex; justify-content: space-between; align-items: center;
        margin-bottom: 20px; padding-bottom: 16px;
        border-bottom: 1px solid var(--brand-border);
    }
    .section-head h5 {
        margin: 0; font-weight: 800; font-size: 16px; color: var(--brand-dark);
        display: flex; align-items: center; gap: 10px;
    }
    .section-head h5 i { color: var(--brand-gold); }
    .section-head .count {
        background: var(--brand-primary); color: #fff;
        padding: 2px 10px; border-radius: 12px;
        font-size: 12px; font-weight: 700;
    }
    .section-head small { color: var(--brand-muted); font-size: 12px; }


    .task-card {
        background: #fff;
        border: 1px solid var(--brand-border);
        border-radius: 14px;
        padding: 18px 20px;
        margin-bottom: 12px;
        transition: all .2s;
        position: relative;
        overflow: hidden;
    }
    .task-card::before {
        content: ''; position: absolute; right: 0; top: 0; bottom: 0;
        width: 4px; background: var(--brand-gold); opacity: 0;
        transition: opacity .2s;
    }
    .task-card:hover {
        border-color: #d4af37;
        box-shadow: 0 6px 20px rgba(212,175,55,.12);
        transform: translateY(-1px);
    }
    .task-card:hover::before { opacity: 1; }


    .task-title {
        font-size: 15px; font-weight: 800; color: var(--brand-dark);
        margin: 0 0 8px; line-height: 1.4;
    }
    .task-desc {
        font-size: 13px; color: var(--brand-muted);
        line-height: 1.7; margin: 0 0 14px;
    }
    .task-meta {
        display: flex; flex-wrap: wrap; gap: 18px;
        font-size: 12px; color: var(--brand-muted);
    }
    .task-meta span {
        display: inline-flex; align-items: center; gap: 6px;
    }
    .task-meta i { color: var(--brand-gold); }
    .task-meta .priority {
        background: #fef3c7; color: #92400e;
        padding: 3px 10px; border-radius: 10px; font-weight: 700;
    }


    .task-actions {
        display: flex; gap: 6px; margin-right: auto;
    }
    .btn-icon {
        width: 36px; height: 36px; border-radius: 10px;
        border: 1px solid var(--brand-border);
        background: #fff; color: var(--brand-muted);
        display: inline-flex; align-items: center; justify-content: center;
        transition: all .2s; font-size: 13px;
    }
    .btn-icon:hover.edit { background: #dbeafe; border-color: #60a5fa; color: #1e40af; }
    .btn-icon:hover.delete { background: #fee2e2; border-color: #f87171; color: #dc2626; }


    .empty-tasks {
        text-align: center; padding: 48px 20px;
        color: var(--brand-muted);
    }
    .empty-tasks i {
        font-size: 48px; color: #cbd5e0; margin-bottom: 16px;
        display: block;
    }
    .empty-tasks p { margin: 0; font-size: 14px; }


    .prompt-box {
        background: linear-gradient(135deg, #faf5ff, #f3e8ff);
        border: 1px dashed #c4b5fd;
        border-radius: 14px;
        padding: 20px;
    }
    .prompt-box textarea {
        border: 2px solid #ddd6fe; border-radius: 10px;
        padding: 12px 14px; font-size: 14px; resize: vertical;
        width: 100%; transition: all .2s; line-height: 1.7;
        background: #fff;
    }
    .prompt-box textarea:focus {
        border-color: #7c3aed;
        box-shadow: 0 0 0 4px rgba(124,58,237,.1);
        outline: none;
    }
    .btn-prompt {
        background: linear-gradient(135deg, #7c3aed, #6d28d9);
        color: #fff; border: none;
        padding: 12px 28px; border-radius: 10px;
        font-weight: 700; font-size: 14px;
        display: inline-flex; align-items: center; gap: 8px;
        transition: all .2s;
        box-shadow: 0 4px 12px rgba(124,58,237,.25);
    }
    .btn-prompt:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(124,58,237,.35);
        color: #fff;
    }


    .approve-card {
        background: linear-gradient(135deg, #065f46, #047857);
        border-radius: 16px;
        padding: 24px;
        color: #fff;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 20px;
        flex-wrap: wrap;
    }
    .approve-card h6 {
        margin: 0 0 6px; font-size: 17px; font-weight: 800; color: #fff;
    }
    .approve-card p {
        margin: 0; font-size: 13px; color: rgba(255,255,255,.75);
    }
    .btn-approve {
        background: #d4af37; color: #1a1a2e;
        border: none; padding: 14px 32px;
        border-radius: 12px; font-weight: 800; font-size: 15px;
        display: inline-flex; align-items: center; gap: 10px;
        transition: all .25s;
        box-shadow: 0 4px 16px rgba(0,0,0,.2);
    }
    .btn-approve:hover:not(:disabled) {
        background: #e5c04a;
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0,0,0,.3);
        color: #1a1a2e;
    }
    .btn-approve:disabled { opacity: .5; cursor: not-allowed; }


    .modal-content { border: none; border-radius: 16px; overflow: hidden; }
    .modal-header {
        background: linear-gradient(135deg, #d4af37, #b8942a);
        color: #1a1a2e; border: none; padding: 18px 24px;
    }
    .modal-header .modal-title {
        font-weight: 800; display: flex; align-items: center; gap: 10px;
    }
    .modal-body { padding: 24px; }
    .modal-body label {
        font-weight: 700; font-size: 13px; color: var(--brand-dark);
        margin-bottom: 6px; display: block;
    }
    .modal-body .form-control, .modal-body .form-select {
        border: 2px solid var(--brand-border);
        border-radius: 10px; padding: 10px 14px;
        font-size: 14px; transition: all .2s;
    }
    .modal-body .form-control:focus, .modal-body .form-select:focus {
        border-color: var(--brand-gold);
        box-shadow: 0 0 0 4px rgba(212,175,55,.1);
        outline: none;
    }
    .modal-footer { border-top: 1px solid var(--brand-border); padding: 16px 24px; }
    .modal-footer .btn { border-radius: 10px; padding: 10px 24px; font-weight: 700; }


    .op-overlay {
        position: fixed; inset: 0;
        background: linear-gradient(135deg, #0f172a 0%, #1a1a2e 50%, #16213e 100%);
        z-index: 99999; display: none; align-items: center; justify-content: center;
        color: #fff; padding: 20px;
    }
    .op-overlay.show { display: flex; animation: fadeIn .3s ease; }
    @keyframes fadeIn { from { opacity: 0 } to { opacity: 1 } }


    .overlay-panel { text-align: center; max-width: 480px; width: 100%; }
    .overlay-spinner {
        position: relative; width: 90px; height: 90px; margin: 0 auto 28px;
    }
    .overlay-spinner .ring {
        position: absolute; inset: 0;
        border: 4px solid rgba(212,175,55,.15);
        border-radius: 50%;
    }
    .overlay-spinner .ring::before {
        content: ''; position: absolute; inset: -4px;
        border: 4px solid transparent;
        border-top-color: #d4af37;
        border-radius: 50%; animation: spin 1.2s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }


    .overlay-title {
        font-size: 20px; font-weight: 800; margin: 0 0 10px; color: #fff;
    }
    .overlay-subtitle {
        font-size: 13px; color: rgba(255,255,255,.7);
        margin: 0 0 20px; line-height: 1.6;
    }
    .overlay-timer {
        display: inline-flex; align-items: baseline; gap: 8px;
        background: rgba(212,175,55,.12); padding: 10px 20px;
        border-radius: 12px; border: 1px solid rgba(212,175,55,.25);
        margin-bottom: 16px;
    }
    .overlay-timer .seconds {
        font-size: 28px; font-weight: 900; color: #d4af37; line-height: 1;
    }
    .overlay-timer .label { font-size: 12px; color: rgba(255,255,255,.6); }
    .overlay-status {
        font-size: 12px; color: rgba(255,255,255,.5); margin: 0;
        font-family: monospace;
    }
</style>
@endpush


@section('content')
@php
    $priorities = $priorities ?? [];
    $statusClass = 'pending';
    if (($jobStatus ?? '') === 'review') $statusClass = 'review';
    elseif (($jobStatus ?? '') === 'editing') $statusClass = 'editing';
    elseif (($jobStatus ?? '') === 'completed') $statusClass = 'completed';
    elseif (($jobStatus ?? '') === 'failed') $statusClass = 'failed';
@endphp


<div class="container-fluid px-4 review-page">


    <div class="page-head">
        <h3>
            <i class="fas fa-clipboard-check"></i>
            مراجعة المهام التشغيلية
        </h3>
        <span class="status-badge {{ $statusClass }}" id="jobStatusBadge">
            <i class="fas fa-circle" style="font-size:7px;"></i>
            {{ $jobStatus }}
        </span>
    </div>


    @if(session('error'))
        <div class="alert alert-danger rounded-3 mb-4">{{ session('error') }}</div>
    @endif


    <div class="section-card">
        <div class="section-head">
            <h5>
                <i class="fas fa-list-check"></i>
                المهام المولّدة
                <span class="count" id="taskCount">{{ count($tasks) }}</span>
            </h5>
            <small>يمكنك التعديل أو الحذف يدويًا أو إعادة التوليد بالبرومت</small>
        </div>


        <div id="tasksContainer">
            @forelse($tasks as $i => $t)
                <div class="task-card" data-index="{{ $i }}">
                    <div class="d-flex justify-content-between align-items-start gap-3">
                        <div class="flex-grow-1">
                            <h6 class="task-title">{{ $t['title'] ?? '' }}</h6>
                            <p class="task-desc">{{ $t['description'] ?? '' }}</p>
                            <div class="task-meta">
                                <span class="priority">
                                    <i class="fas fa-flag"></i>
                                    أولوية {{ $t['priority_id'] ?? '—' }}
                                </span>
                                <span>
                                    <i class="fas fa-clock"></i>
                                    {{ $t['estimated_hours'] ?? 0 }} ساعة
                                </span>
                                <span>
                                    <i class="fas fa-calendar-alt"></i>
                                    {{ $t['start_date'] ?? '' }} → {{ $t['end_date'] ?? '' }}
                                </span>
                            </div>
                        </div>
                        <div class="task-actions">
                            <button class="btn-icon edit" onclick="openEdit({{ $i }})" title="تعديل">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn-icon delete" onclick="deleteTask({{ $i }})" title="حذف">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="empty-tasks">
                    <i class="fas fa-inbox"></i>
                    <p>لا توجد مهام حالياً</p>
                </div>
            @endforelse
        </div>
    </div>


    <div class="section-card">
        <div class="section-head">
            <h5>
                <i class="fas fa-magic"></i>
                إعادة التوليد بالبرومت
            </h5>
            <small>صف احتياجك بلغة طبيعية</small>
        </div>
        <div class="prompt-box">
            <textarea id="editPrompt" rows="2"
                placeholder="مثال: أضف مهمة للتدقيق النهائي، واحذف المهام التي تتضمن كلمة تقارير"></textarea>
            <div class="d-flex justify-content-end mt-3">
                <button type="button" class="btn-prompt" onclick="editWithPrompt()">
                    <i class="fas fa-robot"></i>
                    إعادة التوليد
                </button>
            </div>
        </div>
    </div>


    <div class="approve-card">
        <div>
            <h6><i class="fas fa-check-double ml-2"></i> الاعتماد والحفظ</h6>
            <p>سيتم حفظ المهام في قاعدة البيانات وإسنادها إلى إدارتك.</p>
        </div>
        <form method="POST" action="{{ route('operational.approve') }}" id="approveForm" onsubmit="return confirmApprove()">
            @csrf
            <input type="hidden" name="job_id" value="{{ $jobId }}">
            <button type="submit" class="btn-approve" id="approveBtn" @disabled($jobStatus !== 'review')>
                <i class="fas fa-check-double"></i>
                اعتماد وحفظ المهام
            </button>
        </form>
    </div>
</div>


<div class="modal fade" id="editTaskModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit"></i>
                    تعديل المهمة
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="e_index">
                <div class="mb-3">
                    <label>العنوان</label>
                    <input type="text" id="e_title" class="form-control" maxlength="255">
                </div>
                <div class="mb-3">
                    <label>الوصف</label>
                    <textarea id="e_description" class="form-control" rows="3" maxlength="1000"></textarea>
                </div>
                <div class="row g-2">
                    <div class="col-md-4">
                        <label>الأولوية</label>
                        <select id="e_priority_id" class="form-select">
                            @foreach($priorities as $p)
                                <option value="{{ $p['priority_id'] }}">{{ $p['name_ar'] ?? $p['priority_id'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label>الساعات</label>
                        <input type="number" id="e_estimated_hours" class="form-control" min="1" max="120">
                    </div>
                    <div class="col-md-2">
                        <label>من</label>
                        <input type="date" id="e_start_date" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label>إلى</label>
                        <input type="date" id="e_end_date" class="form-control">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button class="btn btn-warning" onclick="saveEditedTask()">
                    <i class="fas fa-save"></i> حفظ التعديل
                </button>
            </div>
        </div>
    </div>
</div>


<div class="op-overlay" id="editOverlay">
    <div class="overlay-panel">
        <div class="overlay-spinner">
            <div class="ring"></div>
        </div>
        <h2 class="overlay-title">جاري إعادة التوليد</h2>
        <p class="overlay-subtitle">يتم تطبيق تعليماتك على المهام</p>
        <div class="overlay-timer">
            <span class="seconds" id="editTimer">0</span>
            <span class="label">ثانية</span>
        </div>
        <p class="overlay-status" id="editStatus">جاري التحقق...</p>
    </div>
</div>
@endsection


@push('scripts')
<script>
(function () {
    'use strict';


    let tasks = @json($tasks);
    const jobId         = @json($jobId);
    const updateUrlBase = '/operational/tasks';
    const editUrlBase   = '/operational/edit';
    const jobUrlBase    = '/operational/job';


    const overlay    = document.getElementById('editOverlay');
    const editTimer  = document.getElementById('editTimer');
    const editStatus = document.getElementById('editStatus');


    let timerHandle = null, seconds = 0, attempts = 0;


    function getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta && meta.getAttribute('content')) return meta.getAttribute('content');
        const input = document.querySelector('input[name="_token"]');
        if (input && input.value) return input.value;
        return '';
    }


    function readApiErrorText(status, data, statusText) {
        if (data) {
            if (data.errors) return Object.values(data.errors).flat().join('\n');
            if (data.detail) return data.detail;
            if (data.message) return data.message;
        }
        if (status === 419) return 'انتهت الجلسة (CSRF). أعد تحميل الصفحة.';
        if (status === 401) return 'انتهت جلستك. سجّل الدخول من جديد.';
        if (status === 403) return 'غير مصرح.';
        if (status === 404) return 'المسار غير موجود.';
        if (status === 422) return 'بيانات غير صالحة. راجع القيم.';
        if (status === 500) return 'خطأ على الخادم. راجع السجلات.';
        return 'HTTP ' + status + ' ' + (statusText || '');
    }


    function escapeHtml(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
        });
    }


    function setJobStatus(status) {
        const badge = document.getElementById('jobStatusBadge');
        if (!badge) return;
        badge.className = 'status-badge';
        const map = {
            review: 'review', editing: 'editing',
            completed: 'completed', failed: 'failed', pending: 'pending'
        };
        badge.classList.add(map[status] || 'pending');
        badge.innerHTML = '<i class="fas fa-circle" style="font-size:7px;"></i> ' + escapeHtml(status);
    }


    function updateUI() {
        document.getElementById('taskCount').textContent = tasks.length;
        const container = document.getElementById('tasksContainer');
        container.innerHTML = '';
        if (!tasks.length) {
            container.innerHTML = '<div class="empty-tasks"><i class="fas fa-inbox"></i><p>لا توجد مهام حالياً</p></div>';
            return;
        }
        tasks.forEach(function (t, i) {
            const div = document.createElement('div');
            div.className = 'task-card';
            div.innerHTML =
                '<div class="d-flex justify-content-between align-items-start gap-3">' +
                    '<div class="flex-grow-1">' +
                        '<h6 class="task-title">' + escapeHtml(t.title) + '</h6>' +
                        '<p class="task-desc">' + escapeHtml(t.description) + '</p>' +
                        '<div class="task-meta">' +
                            '<span class="priority"><i class="fas fa-flag"></i> أولوية ' + escapeHtml(t.priority_id == null ? '—' : t.priority_id) + '</span>' +
                            '<span><i class="fas fa-clock"></i> ' + escapeHtml(t.estimated_hours == null ? 0 : t.estimated_hours) + ' ساعة</span>' +
                            '<span><i class="fas fa-calendar-alt"></i> ' + escapeHtml(t.start_date || '') + ' → ' + escapeHtml(t.end_date || '') + '</span>' +
                        '</div>' +
                    '</div>' +
                    '<div class="task-actions">' +
                        '<button class="btn-icon edit" onclick="openEdit(' + i + ')"><i class="fas fa-edit"></i></button>' +
                        '<button class="btn-icon delete" onclick="deleteTask(' + i + ')"><i class="fas fa-trash"></i></button>' +
                    '</div>' +
                '</div>';
            container.appendChild(div);
        });
    }


    async function persistTasks() {
        let res;
        try {
            res = await fetch(updateUrlBase + '/' + jobId, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
                body: JSON.stringify({ operational_tasks: tasks }),
            });
        } catch (netErr) {
            console.error('[persistTasks] network error', netErr);
            alert('تعذر الوصول للخادم: ' + (netErr.message || netErr));
            return false;
        }


        const raw = await res.text();
        console.log('[persistTasks] HTTP', res.status, 'response:', raw);


        let data = null;
        try { data = JSON.parse(raw); } catch (_) { data = null; }


        if (!res.ok) {
            alert(readApiErrorText(res.status, data, res.statusText));
            return false;
        }
        if (!data || !data.success) {
            alert((data && (data.detail || data.message)) || 'فشل تحديث المهام');
            return false;
        }
        return true;
    }


    window.openEdit = function (idx) {
        const t = tasks[idx];
        document.getElementById('e_index').value = idx;
        document.getElementById('e_title').value = t.title || '';
        document.getElementById('e_description').value = t.description || '';
        document.getElementById('e_priority_id').value = t.priority_id != null ? t.priority_id : '';
        document.getElementById('e_estimated_hours').value = t.estimated_hours != null ? t.estimated_hours : 40;
        document.getElementById('e_start_date').value = t.start_date || '';
        document.getElementById('e_end_date').value = t.end_date || '';
        new bootstrap.Modal(document.getElementById('editTaskModal')).show();
    };


    window.saveEditedTask = async function () {
        const idx = parseInt(document.getElementById('e_index').value, 10);
        const updated = {
            title: document.getElementById('e_title').value.trim(),
            description: document.getElementById('e_description').value.trim(),
            priority_id: parseInt(document.getElementById('e_priority_id').value, 10),
            estimated_hours: parseFloat(document.getElementById('e_estimated_hours').value),
            start_date: document.getElementById('e_start_date').value,
            end_date: document.getElementById('e_end_date').value,
        };
        if (!updated.title || updated.title.length < 3) { alert('العنوان يجب أن يكون 3 أحرف على الأقل.'); return; }
        if (!updated.description) { alert('الوصف مطلوب.'); return; }
        if (!updated.start_date || !updated.end_date || updated.end_date < updated.start_date) {
            alert('تحقق من تاريخي البداية والنهاية.'); return;
        }
        const prev = tasks[idx];
        tasks[idx] = updated;
        const ok = await persistTasks();
        if (ok) {
            bootstrap.Modal.getInstance(document.getElementById('editTaskModal')).hide();
            updateUI();
        } else {
            tasks[idx] = prev;
        }
    };


    window.deleteTask = async function (idx) {
        if (!confirm('حذف هذه المهمة؟')) return;
        const removed = tasks.splice(idx, 1)[0];
        const ok = await persistTasks();
        if (ok) updateUI();
        else tasks.splice(idx, 0, removed);
    };


    function startEditTimer() {
        seconds = 0;
        editTimer.textContent = '0';
        timerHandle = setInterval(function () { seconds++; editTimer.textContent = seconds; }, 1000);
    }


    function stopEditTimer() {
        if (timerHandle) { clearInterval(timerHandle); timerHandle = null; }
    }


    async function pollEdit() {
        attempts++;
        if (attempts > 180) {
            stopEditTimer();
            overlay.classList.remove('show');
            alert('انتهت مدة الانتظار');
            return;
        }
        try {
            const res = await fetch(jobUrlBase + '/' + encodeURIComponent(jobId), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                cache: 'no-store',
                credentials: 'same-origin'
            });
            const data = await res.json();
            if (!data.success) throw new Error('bad response');
            const job = data.data;
            editStatus.textContent = 'الحالة: ' + job.status;
            setJobStatus(job.status);


            if (job.status === 'review') {
                stopEditTimer();
                tasks = (job.result && job.result.operational_tasks) || [];
                overlay.classList.remove('show');
                updateUI();
                const btn = document.getElementById('approveBtn');
                if (btn) btn.removeAttribute('disabled');
                return;
            }
            if (job.status === 'failed') {
                stopEditTimer();
                overlay.classList.remove('show');
                alert((job.result && job.result.error) || 'فشلت إعادة التوليد');
                return;
            }
            setTimeout(pollEdit, 2000);
        } catch (e) {
            setTimeout(pollEdit, 3000);
        }
    }


    window.editWithPrompt = async function () {
        const instruction = document.getElementById('editPrompt').value.trim();
        if (!instruction) { alert('أدخل التعليمات أولاً'); return; }


        let res;
        try {
            res = await fetch(editUrlBase + '/' + jobId, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
                body: JSON.stringify({ instruction: instruction }),
            });
        } catch (netErr) {
            console.error('[editWithPrompt] network error', netErr);
            alert('تعذر الوصول للخادم: ' + (netErr.message || netErr));
            return;
        }


        const raw = await res.text();
        console.log('[editWithPrompt] HTTP', res.status, 'response:', raw);


        let data = null;
        try { data = JSON.parse(raw); } catch (_) { data = null; }


        if (!res.ok) {
            alert(readApiErrorText(res.status, data, res.statusText));
            return;
        }
        if (!data || !data.success) {
            alert((data && (data.detail || data.message)) || 'فشل بدء إعادة التوليد');
            return;
        }


        overlay.classList.add('show');
        document.getElementById('editPrompt').value = '';
        attempts = 0;
        startEditTimer();
        setJobStatus('editing');
        const btn = document.getElementById('approveBtn');
        if (btn) btn.setAttribute('disabled', 'disabled');
        pollEdit();
    };


    window.confirmApprove = function () {
        return confirm('سيتم حفظ المهام في قاعدة البيانات. متابعة؟');
    };


    updateUI();
})();
</script>
@endpush

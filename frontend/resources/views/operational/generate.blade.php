@extends('layouts.app')


@section('title', 'توليد المهام التشغيلية')


@push('styles')
<style>
    :root {
        --brand-primary: #1a4a8a;
        --brand-gold: #d4af37;
        --brand-dark: #0f172a;
        --brand-muted: #64748b;
        --brand-border: #e2e8f0;
        --brand-bg: #f8fafc;
    }


    .gen-page { max-width: 1280px; margin: 0 auto; }


    .page-head {
        display: flex; justify-content: space-between; align-items: center;
        padding: 20px 0; border-bottom: 1px solid var(--brand-border); margin-bottom: 28px;
    }
    .page-head h3 {
        margin: 0; font-weight: 800; font-size: 22px; color: var(--brand-dark);
        display: flex; align-items: center; gap: 10px;
    }
    .page-head h3 i { color: var(--brand-gold); font-size: 20px; }
    .page-head .model-badge {
        background: linear-gradient(135deg, #d4af37, #b8942a);
        color: #1a1a2e; padding: 6px 14px; border-radius: 20px;
        font-weight: 700; font-size: 12px; letter-spacing: .3px;
        display: inline-flex; align-items: center; gap: 6px;
        box-shadow: 0 2px 8px rgba(212,175,55,.3);
    }


    .steps {
        display: flex; gap: 0; margin-bottom: 32px;
        background: #fff; border-radius: 14px; overflow: hidden;
        border: 1px solid var(--brand-border);
        box-shadow: 0 1px 3px rgba(0,0,0,.03);
    }
    .step-item {
        flex: 1; padding: 16px 20px; text-align: center;
        font-size: 13px; font-weight: 600; color: var(--brand-muted);
        display: flex; align-items: center; justify-content: center; gap: 10px;
        position: relative; transition: all .25s;
    }
    .step-item .num {
        width: 26px; height: 26px; border-radius: 50%;
        background: #e2e8f0; color: #64748b; font-size: 12px; font-weight: 800;
        display: flex; align-items: center; justify-content: center;
    }
    .step-item.active {
        background: linear-gradient(135deg, #1a4a8a, #2563a8);
        color: #fff;
    }
    .step-item.active .num {
        background: rgba(255,255,255,.25); color: #fff;
    }
    .step-item.done { color: #16a34a; background: #f0fdf4; }
    .step-item.done .num { background: #16a34a; color: #fff; }


    .strat-section {
        background: #fff; border-radius: 16px;
        border: 1px solid var(--brand-border);
        box-shadow: 0 2px 12px rgba(0,0,0,.04);
        padding: 24px; margin-bottom: 24px;
    }
    .strat-section h5 {
        margin: 0 0 22px; font-weight: 800; font-size: 16px; color: var(--brand-dark);
        display: flex; align-items: center; gap: 10px;
    }
    .strat-section h5 i { color: var(--brand-gold); }


    .strat-chain { position: relative; padding-right: 26px; }
    .strat-chain::before {
        content: ''; position: absolute; right: 10px; top: 10px; bottom: 10px;
        width: 2px;
        background: linear-gradient(to bottom, #d4af37 0%, #1a4a8a 33%, #0d9488 66%, #7c3aed 100%);
        border-radius: 2px; opacity: .35;
    }


    .strat-node {
        position: relative; background: #fff;
        border: 1px solid var(--brand-border); border-radius: 12px;
        padding: 16px 20px; margin-bottom: 14px; transition: all .2s;
        box-shadow: 0 1px 2px rgba(0,0,0,.03);
    }
    .strat-node:hover {
        transform: translateX(-2px);
        box-shadow: 0 4px 14px rgba(0,0,0,.07);
    }
    .strat-node::before {
        content: ''; position: absolute; right: -20px; top: 50%; transform: translateY(-50%);
        width: 14px; height: 14px; border-radius: 50%;
        background: #fff; border: 3px solid currentColor;
        box-shadow: 0 0 0 3px #fff;
    }
    .strat-node.goal        { border-right: 4px solid #d4af37; color: #d4af37; }
    .strat-node.program     { border-right: 4px solid #1a4a8a; color: #1a4a8a; }
    .strat-node.initiative  { border-right: 4px solid #0d9488; color: #0d9488; }
    .strat-node.major-task  { border-right: 4px solid #7c3aed; color: #7c3aed; background: linear-gradient(135deg, #faf5ff, #ffffff); }


    .strat-node .node-label {
        display: inline-block; font-size: 10px; font-weight: 800;
        letter-spacing: .8px; text-transform: uppercase;
        background: currentColor; color: #fff;
        padding: 3px 10px; border-radius: 10px; margin-bottom: 10px;
    }
    .strat-node .node-title {
        font-size: 15px; font-weight: 700; color: var(--brand-dark);
        margin: 0 0 6px; line-height: 1.4;
    }
    .strat-node .node-desc {
        font-size: 13px; color: var(--brand-muted); line-height: 1.6;
        margin: 0 0 10px;
    }
    .strat-node .node-meta {
        display: flex; flex-wrap: wrap; gap: 16px;
        font-size: 12px; color: var(--brand-muted);
    }
    .strat-node .node-meta span {
        display: inline-flex; align-items: center; gap: 5px;
    }
    .strat-node .node-meta i { color: currentColor; }


    .dept-chips { margin-top: 12px; display: flex; flex-wrap: wrap; gap: 6px; }
    .dept-chip {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 4px 12px; border-radius: 20px;
        font-size: 11px; font-weight: 700;
    }
    .dept-chip.lead { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
    .dept-chip.support { background: #e0e7ff; color: #3730a3; border: 1px solid #c7d2fe; }


    .form-section {
        background: #fff; border-radius: 16px;
        border: 1px solid var(--brand-border);
        box-shadow: 0 2px 12px rgba(0,0,0,.04);
        padding: 24px; margin-bottom: 24px;
    }
    .form-section h5 {
        margin: 0 0 16px; font-weight: 800; font-size: 16px; color: var(--brand-dark);
        display: flex; align-items: center; gap: 10px;
    }
    .form-section h5 i { color: var(--brand-gold); }


    .form-section textarea {
        border: 2px solid var(--brand-border); border-radius: 12px;
        padding: 14px 16px; font-size: 14px; resize: vertical;
        transition: all .2s; line-height: 1.7;
    }
    .form-section textarea:focus {
        border-color: var(--brand-gold); box-shadow: 0 0 0 4px rgba(212,175,55,.12);
        outline: none;
    }


    .btn-generate {
        background: linear-gradient(135deg, #d4af37, #b8942a);
        color: #1a1a2e; border: none; padding: 14px 32px;
        border-radius: 12px; font-weight: 800; font-size: 15px;
        display: inline-flex; align-items: center; gap: 10px;
        transition: all .25s; box-shadow: 0 4px 16px rgba(212,175,55,.3);
    }
    .btn-generate:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(212,175,55,.4);
        color: #1a1a2e;
    }
    .btn-generate:disabled { opacity: .5; cursor: not-allowed; }


    .info-card {
        background: linear-gradient(135deg, #f8fafc, #f1f5f9);
        border-radius: 16px; border: 1px solid var(--brand-border);
        padding: 22px; position: sticky; top: 20px;
    }
    .info-card h6 {
        margin: 0 0 16px; font-weight: 800; font-size: 14px; color: var(--brand-dark);
        display: flex; align-items: center; gap: 8px;
    }
    .info-card h6 i { color: var(--brand-gold); }
    .info-card ol {
        margin: 0; padding-right: 20px; font-size: 13px;
        color: var(--brand-muted); line-height: 2;
    }
    .info-card ol li { padding-right: 4px; }
    .info-card ol li::marker { color: var(--brand-gold); font-weight: 800; }


    .gen-overlay {
        position: fixed; inset: 0;
        background: linear-gradient(135deg, #0f172a 0%, #1a1a2e 50%, #16213e 100%);
        z-index: 99999; display: none; align-items: center; justify-content: center;
        color: #fff; padding: 20px;
    }
    .gen-overlay.show { display: flex; animation: fadeIn .3s ease; }
    .gen-overlay.error { background: linear-gradient(135deg, #7f1d1d, #991b1b); }
    @keyframes fadeIn { from { opacity: 0 } to { opacity: 1 } }


    .overlay-panel {
        text-align: center; max-width: 540px; width: 100%;
    }
    .overlay-spinner {
        position: relative; width: 100px; height: 100px; margin: 0 auto 32px;
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
    .overlay-spinner .ring::after {
        content: ''; position: absolute; inset: 8px;
        border: 3px solid transparent;
        border-top-color: rgba(212,175,55,.6);
        border-radius: 50%; animation: spin 1.8s linear infinite reverse;
    }
    @keyframes spin { to { transform: rotate(360deg); } }


    .overlay-title {
        font-size: 22px; font-weight: 800; margin: 0 0 12px;
        color: #fff;
    }
    .overlay-subtitle {
        font-size: 14px; color: rgba(255,255,255,.7);
        margin: 0 0 24px; line-height: 1.6;
    }
    .overlay-timer {
        display: inline-flex; align-items: baseline; gap: 8px;
        background: rgba(212,175,55,.12); padding: 12px 24px;
        border-radius: 14px; margin-bottom: 20px;
        border: 1px solid rgba(212,175,55,.25);
    }
    .overlay-timer .seconds {
        font-size: 32px; font-weight: 900; color: #d4af37;
        line-height: 1;
    }
    .overlay-timer .label {
        font-size: 13px; color: rgba(255,255,255,.6); font-weight: 500;
    }
    .overlay-progress {
        width: 100%; height: 6px; background: rgba(255,255,255,.1);
        border-radius: 3px; overflow: hidden; margin: 24px 0;
    }
    .overlay-progress .bar {
        height: 100%; width: 30%;
        background: linear-gradient(90deg, #d4af37, #f4d97d);
        border-radius: 3px; animation: slide 2s infinite ease-in-out;
    }
    @keyframes slide { 0%{transform:translateX(-100%)} 100%{transform:translateX(400%)} }
    .overlay-status {
        font-size: 12px; color: rgba(255,255,255,.5);
        margin: 0; font-family: monospace;
    }
    .overlay-retry {
        background: #d4af37; color: #1a1a2e; border: none;
        padding: 12px 32px; border-radius: 10px;
        font-weight: 800; font-size: 14px; margin-top: 20px;
        display: inline-flex; align-items: center; gap: 8px;
        transition: all .2s;
    }
    .overlay-retry:hover { background: #e5c04a; transform: translateY(-1px); }
</style>
@endpush


@section('content')
@php
    $tid = $majorTask['major_task_id'] ?? $majorTask['id'] ?? 0;
    $sm = $strategicMap ?? [];
    $goal        = $sm['goal'] ?? null;
    $program     = $sm['program'] ?? null;
    $initiative  = $sm['initiative'] ?? null;
    $mtCtx       = $sm['major_task'] ?? null;
    $departments = $sm['departments'] ?? [];
@endphp


<div class="container-fluid px-4 gen-page">


    <div class="page-head">
        <h3>
            <i class="fas fa-robot"></i>
            توليد المهام التشغيلية
        </h3>
        <span class="model-badge">
            <i class="fas fa-bolt"></i>
            Qwen 2.5
        </span>
    </div>


    <div class="steps">
        <div class="step-item active">
            <span class="num">1</span>
            السياق الاستراتيجي
        </div>
        <div class="step-item" id="step2">
            <span class="num">2</span>
            التعليمات والتوليد
        </div>
    </div>


    @if($tid <= 0)
        <div class="alert alert-danger rounded-3">تعذر تحديد رقم المهمة الرئيسية.</div>
    @endif


    <div class="row g-4">
        <div class="col-lg-8">
            <div class="strat-section">
                <h5>
                    <i class="fas fa-sitemap"></i>
                    الخريطة الاستراتيجية
                </h5>


                @if($goal || $program || $initiative || $mtCtx)
                    <div class="strat-chain">
                        @if($goal)
                            <div class="strat-node goal">
                                <span class="node-label">الهدف</span>
                                <h6 class="node-title">{{ $goal['title'] ?? '' }}</h6>
                                @if(!empty($goal['description']))
                                    <p class="node-desc">{{ \Illuminate\Support\Str::limit($goal['description'], 200) }}</p>
                                @endif
                                @if(!empty($goal['target_date']))
                                    <div class="node-meta">
                                        <span><i class="fas fa-calendar-alt"></i> {{ $goal['target_date'] }}</span>
                                    </div>
                                @endif
                            </div>
                        @endif


                        @if($program)
                            <div class="strat-node program">
                                <span class="node-label">البرنامج</span>
                                <h6 class="node-title">{{ $program['name'] ?? '' }}</h6>
                                @if(!empty($program['description']))
                                    <p class="node-desc">{{ \Illuminate\Support\Str::limit($program['description'], 200) }}</p>
                                @endif
                                @if(!empty($program['start_date']) || !empty($program['end_date']))
                                    <div class="node-meta">
                                        <span><i class="fas fa-calendar-alt"></i> {{ $program['start_date'] ?? '—' }} → {{ $program['end_date'] ?? '—' }}</span>
                                    </div>
                                @endif
                            </div>
                        @endif


                        @if($initiative)
                            <div class="strat-node initiative">
                                <span class="node-label">المبادرة</span>
                                <h6 class="node-title">{{ $initiative['name'] ?? '' }}</h6>
                                @if(!empty($initiative['description']))
                                    <p class="node-desc">{{ \Illuminate\Support\Str::limit($initiative['description'], 200) }}</p>
                                @endif
                                <div class="node-meta">
                                    <span><i class="fas fa-calendar-alt"></i> {{ $initiative['start_date'] ?? '—' }} → {{ $initiative['end_date'] ?? '—' }}</span>
                                </div>
                            </div>
                        @endif


                        @if($mtCtx || $majorTask)
                            @php
                                $mtName  = $mtCtx['name']        ?? $majorTask['name'] ?? '';
                                $mtDesc  = $mtCtx['description'] ?? $majorTask['description'] ?? '';
                                $mtDays  = $mtCtx['duration_days'] ?? $majorTask['estimated_duration_days'] ?? 0;
                                $mtCross = $mtCtx['is_cross_department'] ?? $majorTask['is_cross_department'] ?? false;
                            @endphp
                            <div class="strat-node major-task">
                                <span class="node-label">المهمة الرئيسية</span>
                                <h6 class="node-title">{{ $mtName }}</h6>
                                @if($mtDesc)
                                    <p class="node-desc">{{ \Illuminate\Support\Str::limit($mtDesc, 200) }}</p>
                                @endif
                                <div class="node-meta">
                                    <span><i class="fas fa-clock"></i> {{ $mtDays }} يوم</span>
                                    <span><i class="fas fa-building"></i> {{ count($departments) }} إدارة</span>
                                    @if($mtCross)
                                        <span style="color:#d4af37;"><i class="fas fa-share-alt"></i> مشتركة</span>
                                    @endif
                                </div>
                                @if(!empty($departments))
                                    <div class="dept-chips">
                                        @foreach($departments as $d)
                                            <span class="dept-chip {{ ($d['responsibility_type'] ?? '') == 'LEAD' ? 'lead' : 'support' }}">
                                                <i class="fas fa-{{ ($d['responsibility_type'] ?? '') == 'LEAD' ? 'star' : 'link' }}"></i>
                                                {{ $d['department_name'] ?? '' }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                @else
                    <div class="alert alert-warning mb-0 rounded-3">لم يتم العثور على خريطة استراتيجية لهذه المهمة.</div>
                @endif
            </div>


            <div class="form-section">
                <h5>
                    <i class="fas fa-comment-dots"></i>
                    تعليمات إضافية
                    <span class="text-muted" style="font-size:12px;font-weight:500;">(اختياري)</span>
                </h5>
                <form method="POST" action="{{ route('operational.start-generation') }}" id="generateForm">
                    @csrf
                    <input type="hidden" name="major_task_id" value="{{ $tid }}">
                    <textarea name="instructions" id="instructions" rows="3"
                        placeholder="مثال: ركز على المهام التقنية، أضف مهمة للتدقيق النهائي...">{{ old('instructions') }}</textarea>
                    <div class="mt-3">
                        <button type="submit" class="btn-generate" id="generateBtn" @disabled($tid <= 0)>
                            <i class="fas fa-robot"></i>
                            توليد المهام التشغيلية
                        </button>
                    </div>
                </form>
            </div>
        </div>


        <div class="col-lg-4">
            <div class="info-card">
                <h6>
                    <i class="fas fa-lightbulb"></i>
                    كيف يعمل النظام؟
                </h6>
                <ol>
                    <li>يقرأ النظام الخريطة الاستراتيجية الكاملة</li>
                    <li>يبني سياقًا موحّدًا للمهمة الرئيسية</li>
                    <li>يولّد مهام تشغيلية واقعية</li>
                    <li>تراجع وتعدّل وتعتمد</li>
                </ol>
            </div>
        </div>
    </div>
</div>


<div class="gen-overlay" id="genOverlay">
    <div class="overlay-panel">
        <div class="overlay-spinner">
            <div class="ring"></div>
        </div>
        <h2 class="overlay-title" id="genTitle">جاري توليد المهام</h2>
        <p class="overlay-subtitle" id="genMessage">
            يتم تحليل السياق الاستراتيجي والمهمة الرئيسية لتوليد مهام تشغيلية مناسبة
        </p>
        <div class="overlay-timer">
            <span class="seconds" id="genTimer">0</span>
            <span class="label">ثانية</span>
        </div>
        <div class="overlay-progress" id="genProgress">
            <div class="bar"></div>
        </div>
        <p class="overlay-status" id="genStatus">جاري التحقق من حالة التوليد...</p>
        <button type="button" class="overlay-retry d-none" id="genRetry" onclick="window.location.reload()">
            <i class="fas fa-redo"></i>
            إعادة المحاولة
        </button>
    </div>
</div>
@endsection


@push('scripts')
<script>
(function () {
    'use strict';


    const overlay   = document.getElementById('genOverlay');
    const timerEl   = document.getElementById('genTimer');
    const statusEl  = document.getElementById('genStatus');
    const messageEl = document.getElementById('genMessage');
    const titleEl   = document.getElementById('genTitle');
    const retryBtn  = document.getElementById('genRetry');
    const progress  = document.getElementById('genProgress');
    const spinner   = document.querySelector('.overlay-spinner');


    const form   = document.getElementById('generateForm');
    const btn    = document.getElementById('generateBtn');
    const step2  = document.getElementById('step2');


    const urlParams   = new URLSearchParams(window.location.search);
    const jobId       = urlParams.get('job_id');
    const majorTaskId = urlParams.get('major_task_id') || @json($tid);


    let seconds = 0, attempts = 0, timerHandle = null;
    const maxAttempts = 180;


    function startTimer() {
        seconds = 0;
        timerEl.textContent = '0';
        timerHandle = setInterval(function () { seconds++; timerEl.textContent = seconds; }, 1000);
    }
    function stopTimer() { if (timerHandle) { clearInterval(timerHandle); timerHandle = null; } }


    function showOverlay() {
        overlay.classList.add('show');
        document.body.style.overflow = 'hidden';
        startTimer();
    }


    function showError(msg) {
        stopTimer();
        overlay.classList.add('show', 'error');
        titleEl.textContent = 'فشل توليد المهام';
        messageEl.textContent = msg || 'حدث خطأ غير متوقع.';
        statusEl.textContent = '';
        spinner.style.display = 'none';
        progress.style.display = 'none';
        retryBtn.classList.remove('d-none');
    }


    async function pollJob() {
        attempts++;
        if (attempts > maxAttempts) {
            showError('انتهت مدة الانتظار. يرجى إعادة المحاولة.');
            return;
        }
        try {
            const res = await fetch('/operational/job/' + encodeURIComponent(jobId), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                cache: 'no-store',
                credentials: 'same-origin'
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const data = await res.json();
            if (!data.success || !data.data) throw new Error('Invalid response');


            const job = data.data;
            statusEl.textContent = 'الحالة: ' + job.status;


            if (job.status === 'review') {
                stopTimer();
                statusEl.textContent = 'اكتمل التوليد — جاري فتح المراجعة...';
                setTimeout(function () {
                    window.location.href = '/operational/review'
                        + '?job_id=' + encodeURIComponent(jobId)
                        + '&major_task_id=' + encodeURIComponent(majorTaskId);
                }, 400);
                return;
            }


            if (job.status === 'failed') {
                showError((job.result && job.result.error) || 'فشل التوليد.');
                return;
            }


            setTimeout(pollJob, 2000);
        } catch (err) {
            console.error('Poll error', err);
            statusEl.textContent = 'تعذر الاتصال، إعادة المحاولة...';
            setTimeout(pollJob, 3000);
        }
    }


    if (jobId) {
        showOverlay();
        pollJob();
    }


    if (form && btn) {
        form.addEventListener('submit', function () {
            if (btn.disabled) return;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري التوليد...';
            if (step2) step2.classList.add('active');
            showOverlay();
        });
    }
})();
</script>
@endpush

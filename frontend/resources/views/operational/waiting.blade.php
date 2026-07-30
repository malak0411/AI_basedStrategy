@extends('layouts.app')

@section('title', 'جاري توليد المهام التشغيلية')

@section('content')
<div class="container-fluid px-4">
    <div class="text-center py-5">
        <div class="mb-4">
            <div class="spinner-border text-warning" role="status" style="width: 4rem; height: 4rem;">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
        <h3>جاري توليد المهام التشغيلية</h3>
        <p class="text-muted">يتم تحليل المهمة الرئيسية وتوليد المهام التشغيلية باستخدام الذكاء الاصطناعي</p>
        <p class="text-muted small">قد يستغرق هذا دقيقة إلى دقيقتين</p>

        <div class="progress mt-4" style="width: 300px; margin: 0 auto;">
            <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>
        </div>

        <p class="mt-4">
            <span id="timer">0</span> ثانية
        </p>
    </div>
</div>
@endsection

@push('scripts')
<script>
let seconds = 0;
const timer = document.getElementById('timer');
const jobId = '{{ $jobId }}';
const majorTaskId = '{{ $majorTaskId }}';

setInterval(() => {
    seconds++;
    timer.textContent = seconds;
}, 1000);

function checkStatus() {
    fetch('/api/ai/jobs/' + jobId)
        .then(r => r.json())
        .then(data => {
            if (data.success && data.data) {
                const status = data.data.status;
                if (status === 'review') {
                    window.location.href = '/operational/review?job_id=' + jobId + '&major_task_id=' + majorTaskId;
                } else if (status === 'failed') {
                    alert('فشل توليد المهام التشغيلية');
                    window.location.href = '/operational/major-tasks';
                }
            }
        })
        .catch(err => console.error('Error:', err));
}

setInterval(checkStatus, 3000);
checkStatus();
</script>
@endpush

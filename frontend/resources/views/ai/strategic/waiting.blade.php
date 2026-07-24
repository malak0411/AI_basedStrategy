@extends('layouts.app')

@section('title', ' جاري توليد المهام الرئيسية')

@section('content')
<div class="container-fluid px-4">
    <div class="text-center py-5">
        <div class="mb-4">
            <div class="spinner-border text-warning" role="status" style="width: 4rem; height: 4rem;">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
        <h3>توليد المهام الرئيسية</h3>
        <p class="text-muted">تحليل المبادرة وتوليد المهام باستخدام الذكاء الاصطناعي</p>
        <p class="text-muted small">قد يستغرق هذا 1-2 دقيقة</p>

        <div class="progress mt-4" style="width: 300px; margin: 0 auto;">
            <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>
        </div>

        <p class="mt-4">
            <span id="timer">0</span> ثوانٍ
        </p>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let seconds = 0;
    const timer = document.getElementById('timer');
    const jobId = '{{ $jobId }}';
    const initiativeId = '{{ $initiativeId }}';

    setInterval(() => {
        seconds++;
        timer.textContent = seconds;
    }, 1000);

    function checkStatus() {
        fetch('/api/ai/jobs/' + jobId)
            .then(r => r.json())
            .then(data => {
                if (data.success && data.data) {
                    if (data.data.status === 'review') {
                        window.location.href = '/ai/strategic/review?job_id=' + jobId + '&initiative_id=' + initiativeId;
                    } else if (data.data.status === 'failed') {
                        alert('Generation failed');
                        window.location.href = '/ai/strategic';
                    }
                }
            })
            .catch(err => console.error('Error checking status:', err));
    }

    setInterval(checkStatus, 3000);
    checkStatus();
</script>
@endpush

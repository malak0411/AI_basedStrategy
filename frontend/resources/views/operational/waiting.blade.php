@extends('layouts.app')


@section('title', 'جاري توليد المهام التشغيلية')


@section('content')


<div class="container-fluid px-4">
    <div class="text-center py-5">
        <div class="mb-4">
            <div class="spinner-border text-warning" role="status" style="width: 4rem; height: 4rem;">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>    <h3>جاري توليد المهام التشغيلية</h3>


    <p class="text-muted">
        يتم تحليل المهمة الرئيسية وتوليد المهام التشغيلية باستخدام الذكاء الاصطناعي
    </p>


    <p class="text-muted small">
        قد يستغرق هذا دقيقة إلى دقيقتين
    </p>


    <div class="progress mt-4" style="width: 300px; margin: 0 auto;">
        <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%;"></div>
    </div>


    <p class="mt-4">
        <span id="timer">0</span> ثانية
    </p>


    <p id="statusMessage" class="text-muted mt-2">
        جاري التحقق من حالة التوليد...
    </p>
</div>


</div>
@endsection
@push('scripts')


<script>
let seconds = 0;
let attempts = 0;
const maxAttempts = 180;


const timer = document.getElementById('timer');
const statusMessage = document.getElementById('statusMessage');


const jobId = @json($jobId);
const majorTaskId = @json($majorTaskId);


setInterval(() => {
    seconds++;
    timer.textContent = seconds;
}, 1000);


async function checkStatus() {
    attempts++;


    if (attempts > maxAttempts) {
        statusMessage.textContent = 'انتهت مدة الانتظار. يرجى المحاولة مرة أخرى.';
        return;
    }


    try {
        const response = await fetch(
            @json(route('operational.job', ['job_id' => $jobId])),
            {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                cache: 'no-store'
            }
        );


        if (!response.ok) {
            throw new Error('HTTP ' + response.status);
        }


        const data = await response.json();


        console.log('Operational Job Response:', data);


        if (!data.success || !data.data) {
            throw new Error('Invalid job response');
        }


        const status = data.data.status;


        console.log('Operational Job Status:', status);


        statusMessage.textContent = 'حالة التوليد: ' + status;


        if (status === 'review') {
            window.location.href =
                @json(url('/operational/review')) +
                '?job_id=' + encodeURIComponent(jobId) +
                '&major_task_id=' + encodeURIComponent(majorTaskId);


            return;
        }


        if (status === 'failed') {
            let errorMessage = 'فشل توليد المهام التشغيلية.';


            if (
                data.data.result &&
                data.data.result.error
            ) {
                errorMessage = data.data.result.error;
            }


            statusMessage.textContent = errorMessage;
            return;
        }


        setTimeout(checkStatus, 2000);


    } catch (error) {
        console.error('Operational Job Status Error:', error);


        statusMessage.textContent =
            'تعذر التحقق من حالة التوليد، سيتم إعادة المحاولة...';


        setTimeout(checkStatus, 3000);
    }
}


checkStatus();
</script>
@endpush

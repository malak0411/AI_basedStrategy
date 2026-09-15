@extends('layouts.app')


@section('title', 'مراجعة المهام التشغيلية')


@section('content')
<div class="container-fluid py-4">


    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">مراجعة المهام التشغيلية</h2>
            <p class="text-muted mb-0">
                مراجعة المهام التي تم توليدها قبل اعتمادها
            </p>
        </div>


        <a
            href="{{ url()->previous() }}"
            class="btn btn-outline-secondary"
        >
            العودة
        </a>
    </div>


    @if(session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif


    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif


    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white border-0 py-3">
            <h5 class="fw-bold mb-0">
                معلومات التوليد
            </h5>
        </div>


        <div class="card-body">


            <div class="row g-4">


                <div class="col-md-8">
                    <label class="form-label fw-semibold">
                        الإدارة
                    </label>


                    <div class="form-control bg-light">
                        {{ $department['name'] ?? 'الإدارة الحالية للمدير' }}
                    </div>
                </div>


                <div class="col-md-4">
                    <label class="form-label fw-semibold">
                        عدد المهام
                    </label>


                    <div class="form-control bg-light">
                        {{ count($tasks) }}
                    </div>
                </div>


            </div>


        </div>
    </div>


    @if(count($tasks) > 0)


        <div class="mb-4">
            <h5 class="fw-bold">
                المهام التشغيلية المقترحة
            </h5>
        </div>


        @foreach($tasks as $index => $task)


            <div class="card shadow-sm border-0 mb-4">


                <div class="card-header bg-white border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">


                        <h5 class="fw-bold mb-0">
                            المهمة التشغيلية {{ $index + 1 }}
                        </h5>


                        <span class="badge bg-primary">
                            {{ $department['name'] ?? 'الإدارة الحالية' }}
                        </span>


                    </div>
                </div>


                <div class="card-body">


                    <div class="mb-4">
                        <label class="form-label fw-semibold">
                            عنوان المهمة
                        </label>


                        <input
                            type="text"
                            class="form-control"
                            value="{{ $task['title'] ?? '' }}"
                            readonly
                        >
                    </div>


                    <div class="mb-4">
                        <label class="form-label fw-semibold">
                            وصف المهمة
                        </label>


                        <textarea
                            class="form-control"
                            rows="4"
                            readonly
                        >{{ $task['description'] ?? '' }}</textarea>
                    </div>


                    <div class="row g-3">


                        <div class="col-md-3">
                            <label class="form-label fw-semibold">
                                الأولوية
                            </label>


                            <input
                                type="text"
                                class="form-control"
                                value="{{ $task['priority_id'] ?? 'غير محدد' }}"
                                readonly
                            >
                        </div>


                        <div class="col-md-3">
                            <label class="form-label fw-semibold">
                                الساعات المتوقعة
                            </label>


                            <input
                                type="text"
                                class="form-control"
                                value="{{ $task['estimated_hours'] ?? 'غير محدد' }}"
                                readonly
                            >
                        </div>


                        <div class="col-md-3">
                            <label class="form-label fw-semibold">
                                تاريخ البداية
                            </label>


                            <input
                                type="text"
                                class="form-control"
                                value="{{ $task['start_date'] ?? 'غير محدد' }}"
                                readonly
                            >
                        </div>


                        <div class="col-md-3">
                            <label class="form-label fw-semibold">
                                تاريخ النهاية
                            </label>


                            <input
                                type="text"
                                class="form-control"
                                value="{{ $task['end_date'] ?? 'غير محدد' }}"
                                readonly
                            >
                        </div>


                    </div>


                    <div class="mt-4">
                        <label class="form-label fw-semibold">
                            الإدارة المسؤولة
                        </label>


                        <input
                            type="text"
                            class="form-control bg-light"
                            value="{{ $department['name'] ?? 'غير محدد' }}"
                            readonly
                        >
                    </div>


                </div>


            </div>


        @endforeach


        <div class="card shadow-sm border-0 mt-4">
            <div class="card-body">


                <div class="alert alert-warning mb-4">
                    بعد الاعتماد سيتم حفظ المهام التشغيلية ضمن إدارة المدير الحالي.
                    توزيع المهام على الموظفين يتم في مرحلة لاحقة.
                </div>


                <div class="d-flex justify-content-end gap-2">


                    <a
                        href="{{ url()->previous() }}"
                        class="btn btn-outline-secondary px-4"
                    >
                        العودة
                    </a>


                    <form
                        method="POST"
                        action="{{ route('operational.approve') }}"
                    >
                        @csrf


                        <input
                            type="hidden"
                            name="job_id"
                            value="{{ $job['job_id'] ?? '' }}"
                        >


                        <button
                            type="submit"
                            class="btn btn-success px-5"
                        >
                            اعتماد المهام التشغيلية
                        </button>
                    </form>


                </div>


            </div>
        </div>


    @else


        <div class="card shadow-sm border-0">
            <div class="card-body text-center py-5">


                <h5 class="fw-bold mb-2">
                    لا توجد مهام تشغيلية
                </h5>


                <p class="text-muted mb-4">
                    لم يتم إنشاء مهام تشغيلية للمهمة الرئيسية.
                </p>


                <a
                    href="{{ url()->previous() }}"
                    class="btn btn-primary px-4"
                >
                    العودة
                </a>


            </div>
        </div>


    @endif


</div>
@endsection

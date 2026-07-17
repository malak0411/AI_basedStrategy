@extends('layouts.app')

@section('title', 'مراجعة المهام المولدة')

@push('styles')
<style>
    .task-review-card {
        background: #fff;
        border-radius: 16px;
        padding: 24px;
        margin-bottom: 16px;
        border: 1px solid #e2e8f0;
        transition: all 0.3s ease;
    }
    .task-review-card:hover { box-shadow: 0 4px 15px rgba(0,0,0,0.06); }
    .task-number {
        width: 36px; height: 36px; border-radius: 50%;
        background: #1a4a8a; color: #d4af37;
        display: flex; align-items: center; justify-content: center;
        font-weight: 800; font-size: 16px;
    }
    .dept-badge {
        display: inline-block; padding: 4px 10px; border-radius: 20px;
        font-size: 11px; margin: 2px;
    }
    .dept-lead { background: #d4af37; color: #1a1a2e; }
    .dept-support { background: #e2e8f0; color: #4a5568; }
    .deliverable-tag {
        display: inline-block; background: #38a169; color: #fff;
        padding: 3px 10px; border-radius: 12px; font-size: 11px; margin: 2px;
    }
    .prompt-edit-box {
        background: #f8fafc;
        border: 2px dashed #d4af37;
        border-radius: 12px;
        padding: 16px;
        margin-bottom: 20px;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    {{-- رأس الصفحة --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3><i class="fas fa-check-circle ml-2 text-success"></i>مراجعة المهام الرئيسية</h3>
            <p class="text-muted mb-0">
                المبادرة: <strong>{{ $initiative['name'] ?? $initiative['title'] ?? '' }}</strong>
                | Job #{{ $jobId }}
            </p>
        </div>
        <span class="badge bg-warning fs-6">⏳ بانتظار الاعتماد</span>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- مربع التعديل بالـ Prompt --}}
    <div class="prompt-edit-box">
        <h6><i class="fas fa-robot ml-2"></i>تعديل الخطة باستخدام الذكاء الاصطناعي</h6>
        <div class="row">
            <div class="col-md-9">
                <input type="text" id="editInstruction" class="form-control" 
                    placeholder="مثال: اجعل المهمة الأولى مدتها 30 يوم وأضف إدارة التخطيط كشريك...">
            </div>
            <div class="col-md-3">
                <button class="btn btn-info w-100" onclick="editWithPrompt()" id="editBtn">
                    <i class="fas fa-robot"></i> تعديل بـ AI
                </button>
            </div>
        </div>
        <small class="text-muted mt-2 d-block">
            <i class="fas fa-lightbulb ml-1"></i> يمكنك كتابة أي تعديل باللغة العربية وسيقوم Gemini بتعديل الخطة
        </small>
    </div>

    {{-- قائمة المهام --}}
    <div id="tasksContainer">
        @forelse($tasks as $index => $task)
        <div class="task-review-card" id="task-{{ $index }}">
            <div class="d-flex gap-3">
                <div class="task-number">{{ $index + 1 }}</div>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-start">
                        <h5 class="mb-2">{{ $task['name'] ?? '' }}</h5>
                        <span class="badge bg-{{ ($task['priority'] ?? '') == 'High' ? 'danger' : (($task['priority'] ?? '') == 'Medium' ? 'warning' : 'info') }}">
                            {{ $task['priority'] ?? 'Medium' }}
                        </span>
                    </div>
                    <p class="text-muted small mb-3">{{ $task['description'] ?? '' }}</p>

                    <div class="row mb-2">
                        <div class="col-md-4">
                            <small class="text-muted">⏱️ المدة:</small>
                            <strong>{{ $task['estimated_duration_days'] ?? 0 }} يوم</strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted">🔄 مشتركة:</small>
                            <strong>{{ ($task['is_cross_department'] ?? false) ? 'نعم' : 'لا' }}</strong>
                        </div>
                    </div>

                    {{-- الإدارات --}}
                    @if(!empty($task['departments']))
                    <div class="mb-2">
                        <small class="text-muted">🏢 الإدارات:</small>
                        @foreach($task['departments'] as $dept)
                            <span class="dept-badge {{ ($dept['responsibility_type'] ?? '') == 'LEAD' ? 'dept-lead' : 'dept-support' }}">
                                {{ ($dept['responsibility_type'] ?? '') == 'LEAD' ? '👑' : '🤝' }}
                                #{{ $dept['department_id'] ?? '' }}
                                @if(!empty($dept['notes']))
                                    <small>({{ $dept['notes'] }})</small>
                                @endif
                            </span>
                        @endforeach
                    </div>
                    @endif

                    {{-- المخرجات --}}
                    @if(!empty($task['deliverables']))
                    <div>
                        <small class="text-muted">📦 المخرجات:</small>
                        @foreach($task['deliverables'] as $d)
                            <span class="deliverable-tag">{{ $d }}</span>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="card-custom text-center py-5">
            <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
            <h5>لا توجد مهام</h5>
            <p class="text-muted">لم يتم توليد مهام بعد</p>
        </div>
        @endforelse
    </div>

    {{-- زر الاعتماد --}}
    @if(!empty($tasks))
    <div class="text-center mt-4">
        <form method="POST" action="{{ route('ai.strategic.approve') }}" style="display:inline;">
            @csrf
            <input type="hidden" name="job_id" value="{{ $jobId }}">
            <input type="hidden" name="initiative_id" value="{{ $initiativeId }}">
            <button type="submit" class="btn-gold btn-lg" id="approveBtn">
                <i class="fas fa-check-circle"></i> Approve Plan - اعتماد الخطة وحفظها
            </button>
        </form>
        <a href="{{ route('ai.strategic.index') }}" class="btn btn-outline-secondary btn-lg mr-3">
            <i class="fas fa-times"></i> إلغاء
        </a>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    async function editWithPrompt() {
        const instruction = document.getElementById('editInstruction').value;
        if (!instruction) {
            alert('الرجاء إدخال تعليمات التعديل');
            return;
        }

        const btn = document.getElementById('editBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري التعديل...';

        try {
            const response = await fetch('{{ route("ai.strategic.edit-plan") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    job_id: '{{ $jobId }}',
                    instruction: instruction
                })
            });

            const data = await response.json();
            if (data.major_tasks) {
                // إعادة تحميل الصفحة لعرض التعديلات
                window.location.reload();
            } else {
                alert('فشل التعديل: ' + (data.error || 'خطأ غير معروف'));
            }
        } catch (error) {
            alert('❌ خطأ في الاتصال');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-robot"></i> تعديل بـ AI';
        }
    }

    // تأكيد قبل الاعتماد
    document.getElementById('approveBtn')?.addEventListener('click', function(e) {
        if (!confirm('هل أنت متأكد من اعتماد وحفظ جميع المهام الرئيسية؟')) {
            e.preventDefault();
        }
    });
</script>
@endpush

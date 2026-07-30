@extends('layouts.app')

@section('title', 'توليد المهام التشغيلية')

@push('styles')
<style>
    .step-indicator { display: flex; gap: 8px; margin-bottom: 24px; }
    .step { flex: 1; text-align: center; padding: 12px; border-radius: 8px; background: #f0f0f0; font-size: 13px; font-weight: 600; }
    .step.active { background: #1a4a8a; color: #fff; }
    .flow-item { padding: 10px 16px; background: #f8fafc; border-radius: 8px; border-right: 4px solid #d4af37; font-size: 14px; margin-bottom: 6px; }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3><i class="fas fa-robot ml-2"></i>توليد المهام التشغيلية</h3>
        <span class="badge bg-info">Qwen 2.5</span>
    </div>

    <div class="step-indicator">
        <div class="step active">1. المهمة الرئيسية</div>
        <div class="step" id="step2">2. إضافة تعليمات</div>
    </div>

    @php $tid = $majorTask['id'] ?? 0; @endphp

    <div class="row">
        <div class="col-lg-8">
            <div class="card-custom mb-4">
                <h5>المهمة الرئيسية المختارة</h5>
                <div class="flow-item mt-3">
                    <strong>{{ $majorTask['name'] ?? '' }}</strong>
                    <p class="text-muted small mb-0">{{ Str::limit($majorTask['description'] ?? '', 200) }}</p>
                    <small>المدة: {{ $majorTask['estimated_duration_days'] ?? 0 }} يوم</small>
                </div>
            </div>

            <div class="card-custom mb-4">
                <h5>تعليمات إضافية (اختياري)</h5>
                <form method="POST" action="{{ route('operational.start-generation') }}" id="generateForm">
                    @csrf
                    <input type="hidden" name="major_task_id" value="{{ $tid }}">
                    <textarea name="instructions" class="form-control" rows="3" placeholder="مثال: ركز على المهام التقنية، أضف مهمة للتدقيق..."></textarea>
                    <button type="submit" class="btn-gold btn-lg mt-3" id="generateBtn">
                        <i class="fas fa-robot"></i> Generate Operational Tasks
                    </button>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card-custom">
                <h6>كيف يعمل؟</h6>
                <ol class="small text-muted mt-2">
                    <li>اختر المهمة الرئيسية</li>
                    <li>أضف تعليمات اختيارية</li>
                    <li>الذكاء الاصطناعي يولد المهام التشغيلية</li>
                    <li>تراجع وتعدل وتعتمد</li>
                </ol>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('generateForm').addEventListener('submit', function() {
    const btn = document.getElementById('generateBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري التوليد... قد يستغرق دقيقة';
});
</script>
@endpush

@extends('layouts.app')

@section('title', 'توليد المهام الرئيسية بالذكاء الاصطناعي')

@push('styles')
<style>
    .step-indicator { display: flex; gap: 8px; margin-bottom: 24px; }
    .step { flex: 1; text-align: center; padding: 12px; border-radius: 8px; background: #f0f0f0; font-size: 13px; font-weight: 600; }
    .step.active { background: #1a4a8a; color: #fff; }
    .step.completed { background: #38a169; color: #fff; }
    .strategy-flow { display: flex; flex-direction: column; gap: 6px; }
    .flow-item { padding: 10px 16px; background: #f8fafc; border-radius: 8px; border-right: 4px solid #d4af37; font-size: 14px; }
    .context-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .context-mini { background: #fff; border-radius: 10px; padding: 14px; border: 1px solid #e2e8f0; }
    .context-mini h6 { font-size: 13px; font-weight: 700; color: #1a4a8a; margin-bottom: 6px; }
    .context-mini p { font-size: 12px; color: #666; margin: 0; line-height: 1.6; }
    .loading-pulse { animation: pulse 1.5s infinite; }
    @keyframes pulse { 0%,100% { opacity:1; } 50% { opacity:0.5; } }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3><i class="fas fa-robot ml-2"></i>توليد المهام الرئيسية بالذكاء الاصطناعي</h3>
        <span class="badge bg-info"><i class="fas fa-brain"></i> ollama </span>
    </div>

    {{-- خطوات المؤشر --}}
    <div class="step-indicator">
        <div class="step active" id="step1"> اختيار المبادرة</div>
        <div class="step" id="step2"> مراجعة السياق</div>
        <div class="step" id="step3"> إضافة تعليمات</div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            {{-- المرحلة 1: اختيار المبادرة --}}
            <div class="card-custom mb-4">
                <h5 class="mb-3"><i class="fas fa-hand-pointer ml-2 text-primary"></i>اختر المبادرة</h5>
                <select id="initiativeSelect" class="form-control form-control-lg">
                    <option value="">-- اختر المبادرة من القائمة --</option>
                    @foreach($initiatives as $init)
                        <option value="{{ $init['id'] }}">{{ $init['name'] ?? $init['title'] ?? '' }}</option>
                    @endforeach
                </select>
            </div>

            {{-- المرحلة 2: السياق --}}
            <div id="contextSection" style="display:none;">
                <div class="card-custom mb-4">
                    <h5 class="mb-3"><i class="fas fa-sitemap ml-2 text-info"></i>السياق الاستراتيجي</h5>
                    <div id="contextLoading" class="text-center py-4">
                        <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                        <p class="text-muted mt-2 loading-pulse">جاري تحليل السياق الاستراتيجي...</p>
                    </div>
                    <div id="contextData"></div>
                </div>

                {{-- المرحلة 3: التعليمات --}}
                <div class="card-custom mb-4">
                    <h5 class="mb-3"><i class="fas fa-pen ml-2 text-warning"></i>تعليمات إضافية (اختياري)</h5>
                    <form method="POST" action="{{ route('ai.strategic.generate') }}" id="generateForm">
                        @csrf
                        <input type="hidden" name="initiative_id" id="hiddenInitiativeId">
                        <textarea name="instructions" class="form-control" rows="3" 
                            placeholder="مثال: ركز على الجانب التقني... قلل مدة التنفيذ... أضف إدارة القانونية..."></textarea>
                        <button type="submit" class="btn-gold btn-lg mt-3" id="generateBtn">
                            <i class="fas fa-robot"></i> Generate Tasks With AI
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card-custom">
                <h6><i class="fas fa-info-circle ml-2"></i>كيف يعمل؟</h6>
                <ol class="small text-muted mt-2">
                    <li>اختر المبادرة من القائمة</li>
                    <li>يراجع النظام الرؤية والركائز والأهداف والبرنامج</li>
                    <li>يحلل SWOT و PESTEL والميزانية والمخاطر</li>
                    <li>أضف تعليمات اختيارية</li>
                    <li يولد المهام الرئيسية</li>
                    <li>تراجع وتعدل وتعتمد الخطة</li>
                </ol>
                <hr>
                <small class="text-muted">
                    <i class="fas fa-shield-alt ml-1"></i> البيانات لا تحفظ مباشرة - يجب اعتمادها أولاً
                </small>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.getElementById('initiativeSelect').addEventListener('change', async function() {
        const id = this.value;
        if (!id) return;

        // إظهار قسم السياق
        document.getElementById('contextSection').style.display = 'block';
        document.getElementById('contextLoading').style.display = 'block';
        document.getElementById('contextData').innerHTML = '';
        document.getElementById('hiddenInitiativeId').value = id;
        document.getElementById('step2').classList.add('active');

        try {
            const response = await fetch(`/ai/strategic/context/${id}`);
            const data = await response.json();

            document.getElementById('contextLoading').style.display = 'none';
            document.getElementById('step3').classList.add('active');

            let html = '<div class="strategy-flow mb-3">';
            
            // الرؤية
            if (data.vision?.text) {
                html += `<div class="flow-item">👁️ <strong>الرؤية:</strong> ${data.vision.text.substring(0, 150)}${data.vision.text.length > 150 ? '...' : ''}</div>`;
            }
            
            // المبادرة
            if (data.initiative?.name) {
                html += `<div class="flow-item"> <strong>المبادرة:</strong> ${data.initiative.name}</div>`;
                if (data.initiative.description) {
                    html += `<div class="flow-item"> ${data.initiative.description.substring(0, 200)}</div>`;
                }
                if (data.initiative.budget_estimate) {
                    html += `<div class="flow-item"> الميزانية: ${Number(data.initiative.budget_estimate).toLocaleString()}</div>`;
                }
                if (data.initiative.start_date) {
                    html += `<div class="flow-item"> ${data.initiative.start_date} → ${data.initiative.end_date || 'غير محدد'}</div>`;
                }
            }
            
            html += '</div>';

            // SWOT و PESTEL
            if (data.swot || data.pestel) {
                html += '<div class="context-grid">';
                
                if (data.swot?.strengths) {
                    html += `<div class="context-mini"><h6> نقاط القوة</h6><p>${data.swot.strengths.substring(0, 150)}</p></div>`;
                }
                if (data.swot?.weaknesses) {
                    html += `<div class="context-mini"><h6> نقاط الضعف</h6><p>${data.swot.weaknesses.substring(0, 150)}</p></div>`;
                }
                if (data.swot?.opportunities) {
                    html += `<div class="context-mini"><h6> الفرص</h6><p>${data.swot.opportunities.substring(0, 150)}</p></div>`;
                }
                if (data.swot?.threats) {
                    html += `<div class="context-mini"><h6> التهديدات</h6><p>${data.swot.threats.substring(0, 150)}</p></div>`;
                }
                
                html += '</div>';
            }

            document.getElementById('contextData').innerHTML = html;
        } catch (error) {
            document.getElementById('contextLoading').innerHTML = 
                '<p class="text-danger"> خطأ في تحميل السياق</p>';
        }
    });

    // إظهار تحميل عند الضغط على Generate
    document.getElementById('generateForm').addEventListener('submit', function() {
        const btn = document.getElementById('generateBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري التوليد... قد يستغرق 10-30 ثانية';
    });
</script>
@endpush

@extends('layouts.app')

@section('title', 'تفاصيل الركيزة')

@push('styles')
<style>
    .goal-card {
        background: #fff; border-radius: 12px; padding: 16px; cursor: pointer;
        border: 1px solid #e2e8f0; transition: all 0.2s; border-right: 4px solid #3182ce;
        margin-bottom: 12px;
    }
    .goal-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.06); }
    .goal-card.inactive { opacity: 0.5; border-right-color: #ccc; }
    .pillar-info { background: #fff; border-radius: 16px; padding: 24px; border: 1px solid #e2e8f0; margin-bottom: 24px; }
    .text-danger { font-size: 12px; display: none; }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <a href="{{ route('strategic.pillars.index') }}" class="btn btn-outline-secondary mb-3">
        <i class="fas fa-arrow-right"></i> العودة للركائز
    </a>

    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif

    @php
        $pillarId = $pillar['id'] ?? 0;
        $pillarName = $pillar['name'] ?? '';
        $pillarDesc = $pillar['description'] ?? '';
        $isActive = $pillar['is_active'] ?? true;
        $goals = $pillar['goals'] ?? [];
        $createdAt = $pillar['created_at'] ?? date('Y-m-d');
    @endphp

    <div class="pillar-info">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <h4>{{ $pillarName }}</h4>
                <p class="text-muted">{{ $pillarDesc }}</p>
            </div>
            <span class="badge bg-{{ $isActive ? 'success' : 'secondary' }} fs-6">
                {{ $isActive ? 'نشط' : 'غير نشط' }}
            </span>
        </div>
        <div class="row mt-3">
            <div class="col-md-3">
                <small class="text-muted">الترتيب</small>
                <div><strong>{{ $pillar['order_index'] ?? 0 }}</strong></div>
            </div>
            <div class="col-md-3">
                <small class="text-muted">تاريخ الإنشاء</small>
                <div><strong>{{ $createdAt }}</strong></div>
            </div>
        </div>
    </div>
    <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-3">
                    <i class="fas fa-bullseye ml-2"></i>الأهداف المرتبطة ({{ count($goals) }})
                </h5>
                <div class="col-md-6">
                <button class="btn-gold btn-sm float-start" data-bs-toggle="modal" data-bs-target="#addGoalModal">
                    <i class="fas fa-plus"></i> إضافة هدف جديد
                </button>
                
    </div>
            </div>
    
    
    @if(empty($goals))
    <div class="card-custom text-center py-5">
        <i class="fas fa-bullseye fa-3x text-muted mb-3"></i>
        <h5>لا توجد أهداف مرتبطة بهذه الركيزة</h5>
        <button class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#addGoalModal">
            <i class="fas fa-plus"></i> إضافة أول هدف
        </button>
    </div>
    @else
    @foreach($goals as $goal)
    @php $gActive = $goal['is_active'] ?? true; @endphp
    <div class="goal-card {{ $gActive ? '' : 'inactive' }}" onclick="window.location='{{ route('strategic.goals.show', $goal['id']) }}'">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <h6>{{ $goal['title'] ?? '' }}</h6>
                <p class="text-muted small mb-0">{{ Str::limit($goal['description'] ?? '', 100) }}</p>
            </div>
            <span class="badge bg-{{ $gActive ? 'success' : 'secondary' }}">{{ $gActive ? 'نشط' : 'غير نشط' }}</span>
        </div>
        @if(!empty($goal['target_date']))
        <small class="text-muted mt-2 d-block">التاريخ المستهدف: {{ $goal['target_date'] }}</small>
        @endif
        @if(!empty($goal['valid_from']))
        <small class="text-muted d-block">تاريخ الصلاحية من: {{ $goal['valid_from'] }} إلى: {{ $goal['valid_until'] ?? 'غير محدد' }}</small>
        @endif
    </div>
    @endforeach
    @endif

    {{-- Modal إضافة هدف --}}
    <div class="modal fade" id="addGoalModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-plus-circle ml-2"></i>إضافة هدف استراتيجي</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('strategic.goals.store') }}" onsubmit="return validateGoalForm()">
                    @csrf
                    <input type="hidden" name="pillar_id" value="{{ $pillarId }}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">عنوان الهدف <span class="text-danger">*</span></label>
                            <input type="text" name="title" id="goalTitle" class="form-control" required maxlength="255">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">الوصف</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">تاريخ البداية (الصلاحية)</label>
                                <input type="date" name="valid_from" id="validFrom" class="form-control" onchange="validateGoalDates()">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">تاريخ النهاية (الصلاحية)</label>
                                <input type="date" name="valid_until" id="validUntil" class="form-control" onchange="validateGoalDates()">
                                <small class="text-danger" id="validUntilError">يجب أن يكون تاريخ النهاية بعد تاريخ البداية</small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">التاريخ المستهدف</label>
                                <input type="date" name="target_date" id="targetDate" class="form-control" onchange="validateGoalDates()">
                                <small class="text-danger" id="targetDateError">يجب أن يكون التاريخ المستهدف ضمن فترة الصلاحية</small>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">الوزن</label>
                                <input type="number" name="weight" class="form-control" value="1" min="1" max="10">
                            </div>
                        </div>
                        <small class="text-muted">
                            <i class="fas fa-info-circle ml-1"></i> 
                            جميع التواريخ يجب أن تكون بعد تاريخ إنشاء الركيزة ({{ $createdAt }})
                        </small>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-primary">إضافة الهدف</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const createdAt = '{{ $createdAt }}';

    function validateGoalDates() {
        const validFrom = document.getElementById('validFrom').value;
        const validUntil = document.getElementById('validUntil').value;
        const targetDate = document.getElementById('targetDate').value;
        const validUntilError = document.getElementById('validUntilError');
        const targetDateError = document.getElementById('targetDateError');
        
        let isValid = true;
        
        validUntilError.style.display = 'none';
        targetDateError.style.display = 'none';
        
        if (validFrom && validUntil) {
            if (validUntil <= validFrom) {
                validUntilError.textContent = 'يجب أن يكون تاريخ النهاية بعد تاريخ البداية';
                validUntilError.style.display = 'block';
                isValid = false;
            }
        }
        
        if (targetDate && validFrom && validUntil) {
            if (targetDate < validFrom || targetDate > validUntil) {
                targetDateError.textContent = 'يجب أن يكون التاريخ المستهدف بين ' + validFrom + ' و ' + validUntil;
                targetDateError.style.display = 'block';
                isValid = false;
            }
        }
        
        if (validFrom && validFrom < createdAt) {
            validUntilError.textContent = 'تاريخ البداية يجب أن يكون بعد تاريخ إنشاء الركيزة (' + createdAt + ')';
            validUntilError.style.display = 'block';
            isValid = false;
        }
        
        if (validUntil && validUntil < createdAt) {
            validUntilError.textContent = 'تاريخ النهاية يجب أن يكون بعد تاريخ إنشاء الركيزة (' + createdAt + ')';
            validUntilError.style.display = 'block';
            isValid = false;
        }
        
        if (targetDate && targetDate < createdAt) {
            targetDateError.textContent = 'التاريخ المستهدف يجب أن يكون بعد تاريخ إنشاء الركيزة (' + createdAt + ')';
            targetDateError.style.display = 'block';
            isValid = false;
        }
        
        return isValid;
    }

    function validateGoalForm() {
        const title = document.getElementById('goalTitle').value.trim();
        if (!title) {
            alert('عنوان الهدف مطلوب');
            return false;
        }
        return validateGoalDates();
    }

    document.getElementById('validFrom').addEventListener('change', validateGoalDates);
    document.getElementById('validUntil').addEventListener('change', validateGoalDates);
    document.getElementById('targetDate').addEventListener('change', validateGoalDates);
</script>
@endpush

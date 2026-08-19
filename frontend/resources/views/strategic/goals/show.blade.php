@extends('layouts.app')

@section('title', 'تفاصيل الهدف')

@push('styles')
<style>
    .program-card {
        background: #fff;
        border-radius: 12px;
        padding: 16px;
        cursor: pointer;
        border: 1px solid #e2e8f0;
        transition: all 0.2s;
        border-right: 4px solid #38a169;
        margin-bottom: 12px;
    }
    .program-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.06);
    }
    .program-card.inactive {
        opacity: 0.5;
        border-right-color: #ccc;
    }
    .goal-info {
        background: #fff;
        border-radius: 16px;
        padding: 24px;
        border: 1px solid #e2e8f0;
        margin-bottom: 24px;
    }
    .text-danger {
        font-size: 12px;
        display: none;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <div class="mb-3">
        <a href="{{ route('strategic.goals.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-right"></i> العودة للأهداف
        </a>
        <a href="{{ route('strategic.pillars.show', $goal['pillar_id'] ?? 0) }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-right"></i> العودة للركيزة
        </a>
    </div>

    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif

    @php
        $goalId = $goal['id'] ?? 0;
        $goalTitle = $goal['name'] ?? $goal['title'] ?? '';
        $goalDesc = $goal['description'] ?? '';
        $isActive = $goal['is_active'] ?? true;
        $programs = $goal['programs'] ?? [];
        $pillarName = $goal['pillar_name'] ?? $goal['pillar']['name'] ?? '';
        $startDate = $goal['start_date'] ?? $goal['valid_from'] ?? '-';
        $endDate = $goal['end_date'] ?? $goal['valid_until'] ?? '-';
        $targetDate = $goal['target_date'] ?? '-';
        $weight = $goal['weight'] ?? 0;
        $status = $goal['status'] ?? 'active';
        $createdAt = $goal['created_at'] ?? date('Y-m-d');
    @endphp

    <div class="goal-info">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <h4>{{ $goalTitle }}</h4>
                <p class="text-muted">{{ $goalDesc }}</p>
            </div>
            <span class="badge bg-{{ $isActive ? 'success' : 'secondary' }} fs-6">
                {{ $isActive ? 'نشط' : 'غير نشط' }}
            </span>
        </div>
        <div class="row mt-3">
            <div class="col-md-3">
                <small class="text-muted">الركيزة</small>
                <div><strong>{{ $pillarName }}</strong></div>
            </div>
            <div class="col-md-3">
                <small class="text-muted">تاريخ البداية</small>
                <div><strong>{{ $startDate }}</strong></div>
            </div>
            <div class="col-md-3">
                <small class="text-muted">تاريخ النهاية</small>
                <div><strong>{{ $endDate }}</strong></div>
            </div>
            <div class="col-md-3">
                <small class="text-muted">التاريخ المستهدف</small>
                <div><strong>{{ $targetDate }}</strong></div>
            </div>
            <div class="col-md-3">
                <small class="text-muted">الوزن</small>
                <div><strong>{{ $weight }}</strong></div>
            </div>
            <div class="col-md-3">
                <small class="text-muted">الحالة</small>
                <div>
                    <span class="badge bg-{{ $status == 'active' ? 'success' : 'secondary' }}">
                        {{ $status == 'active' ? 'نشط' : 'غير نشط' }}
                    </span>
                </div>
            </div>
            <div class="col-md-3">
                <small class="text-muted">تاريخ الإنشاء</small>
                <div><strong>{{ $createdAt }}</strong></div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5>
            <i class="fas fa-project-diagram ml-2"></i>
            البرامج المرتبطة ({{ count($programs) }})
        </h5>
        <button class="btn-gold" data-bs-toggle="modal" data-bs-target="#addProgramModal">
            <i class="fas fa-plus"></i> برنامج جديد
        </button>
    </div>

    @if(empty($programs))
    <div class="card-custom text-center py-5">
        <i class="fas fa-project-diagram fa-3x text-muted mb-3"></i>
        <h5>لا توجد برامج مرتبطة بهذا الهدف</h5>
        <button class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#addProgramModal">
            <i class="fas fa-plus"></i> إضافة أول برنامج
        </button>
    </div>
    @else
    @foreach($programs as $program)
    @php $pActive = $program['is_active'] ?? true; @endphp
    <div class="program-card {{ $pActive ? '' : 'inactive' }}" 
         onclick="window.location='{{ route('strategic.programs.show', $program['id']) }}'">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <h6>{{ $program['name'] ?? $program['title'] ?? '' }}</h6>
                <p class="text-muted small mb-0">{{ Str::limit($program['description'] ?? '', 100) }}</p>
            </div>
            <span class="badge bg-{{ $pActive ? 'success' : 'secondary' }}">
                {{ $pActive ? 'نشط' : 'غير نشط' }}
            </span>
        </div>
        <div class="mt-2">
            <small class="text-muted">
                <i class="fas fa-coins"></i> الميزانية: {{ number_format($program['budget_estimate'] ?? $program['budget'] ?? 0) }}
            </small>
            @if(!empty($program['start_date']))
            <small class="text-muted me-3">
                <i class="fas fa-calendar-alt"></i> {{ $program['start_date'] }} 
                <i class="fas fa-arrow-left mx-1"></i> {{ $program['end_date'] ?? 'غير محدد' }}
            </small>
            @endif
        </div>
    </div>
    @endforeach
    @endif

    <div class="modal fade" id="addProgramModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle ml-2"></i>إضافة برنامج جديد
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('strategic.programs.store') }}" onsubmit="return validateProgramForm()">
                    @csrf
                    <input type="hidden" name="goal_id" value="{{ $goalId }}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">اسم البرنامج <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="programName" class="form-control" required maxlength="255">
                            <div class="text-danger" id="nameError" style="font-size:12px;display:none;">اسم البرنامج مطلوب</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">الوصف</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">الميزانية</label>
                                <input type="number" name="budget" class="form-control" step="0.01">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">تاريخ البداية</label>
                                <input type="date" name="start_date" id="programStart" class="form-control">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">تاريخ النهاية</label>
                                <input type="date" name="end_date" id="programEnd" class="form-control">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">الحالة</label>
                                <select name="status" class="form-control">
                                    <option value="active">نشط</option>
                                    <option value="inactive">غير نشط</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-primary">إضافة البرنامج</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function validateProgramForm() {
        const name = document.getElementById('programName').value.trim();
        const error = document.getElementById('nameError');
        
        if (!name) {
            error.style.display = 'block';
            document.getElementById('programName').classList.add('is-invalid');
            return false;
        }
        
        error.style.display = 'none';
        document.getElementById('programName').classList.remove('is-invalid');
        return true;
    }

    document.getElementById('programName').addEventListener('input', function() {
        const error = document.getElementById('nameError');
        if (this.value.trim()) {
            error.style.display = 'none';
            this.classList.remove('is-invalid');
        } else {
            error.style.display = 'block';
            this.classList.add('is-invalid');
        }
    });

    @if(session('success'))
        $(document).ready(function() {
            if (typeof toastr !== 'undefined') {
                toastr.success('{{ session('success') }}');
            }
        });
    @endif

    @if(session('error'))
        $(document).ready(function() {
            if (typeof toastr !== 'undefined') {
                toastr.error('{{ session('error') }}');
            }
        });
    @endif

    @if($errors->any())
        $(document).ready(function() {
            @foreach($errors->all() as $error)
                if (typeof toastr !== 'undefined') {
                    toastr.error('{{ $error }}');
                }
            @endforeach
        });
    @endif
</script>
@endpush

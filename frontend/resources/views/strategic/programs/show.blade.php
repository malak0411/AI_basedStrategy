@extends('layouts.app')

@section('title', 'تفاصيل البرنامج')

@push('styles')
<style>
    .initiative-card {
        background: #fff;
        border-radius: 12px;
        padding: 16px;
        cursor: pointer;
        border: 1px solid #e2e8f0;
        transition: all 0.2s;
        border-right: 4px solid #dd6b20;
        margin-bottom: 12px;
    }
    .initiative-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.06);
    }
    .initiative-card.inactive {
        opacity: 0.5;
        border-right-color: #ccc;
    }
    .program-info {
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
        <a href="{{ route('strategic.programs.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-right"></i> العودة للبرامج
        </a>
        <a href="{{ route('strategic.goals.show', $program['goal_id'] ?? 0) }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-right"></i> العودة للهدف
        </a>
    </div>

    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif

    @php
        $programId = $program['id'] ?? 0;
        $programTitle = $program['name'] ?? $program['title'] ?? '';
        $programDesc = $program['description'] ?? '';
        $isActive = $program['is_active'] ?? true;
        $initiatives = $program['initiatives'] ?? [];
        $goalName = $program['goal_name'] ?? $program['goal']['name'] ?? '';
        $budget = $program['budget_estimate'] ?? $program['budget'] ?? 0;
        $startDate = $program['start_date'] ?? '-';
        $endDate = $program['end_date'] ?? '-';
        $status = $program['status'] ?? 'active';
        $createdAt = $program['created_at'] ?? date('Y-m-d');
    @endphp

    <div class="program-info">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <h4>{{ $programTitle }}</h4>
                <p class="text-muted">{{ $programDesc }}</p>
            </div>
            <span class="badge bg-{{ $isActive ? 'success' : 'secondary' }} fs-6">
                {{ $isActive ? 'نشط' : 'غير نشط' }}
            </span>
        </div>
        <div class="row mt-3">
            <div class="col-md-3">
                <small class="text-muted">الهدف</small>
                <div><strong>{{ $goalName }}</strong></div>
            </div>
            <div class="col-md-3">
                <small class="text-muted">الميزانية</small>
                <div><strong>{{ number_format($budget) }}</strong></div>
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
            <i class="fas fa-lightbulb ml-2"></i>
            المبادرات المرتبطة ({{ count($initiatives) }})
        </h5>
        <button class="btn-gold" data-bs-toggle="modal" data-bs-target="#addInitiativeModal">
            <i class="fas fa-plus"></i> مبادرة جديدة
        </button>
    </div>

    @if(empty($initiatives))
    <div class="card-custom text-center py-5">
        <i class="fas fa-lightbulb fa-3x text-muted mb-3"></i>
        <h5>لا توجد مبادرات مرتبطة بهذا البرنامج</h5>
        <button class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#addInitiativeModal">
            <i class="fas fa-plus"></i> إضافة أول مبادرة
        </button>
    </div>
    @else
    @foreach($initiatives as $initiative)
    @php $iActive = $initiative['is_active'] ?? true; @endphp
    <div class="initiative-card {{ $iActive ? '' : 'inactive' }}" 
         onclick="window.location='{{ route('strategic.initiatives.show', $initiative['id']) }}'">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <h6>{{ $initiative['name'] ?? $initiative['title'] ?? '' }}</h6>
                <p class="text-muted small mb-0">{{ Str::limit($initiative['description'] ?? '', 100) }}</p>
            </div>
            <span class="badge bg-{{ $iActive ? 'success' : 'secondary' }}">
                {{ $iActive ? 'نشط' : 'غير نشط' }}
            </span>
        </div>
        <div class="mt-2">
            @if(!empty($initiative['priority']))
            <small class="text-muted">
                <i class="fas fa-flag"></i> الأولوية: 
                <span class="badge bg-{{ $initiative['priority'] == 'high' ? 'danger' : ($initiative['priority'] == 'medium' ? 'warning' : 'info') }}">
                    {{ $initiative['priority'] == 'high' ? 'عالية' : ($initiative['priority'] == 'medium' ? 'متوسطة' : 'منخفضة') }}
                </span>
            </small>
            @endif
            @if(!empty($initiative['start_date']))
            <small class="text-muted me-3">
                <i class="fas fa-calendar-alt"></i> {{ $initiative['start_date'] }} 
                <i class="fas fa-arrow-left mx-1"></i> {{ $initiative['end_date'] ?? 'غير محدد' }}
            </small>
            @endif
        </div>
    </div>
    @endforeach
    @endif

    <div class="modal fade" id="addInitiativeModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle ml-2"></i>إضافة مبادرة جديدة
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('strategic.initiatives.store') }}" onsubmit="return validateInitiativeForm()">
                    @csrf
                    <input type="hidden" name="program_id" value="{{ $programId }}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">اسم المبادرة <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="initiativeName" class="form-control" required maxlength="255">
                            <div class="text-danger" id="nameError" style="font-size:12px;display:none;">اسم المبادرة مطلوب</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">الوصف</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">تاريخ البداية</label>
                                <input type="date" name="start_date" id="initiativeStart" class="form-control">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">تاريخ النهاية</label>
                                <input type="date" name="end_date" id="initiativeEnd" class="form-control">
                                <div class="text-danger" id="dateError" style="font-size:12px;display:none;">تاريخ النهاية يجب أن يكون بعد تاريخ البداية</div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">الأولوية</label>
                                <select name="priority" class="form-control">
                                    <option value="low">منخفضة</option>
                                    <option value="medium" selected>متوسطة</option>
                                    <option value="high">عالية</option>
                                </select>
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
                        <button type="submit" class="btn btn-primary">إضافة المبادرة</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function validateInitiativeForm() {
        const name = document.getElementById('initiativeName').value.trim();
        const nameError = document.getElementById('nameError');
        
        if (!name) {
            nameError.style.display = 'block';
            document.getElementById('initiativeName').classList.add('is-invalid');
            return false;
        }
        
        nameError.style.display = 'none';
        document.getElementById('initiativeName').classList.remove('is-invalid');
        
        const startDate = document.getElementById('initiativeStart').value;
        const endDate = document.getElementById('initiativeEnd').value;
        const dateError = document.getElementById('dateError');
        
        if (startDate && endDate && endDate < startDate) {
            dateError.style.display = 'block';
            document.getElementById('initiativeEnd').classList.add('is-invalid');
            return false;
        }
        
        dateError.style.display = 'none';
        document.getElementById('initiativeEnd').classList.remove('is-invalid');
        
        return true;
    }

    document.getElementById('initiativeName').addEventListener('input', function() {
        const error = document.getElementById('nameError');
        if (this.value.trim()) {
            error.style.display = 'none';
            this.classList.remove('is-invalid');
        } else {
            error.style.display = 'block';
            this.classList.add('is-invalid');
        }
    });

    document.getElementById('initiativeEnd').addEventListener('change', function() {
        const startDate = document.getElementById('initiativeStart').value;
        const endDate = this.value;
        const error = document.getElementById('dateError');
        
        if (startDate && endDate && endDate < startDate) {
            error.style.display = 'block';
            this.classList.add('is-invalid');
        } else {
            error.style.display = 'none';
            this.classList.remove('is-invalid');
        }
    });

    document.getElementById('initiativeStart').addEventListener('change', function() {
        const endDate = document.getElementById('initiativeEnd').value;
        const startDate = this.value;
        const error = document.getElementById('dateError');
        
        if (startDate && endDate && endDate < startDate) {
            error.style.display = 'block';
            document.getElementById('initiativeEnd').classList.add('is-invalid');
        } else {
            error.style.display = 'none';
            document.getElementById('initiativeEnd').classList.remove('is-invalid');
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

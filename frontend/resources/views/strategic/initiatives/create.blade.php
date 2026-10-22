@extends('layouts.app')


@section('title', 'إنشاء مبادرة')


@section('content')
<div class="container-fluid px-4">
    <a href="javascript:history.back()" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-right"></i> العودة
    </a>
    <div class="card-custom">
        <h4 class="mb-4"><i class="fas fa-plus-circle ml-2"></i>إنشاء مبادرة جديدة</h4>


        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif


        <form method="POST" action="{{ route('strategic.initiatives.store') }}" id="initiativeForm">
            @csrf
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">اسم المبادرة</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name') }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">البرنامج</label>
                    <select name="program_id" id="program_id" class="form-control @error('program_id') is-invalid @enderror" required>
                        <option value="">اختر البرنامج</option>
                        @foreach($programs as $program)
                            <option value="{{ $program['id'] }}" @selected(old('program_id') == $program['id'])>
                                {{ $program['name'] ?? $program['title'] }}
                            </option>
                        @endforeach
                    </select>
                    @error('program_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">الأولوية</label>
                    <select name="priority_id" id="priority_id" class="form-control @error('priority_id') is-invalid @enderror" required>
                        <option value="">اختر الأولوية</option>
                        @foreach($priorities as $priority)
                            <option value="{{ $priority['id'] }}" @selected(old('priority_id', 2) == $priority['id'])>
                                {{ $priority['name'] }}
                            </option>
                        @endforeach
                    </select>
                    @error('priority_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>


                <div class="col-12 mb-3">
                    <div id="programNote" class="alert alert-info" style="display:none;">
                        <i class="fas fa-info-circle ml-1"></i>
                        <strong>بيانات البرنامج:</strong>
                        من <span id="programStartDisplay" class="fw-bold">-</span>
                        إلى <span id="programEndDisplay" class="fw-bold">-</span>
                        &nbsp;|&nbsp;
                        الميزانية: <span id="programBudgetDisplay" class="fw-bold">-</span>
                        <br>
                        <small class="text-muted">
                            يجب أن تكون تواريخ المبادرة داخل فترة البرنامج،
                            وألا تتجاوز ميزانية المبادرة ميزانية البرنامج.
                        </small>
                    </div>
                    <div id="programMissing" class="alert alert-warning" style="display:none;">
                        <i class="fas fa-exclamation-triangle ml-1"></i>
                        <strong>تنبيه:</strong> البرنامج المحدد لا يحتوي على تواريخ أو ميزانية صالحة.
                    </div>
                </div>


                <div class="col-12 mb-3">
                    <label class="form-label">الوصف</label>
                    <textarea name="description" class="form-control" rows="4">{{ old('description') }}</textarea>
                </div>


                <div class="col-md-4 mb-3">
                    <label class="form-label">الميزانية</label>
                    <input type="number" id="budget_estimate" name="budget_estimate" step="0.01" min="0"
                           class="form-control @error('budget_estimate') is-invalid @enderror"
                           value="{{ old('budget_estimate') }}" required>
                    <small class="text-muted" id="budgetHint"></small>
                    @error('budget_estimate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">تاريخ البداية</label>
                    <input type="date" id="start_date" name="start_date"
                           class="form-control @error('start_date') is-invalid @enderror"
                           value="{{ old('start_date') }}" required>
                    @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">تاريخ النهاية</label>
                    <input type="date" id="end_date" name="end_date"
                           class="form-control @error('end_date') is-invalid @enderror"
                           value="{{ old('end_date') }}" required>
                    @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>


                <div class="col-12">
                    <button type="submit" class="btn-gold">إنشاء المبادرة</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection


@section('scripts')
<script>
window.PROGRAM_DATA = @json($programData ?? []);


document.addEventListener('DOMContentLoaded', function () {


    const programSelect  = document.getElementById('program_id');
    const startDate      = document.getElementById('start_date');
    const endDate        = document.getElementById('end_date');
    const budgetInput    = document.getElementById('budget_estimate');
    const form           = document.getElementById('initiativeForm');


    const noteBox        = document.getElementById('programNote');
    const missingBox     = document.getElementById('programMissing');
    const pStartDisp     = document.getElementById('programStartDisplay');
    const pEndDisp       = document.getElementById('programEndDisplay');
    const pBudgetDisp    = document.getElementById('programBudgetDisplay');
    const budgetHint     = document.getElementById('budgetHint');


    function getSelectedProgram() {
        const pid = programSelect.value;
        if (!pid) return { start: '', end: '', budget: 0 };
        return window.PROGRAM_DATA[pid] || { start: '', end: '', budget: 0 };
    }


    function refreshProgramNote() {
        if (!programSelect.value) {
            noteBox.style.display = 'none';
            missingBox.style.display = 'none';
            budgetHint.textContent = '';
            budgetInput.removeAttribute('max');
            return;
        }
        const p = getSelectedProgram();
        const hasDates  = p.start && p.end;
        const hasBudget = p.budget && p.budget > 0;


        if (hasDates || hasBudget) {
            noteBox.style.display = 'block';
            missingBox.style.display = 'none';
            pStartDisp.textContent  = hasDates ? p.start : '-';
            pEndDisp.textContent    = hasDates ? p.end   : '-';
            pBudgetDisp.textContent = hasBudget ? p.budget.toLocaleString() : '-';


            if (hasBudget) {
                budgetInput.max = p.budget;
                budgetHint.textContent = 'الحد الأقصى: ' + p.budget.toLocaleString();
            } else {
                budgetInput.removeAttribute('max');
                budgetHint.textContent = '';
            }
        } else {
            noteBox.style.display = 'none';
            missingBox.style.display = 'block';
        }
    }


    function updateDateLimits() {
        const p = getSelectedProgram();


        if (p.start) startDate.min = p.start; else startDate.removeAttribute('min');
        if (p.end)   startDate.max = p.end;   else startDate.removeAttribute('max');


        if (startDate.value) endDate.min = startDate.value; else endDate.removeAttribute('min');
        if (p.end) endDate.max = p.end; else endDate.removeAttribute('max');
    }


    programSelect.addEventListener('change', function () {
        const p = getSelectedProgram();


        if (p.start && startDate.value && startDate.value < p.start) startDate.value = '';
        if (p.end   && startDate.value && startDate.value > p.end)   startDate.value = '';
        if (startDate.value && endDate.value && endDate.value < startDate.value) endDate.value = '';
        if (p.end   && endDate.value   && endDate.value > p.end)     endDate.value = '';


        if (p.budget > 0 && budgetInput.value && parseFloat(budgetInput.value) > p.budget) {
            budgetInput.value = '';
        }


        refreshProgramNote();
        updateDateLimits();
    });


    startDate.addEventListener('change', function () {
        const p = getSelectedProgram();
        if (!programSelect.value) {
            alert('يرجى اختيار البرنامج أولاً.');
            startDate.value = '';
            return;
        }
        if (p.start && startDate.value < p.start) {
            alert('تاريخ بداية المبادرة يجب أن يكون بعد أو يساوي تاريخ بداية البرنامج (' + p.start + ').');
            startDate.value = '';
        }
        if (p.end && startDate.value && startDate.value > p.end) {
            alert('تاريخ بداية المبادرة يجب أن يكون قبل تاريخ انتهاء البرنامج (' + p.end + ').');
            startDate.value = '';
        }
        if (endDate.value && startDate.value && endDate.value < startDate.value) endDate.value = '';
        updateDateLimits();
    });


    endDate.addEventListener('change', function () {
        const p = getSelectedProgram();
        const start = startDate.value;
        const end   = endDate.value;


        if (!start) {
            alert('يرجى تحديد تاريخ بداية المبادرة أولاً.');
            endDate.value = '';
            return;
        }
        if (end < start) {
            alert('تاريخ نهاية المبادرة يجب أن يكون بعد أو يساوي تاريخ بداية المبادرة.');
            endDate.value = '';
            return;
        }
        if (p.end && end > p.end) {
            alert('تاريخ نهاية المبادرة يجب أن يكون قبل أو يساوي تاريخ انتهاء البرنامج (' + p.end + ').');
            endDate.value = '';
            return;
        }
        updateDateLimits();
    });


    budgetInput.addEventListener('input', function () {
        const p = getSelectedProgram();
        if (p.budget > 0 && budgetInput.value && parseFloat(budgetInput.value) > p.budget) {
            alert('ميزانية المبادرة يجب ألا تتجاوز ميزانية البرنامج (' + p.budget.toLocaleString() + ').');
            budgetInput.value = p.budget;
        }
    });


    form.addEventListener('submit', function (e) {
        const p = getSelectedProgram();
        const start  = startDate.value;
        const end    = endDate.value;
        const budget = parseFloat(budgetInput.value || 0);


        if (p.start && start && start < p.start) {
            e.preventDefault();
            alert('تاريخ بداية المبادرة يجب أن يكون بعد أو يساوي تاريخ بداية البرنامج (' + p.start + ').');
            return;
        }
        if (p.end && start && start > p.end) {
            e.preventDefault();
            alert('تاريخ بداية المبادرة يجب أن يكون قبل تاريخ انتهاء البرنامج (' + p.end + ').');
            return;
        }
        if (start && end && end < start) {
            e.preventDefault();
            alert('تاريخ نهاية المبادرة يجب أن يكون بعد أو يساوي تاريخ بداية المبادرة.');
            return;
        }
        if (p.end && end && end > p.end) {
            e.preventDefault();
            alert('تاريخ نهاية المبادرة يجب أن يكون قبل أو يساوي تاريخ انتهاء البرنامج (' + p.end + ').');
            return;
        }
        if (p.budget > 0 && budget > p.budget) {
            e.preventDefault();
            alert('ميزانية المبادرة يجب ألا تتجاوز ميزانية البرنامج (' + p.budget.toLocaleString() + ').');
        }
    });


    refreshProgramNote();
    updateDateLimits();
});
</script>
@endsection

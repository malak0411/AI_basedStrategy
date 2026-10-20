@extends('layouts.app')


@section('title', 'تعديل البرنامج')


@section('content')
<div class="container-fluid px-4">
    <a href="{{ route('strategic.programs.show', $program['id']) }}" class="btn btn-outline-secondary mb-3">
        <i class="fas fa-arrow-right"></i> العودة
    </a>
    <div class="card-custom">
        <h4 class="mb-4"><i class="fas fa-edit ml-2"></i>تعديل البرنامج</h4>


        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif


        <form method="POST" action="{{ route('strategic.programs.update', $program['id']) }}" id="programForm">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col-md-8 mb-3">
                    <label class="form-label">اسم البرنامج</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name', $program['name'] ?? '') }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">الهدف</label>
                    <select name="goal_id" id="goal_id" class="form-control @error('goal_id') is-invalid @enderror" required>
                        <option value="">اختر الهدف</option>
                        @foreach($goals as $goal)
                            <option value="{{ $goal['id'] }}"
                                    data-start="{{ $goal['start_date'] ?? '' }}"
                                    data-end="{{ $goal['end_date'] ?? '' }}"
                                    @selected(old('goal_id', $program['goal_id'] ?? '') == $goal['id'])>
                                {{ $goal['name'] ?? $goal['title'] }}
                            </option>
                        @endforeach
                    </select>
                    @error('goal_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>


                <div class="col-12 mb-3">
                    <div id="goalDatesNote" class="alert alert-info d-none">
                        <i class="fas fa-info-circle ml-1"></i>
                        <strong>الفترة المسموحة للبرنامج:</strong>
                        من <span id="goalStartDisplay" class="fw-bold">-</span>
                        إلى <span id="goalEndDisplay" class="fw-bold">-</span>
                        <br>
                        <small class="text-muted">
                            يجب أن يكون تاريخ بداية البرنامج بعد أو يساوي تاريخ بداية الهدف،
                            وتاريخ النهاية بعد تاريخ بداية البرنامج وقبل أو يساوي تاريخ انتهاء الهدف.
                        </small>
                    </div>
                </div>


                <div class="col-12 mb-3">
                    <label class="form-label">الوصف</label>
                    <textarea name="description" class="form-control" rows="4">{{ old('description', $program['description'] ?? '') }}</textarea>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">الميزانية</label>
                    <input type="number" name="budget_estimate" class="form-control" step="0.01"
                           value="{{ old('budget_estimate', $program['budget_estimate'] ?? '') }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">تاريخ البداية</label>
                    <input type="date" id="start_date" name="start_date"
                           class="form-control @error('start_date') is-invalid @enderror"
                           value="{{ old('start_date', $program['start_date'] ?? '') }}" required>
                    @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">تاريخ النهاية</label>
                    <input type="date" id="end_date" name="end_date"
                           class="form-control @error('end_date') is-invalid @enderror"
                           value="{{ old('end_date', $program['end_date'] ?? '') }}" required>
                    @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <button type="submit" class="btn-gold">حفظ التعديلات</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection


@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {


    const goalSelect      = document.getElementById('goal_id');
    const startDate       = document.getElementById('start_date');
    const endDate         = document.getElementById('end_date');
    const form            = document.getElementById('programForm');


    const noteBox         = document.getElementById('goalDatesNote');
    const goalStartDisp   = document.getElementById('goalStartDisplay');
    const goalEndDisp     = document.getElementById('goalEndDisplay');


    function formatDate(d) {
        if (!d) return '-';
        return d;
    }


    function getGoalDates() {
        const opt = goalSelect.options[goalSelect.selectedIndex];
        return {
            start: opt ? (opt.dataset.start || '') : '',
            end:   opt ? (opt.dataset.end   || '') : ''
        };
    }


    function showGoalNote(goalStart, goalEnd) {
        if (!goalSelect.value) {
            noteBox.classList.add('d-none');
            goalStartDisp.textContent = '-';
            goalEndDisp.textContent   = '-';
            return;
        }
        noteBox.classList.remove('d-none');
        goalStartDisp.textContent = formatDate(goalStart);
        goalEndDisp.textContent   = formatDate(goalEnd);
    }


    function updateLimits() {
        const { start: goalStart, end: goalEnd } = getGoalDates();
        const start = startDate.value;


        if (goalStart) startDate.min = goalStart;
        else startDate.removeAttribute('min');


        if (goalEnd) startDate.max = goalEnd;
        else startDate.removeAttribute('max');


        if (start) endDate.min = start;
        else endDate.removeAttribute('min');


        if (goalEnd) endDate.max = goalEnd;
        else endDate.removeAttribute('max');
    }


    function refreshGoalUI() {
        const { start: goalStart, end: goalEnd } = getGoalDates();
        showGoalNote(goalStart, goalEnd);
        updateLimits();
    }


    goalSelect.addEventListener('change', function () {
        const { start: goalStart, end: goalEnd } = getGoalDates();


        if (goalStart && startDate.value && startDate.value < goalStart) startDate.value = '';
        if (goalEnd   && startDate.value && startDate.value > goalEnd)   startDate.value = '';
        if (startDate.value && endDate.value && endDate.value < startDate.value) endDate.value = '';
        if (goalEnd   && endDate.value   && endDate.value > goalEnd)     endDate.value = '';


        refreshGoalUI();
    });


    startDate.addEventListener('change', function () {
        const { start: goalStart, end: goalEnd } = getGoalDates();
        if (!goalSelect.value) {
            alert('يرجى اختيار الهدف أولاً.');
            startDate.value = '';
            return;
        }
        if (goalStart && startDate.value < goalStart) {
            alert('تاريخ بداية البرنامج يجب أن يكون بنفس تاريخ بداية الهدف أو بعده.\n' +
                  'تاريخ بداية الهدف: ' + goalStart);
            startDate.value = '';
        }
        if (goalEnd && startDate.value && startDate.value > goalEnd) {
            alert('تاريخ بداية البرنامج يجب أن يكون قبل تاريخ انتهاء الهدف.\n' +
                  'تاريخ انتهاء الهدف: ' + goalEnd);
            startDate.value = '';
        }
        if (endDate.value && startDate.value && endDate.value < startDate.value) endDate.value = '';
        updateLimits();
    });


    endDate.addEventListener('change', function () {
        const { end: goalEnd } = getGoalDates();
        const start = startDate.value;
        const end   = endDate.value;


        if (!start) {
            alert('يرجى تحديد تاريخ بداية البرنامج أولاً.');
            endDate.value = '';
            return;
        }
        if (end < start) {
            alert('تاريخ نهاية البرنامج يجب أن يكون بعد تاريخ بداية البرنامج.');
            endDate.value = '';
            return;
        }
        if (goalEnd && end > goalEnd) {
            alert('تاريخ نهاية البرنامج يجب أن يكون قبل أو يساوي تاريخ انتهاء الهدف.\n' +
                  'تاريخ انتهاء الهدف: ' + goalEnd);
            endDate.value = '';
            return;
        }
        updateLimits();
    });


    form.addEventListener('submit', function (e) {
        const { start: goalStart, end: goalEnd } = getGoalDates();
        const start = startDate.value;
        const end   = endDate.value;


        if (goalStart && start && start < goalStart) {
            e.preventDefault();
            alert('تاريخ بداية البرنامج يجب أن يكون بنفس تاريخ بداية الهدف أو بعده.\n' +
                  'تاريخ بداية الهدف: ' + goalStart);
            return;
        }
        if (start && end && end < start) {
            e.preventDefault();
            alert('تاريخ نهاية البرنامج يجب أن يكون بعد تاريخ بداية البرنامج.');
            return;
        }
        if (goalEnd && end && end > goalEnd) {
            e.preventDefault();
            alert('تاريخ نهاية البرنامج يجب أن يكون قبل أو يساوي تاريخ انتهاء الهدف.\n' +
                  'تاريخ انتهاء الهدف: ' + goalEnd);
        }
    });


    refreshGoalUI();
});
</script>
@endsection

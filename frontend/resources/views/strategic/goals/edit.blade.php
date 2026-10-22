@extends('layouts.app')


@section('title', 'تعديل الهدف')


@section('content')
<div class="container-fluid px-4">
    <a href="javascript:history.back()" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-right"></i> العودة
    </a>


    <div class="card-custom">
        <h4 class="mb-4"><i class="fas fa-edit ml-2"></i>تعديل الهدف</h4>


        <form method="POST" action="{{ route('strategic.goals.update', $goal['id']) }}" id="goalForm">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col-md-8 mb-3">
                    <label class="form-label">اسم الهدف</label>
                    <input type="text" name="name" class="form-control" value="{{ $goal['name'] ?? '' }}" required>
                </div>
                <div class="col-12 mb-3">
                    <label class="form-label">الوصف</label>
                    <textarea name="description" class="form-control" rows="4">{{ $goal['description'] ?? '' }}</textarea>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">تاريخ البداية</label>
                    <input type="date" id="start_date" name="start_date" class="form-control" value="{{ $goal['start_date'] ?? '' }}" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">تاريخ النهاية</label>
                    <input type="date" id="end_date" name="end_date" class="form-control" value="{{ $goal['end_date'] ?? '' }}" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">التاريخ المستهدف</label>
                    <input type="date" id="target_date" name="target_date" class="form-control" value="{{ $goal['target_date'] ?? '' }}" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">وزن الهدف</label>
                    <input type="number" name="weight" class="form-control" value="{{ $goal['weight'] ?? 0 }}" min="0">
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


    const startDate  = document.getElementById('start_date');
    const endDate    = document.getElementById('end_date');
    const targetDate = document.getElementById('target_date');
    const form       = document.getElementById('goalForm');


    const today = new Date();
    const todayString = today.getFullYear() + '-' +
        String(today.getMonth() + 1).padStart(2, '0') + '-' +
        String(today.getDate()).padStart(2, '0');


    startDate.min = todayString;
    endDate.min   = todayString;


    function updateLimits() {
        const start = startDate.value;
        const end   = endDate.value;


        endDate.min = start || todayString;


        targetDate.min = start || todayString;
        if (end) {
            targetDate.max = end;
        } else {
            targetDate.removeAttribute('max');
        }
    }


    startDate.addEventListener('change', function () {
        if (startDate.value && startDate.value < todayString) {
            alert('تاريخ البداية يجب أن يكون تاريخ اليوم أو تاريخ مستقبلي.');
            startDate.value = '';
        }
        if (endDate.value && endDate.value < startDate.value) {
            endDate.value = '';
        }
        if (targetDate.value && targetDate.value < startDate.value) {
            targetDate.value = '';
        }
        updateLimits();
    });


    endDate.addEventListener('change', function () {
        const start = startDate.value;
        const end   = endDate.value;


        if (start && end && end < start) {
            alert('تاريخ النهاية يجب أن يكون بعد تاريخ البداية.');
            endDate.value = '';
        }
        if (targetDate.value && endDate.value && targetDate.value > endDate.value) {
            targetDate.value = '';
        }
        updateLimits();
    });


    targetDate.addEventListener('change', function () {
        const start  = startDate.value;
        const end    = endDate.value;
        const target = targetDate.value;


        if (!start || !end) {
            alert('يرجى تحديد تاريخ البداية وتاريخ النهاية أولاً.');
            targetDate.value = '';
            return;
        }
        if (target < start) {
            alert('التاريخ المستهدف يجب أن يقع بعد تاريخ البداية.');
            targetDate.value = '';
        } else if (target > end) {
            alert('التاريخ المستهدف يجب أن يقع قبل تاريخ النهاية.');
            targetDate.value = '';
        }
    });


    form.addEventListener('submit', function (e) {
        const start  = startDate.value;
        const end    = endDate.value;
        const target = targetDate.value;


        if (start && start < todayString) {
            e.preventDefault();
            alert('تاريخ البداية يجب أن يكون تاريخ اليوم أو تاريخ مستقبلي.');
            return;
        }
        if (start && end && end < start) {
            e.preventDefault();
            alert('تاريخ النهاية يجب أن يكون بعد تاريخ البداية.');
            return;
        }
        if (start && end && target && (target < start || target > end)) {
            e.preventDefault();
            alert('التاريخ المستهدف يجب أن يقع بين تاريخ البداية وتاريخ النهاية.');
        }
    });


    updateLimits();
});
</script>
@endsection

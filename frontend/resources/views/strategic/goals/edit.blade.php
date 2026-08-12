@extends('layouts.app')

@section('title', 'تعديل الهدف')

@section('content')
<div class="container-fluid px-4">
    <a href="{{ route('strategic.goals.show', $goal['id']) }}" class="btn btn-outline-secondary mb-3">
        <i class="fas fa-arrow-right"></i> العودة
    </a>

    <div class="card-custom">
        <h4 class="mb-4"><i class="fas fa-edit ml-2"></i>تعديل الهدف</h4>

        <form method="POST" action="{{ route('strategic.goals.update', $goal['id']) }}">
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
                    <input type="date" name="start_date" class="form-control" value="{{ $goal['start_date'] ?? '' }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">تاريخ النهاية</label>
                    <input type="date" name="end_date" class="form-control" value="{{ $goal['end_date'] ?? '' }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label"> التاريخ المستهدف</label>
                    <input type="date" name="target_date" class="form-control" value="{{ $goal['target_date'] ?? '' }}">
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

    const today = new Date();
    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, '0');
    const day = String(today.getDate()).padStart(2, '0');

    const todayString = `${year}-${month}-${day}`;


    startDate.min = todayString;



    function updateDateLimits() {

        const start = startDate.value;
        const end = endDate.value;


        if (start) {

            endDate.min = start;

            if (endDate.value && endDate.value < start) {
                endDate.value = '';
            }
        } else {

            endDate.min = todayString;
        }



        if (start) {
            targetDate.min = start;
        } else {
            targetDate.min = todayString;
        }

        if (end) {
            targetDate.max = end;
        } else {
            targetDate.removeAttribute('max');
        }



        if (targetDate.value) {

            if (start && targetDate.value < start) {
                targetDate.value = '';
            }

            if (end && targetDate.value > end) {
                targetDate.value = '';
            }
        }
    }


    startDate.addEventListener('change', function () {

        updateDateLimits();

    });


    endDate.addEventListener('change', function () {

        const start = startDate.value;
        const end = endDate.value;

        if (start && end && end < start) {

            alert('تاريخ النهاية يجب أن يكون بعد أو مساويًا لتاريخ البداية.');

            endDate.value = '';
            targetDate.value = '';

            updateDateLimits();

            return;
        }

        updateDateLimits();

    });


    targetDate.addEventListener('change', function () {

        const start = startDate.value;
        const end = endDate.value;
        const target = targetDate.value;

        if (!start || !end) {

            alert('يرجى تحديد تاريخ البداية وتاريخ النهاية أولًا.');

            targetDate.value = '';

            return;
        }

        if (target < start) {

            alert('التاريخ المستهدف لا يمكن أن يكون قبل تاريخ البداية.');

            targetDate.value = '';

            return;
        }

        if (target > end) {

            alert('التاريخ المستهدف لا يمكن أن يكون بعد تاريخ النهاية.');

            targetDate.value = '';

            return;
        }

    });


    updateDateLimits();

});
</script>

@endsection

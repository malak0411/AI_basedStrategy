@extends('layouts.app')

@section('title', 'إنشاء هدف استراتيجي')

@section('content')
<div class="container-fluid px-4">

    <a href="{{ route('strategic.goals.index') }}" class="btn btn-outline-secondary mb-3">
        <i class="fas fa-arrow-right"></i> العودة
    </a>

    <div class="card-custom">

        <h4 class="mb-4">
            <i class="fas fa-plus-circle ml-2"></i>
            إنشاء هدف استراتيجي جديد
        </h4>

        <form method="POST" action="{{ route('strategic.goals.store') }}">
            @csrf

            <div class="row">

                {{-- اسم الهدف --}}
                <div class="col-md-8 mb-3">
                    <label class="form-label">اسم الهدف</label>

                    <input type="text"
                           name="name"
                           class="form-control"
                           value="{{ old('name') }}"
                           required>
                </div>

                {{-- الركيزة --}}
                <div class="col-md-4 mb-3">
                    <label class="form-label">الركيزة</label>

                    <select name="pillar_id"
                            class="form-control"
                            required>

                        <option value="">اختر الركيزة</option>

                        @foreach($pillars as $pillar)
                            <option value="{{ $pillar['id'] }}"
                                {{ old('pillar_id') == $pillar['id'] ? 'selected' : '' }}>

                                {{ $pillar['name'] ?? $pillar['title'] }}

                            </option>
                        @endforeach

                    </select>
                </div>

                {{-- الوصف --}}
                <div class="col-12 mb-3">
                    <label class="form-label">الوصف</label>

                    <textarea name="description"
                              class="form-control"
                              rows="4">{{ old('description') }}</textarea>
                </div>

                {{-- تاريخ البداية --}}
                <div class="col-md-4 mb-3">
                    <label class="form-label">
                        تاريخ البداية
                    </label>

                    <input type="date"
                           name="start_date"
                           id="start_date"
                           class="form-control"
                           value="{{ old('start_date') }}"
                           required>

                    <small class="text-muted">
                        يجب أن يكون اليوم أو تاريخًا مستقبليًا.
                    </small>
                </div>

                {{-- تاريخ النهاية --}}
                <div class="col-md-4 mb-3">
                    <label class="form-label">
                        تاريخ النهاية
                    </label>

                    <input type="date"
                           name="end_date"
                           id="end_date"
                           class="form-control"
                           value="{{ old('end_date') }}"
                           required>

                    <small class="text-muted">
                        يجب أن يكون بعد تاريخ البداية.
                    </small>
                </div>

                {{-- التاريخ المستهدف --}}
                <div class="col-md-4 mb-3">
                    <label class="form-label">
                        التاريخ المستهدف
                    </label>

                    <input type="date"
                           name="target_date"
                           id="target_date"
                           class="form-control"
                           value="{{ old('target_date') }}"
                           required>

                    <small class="text-muted">
                        يجب أن يقع بين تاريخ البداية وتاريخ النهاية.
                    </small>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">وزن الهدف</label>
                    <input type="number" name="weight" class="form-control" value="{{ old('weight', 0) }}" min="0">
                </div>

                {{-- زر الإنشاء --}}
                <div class="col-12">
                    <button type="submit" class="btn-gold">
                        إنشاء الهدف
                    </button>
                </div>

            </div>
        </form>

    </div>
</div>


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

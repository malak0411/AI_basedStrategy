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
                <div class="col-md-4 mb-3">
                    <label class="form-label">الركيزة</label>
                    <select name="pillar_id" class="form-control" required>
                        @foreach($pillars as $pillar)
                            <option value="{{ $pillar['id'] }}" {{ ($goal['pillar_id'] ?? '') == $pillar['id'] ? 'selected' : '' }}>
                                {{ $pillar['name'] ?? $pillar['title'] }}
                            </option>
                        @endforeach
                    </select>
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
                <div class="col-12">
                    <button type="submit" class="btn-gold">حفظ التعديلات</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

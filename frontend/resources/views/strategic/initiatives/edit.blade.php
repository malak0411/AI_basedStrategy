@extends('layouts.app')

@section('title', 'تعديل المبادرة')

@section('content')
<div class="container-fluid px-4">
    <a href="{{ route('strategic.initiatives.show', $initiative['id']) }}" class="btn btn-outline-secondary mb-3">
        <i class="fas fa-arrow-right"></i> العودة
    </a>

    <div class="card-custom">
        <h4 class="mb-4"><i class="fas fa-edit ml-2"></i>تعديل المبادرة</h4>

        <form method="POST" action="{{ route('strategic.initiatives.update', $initiative['id']) }}">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col-md-8 mb-3">
                    <label class="form-label">اسم المبادرة</label>
                    <input type="text" name="name" class="form-control" value="{{ $initiative['name'] ?? '' }}" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">البرنامج</label>
                    <select name="program_id" class="form-control" required>
                        @foreach($programs as $program)
                            <option value="{{ $program['id'] }}" {{ ($initiative['program_id'] ?? '') == $program['id'] ? 'selected' : '' }}>
                                {{ $program['name'] ?? $program['title'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 mb-3">
                    <label class="form-label">الوصف</label>
                    <textarea name="description" class="form-control" rows="4">{{ $initiative['description'] ?? '' }}</textarea>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">تاريخ البداية</label>
                    <input type="date" name="start_date" class="form-control" value="{{ $initiative['start_date'] ?? '' }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">تاريخ النهاية</label>
                    <input type="date" name="end_date" class="form-control" value="{{ $initiative['end_date'] ?? '' }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">الأولوية</label>
                    <select name="priority" class="form-control">
                        <option value="low" {{ ($initiative['priority'] ?? '') == 'low' ? 'selected' : '' }}>منخفضة</option>
                        <option value="medium" {{ ($initiative['priority'] ?? '') == 'medium' ? 'selected' : '' }}>متوسطة</option>
                        <option value="high" {{ ($initiative['priority'] ?? '') == 'high' ? 'selected' : '' }}>عالية</option>
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn-gold">حفظ التعديلات</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

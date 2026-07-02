@extends('layouts.app')

@section('title', 'إنشاء برنامج')

@section('content')
<div class="container-fluid px-4">
    <a href="{{ route('strategic.programs.index') }}" class="btn btn-outline-secondary mb-3">
        <i class="fas fa-arrow-right"></i> العودة
    </a>

    <div class="card-custom">
        <h4 class="mb-4"><i class="fas fa-plus-circle ml-2"></i>إنشاء برنامج جديد</h4>

        <form method="POST" action="{{ route('strategic.programs.store') }}">
            @csrf
            <div class="row">
                <div class="col-md-8 mb-3">
                    <label class="form-label">اسم البرنامج</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">الهدف</label>
                    <select name="goal_id" class="form-control" required>
                        <option value="">اختر الهدف</option>
                        @foreach($goals as $goal)
                            <option value="{{ $goal['id'] }}">{{ $goal['name'] ?? $goal['title'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 mb-3">
                    <label class="form-label">الوصف</label>
                    <textarea name="description" class="form-control" rows="4"></textarea>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">الميزانية</label>
                    <input type="number" name="budget" class="form-control" step="0.01">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">تاريخ البداية</label>
                    <input type="date" name="start_date" class="form-control">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">تاريخ النهاية</label>
                    <input type="date" name="end_date" class="form-control">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn-gold">إنشاء البرنامج</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

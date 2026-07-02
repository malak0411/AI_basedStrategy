@extends('layouts.app')

@section('title', 'إنشاء مبادرة')

@section('content')
<div class="container-fluid px-4">
    <a href="{{ route('strategic.initiatives.index') }}" class="btn btn-outline-secondary mb-3">
        <i class="fas fa-arrow-right"></i> العودة
    </a>

    <div class="card-custom">
        <h4 class="mb-4"><i class="fas fa-plus-circle ml-2"></i>إنشاء مبادرة جديدة</h4>

        <form method="POST" action="{{ route('strategic.initiatives.store') }}">
            @csrf
            <div class="row">
                <div class="col-md-8 mb-3">
                    <label class="form-label">اسم المبادرة</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">البرنامج</label>
                    <select name="program_id" class="form-control" required>
                        <option value="">اختر البرنامج</option>
                        @foreach($programs as $program)
                            <option value="{{ $program['id'] }}">{{ $program['name'] ?? $program['title'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 mb-3">
                    <label class="form-label">الوصف</label>
                    <textarea name="description" class="form-control" rows="4"></textarea>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">تاريخ البداية</label>
                    <input type="date" name="start_date" class="form-control">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">تاريخ النهاية</label>
                    <input type="date" name="end_date" class="form-control">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">الأولوية</label>
                    <select name="priority" class="form-control">
                        <option value="low">منخفضة</option>
                        <option value="medium" selected>متوسطة</option>
                        <option value="high">عالية</option>
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn-gold">إنشاء المبادرة</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@extends('layouts.app')

@section('title', 'الرؤية الاستراتيجية')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3><i class="fas fa-eye ml-2"></i>الرؤية الاستراتيجية</h3>
        <button class="btn-gold" onclick="toggleEdit()">
            <i class="fas fa-edit"></i> تعديل الرؤية
        </button>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div id="viewMode">
        <div class="card-custom">
            <h4 class="text-primary">{{ $vision['text'] ?? 'لم يتم تحديد الرؤية بعد' }}</h4>
            <p class="text-muted mt-3">{{ $vision['description'] ?? '' }}</p>
            @if(!empty($vision['version']))
                <small class="text-muted">الإصدار: {{ $vision['version'] }}</small>
            @endif
        </div>
    </div>

    <div id="editMode" style="display:none;">
        <div class="card-custom">
            <h4 class="mb-4"><i class="fas fa-edit ml-2"></i>تعديل الرؤية</h4>
            <form method="POST" action="{{ route('strategic.vision.update') }}">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label class="form-label">نص الرؤية</label>
                    <textarea name="text" class="form-control" rows="3" required>{{ $vision['text'] ?? '' }}</textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">الوصف</label>
                    <textarea name="description" class="form-control" rows="5">{{ $vision['description'] ?? '' }}</textarea>
                </div>
                <button type="submit" class="btn-gold">حفظ</button>
                <button type="button" class="btn btn-outline-secondary mr-2" onclick="toggleEdit()">إلغاء</button>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function toggleEdit() {
        document.getElementById('viewMode').style.display = 
            document.getElementById('viewMode').style.display === 'none' ? 'block' : 'none';
        document.getElementById('editMode').style.display = 
            document.getElementById('editMode').style.display === 'none' ? 'block' : 'none';
    }
</script>
@endpush
@endsection

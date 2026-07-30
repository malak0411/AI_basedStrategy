@extends('layouts.app')

@section('title', 'المهام الرئيسية للإدارة')

@section('content')
<div class="container-fluid px-4">
    <h3 class="mb-4"><i class="fas fa-project-diagram ml-2"></i>المهام الرئيسية</h3>

    @if(empty($tasks))
    <div class="card-custom text-center py-5">
        <i class="fas fa-tasks fa-4x text-muted mb-3"></i>
        <h5>لا توجد مهام رئيسية</h5>
    </div>
    @else
    <div class="row">
        @foreach($tasks as $task)
        @php $tid = $task['id'] ?? 0; $active = $task['is_active'] ?? true; @endphp
        <div class="col-md-4 mb-4">
            <div class="card-custom h-100 {{ $active ? '' : 'opacity-50 bg-light' }}">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h6 class="mb-0">{{ $task['name'] ?? '' }}</h6>
                    <span class="badge bg-{{ $active ? 'success' : 'secondary' }}">{{ $active ? 'نشط' : 'غير نشط' }}</span>
                </div>
                <p class="text-muted small mb-2">{{ Str::limit($task['description'] ?? '', 100) }}</p>
                @if(!empty($task['initiative_name']))
                <p class="text-muted small mb-2">المبادرة: {{ $task['initiative_name'] }}</p>
                @endif
                <div class="d-flex justify-content-between align-items-center mt-auto">
                    <small class="text-muted">{{ $task['estimated_duration_days'] ?? 0 }} يوم</small>
                    <div class="d-flex gap-1">
                        <a href="{{ route('operational.show-major-task', $tid) }}" class="btn btn-sm btn-outline-info" title="تفاصيل">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="{{ route('operational.generate', $tid) }}" class="btn btn-sm btn-outline-success" title="توليد مهام تشغيلية">
                            <i class="fas fa-robot"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>
@endsection

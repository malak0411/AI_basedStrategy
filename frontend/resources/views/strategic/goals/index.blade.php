@extends('layouts.app')

@section('title', 'الأهداف الاستراتيجية')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3><i class="fas fa-bullseye ml-2"></i>الأهداف الاستراتيجية</h3>
        <a href="{{ route('strategic.goals.create') }}" class="btn-gold">
            <i class="fas fa-plus"></i> هدف جديد
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(empty($goals))
        <div class="card-custom text-center py-5">
            <i class="fas fa-bullseye fa-3x text-muted mb-3"></i>
            <h5>لا توجد أهداف استراتيجية</h5>
            <a href="{{ route('strategic.goals.create') }}" class="btn btn-primary mt-3">إضافة هدف</a>
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-hover card-custom">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>الهدف</th>
                        <th>الركيزة</th>
                        <th>التقدم</th>
                        <th>الحالة</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($goals as $goal)
                    <tr>
                        <td>{{ $goal['id'] ?? $loop->iteration }}</td>
                        <td>
                            <a href="{{ route('strategic.goals.show', $goal['id']) }}" class="text-decoration-none fw-bold">
                                {{ $goal['name'] ?? $goal['title'] ?? 'غير محدد' }}
                            </a>
                        </td>
                        <td>{{ $goal['pillar_name'] ?? '' }}</td>
                        <td>
                            @php $progress = $goal['progress'] ?? 0; @endphp
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-{{ $progress >= 80 ? 'success' : 'info' }}" 
                                     style="width: {{ $progress }}%"></div>
                            </div>
                            <small>{{ $progress }}%</small>
                        </td>
                        <td>
                            @php $status = $goal['status'] ?? 'active'; @endphp
                            <span class="badge bg-{{ $status == 'active' || $status == 1 ? 'success' : 'secondary' }}">
                                {{ $status == 'active' || $status == 1 ? 'نشط' : 'غير نشط' }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('strategic.goals.show', $goal['id']) }}" class="btn btn-sm btn-outline-info">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('strategic.goals.edit', $goal['id']) }}" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-edit"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection

@extends('layouts.app')

@section('title', 'البرامج')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3><i class="fas fa-project-diagram ml-2"></i>البرامج</h3>
        <a href="{{ route('strategic.programs.create') }}" class="btn-gold">
            <i class="fas fa-plus"></i> برنامج جديد
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(empty($programs))
        <div class="card-custom text-center py-5">
            <i class="fas fa-project-diagram fa-3x text-muted mb-3"></i>
            <h5>لا توجد برامج</h5>
            <a href="{{ route('strategic.programs.create') }}" class="btn btn-primary mt-3">إضافة برنامج</a>
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-hover card-custom">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>البرنامج</th>
                        <th>الهدف</th>
                        <th>الميزانية</th>
                        <th>التقدم</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($programs as $program)
                    <tr>
                        <td>{{ $program['id'] ?? $loop->iteration }}</td>
                        <td>
                            <a href="{{ route('strategic.programs.show', $program['id']) }}" class="text-decoration-none fw-bold">
                                {{ $program['name'] ?? $program['title'] ?? 'غير محدد' }}
                            </a>
                        </td>
                        <td>{{ $program['goal_name'] ?? '' }}</td>
                        <td>{{ number_format($program['budget'] ?? 0) }}</td>
                        <td>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-info" style="width: {{ $program['progress'] ?? 0 }}%"></div>
                            </div>
                            <small>{{ $program['progress'] ?? 0 }}%</small>
                        </td>
                        <td>
                            <a href="{{ route('strategic.programs.show', $program['id']) }}" class="btn btn-sm btn-outline-info">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('strategic.programs.edit', $program['id']) }}" class="btn btn-sm btn-outline-primary">
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

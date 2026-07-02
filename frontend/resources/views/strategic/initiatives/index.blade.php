@extends('layouts.app')

@section('title', 'المبادرات')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3><i class="fas fa-lightbulb ml-2"></i>المبادرات</h3>
        <a href="{{ route('strategic.initiatives.create') }}" class="btn-gold">
            <i class="fas fa-plus"></i> مبادرة جديدة
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(empty($initiatives))
        <div class="card-custom text-center py-5">
            <i class="fas fa-lightbulb fa-3x text-muted mb-3"></i>
            <h5>لا توجد مبادرات</h5>
            <a href="{{ route('strategic.initiatives.create') }}" class="btn btn-primary mt-3">إضافة مبادرة</a>
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-hover card-custom">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>المبادرة</th>
                        <th>البرنامج</th>
                        <th>الحالة</th>
                        <th>التقدم</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($initiatives as $initiative)
                    <tr>
                        <td>{{ $initiative['id'] ?? $loop->iteration }}</td>
                        <td>
                            <a href="{{ route('strategic.initiatives.show', $initiative['id']) }}" class="text-decoration-none fw-bold">
                                {{ $initiative['name'] ?? $initiative['title'] ?? 'غير محدد' }}
                            </a>
                        </td>
                        <td>{{ $initiative['program_name'] ?? '' }}</td>
                        <td>
                            <span class="badge bg-{{ ($initiative['status'] ?? '') === 'active' ? 'success' : 'warning' }}">
                                {{ ($initiative['status'] ?? '') === 'active' ? 'نشط' : 'معلق' }}
                            </span>
                        </td>
                        <td>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-info" style="width: {{ $initiative['progress'] ?? 0 }}%"></div>
                            </div>
                            <small>{{ $initiative['progress'] ?? 0 }}%</small>
                        </td>
                        <td>
                            <a href="{{ route('strategic.initiatives.show', $initiative['id']) }}" class="btn btn-sm btn-outline-info">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('strategic.initiatives.edit', $initiative['id']) }}" class="btn btn-sm btn-outline-primary">
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

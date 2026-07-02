@extends('layouts.app')

@section('title', 'إدارة الأدوار')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3><i class="fas fa-user-tag ml-2"></i>إدارة الأدوار</h3>
        <a href="{{ route('admin.roles.create') }}" class="btn-gold">
            <i class="fas fa-plus"></i> دور جديد
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(empty($roles))
        <div class="card-custom text-center py-5">
            <i class="fas fa-user-tag fa-3x text-muted mb-3"></i>
            <h5>لا توجد أدوار</h5>
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-hover card-custom">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>الدور</th>
                        <th>الوصف</th>
                        <th>نظامي</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($roles as $role)
                    <tr>
                        <td>{{ $role['id'] ?? $loop->iteration }}</td>
                        <td><strong>{{ $role['name'] ?? '' }}</strong></td>
                        <td>{{ Str::limit($role['description'] ?? '', 60) }}</td>
                        <td>
                            <span class="badge bg-{{ ($role['is_system'] ?? false) ? 'warning' : 'info' }}">
                                {{ ($role['is_system'] ?? false) ? 'نظامي' : 'مخصص' }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('admin.roles.show', $role['id']) }}" class="btn btn-sm btn-outline-info">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('admin.roles.edit', $role['id']) }}" class="btn btn-sm btn-outline-primary">
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
s
@extends('layouts.app')

@section('title', 'إدارة الصلاحيات')

@section('content')
<div class="container-fluid px-4">
    <h3 class="mb-4"><i class="fas fa-shield-alt ml-2"></i>إدارة الصلاحيات</h3>

    @if(empty($permissions))
        <div class="card-custom text-center py-5">
            <i class="fas fa-shield-alt fa-3x text-muted mb-3"></i>
            <h5>لا توجد صلاحيات</h5>
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-hover card-custom">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>الصلاحية</th>
                        <th>الوصف</th>
                        <th>إجراء</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($permissions as $permission)
                    <tr>
                        <td>{{ $permission['id'] ?? $loop->iteration }}</td>
                        <td><code>{{ $permission['name'] ?? '' }}</code></td>
                        <td>{{ Str::limit($permission['description'] ?? '', 80) }}</td>
                        <td>
                            <a href="{{ route('admin.permissions.show', $permission['id']) }}" class="btn btn-sm btn-outline-info">
                                <i class="fas fa-eye"></i>
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

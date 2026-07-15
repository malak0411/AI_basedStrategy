@extends('layouts.app')

@section('title', 'إدارة الإدارات')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3><i class="fas fa-building ml-2"></i>إدارة الإدارات</h3>
        <a href="{{ route('admin.departments.create') }}" class="btn-gold"><i class="fas fa-plus"></i> إدارة جديدة</a>
    </div>

    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif

    <div class="table-responsive">
        <table class="table table-hover card-custom">
            <thead>
                <tr><th>#</th><th>الاسم</th><th>الكود</th><th>المدير</th><th>المستوى</th><th>الحالة</th><th>إجراءات</th></tr>
            </thead>
            <tbody>
                @forelse($departments as $dept)
                <tr>
                    <td>{{ $dept['department_id'] ?? $loop->iteration }}</td>
                    <td><a href="{{ route('admin.departments.show', $dept['department_id']) }}" class="fw-bold text-decoration-none">{{ $dept['name'] ?? '' }}</a></td>
                    <td>{{ $dept['code'] ?? '' }}</td>
                    <td>{{ $dept['manager_name'] ?? 'غير محدد' }}</td>
                    <td>{{ $dept['level'] ?? '' }}</td>
                    <td><span class="badge bg-{{ ($dept['is_active'] ?? true) ? 'success' : 'danger' }}">{{ ($dept['is_active'] ?? true) ? 'نشطة' : 'غير نشطة' }}</span></td>
                    <td>
                        <a href="{{ route('admin.departments.show', $dept['department_id']) }}" class="btn btn-sm btn-outline-info"><i class="fas fa-eye"></i></a>
                        <a href="{{ route('admin.departments.edit', $dept['department_id']) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                        <form action="{{ route('admin.departments.destroy', $dept['department_id']) }}" method="POST" class="d-inline" onsubmit="return confirm('متأكد من حذف الإدارة؟')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center py-4">لا توجد إدارات</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

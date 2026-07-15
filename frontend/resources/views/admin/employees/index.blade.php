@extends('layouts.app')

@section('title', 'إدارة الموظفين')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3><i class="fas fa-users ml-2"></i>إدارة الموظفين</h3>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if(empty($employees))
        <div class="card-custom text-center py-5">
            <i class="fas fa-user-slash fa-3x text-muted mb-3"></i>
            <h5>لا يوجد موظفين</h5>
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-hover card-custom">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>الاسم</th>
                        <th>البريد الإلكتروني</th>
                        <th>الإدارة</th>
                        <th>المسمى الوظيفي</th>
                        <th>الحالة</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($employees as $emp)
                    <tr>
                        <td>{{ $emp['employee_id'] ?? $loop->iteration }}</td>
                        <td>
                            <a href="{{ route('admin.employees.show', $emp['employee_id']) }}" class="text-decoration-none fw-bold">
                                {{ $emp['full_name'] ?? '' }}
                            </a>
                        </td>
                        <td>{{ $emp['email'] ?? '' }}</td>
                        <td>{{ $emp['department_name'] ?? '' }}</td>
                        <td>{{ $emp['job_title'] ?? '' }}</td>
                        <td>
                            <span class="badge bg-{{ ($emp['is_active'] ?? false) ? 'success' : 'danger' }}">
                                {{ ($emp['is_active'] ?? false) ? 'نشط' : 'غير نشط' }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('admin.employees.show', $emp['employee_id']) }}" class="btn btn-sm btn-outline-info" title="عرض">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('admin.employees.edit', $emp['employee_id']) }}" class="btn btn-sm btn-outline-primary" title="تعديل">
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

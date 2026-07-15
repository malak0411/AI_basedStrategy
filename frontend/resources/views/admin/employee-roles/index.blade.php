@extends('layouts.app')

@section('title', 'ربط الموظفين بالأدوار')

@section('content')
<div class="container-fluid px-4">
    <h3 class="mb-4"><i class="fas fa-user-check ml-2"></i>ربط الموظفين بالأدوار</h3>

    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif

    {{-- نموذج الإضافة --}}
    <div class="card-custom mb-4">
        <h5>إضافة ربط جديد</h5>
        <form method="POST" action="{{ route('admin.employee-roles.store') }}" class="row">
            @csrf
            <div class="col-md-5">
                <select name="employee_id" class="form-control" required>
                    <option value="">اختر الموظف</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp['employee_id'] ?? '' }}">{{ $emp['full_name'] ?? '' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-5">
                <select name="role_id" class="form-control" required>
                    <option value="">اختر الدور</option>
                    @foreach($roles as $role)
                        <option value="{{ $role['id'] ?? '' }}">{{ $role['name'] ?? '' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn-gold w-100">ربط</button>
            </div>
        </form>
    </div>

    {{-- قائمة الارتباطات --}}
    <div class="table-responsive">
        <table class="table table-hover card-custom">
            <thead><tr><th>الموظف</th><th>الدور</th><th>إجراء</th></tr></thead>
            <tbody>
                @forelse($employeeRoles as $er)
                <tr>
                    <td>{{ $er['employee_name'] ?? '' }}</td>
                    <td><span class="badge bg-info">{{ $er['role_name'] ?? '' }}</span></td>
                    <td>
                        <form method="POST" action="{{ route('admin.employee-roles.destroy') }}" onsubmit="return confirm('متأكد من إلغاء الربط؟')">
                            @csrf @method('DELETE')
                            <input type="hidden" name="employee_id" value="{{ $er['employee_id'] }}">
                            <input type="hidden" name="role_id" value="{{ $er['role_id'] }}">
                            <button class="btn btn-sm btn-outline-danger"><i class="fas fa-unlink"></i> إلغاء</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="3" class="text-center py-4">لا توجد ارتباطات</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

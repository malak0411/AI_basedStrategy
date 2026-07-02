@extends('layouts.app')

@section('title', 'ربط الموظفين بالأدوار')

@section('content')
<div class="container-fluid px-4">
    <h3 class="mb-4"><i class="fas fa-user-check ml-2"></i>ربط الموظفين بالأدوار</h3>

    @if(empty($employeeRoles))
        <div class="card-custom text-center py-5">
            <i class="fas fa-user-check fa-3x text-muted mb-3"></i>
            <h5>لا توجد ارتباطات</h5>
        </div>
    @else
        @php $grouped = []; @endphp
        @foreach($employeeRoles as $er)
            @php $grouped[$er['employee_name'] ?? ''][] = $er['role_name'] ?? ''; @endphp
        @endforeach

        <div class="table-responsive">
            <table class="table table-hover card-custom">
                <thead>
                    <tr>
                        <th>الموظف</th>
                        <th>الأدوار</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($grouped as $employee => $roles)
                    <tr>
                        <td><strong>{{ $employee }}</strong></td>
                        <td>
                            @foreach($roles as $role)
                                <span class="badge bg-info me-1">{{ $role }}</span>
                            @endforeach
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection

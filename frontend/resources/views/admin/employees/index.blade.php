@extends('layouts.app')

@section('title', 'إدارة الموظفين')

@section('content')
<div class="container-fluid px-4">
    <h3><i class="fas fa-users ml-2"></i>إدارة الموظفين</h3>

    <table class="table table-bordered mt-4">
        <thead>
            <tr>
                <th>#</th>
                <th>الاسم</th>
                <th>البريد الإلكتروني</th>
                <th>الإدارة</th>
                <th>الحالة</th>
            </tr>
        </thead>
        <tbody>
            @forelse($employees as $emp)
            <tr>
                <td>{{ $emp['employee_id'] ?? '' }}</td>
                <td>{{ $emp['full_name'] ?? '' }}</td>
                <td>{{ $emp['email'] ?? '' }}</td>
                <td>{{ $emp['department_name'] ?? '' }}</td>
                <td>{{ ($emp['is_active'] ?? false) ? 'نشط' : 'غير نشط' }}</td>
            </tr>
            @empty
            <tr><td colspan="5">لا يوجد موظفين</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection

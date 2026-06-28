@extends('layouts.app')

@section('title', 'إدارة الإدارات')

@section('content')
<div class="container-fluid px-4">
    <h3><i class="fas fa-building ml-2"></i>إدارة الإدارات</h3>

    <table class="table table-bordered mt-4">
        <thead>
            <tr>
                <th>#</th>
                <th>اسم الإدارة</th>
                <th>الكود</th>
                <th>المدير</th>
            </tr>
        </thead>
        <tbody>
            @forelse($departments as $dept)
            <tr>
                <td>{{ $dept['department_id'] ?? '' }}</td>
                <td>{{ $dept['name'] ?? '' }}</td>
                <td>{{ $dept['code'] ?? '' }}</td>
                <td>{{ $dept['manager_name'] ?? 'غير محدد' }}</td>
            </tr>
            @empty
            <tr><td colspan="4">لا توجد إدارات</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection

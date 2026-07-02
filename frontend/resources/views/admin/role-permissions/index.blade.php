@extends('layouts.app')

@section('title', 'ربط الأدوار بالصلاحيات')

@section('content')
<div class="container-fluid px-4">
    <h3 class="mb-4"><i class="fas fa-link ml-2"></i>ربط الأدوار بالصلاحيات</h3>

    @if(empty($rolePermissions))
        <div class="card-custom text-center py-5">
            <i class="fas fa-link fa-3x text-muted mb-3"></i>
            <h5>لا توجد ارتباطات</h5>
        </div>
    @else
        @php $grouped = []; @endphp
        @foreach($rolePermissions as $rp)
            @php $grouped[$rp['role_name'] ?? ''][] = $rp['permission_name'] ?? ''; @endphp
        @endforeach

        <div class="table-responsive">
            <table class="table table-hover card-custom">
                <thead>
                    <tr>
                        <th>الدور</th>
                        <th>الصلاحيات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($grouped as $role => $permissions)
                    <tr>
                        <td><strong>{{ $role }}</strong></td>
                        <td>
                            @foreach($permissions as $perm)
                                <code class="me-2">{{ $perm }}</code>
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

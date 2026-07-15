@extends('layouts.app')

@section('title', 'ربط الأدوار بالصلاحيات')

@section('content')
<div class="container-fluid px-4">
    <h3 class="mb-4"><i class="fas fa-link ml-2"></i>ربط الأدوار بالصلاحيات</h3>

    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif

    {{-- نموذج الإضافة --}}
    <div class="card-custom mb-4">
        <h5>إضافة ربط جديد</h5>
        <form method="POST" action="{{ route('admin.role-permissions.store') }}" class="row">
            @csrf
            <div class="col-md-5">
                <select name="role_id" class="form-control" required>
                    <option value="">اختر الدور</option>
                    @foreach($roles as $role)
                        <option value="{{ $role['id'] ?? '' }}">{{ $role['name'] ?? '' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-5">
                <select name="permission_id" class="form-control" required>
                    <option value="">اختر الصلاحية</option>
                    @foreach($permissions as $perm)
                        <option value="{{ $perm['id'] ?? '' }}">{{ $perm['name'] ?? '' }}</option>
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
            <thead><tr><th>الدور</th><th>الصلاحية</th><th>إجراء</th></tr></thead>
            <tbody>
                @forelse($rolePermissions as $rp)
                <tr>
                    <td>{{ $rp['role_name'] ?? '' }}</td>
                    <td><code>{{ $rp['permission_name'] ?? '' }}</code></td>
                    <td>
                        <form method="POST" action="{{ route('admin.role-permissions.destroy') }}" onsubmit="return confirm('متأكد من إلغاء الربط؟')">
                            @csrf @method('DELETE')
                            <input type="hidden" name="role_id" value="{{ $rp['role_id'] }}">
                            <input type="hidden" name="permission_id" value="{{ $rp['permission_id'] }}">
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

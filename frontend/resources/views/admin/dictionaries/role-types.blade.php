@extends('layouts.app')

@section('title', 'إدارة أنواع الأدوار')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>إدارة أنواع الأدوار (Role Types)</h3>
        <button class="btn-gold" data-bs-toggle="modal" data-bs-target="#addModal">إضافة نوع</button>
    </div>

    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif

    <div class="table-responsive card-custom">
        <table class="table table-hover mb-0">
            <thead><tr><th>#</th><th>الكود</th><th>العربية</th><th>الإنجليزية</th><th>الوصف</th><th>إجراءات</th></tr></thead>
            <tbody>
                @forelse($items as $item)
                <tr>
                    <td>{{ $item['role_type_id'] ?? $loop->iteration }}</td>
                    <td><code>{{ $item['code'] ?? '' }}</code></td>
                    <td>{{ $item['name_ar'] ?? '' }}</td>
                    <td>{{ $item['name_en'] ?? '' }}</td>
                    <td>{{ Str::limit($item['description'] ?? '', 60) }}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" onclick="editItem({{ json_encode($item) }})"><i class="fas fa-edit"></i></button>
                        <form action="{{ route('admin.dictionaries.role-types.destroy', $item['role_type_id']) }}" method="POST" class="d-inline" onsubmit="return confirm('متأكد من الحذف؟')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center py-4">لا توجد بيانات</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog"><div class="modal-content">
            <div class="modal-header bg-primary text-white"><h5>إضافة نوع دور</h5><button class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <form method="POST" action="{{ route('admin.dictionaries.role-types.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">الكود</label><input type="text" name="code" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">العربية</label><input type="text" name="name_ar" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">الإنجليزية</label><input type="text" name="name_en" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">الوصف</label><textarea name="description" class="form-control" rows="3"></textarea></div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-primary">إضافة</button></div>
            </form>
        </div></div>
    </div>

    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog"><div class="modal-content">
            <div class="modal-header bg-warning"><h5>تعديل نوع دور</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <form id="editForm" method="POST">
                @csrf @method('PUT')
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">الكود</label><input type="text" name="code" id="e_code" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">العربية</label><input type="text" name="name_ar" id="e_name_ar" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">الإنجليزية</label><input type="text" name="name_en" id="e_name_en" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">الوصف</label><textarea name="description" id="e_desc" class="form-control" rows="3"></textarea></div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-warning">حفظ</button></div>
            </form>
        </div></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function editItem(item) {
    document.getElementById('editForm').action = '/admin/dictionaries/role-types/' + item.role_type_id;
    document.getElementById('e_code').value = item.code || '';
    document.getElementById('e_name_ar').value = item.name_ar || '';
    document.getElementById('e_name_en').value = item.name_en || '';
    document.getElementById('e_desc').value = item.description || '';
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>
@endpush

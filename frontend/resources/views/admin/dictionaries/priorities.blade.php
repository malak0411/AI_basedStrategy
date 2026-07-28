@extends('layouts.app')

@section('title', 'إدارة الأولويات')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>إدارة الأولويات (Priorities)</h3>
        <button class="btn-gold" data-bs-toggle="modal" data-bs-target="#addModal">إضافة أولوية</button>
    </div>

    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif

    <div class="table-responsive card-custom">
        <table class="table table-hover mb-0">
            <thead><tr><th>#</th><th>الكود</th><th>العربية</th><th>الإنجليزية</th><th>المستوى</th><th>اللون</th><th>إجراءات</th></tr></thead>
            <tbody>
                @forelse($items as $item)
                <tr>
                    <td>{{ $item['priority_id'] ?? $loop->iteration }}</td>
                    <td><code>{{ $item['code'] ?? '' }}</code></td>
                    <td>{{ $item['name_ar'] ?? '' }}</td>
                    <td>{{ $item['name_en'] ?? '' }}</td>
                    <td>{{ $item['level'] ?? '' }}</td>
                    <td><span style="background:{{ $item['color_hex'] ?? '#ccc' }};padding:4px 12px;border-radius:4px;">&nbsp;</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" onclick="editItem({{ json_encode($item) }})"><i class="fas fa-edit"></i></button>
                        <form action="{{ route('admin.dictionaries.priorities.destroy', $item['priority_id']) }}" method="POST" class="d-inline" onsubmit="return confirm('متأكد من الحذف؟')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center py-4">لا توجد بيانات</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog"><div class="modal-content">
            <div class="modal-header bg-primary text-white"><h5>إضافة أولوية</h5><button class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <form method="POST" action="{{ route('admin.dictionaries.priorities.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">الكود</label><input type="text" name="code" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">العربية</label><input type="text" name="name_ar" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">الإنجليزية</label><input type="text" name="name_en" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">المستوى</label><input type="number" name="level" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">اللون</label><input type="color" name="color_hex" class="form-control" value="#6c757d"></div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-primary">إضافة</button></div>
            </form>
        </div></div>
    </div>

    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog"><div class="modal-content">
            <div class="modal-header bg-warning"><h5>تعديل أولوية</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <form id="editForm" method="POST">
                @csrf @method('PUT')
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">الكود</label><input type="text" name="code" id="e_code" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">العربية</label><input type="text" name="name_ar" id="e_name_ar" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">الإنجليزية</label><input type="text" name="name_en" id="e_name_en" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">المستوى</label><input type="number" name="level" id="e_level" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">اللون</label><input type="color" name="color_hex" id="e_color" class="form-control"></div>
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
    document.getElementById('editForm').action = '/admin/dictionaries/priorities/' + item.priority_id;
    document.getElementById('e_code').value = item.code || '';
    document.getElementById('e_name_ar').value = item.name_ar || '';
    document.getElementById('e_name_en').value = item.name_en || '';
    document.getElementById('e_level').value = item.level || '';
    document.getElementById('e_color').value = item.color_hex || '#6c757d';
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>
@endpush

@extends('layouts.app')

@section('title', 'الركائز الاستراتيجية')

@push('styles')
<style>
    .pillar-card {
        background: #fff;
        border-radius: 16px;
        padding: 24px;
        height: 100%;
        border: 1px solid #e2e8f0;
        transition: all 0.3s ease;
        position: relative;
        border-right: 5px solid #d4af37;
    }
    .pillar-card:hover {
        box-shadow: 0 8px 25px rgba(0,0,0,0.08);
        transform: translateY(-2px);
    }
    .pillar-card.inactive {
        opacity: 0.6;
        border-right-color: #ccc;
    }
    .pillar-number {
        position: absolute;
        top: 16px;
        left: 16px;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #0a2e5c;
        color: #d4af37;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 18px;
    }
    .pillar-actions {
        position: absolute;
        top: 16px;
        right: 16px;
        display: flex;
        gap: 6px;
    }
    .pillar-actions .btn {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        padding: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3><i class="fas fa-chess-queen ml-2"></i>الركائز الاستراتيجية</h3>
            <p class="text-muted mb-0">الأعمدة الأساسية لتحقيق الرؤية</p>
        </div>
        <button class="btn-gold" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="fas fa-plus"></i> ركيزة جديدة
        </button>
    </div>

    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif

    @if(empty($pillars))
        <div class="card-custom text-center py-5">
            <i class="fas fa-chess-queen fa-4x text-muted mb-3"></i>
            <h5>لا توجد ركائز استراتيجية</h5>
            <p class="text-muted">أضف الركيزة الأولى للبدء</p>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="fas fa-plus"></i> إضافة ركيزة
            </button>
        </div>
    @else
        <div class="row">
            @foreach($pillars as $pillar)
            <div class="col-md-4 mb-4">
                <div class="pillar-card {{ !($pillar['is_active'] ?? true) ? 'inactive' : '' }}">
                    <div class="pillar-number">{{ $pillar['order_index'] ?? $loop->iteration }}</div>
                    <div class="pillar-actions">
                        <button class="btn btn-sm btn-outline-primary" onclick="editPillar({{ json_encode($pillar) }})" title="تعديل">
                            <i class="fas fa-edit"></i>
                        </button>
                        <form action="{{ route('strategic.pillars.destroy', $pillar['id']) }}" method="POST" onsubmit="return confirm('متأكد من حذف هذه الركيزة؟')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" title="حذف"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                    <div class="mt-5 pt-3">
                        <h5 class="fw-bold mb-2">{{ $pillar['name'] ?? $pillar['title'] ?? '' }}</h5>
                        <p class="text-muted small">{{ Str::limit($pillar['description'] ?? '', 120) }}</p>
                        @if(!($pillar['is_active'] ?? true))
                            <span class="badge bg-secondary">غير نشطة</span>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    @endif

    {{-- Modal إضافة --}}
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-plus ml-2"></i>إضافة ركيزة جديدة</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('strategic.pillars.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">اسم الركيزة</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">الوصف</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label">الترتيب</label>
                                <input type="number" name="order_index" class="form-control" value="0" min="0">
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">الحالة</label>
                                <div class="form-check mt-2">
                                    <input type="checkbox" name="is_active" class="form-check-input" value="1" checked>
                                    <label class="form-check-label">نشطة</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-primary">إضافة</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal تعديل --}}
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title"><i class="fas fa-edit ml-2"></i>تعديل الركيزة</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editForm" method="POST">
                    @csrf @method('PUT')
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">اسم الركيزة</label>
                            <input type="text" name="name" id="edit-name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">الوصف</label>
                            <textarea name="description" id="edit-desc" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label">الترتيب</label>
                                <input type="number" name="order_index" id="edit-order" class="form-control" min="0">
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">الحالة</label>
                                <div class="form-check mt-2">
                                    <input type="checkbox" name="is_active" id="edit-active" class="form-check-input" value="1">
                                    <label class="form-check-label">نشطة</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-warning">حفظ التعديلات</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function editPillar(pillar) {
        document.getElementById('editForm').action = '/strategic/pillars/' + pillar.id;
        document.getElementById('edit-name').value = pillar.name || pillar.title || '';
        document.getElementById('edit-desc').value = pillar.description || '';
        document.getElementById('edit-order').value = pillar.order_index || 0;
        document.getElementById('edit-active').checked = pillar.is_active !== false;
        new bootstrap.Modal(document.getElementById('editModal')).show();
    }
</script>
@endpush

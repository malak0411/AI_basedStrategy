@extends('layouts.app')

@section('title', 'الركائز الاستراتيجية')

@push('styles')
<style>
    .pillar-card {
        background: #fff; border-radius: 16px; padding: 24px; height: 100%;
        border: 1px solid #e2e8f0; transition: all 0.3s ease; cursor: pointer;
        border-right: 5px solid #d4af37; position: relative;
    }
    .pillar-card:hover { box-shadow: 0 8px 25px rgba(0,0,0,0.08); transform: translateY(-2px); }
    .pillar-card.inactive { opacity: 0.6; border-right-color: #ccc; }
    .pillar-number {
        width: 40px; height: 40px; border-radius: 50%; background: #0a2e5c; color: #d4af37;
        display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 18px;
    }
    .pillar-actions { position: absolute; top: 16px; left: 16px; display: flex; gap: 6px; }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3><i class="fas fa-chess-queen ml-2"></i>الركائز الاستراتيجية</h3>
            <p class="text-muted mb-0">اضغط على أي ركيزة لعرض أهدافها</p>
        </div>
        <button class="btn-gold" data-bs-toggle="modal" data-bs-target="#addPillarModal">
            <i class="fas fa-plus"></i> ركيزة جديدة
        </button>
    </div>

    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif

    @if(empty($pillars))
    <div class="card-custom text-center py-5">
        <i class="fas fa-chess-queen fa-4x text-muted mb-3"></i>
        <h5>لا توجد ركائز استراتيجية</h5>
        <button class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#addPillarModal">إضافة ركيزة</button>
    </div>
    @else
    <div class="row">
        @foreach($pillars as $pillar)
        @php $active = $pillar['is_active'] ?? true; @endphp
        <div class="col-md-4 mb-4">
            <div class="pillar-card {{ $active ? '' : 'inactive' }}" onclick="window.location='{{ route('strategic.pillars.show', $pillar['id']) }}'">
                <div class="pillar-actions" onclick="event.stopPropagation()">
                    <button class="btn btn-sm btn-outline-primary" onclick="editPillar({{ json_encode($pillar) }})" title="تعديل"><i class="fas fa-edit"></i></button>
                </div>
                <div class="d-flex justify-content-between align-items-start mb-3 mt-4">
                    <div class="pillar-number">{{ $pillar['order_index'] ?? $loop->iteration }}</div>
                    <span class="badge bg-{{ $active ? 'success' : 'secondary' }}">{{ $active ? 'نشط' : 'غير نشط' }}</span>
                </div>
                <h5 class="fw-bold mb-2">{{ $pillar['name'] ?? $pillar['title'] ?? '' }}</h5>
                <p class="text-muted small">{{ Str::limit($pillar['description'] ?? '', 120) }}</p>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    <div class="modal fade" id="addPillarModal" tabindex="-1">
        <div class="modal-dialog"><div class="modal-content">
            <div class="modal-header bg-primary text-white"><h5>إضافة ركيزة</h5><button class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <form method="POST" action="{{ route('strategic.pillars.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">اسم الركيزة</label><input type="text" name="name" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">الوصف</label><textarea name="description" class="form-control" rows="3"></textarea></div>
                    <div class="row">
                        <div class="col-6"><label class="form-label">الترتيب</label><input type="number" name="order_index" class="form-control" value="0"></div>
                        <div class="col-6"><label class="form-label">الحالة</label><div class="form-check mt-2"><input type="checkbox" name="is_active" class="form-check-input" value="1" checked><label class="form-check-label">نشطة</label></div></div>
                    </div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-primary">إضافة</button></div>
            </form>
        </div></div>
    </div>

    <div class="modal fade" id="editPillarModal" tabindex="-1">
        <div class="modal-dialog"><div class="modal-content">
            <div class="modal-header bg-warning"><h5>تعديل ركيزة</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <form id="editPillarForm" method="POST">
                @csrf @method('PUT')
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">اسم الركيزة</label><input type="text" name="name" id="ep_name" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">الوصف</label><textarea name="description" id="ep_desc" class="form-control" rows="3"></textarea></div>
                    <div class="row">
                        <div class="col-6"><label class="form-label">الترتيب</label><input type="number" name="order_index" id="ep_order" class="form-control"></div>
                        <div class="col-6"><label class="form-label">الحالة</label><div class="form-check mt-2"><input type="checkbox" name="is_active" id="ep_active" class="form-check-input" value="1"><label class="form-check-label">نشطة</label></div></div>
                    </div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-warning">حفظ</button></div>
            </form>
        </div></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function editPillar(pillar) {
    document.getElementById('editPillarForm').action = '/strategic/pillars/' + pillar.id;
    document.getElementById('ep_name').value = pillar.name || pillar.title || '';
    document.getElementById('ep_desc').value = pillar.description || '';
    document.getElementById('ep_order').value = pillar.order_index || 0;
    document.getElementById('ep_active').checked = pillar.is_active !== false;
    new bootstrap.Modal(document.getElementById('editPillarModal')).show();
}
</script>
@endpush

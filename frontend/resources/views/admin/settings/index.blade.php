@extends('layouts.app')

@section('title', 'تهيئة النظام')

@push('styles')
<style>
    .settings-table { background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.04); }
    .settings-table th { background: #0a2e5c; color: #fff; font-size: 14px; padding: 14px 16px; }
    .settings-table td { padding: 12px 16px; vertical-align: middle; }
    .toast-notification { position: fixed; top: 20px; left: 20px; z-index: 9999; padding: 12px 20px; border-radius: 8px; color: #fff; font-weight: 600; animation: slideIn 0.3s ease; }
    @keyframes slideIn { from { transform: translateX(-100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3><i class="fas fa-cogs ml-2"></i>إعدادات النظام</h3>
        <button class="btn-gold" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="fas fa-plus"></i> إضافة إعداد
        </button>
    </div>

    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif

    <div class="table-responsive settings-table">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>المفتاح</th>
                    <th>القيمة</th>
                    <th>الوصف</th>
                    <th>آخر تحديث</th>
                    <th style="width:140px;">إجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($configs as $config)
                <tr id="row-{{ $config['config_key'] }}">
                    <td>{{ $config['config_id'] ?? $loop->iteration }}</td>
                    <td><code>{{ $config['config_key'] }}</code></td>
                    <td>
                        <span class="value-text" id="display-{{ $config['config_key'] }}">{{ $config['config_value'] }}</span>
                        <input type="text" class="form-control form-control-sm d-none" id="input-{{ $config['config_key'] }}" value="{{ $config['config_value'] }}" style="width:150px;">
                    </td>
                    <td><small class="text-muted">{{ $config['description'] ?? '' }}</small></td>
                    <td><small>{{ \Carbon\Carbon::parse($config['updated_at'] ?? '')->format('Y-m-d H:i') }}</small></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary edit-btn" onclick="toggleEdit('{{ $config['config_key'] }}')">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-success d-none save-btn" onclick="saveConfig('{{ $config['config_key'] }}')">
                            <i class="fas fa-check"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-secondary d-none cancel-btn" onclick="cancelEdit('{{ $config['config_key'] }}')">
                            <i class="fas fa-times"></i>
                        </button>
                        {{-- زر الحذف --}}
                        <form action="/admin/settings/{{ $config['config_key'] }}" method="POST" class="d-inline" onsubmit="return confirm('متأكد من حذف {{ $config['config_key'] }}؟')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center py-4">لا توجد إعدادات</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Modal إضافة إعداد --}}
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-plus ml-2"></i>إضافة إعداد جديد</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="/admin/settings" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">المفتاح</label>
                            <input type="text" name="config_key" class="form-control" required placeholder="مثال: app_name">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">القيمة</label>
                            <input type="text" name="config_value" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">الوصف</label>
                            <input type="text" name="description" class="form-control">
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
</div>
@endsection

@push('scripts')
<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const originalValues = {};

    function toggleEdit(key) {
        document.getElementById('display-' + key).classList.add('d-none');
        document.getElementById('input-' + key).classList.remove('d-none');
        document.querySelector('.edit-btn[data-key="' + key + '"]')?.classList.add('d-none');
        document.querySelector('.save-btn[data-key="' + key + '"]')?.classList.remove('d-none');
        document.querySelector('.cancel-btn[data-key="' + key + '"]')?.classList.remove('d-none');
        if (!originalValues[key]) originalValues[key] = document.getElementById('input-' + key).value;
        document.getElementById('input-' + key).focus();
    }

    function cancelEdit(key) {
        document.getElementById('input-' + key).value = originalValues[key] || '';
        document.getElementById('display-' + key).textContent = originalValues[key] || '';
        document.getElementById('display-' + key).classList.remove('d-none');
        document.getElementById('input-' + key).classList.add('d-none');
        document.querySelector('.edit-btn[data-key="' + key + '"]')?.classList.remove('d-none');
        document.querySelector('.save-btn[data-key="' + key + '"]')?.classList.add('d-none');
        document.querySelector('.cancel-btn[data-key="' + key + '"]')?.classList.add('d-none');
        delete originalValues[key];
    }

    async function saveConfig(key) {
        const input = document.getElementById('input-' + key);
        const value = input.value;
        try {
            const r = await fetch('/admin/settings/update', {
                method: 'POST',
                headers: {'Content-Type':'application/json','X-CSRF-TOKEN':csrfToken},
                body: JSON.stringify({config_key: key, config_value: value})
            });
            const d = await r.json();
            if (d.success) {
                document.getElementById('display-' + key).textContent = value;
                cancelEdit(key);
                showToast('✅ تم التحديث');
            } else { showToast('❌ ' + (d.detail || 'فشل')); }
        } catch(e) { showToast('❌ خطأ في الاتصال'); }
    }

    function showToast(msg) {
        const t = document.createElement('div'); t.className = 'toast-notification';
        t.style.background = msg.includes('✅') ? '#38a169' : '#e53e3e';
        t.textContent = msg; document.body.appendChild(t);
        setTimeout(() => t.remove(), 3000);
    }
</script>
@endpush

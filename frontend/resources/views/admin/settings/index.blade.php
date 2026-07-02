@extends('layouts.app')

@section('title', 'تهيئة النظام')

@push('styles')
<style>
    .config-card {
        background: #fff;
        border-radius: 16px;
        padding: 24px;
        margin-bottom: 20px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    }
    .config-card .section-title {
        font-size: 18px;
        font-weight: 700;
        color: var(--primary-dark);
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid var(--gold);
    }
    .config-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 0;
        border-bottom: 1px solid #f0f0f0;
    }
    .config-item:last-child { border-bottom: none; }
    .config-label {
        font-weight: 600;
        color: var(--text-dark);
    }
    .config-desc {
        font-size: 12px;
        color: #888;
        margin-top: 2px;
    }
    .config-value {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .config-value .value-text {
        font-weight: 500;
        color: var(--primary);
        min-width: 120px;
        text-align: left;
    }
    .config-value input {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 8px 12px;
        text-align: left;
        direction: ltr;
        width: 200px;
    }
    .btn-save {
        background: var(--gold);
        color: var(--primary-dark);
        border: none;
        padding: 6px 16px;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }
    .btn-save:hover { background: var(--gold-dark); color: #fff; }
    .badge-status {
        font-size: 11px;
        padding: 4px 10px;
        border-radius: 20px;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>
            <i class="fas fa-cogs ml-2 text-primary"></i>
            تهيئة النظام
        </h3>
        <span class="text-muted small">
            <i class="far fa-clock ml-1"></i>
            آخر تحديث: {{ date('Y-m-d H:i:s') }}
        </span>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(isset($error))
        <div class="alert alert-warning">{{ $error }}</div>
    @endif

    @if(empty($configs))
        <div class="card-custom text-center py-5">
            <i class="fas fa-cogs fa-3x text-muted mb-3"></i>
            <h5>لا توجد إعدادات نظام</h5>
            <p class="text-muted">لم يتم تحميل أي إعدادات</p>
        </div>
    @else
        <div class="row">
            {{-- ========== الإعدادات العامة ========== --}}
            @if(!empty($configs['general']))
            <div class="col-lg-6">
                <div class="config-card">
                    <div class="section-title">
                        <i class="fas fa-building ml-2"></i>
                        الإعدادات العامة
                    </div>
                    @foreach($configs['general'] as $key => $config)
                    <div class="config-item">
                        <div>
                            <div class="config-label">
                                @php
                                    $labels = [
                                        'sector_type' => 'نوع القطاع',
                                        'ministry_name' => 'اسم الوزارة',
                                        'timezone' => 'المنطقة الزمنية',
                                    ];
                                @endphp
                                {{ $labels[$key] ?? $key }}
                            </div>
                            <div class="config-desc">{{ $config['description'] ?? '' }}</div>
                        </div>
                        <div class="config-value">
                            <span class="value-text" id="display-{{ $key }}">{{ $config['config_value'] }}</span>
                            <input type="text" 
                                   class="form-control-sm d-none" 
                                   id="input-{{ $key }}" 
                                   value="{{ $config['config_value'] }}"
                                   data-key="{{ $key }}">
                            <button class="btn-save btn-sm edit-btn" 
                                    data-key="{{ $key }}"
                                    onclick="toggleEdit('{{ $key }}')">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn-save btn-sm d-none save-btn" 
                                    data-key="{{ $key }}"
                                    onclick="saveConfig('{{ $key }}')">
                                <i class="fas fa-save"></i> حفظ
                            </button>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- ========== الإعدادات المالية ========== --}}
            @if(!empty($configs['financial']))
            <div class="col-lg-6">
                <div class="config-card">
                    <div class="section-title">
                        <i class="fas fa-money-bill-wave ml-2"></i>
                        الإعدادات المالية
                    </div>
                    @foreach($configs['financial'] as $key => $config)
                    <div class="config-item">
                        <div>
                            <div class="config-label">
                                @php
                                    $labels = [
                                        'currency' => 'العملة الأساسية',
                                        'fiscal_year_start' => 'بداية السنة المالية',
                                    ];
                                @endphp
                                {{ $labels[$key] ?? $key }}
                            </div>
                            <div class="config-desc">{{ $config['description'] ?? '' }}</div>
                        </div>
                        <div class="config-value">
                            <span class="value-text" id="display-{{ $key }}">{{ $config['config_value'] }}</span>
                            <input type="text" 
                                   class="form-control-sm d-none" 
                                   id="input-{{ $key }}" 
                                   value="{{ $config['config_value'] }}"
                                   data-key="{{ $key }}">
                            <button class="btn-save btn-sm edit-btn" 
                                    data-key="{{ $key }}"
                                    onclick="toggleEdit('{{ $key }}')">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn-save btn-sm d-none save-btn" 
                                    data-key="{{ $key }}"
                                    onclick="saveConfig('{{ $key }}')">
                                <i class="fas fa-save"></i> حفظ
                            </button>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- ========== الإعدادات التشغيلية ========== --}}
            @if(!empty($configs['operational']))
            <div class="col-lg-6">
                <div class="config-card">
                    <div class="section-title">
                        <i class="fas fa-cog ml-2"></i>
                        الإعدادات التشغيلية
                    </div>
                    @foreach($configs['operational'] as $key => $config)
                    <div class="config-item">
                        <div>
                            <div class="config-label">
                                @php
                                    $labels = [
                                        'default_priority' => 'الأولوية الافتراضية',
                                    ];
                                @endphp
                                {{ $labels[$key] ?? $key }}
                            </div>
                            <div class="config-desc">{{ $config['description'] ?? '' }}</div>
                        </div>
                        <div class="config-value">
                            <span class="value-text" id="display-{{ $key }}">{{ $config['config_value'] }}</span>
                            <select class="form-control-sm d-none" 
                                    id="input-{{ $key }}" 
                                    data-key="{{ $key }}">
                                <option value="high" {{ $config['config_value'] == 'high' ? 'selected' : '' }}>عالية</option>
                                <option value="medium" {{ $config['config_value'] == 'medium' ? 'selected' : '' }}>متوسطة</option>
                                <option value="low" {{ $config['config_value'] == 'low' ? 'selected' : '' }}>منخفضة</option>
                            </select>
                            <button class="btn-save btn-sm edit-btn" 
                                    data-key="{{ $key }}"
                                    onclick="toggleEdit('{{ $key }}')">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn-save btn-sm d-none save-btn" 
                                    data-key="{{ $key }}"
                                    onclick="saveConfig('{{ $key }}')">
                                <i class="fas fa-save"></i> حفظ
                            </button>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    function toggleEdit(key) {
        const display = document.getElementById('display-' + key);
        const input = document.getElementById('input-' + key);
        const editBtn = document.querySelector('.edit-btn[data-key="' + key + '"]');
        const saveBtn = document.querySelector('.save-btn[data-key="' + key + '"]');

        display.classList.toggle('d-none');
        input.classList.toggle('d-none');
        editBtn.classList.toggle('d-none');
        saveBtn.classList.toggle('d-none');
        
        if (!input.classList.contains('d-none')) {
            input.focus();
        }
    }

    async function saveConfig(key) {
        const input = document.getElementById('input-' + key);
        const value = input.value || input.options[input.selectedIndex]?.value;
        
        try {
            const response = await fetch('/admin/settings/update', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    config_key: key,
                    config_value: value,
                }),
            });

            const data = await response.json();

            if (data.success || data.status === 'success') {
                // تحديث العرض
                const display = document.getElementById('display-' + key);
                display.textContent = value;
                
                // إخفاء input وإظهار display
                toggleEdit(key);
                
                // إظهار رسالة نجاح
                showToast('success', 'تم تحديث الإعداد بنجاح');
            } else {
                showToast('error', data.error || data.detail || 'فشل التحديث');
            }
        } catch (error) {
            showToast('error', 'حدث خطأ في الاتصال');
            console.error('Error:', error);
        }
    }

    function showToast(type, message) {
        const colors = {
            success: '#38a169',
            error: '#e53e3e',
        };
        
        const toast = document.createElement('div');
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            left: 20px;
            background: ${colors[type] || '#333'};
            color: #fff;
            padding: 12px 24px;
            border-radius: 8px;
            z-index: 9999;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            animation: slideIn 0.3s ease;
        `;
        toast.textContent = message;
        document.body.appendChild(toast);
        
        setTimeout(() => toast.remove(), 3000);
    }
</script>
@endpush

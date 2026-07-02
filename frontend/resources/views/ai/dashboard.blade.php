@extends('layouts.app')

@section('title', 'لوحة الذكاء الاصطناعي')

@push('styles')
<style>
    .ai-stat-card {
        background: linear-gradient(135deg, #1a1a2e, #16213e);
        color: #fff;
        border-radius: 16px;
        padding: 24px;
        margin-bottom: 20px;
    }
    .ai-stat-card .number {
        font-size: 36px;
        font-weight: 800;
        color: var(--gold);
    }
    .ai-stat-card .label {
        color: rgba(255,255,255,0.7);
        font-size: 14px;
    }
    .model-badge {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    .model-active { background: #38a169; color: #fff; }
    .model-training { background: #ed8936; color: #fff; }
    .model-inactive { background: #718096; color: #fff; }
    .risk-bar {
        height: 8px;
        border-radius: 4px;
        margin-top: 8px;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <h3 class="mb-4"><i class="fas fa-brain ml-2 text-warning"></i>لوحة الذكاء الاصطناعي</h3>

    {{-- إحصائيات النماذج --}}
    <div class="row">
        <div class="col-md-3">
            <div class="ai-stat-card">
                <div class="number">{{ $dashboard['total_models'] ?? 5 }}</div>
                <div class="label">النماذج النشطة</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="ai-stat-card">
                <div class="number">{{ $dashboard['total_predictions'] ?? 0 }}</div>
                <div class="label">التنبؤات</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="ai-stat-card">
                <div class="number">{{ $dashboard['total_recommendations'] ?? 0 }}</div>
                <div class="label">التوصيات</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="ai-stat-card">
                <div class="number">{{ $dashboard['accuracy'] ?? 0 }}%</div>
                <div class="label">نسبة الدقة</div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- النماذج --}}
        <div class="col-lg-6 mb-4">
            <div class="card-custom">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5><i class="fas fa-microchip ml-2"></i>النماذج</h5>
                    <a href="{{ route('ai.models') }}" class="btn btn-sm btn-outline-primary">عرض الكل</a>
                </div>
                @if(!empty($dashboard['models']))
                    @foreach($dashboard['models'] as $model)
                    <div class="d-flex justify-content-between align-items-center mb-2 p-2 border rounded">
                        <div>
                            <strong>{{ $model['name'] ?? '' }}</strong>
                            <br><small class="text-muted">{{ $model['description'] ?? '' }}</small>
                        </div>
                        <span class="model-badge {{ ($model['status'] ?? '') == 'active' ? 'model-active' : 'model-inactive' }}">
                            {{ ($model['status'] ?? '') == 'active' ? 'نشط' : 'غير نشط' }}
                        </span>
                    </div>
                    @endforeach
                @else
                    <p class="text-muted">لا توجد نماذج</p>
                @endif
            </div>
        </div>

        {{-- أكثر المهام خطورة --}}
        <div class="col-lg-6 mb-4">
            <div class="card-custom">
                <h5 class="mb-3"><i class="fas fa-exclamation-triangle text-danger ml-2"></i>أكثر المهام خطورة</h5>
                @if(!empty($dashboard['risky_tasks']))
                    @foreach($dashboard['risky_tasks'] as $task)
                    <div class="border rounded p-3 mb-2">
                        <div class="d-flex justify-content-between">
                            <strong>{{ $task['task_name'] ?? '' }}</strong>
                            <span class="badge bg-danger">{{ $task['delay_probability'] ?? 0 }}% احتمال تأخير</span>
                        </div>
                        <div class="risk-bar bg-{{ ($task['delay_probability'] ?? 0) > 70 ? 'danger' : 'warning' }}" 
                             style="width: {{ $task['delay_probability'] ?? 0 }}%; background: {{ ($task['delay_probability'] ?? 0) > 70 ? '#e53e3e' : '#ed8936' }};"></div>
                    </div>
                    @endforeach
                @else
                    <p class="text-muted">لا توجد مهام خطرة حالياً</p>
                @endif
            </div>
        </div>
    </div>

    {{-- آخر التوصيات --}}
    <div class="row">
        <div class="col-12">
            <div class="card-custom">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5><i class="fas fa-lightbulb text-warning ml-2"></i>آخر التوصيات</h5>
                    <a href="{{ route('ai.recommendations') }}" class="btn btn-sm btn-outline-primary">عرض الكل</a>
                </div>
                @if(!empty($dashboard['recent_recommendations']))
                    @foreach($dashboard['recent_recommendations'] as $rec)
                    <div class="border rounded p-3 mb-2">
                        <div class="d-flex justify-content-between">
                            <strong>{{ $rec['title'] ?? '' }}</strong>
                            <small class="text-muted">{{ $rec['created_at'] ?? '' }}</small>
                        </div>
                        <p class="text-muted mb-0 mt-1">{{ Str::limit($rec['description'] ?? '', 120) }}</p>
                    </div>
                    @endforeach
                @else
                    <p class="text-muted">لا توجد توصيات</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

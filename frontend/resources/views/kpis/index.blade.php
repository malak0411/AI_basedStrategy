@extends('layouts.app')

@section('title', 'مؤشرات الأداء')

@push('styles')
<style>
    .kpi-card {
        border-radius: 16px;
        padding: 24px;
        background: #fff;
        border: 1px solid #e2e8f0;
        transition: all 0.3s ease;
        position: relative;
        height: 100%;
    }
    .kpi-card:hover { box-shadow: 0 8px 25px rgba(0,0,0,0.08); transform: translateY(-2px); }
    .kpi-card .kpi-value { font-size: 42px; font-weight: 800; }
    .kpi-card .kpi-target { font-size: 14px; color: #888; }
    .kpi-card .kpi-unit { font-size: 16px; color: #aaa; }
    .kpi-card.on-target { border-right: 5px solid #38a169; }
    .kpi-card.below-target { border-right: 5px solid #e53e3e; }
    .progress-ring { margin: 10px auto; }
    .stat-mini { text-align: center; padding: 16px; border-radius: 12px; background: #f8fafc; }
    .stat-mini .number { font-size: 24px; font-weight: 800; }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3><i class="fas fa-chart-line ml-2"></i>مؤشرات الأداء الرئيسية</h3>
            <p class="text-muted mb-0">متابعة وتحديث مؤشرات الأداء</p>
        </div>
        <a href="{{ route('kpis.create') }}" class="btn-gold">
            <i class="fas fa-plus"></i> مؤشر جديد
        </a>
    </div>

    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif

    {{-- إحصائيات سريعة --}}
    <div class="row mb-4">
        <div class="col-md-3"><div class="stat-mini"><div class="number text-primary">{{ $total ?? 0 }}</div><small>إجمالي المؤشرات</small></div></div>
        <div class="col-md-3"><div class="stat-mini"><div class="number text-success">{{ $onTarget ?? 0 }}</div><small>محققة المستهدف</small></div></div>
        <div class="col-md-3"><div class="stat-mini"><div class="number text-danger">{{ $belowTarget ?? 0 }}</div><small>دون المستهدف</small></div></div>
        <div class="col-md-3"><div class="stat-mini"><div class="number text-info">{{ $total > 0 ? round(($onTarget/$total)*100) : 0 }}%</div><small>نسبة الإنجاز</small></div></div>
    </div>

    @if(empty($kpis))
        <div class="card-custom text-center py-5">
            <i class="fas fa-chart-bar fa-4x text-muted mb-3"></i>
            <h5>لا توجد مؤشرات أداء</h5>
            <a href="{{ route('kpis.create') }}" class="btn btn-primary mt-3">إضافة أول مؤشر</a>
        </div>
    @else
        <div class="row">
            @foreach($kpis as $kpi)
            @php
                $current = $kpi['current_value'] ?? 0;
                $target = $kpi['target_value'] ?? 100;
                $pct = $target > 0 ? min(round(($current / $target) * 100), 100) : 0;
                $onTarget = $current >= $target;
            @endphp
            <div class="col-md-4 mb-4">
                <div class="kpi-card {{ $onTarget ? 'on-target' : 'below-target' }}">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <h5 class="fw-bold">{{ $kpi['name'] ?? $kpi['title'] ?? '' }}</h5>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">⋯</button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="{{ route('kpis.show', $kpi['id']) }}"><i class="fas fa-eye"></i> تفاصيل</a></li>
                                <li><a class="dropdown-item" href="{{ route('kpis.measurements', $kpi['id']) }}"><i class="fas fa-ruler"></i> القياسات</a></li>
                                <li><a class="dropdown-item" href="{{ route('kpis.edit', $kpi['id']) }}"><i class="fas fa-edit"></i> تعديل</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form action="{{ route('kpis.destroy', $kpi['id']) }}" method="POST" onsubmit="return confirm('متأكد من الحذف؟')">
                                        @csrf @method('DELETE')
                                        <button class="dropdown-item text-danger"><i class="fas fa-trash"></i> حذف</button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="text-center my-3">
                        <div class="kpi-value {{ $onTarget ? 'text-success' : 'text-danger' }}">{{ $current }}<span class="kpi-unit"> {{ $kpi['unit'] ?? '%' }}</span></div>
                        <div class="kpi-target">المستهدف: {{ $target }} {{ $kpi['unit'] ?? '%' }}</div>
                    </div>
                    <div class="progress" style="height: 10px; border-radius: 5px;">
                        <div class="progress-bar {{ $onTarget ? 'bg-success' : ($pct > 60 ? 'bg-warning' : 'bg-danger') }}" style="width: {{ $pct }}%"></div>
                    </div>
                    <small class="text-muted mt-2 d-block text-center">{{ $pct }}% من المستهدف</small>
                </div>
            </div>
            @endforeach
        </div>
    @endif
</div>
@endsection

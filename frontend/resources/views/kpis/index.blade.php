@extends('layouts.app')

@section('title', 'مؤشرات الأداء')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3><i class="fas fa-chart-line ml-2"></i>مؤشرات الأداء</h3>
        <a href="{{ route('kpis.create') }}" class="btn-gold">
            <i class="fas fa-plus"></i> مؤشر جديد
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(empty($kpis))
        <div class="card-custom text-center py-5">
            <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
            <h5>لا توجد مؤشرات أداء</h5>
            <a href="{{ route('kpis.create') }}" class="btn btn-primary mt-3">إضافة مؤشر</a>
        </div>
    @else
        <div class="row">
            @foreach($kpis as $kpi)
            <div class="col-md-4 mb-4">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h5>{{ $kpi['name'] ?? $kpi['title'] ?? 'مؤشر' }}</h5>
                            <div class="number">{{ $kpi['current_value'] ?? 0 }}%</div>
                            <small class="text-muted">المستهدف: {{ $kpi['target_value'] ?? 0 }}%</small>
                        </div>
                        <div class="icon"><i class="fas fa-chart-line"></i></div>
                    </div>
                    <div class="progress mt-3" style="height: 8px;">
                        @php $val = min(($kpi['current_value'] ?? 0), 100); @endphp
                        <div class="progress-bar bg-{{ $val >= 80 ? 'success' : 'warning' }}" style="width: {{ $val }}%"></div>
                    </div>
                    <div class="d-flex justify-content-between mt-3">
                        <a href="{{ route('kpis.show', $kpi['id']) }}" class="btn btn-sm btn-outline-info">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="{{ route('kpis.measurements', $kpi['id']) }}" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-ruler"></i> القياسات
                        </a>
                        <a href="{{ route('kpis.edit', $kpi['id']) }}" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-edit"></i>
                        </a>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    @endif
</div>
@endsection

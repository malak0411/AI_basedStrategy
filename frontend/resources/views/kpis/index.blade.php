@extends('layouts.app')

@section('title', 'مؤشرات الأداء')

@section('content')
<div class="container-fluid">
    <h3><i class="fas fa-chart-line ml-2"></i>مؤشرات الأداء</h3>

    @if(empty($kpis))
        <div class="card-custom text-center py-5 mt-4">
            <p>لا توجد مؤشرات أداء حالياً</p>
        </div>
    @else
        <div class="row mt-4">
            @foreach($kpis as $kpi)
                <div class="col-md-4 mb-3">
                    <div class="stat-card">
                        <h5>{{ $kpi['name'] ?? $kpi['title'] ?? '' }}</h5>
                        <div class="number">{{ $kpi['current_value'] ?? 0 }}%</div>
                        <small>المستهدف: {{ $kpi['target_value'] ?? 0 }}%</small>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection

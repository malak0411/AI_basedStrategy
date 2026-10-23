@extends('layouts.app')

@section('title', 'التقرير الاستراتيجي')

@push('styles')
<style>
    .kpi-card { border-radius: 12px; border: 1px solid #e2e8f0; background: #fff; padding: 15px; text-align: center; }
    .tree-level-1 { border-right: 4px solid #3182ce; margin-bottom: 15px; }
    .tree-level-2 { border-right: 3px solid #38a169; margin-right: 20px; margin-bottom: 10px; background: #f8fafc; }
    @media print {
        .no-print { display: none !important; }
        .collapse { display: block !important; height: auto !important; }
        .accordion-button::after { display: none !important; }
        .card { border: none !important; box-shadow: none !important; }
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-4 py-4" dir="rtl">
    
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <h3 class="fw-bold"><i class="fas fa-sitemap text-primary ml-2"></i> التقرير الاستراتيجي العام</h3>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-outline-secondary"><i class="fas fa-print"></i> طباعة</button>
            <a href="{{ route('reports.index') }}" class="btn btn-secondary">عودة</a>
        </div>
    </div>

    <div class="row g-3 mb-5">
        @foreach(['pillars'=>'الركائز', 'goals'=>'الأهداف', 'programs'=>'البرامج', 'initiatives'=>'المبادرات', 'major_tasks'=>'المهام الرئيسية', 'op_tasks'=>'المهام التشغيلية', 'completed_tasks'=>'المهام المكتملة', 'in_progress_tasks'=>'قيد التنفيذ'] as $key => $label)
        <div class="col-md-3">
            <div class="kpi-card">
                <h6 class="text-muted">{{ $label }}</h6>
                <h3 class="fw-bold text-navy">{{ $general[$key] ?? 0 }}</h3>
            </div>
        </div>
        @endforeach
    </div>

    <div class="accordion" id="pillarAccordion">
        @foreach($tree as $pillar)
        <div class="accordion-item border-0 mb-4 shadow-sm rounded tree-level-1">
            <h2 class="accordion-header bg-light">
                <button class="accordion-button fw-bold fs-5 text-navy" type="button" data-bs-toggle="collapse" data-bs-target="#pillar{{ $pillar['id'] }}">
                    <i class="fas fa-flag-checkered text-primary ml-3"></i> الركيزة: {{ $pillar['name'] }}
                </button>
            </h2>
            <div id="pillar{{ $pillar['id'] }}" class="accordion-collapse collapse show">
                <div class="accordion-body p-3 bg-white">
                    @foreach($pillar['goals'] as $goal)
                    <div class="accordion tree-level-2">
                        <div class="accordion-item border-0 shadow-sm">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed fw-bold text-success" type="button" data-bs-toggle="collapse" data-bs-target="#goal{{ $goal['id'] }}">
                                    <i class="fas fa-bullseye ml-2"></i> الهدف: {{ $goal['name'] }}
                                </button>
                            </h2>
                            <div id="goal{{ $goal['id'] }}" class="accordion-collapse collapse">
                                <div class="accordion-body p-2">
                                    @foreach($goal['programs'] as $prog)
                                    <div class="card mb-3 border-0 shadow-sm">
                                        <div class="card-header bg-primary text-white fw-bold d-flex justify-content-between">
                                            <span><i class="fas fa-layer-group ml-2"></i> {{ $prog['name'] }} <span class="badge bg-light text-dark ms-2">{{ $prog['status'] }}</span></span>
                                            <span class="col-budget">الميزانية: {{ number_format($prog['allocated'], 2) }} ريال</span>
                                        </div>
                                        <div class="card-body">
                                            @foreach($prog['initiatives'] as $init)
                                            <div class="mb-3 ps-3 border-start border-3 border-info">
                                                <h6 class="fw-bold text-info d-flex justify-content-between">
                                                    <span><i class="fas fa-flag ml-1"></i> {{ $init['name'] }} <span class="badge bg-secondary small">{{ $init['status'] }}</span></span>
                                                    <span class="col-budget small">مخصص: {{ number_format($init['allocated'], 2) }}</span>
                                                </h6>
                                                @foreach($init['major_tasks'] as $mt)
                                                <div class="ms-4 mt-2">
                                                    <div class="text-muted fw-bold d-flex justify-content-between">
                                                        <span><i class="fas fa-tasks ml-1"></i> {{ $mt['name'] }}</span>
                                                        <span class="col-tasks small text-primary">المهام: {{ $mt['completed_tasks'] }}/{{ $mt['total_tasks'] }}</span>
                                                    </div>
                                                    <ul class="list-group list-group-flush small ms-3 mt-1">
                                                        @foreach($mt['op_tasks'] as $ot)
                                                        <li class="list-group-item d-flex justify-content-between py-1">
                                                            <span>{{ $ot['title'] }}</span>
                                                            <span class="badge bg-light text-dark">{{ $ot['status'] }}</span>
                                                        </li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                                @endforeach
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection

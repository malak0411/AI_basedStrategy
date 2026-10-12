@extends('layouts.app')


@section('title', 'الركائز الاستراتيجية')


@push('styles')
<style>
    .pillar-card {
        background: #fff; border-radius: 16px; padding: 24px; height: 100%;
        border: 1px solid #e2e8f0; transition: all 0.3s ease; cursor: pointer;
        border-right: 5px solid #dd6b20;
        position: relative;
    }
    .pillar-card:hover { box-shadow: 0 8px 25px rgba(0,0,0,0.08); transform: translateY(-2px); }


    .pillar-order {
        position: absolute; top: 12px; left: 12px;
        width: 32px; height: 32px; border-radius: 50%;
        background: #dd6b20; color: #fff;
        display: flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: 14px;
    }


    .progress-bar-custom { height: 6px; background: #edf2f7; border-radius: 3px; overflow: hidden; }
    .progress-bar-fill { height: 100%; background: linear-gradient(90deg, #dd6b20, #f6ad55); }
    .progress-bar-kpi { height: 6px; background: #edf2f7; border-radius: 3px; overflow: hidden; }
    .progress-bar-kpi-fill { height: 100%; background: linear-gradient(90deg, #805ad5, #b794f4); }


    .kpi-badge {
        display: inline-block; padding: 2px 8px; border-radius: 10px;
        font-size: 10px; font-weight: 600; margin: 1px;
    }
    .kpi-achieved  { background: #c6f6d5; color: #22543d; }
    .kpi-at-risk   { background: #fefcbf; color: #744210; }
    .kpi-no-data   { background: #e2e8f0; color: #4a5568; }
</style>
@endpush


@section('content')
<div class="container-fluid px-4">


    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3><i class="fas fa-layer-group ml-2"></i>الركائز الاستراتيجية</h3>
            <p class="text-muted mb-0">اضغط على أي ركيزة لعرض أهدافها وتفاصيلها</p>
        </div>
        <a href="{{ route('strategic.pillars.create') }}" class="btn-gold">
            <i class="fas fa-plus"></i> ركيزة جديدة
        </a>
    </div>


    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif


    @if(empty($pillars))
        <div class="card-custom text-center py-5"><h5>لا توجد ركائز استراتيجية</h5></div>
    @else
    <div class="row">
        @foreach($pillars as $pillar)
        @php
            $progress    = (int) ($pillar['progress'] ?? 0);
            $kpiProgress = (int) ($pillar['kpi_progress'] ?? 0);
        @endphp
        <div class="col-md-4 mb-4">
            <div class="pillar-card" onclick="window.location='{{ route('strategic.pillars.show', $pillar['id']) }}'">

                <div class="d-flex justify-content-end mb-2" onclick="event.stopPropagation()">
                    <a href="{{ route('strategic.pillars.edit', $pillar['id']) }}"
                       class="btn btn-sm btn-outline-primary" title="تعديل">
                        <i class="fas fa-edit"></i>
                    </a>
                </div>


                <h5 class="fw-bold mb-2 mt-2">{{ $pillar['name'] ?? '' }}</h5>
                <p class="text-muted small mb-3">{{ Str::limit($pillar['description'] ?? '', 120) }}</p>


                <div class="mb-3">
                    <div class="d-flex justify-content-between small mb-1">
                        <span class="text-muted">تقدم الأهداف</span>
                        <strong style="color:#dd6b20;">{{ $progress }}%</strong>
                    </div>
                    <div class="progress-bar-custom">
                        <div class="progress-bar-fill" style="width: {{ $progress }}%"></div>
                    </div>
                </div>


                <div class="mb-3">
                    <div class="d-flex justify-content-between small mb-1">
                        <span class="text-muted">تحقيق المؤشرات</span>
                        <strong style="color: #805ad5;">{{ $kpiProgress }}%</strong>
                    </div>
                    <div class="progress-bar-kpi">
                        <div class="progress-bar-kpi-fill" style="width: {{ min($kpiProgress, 100) }}%"></div>
                    </div>
                </div>


                <div class="mb-2">
                    @if(($pillar['kpis_achieved'] ?? 0) > 0)
                        <span class="kpi-badge kpi-achieved">✓ {{ $pillar['kpis_achieved'] }} محقق</span>
                    @endif
                    @if(($pillar['kpis_at_risk'] ?? 0) > 0)
                        <span class="kpi-badge kpi-at-risk">⚠ {{ $pillar['kpis_at_risk'] }} معرض للخطر</span>
                    @endif
                    @if(($pillar['kpis_count'] ?? 0) === 0)
                        <span class="kpi-badge kpi-no-data">لا توجد مؤشرات</span>
                    @endif
                </div>


                <div class="row small text-muted border-top pt-2 mt-2">
                    <div class="col-3 text-center">
                        <div class="fw-bold" style="color:#e53e3e; font-size: 16px;">{{ $pillar['goals_count'] ?? 0 }}</div>
                        <div style="font-size: 10px;">هدف</div>
                    </div>
                    <div class="col-3 text-center">
                        <div class="fw-bold text-primary" style="font-size: 16px;">{{ $pillar['programs_count'] ?? 0 }}</div>
                        <div style="font-size: 10px;">برنامج</div>
                    </div>
                    <div class="col-3 text-center">
                        <div class="fw-bold text-info" style="font-size: 16px;">{{ $pillar['initiatives_count'] ?? 0 }}</div>
                        <div style="font-size: 10px;">مبادرة</div>
                    </div>
                    <div class="col-3 text-center">
                        <div class="fw-bold" style="color:#805ad5; font-size: 16px;">{{ $pillar['kpis_count'] ?? 0 }}</div>
                        <div style="font-size: 10px;">مؤشر</div>
                    </div>
                </div>


            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>
@endsection

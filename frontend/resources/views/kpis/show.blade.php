@extends('layouts.app')

@section('title', 'تفاصيل المؤشر')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3><i class="fas fa-chart-line ml-2"></i>{{ $kpi['name'] ?? '' }}</h3>
            <p class="text-muted mb-0">{{ $kpi['description'] ?? '' }}</p>
        </div>
        <div>
            <a href="{{ route('kpis.edit', $kpi['kpi_id']) }}" class="btn btn-outline-primary">
                <i class="fas fa-edit"></i> تعديل
            </a>
            <a href="{{ route('kpis.measurements.create', $kpi['kpi_id']) }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> إضافة قياس
            </a>
            <a href="{{ route('kpis.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-right"></i> العودة
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card-custom text-center">
                <div class="card-body">
                    <h6 class="text-muted">التصنيف</h6>
                    <h5>{{ $kpi['category'] ?? 'غير محدد' }}</h5>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-custom text-center">
                <div class="card-body">
                    <h6 class="text-muted">وحدة القياس</h6>
                    <h5>{{ $kpi['unit'] ?? 'غير محدد' }}</h5>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-custom text-center">
                <div class="card-body">
                    <h6 class="text-muted">الهدف الاستراتيجي</h6>
                    <h5>{{ $kpi['goal']['title'] ?? 'غير مرتبط' }}</h5>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card-custom text-center border-primary">
                <div class="card-body">
                    <h6 class="text-muted">القيمة الحالية</h6>
                    <h2 class="text-primary">
                        @if(isset($kpi['current_value']))
                            {{ number_format($kpi['current_value'], 2) }} {{ $kpi['unit'] ?? '' }}
                        @else
                            --
                        @endif
                    </h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-custom text-center border-success">
                <div class="card-body">
                    <h6 class="text-muted">القيمة المستهدفة</h6>
                    <h2 class="text-success">
                        @if(isset($kpi['goal_kpi']['target_value']))
                            {{ number_format($kpi['goal_kpi']['target_value'], 2) }} {{ $kpi['unit'] ?? '' }}
                        @else
                            --
                        @endif
                    </h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-custom text-center border-info">
                <div class="card-body">
                    <h6 class="text-muted">نسبة الإنجاز</h6>
                    <h2 class="text-info">
                        @if(isset($kpi['achievement_percentage']))
                            {{ round($kpi['achievement_percentage']) }}%
                        @else
                            --
                        @endif
                    </h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-custom text-center border-warning">
                <div class="card-body">
                    <h6 class="text-muted">القيمة الأساسية</h6>
                    <h2 class="text-warning">
                        @if(isset($kpi['goal_kpi']['baseline_value']))
                            {{ number_format($kpi['goal_kpi']['baseline_value'], 2) }} {{ $kpi['unit'] ?? '' }}
                        @else
                            --
                        @endif
                    </h2>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card-custom">
                <div class="card-body">
                    <h5><i class="fas fa-chart-area text-primary me-2"></i>أداء المؤشر عبر الزمن</h5>
                    <div style="height:300px;">
                        <canvas id="kpiChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-custom">
                <div class="card-body">
                    <h5><i class="fas fa-clock text-info me-2"></i>آخر القياسات</h5>
                    <div class="timeline" style="max-height:300px;overflow-y:auto;">
                        @forelse(array_slice($kpi['measurements'] ?? [], 0, 5) as $measurement)
                        <div class="timeline-item">
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <div class="d-flex justify-content-between">
                                    <strong>{{ $measurement['value'] ?? '' }} {{ $kpi['unit'] ?? '' }}</strong>
                                    <small class="text-muted">{{ isset($measurement['measured_at']) ? \Carbon\Carbon::parse($measurement['measured_at'])->format('Y-m-d H:i') : '' }}</small>
                                </div>
                                @if(isset($measurement['notes']))
                                <small class="text-muted">{{ $measurement['notes'] }}</small>
                                @endif
                            </div>
                        </div>
                        @empty
                        <div class="text-center py-3 text-muted">لا توجد قياسات</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .timeline {
        position: relative;
        padding-left: 20px;
    }
    .timeline::before {
        content: '';
        position: absolute;
        left: 8px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #e9ecef;
    }
    .timeline-item {
        position: relative;
        margin-bottom: 12px;
        padding-left: 12px;
    }
    .timeline-item:last-child {
        margin-bottom: 0;
    }
    .timeline-marker {
        position: absolute;
        left: -16px;
        top: 6px;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #007bff;
        border: 2px solid #fff;
        box-shadow: 0 0 0 2px #007bff;
    }
    .timeline-content {
        background: #f8f9fa;
        padding: 8px 12px;
        border-radius: 6px;
    }
    .border-primary { border-top: 3px solid #007bff !important; }
    .border-success { border-top: 3px solid #28a745 !important; }
    .border-info { border-top: 3px solid #17a2b8 !important; }
    .border-warning { border-top: 3px solid #ffc107 !important; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var ctx = document.getElementById('kpiChart').getContext('2d');

    var chartData = @json($chartData ?? ['labels' => [], 'actual' => [], 'target' => []]);

    var labels = chartData.labels || [];
    var actualData = chartData.actual || [];
    var targetData = chartData.target || [];

    if (labels.length === 0) {
        labels = ['لا توجد بيانات'];
        actualData = [0];
        targetData = [0];
    }

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'القيمة الفعلية',
                    data: actualData,
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 4,
                    pointBackgroundColor: '#007bff'
                },
                {
                    label: 'القيمة المستهدفة',
                    data: targetData,
                    borderColor: '#dc3545',
                    backgroundColor: 'rgba(220, 53, 69, 0.05)',
                    borderDash: [5, 5],
                    fill: false,
                    tension: 0.3,
                    pointRadius: 0
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        padding: 20
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            var label = context.dataset.label || '';
                            var value = context.parsed.y || 0;
                            var unit = '{{ $kpi["unit"] ?? "" }}';
                            return label + ': ' + value.toFixed(2) + (unit ? ' ' + unit : '');
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            var unit = '{{ $kpi["unit"] ?? "" }}';
                            return value + (unit ? ' ' + unit : '');
                        }
                    }
                },
                x: {
                    ticks: {
                        maxRotation: 45,
                        minRotation: 30
                    }
                }
            }
        }
    });
});
</script>
@endpush

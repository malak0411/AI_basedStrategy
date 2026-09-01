@extends('layouts.app')

@section('title', 'سجل قياسات المؤشر')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3><i class="fas fa-list ml-2"></i>سجل قياسات المؤشر</h3>
            <p class="text-muted mb-0">{{ $kpi['name'] ?? '' }}</p>
        </div>
        <div>
            <a href="{{ route('kpis.measurements.create', $kpi['kpi_id']) }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> إضافة قياس
            </a>
            <a href="{{ route('kpis.show', $kpi['kpi_id']) }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-right"></i> العودة للتفاصيل
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

    <div class="card-custom">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>التاريخ</th>
                            <th>القيمة</th>
                            <th>الهدف</th>
                            <th>نسبة الإنجاز</th>
                            <th>الانحراف</th>
                            <th>المصدر</th>
                            <th>الملاحظات</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($measurements as $measurement)
                        <tr>
                            <td>{{ isset($measurement['measured_at']) ? \Carbon\Carbon::parse($measurement['measured_at'])->format('Y-m-d H:i') : '' }}</td>
                            <td>
                                <strong>{{ number_format($measurement['value'] ?? 0, 2) }} {{ $kpi['unit'] ?? '' }}</strong>
                            </td>
                            <td>{{ number_format($measurement['target_value'] ?? 0, 2) }} {{ $kpi['unit'] ?? '' }}</td>
                            <td>
                                @if(isset($measurement['achievement_percentage']))
                                    @php $ach = $measurement['achievement_percentage']; @endphp
                                    <span class="badge {{ $ach >= 100 ? 'bg-success' : ($ach >= 80 ? 'bg-info' : ($ach >= 60 ? 'bg-warning' : 'bg-danger')) }}">
                                        {{ round($ach) }}%
                                    </span>
                                @else
                                    <span class="text-muted">--</span>
                                @endif
                            </td>
                            <td>
                                @if(isset($measurement['variance']))
                                    @php $var = $measurement['variance']; @endphp
                                    <span class="{{ $var >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $var >= 0 ? '+' : '' }}{{ number_format($var, 2) }} {{ $kpi['unit'] ?? '' }}
                                    </span>
                                @else
                                    <span class="text-muted">--</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-secondary">{{ $measurement['source_type'] ?? 'manual' }}</span>
                            </td>
                            <td>{{ $measurement['notes'] ?? '' }}</td>
                            <td>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteMeasurement({{ $measurement['measurement_id'] }})">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <i class="fas fa-chart-bar fa-2x text-muted d-block mb-2"></i>
                                <p class="text-muted">لا توجد قياسات مسجلة لهذا المؤشر</p>
                                <a href="{{ route('kpis.measurements.create', $kpi['kpi_id']) }}" class="btn btn-primary btn-sm">
                                    إضافة أول قياس
                                </a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<form id="deleteForm" method="POST" style="display:none;">
    @csrf
    @method('DELETE')
</form>
@endsection

@push('scripts')
<script>
    function deleteMeasurement(id) {
        if (confirm('هل أنت متأكد من حذف هذا القياس؟')) {
            var form = document.getElementById('deleteForm');
            form.action = '/kpis/measurements/' + id;
            form.submit();
        }
    }
</script>
@endpush

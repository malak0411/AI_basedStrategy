@extends('layouts.app')

@section('title', 'تتبع المواقع')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3><i class="fas fa-map-marker-alt ml-2"></i>تتبع المواقع</h3>
            <p class="text-muted mb-0">عرض المواقع الحالية للموظفين</p>
        </div>
        <div>
            <a href="{{ route('location.history') }}" class="btn btn-outline-secondary">
                <i class="fas fa-history"></i> سجل المواقع
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
        <div class="col-md-3">
            <div class="card-custom text-center">
                <div class="card-body">
                    <h6 class="text-muted">إجمالي سجلات الموقع</h6>
                    <h2 class="text-primary">{{ $summary['total_logs'] ?? 0 }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-custom text-center">
                <div class="card-body">
                    <h6 class="text-muted">موظفين بـ GPS نشط</h6>
                    <h2 class="text-success">{{ $summary['employees_with_gps'] ?? 0 }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-custom text-center">
                <div class="card-body">
                    <h6 class="text-muted">مواقع اليوم</h6>
                    <h2 class="text-info">{{ $summary['locations_today'] ?? 0 }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-custom text-center">
                <div class="card-body">
                    <h6 class="text-muted">موظفين نشطين</h6>
                    <h2 class="text-warning">{{ $summary['active_employees'] ?? 0 }}</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card-custom">
                <div class="card-body">
                    <h5><i class="fas fa-map text-primary me-2"></i>خريطة المواقع الحالية</h5>
                    <div id="locationMap" style="height:500px;border-radius:8px;background:#e9ecef;"></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-custom">
                <div class="card-body">
                    <h5><i class="fas fa-users text-info me-2"></i>مواقع الموظفين الحالية</h5>
                    <div class="employee-locations-list" style="max-height:450px;overflow-y:auto;">
                        @forelse($locations as $location)
                        @php
                            $employee = $location['employee'] ?? [];
                            $task = $location['task'] ?? [];
                        @endphp
                        <div class="employee-location-item border-bottom py-2">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong>{{ $employee['full_name'] ?? 'غير معروف' }}</strong>
                                    <br>
                                    <small class="text-muted">{{ $employee['job_title'] ?? '' }}</small>
                                    @if($location['task_id'])
                                    <br>
                                    <small class="text-muted"><i class="fas fa-tasks"></i> {{ $task['title'] ?? '' }}</small>
                                    @endif
                                </div>
                                <div class="text-end">
                                    <small class="text-muted d-block">{{ isset($location['recorded_at']) ? \Carbon\Carbon::parse($location['recorded_at'])->diffForHumans() : '' }}</small>
                                    @if(isset($location['accuracy']))
                                    <small class="text-muted">دقة: {{ number_format($location['accuracy'], 2) }} م</small>
                                    @endif
                                    <a href="{{ route('location.employee', $employee['employee_id']) }}" class="btn btn-sm btn-outline-primary mt-1">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-map-marker-alt fa-2x d-block mb-2"></i>
                            <p>لا توجد مواقع حالية</p>
                        </div>
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
    .card-custom {
        background: #fff;
        border-radius: 12px;
        border: 1px solid #e9ecef;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        transition: all 0.2s ease;
    }
    .card-custom:hover {
        box-shadow: 0 4px 20px rgba(0,0,0,0.12);
    }
    .employee-location-item:hover {
        background: #f8f9fa;
        border-radius: 4px;
    }
    #locationMap {
        background: #e9ecef;
    }
    .leaflet-popup-content {
        text-align: right;
    }
</style>
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var mapData = @json($mapData ?? []);

    if (mapData.length > 0) {
        var map = L.map('locationMap').setView([15.3694, 44.1910], 6);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap'
        }).addTo(map);

        var bounds = [];
        var markerIcon = L.divIcon({
            className: 'custom-marker',
            html: '<i class="fas fa-circle text-danger" style="font-size:16px;"></i>',
            iconSize: [16, 16],
            iconAnchor: [8, 8]
        });

        mapData.forEach(function(location) {
            if (location.latitude && location.longitude) {
                var popupContent = '<div style="text-align:right;">' +
                    '<strong>' + (location.employee_name || 'غير معروف') + '</strong><br>' +
                    (location.job_title ? location.job_title + '<br>' : '') +
                    (location.task_title ? '<i class="fas fa-tasks"></i> ' + location.task_title + '<br>' : '') +
                    'الإحداثيات: ' + location.latitude.toFixed(6) + '، ' + location.longitude.toFixed(6) + '<br>' +
                    'الدقة: ' + (location.accuracy ? location.accuracy.toFixed(2) + ' م' : 'غير متاحة') + '<br>' +
                    (location.recorded_at ? 'آخر تحديث: ' + new Date(location.recorded_at).toLocaleString('ar-EG') : '') +
                    '</div>';

                var marker = L.marker([location.latitude, location.longitude], {
                    icon: markerIcon
                }).addTo(map);

                marker.bindPopup(popupContent);

                bounds.push([location.latitude, location.longitude]);
            }
        });

        if (bounds.length > 0) {
            map.fitBounds(bounds, { padding: [50, 50] });
        }
    } else {
        document.getElementById('locationMap').innerHTML = '<div class="text-center py-5"><i class="fas fa-map-marker-alt fa-3x text-muted d-block mb-3"></i><p class="text-muted">لا توجد مواقع حالية لعرضها</p></div>';
    }
});
</script>
@endpush

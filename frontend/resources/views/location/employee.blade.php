@extends('layouts.app')

@section('title', 'تتبع موقع الموظف')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3><i class="fas fa-user ml-2"></i>تتبع موقع الموظف</h3>
            <p class="text-muted mb-0">{{ $employeeData['employee']['full_name'] ?? 'غير معروف' }}</p>
        </div>
        <div>
            <a href="{{ route('location.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-right"></i> العودة
            </a>
        </div>
    </div>

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="row">
        <div class="col-md-4">
            <div class="card-custom">
                <div class="card-body">
                    <h5><i class="fas fa-user-circle text-primary me-2"></i>بيانات الموظف</h5>
                    <table class="table table-borderless">
                        <tr>
                            <td class="fw-bold">الرقم الوظيفي</td>
                            <td>{{ $employeeData['employee']['employee_number'] ?? 'غير محدد' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">الاسم</td>
                            <td>{{ $employeeData['employee']['full_name'] ?? 'غير محدد' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">المسمى الوظيفي</td>
                            <td>{{ $employeeData['employee']['job_title'] ?? 'غير محدد' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">القسم</td>
                            <td>{{ $employeeData['employee']['department']['name'] ?? 'غير محدد' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">حالة GPS</td>
                            <td>
                                @if($employeeData['employee']['gps_enabled'] ?? false)
                                <span class="badge bg-success">نشط</span>
                                @else
                                <span class="badge bg-danger">معطل</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="fw-bold">حالة الموظف</td>
                            <td>
                                @if($employeeData['employee']['is_active'] ?? false)
                                <span class="badge bg-success">نشط</span>
                                @else
                                <span class="badge bg-danger">غير نشط</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="card-custom mt-3">
                <div class="card-body">
                    <h5><i class="fas fa-map-pin text-success me-2"></i>آخر موقع</h5>
                    @if(($employeeData['has_location'] ?? false) && isset($employeeData['location']))
                    @php $location = $employeeData['location']; @endphp
                    <table class="table table-borderless">
                        <tr>
                            <td class="fw-bold">خط العرض</td>
                            <td>{{ number_format($location['latitude'] ?? 0, 6) }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">خط الطول</td>
                            <td>{{ number_format($location['longitude'] ?? 0, 6) }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">الدقة</td>
                            <td>{{ isset($location['accuracy']) ? number_format($location['accuracy'], 2) . ' م' : 'غير متاحة' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">المصدر</td>
                            <td>{{ $location['source'] ?? 'غير محدد' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">المهمة</td>
                            <td>{{ $location['task']['title'] ?? 'غير مرتبط' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">وقت التسجيل</td>
                            <td>{{ isset($location['recorded_at']) ? \Carbon\Carbon::parse($location['recorded_at'])->format('Y-m-d H:i:s') : 'غير محدد' }}</td>
                        </tr>
                    </table>
                    @else
                    <div class="text-center py-3 text-muted">
                        <i class="fas fa-map-marker-alt fa-2x d-block mb-2"></i>
                        <p>لا توجد بيانات موقع</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card-custom">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5><i class="fas fa-history text-info me-2"></i>سجل المواقع</h5>
                        @if(isset($historyData['total']) && $historyData['total'] > 0)
                        <small class="text-muted">إجمالي: {{ $historyData['total'] }}</small>
                        @endif
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>المهمة</th>
                                    <th>خط العرض</th>
                                    <th>خط الطول</th>
                                    <th>الدقة</th>
                                    <th>المصدر</th>
                                    <th>وقت التسجيل</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse(($historyData['data'] ?? []) as $location)
                                <tr>
                                    <td>{{ $location['location_id'] ?? '' }}</td>
                                    <td>{{ $location['task']['title'] ?? 'غير مرتبط' }}</td>
                                    <td>{{ number_format($location['latitude'] ?? 0, 6) }}</td>
                                    <td>{{ number_format($location['longitude'] ?? 0, 6) }}</td>
                                    <td>{{ isset($location['accuracy']) ? number_format($location['accuracy'], 2) . ' م' : 'غير متاحة' }}</td>
                                    <td>{{ $location['source'] ?? 'غير محدد' }}</td>
                                    <td>{{ isset($location['recorded_at']) ? \Carbon\Carbon::parse($location['recorded_at'])->format('Y-m-d H:i:s') : '' }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-3">لا توجد سجلات مواقع</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if(isset($historyData['total_pages']) && $historyData['total_pages'] > 1)
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div>
                            عرض {{ ($historyData['page'] - 1) * $historyData['per_page'] + 1 }} - {{ min($historyData['page'] * $historyData['per_page'], $historyData['total']) }} من {{ $historyData['total'] }}
                        </div>
                        <nav>
                            <ul class="pagination">
                                @for($i = 1; $i <= $historyData['total_pages']; $i++)
                                <li class="page-item {{ $i == $historyData['page'] ? 'active' : '' }}">
                                    <a class="page-link" href="?page={{ $i }}&per_page={{ $historyData['per_page'] }}">{{ $i }}</a>
                                </li>
                                @endfor
                            </ul>
                        </nav>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

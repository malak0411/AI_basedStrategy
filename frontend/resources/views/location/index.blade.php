@extends('layouts.app')

@section('title', 'تتبع المواقع')

@section('content')
<div class="container-fluid px-4">
    <h3 class="mb-4"><i class="fas fa-map-marker-alt ml-2"></i>تتبع المواقع</h3>

    @if(empty($locations))
        <div class="card-custom text-center py-5">
            <i class="fas fa-map-marker-alt fa-3x text-muted mb-3"></i>
            <h5>لا توجد بيانات مواقع</h5>
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-hover card-custom">
                <thead>
                    <tr>
                        <th>الموظف</th>
                        <th>الإحداثيات</th>
                        <th>الوقت</th>
                        <th>إجراء</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($locations as $loc)
                    <tr>
                        <td>{{ $loc['employee_name'] ?? '' }}</td>
                        <td>
                            <small>{{ $loc['latitude'] ?? '' }}, {{ $loc['longitude'] ?? '' }}</small>
                        </td>
                        <td><small>{{ $loc['created_at'] ?? '' }}</small></td>
                        <td>
                            <a href="{{ route('location.employee', $loc['employee_id'] ?? 0) }}" class="btn btn-sm btn-outline-info">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection

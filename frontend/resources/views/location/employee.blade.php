@extends('layouts.app')

@section('title', 'مواقع الموظف')

@section('content')
<div class="container-fluid px-4">
    <a href="{{ route('location.index') }}" class="btn btn-outline-secondary mb-3">
        <i class="fas fa-arrow-right"></i> العودة
    </a>

    <h4 class="mb-4"><i class="fas fa-map-marker-alt ml-2"></i>سجل مواقع الموظف #{{ $employeeId }}</h4>

    @if(empty($locations))
        <div class="card-custom text-center py-5">
            <h5>لا توجد بيانات مواقع لهذا الموظف</h5>
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-hover card-custom">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>خط العرض</th>
                        <th>خط الطول</th>
                        <th>الوقت</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($locations as $loc)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $loc['latitude'] ?? '' }}</td>
                        <td>{{ $loc['longitude'] ?? '' }}</td>
                        <td><small>{{ $loc['created_at'] ?? '' }}</small></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection

@extends('layouts.app')

@section('title', 'سجل المواقع')

@section('content')
<div class="container-fluid px-4">
    <h3 class="mb-4"><i class="fas fa-history ml-2"></i>سجل المواقع</h3>

    @if(empty($history))
        <div class="card-custom text-center py-5">
            <h5>لا يوجد سجل مواقع</h5>
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-hover card-custom">
                <thead>
                    <tr>
                        <th>الموظف</th>
                        <th>عدد التسجيلات</th>
                        <th>آخر موقع</th>
                        <th>آخر تحديث</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($history as $h)
                    <tr>
                        <td>{{ $h['employee_name'] ?? '' }}</td>
                        <td>{{ $h['total_logs'] ?? 0 }}</td>
                        <td><small>{{ $h['last_latitude'] ?? '' }}, {{ $h['last_longitude'] ?? '' }}</small></td>
                        <td><small>{{ $h['last_updated'] ?? '' }}</small></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection

@extends('layouts.app')

@section('title', 'قياسات المؤشر')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3><i class="fas fa-ruler ml-2"></i>قياسات: {{ $kpi['name'] ?? '' }}</h3>
        <a href="{{ route('kpis.measurements.create', $id) }}" class="btn-gold">
            <i class="fas fa-plus"></i> تسجيل قياس جديد
        </a>
    </div>

    <a href="{{ route('kpis.show', $id) }}" class="btn btn-outline-secondary mb-3">
        <i class="fas fa-arrow-right"></i> العودة للمؤشر
    </a>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(empty($measurements))
        <div class="card-custom text-center py-5">
            <i class="fas fa-ruler fa-3x text-muted mb-3"></i>
            <h5>لا توجد قياسات</h5>
            <a href="{{ route('kpis.measurements.create', $id) }}" class="btn btn-primary mt-3">تسجيل أول قياس</a>
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-hover card-custom">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>القيمة</th>
                        <th>التاريخ</th>
                        <th>ملاحظات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($measurements as $m)
                    <tr>
                        <td>{{ $m['id'] ?? $loop->iteration }}</td>
                        <td><strong>{{ $m['value'] ?? 0 }}</strong></td>
                        <td>{{ $m['measurement_date'] ?? $m['created_at'] ?? '' }}</td>
                        <td>{{ $m['notes'] ?? '' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection

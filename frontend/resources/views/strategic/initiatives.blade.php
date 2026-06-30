@extends('layouts.app')

@section('title', 'المبادرات')

@section('content')
<div class="container-fluid">
    <h3><i class="fas fa-lightbulb ml-2"></i>المبادرات</h3>

    @if(empty($initiatives))
        <div class="card-custom text-center py-5 mt-4">
            <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
            <p>لا توجد مبادرات حالياً</p>
        </div>
    @else
        <table class="table table-bordered mt-4">
            <thead><tr><th>المبادرة</th><th>البرنامج</th><th>الحالة</th></tr></thead>
            <tbody>
                @foreach($initiatives as $initiative)
                <tr>
                    <td>{{ $initiative['name'] ?? '' }}</td>
                    <td>{{ $initiative['program_name'] ?? '' }}</td>
                    <td>{{ $initiative['status'] ?? '' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection

@extends('layouts.app')

@section('title', 'الأهداف الاستراتيجية')

@section('content')
<div class="container-fluid">
    <h3><i class="fas fa-bullseye ml-2"></i>الأهداف الاستراتيجية</h3>

    @if(empty($goals))
        <div class="card-custom text-center py-5 mt-4">
            <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
            <p>لا توجد أهداف استراتيجية حالياً</p>
        </div>
    @else
        <table class="table table-bordered mt-4">
            <thead><tr><th>الهدف</th><th>الركيزة</th><th>نسبة الإنجاز</th></tr></thead>
            <tbody>
                @foreach($goals as $goal)
                <tr>
                    <td>{{ $goal['name'] ?? $goal['title'] ?? '' }}</td>
                    <td>{{ $goal['pillar_name'] ?? '' }}</td>
                    <td>{{ $goal['progress'] ?? 0 }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection

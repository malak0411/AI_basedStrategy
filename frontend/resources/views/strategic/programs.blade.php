@extends('layouts.app')

@section('title', 'البرامج')

@section('content')
<div class="container-fluid">
    <h3><i class="fas fa-project-diagram ml-2"></i>البرامج</h3>

    @if(empty($programs))
        <div class="card-custom text-center py-5 mt-4">
            <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
            <p>لا توجد برامج حالياً</p>
        </div>
    @else
        <table class="table table-bordered mt-4">
            <thead><tr><th>البرنامج</th><th>الهدف</th><th>الميزانية</th></tr></thead>
            <tbody>
                @foreach($programs as $program)
                <tr>
                    <td>{{ $program['name'] ?? '' }}</td>
                    <td>{{ $program['goal_name'] ?? '' }}</td>
                    <td>{{ $program['budget'] ?? 0 }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection

@extends('layouts.app')

@section('title', 'المخاطر')

@section('content')
<div class="container-fluid">
    <h3><i class="fas fa-exclamation-triangle ml-2"></i>المخاطر</h3>

    @if(empty($risks))
        <div class="card-custom text-center py-5 mt-4">
            <p>لا توجد مخاطر حالياً</p>
        </div>
    @else
        <table class="table table-bordered mt-4">
            <thead><tr><th>الخطر</th><th>المستوى</th><th>الحالة</th></tr></thead>
            <tbody>
                @foreach($risks as $risk)
                <tr>
                    <td>{{ $risk['name'] ?? $risk['title'] ?? '' }}</td>
                    <td>{{ $risk['level'] ?? '' }}</td>
                    <td>{{ $risk['status'] ?? '' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection

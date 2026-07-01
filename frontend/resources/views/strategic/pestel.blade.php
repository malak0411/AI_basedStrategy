@extends('layouts.app')

@section('title', 'تحليل PESTEL')

@section('content')
<div class="container-fluid px-4">
    <h3 class="mb-4"><i class="fas fa-globe ml-2"></i>تحليل PESTEL</h3>

    <div class="row">
        @php
            $categories = [
                'political' => ['سياسي', 'fa-landmark', 'primary'],
                'economic' => ['اقتصادي', 'fa-chart-line', 'success'],
                'social' => ['اجتماعي', 'fa-users', 'info'],
                'technological' => ['تقني', 'fa-microchip', 'dark'],
                'environmental' => ['بيئي', 'fa-leaf', 'success'],
                'legal' => ['قانوني', 'fa-gavel', 'warning'],
            ];
        @endphp

        @foreach($categories as $key => [$label, $icon, $color])
        <div class="col-md-4 mb-4">
            <div class="card-custom border-{{ $color }} border-right">
                <h5 class="text-{{ $color }}">
                    <i class="fas {{ $icon }} ml-2"></i>{{ $label }}
                </h5>
                @if(!empty($pestel[$key]))
                    <ul class="mt-3">
                        @foreach($pestel[$key] as $item)
                            <li>{{ $item['description'] ?? $item }}</li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-muted mt-3">لا توجد بيانات</p>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection

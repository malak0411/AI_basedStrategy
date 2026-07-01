@extends('layouts.app')

@section('title', 'تحليل SWOT')

@section('content')
<div class="container-fluid px-4">
    <h3 class="mb-4"><i class="fas fa-chess-board ml-2"></i>تحليل SWOT</h3>

    <div class="row">
        {{-- نقاط القوة --}}
        <div class="col-md-6 mb-4">
            <div class="card-custom border-success border-right">
                <h5 class="text-success"><i class="fas fa-check-circle ml-2"></i>نقاط القوة (Strengths)</h5>
                @if(!empty($swot['strengths']))
                    <ul class="mt-3">
                        @foreach($swot['strengths'] as $item)
                            <li>{{ $item['description'] ?? $item }}</li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-muted mt-3">لا توجد بيانات</p>
                @endif
            </div>
        </div>

        {{-- نقاط الضعف --}}
        <div class="col-md-6 mb-4">
            <div class="card-custom border-danger border-right">
                <h5 class="text-danger"><i class="fas fa-exclamation-circle ml-2"></i>نقاط الضعف (Weaknesses)</h5>
                @if(!empty($swot['weaknesses']))
                    <ul class="mt-3">
                        @foreach($swot['weaknesses'] as $item)
                            <li>{{ $item['description'] ?? $item }}</li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-muted mt-3">لا توجد بيانات</p>
                @endif
            </div>
        </div>

        {{-- الفرص --}}
        <div class="col-md-6 mb-4">
            <div class="card-custom border-info border-right">
                <h5 class="text-info"><i class="fas fa-lightbulb ml-2"></i>الفرص (Opportunities)</h5>
                @if(!empty($swot['opportunities']))
                    <ul class="mt-3">
                        @foreach($swot['opportunities'] as $item)
                            <li>{{ $item['description'] ?? $item }}</li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-muted mt-3">لا توجد بيانات</p>
                @endif
            </div>
        </div>

        {{-- التهديدات --}}
        <div class="col-md-6 mb-4">
            <div class="card-custom border-warning border-right">
                <h5 class="text-warning"><i class="fas fa-exclamation-triangle ml-2"></i>التهديدات (Threats)</h5>
                @if(!empty($swot['threats']))
                    <ul class="mt-3">
                        @foreach($swot['threats'] as $item)
                            <li>{{ $item['description'] ?? $item }}</li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-muted mt-3">لا توجد بيانات</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

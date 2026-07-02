@extends('layouts.app')

@section('title', 'تفاصيل التنبؤ')

@section('content')
<div class="container-fluid px-4">
    <a href="{{ route('ai.predictions') }}" class="btn btn-outline-secondary mb-3">
        <i class="fas fa-arrow-right"></i> العودة
    </a>

    @if(empty($prediction))
        <div class="alert alert-info">التنبؤ غير موجود</div>
    @else
        <div class="card-custom">
            <h4 class="mb-4">تفاصيل التنبؤ #{{ $prediction['id'] ?? '' }}</h4>

            <div class="row">
                <div class="col-md-4">
                    <strong>النموذج:</strong> {{ $prediction['model_name'] ?? '' }}
                </div>
                <div class="col-md-4">
                    <strong>نسبة الثقة:</strong> {{ $prediction['confidence'] ?? 0 }}%
                </div>
                <div class="col-md-4">
                    <strong>التاريخ:</strong> {{ $prediction['created_at'] ?? '' }}
                </div>
            </div>

            <hr>
            <h5>المدخلات</h5>
            <pre class="bg-light p-3 rounded">{{ json_encode($prediction['input_data'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>

            <h5 class="mt-3">النتيجة</h5>
            <div class="alert alert-info">{{ $prediction['result'] ?? '' }}</div>

            @if(!empty($prediction['details']))
            <h5>التفاصيل</h5>
            <pre class="bg-light p-3 rounded">{{ json_encode($prediction['details'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            @endif
        </div>
    @endif
</div>
@endsection

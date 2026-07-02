@extends('layouts.app')

@section('title', 'تفاصيل التوصية')

@section('content')
<div class="container-fluid px-4">
    <a href="{{ route('ai.recommendations') }}" class="btn btn-outline-secondary mb-3">
        <i class="fas fa-arrow-right"></i> العودة
    </a>

    @if(empty($recommendation))
        <div class="alert alert-info">التوصية غير موجودة</div>
    @else
        <div class="card-custom">
            <div class="d-flex justify-content-between align-items-start mb-4">
                <h4>{{ $recommendation['title'] ?? '' }}</h4>
                <div>
                    <span class="badge bg-{{ ($recommendation['priority'] ?? '') == 'high' ? 'danger' : 'info' }}">
                        {{ ($recommendation['priority'] ?? '') == 'high' ? 'عاجل' : 'عادي' }}
                    </span>
                </div>
            </div>

            <p>{{ $recommendation['description'] ?? '' }}</p>

            <div class="row mt-4">
                <div class="col-md-3">
                    <strong>الفئة:</strong> {{ $recommendation['category'] ?? '' }}
                </div>
                <div class="col-md-3">
                    <strong>النموذج:</strong> {{ $recommendation['model_name'] ?? '' }}
                </div>
                <div class="col-md-3">
                    <strong>الحالة:</strong> {{ $recommendation['status'] ?? 'جديد' }}
                </div>
                <div class="col-md-3">
                    <strong>التاريخ:</strong> {{ $recommendation['created_at'] ?? '' }}
                </div>
            </div>

            @if(!empty($recommendation['actions']))
            <hr>
            <h5>الإجراءات المقترحة</h5>
            <ul>
                @foreach($recommendation['actions'] as $action)
                    <li>{{ $action }}</li>
                @endforeach
            </ul>
            @endif
        </div>
    @endif
</div>
@endsection

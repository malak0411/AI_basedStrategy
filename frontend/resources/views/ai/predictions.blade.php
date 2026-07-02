@extends('layouts.app')

@section('title', 'سجل التنبؤات')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3><i class="fas fa-chart-line ml-2"></i>سجل التنبؤات</h3>
        <a href="{{ route('ai.dashboard') }}" class="btn btn-outline-secondary">
            <i class="fas fa-chart-bar"></i> لوحة AI
        </a>
    </div>

    @if(empty($predictions))
        <div class="card-custom text-center py-5">
            <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
            <h5>لا توجد تنبؤات</h5>
            <p class="text-muted">لم يتم إجراء أي تنبؤات بعد</p>
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-hover card-custom">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>النموذج</th>
                        <th>النتيجة</th>
                        <th>نسبة الثقة</th>
                        <th>التاريخ</th>
                        <th>إجراء</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($predictions as $prediction)
                    <tr>
                        <td>{{ $prediction['id'] ?? $loop->iteration }}</td>
                        <td>{{ $prediction['model_name'] ?? '' }}</td>
                        <td>{{ Str::limit($prediction['result'] ?? '', 60) }}</td>
                        <td>
                            <span class="badge bg-{{ ($prediction['confidence'] ?? 0) >= 80 ? 'success' : 'warning' }}">
                                {{ $prediction['confidence'] ?? 0 }}%
                            </span>
                        </td>
                        <td>{{ $prediction['created_at'] ?? '' }}</td>
                        <td>
                            <a href="{{ route('ai.predictions.show', $prediction['id']) }}" class="btn btn-sm btn-outline-info">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection

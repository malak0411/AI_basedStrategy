@extends('layouts.app')

@section('title', 'تفاصيل سجل التدقيق')

@section('content')
<div class="container-fluid px-4">
    <a href="{{ route('admin.audit-logs.index') }}" class="btn btn-outline-secondary mb-3">
        <i class="fas fa-arrow-right"></i> العودة
    </a>

    @if(empty($log))
        <div class="alert alert-info">السجل غير موجود</div>
    @else
        <div class="card-custom">
            <h4 class="mb-4">تفاصيل السجل #{{ $log['id'] ?? '' }}</h4>

            <div class="row">
                <div class="col-md-4">
                    <strong>المستخدم:</strong> {{ $log['user_name'] ?? $log['employee_name'] ?? '' }}
                </div>
                <div class="col-md-4">
                    <strong>الإجراء:</strong>
                    <span class="badge bg-{{ ($log['action'] ?? '') == 'DELETE' ? 'danger' : (($log['action'] ?? '') == 'UPDATE' ? 'warning' : 'info') }}">
                        {{ $log['action'] ?? '' }}
                    </span>
                </div>
                <div class="col-md-4">
                    <strong>الجدول:</strong> {{ $log['table_name'] ?? '' }}
                </div>
                <div class="col-md-4 mt-3">
                    <strong>رقم السجل:</strong> {{ $log['record_id'] ?? '' }}
                </div>
                <div class="col-md-4 mt-3">
                    <strong>التاريخ:</strong> {{ $log['created_at'] ?? '' }}
                </div>
                <div class="col-md-4 mt-3">
                    <strong>IP:</strong> {{ $log['ip_address'] ?? '' }}
                </div>
            </div>

            @if(!empty($log['old_values']))
            <hr>
            <h5>القيم القديمة</h5>
            <pre class="bg-light p-3 rounded">{{ json_encode($log['old_values'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            @endif

            @if(!empty($log['new_values']))
            <h5 class="mt-3">القيم الجديدة</h5>
            <pre class="bg-light p-3 rounded">{{ json_encode($log['new_values'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            @endif
        </div>
    @endif
</div>
@endsection

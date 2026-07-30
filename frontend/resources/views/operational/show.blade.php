@extends('layouts.app')

@section('title', 'تفاصيل المهمة التشغيلية')

@section('content')
<div class="container-fluid px-4">
    <a href="{{ route('operational.index') }}" class="btn btn-outline-secondary mb-3">
        <i class="fas fa-arrow-right"></i> العودة
    </a>

    @if(empty($task))
    <div class="alert alert-info">المهمة غير موجودة</div>
    @else
    <div class="card-custom">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <h4>{{ $task['task_name'] ?? $task['title'] ?? '' }}</h4>
            <span class="badge bg-{{ ($task['status']??5)==8?'success':(($task['status']??5)==7?'danger':'warning') }} fs-6">
                {{ ['5'=>'معلق','6'=>'جاري','7'=>'متأخر','8'=>'مكتمل'][$task['status']??5]??'معلق' }}
            </span>
        </div>
        <p class="text-muted">{{ $task['description'] ?? '' }}</p>
        <div class="row mt-3">
            <div class="col-md-3"><small>تاريخ البداية</small><div>{{ $task['start_date'] ?? '' }}</div></div>
            <div class="col-md-3"><small>تاريخ التسليم</small><div>{{ $task['due_date'] ?? $task['end_date'] ?? '' }}</div></div>
            <div class="col-md-3"><small>المسؤول</small><div>{{ $task['assigned_to_name'] ?? '' }}</div></div>
            <div class="col-md-3"><small>الإدارة</small><div>{{ $task['department_name'] ?? '' }}</div></div>
        </div>
    </div>
    @endif
</div>
@endsection

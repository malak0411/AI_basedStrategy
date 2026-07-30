
@extends('layouts.app')

@section('title', 'المهام التشغيلية')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3><i class="fas fa-tasks ml-2"></i>المهام التشغيلية</h3>
        <a href="{{ route('operational.major-tasks') }}" class="btn-gold">
            <i class="fas fa-project-diagram"></i> المهام الرئيسية
        </a>
    </div>

    @if(empty($tasks))
    <div class="card-custom text-center py-5">
        <i class="fas fa-clipboard-list fa-4x text-muted mb-3"></i>
        <h5>لا توجد مهام تشغيلية</h5>
    </div>
    @else
    <div class="table-responsive card-custom">
        <table class="table table-hover mb-0">
            <thead><tr><th>المهمة</th><th>الحالة</th><th>الأولوية</th><th>تاريخ التسليم</th><th>المسؤول</th><th>إجراء</th></tr></thead>
            <tbody>
                @foreach($tasks as $task)
                <tr>
                    <td><a href="{{ route('operational.show', $task['id']) }}" class="fw-bold">{{ $task['task_name'] ?? $task['title'] ?? '' }}</a></td>
                    <td><span class="badge bg-{{ ($task['status']??5)==8?'success':(($task['status']??5)==7?'danger':'warning') }}">{{ ['5'=>'معلق','6'=>'جاري','7'=>'متأخر','8'=>'مكتمل'][$task['status']??5]??'معلق' }}</span></td>
                    <td><span class="badge bg-{{ ($task['priority']??2)>=3?'danger':'info' }}">{{ $task['priority']??2 }}</span></td>
                    <td><small>{{ $task['due_date'] ?? $task['end_date'] ?? '' }}</small></td>
                    <td>{{ $task['assigned_to_name'] ?? '' }}</td>
                    <td><a href="{{ route('operational.show', $task['id']) }}" class="btn btn-sm btn-outline-info"><i class="fas fa-eye"></i></a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
@endsection

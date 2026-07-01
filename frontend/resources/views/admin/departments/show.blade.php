@extends('layouts.app')

@section('title', 'تفاصيل الإدارة')

@section('content')
<div class="container-fluid px-4">
    <a href="{{ route('admin.departments.index') }}" class="btn btn-outline-secondary mb-3">
        <i class="fas fa-arrow-right"></i> العودة للقائمة
    </a>

    @if(empty($department))
        <div class="alert alert-info">الإدارة غير موجودة</div>
    @else
        <div class="card-custom">
            <h4 class="mb-4">{{ $department['name'] ?? '' }}</h4>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <strong>الكود:</strong> {{ $department['code'] ?? '' }}
                </div>
                <div class="col-md-6 mb-3">
                    <strong>المدير:</strong> {{ $department['manager_name'] ?? 'غير محدد' }}
                </div>
                <div class="col-md-6 mb-3">
                    <strong>المستوى:</strong> {{ $department['level'] ?? '' }}
                </div>
                <div class="col-md-6 mb-3">
                    <strong>الإدارة الأم:</strong> {{ $department['parent_name'] ?? 'لا يوجد' }}
                </div>
                <div class="col-12 mb-3">
                    <strong>الوصف:</strong>
                    <p class="text-muted mt-2">{{ $department['description'] ?? 'لا يوجد وصف' }}</p>
                </div>
            </div>

            {{-- موظفي الإدارة --}}
            @if(!empty($department['employees']))
            <hr>
            <h5>الموظفين</h5>
            <ul>
                @foreach($department['employees'] as $emp)
                    <li>{{ $emp['full_name'] ?? $emp['name'] ?? '' }} - {{ $emp['job_title'] ?? '' }}</li>
                @endforeach
            </ul>
            @endif
        </div>
    @endif
</div>
@endsection

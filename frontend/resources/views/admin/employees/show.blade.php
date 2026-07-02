@extends('layouts.app')

@section('title', 'تفاصيل الموظف')

@section('content')
<div class="container-fluid px-4">
    <a href="{{ route('admin.employees.index') }}" class="btn btn-outline-secondary mb-3">
        <i class="fas fa-arrow-right"></i> العودة
    </a>

    @if(empty($employee))
        <div class="alert alert-info">الموظف غير موجود</div>
    @else
        {{-- البيانات الشخصية --}}
        <div class="row">
            <div class="col-lg-4">
                <div class="card-custom text-center mb-4">
                    <div class="avatar-lg bg-gold rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" 
                         style="width: 100px; height: 100px; font-size: 40px;">
                        {{ mb_substr($employee['full_name'] ?? 'م', 0, 1) }}
                    </div>
                    <h4>{{ $employee['full_name'] ?? '' }}</h4>
                    <p class="text-muted">{{ $employee['job_title'] ?? '' }}</p>
                    <span class="badge bg-{{ ($employee['is_active'] ?? false) ? 'success' : 'danger' }}">
                        {{ ($employee['is_active'] ?? false) ? 'نشط' : 'غير نشط' }}
                    </span>
                </div>

                <div class="card-custom mb-4">
                    <h5>معلومات الاتصال</h5>
                    <p><i class="fas fa-envelope ml-2"></i> {{ $employee['email'] ?? '' }}</p>
                    <p><i class="fas fa-phone ml-2"></i> {{ $employee['phone_number'] ?? '' }}</p>
                    <p><i class="fas fa-building ml-2"></i> {{ $employee['department_name'] ?? '' }}</p>
                    <p><i class="fas fa-calendar ml-2"></i> {{ $employee['hire_date'] ?? '' }}</p>
                </div>
            </div>

            <div class="col-lg-8">
                {{-- تبويبات --}}
                <ul class="nav nav-tabs mb-4">
                    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tasks">المهام</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#roles">الأدوار</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#location">الموقع</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#activity">سجل النشاط</a></li>
                </ul>

                <div class="tab-content">
                    {{-- المهام --}}
                    <div class="tab-pane fade show active" id="tasks">
                        <div class="card-custom">
                            @if(!empty($employee['tasks']))
                                @foreach($employee['tasks'] as $task)
                                <div class="border rounded p-3 mb-2">
                                    <strong>{{ $task['task_name'] ?? '' }}</strong>
                                    <span class="badge bg-info float-start">{{ $task['progress'] ?? 0 }}%</span>
                                </div>
                                @endforeach
                            @else
                                <p class="text-muted">لا توجد مهام</p>
                            @endif
                        </div>
                    </div>

                    {{-- الأدوار --}}
                    <div class="tab-pane fade" id="roles">
                        <div class="card-custom">
                            @if(!empty($employee['roles']))
                                @foreach($employee['roles'] as $role)
                                    <span class="badge bg-info me-2">{{ $role }}</span>
                                @endforeach
                            @else
                                <p class="text-muted">لا توجد أدوار</p>
                            @endif
                        </div>
                    </div>

                    {{-- الموقع --}}
                    <div class="tab-pane fade" id="location">
                        <div class="card-custom">
                            @if(!empty($employee['last_location']))
                                <p>آخر موقع: {{ $employee['last_location']['latitude'] ?? '' }}, {{ $employee['last_location']['longitude'] ?? '' }}</p>
                                <small>{{ $employee['last_location']['created_at'] ?? '' }}</small>
                            @else
                                <p class="text-muted">لا توجد بيانات موقع</p>
                            @endif
                        </div>
                    </div>

                    {{-- سجل النشاط --}}
                    <div class="tab-pane fade" id="activity">
                        <div class="card-custom">
                            @if(!empty($employee['activities']))
                                @foreach($employee['activities'] as $activity)
                                <div class="border-bottom pb-2 mb-2">
                                    <strong>{{ $activity['action'] ?? '' }}</strong>
                                    <small class="text-muted">- {{ $activity['created_at'] ?? '' }}</small>
                                </div>
                                @endforeach
                            @else
                                <p class="text-muted">لا يوجد نشاط</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

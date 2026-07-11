@extends('layouts.app')

@section('title', 'تفاصيل المهمة')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="{{ route('tasks.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-right"></i> العودة للقائمة
        </a>
        <a href="{{ route('tasks.edit', $task['id'] ?? 0) }}" class="btn btn-outline-primary">
            <i class="fas fa-edit"></i> تعديل
        </a>
    </div>

    @if(empty($task))
        <div class="alert alert-info">المهمة غير موجودة</div>
    @else
        <div class="row">
            <div class="col-lg-8">
                {{-- تفاصيل المهمة --}}
                <div class="card-custom mb-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <h4>{{ $task['task_name'] ?? $task['title'] ?? '' }}</h4>
                        @php $status = $task['status'] ?? 5; @endphp
                        <span class="badge bg-{{ $status == 8 ? 'success' : ($status == 7 ? 'danger' : ($status == 6 ? 'info' : 'warning')) }} fs-6">
                            {{ ['5'=>'معلق','6'=>'جاري العمل','7'=>'متأخر','8'=>'مكتمل'][$status] ?? 'غير معروف' }}
                        </span>
                    </div>
                    <p class="text-muted mb-4">{{ $task['description'] ?? 'لا يوجد وصف' }}</p>
                    <div class="row mb-4">
                        <div class="col-md-3"><small class="text-muted">تاريخ البداية</small><div class="fw-bold">{{ $task['start_date'] ?? 'غير محدد' }}</div></div>
                        <div class="col-md-3"><small class="text-muted">تاريخ التسليم</small><div class="fw-bold">{{ $task['due_date'] ?? $task['end_date'] ?? 'غير محدد' }}</div></div>
                        <div class="col-md-3"><small class="text-muted">المسؤول</small><div class="fw-bold">{{ $task['assigned_to_name'] ?? 'غير معين' }}</div></div>
                        <div class="col-md-3"><small class="text-muted">الإدارة</small><div class="fw-bold">{{ $task['department_name'] ?? '' }}</div></div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                {{-- تنبؤ AI --}}
                @if(!empty($prediction))
                <div class="card-custom mb-4 border-{{ $prediction['risk_level'] == 'High' ? 'danger' : 'warning' }}">
                    <h6><i class="fas fa-brain text-warning ml-2"></i>توقع الذكاء الاصطناعي</h6>
                    <div class="text-center mt-3">
                        <div class="display-4 fw-bold text-{{ $prediction['risk_level'] == 'High' ? 'danger' : 'warning' }}">
                            {{ round(($prediction['delay_probability'] ?? 0) * 100) }}%
                        </div>
                        <p>احتمالية التأخير</p>
                        <span class="badge bg-{{ $prediction['risk_level'] == 'High' ? 'danger' : 'warning' }}">
                            {{ $prediction['risk_level'] ?? 'غير معروف' }}
                        </span>
                    </div>
                    @if(!empty($prediction['top_factors']))
                        <hr>
                        <small class="text-muted">العوامل المؤثرة:</small>
                        @foreach($prediction['top_factors'] as $factor)
                            <li><small>{{ $factor['feature'] ?? '' }}</small></li>
                        @endforeach
                    @endif
                </div>
                @endif

                {{-- توصيات AI --}}
                @if(!empty($aiRecommendations))
                <div class="card-custom mb-4">
                    <h6><i class="fas fa-robot text-info ml-2"></i>توصيات ذكية</h6>
                    @foreach($aiRecommendations as $rec)
                    <div class="border rounded p-3 mb-2">
                        <div class="d-flex justify-content-between">
                            <strong>{{ $rec['action_ar'] ?? $rec['action'] ?? '' }}</strong>
                            <span class="badge bg-info">{{ round(($rec['confidence'] ?? 0) * 100) }}%</span>
                        </div>
                        <p class="text-muted small mt-2 mb-0">{{ $rec['reason'] ?? '' }}</p>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    @endif
</div>
@endsection

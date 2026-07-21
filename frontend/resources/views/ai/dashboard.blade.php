@extends('layouts.app')

@section('title', 'لوحة الذكاء الاصطناعي')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3><i class="fas fa-brain text-warning ml-2"></i>لوحة الذكاء الاصطناعي</h3>
            <p class="text-muted mb-0">نظام التنبؤ الاستباقي لتأخير المهام</p>
        </div>
        <div>
            <a href="{{ route('ai.predict-all') }}" class="btn btn-outline-info btn-sm">
                <i class="fas fa-sync"></i> تنبؤ الآن
            </a>
            <a href="{{ route('ai.train-now') }}" class="btn btn-outline-warning btn-sm mx-1">
                <i class="fas fa-cogs"></i> تدريب الآن
            </a>
            <a href="{{ route('ai.toggle-scheduler') }}" class="btn btn-sm btn-{{ ($scheduler['is_running'] ?? false) ? 'danger' : 'success' }}">
                <i class="fas fa-power-off"></i> {{ ($scheduler['is_running'] ?? false) ? 'إيقاف' : 'تشغيل' }} الجدولة
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- حالة الجدولة --}}
    <div class="alert alert-{{ ($scheduler['is_running'] ?? false) ? 'success' : 'warning' }} mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-clock ml-2"></i>
                الجدولة التلقائية: <strong>{{ ($scheduler['is_running'] ?? false) ? 'نشطة ✅' : 'متوقفة ❌' }}</strong>
                | التنبؤ اليومي: <strong>06:00</strong> | التدريب الأسبوعي: <strong>الإثنين 02:00</strong>
            </div>
            <small class="text-muted">
                <i class="far fa-clock ml-1"></i> 
                آخر تنبؤ: {{ $dashboard['last_prediction_time'] ?? 'لم يتم بعد' }}
            </small>
        </div>
    </div>

    {{-- بطاقات الإحصائيات --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="number text-primary">{{ $dashboard['total_predictions'] ?? 0 }}</div>
                <div class="label">إجمالي التنبؤات</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="number text-danger">{{ $dashboard['high_risk_tasks'] ?? 0 }}</div>
                <div class="label">مهام عالية الخطورة</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="number text-info">{{ $dashboard['average_delay_probability'] ?? 0 }}%</div>
                <div class="label">متوسط احتمالية التأخير</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="number">
                    @if($dashboard['is_model_trained'] ?? false)
                        ✅
                    @else
                        ❌
                    @endif
                </div>
                <div class="label">حالة النموذج (v{{ $dashboard['model_version'] ?? '1.0' }})</div>
            </div>
        </div>
    </div>

    {{-- معلومات إضافية --}}
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card-custom">
                <h6><i class="fas fa-info-circle ml-2"></i>معلومات النموذج</h6>
                <table class="table table-sm mt-2 mb-0">
                    <tr><td>الخوارزمية</td><td><strong>XGBoost</strong></td></tr>
                    <tr><td>الإصدار</td><td>{{ $dashboard['model_version'] ?? '1.0.0' }}</td></tr>
                    <tr><td>الحالة</td><td>{{ ($dashboard['is_model_trained'] ?? false) ? '✅ مدرب' : '❌ غير مدرب' }}</td></tr>
                </table>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card-custom">
                <h6><i class="fas fa-robot ml-2"></i>نموذج التوصيات</h6>
                <table class="table table-sm mt-2 mb-0">
                    <tr><td>النوع</td><td><strong>قواعد منطقية (Rule-Based)</strong></td></tr>
                    <tr><td>الحالة</td><td>{{ ($models['recommender']['trained'] ?? false) ? '✅ نشط' : '✅ نشط' }}</td></tr>
                    <tr><td>الإصدار</td><td>{{ $models['recommender']['version'] ?? '1.0.0' }}</td></tr>
                </table>
            </div>
        </div>
    </div>

    {{-- المهام عالية الخطورة --}}
    <div class="card-custom">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5><i class="fas fa-exclamation-triangle text-danger ml-2"></i>أعلى المهام خطورة</h5>
            <span class="badge bg-secondary">{{ $totalPredicted ?? 0 }} مهمة مراقبة</span>
        </div>

        @if(!empty($riskyTasks))
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="bg-light">
                        <tr>
                            <th>#</th>
                            <th>المهمة</th>
                            <th>احتمالية التأخير</th>
                            <th>مستوى الخطر</th>
                            <th>العوامل المؤثرة</th>
                            <th>إجراء</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($riskyTasks as $index => $task)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>
                                <a href="{{ route('tasks.show', $task['task_id'] ?? 0) }}" class="text-decoration-none fw-bold">
                                    {{ $task['task_name'] ?? 'مهمة #' . ($task['task_id'] ?? '') }}
                                </a>
                            </td>
                            <td style="width: 150px;">
                                @php $prob = ($task['delay_probability'] ?? 0); @endphp
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height: 8px;">
                                        <div class="progress-bar bg-{{ $prob > 0.7 ? 'danger' : ($prob > 0.4 ? 'warning' : 'info') }}" 
                                             style="width: {{ min($prob * 100, 100) }}%"></div>
                                    </div>
                                    <small class="fw-bold">{{ round($prob * 100) }}%</small>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-{{ ($task['risk_level'] ?? '') == 'High' ? 'danger' : 'warning' }}">
                                    {{ $task['risk_level'] ?? 'Medium' }}
                                </span>
                            </td>
                            <td>
                                @if(!empty($task['top_factors']))
                                    @foreach(array_slice($task['top_factors'], 0, 2) as $factor)
                                        <small class="d-block text-muted">• {{ $factor['feature'] ?? '' }}</small>
                                    @endforeach
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('tasks.show', $task['task_id'] ?? 0) }}" class="btn btn-sm btn-outline-info">
                                    <i class="fas fa-eye"></i> تفاصيل
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-4">
                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                <h5>لا توجد مهام عالية الخطورة</h5>
                <p class="text-muted">جميع المهام تسير بشكل طبيعي 🎉</p>
            </div>
        @endif
    </div>
</div>
@endsection

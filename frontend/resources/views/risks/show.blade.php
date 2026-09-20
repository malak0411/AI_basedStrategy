@extends('layouts.app')


@section('title', 'تفاصيل الخطر')


@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3><i class="fas fa-exclamation-triangle ml-2"></i>تفاصيل الخطر</h3>
            <p class="text-muted mb-0">#{{ $risk['risk_id'] ?? '' }} - {{ $risk['name'] ?? '' }}</p>
        </div>
        <div>
            <a href="{{ route('risks.edit', $risk['risk_id']) }}" class="btn btn-outline-primary">
                <i class="fas fa-edit"></i> تعديل
            </a>
            <a href="{{ route('risks.mitigations', $risk['risk_id']) }}" class="btn btn-outline-info">
                <i class="fas fa-tasks"></i> الإجراءات
            </a>
            <a href="{{ route('risks.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-right"></i> العودة
            </a>
        </div>
    </div>


    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif


    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif


    <div class="row">
        <div class="col-md-8">
            <div class="card-custom">
                <div class="card-body">
                    <h5><i class="fas fa-info-circle text-primary me-2"></i>معلومات الخطر</h5>
                    <table class="table table-borderless">
                        <tr>
                            <td class="fw-bold" style="width:180px;">اسم الخطر</td>
                            <td>{{ $risk['name'] ?? '' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">الوصف</td>
                            <td>{{ $risk['description'] ?? 'لا يوجد وصف' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">المهمة التشغيلية</td>
                            <td>
                                @if(!empty($risk['task']['task_id']))
                                <a href="{{ route('operational.task-detail', $risk['task']['task_id']) }}">
                                    {{ $risk['task']['title'] ?? '' }}
                                </a>
                                @else
                                غير مرتبط
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="fw-bold">تم التعريف بواسطة</td>
                            <td>{{ $risk['identified_by']['full_name'] ?? 'غير معروف' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">تاريخ التعريف</td>
                            <td>{{ isset($risk['identified_at']) ? \Carbon\Carbon::parse($risk['identified_at'])->format('Y-m-d H:i') : 'غير محدد' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">تاريخ الاستهداف</td>
                            <td>{{ isset($risk['target_date']) ? \Carbon\Carbon::parse($risk['target_date'])->format('Y-m-d') : 'غير محدد' }}</td>
                        </tr>
                    </table>
                </div>
            </div>


            <div class="card-custom mt-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5><i class="fas fa-robot text-primary me-2"></i>التوصيات الذكية</h5>
                        <button class="btn btn-primary btn-sm" onclick="generateRecommendations()" id="genRecsBtn">
                            <i class="fas fa-magic"></i> توليد توصيات جديدة
                        </button>
                    </div>
                    <div id="aiRecommendations">
                        @if(!empty($aiRecommendations))
                        @foreach($aiRecommendations as $rec)
                        <div class="border rounded p-3 mb-2">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <p class="mb-1"><strong>{{ $rec['text'] ?? '' }}</strong></p>
                                    @if(!empty($rec['reasoning']))
                                    <p class="text-muted small mb-0">{{ $rec['reasoning'] }}</p>
                                    @endif
                                </div>
                                <div class="text-end">
                                    @if(!empty($rec['priority']))
                                    <span class="badge bg-{{ ($rec['priority']['code'] ?? '') == 'high' ? 'danger' : (($rec['priority']['code'] ?? '') == 'medium' ? 'warning' : 'secondary') }}">
                                        {{ $rec['priority']['name_ar'] ?? '' }}
                                    </span>
                                    @endif
                                    <small class="text-muted d-block mt-1">
                                        {{ isset($rec['created_at']) ? \Carbon\Carbon::parse($rec['created_at'])->diffForHumans() : '' }}
                                    </small>
                                </div>
                            </div>
                        </div>
                        @endforeach
                        @else
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-robot fa-2x d-block mb-2"></i>
                            <p>لا توجد توصيات ذكية بعد</p>
                            <small>اضغط على "توليد توصيات جديدة" لإنشاء توصيات مبنية على الذكاء الاصطناعي</small>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>


        <div class="col-md-4">
            <div class="card-custom">
                <div class="card-body">
                    <h5><i class="fas fa-chart-line text-success me-2"></i>تقييم الخطر</h5>
                    <div class="text-center">
                        <div class="row mt-3">
                            <div class="col-6">
                                <small class="text-muted d-block">الاحتمال</small>
                                <h3 class="text-primary">{{ $risk['probability'] ?? 0 }}<small class="text-muted">/10</small></h3>
                            </div>
                            <div class="col-6">
                                <small class="text-muted d-block">التأثير</small>
                                <h3 class="text-primary">{{ $risk['impact'] ?? 0 }}<small class="text-muted">/10</small></h3>
                            </div>
                        </div>
                        <div class="mt-3">
                            <small class="text-muted d-block">درجة الخطر</small>
                            <h2 class="fw-bold {{ ($risk['risk_score'] ?? 0) >= 60 ? 'text-danger' : (($risk['risk_score'] ?? 0) >= 40 ? 'text-warning' : 'text-success') }}">
                                {{ $risk['risk_score'] ?? 0 }}<small class="text-muted">/100</small>
                            </h2>
                        </div>
                        <div class="mt-3">
                            <small class="text-muted d-block">مستوى الخطر</small>
                            @php
                                $level = $risk['risk_level'] ?? [];
                                $color = $level['color_hex'] ?? '#6c757d';
                            @endphp
                            <span class="badge" style="background-color: {{ $color }}; color: #fff; font-size: 16px; padding: 8px 20px;">
                                {{ $level['name_ar'] ?? 'غير محدد' }}
                            </span>
                        </div>
                        <div class="mt-3">
                            <small class="text-muted d-block">الحالة</small>
                            @php
                                $status = $risk['status'] ?? [];
                                $statusColor = $status['color_hex'] ?? '#6c757d';
                            @endphp
                            <span class="badge" style="background-color: {{ $statusColor }}; color: #fff; font-size: 14px;">
                                {{ $status['name_ar'] ?? 'غير محدد' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>


            <div class="card-custom mt-3">
                <div class="card-body">
                    <h5><i class="fas fa-redo text-info me-2"></i>إعادة تقييم ذكية</h5>
                    <p class="text-muted small">
                        إعادة تقييم الاحتمال والتأثير بناءً على الإجراءات المنجزة.
                    </p>
                    <button class="btn btn-info w-100" onclick="reassessRisk()" id="reassessBtn">
                        <i class="fas fa-sync-alt"></i> إعادة التقييم بالذكاء الاصطناعي
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>


<div id="loadingOverlay" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(255,255,255,0.8);z-index:9999;align-items:center;justify-content:center;">
    <div class="text-center">
        <div class="spinner-border text-primary mb-3" role="status" style="width:3rem;height:3rem;"></div>
        <p class="text-muted" id="loadingText">جارٍ المعالجة...</p>
    </div>
</div>
@endsection


@push('scripts')
<script>
var currentRiskId = {{ $risk['risk_id'] ?? 0 }};


function showLoading(text) {
    document.getElementById('loadingText').textContent = text || 'جارٍ المعالجة...';
    document.getElementById('loadingOverlay').style.display = 'flex';
}


function hideLoading() {
    document.getElementById('loadingOverlay').style.display = 'none';
}


function generateRecommendations() {
    showLoading('جارٍ توليد التوصيات بالذكاء الاصطناعي...');
    document.getElementById('genRecsBtn').disabled = true;


    var token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');


    fetch('/risks/' + currentRiskId + '/recommendations', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token,
            'Accept': 'application/json'
        },
        credentials: 'same-origin'
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        hideLoading();
        document.getElementById('genRecsBtn').disabled = false;


        if (data.success) {
            alert('تم توليد التوصيات بنجاح');
            location.reload();
        } else {
            alert('خطأ: ' + (data.error || 'فشل توليد التوصيات'));
        }
    })
    .catch(function(error) {
        hideLoading();
        document.getElementById('genRecsBtn').disabled = false;
        alert('حدث خطأ: ' + error);
    });
}


function reassessRisk() {
    if (!confirm('هل أنت متأكد من إعادة تقييم الخطر بالذكاء الاصطناعي؟')) return;


    showLoading('جارٍ إعادة تقييم الخطر...');
    document.getElementById('reassessBtn').disabled = true;


    var token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');


    fetch('/risks/' + currentRiskId + '/reassess', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token,
            'Accept': 'application/json'
        },
        credentials: 'same-origin'
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        hideLoading();
        document.getElementById('reassessBtn').disabled = false;


        if (data.success) {
            var d = data.data || {};
            alert('تم إعادة التقييم\nالدرجة القديمة: ' + d.old_score + '\nالدرجة الجديدة: ' + d.new_score);
            location.reload();
        } else {
            alert('خطأ: ' + (data.error || 'فشل إعادة التقييم'));
        }
    })
    .catch(function(error) {
        hideLoading();
        document.getElementById('reassessBtn').disabled = false;
        alert('حدث خطأ: ' + error);
    });
}
</script>
@endpush

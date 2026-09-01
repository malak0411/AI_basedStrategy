@extends('layouts.app')

@section('title', 'إضافة خطر جديد')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3><i class="fas fa-plus ml-2"></i>إضافة خطر جديد</h3>
            <p class="text-muted mb-0">إنشاء خطر جديد مرتبط بمهمة تشغيلية</p>
        </div>
        <a href="{{ route('risks.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-right"></i> العودة
        </a>
    </div>

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="card-custom">
        <div class="card-body">
            <form action="{{ route('risks.store') }}" method="POST">
                @csrf

                <div class="row">
                    <div class="col-md-8">
                        <h5 class="mb-3"><i class="fas fa-info-circle text-primary"></i> معلومات الخطر</h5>

                        <div class="mb-3">
                            <label class="form-label">اسم الخطر <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" 
                                value="{{ old('name') }}" placeholder="أدخل اسم الخطر" required>
                            @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">الوصف</label>
                            <textarea name="description" class="form-control @error('description') is-invalid @enderror" 
                                rows="3" placeholder="وصف الخطر وتأثيره المحتمل">{{ old('description') }}</textarea>
                            @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">المهمة التشغيلية</label>
                            <select name="task_id" class="form-control @error('task_id') is-invalid @enderror">
                                <option value="">اختر مهمة تشغيلية</option>
                                @foreach(($options['tasks'] ?? []) as $task)
                                <option value="{{ $task['task_id'] }}" {{ old('task_id') == $task['task_id'] ? 'selected' : '' }}>
                                    {{ $task['title'] }}
                                </option>
                                @endforeach
                            </select>
                            @error('task_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">نسبة الاحتمال <span class="text-danger">*</span></label>
                                    <select name="probability" class="form-control @error('probability') is-invalid @enderror" required>
                                        <option value="">اختر الاحتمال</option>
                                        <option value="1" {{ old('probability') == 1 ? 'selected' : '' }}>1 - منخفض جداً</option>
                                        <option value="2" {{ old('probability') == 2 ? 'selected' : '' }}>2 - منخفض</option>
                                        <option value="3" {{ old('probability') == 3 ? 'selected' : '' }}>3 - متوسط</option>
                                        <option value="4" {{ old('probability') == 4 ? 'selected' : '' }}>4 - مرتفع</option>
                                        <option value="5" {{ old('probability') == 5 ? 'selected' : '' }}>5 - مرتفع جداً</option>
                                    </select>
                                    @error('probability')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">نسبة التأثير <span class="text-danger">*</span></label>
                                    <select name="impact" class="form-control @error('impact') is-invalid @enderror" required>
                                        <option value="">اختر التأثير</option>
                                        <option value="1" {{ old('impact') == 1 ? 'selected' : '' }}>1 - ضئيل</option>
                                        <option value="2" {{ old('impact') == 2 ? 'selected' : '' }}>2 - بسيط</option>
                                        <option value="3" {{ old('impact') == 3 ? 'selected' : '' }}>3 - متوسط</option>
                                        <option value="4" {{ old('impact') == 4 ? 'selected' : '' }}>4 - كبير</option>
                                        <option value="5" {{ old('impact') == 5 ? 'selected' : '' }}>5 - كارثي</option>
                                    </select>
                                    @error('impact')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">تاريخ الاستهداف</label>
                                    <input type="date" name="target_date" class="form-control @error('target_date') is-invalid @enderror" 
                                        value="{{ old('target_date') }}">
                                    @error('target_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">الحالة</label>
                                    <select name="status_id" class="form-control @error('status_id') is-invalid @enderror">
                                        <option value="">اختر الحالة</option>
                                        @foreach(($options['statuses'] ?? []) as $status)
                                        <option value="{{ $status['status_id'] }}" {{ old('status_id') == $status['status_id'] ? 'selected' : '' }}>
                                            {{ $status['name_ar'] }}
                                        </option>
                                        @endforeach
                                    </select>
                                    @error('status_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card-custom bg-light">
                            <div class="card-body">
                                <h5 class="mb-3"><i class="fas fa-calculator text-success"></i> حساب درجة الخطر</h5>
                                <div class="text-center">
                                    <div class="row">
                                        <div class="col-6">
                                            <small class="text-muted">الاحتمال</small>
                                            <h4 id="probabilityDisplay" class="text-primary">0</h4>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted">التأثير</small>
                                            <h4 id="impactDisplay" class="text-primary">0</h4>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <small class="text-muted">درجة الخطر</small>
                                        <h2 id="riskScoreDisplay" class="fw-bold">0</h2>
                                    </div>
                                    <div class="mt-2">
                                        <small class="text-muted">مستوى الخطر</small>
                                        <h4 id="riskLevelDisplay"><span class="badge bg-secondary">غير محدد</span></h4>
                                    </div>
                                </div>
                                <hr>
                                <div class="text-muted small">
                                    <i class="fas fa-info-circle"></i>
                                    درجة الخطر = الاحتمال × التأثير
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border-top pt-3 mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> حفظ الخطر
                    </button>
                    <a href="{{ route('risks.index') }}" class="btn btn-outline-secondary">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var probabilitySelect = document.getElementById('probability');
    var impactSelect = document.getElementById('impact');
    var probabilityDisplay = document.getElementById('probabilityDisplay');
    var impactDisplay = document.getElementById('impactDisplay');
    var riskScoreDisplay = document.getElementById('riskScoreDisplay');
    var riskLevelDisplay = document.getElementById('riskLevelDisplay');

    var riskLevels = @json($options['risk_levels'] ?? []);

    function updateRiskScore() {
        var prob = parseInt(probabilitySelect.value) || 0;
        var imp = parseInt(impactSelect.value) || 0;
        var score = prob * imp;

        probabilityDisplay.textContent = prob;
        impactDisplay.textContent = imp;
        riskScoreDisplay.textContent = score;

        var level = riskLevels.find(function(l) {
            return score >= l.min_score && score <= l.max_score;
        });

        if (level) {
            riskLevelDisplay.innerHTML = '<span class="badge" style="background-color: ' + level.color_hex + '; color: #fff;">' + level.name_ar + '</span>';
        } else {
            riskLevelDisplay.innerHTML = '<span class="badge bg-secondary">غير محدد</span>';
        }
    }

    probabilitySelect.addEventListener('change', updateRiskScore);
    impactSelect.addEventListener('change', updateRiskScore);
    updateRiskScore();
});
</script>
@endpush

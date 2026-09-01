@extends('layouts.app')

@section('title', 'تعديل مؤشر الأداء')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3><i class="fas fa-edit ml-2"></i>تعديل مؤشر الأداء</h3>
            <p class="text-muted mb-0">{{ $kpi['name'] ?? '' }}</p>
        </div>
        <a href="{{ route('kpis.index') }}" class="btn btn-outline-secondary">
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
            <form action="{{ route('kpis.update', $kpi['kpi_id']) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-md-8">
                        <h5 class="mb-3"><i class="fas fa-info-circle text-primary"></i> المعلومات الأساسية</h5>

                        <div class="mb-3">
                            <label class="form-label">اسم المؤشر <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" 
                                value="{{ old('name', $kpi['name'] ?? '') }}" required>
                            @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">الوصف</label>
                            <textarea name="description" class="form-control @error('description') is-invalid @enderror" 
                                rows="3">{{ old('description', $kpi['description'] ?? '') }}</textarea>
                            @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">التصنيف</label>
                                    <input type="text" name="category" class="form-control @error('category') is-invalid @enderror" 
                                        value="{{ old('category', $kpi['category'] ?? '') }}">
                                    @error('category')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">وحدة القياس</label>
                                    <input type="text" name="unit" class="form-control @error('unit') is-invalid @enderror" 
                                        value="{{ old('unit', $kpi['unit'] ?? '') }}">
                                    @error('unit')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">طريقة الحساب</label>
                                    <input type="text" name="calculation_method" class="form-control @error('calculation_method') is-invalid @enderror" 
                                        value="{{ old('calculation_method', $kpi['calculation_method'] ?? '') }}">
                                    @error('calculation_method')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">الحد الأدنى</label>
                                    <input type="number" step="0.01" name="target_min" class="form-control @error('target_min') is-invalid @enderror" 
                                        value="{{ old('target_min', $kpi['target_min'] ?? '') }}">
                                    @error('target_min')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">الحد الأعلى</label>
                                    <input type="number" step="0.01" name="target_max" class="form-control @error('target_max') is-invalid @enderror" 
                                        value="{{ old('target_max', $kpi['target_max'] ?? '') }}">
                                    @error('target_max')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <h5 class="mb-3"><i class="fas fa-bullseye text-success"></i> الارتباط الاستراتيجي</h5>

                        <div class="mb-3">
                            <label class="form-label">الهدف الاستراتيجي <span class="text-danger">*</span></label>
                            <select name="goal_id" class="form-control @error('goal_id') is-invalid @enderror" required>
                                <option value="">اختر هدفاً استراتيجياً</option>
                                @foreach($goals ?? [] as $goal)
                                <option value="{{ $goal['goal_id'] }}" 
                                    {{ old('goal_id', $kpi['goal']['goal_id'] ?? '') == $goal['goal_id'] ? 'selected' : '' }}>
                                    {{ $goal['title'] }}
                                </option>
                                @endforeach
                            </select>
                            @error('goal_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">القيمة الأساسية (Baseline)</label>
                            <input type="number" step="0.01" name="baseline_value" class="form-control @error('baseline_value') is-invalid @enderror" 
                                value="{{ old('baseline_value', $kpi['goal_kpi']['baseline_value'] ?? '') }}">
                            @error('baseline_value')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">القيمة المستهدفة <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="target_value" class="form-control @error('target_value') is-invalid @enderror" 
                                value="{{ old('target_value', $kpi['goal_kpi']['target_value'] ?? '') }}" required>
                            @error('target_value')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">الوزن</label>
                            <input type="number" step="0.1" name="weight" class="form-control @error('weight') is-invalid @enderror" 
                                value="{{ old('weight', $kpi['goal_kpi']['weight'] ?? 1) }}">
                            @error('weight')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">قيمة الوزن بين 0 و 100</small>
                        </div>
                    </div>
                </div>

                <div class="border-top pt-3 mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> تحديث المؤشر
                    </button>
                    <a href="{{ route('kpis.index') }}" class="btn btn-outline-secondary">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

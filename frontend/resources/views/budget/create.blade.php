@extends('layouts.app')

@section('title', 'إضافة ميزانية جديدة')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3><i class="fas fa-plus ml-2"></i>إضافة ميزانية جديدة</h3>
            <p class="text-muted mb-0">إنشاء ميزانية جديدة مرتبطة ببرنامج، مبادرة، مهمة رئيسية، أو مهمة تشغيلية</p>
        </div>
        <a href="{{ route('budget.index') }}" class="btn btn-outline-secondary">
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
            <form action="{{ route('budget.store') }}" method="POST">
                @csrf

                <div class="row">
                    <div class="col-md-6">
                        <h5 class="mb-3"><i class="fas fa-info-circle text-primary"></i> معلومات الميزانية</h5>

                        <div class="mb-3">
                            <label class="form-label">نوع الميزانية <span class="text-danger">*</span></label>
                            <select name="budgetable_type" id="budgetableType" class="form-control @error('budgetable_type') is-invalid @enderror" required>
                                <option value="">اختر نوع الميزانية</option>
                                <option value="program" {{ old('budgetable_type') == 'program' ? 'selected' : '' }}>برنامج</option>
                                <option value="initiative" {{ old('budgetable_type') == 'initiative' ? 'selected' : '' }}>مبادرة</option>
                                <option value="major_task" {{ old('budgetable_type') == 'major_task' ? 'selected' : '' }}>مهمة رئيسية</option>
                                <option value="operational_task" {{ old('budgetable_type') == 'operational_task' ? 'selected' : '' }}>مهمة تشغيلية</option>
                            </select>
                            @error('budgetable_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">العنصر المرتبط <span class="text-danger">*</span></label>
                            <select name="budgetable_id" id="budgetableItem" class="form-control @error('budgetable_id') is-invalid @enderror" required>
                                <option value="">اختر العنصر المرتبط</option>
                            </select>
                            @error('budgetable_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">القسم</label>
                            <select name="department_id" class="form-control @error('department_id') is-invalid @enderror">
                                <option value="">اختر القسم</option>
                                @foreach(($options['departments'] ?? []) as $dept)
                                <option value="{{ $dept['id'] }}" {{ old('department_id') == $dept['id'] ? 'selected' : '' }}>
                                    {{ $dept['name'] }}
                                </option>
                                @endforeach
                            </select>
                            @error('department_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">السنة المالية <span class="text-danger">*</span></label>
                            <select name="fiscal_year" class="form-control @error('fiscal_year') is-invalid @enderror" required>
                                <option value="">اختر السنة المالية</option>
                                @php
                                    $currentYear = date('Y');
                                    $years = range($currentYear - 3, $currentYear + 2);
                                @endphp
                                @foreach($years as $year)
                                <option value="{{ $year }}" {{ old('fiscal_year', $currentYear) == $year ? 'selected' : '' }}>{{ $year }}</option>
                                @endforeach
                            </select>
                            @error('fiscal_year')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">المبلغ المخصص <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="allocated_amount" class="form-control @error('allocated_amount') is-invalid @enderror" 
                                value="{{ old('allocated_amount') }}" placeholder="0.00" required>
                            @error('allocated_amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">الميزانية الأب</label>
                            <select name="parent_budget_id" class="form-control @error('parent_budget_id') is-invalid @enderror">
                                <option value="">لا يوجد</option>
                                @foreach(($options['parent_budgets'] ?? []) as $parent)
                                <option value="{{ $parent['id'] }}" {{ old('parent_budget_id') == $parent['id'] ? 'selected' : '' }}>
                                    {{ $parent['name'] }}
                                </option>
                                @endforeach
                            </select>
                            @error('parent_budget_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card-custom bg-light">
                            <div class="card-body">
                                <h5 class="mb-3"><i class="fas fa-info-circle text-info"></i> تعليمات</h5>
                                <ul class="text-muted">
                                    <li>اختر نوع الميزانية ثم العنصر المرتبط.</li>
                                    <li>المبلغ المخصص يجب أن يكون أكبر من صفر.</li>
                                    <li>يمكن ربط الميزانية بميزانية أب إذا لزم الأمر.</li>
                                    <li>السنة المالية تحدد فترة الميزانية.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border-top pt-3 mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> حفظ الميزانية
                    </button>
                    <a href="{{ route('budget.index') }}" class="btn btn-outline-secondary">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var typeSelect = document.getElementById('budgetableType');
    var itemSelect = document.getElementById('budgetableItem');

    var options = @json($options ?? []);

    function updateItems() {
        var type = typeSelect.value;
        var currentValue = itemSelect.value;
        itemSelect.innerHTML = '<option value="">اختر العنصر المرتبط</option>';

        if (!type) return;

        var items = [];
        if (type === 'program') items = options.programs || [];
        else if (type === 'initiative') items = options.initiatives || [];
        else if (type === 'major_task') items = options.major_tasks || [];
        else if (type === 'operational_task') items = options.operational_tasks || [];

        items.forEach(function(item) {
            var option = document.createElement('option');
            option.value = item.id;
            option.textContent = item.name;
            if (currentValue == item.id) option.selected = true;
            itemSelect.appendChild(option);
        });
    }

    typeSelect.addEventListener('change', updateItems);
    updateItems();
});
</script>
@endpush

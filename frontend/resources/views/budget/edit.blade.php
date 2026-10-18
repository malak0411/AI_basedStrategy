@extends('layouts.app')


@section('title', 'تعديل ميزانية')


@section('content')
<div class="container py-4" dir="rtl">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow border-0">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-edit ml-2"></i> تعديل الميزانية</h5>
                </div>
                <div class="card-body p-4">
                    <div class="alert alert-secondary">
                        <strong>البند:</strong> {{ $budget['budgetable_name'] }}<br>
                        <small class="text-muted">{{ $budget['hierarchy'] }}</small>
                    </div>


                    @if($budget['parent_warning'])
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle ml-2"></i> {{ $budget['parent_warning'] }}
                    </div>
                    @endif


                    @php
                        $isEditable = false;
                        if ($budget['budgetable_type'] == 'program' && $budget['status_id'] == 47) $isEditable = true;
                        if ($budget['budgetable_type'] == 'initiative' && $budget['status_id'] == 2) $isEditable = true;
                        if ($budget['budgetable_type'] == 'operational_task' && $budget['status_id'] == 6) $isEditable = true;
                    @endphp


                    @if(!$isEditable)
                    <div class="alert alert-danger text-center">
                        <i class="fas fa-lock fa-2x mb-2"></i><br>
                        لا يمكن تعديل الميزانية لأن البند المرتبط ليس في حالة "قيد التنفيذ".
                    </div>
                    @endif


                    <form action="{{ route('budget.update', $budget['budget_id']) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">السنة المالية</label>
                                <input type="number" name="fiscal_year" class="form-control" value="{{ $budget['fiscal_year'] }}" required {{ !$isEditable ? 'disabled' : '' }}>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">المبلغ المخصص (ريال)</label>
                                <input type="number" step="0.01" name="allocated_amount" class="form-control" value="{{ $budget['allocated_amount'] }}" required min="0" {{ !$isEditable ? 'disabled' : '' }}>
                            </div>
                        </div>


                        <div class="row mb-4">
                            <div class="col-md-4">
                                <label class="form-label text-muted">المصروف الفعلي</label>
                                <input type="text" class="form-control bg-light" value="{{ number_format($budget['spent_amount'], 2) }}" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-muted">المتبقي</label>
                                <input type="text" class="form-control bg-light" value="{{ number_format($budget['remaining_amount'], 2) }}" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-muted">الميزانية الأب (ID)</label>
                                <input type="text" class="form-control bg-light" value="{{ $budget['parent_budget_id'] ?? 'لا يوجد' }}" readonly>
                            </div>
                        </div>


                        <div class="d-flex justify-content-end gap-2 border-top pt-3">
                            <a href="{{ route('budget.index') }}" class="btn btn-light border">عودة</a>
                            @if($isEditable)
                            <button type="submit" class="btn btn-warning px-4">تحديث البيانات</button>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

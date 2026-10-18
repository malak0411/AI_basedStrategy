@extends('layouts.app')


@section('title', 'إضافة ميزانية')


@section('content')
<div class="container py-4" dir="rtl">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow border-0">
                <div class="card-header bg-navy text-white">
                    <h5 class="mb-0"><i class="fas fa-file-invoice-dollar ml-2"></i> تخصيص ميزانية جديدة</h5>
                </div>
                <div class="card-body p-4">
                    <div class="alert alert-info border-info">
                        <strong>البند المرتبط:</strong> {{ $name }} ({{ $type }})
                    </div>


                    <form action="{{ route('budget.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="budgetable_type" value="{{ $type }}">
                        <input type="hidden" name="budgetable_id" value="{{ $id }}">
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">السنة المالية</label>
                                <input type="number" name="fiscal_year" class="form-control" value="{{ date('Y') }}" required min="2000" max="2100">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">المبلغ المخصص (ريال)</label>
                                <input type="number" step="0.01" name="allocated_amount" class="form-control" required min="0" placeholder="0.00">
                            </div>
                        </div>


                        <div class="mb-4">
                            <label class="form-label fw-bold">الإدارة المسؤولة</label>
                            <select name="department_id" class="form-select">
                                <option value="">-- بدون إدارة --</option>
                                @foreach($options['departments'] ?? [] as $dept)
                                <option value="{{ $dept['id'] }}">{{ $dept['name'] }}</option>
                                @endforeach
                            </select>
                        </div>


                        <div class="d-flex justify-content-end gap-2 border-top pt-3">
                            <a href="{{ route('budget.index') }}" class="btn btn-light border">إلغاء</a>
                            <button type="submit" class="btn btn-primary px-4">حفظ الميزانية</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@extends('layouts.app')

@section('title', 'الميزانية')

@section('content')
<div class="container-fluid">
    <h3><i class="fas fa-money-bill-wave ml-2"></i>الميزانية</h3>

    @if(empty($budget))
        <div class="card-custom text-center py-5 mt-4">
            <p>لا توجد بيانات ميزانية حالياً</p>
        </div>
    @else
        <div class="stat-card mt-4">
            <h5>إجمالي الميزانية: {{ $budget['total'] ?? 0 }}</h5>
            <p>المستخدم: {{ $budget['used'] ?? 0 }} | المتبقي: {{ ($budget['total'] ?? 0) - ($budget['used'] ?? 0) }}</p>
        </div>
    @endif
</div>
@endsection

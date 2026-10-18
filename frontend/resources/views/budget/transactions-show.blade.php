@extends('layouts.app')


@section('title', 'تفاصيل العملية المالية')


@section('content')
<div class="container py-4" dir="rtl">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow border-0">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-receipt ml-2"></i> إيصال مالي #{{ $transaction['transaction_id'] }}</h5>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <th class="bg-light w-50">التاريخ</th>
                                <td>{{ \Carbon\Carbon::parse($transaction['transaction_date'])->format('Y-m-d') }}</td>
                            </tr>
                            <tr>
                                <th class="bg-light">نوع الحركة</th>
                                <td><strong>{{ $transaction['transaction_type'] }}</strong></td>
                            </tr>
                            <tr>
                                <th class="bg-light">المبلغ</th>
                                <td class="text-danger fw-bold fs-5">{{ number_format($transaction['amount'], 2) }} ريال</td>
                            </tr>
                            <tr>
                                <th class="bg-light">البيان</th>
                                <td>{{ $transaction['description'] }}</td>
                            </tr>
                            <tr>
                                <th class="bg-light">المنشئ</th>
                                <td>{{ $transaction['created_by'] }}</td>
                            </tr>
                            <tr>
                                <th class="bg-light">المعتمد</th>
                                <td>{{ $transaction['approved_by'] }}</td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <div class="text-center mt-4">
                        <button onclick="window.print()" class="btn btn-outline-secondary"><i class="fas fa-print"></i> طباعة</button>
                        <a href="{{ url()->previous() }}" class="btn btn-primary">عودة</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@extends('layouts.app')

@section('title', 'معاملات الميزانية')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3><i class="fas fa-exchange-alt ml-2"></i>معاملات الميزانية</h3>
        <a href="{{ route('budget.transactions.create') }}" class="btn-gold">
            <i class="fas fa-plus"></i> معاملة جديدة
        </a>
    </div>

    @if(empty($transactions))
        <div class="card-custom text-center py-5">
            <i class="fas fa-receipt fa-3x text-muted mb-3"></i>
            <h5>لا توجد معاملات</h5>
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-hover card-custom">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>البند</th>
                        <th>المبلغ</th>
                        <th>النوع</th>
                        <th>التاريخ</th>
                        <th>إجراء</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transactions as $t)
                    <tr>
                        <td>{{ $t['id'] ?? $loop->iteration }}</td>
                        <td>{{ $t['budget_line_name'] ?? '' }}</td>
                        <td>{{ number_format($t['amount'] ?? 0) }}</td>
                        <td>
                            <span class="badge bg-{{ ($t['type'] ?? '') == 'expense' ? 'danger' : 'success' }}">
                                {{ ($t['type'] ?? '') == 'expense' ? 'صرف' : 'إيداع' }}
                            </span>
                        </td>
                        <td>{{ $t['transaction_date'] ?? '' }}</td>
                        <td>
                            <a href="{{ route('budget.transactions.show', $t['id']) }}" class="btn btn-sm btn-outline-info">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection

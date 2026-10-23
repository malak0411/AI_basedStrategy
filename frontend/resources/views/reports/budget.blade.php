@extends('layouts.app')

@section('title', 'تقرير الميزانية')

@section('content')
<div class="container-fluid px-4 py-4" dir="rtl">
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <h3 class="fw-bold"><i class="fas fa-coins text-success ml-2"></i> تفاصيل الميزانية</h3>
        <div class="d-flex gap-2">
            <div class="dropdown">
                <button class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">تخصيص الأعمدة</button>
                <ul class="dropdown-menu p-2">
                    <li><label><input type="checkbox" class="col-toggle" data-col="col-parent" checked> التقرير الأب</label></li>
                    <li><label><input type="checkbox" class="col-toggle" data-col="col-year" checked> السنة</label></li>
                </ul>
            </div>
            <button onclick="window.print()" class="btn btn-outline-secondary"><i class="fas fa-print"></i> طباعة</button>
            <a href="{{ route('reports.index') }}" class="btn btn-secondary">عودة</a>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <table class="table table-bordered table-hover text-center align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>النوع</th>
                        <th>الاسم</th>
                        <th class="col-parent">التابع لـ (الأب)</th>
                        <th class="col-year">السنة المالية</th>
                        <th>المخصص</th>
                        <th>المصروف</th>
                        <th>المتبقي</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($budgets as $b)
                    <tr>
                        <td>
                            @if($b['type'] == 'program') <span class="badge bg-primary">برنامج</span>
                            @elseif($b['type'] == 'initiative') <span class="badge bg-info text-dark">مبادرة</span>
                            @elseif($b['type'] == 'major_task') <span class="badge bg-warning text-dark">مهمة رئيسية</span>
                            @else <span class="badge bg-secondary">مهمة تشغيلية</span>
                            @endif
                        </td>
                        <td class="fw-bold">{{ $b['name'] }}</td>
                        <td class="col-parent text-muted">{{ $b['parent_name'] }}</td>
                        <td class="col-year">{{ $b['fiscal_year'] }}</td>
                        <td class="text-primary fw-bold">{{ number_format($b['allocated'], 2) }}</td>
                        <td class="text-danger">{{ number_format($b['spent'], 2) }}</td>
                        <td class="text-success fw-bold">{{ number_format($b['remaining'], 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.col-toggle').forEach(toggle => {
        toggle.addEventListener('change', function() {
            const colClass = this.dataset.col;
            const display = this.checked ? '' : 'none';
            document.querySelectorAll('.' + colClass).forEach(el => el.style.display = display);
        });
    });
</script>
@endpush

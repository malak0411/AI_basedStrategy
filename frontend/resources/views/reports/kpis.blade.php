@extends('layouts.app')

@section('title', 'تقرير مؤشرات الأداء')

@push('styles')
<style>
    .kpi-card { border-radius: 12px; border: 1px solid #e2e8f0; background: #fff; padding: 15px; text-align: center; }
    .progress-thin { height: 10px; border-radius: 5px; }
    
    @media print {
        .no-print { display: none !important; }
        body { background: #fff; font-size: 12px; }
        .main-sidebar, .navbar, .footer { display: none !important; }
        .main-content { margin: 0 !important; padding: 0 !important; width: 100% !important; }
        .card { border: none !important; box-shadow: none !important; }
        table { page-break-inside: auto; }
        tr { page-break-inside: avoid; page-break-after: auto; }
        thead { display: table-header-group; }
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-4 py-4" dir="rtl">
    
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <div>
            <h3 class="fw-bold mb-1"><i class="fas fa-chart-line text-warning ml-2"></i> تقرير مؤشرات الأداء</h3>
            <p class="text-muted mb-0">متابعة نسب الإنجاز للمؤشرات الاستراتيجية بناءً على آخر قياس</p>
        </div>
        <div class="d-flex gap-2">
            <div class="dropdown">
                <button class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="fas fa-columns"></i> تخصيص الأعمدة
                </button>
                <ul class="dropdown-menu p-2 shadow-sm">
                    <li><label class="dropdown-item"><input type="checkbox" class="col-toggle" data-col="col-goals" checked> الأهداف المرتبطة</label></li>
                    <li><label class="dropdown-item"><input type="checkbox" class="col-toggle" data-col="col-targets" checked> القيم المستهدفة</label></li>
                    <li><label class="dropdown-item"><input type="checkbox" class="col-toggle" data-col="col-dates" checked> تاريخ القياس</label></li>
                </ul>
            </div>
            <button onclick="window.print()" class="btn btn-outline-secondary"><i class="fas fa-print"></i> طباعة</button>
            <a href="{{ route('reports.index') }}" class="btn btn-secondary">عودة</a>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4 no-print">
        <div class="card-body">
            <form action="{{ route('reports.kpis') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small text-muted">تصنيف المؤشر</label>
                    <select name="category" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">كل التصنيفات</option>
                        <option value="تقني" {{ request('category') == 'تقني' ? 'selected' : '' }}>تقني</option>
                        <option value="حوكمة" {{ request('category') == 'حوكمة' ? 'selected' : '' }}>حوكمة</option>
                        <option value="تشغيلي" {{ request('category') == 'تشغيلي' ? 'selected' : '' }}>تشغيلي</option>
                        <option value="اجتماعي" {{ request('category') == 'اجتماعي' ? 'selected' : '' }}>اجتماعي</option>
                        <option value="مالي" {{ request('category') == 'مالي' ? 'selected' : '' }}>مالي</option>
                        <option value="تنموي" {{ request('category') == 'تنموي' ? 'selected' : '' }}>تنموي</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="button" onclick="window.location.href='{{ route('reports.kpis') }}'" class="btn btn-sm btn-light border w-100">إلغاء الفلاتر</button>
                </div>
            </form>
        </div>
    </div>


    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <table class="table table-bordered table-hover text-center align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th width="5%">#</th>
                        <th width="20%" class="text-start">المؤشر</th>
                        <th width="10%">التصنيف</th>
                        <th width="5%">الوحدة</th>
                        <th width="10%" class="col-goals">الهدف الاستراتيجي</th>
                        <th width="10%" class="col-targets">المستهدف</th>
                        <th width="10%">الفعلي</th>
                        <th width="15%">نسبة الإنجاز</th>
                        <th width="10%" class="col-dates">آخر قياس</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($kpis as $index => $k)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td class="text-start">
                            <strong>{{ $k['name'] }}</strong>
                            @if($k['description'] && $k['description'] !== 'لا يوجد وصف')
                            <br><small class="text-muted" style="font-size:11px;">{{ Str::limit($k['description'], 60) }}</small>
                            @endif
                        </td>
                        <td><span class="badge bg-secondary">{{ $k['category'] }}</span></td>
                        <td>{{ $k['unit'] }}</td>
                        <td class="col-goals small text-primary">
                            @foreach($k['goals'] as $goal)
                                <i class="fas fa-bullseye ml-1"></i> {{ $goal }}<br>
                            @endforeach
                        </td>
                        <td class="col-targets fw-bold text-navy">{{ number_format($k['target_max'], 1) }}</td>
                        <td class="fw-bold {{ $k['actual'] !== null ? 'text-primary' : 'text-muted' }}">
                            {{ $k['actual'] !== null ? number_format($k['actual'], 1) : 'غير مقاس' }}
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress progress-thin flex-grow-1">
                                    <div class="progress-bar {{ $k['achievement'] >= 100 ? 'bg-success' : ($k['achievement'] >= 50 ? 'bg-warning' : 'bg-danger') }}" 
                                         role="progressbar" 
                                         style="width: {{ min(100, $k['achievement']) }}%">
                                    </div>
                                </div>
                                <span class="fw-bold small" style="min-width: 45px;">{{ $k['achievement'] }}%</span>
                            </div>
                        </td>
                        <td class="col-dates small text-muted">
                            {{ $k['last_measured'] ? \Carbon\Carbon::parse($k['last_measured'])->format('Y-m-d') : 'لم يقس' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-muted py-5">
                            <i class="fas fa-chart-line fa-3x mb-3 d-block text-light"></i>
                            لا توجد مؤشرات أداء مسجلة تطابق بحثك.
                        </td>
                    </tr>
                    @endforelse
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
            const elements = document.querySelectorAll('.' + colClass);
            
            elements.forEach(el => {
                if (this.checked) {
                    el.style.display = ''; 
                } else {
                    el.style.display = 'none'; 
                }
            });
        });
    });
</script>
@endpush

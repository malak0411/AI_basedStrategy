@extends('layouts.app')

@section('title', 'مؤشرات الأداء الرئيسية')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3><i class="fas fa-chart-line ml-2"></i>مؤشرات الأداء الرئيسية</h3>
            <p class="text-muted mb-0">متابعة مؤشرات الأداء المرتبطة بالأهداف الاستراتيجية</p>
        </div>
        <div>
            <a href="{{ route('kpis.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> إضافة مؤشر أداء
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card-custom text-center">
                <div class="card-body">
                    <h6 class="text-muted">إجمالي المؤشرات</h6>
                    <h2 class="text-primary">{{ $pagination['total'] ?? 0 }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-custom text-center">
                <div class="card-body">
                    <h6 class="text-muted">متوسط الإنجاز</h6>
                    <h2 class="text-info">
                        @php
                            $totalAchievement = 0;
                            $count = 0;
                            foreach($kpis as $kpi) {
                                if(isset($kpi['achievement_percentage'])) {
                                    $totalAchievement += $kpi['achievement_percentage'];
                                    $count++;
                                }
                            }
                            $avgAchievement = $count > 0 ? round($totalAchievement / $count) : 0;
                        @endphp
                        {{ $avgAchievement }}%
                    </h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-custom text-center">
                <div class="card-body">
                    <h6 class="text-muted">أعلى مؤشر</h6>
                    <h2 class="text-success">
                        @php
                            $maxAchievement = 0;
                            foreach($kpis as $kpi) {
                                if(isset($kpi['achievement_percentage']) && $kpi['achievement_percentage'] > $maxAchievement) {
                                    $maxAchievement = $kpi['achievement_percentage'];
                                }
                            }
                        @endphp
                        {{ round($maxAchievement) }}%
                    </h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-custom text-center">
                <div class="card-body">
                    <h6 class="text-muted">المؤشرات المقاسة</h6>
                    <h2 class="text-warning">
                        @php
                            $measured = 0;
                            foreach($kpis as $kpi) {
                                if(isset($kpi['current_value'])) $measured++;
                            }
                        @endphp
                        {{ $measured }}
                    </h2>
                </div>
            </div>
        </div>
    </div>

    <div class="card-custom">
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-4">
                    <input type="text" id="searchInput" class="form-control" placeholder="بحث باسم المؤشر..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select id="categoryFilter" class="form-control">
                        <option value="">جميع التصنيفات</option>
                        @foreach($categories ?? [] as $category)
                        <option value="{{ $category }}" {{ request('category') == $category ? 'selected' : '' }}>{{ $category }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select id="goalFilter" class="form-control">
                        <option value="">جميع الأهداف</option>
                        @foreach($goals ?? [] as $goal)
                        <option value="{{ $goal['goal_id'] }}" {{ request('goal_id') == $goal['goal_id'] ? 'selected' : '' }}>
                            {{ $goal['title'] }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button id="filterBtn" class="btn btn-primary w-100"><i class="fas fa-filter"></i> تصفية</button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>المؤشر</th>
                            <th>الهدف الاستراتيجي</th>
                            <th>القيمة الحالية</th>
                            <th>الهدف</th>
                            <th>نسبة الإنجاز</th>
                            <th>الاتجاه</th>
                            <th>آخر تحديث</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($kpis as $kpi)
                        <tr>
                            <td>
                                <strong>{{ $kpi['name'] ?? '' }}</strong>
                                <br><small class="text-muted">{{ $kpi['category'] ?? '' }}</small>
                            </td>
                            <td>{{ $kpi['goal_title'] ?? 'غير مرتبط' }}</td>
                            <td>
                                @if(isset($kpi['current_value']))
                                    {{ number_format($kpi['current_value'], 2) }} {{ $kpi['unit'] ?? '' }}
                                @else
                                    <span class="text-muted">--</span>
                                @endif
                            </td>
                            <td>
                                @if(isset($kpi['target_value']))
                                    {{ number_format($kpi['target_value'], 2) }} {{ $kpi['unit'] ?? '' }}
                                @else
                                    <span class="text-muted">--</span>
                                @endif
                            </td>
                            <td>
                                @if(isset($kpi['achievement_percentage']))
                                    <div class="progress" style="height:20px;width:100px;">
                                        <div class="progress-bar 
                                            @if($kpi['achievement_percentage'] >= 100) bg-success
                                            @elseif($kpi['achievement_percentage'] >= 80) bg-info
                                            @elseif($kpi['achievement_percentage'] >= 60) bg-warning
                                            @else bg-danger @endif" 
                                            role="progressbar" 
                                            style="width: {{ min($kpi['achievement_percentage'], 100) }}%;" 
                                            aria-valuenow="{{ $kpi['achievement_percentage'] }}" 
                                            aria-valuemin="0" 
                                            aria-valuemax="100">
                                            {{ round($kpi['achievement_percentage']) }}%
                                        </div>
                                    </div>
                                @else
                                    <span class="text-muted">--</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $trend = $kpi['trend'] ?? 'stable';
                                    $trendIcon = $trend == 'up' ? 'fa-arrow-up text-success' : ($trend == 'down' ? 'fa-arrow-down text-danger' : 'fa-minus text-muted');
                                    $trendText = $trend == 'up' ? 'تحسن' : ($trend == 'down' ? 'تراجع' : 'ثابت');
                                @endphp
                                <span title="{{ $trendText }}">
                                    <i class="fas {{ $trendIcon }}"></i>
                                </span>
                            </td>
                            <td>
                                @if(isset($kpi['last_updated']))
                                    {{ \Carbon\Carbon::parse($kpi['last_updated'])->format('Y-m-d H:i') }}
                                @else
                                    <span class="text-muted">--</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="{{ route('kpis.show', $kpi['kpi_id']) }}" class="btn btn-sm btn-outline-info" title="عرض التفاصيل">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('kpis.edit', $kpi['kpi_id']) }}" class="btn btn-sm btn-outline-primary" title="تعديل">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="{{ route('kpis.measurements', $kpi['kpi_id']) }}" class="btn btn-sm btn-outline-secondary" title="القياسات">
                                        <i class="fas fa-list"></i>
                                    </a>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteKpi({{ $kpi['kpi_id'] }}, '{{ addslashes($kpi['name']) }}')" title="حذف">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <i class="fas fa-chart-line fa-2x text-muted d-block mb-2"></i>
                                <p class="text-muted">لا توجد مؤشرات أداء</p>
                                <a href="{{ route('kpis.create') }}" class="btn btn-primary btn-sm">إضافة مؤشر أداء</a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if(isset($pagination['total_pages']) && $pagination['total_pages'] > 1)
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div>
                    عرض {{ ($pagination['page'] - 1) * $pagination['per_page'] + 1 }} - {{ min($pagination['page'] * $pagination['per_page'], $pagination['total']) }} من {{ $pagination['total'] }}
                </div>
                <nav>
                    <ul class="pagination">
                        @for($i = 1; $i <= $pagination['total_pages']; $i++)
                        <li class="page-item {{ $i == $pagination['page'] ? 'active' : '' }}">
                            <a class="page-link" href="?page={{ $i }}&per_page={{ $pagination['per_page'] }}">{{ $i }}</a>
                        </li>
                        @endfor
                    </ul>
                </nav>
            </div>
            @endif
        </div>
    </div>
</div>

<form id="deleteForm" method="POST" style="display:none;">
    @csrf
    @method('DELETE')
</form>
@endsection

@push('scripts')
<script>
    function deleteKpi(id, name) {
        if (confirm('هل أنت متأكد من حذف المؤشر "' + name + '"؟')) {
            var form = document.getElementById('deleteForm');
            form.action = '/kpis/' + id;
            form.submit();
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        var searchInput = document.getElementById('searchInput');
        var categoryFilter = document.getElementById('categoryFilter');
        var goalFilter = document.getElementById('goalFilter');
        var filterBtn = document.getElementById('filterBtn');

        function applyFilters() {
            var url = new URL(window.location.href);
            var search = searchInput.value.trim();
            var category = categoryFilter.value;
            var goal = goalFilter.value;

            if (search) {
                url.searchParams.set('search', search);
            } else {
                url.searchParams.delete('search');
            }

            if (category) {
                url.searchParams.set('category', category);
            } else {
                url.searchParams.delete('category');
            }

            if (goal) {
                url.searchParams.set('goal_id', goal);
            } else {
                url.searchParams.delete('goal_id');
            }

            window.location.href = url.toString();
        }

        filterBtn.addEventListener('click', applyFilters);

        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                applyFilters();
            }
        });
    });
</script>
@endpush

@extends('layouts.app')

@section('title', 'تفاصيل الإدارة')

@section('content')
<div class="container-fluid px-4">
    <a href="{{ route('admin.departments.index') }}" class="btn btn-outline-secondary mb-3">
        <i class="fas fa-arrow-right"></i> العودة
    </a>

    @if(empty($department))
        <div class="alert alert-info">الإدارة غير موجودة</div>
    @else
        {{-- رأس الصفحة --}}
        <div class="card-custom mb-4">
            <h4>{{ $department['name'] ?? '' }}</h4>
            <p class="text-muted">{{ $department['description'] ?? '' }}</p>
            <div class="row mt-3">
                <div class="col-md-3"><strong>الكود:</strong> {{ $department['code'] ?? '' }}</div>
                <div class="col-md-3"><strong>المدير:</strong> {{ $department['manager_name'] ?? 'غير محدد' }}</div>
                <div class="col-md-3"><strong>المستوى:</strong> {{ $department['level'] ?? '' }}</div>
                <div class="col-md-3"><strong>الإدارة الأم:</strong> {{ $department['parent_name'] ?? 'لا يوجد' }}</div>
            </div>
        </div>

        {{-- تبويبات --}}
        <ul class="nav nav-tabs mb-4" id="deptTabs">
            <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="tab" href="#employees">الموظفون</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#budget">الميزانية</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#tasks">المهام</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#initiatives">المبادرات</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#kpis">مؤشرات الأداء</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#pestel">تحليل PESTEL</a>
            </li>
        </ul>

        <div class="tab-content">
            {{-- الموظفون --}}
            <div class="tab-pane fade show active" id="employees">
                <div class="card-custom">
                    <h5>الموظفون</h5>
                    @if(!empty($department['employees']))
                        <table class="table">
                            <thead><tr><th>الاسم</th><th>المسمى</th><th>الحالة</th></tr></thead>
                            <tbody>
                                @foreach($department['employees'] as $emp)
                                <tr>
                                    <td>{{ $emp['full_name'] ?? '' }}</td>
                                    <td>{{ $emp['job_title'] ?? '' }}</td>
                                    <td><span class="badge bg-{{ ($emp['is_active'] ?? false) ? 'success' : 'danger' }}">{{ ($emp['is_active'] ?? false) ? 'نشط' : 'غير نشط' }}</span></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p class="text-muted">لا يوجد موظفين</p>
                    @endif
                </div>
            </div>

            {{-- الميزانية --}}
            <div class="tab-pane fade" id="budget">
                <div class="card-custom">
                    <h5>الميزانية</h5>
                    <p><strong>المخصص:</strong> {{ number_format($department['budget_allocated'] ?? 0) }}</p>
                    <p><strong>المستخدم:</strong> {{ number_format($department['budget_used'] ?? 0) }}</p>
                </div>
            </div>

            {{-- المهام --}}
            <div class="tab-pane fade" id="tasks">
                <div class="card-custom">
                    <h5>المهام</h5>
                    <p><strong>إجمالي المهام:</strong> {{ $department['total_tasks'] ?? 0 }}</p>
                    <p><strong>المكتملة:</strong> {{ $department['completed_tasks'] ?? 0 }}</p>
                </div>
            </div>

            {{-- المبادرات --}}
            <div class="tab-pane fade" id="initiatives">
                <div class="card-custom">
                    <h5>المبادرات</h5>
                    @if(!empty($department['initiatives']))
                        <ul>
                            @foreach($department['initiatives'] as $init)
                                <li>{{ $init['name'] ?? $init['title'] ?? '' }}</li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted">لا توجد مبادرات</p>
                    @endif
                </div>
            </div>

            {{-- مؤشرات الأداء --}}
            <div class="tab-pane fade" id="kpis">
                <div class="card-custom">
                    <h5>مؤشرات الأداء</h5>
                    @if(!empty($department['kpis']))
                        @foreach($department['kpis'] as $kpi)
                            <p>{{ $kpi['name'] ?? '' }}: {{ $kpi['current_value'] ?? 0 }}%</p>
                        @endforeach
                    @else
                        <p class="text-muted">لا توجد مؤشرات</p>
                    @endif
                </div>
            </div>

            {{-- PESTEL --}}
            <div class="tab-pane fade" id="pestel">
                <div class="card-custom">
                    <h5>تحليل PESTEL</h5>
                    <p class="text-muted">بيانات التحليل ستظهر هنا</p>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

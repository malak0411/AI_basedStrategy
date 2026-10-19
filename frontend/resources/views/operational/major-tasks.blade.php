@extends('layouts.app')


@section('title', 'المهام الرئيسية للإدارة')


@push('styles')
<style>
    .task-card {
        background: #fff; border-radius: 16px; padding: 24px; height: 100%;
        border: 1px solid #e2e8f0; transition: all 0.3s ease; cursor: pointer;
        border-right: 5px solid #d4af37; position: relative; overflow: hidden;
        display: flex; flex-direction: column;
    }
    .task-card:hover { box-shadow: 0 8px 25px rgba(0,0,0,0.08); transform: translateY(-2px); }
    .task-card.support { border-right-color: #718096; }
    .task-card.inactive { opacity: 0.6; background: #f8f9fa; }


    .empty-overlay {
        position: absolute;
        inset: 0;
        background: rgba(255, 255, 255, 0.97);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 24px;
        z-index: 10;
        border-radius: 16px;
    }
    .empty-overlay i  { color: #cbd5e0; }
    .empty-overlay h5 { color: #2d3748; }
    .empty-overlay p  { color: #4a5568; }
    .empty-overlay small { color: #a0aec0; }


    .status-badge { padding: 4px 12px; border-radius: 12px; font-size: 11px; font-weight: 600; }
    .status-active   { background: #c6f6d5; color: #22543d; }
    .status-inactive { background: #e2e8f0; color: #4a5568; }


    .dept-badge {
        display: inline-block; padding: 2px 10px; border-radius: 12px;
        font-size: 11px; font-weight: 600;
    }
    .dept-lead    { background: #fefcbf; color: #744210; }
    .dept-support { background: #e2e8f0; color: #4a5568; }


    .filter-btn {
        border-radius: 20px; padding: 6px 20px; font-size: 14px;
        transition: all 0.3s; border: 2px solid #e2e8f0;
        background: #fff; color: #4a5568; cursor: pointer;
    }
    .filter-btn:hover { border-color: #cbd5e0; background: #f7fafc; }
    .filter-btn.active-lead    { border-color: #d4af37; background: #d4af37; color: #1a1a2e; }
    .filter-btn.active-support { border-color: #718096; background: #718096; color: #fff; }
    .filter-btn.active-all     { border-color: #3182ce; background: #3182ce; color: #fff; }


    .filter-container {
        display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 20px;
    }


    .task-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 16px;
    }
    .task-grid.hidden { display: none; }


    .count-badge {
        background: #e2e8f0; padding: 2px 10px; border-radius: 12px;
        font-size: 12px; color: #4a5568;
    }
</style>
@endpush


@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3><i class="fas fa-project-diagram ml-2"></i>المهام الرئيسية للإدارة</h3>
            <p class="text-muted mb-0">اضغط على أي مهمة لعرض تفاصيلها ومهامها التشغيلية</p>
        </div>
        <span class="text-muted" id="totalCount">0 مهام</span>
    </div>


    @php
        $leadTasks = array_filter($tasks, function($task) {
            return ($task['responsibility_type'] ?? '') === 'LEAD';
        });
        $supportTasks = array_filter($tasks, function($task) {
            return ($task['responsibility_type'] ?? '') === 'SUPPORT';
        });
        $allTasks = $tasks;
    @endphp


    <div class="filter-container">
        <button class="filter-btn active-all" id="filterAll" onclick="filterTasks('all')">
            <i class="fas fa-list"></i> الكل
            <span class="count-badge">{{ count($allTasks) }}</span>
        </button>
        <button class="filter-btn" id="filterLead" onclick="filterTasks('lead')">
            <i class="fas fa-star text-warning"></i> رئيسية (LEAD)
            <span class="count-badge">{{ count($leadTasks) }}</span>
        </button>
        <button class="filter-btn" id="filterSupport" onclick="filterTasks('support')">
            <i class="fas fa-handshake"></i> مساندة (SUPPORT)
            <span class="count-badge">{{ count($supportTasks) }}</span>
        </button>
    </div>


    @if(empty($allTasks))
    <div class="card-custom text-center py-5">
        <i class="fas fa-tasks fa-4x text-muted mb-3"></i>
        <h5>لا توجد مهام رئيسية</h5>
        <p class="text-muted mb-0">سيتم عرض المهام الرئيسية المخصصة لإدارتك هنا</p>
    </div>
    @else
    <div id="allTasksGrid" class="task-grid">
        @foreach($allTasks as $task)
        @php
            $tid = $task['id'] ?? 0;
            $active = $task['is_active'] ?? true;
            $type = $task['responsibility_type'] ?? 'SUPPORT';
            $url = route('operational.show-major-task', $tid);
            $opTasksCount = (int)($task['operational_tasks_count'] ?? $task['total_operational_tasks'] ?? 0);
        @endphp
        <div class="task-card {{ $type == 'LEAD' ? '' : 'support' }} {{ $active ? '' : 'inactive' }}"
             data-type="{{ $type }}"
             onclick="window.location='{{ $url }}'">


            @if($opTasksCount === 0)
                <div class="empty-overlay" onclick="event.stopPropagation()">
                    <i class="fas fa-cogs fa-3x mb-3"></i>
                    <h5 class="fw-bold mb-2">{{ $task['name'] ?? $task['title'] ?? '' }}</h5>
                    <p class="mb-1 fw-semibold">لم يتم إضافة أي مهام تشغيلية إلى هذه المهمة الرئيسية بعد</p>
                    <small class="mb-3 d-block">ابدأ بإضافة أول مهمة تشغيلية لهذه المهمة</small>
                    <a href="{{ $url }}" class="btn btn-sm btn-gold">
                        <i class="fas fa-plus ml-1"></i> إضافة مهمة تشغيلية
                    </a>
                </div>
            @endif


            <div class="d-flex justify-content-between align-items-start mb-3">
                @if(!empty($task['initiative_name']))
                    <span class="badge bg-warning">{{ $task['initiative_name'] }}</span>
                @else
                    <span class="badge bg-secondary">مهمة رئيسية</span>
                @endif
                <span class="status-badge {{ $active ? 'status-active' : 'status-inactive' }}">
                    {{ $active ? 'نشط' : 'غير نشط' }}
                </span>
            </div>


            <h5 class="fw-bold mb-2">{{ $task['name'] ?? $task['title'] ?? '' }}</h5>
            <p class="text-muted small mb-3">{{ Str::limit($task['description'] ?? '', 110) }}</p>


            <div class="mb-3">
                <span class="dept-badge {{ $type == 'LEAD' ? 'dept-lead' : 'dept-support' }}">
                    {{ $type == 'LEAD' ? 'رئيسية' : 'مساندة' }}
                </span>
            </div>


            <div class="small text-muted mb-3">
                @if(!empty($task['initiative_name']))
                    <div><i class="fas fa-lightbulb text-info ml-1"></i>
                        المبادرة: <strong>{{ $task['initiative_name'] }}</strong>
                    </div>
                @endif
                <div><i class="fas fa-clock text-warning ml-1"></i>
                    المدة المقدرة: <strong>{{ $task['estimated_duration_days'] ?? 0 }} يوم</strong>
                </div>
            </div>


            <div class="d-flex justify-content-between small text-muted border-top pt-2 mt-auto">
                <span><i class="fas fa-cogs text-secondary ml-1"></i>
                    {{ $opTasksCount }} مهمة تشغيلية
                </span>
            </div>
        </div>
        @endforeach
    </div>


    <div id="leadTasksGrid" class="task-grid hidden">
        @foreach($leadTasks as $task)
        @php
            $tid = $task['id'] ?? 0;
            $active = $task['is_active'] ?? true;
            $url = route('operational.show-major-task', $tid);
            $opTasksCount = (int)($task['operational_tasks_count'] ?? $task['total_operational_tasks'] ?? 0);
        @endphp
        <div class="task-card {{ $active ? '' : 'inactive' }}" onclick="window.location='{{ $url }}'">


            @if($opTasksCount === 0)
                <div class="empty-overlay" onclick="event.stopPropagation()">
                    <i class="fas fa-cogs fa-3x mb-3"></i>
                    <h5 class="fw-bold mb-2">{{ $task['name'] ?? $task['title'] ?? '' }}</h5>
                    <p class="mb-1 fw-semibold">لم يتم إضافة أي مهام تشغيلية إلى هذه المهمة الرئيسية بعد</p>
                    <small class="mb-3 d-block">ابدأ بإضافة أول مهمة تشغيلية لهذه المهمة</small>
                    <a href="{{ $url }}" class="btn btn-sm btn-gold">
                        <i class="fas fa-plus ml-1"></i> إضافة مهمة تشغيلية
                    </a>
                </div>
            @endif


            <div class="d-flex justify-content-between align-items-start mb-3">
                @if(!empty($task['initiative_name']))
                    <span class="badge bg-warning">{{ $task['initiative_name'] }}</span>
                @else
                    <span class="badge bg-secondary">مهمة رئيسية</span>
                @endif
                <span class="status-badge {{ $active ? 'status-active' : 'status-inactive' }}">
                    {{ $active ? 'نشط' : 'غير نشط' }}
                </span>
            </div>


            <h5 class="fw-bold mb-2">{{ $task['name'] ?? $task['title'] ?? '' }}</h5>
            <p class="text-muted small mb-3">{{ Str::limit($task['description'] ?? '', 110) }}</p>


            <div class="mb-3">
                <span class="dept-badge dept-lead">رئيسية</span>
            </div>


            <div class="small text-muted mb-3">
                @if(!empty($task['initiative_name']))
                    <div><i class="fas fa-lightbulb text-info ml-1"></i>
                        المبادرة: <strong>{{ $task['initiative_name'] }}</strong>
                    </div>
                @endif
                <div><i class="fas fa-clock text-warning ml-1"></i>
                    المدة المقدرة: <strong>{{ $task['estimated_duration_days'] ?? 0 }} يوم</strong>
                </div>
            </div>


            <div class="d-flex justify-content-between small text-muted border-top pt-2 mt-auto">
                <span><i class="fas fa-cogs text-secondary ml-1"></i>
                    {{ $opTasksCount }} مهمة تشغيلية
                </span>
            </div>
        </div>
        @endforeach
    </div>


    <div id="supportTasksGrid" class="task-grid hidden">
        @foreach($supportTasks as $task)
        @php
            $tid = $task['id'] ?? 0;
            $active = $task['is_active'] ?? true;
            $url = route('operational.show-major-task', $tid);
            $opTasksCount = (int)($task['operational_tasks_count'] ?? $task['total_operational_tasks'] ?? 0);
        @endphp
        <div class="task-card support {{ $active ? '' : 'inactive' }}" onclick="window.location='{{ $url }}'">


            @if($opTasksCount === 0)
                <div class="empty-overlay" onclick="event.stopPropagation()">
                    <i class="fas fa-cogs fa-3x mb-3"></i>
                    <h5 class="fw-bold mb-2">{{ $task['name'] ?? $task['title'] ?? '' }}</h5>
                    <p class="mb-1 fw-semibold">لم يتم إضافة أي مهام تشغيلية إلى هذه المهمة الرئيسية بعد</p>
                    <small class="mb-3 d-block">ابدأ بإضافة أول مهمة تشغيلية لهذه المهمة</small>
                    <a href="{{ $url }}" class="btn btn-sm btn-gold">
                        <i class="fas fa-plus ml-1"></i> إضافة مهمة تشغيلية
                    </a>
                </div>
            @endif


            <div class="d-flex justify-content-between align-items-start mb-3">
                @if(!empty($task['initiative_name']))
                    <span class="badge bg-warning">{{ $task['initiative_name'] }}</span>
                @else
                    <span class="badge bg-secondary">مهمة رئيسية</span>
                @endif
                <span class="status-badge {{ $active ? 'status-active' : 'status-inactive' }}">
                    {{ $active ? 'نشط' : 'غير نشط' }}
                </span>
            </div>


            <h5 class="fw-bold mb-2">{{ $task['name'] ?? $task['title'] ?? '' }}</h5>
            <p class="text-muted small mb-3">{{ Str::limit($task['description'] ?? '', 110) }}</p>


            <div class="mb-3">
                <span class="dept-badge dept-support">مساندة</span>
            </div>


            <div class="small text-muted mb-3">
                @if(!empty($task['initiative_name']))
                    <div><i class="fas fa-lightbulb text-info ml-1"></i>
                        المبادرة: <strong>{{ $task['initiative_name'] }}</strong>
                    </div>
                @endif
                <div><i class="fas fa-clock text-warning ml-1"></i>
                    المدة المقدرة: <strong>{{ $task['estimated_duration_days'] ?? 0 }} يوم</strong>
                </div>
            </div>


            <div class="d-flex justify-content-between small text-muted border-top pt-2 mt-auto">
                <span><i class="fas fa-cogs text-secondary ml-1"></i>
                    {{ $opTasksCount }} مهمة تشغيلية
                </span>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>
@endsection


@push('scripts')
<script>
    function filterTasks(type) {
        const allGrid = document.getElementById('allTasksGrid');
        const leadGrid = document.getElementById('leadTasksGrid');
        const supportGrid = document.getElementById('supportTasksGrid');


        const btnAll = document.getElementById('filterAll');
        const btnLead = document.getElementById('filterLead');
        const btnSupport = document.getElementById('filterSupport');


        allGrid.classList.add('hidden');
        leadGrid.classList.add('hidden');
        supportGrid.classList.add('hidden');


        btnAll.classList.remove('active-all', 'active-lead', 'active-support');
        btnLead.classList.remove('active-all', 'active-lead', 'active-support');
        btnSupport.classList.remove('active-all', 'active-lead', 'active-support');


        if (type === 'all') {
            allGrid.classList.remove('hidden');
            btnAll.classList.add('active-all');
        } else if (type === 'lead') {
            leadGrid.classList.remove('hidden');
            btnLead.classList.add('active-lead');
        } else if (type === 'support') {
            supportGrid.classList.remove('hidden');
            btnSupport.classList.add('active-support');
        }


        updateTotalCount(type);
    }


    function updateTotalCount(type) {
        const totalEl = document.getElementById('totalCount');
        const leadCount = {{ count($leadTasks) }};
        const supportCount = {{ count($supportTasks) }};
        const allCount = {{ count($allTasks) }};


        let count = allCount;
        let label = 'مهام';


        if (type === 'lead') {
            count = leadCount;
            label = 'مهام رئيسية';
        } else if (type === 'support') {
            count = supportCount;
            label = 'مهام مساندة';
        }


        totalEl.textContent = count + ' ' + label;
    }


    @if(count($allTasks) > 0)
        filterTasks('all');
    @endif
</script>
@endpush

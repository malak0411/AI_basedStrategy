@extends('layouts.app')

@section('title', 'المهام الرئيسية للإدارة')

@push('styles')
<style>
    .task-card {
        background: #fff;
        border-radius: 12px;
        padding: 16px;
        border: 1px solid #e2e8f0;
        transition: all 0.2s;
        height: 100%;
        display: flex;
        flex-direction: column;
        cursor: pointer;
    }
    .task-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.06);
        transform: translateY(-2px);
    }
    .task-card.lead {
        border-right: 4px solid #d4af37;
    }
    .task-card.support {
        border-right: 4px solid #718096;
        opacity: 0.85;
    }
    .task-card.inactive {
        opacity: 0.5;
        background: #f8f9fa;
    }
    .task-card .btn-robot {
        position: absolute;
        bottom: 12px;
        left: 12px;
        opacity: 0;
        transition: opacity 0.3s;
    }
    .task-card:hover .btn-robot {
        opacity: 1;
    }
    .task-card .task-content {
        flex: 1;
    }
    .task-card .task-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 12px;
        padding-top: 12px;
        border-top: 1px solid #f0f0f0;
    }
    .section-header {
        background: #fff;
        border-radius: 12px;
        padding: 16px 20px;
        border: 1px solid #e2e8f0;
        margin-bottom: 16px;
    }
    .section-header.lead {
        border-right: 4px solid #d4af37;
    }
    .section-header.support {
        border-right: 4px solid #718096;
    }
    .empty-state {
        background: #fff;
        border-radius: 12px;
        padding: 40px 20px;
        text-align: center;
        border: 1px dashed #e2e8f0;
    }
    .dept-badge {
        display: inline-block;
        padding: 2px 10px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
    }
    .dept-lead {
        background: #d4af37;
        color: #1a1a2e;
    }
    .dept-support {
        background: #e2e8f0;
        color: #4a5568;
    }
    .filter-btn {
        border-radius: 20px;
        padding: 6px 20px;
        font-size: 14px;
        transition: all 0.3s;
        border: 2px solid #e2e8f0;
        background: #fff;
        color: #4a5568;
        cursor: pointer;
    }
    .filter-btn:hover {
        border-color: #cbd5e0;
        background: #f7fafc;
    }
    .filter-btn.active-lead {
        border-color: #d4af37;
        background: #d4af37;
        color: #1a1a2e;
    }
    .filter-btn.active-support {
        border-color: #718096;
        background: #718096;
        color: #fff;
    }
    .filter-btn.active-all {
        border-color: #3182ce;
        background: #3182ce;
        color: #fff;
    }
    .filter-container {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-bottom: 20px;
    }
    .task-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 16px;
    }
    .task-grid.hidden {
        display: none;
    }
    .count-badge {
        background: #e2e8f0;
        padding: 2px 10px;
        border-radius: 12px;
        font-size: 12px;
        color: #4a5568;
    }
    .task-card {
        position: relative;
    }
    .task-card .btn-robot {
        position: absolute;
        bottom: 12px;
        left: 12px;
        opacity: 0;
        transition: opacity 0.3s;
        z-index: 2;
    }
    .task-card:hover .btn-robot {
        opacity: 1;
    }
    .task-card .click-overlay {
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
        z-index: 1;
    }
    .task-card .card-content {
        position: relative;
        z-index: 0;
    }
    .task-card .card-actions {
        position: relative;
        z-index: 2;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3><i class="fas fa-project-diagram ml-2"></i>المهام الرئيسية للإدارة</h3>
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
    <div class="empty-state">
        <i class="fas fa-tasks fa-4x text-muted mb-3"></i>
        <h5>لا توجد مهام رئيسية</h5>
        <p class="text-muted">سيتم عرض المهام الرئيسية المخصصة لإدارتك هنا</p>
    </div>
    @else
    <div id="allTasksGrid" class="task-grid">
        @foreach($allTasks as $task)
        @php 
            $tid = $task['id'] ?? 0; 
            $active = $task['is_active'] ?? true; 
            $type = $task['responsibility_type'] ?? 'SUPPORT';
            $url = route('operational.show-major-task', $tid);
            $generateUrl = route('operational.generate', $tid);
        @endphp
        <div class="task-card {{ $type == 'LEAD' ? 'lead' : 'support' }} {{ $active ? '' : 'inactive' }}" data-type="{{ $type }}" onclick="window.location='{{ $url }}'">
            <div class="card-content">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h6 class="mb-0">{{ $task['name'] ?? $task['title'] ?? '' }}</h6>
                    <span class="badge bg-{{ $active ? 'success' : 'secondary' }}">{{ $active ? 'نشط' : 'غير نشط' }}</span>
                </div>
                <p class="text-muted small mb-2 flex-grow-1">{{ Str::limit($task['description'] ?? '', 100) }}</p>
                @if(!empty($task['initiative_name']))
                <p class="text-muted small mb-2">
                    <i class="fas fa-lightbulb"></i> {{ $task['initiative_name'] }}
                </p>
                @endif
                <div class="task-footer">
                    <div>
                        <span class="dept-badge {{ $type == 'LEAD' ? 'dept-lead' : 'dept-support' }}">
                            {{ $type == 'LEAD' ? 'رئيسية' : 'مساندة' }}
                        </span>
                        <small class="text-muted me-2">{{ $task['estimated_duration_days'] ?? 0 }} يوم</small>
                    </div>
                </div>
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
            $generateUrl = route('operational.generate', $tid);
        @endphp
        <div class="task-card lead {{ $active ? '' : 'inactive' }}" onclick="window.location='{{ $url }}'">
            <div class="card-content">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h6 class="mb-0">{{ $task['name'] ?? $task['title'] ?? '' }}</h6>
                    <span class="badge bg-{{ $active ? 'success' : 'secondary' }}">{{ $active ? 'نشط' : 'غير نشط' }}</span>
                </div>
                <p class="text-muted small mb-2 flex-grow-1">{{ Str::limit($task['description'] ?? '', 100) }}</p>
                @if(!empty($task['initiative_name']))
                <p class="text-muted small mb-2">
                    <i class="fas fa-lightbulb"></i> {{ $task['initiative_name'] }}
                </p>
                @endif
                <div class="task-footer">
                    <div>
                        <span class="dept-badge dept-lead">رئيسية</span>
                        <small class="text-muted me-2">{{ $task['estimated_duration_days'] ?? 0 }} يوم</small>
                    </div>

                </div>
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
            $generateUrl = route('operational.generate', $tid);
        @endphp
        <div class="task-card support {{ $active ? '' : 'inactive' }}" onclick="window.location='{{ $url }}'">
            <div class="card-content">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h6 class="mb-0">{{ $task['name'] ?? $task['title'] ?? '' }}</h6>
                    <span class="badge bg-{{ $active ? 'success' : 'secondary' }}">{{ $active ? 'نشط' : 'غير نشط' }}</span>
                </div>
                <p class="text-muted small mb-2 flex-grow-1">{{ Str::limit($task['description'] ?? '', 100) }}</p>
                @if(!empty($task['initiative_name']))
                <p class="text-muted small mb-2">
                    <i class="fas fa-lightbulb"></i> {{ $task['initiative_name'] }}
                </p>
                @endif
                <div class="task-footer">
                    <div>
                        <span class="dept-badge dept-support">مساندة</span>
                        <small class="text-muted me-2">{{ $task['estimated_duration_days'] ?? 0 }} يوم</small>
                    </div>
                    </a>
                </div>
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

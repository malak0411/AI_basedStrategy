@extends('layouts.app')


@section('title', 'إدارة المبادرات - كانبان')


@push('styles')
<style>
    .kanban-board {
        display: flex;
        gap: 16px;
        overflow-x: auto;
        padding-bottom: 16px;
        min-height: calc(100vh - 280px);
    }
    .kanban-board::-webkit-scrollbar { height: 8px; }


    .kanban-column {
        flex: 0 0 300px;
        background: #f7fafc;
        border-radius: 14px;
        display: flex;
        flex-direction: column;
        max-height: calc(100vh - 240px);
        border: 1px solid #e2e8f0;
        transition: all 0.2s;
    }
    .kanban-column-header {
        padding: 14px 16px;
        border-radius: 14px 14px 0 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: 700;
        font-size: 14px;
    }
    .kanban-count {
        background: rgba(255,255,255,0.7);
        padding: 2px 10px;
        border-radius: 10px;
        font-size: 12px;
        font-weight: 600;
    }
    .kanban-column-body {
        padding: 12px;
        overflow-y: auto;
        flex: 1;
        min-height: 120px;
    }
    .kanban-column.drop-target {
        background: #ebf8ff;
        border-color: #3182ce;
    }


    .kanban-card {
        background: #fff;
        border-radius: 10px;
        padding: 12px;
        margin-bottom: 10px;
        border: 1px solid #e2e8f0;
        cursor: pointer;
        transition: all 0.2s;
        border-right: 3px solid #dd6b20;
        position: relative;
        user-select: none;
    }
    .kanban-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        transform: translateY(-1px);
    }
    .kanban-card.sortable-ghost { opacity: 0.4; background: #fffaf0; }
    .kanban-card.sortable-drag {
        transform: rotate(2deg);
        box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        cursor: grabbing;
    }


    .kanban-card-title {
        font-weight: 600;
        font-size: 13px;
        margin-bottom: 6px;
        color: #1a202c;
        padding-left: 20px;
    }
    .kanban-card-arrow {
        position: absolute;
        top: 12px;
        left: 10px;
        font-size: 11px;
        color: #a0aec0;
        transition: all 0.2s;
    }
    .kanban-card:hover .kanban-card-arrow {
        color: #dd6b20;
        transform: translateX(-2px);
    }
    .kanban-card-meta {
        font-size: 11px;
        color: #718096;
        display: flex;
        justify-content: space-between;
        margin-top: 4px;
    }
    .kanban-card-progress {
        height: 4px;
        background: #edf2f7;
        border-radius: 3px;
        margin-top: 8px;
        overflow: hidden;
    }
    .kanban-card-progress-fill {
        height: 100%;
        border-radius: 3px;
        transition: width 0.4s;
    }
    .kanban-empty {
        text-align: center;
        color: #a0aec0;
        font-size: 12px;
        padding: 20px 0;
    }
    .kanban-toast {
        position: fixed;
        bottom: 24px;
        left: 50%;
        transform: translateX(-50%) translateY(100px);
        background: #1a202c;
        color: #fff;
        padding: 12px 24px;
        border-radius: 10px;
        box-shadow: 0 6px 24px rgba(0,0,0,0.25);
        z-index: 9999;
        transition: transform 0.3s ease;
        font-size: 14px;
        font-weight: 600;
    }
    .kanban-toast.show { transform: translateX(-50%) translateY(0); }
    .kanban-toast.error { background: #e53e3e; }
    .kanban-toast.success { background: #38a169; }


    .role-hint {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        background: #fdf8e7;
        border-radius: 10px;
        font-size: 12px;
        color: #744210;
        border: 1px solid #d4af37;
    }
    .hint-click {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        background: #fffaf0;
        border-radius: 10px;
        font-size: 12px;
        color: #7b341e;
        border: 1px solid #dd6b20;
    }
</style>
@endpush


@section('content')
@php
    function progressColor($p) {
        $p = (int) $p;
        if ($p < 20)  return '#e53e3e';
        if ($p < 40)  return '#dd6b20';
        if ($p < 60)  return '#d69e2e';
        if ($p < 80)  return '#48bb78';
        return '#38a169';
    }
@endphp


<div class="container-fluid px-4">


    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h3><i class="fas fa-columns ml-2"></i>إدارة المبادرات - كانبان</h3>
            <p class="text-muted mb-0">اسحب البطاقة لتغيير الحالة • انقر عليها لعرض التفاصيل</p>
        </div>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <span class="hint-click">
                <i class="fas fa-hand-pointer"></i>
                انقر لعرض التفاصيل
            </span>
            <span class="role-hint">
                <i class="fas fa-user-shield"></i>
                دورك: 
                @if($userRole === 'minister') <strong>وزير</strong>
                @elseif($userRole === 'manager') <strong>مدير</strong>
                @else <strong>{{ $userRole }}</strong>
                @endif
            </span>
            <a href="{{ route('strategic.initiatives.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-list"></i> عرض القائمة
            </a>
        </div>
    </div>


    @if($userRole === 'minister')
        <div class="alert alert-info py-2 mb-3">
            <small><i class="fas fa-info-circle ml-1"></i>
                كوزير: يمكنك نقل المبادرات إلى <strong>"معتمد"</strong> و <strong>"ملغي"</strong>.
            </small>
        </div>
    @elseif($userRole === 'manager')
        <div class="alert alert-info py-2 mb-3">
            <small><i class="fas fa-info-circle ml-1"></i>
                كمدير: يمكنك نقل المبادرات إلى <strong>"قيد التنفيذ"</strong> و <strong>"مكتمل"</strong> (بعد الاعتماد).
            </small>
        </div>
    @endif


    <div class="kanban-board">
        @foreach($columns as $sid => $col)
        <div class="kanban-column" data-column-status="{{ $sid }}">
            <div class="kanban-column-header" style="background: {{ $col['color'] }}; color: {{ $col['text_color'] }};">
                <span>{{ $col['name'] }}</span>
                <span class="kanban-count" data-count-for="{{ $sid }}">{{ count($grouped[$sid] ?? []) }}</span>
            </div>
            <div class="kanban-column-body" data-status="{{ $sid }}">
                @forelse($grouped[$sid] ?? [] as $initiative)
                    @php
                        $prog = (int) ($initiative['progress'] ?? 0);
                        $barColor = progressColor($prog);
                        $detailUrl = route('strategic.initiatives.show', $initiative['id']);
                    @endphp
                    <div class="kanban-card" data-id="{{ $initiative['id'] }}" data-url="{{ $detailUrl }}" style="border-right-color: {{ $barColor }};">
                        <i class="fas fa-chevron-left kanban-card-arrow"></i>
                        <div class="kanban-card-title">{{ $initiative['name'] ?? '' }}</div>
                        <div class="kanban-card-meta">
                            <span><i class="fas fa-project-diagram ml-1"></i>{{ $initiative['program_name'] ?? '—' }}</span>
                        </div>
                        <div class="kanban-card-meta">
                            <span><i class="fas fa-tasks ml-1"></i>{{ $initiative['major_tasks_count'] ?? 0 }} مهمة</span>
                            <span><i class="fas fa-coins ml-1"></i>{{ number_format($initiative['budget_estimate'] ?? 0) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mt-2" style="font-size: 11px;">
                            <span class="text-muted">التقدم</span>
                            <strong style="color: {{ $barColor }};">{{ $prog }}%</strong>
                        </div>
                        <div class="kanban-card-progress">
                            <div class="kanban-card-progress-fill" style="width: {{ $prog }}%; background: {{ $barColor }};"></div>
                        </div>
                    </div>
                @empty
                    <div class="kanban-empty">لا توجد مبادرات</div>
                @endforelse
            </div>
        </div>
        @endforeach
    </div>


</div>


<div class="kanban-toast" id="kanbanToast"></div>
@endsection


@push('scripts')
<script src="{{ asset('vendor/sortablejs/Sortable.min.js') }}"></script>
<script>
const baseUrl = '{{ url("strategic/initiatives") }}';
const csrfToken = '{{ csrf_token() }}';
let toastTimer = null;
let isDragging = false;


function showToast(msg, type) {
    const toast = document.getElementById('kanbanToast');
    toast.textContent = msg;
    toast.className = 'kanban-toast ' + (type || '');
    toast.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => toast.classList.remove('show'), 3500);
}


function updateCount(statusId) {
    const column = document.querySelector(`.kanban-column-body[data-status="${statusId}"]`);
    const countEl = document.querySelector(`[data-count-for="${statusId}"]`);
    if (!column || !countEl) return;
    const cards = column.querySelectorAll('.kanban-card').length;
    countEl.textContent = cards;
    const empty = column.querySelector('.kanban-empty');
    if (cards === 0 && !empty) {
        column.insertAdjacentHTML('beforeend', '<div class="kanban-empty">لا توجد مبادرات</div>');
    } else if (cards > 0 && empty) {
        empty.remove();
    }
}


document.querySelectorAll('.kanban-column-body').forEach(column => {
    new Sortable(column, {
        group: 'initiatives-kanban',
        animation: 200,
        ghostClass: 'sortable-ghost',
        dragClass: 'sortable-drag',
        delay: 0,
        delayOnTouchOnly: true,
        touchStartThreshold: 4,
        onStart: function() {
            isDragging = true;
            document.querySelectorAll('.kanban-column').forEach(c => c.classList.add('drop-target'));
        },
        onEnd: function(evt) {
            document.querySelectorAll('.kanban-column').forEach(c => c.classList.remove('drop-target'));


            const card = evt.item;
            const initiativeId = card.dataset.id;
            const newStatus = parseInt(evt.to.dataset.status);
            const oldStatus = parseInt(evt.from.dataset.status);


            setTimeout(() => { isDragging = false; }, 150);


            if (oldStatus === newStatus) {
                updateCount(oldStatus);
                return;
            }


            fetch(baseUrl + '/' + initiativeId + '/kanban-move', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ status_id: newStatus })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showToast('✓ تم تحديث الحالة إلى: ' + (data.new_status_name || ''), 'success');
                    updateCount(oldStatus);
                    updateCount(newStatus);
                } else {
                    evt.from.appendChild(card);
                    updateCount(oldStatus);
                    updateCount(newStatus);
                    showToast('⚠ ' + (data.message || 'فشل تحديث الحالة'), 'error');
                }
            })
            .catch(err => {
                evt.from.appendChild(card);
                updateCount(oldStatus);
                updateCount(newStatus);
                showToast('⚠ حدث خطأ في الاتصال', 'error');
            });
        }
    });
});


document.querySelectorAll('.kanban-card').forEach(card => {
    card.addEventListener('click', function(e) {
        if (isDragging) return;
        const url = this.dataset.url;
        if (url) {
            window.location.href = url;
        }
    });
});
</script>
@endpush

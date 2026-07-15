@extends('layouts.app')

@section('title', 'سجل التدقيق')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3><i class="fas fa-history ml-2"></i>سجل التدقيق (Audit Logs)</h3>
        <span class="text-muted small">آخر 100 سجل</span>
    </div>

    @if(empty($logs))
        <div class="card-custom text-center py-5">
            <i class="fas fa-history fa-3x text-muted mb-3"></i>
            <h5>لا توجد سجلات</h5>
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-hover card-custom">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>المستخدم</th>
                        <th>الإجراء</th>
                        <th>الجدول</th>
                        <th>رقم السجل</th>
                        <th>التاريخ</th>
                        <th>IP</th>
                        <th>تفاصيل</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
                    <tr>
                        <td>{{ $log['id'] ?? $loop->iteration }}</td>
                        <td>
                            <strong>{{ $log['employee_name'] ?? 'غير معروف' }}</strong>
                            @if(!empty($log['employee_id']))
                                <br><small class="text-muted">#{{ $log['employee_id'] }}</small>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-{{ ($log['action'] ?? '') == 'DELETE' ? 'danger' : (($log['action'] ?? '') == 'UPDATE' ? 'warning' : (($log['action'] ?? '') == 'CREATE' ? 'success' : 'info')) }}">
                                @php
                                    $actions = [
                                        'CREATE' => 'إنشاء',
                                        'UPDATE' => 'تحديث',
                                        'DELETE' => 'حذف',
                                        'LOGIN' => 'تسجيل دخول',
                                        'LOGOUT' => 'تسجيل خروج',
                                    ];
                                @endphp
                                {{ $actions[$log['action'] ?? ''] ?? $log['action'] ?? '' }}
                            </span>
                        </td>
                        <td>{{ $log['table_name'] ?? '' }}</td>
                        <td>{{ $log['record_id'] ?? '-' }}</td>
                        <td><small>{{ $log['created_at'] ?? '' }}</small></td>
                        <td><small>{{ $log['ip_address'] ?? '' }}</small></td>
                        <td>
                            <a href="{{ route('admin.audit-logs.show', $log['id']) }}" class="btn btn-sm btn-outline-info">
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

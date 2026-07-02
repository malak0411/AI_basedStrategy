@extends('layouts.app')

@section('title', 'سجل التدقيق')

@section('content')
<div class="container-fluid px-4">
    <h3 class="mb-4"><i class="fas fa-history ml-2"></i>سجل التدقيق (Audit Logs)</h3>

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
                        <th>التاريخ</th>
                        <th>إجراء</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
                    <tr>
                        <td>{{ $log['id'] ?? $loop->iteration }}</td>
                        <td>{{ $log['user_name'] ?? $log['employee_name'] ?? '' }}</td>
                        <td>
                            <span class="badge bg-{{ ($log['action'] ?? '') == 'DELETE' ? 'danger' : (($log['action'] ?? '') == 'UPDATE' ? 'warning' : 'info') }}">
                                {{ $log['action'] ?? '' }}
                            </span>
                        </td>
                        <td>{{ $log['table_name'] ?? '' }}</td>
                        <td><small>{{ $log['created_at'] ?? '' }}</small></td>
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

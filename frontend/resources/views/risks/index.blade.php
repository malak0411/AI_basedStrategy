@extends('layouts.app')

@section('title', 'المخاطر')

@section('content')
<div class="container-fluid px-4">
    <h3><i class="fas fa-exclamation-triangle ml-2"></i>المخاطر</h3>

    @if(empty($risks))
        <div class="card-custom text-center py-5 mt-4">
            <i class="fas fa-shield-alt fa-3x text-muted mb-3"></i>
            <h5>لا توجد مخاطر حالياً</h5>
            <p class="text-muted">لم يتم تسجيل أي مخاطر بعد</p>
        </div>
    @else
        <div class="table-responsive mt-4">
            <table class="table table-bordered card-custom">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>الخطر</th>
                        <th>المستوى</th>
                        <th>الحالة</th>
                        <th>التفاصيل</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($risks as $risk)
                    <tr>
                        <td>{{ $risk['id'] ?? $loop->iteration }}</td>
                        <td>{{ $risk['name'] ?? $risk['title'] ?? 'غير محدد' }}</td>
                        <td>
                            @php $level = $risk['level'] ?? 'low'; @endphp
                            <span class="badge bg-{{ $level === 'high' ? 'danger' : ($level === 'medium' ? 'warning' : 'success') }}">
                                {{ $level === 'high' ? 'عالي' : ($level === 'medium' ? 'متوسط' : 'منخفض') }}
                            </span>
                        </td>
                        <td>{{ $risk['status'] ?? 'غير محدد' }}</td>
                        <td>{{ $risk['description'] ?? '' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection

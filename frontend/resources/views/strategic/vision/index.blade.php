@extends('layouts.app')


@section('title', 'الرؤية الاستراتيجية')


@push('styles')
<style>
    .vision-hero {
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
        color: #fff; border-radius: 20px; padding: 50px 40px;
        position: relative; overflow: hidden;
    }
    .vision-hero::before {
        content: '\f06e'; font-family: 'Font Awesome 5 Free'; font-weight: 900;
        position: absolute; left: 30px; bottom: -30px;
        font-size: 180px; opacity: 0.05; color: #d4af37;
    }
    .vision-text {
        font-size: 24px; line-height: 1.8; font-weight: 600;
        color: #d4af37; margin-bottom: 20px;
    }
    .vision-desc {
        font-size: 15px; line-height: 1.9; color: #e2e8f0; opacity: 0.9;
    }
    .vision-meta {
        display: flex; gap: 30px; flex-wrap: wrap;
        margin-top: 30px; padding-top: 25px;
        border-top: 1px solid rgba(255,255,255,0.1);
    }
    .vision-meta-item { color: #cbd5e0; font-size: 13px; }
    .vision-meta-item strong { color: #d4af37; display: block; margin-bottom: 4px; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; }
    .version-badge {
        position: absolute; top: 20px; left: 20px;
        background: rgba(212, 175, 55, 0.2); color: #d4af37;
        padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 600;
        border: 1px solid rgba(212, 175, 55, 0.3);
    }
</style>
@endpush


@section('content')
<div class="container-fluid px-4">


    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3><i class="fas fa-eye ml-2"></i>الرؤية الاستراتيجية</h3>
            <p class="text-muted mb-0">الرؤية الحالية المعتمدة للمؤسسة</p>
        </div>
        <a href="{{ route('strategic.vision.edit') }}" class="btn-gold">
            <i class="fas fa-edit"></i> تعديل الرؤية
        </a>
    </div>


    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif


    @if(empty($vision['text']))
        <div class="card-custom text-center py-5">
            <i class="fas fa-eye text-muted" style="font-size: 64px;"></i>
            <h5 class="mt-3">لا توجد رؤية استراتيجية معرّفة بعد</h5>
            <p class="text-muted">ابدأ بتعريف رؤية المؤسسة</p>
            <a href="{{ route('strategic.vision.edit') }}" class="btn-gold mt-2">
                <i class="fas fa-plus"></i> تعريف الرؤية
            </a>
        </div>
    @else
        <div class="vision-hero">
            @if(!empty($vision['version']))
                <div class="version-badge">
                    <i class="fas fa-code-branch ml-1"></i> الإصدار {{ $vision['version'] }}
                </div>
            @endif


            <div class="vision-text">
                <i class="fas fa-quote-right ml-2" style="opacity: 0.3; font-size: 18px;"></i>
                {{ $vision['text'] }}
            </div>


            @if(!empty($vision['description']))
                <div class="vision-desc">
                    {{ $vision['description'] }}
                </div>
            @endif


            <div class="vision-meta">
                @if(!empty($vision['effective_date']))
                    <div class="vision-meta-item">
                        <strong>تاريخ السريان</strong>
                        <i class="far fa-calendar ml-1"></i> {{ $vision['effective_date'] }}
                    </div>
                @endif
                @if(!empty($vision['approved_by_name']))
                    <div class="vision-meta-item">
                        <strong>اعتُمدت بواسطة</strong>
                        <i class="fas fa-user-check ml-1"></i> {{ $vision['approved_by_name'] }}
                    </div>
                @endif
                @if(!empty($vision['approved_at']))
                    <div class="vision-meta-item">
                        <strong>تاريخ الاعتماد</strong>
                        <i class="far fa-clock ml-1"></i> {{ \Carbon\Carbon::parse($vision['approved_at'])->format('Y-m-d') }}
                    </div>
                @endif
            </div>
        </div>
    @endif


</div>
@endsection

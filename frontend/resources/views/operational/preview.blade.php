@extends('layouts.app')

@section('title', 'معاينة الملف - ' . $fileName)

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>
            <i class="fas fa-eye text-primary me-2"></i>
            معاينة الملف: {{ $fileName }}
        </h4>
        <div>
            <a href="{{ route('task.attachments.download', request()->segment(2)) }}" 
               class="btn btn-success me-2">
                <i class="fas fa-download"></i> تحميل
            </a>
            <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-right"></i> العودة
            </a>
        </div>
    </div>

    <div class="card-custom shadow-sm">
        <div class="card-body p-0">
            @php
                $imageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml', 'image/webp', 'image/bmp'];
                $pdfType = 'application/pdf';
                $textTypes = ['text/plain', 'text/html', 'text/css', 'text/javascript', 'application/json'];
            @endphp

            @if(in_array($fileType, $imageTypes))
                {{-- عرض الصور --}}
                <div class="text-center p-4">
                    <img src="{{ asset($fileUrl) }}" 
                         alt="{{ $fileName }}" 
                         class="img-fluid" 
                         style="max-height: 80vh;">
                </div>

            @elseif($fileType === $pdfType)
                {{-- عرض PDF --}}
                <div class="text-center p-0" style="height: 80vh;">
                    <iframe src="{{ asset($fileUrl) }}" 
                            style="width:100%; height:100%; border:none;">
                    </iframe>
                </div>

            @elseif(in_array($fileType, $textTypes))
                {{-- عرض النصوص --}}
                <div class="p-4">
                    <pre class="bg-light p-3 rounded" style="max-height: 70vh; overflow: auto;">
                        {{ file_get_contents(public_path($fileUrl)) }}
                    </pre>
                </div>

            @else
                {{-- عرض ملفات Office باستخدام Google Docs Viewer --}}
                <div class="text-center p-0" style="height: 80vh;">
                    <iframe src="{{ $googleViewerUrl }}" 
                            style="width:100%; height:100%; border:none;">
                    </iframe>
                </div>
                <div class="text-center p-2 text-muted small">
                    <i class="fas fa-info-circle"></i>
                    يتم عرض الملف باستخدام Google Docs Viewer. 
                    <a href="{{ asset($fileUrl) }}" target="_blank">افتح الملف مباشرة</a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .card-custom {
        background: #fff;
        border-radius: 12px;
        border: none;
        box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        overflow: hidden;
    }
</style>
@endpush

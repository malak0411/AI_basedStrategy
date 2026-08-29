@extends('layouts.app')

@section('title', 'مرفقات المهمة')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>
            <i class="fas fa-paperclip text-primary me-2"></i>
            مرفقات المهمة: {{ data_get($task, 'task_name', data_get($task, 'title', '#' . ($taskId ?? 0))) }}
        </h4>
        <a href="{{ route('operational.kanban') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-right me-1"></i> العودة للوحة
        </a>
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

    {{-- زر رفع ملف جديد --}}
    <div class="card-custom shadow-sm mb-4">
        <div class="card-body">
            <h5><i class="fas fa-upload text-primary me-2"></i> رفع ملف جديد</h5>
            <form action="{{ route('task.attachments.upload', $taskId) }}" method="POST" enctype="multipart/form-data" class="row g-3">
                @csrf
                <div class="col-md-6">
                    <label for="file" class="form-label">اختر الملف <span class="text-danger">*</span></label>
                    <input type="file" class="form-control" id="file" name="file" required accept=".png,.jpg,.jpeg,.gif,.svg,.webp,.pdf,.doc,.docx,.xls,.xlsx,.csv,.ppt,.pptx,.zip,.rar,.7z">
                    <small class="text-muted">الأنواع المدعومة: صور، PDF، Word، Excel، PowerPoint، مضغوط (حجم أقصى 10 ميجابايت)</small>
                </div>
                <div class="col-md-4">
                    <label for="description" class="form-label">الوصف</label>
                    <input type="text" class="form-control" id="description" name="description" placeholder="وصف الملف (اختياري)">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-upload me-1"></i> رفع
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- قائمة المرفقات --}}
    <div class="card-custom shadow-sm">
        <div class="card-body p-4">

            @php
                if (!is_array($attachments)) {
                    $attachments = [];
                }
                $attachments = array_filter($attachments, function($item) {
                    return is_array($item) && !empty($item);
                });
                $attachments = array_values($attachments);
                $totalAttachments = count($attachments);
            @endphp

            @if($totalAttachments === 0)
                <div class="text-center py-5">
                    <i class="fas fa-file-alt fa-4x text-muted mb-3 d-block"></i>
                    <h5 class="text-muted">لا توجد مرفقات لهذه المهمة</h5>
                    <p class="text-muted small">قم برفع ملف باستخدام النموذج أعلاه</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th width="50">#</th>
                                <th>اسم الملف</th>
                                <th width="100">النوع</th>
                                <th width="100">الحجم</th>
                                <th width="180">رفع بواسطة</th>
                                <th width="160">تاريخ الرفع</th>
                                <th width="160" class="text-center">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($attachments as $index => $att)
                            @php
                                if (!is_array($att)) {
                                    $att = [];
                                }

                                $attachmentId = (int) data_get($att, 'attachment_id', 0);
                                $fileName = (string) data_get($att, 'file_name', 'ملف بدون اسم');
                                $fileType = (string) data_get($att, 'file_type', '');
                                $fileExtension = (string) data_get($att, 'file_extension', '');
                                $description = (string) data_get($att, 'description', '');
                                $createdAt = (string) data_get($att, 'created_at', '');

                                $fileSizeRaw = data_get($att, 'file_size', 0);
                                $fileSize = 0;
                                if (is_numeric($fileSizeRaw)) {
                                    $fileSize = (int) $fileSizeRaw;
                                } elseif (is_string($fileSizeRaw)) {
                                    $clean = preg_replace('/[^0-9.]/', '', $fileSizeRaw);
                                    if (is_numeric($clean)) {
                                        $fileSize = (int) $clean;
                                    }
                                }
                                if ($fileSize < 0) {
                                    $fileSize = 0;
                                }

                                $fileSizeKB = '0.00';
                                if ($fileSize > 0) {
                                    $fileSizeKB = number_format((float) $fileSize / 1024, 2, '.', '');
                                }

                                $uploadedBy = data_get($att, 'uploaded_by', []);
                                if (!is_array($uploadedBy)) {
                                    $uploadedBy = [];
                                }
                                $employeeName = (string) data_get($uploadedBy, 'full_name', 'غير معروف');

                                $avatarLetter = mb_substr($employeeName, 0, 1, 'UTF-8');

                                $icon = 'fa-file';
                                $iconColor = 'text-secondary';
                                $ext = strtolower($fileExtension);

                                if (str_contains($fileType, 'image')) {
                                    $icon = 'fa-file-image';
                                    $iconColor = 'text-success';
                                } elseif ($ext === 'pdf') {
                                    $icon = 'fa-file-pdf';
                                    $iconColor = 'text-danger';
                                } elseif (in_array($ext, ['doc', 'docx'])) {
                                    $icon = 'fa-file-word';
                                    $iconColor = 'text-primary';
                                } elseif (in_array($ext, ['xls', 'xlsx', 'csv'])) {
                                    $icon = 'fa-file-excel';
                                    $iconColor = 'text-success';
                                } elseif (in_array($ext, ['ppt', 'pptx'])) {
                                    $icon = 'fa-file-powerpoint';
                                    $iconColor = 'text-warning';
                                } elseif (in_array($ext, ['zip', 'rar', '7z'])) {
                                    $icon = 'fa-file-archive';
                                    $iconColor = 'text-secondary';
                                } elseif (in_array($ext, ['mp4', 'avi', 'mkv', 'mov'])) {
                                    $icon = 'fa-file-video';
                                    $iconColor = 'text-info';
                                } elseif (in_array($ext, ['mp3', 'wav', 'ogg'])) {
                                    $icon = 'fa-file-audio';
                                    $iconColor = 'text-info';
                                }

                                $formattedDate = 'غير محدد';
                                if (!empty($createdAt)) {
                                    try {
                                        $formattedDate = \Carbon\Carbon::parse($createdAt)->format('Y-m-d H:i');
                                    } catch (\Exception $e) {
                                        $formattedDate = $createdAt;
                                    }
                                }

                                $rowNumber = (int) $index + 1;
                            @endphp
                            <tr>
                                <td>{{ $rowNumber }}</td>
                                <td>
                                    <i class="fas {{ $icon }} {{ $iconColor }} fa-lg me-2"></i>
                                    <span title="{{ $fileName }}">{{ Str::limit($fileName, 40) }}</span>
                                    @if(!empty($description))
                                        <br><small class="text-muted">{{ $description }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        {{ strtoupper($fileExtension) ?: '---' }}
                                    </span>
                                </td>
                                <td>{{ $fileSizeKB }} ك.ب</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-placeholder text-white rounded-circle d-inline-flex align-items-center justify-content-center me-2" style="width:32px;height:32px;font-size:12px;font-weight:bold;background:linear-gradient(135deg,#6c5ce7,#0984e3);">
                                            {{ $avatarLetter }}
                                        </div>
                                        <span>{{ $employeeName }}</span>
                                    </div>
                                </td>
                                <td>
                                    <small class="text-muted">{{ $formattedDate }}</small>
                                </td>
                                <td>
    <div class="d-flex justify-content-center gap-1 flex-wrap">
        {{-- زر المعاينة --}}
        <a href="{{ route('task.attachments.preview', $attachmentId) }}"
           class="btn btn-sm btn-primary"
           target="_blank"
           title="معاينة الملف">
            <i class="fas fa-eye"></i>
        </a>
        
        {{-- زر التحميل --}}
        <a href="{{ route('task.attachments.download', $attachmentId) }}"
           class="btn btn-sm btn-success"
           title="تحميل الملف">
            <i class="fas fa-download"></i>
        </a>
        
        {{-- زر الحذف --}}
        <button type="button"
                class="btn btn-sm btn-outline-danger"
                onclick="confirmDelete('{{ $attachmentId }}', '{{ addslashes($fileName) }}')"
                title="حذف المرفق">
            <i class="fas fa-trash"></i>
        </button>
        
        <form id="delete-form-{{ $attachmentId }}"
              action="{{ route('task.attachments.destroy', $attachmentId) }}"
              method="POST"
              style="display:none;">
            @csrf
            @method('DELETE')
        </form>
    </div>
</td>

                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3 text-muted small">
                    <i class="fas fa-info-circle me-1"></i>
                    إجمالي المرفقات: {{ $totalAttachments }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .avatar-placeholder {
        flex-shrink: 0;
        font-weight: 600;
    }

    .card-custom {
        background: #fff;
        border-radius: 12px;
        border: none;
        box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        transition: all 0.2s ease;
    }

    .card-custom:hover {
        box-shadow: 0 4px 20px rgba(0,0,0,0.12);
    }

    .table th {
        font-weight: 600;
        font-size: 13px;
        border-top: none;
        padding: 12px 8px;
    }

    .table td {
        padding: 12px 8px;
        vertical-align: middle;
    }

    .table tbody tr:hover {
        background-color: #f8f9fa;
    }

    .badge.bg-light {
        background: #f1f3f5 !important;
        font-weight: 500;
        padding: 4px 10px;
        border-radius: 4px;
    }

    .btn-sm {
        padding: 4px 10px;
        font-size: 13px;
        border-radius: 6px;
    }

    .btn-outline-success:hover i,
    .btn-outline-danger:hover i {
        color: #fff;
    }

    @media (max-width: 768px) {
        .table-responsive {
            font-size: 13px;
        }
        .table td,
        .table th {
            padding: 8px 4px;
            min-width: 60px;
        }
        .avatar-placeholder {
            width: 24px !important;
            height: 24px !important;
            font-size: 10px !important;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    function confirmDelete(attachmentId, fileName) {
        if (confirm('هل أنت متأكد من حذف الملف "' + fileName + '"؟')) {
            document.getElementById('delete-form-' + attachmentId).submit();
        }
    }
</script>
@endpush
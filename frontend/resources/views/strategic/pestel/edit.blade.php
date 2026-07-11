@extends('layouts.app')

@section('title', 'تحرير تحليل PESTEL')

@section('content')
<div class="container-fluid px-4">
    <a href="{{ route('strategic.pestel.index') }}" class="btn btn-outline-secondary mb-3">
        <i class="fas fa-arrow-right"></i> العودة للعرض
    </a>

    <div class="card-custom">
        <h4 class="mb-4"><i class="fas fa-edit ml-2"></i>تحرير تحليل PESTEL</h4>

        <form method="POST" action="{{ route('strategic.pestel.update') }}">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold"><i class="fas fa-landmark"></i> سياسي (Political)</label>
                    <textarea name="political" class="form-control" rows="3">{{ $pestel['political'] ?? '' }}</textarea>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold"><i class="fas fa-chart-line"></i> اقتصادي (Economic)</label>
                    <textarea name="economic" class="form-control" rows="3">{{ $pestel['economic'] ?? '' }}</textarea>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold"><i class="fas fa-users"></i> اجتماعي (Social)</label>
                    <textarea name="social" class="form-control" rows="3">{{ $pestel['social'] ?? '' }}</textarea>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold"><i class="fas fa-microchip"></i> تقني (Technological)</label>
                    <textarea name="technological" class="form-control" rows="3">{{ $pestel['technological'] ?? '' }}</textarea>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold"><i class="fas fa-leaf"></i> بيئي (Environmental)</label>
                    <textarea name="environmental" class="form-control" rows="3">{{ $pestel['environmental'] ?? '' }}</textarea>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold"><i class="fas fa-gavel"></i> قانوني (Legal)</label>
                    <textarea name="legal" class="form-control" rows="3">{{ $pestel['legal'] ?? '' }}</textarea>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn-gold"><i class="fas fa-save ml-1"></i> حفظ التحليل</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

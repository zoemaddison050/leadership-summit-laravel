@extends('layouts.admin')

@section('title', 'Edit Media')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Edit Media: {{ $media->name }}</h1>
                <div>
                    <a href="{{ route('admin.media.show', $media) }}" class="btn btn-outline-primary me-2">
                        <i class="fas fa-eye"></i> View
                    </a>
                    <a href="{{ route('admin.media.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Media Library
                    </a>
                </div>
            </div>

            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Media Details</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('admin.media.update', $media) }}">
                                @csrf
                                @method('PUT')

                                <div class="mb-3">
                                    <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                        id="name" name="name" value="{{ old('name', $media->name) }}" required>
                                    @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                @if($media->isImage())
                                <div class="mb-3">
                                    <label for="alt_text" class="form-label">Alt Text</label>
                                    <input type="text" class="form-control @error('alt_text') is-invalid @enderror"
                                        id="alt_text" name="alt_text" value="{{ old('alt_text', $media->alt_text) }}"
                                        placeholder="Describe the image for accessibility">
                                    <div class="form-text">Used for screen readers and when the image cannot be displayed</div>
                                    @error('alt_text')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                @endif

                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror"
                                        id="description" name="description" rows="4"
                                        placeholder="Optional description of the file">{{ old('description', $media->description) }}</textarea>
                                    @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('admin.media.show', $media) }}" class="btn btn-outline-secondary">
                                        Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Update Media
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Media Preview</h6>
                        </div>
                        <div class="card-body text-center">
                            @if($media->isImage())
                            <img src="{{ $media->url }}" alt="{{ $media->alt_text }}"
                                class="img-fluid rounded mb-3" style="max-height: 200px;">
                            @elseif($media->isVideo())
                            <video class="w-100 mb-3" style="max-height: 200px;" controls>
                                <source src="{{ $media->url }}" type="{{ $media->mime_type }}">
                            </video>
                            @elseif($media->isAudio())
                            <div class="mb-3">
                                <i class="fas fa-music fa-3x text-muted mb-2"></i>
                                <audio controls class="w-100">
                                    <source src="{{ $media->url }}" type="{{ $media->mime_type }}">
                                </audio>
                            </div>
                            @else
                            <div class="mb-3">
                                <i class="fas fa-file fa-3x text-muted mb-2"></i>
                                <div>{{ $media->file_name }}</div>
                            </div>
                            @endif
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-header">
                            <h6 class="mb-0">File Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-2">
                                <strong>Original Name:</strong>
                                <div class="text-muted">{{ $media->file_name }}</div>
                            </div>
                            <div class="mb-2">
                                <strong>Size:</strong>
                                <div class="text-muted">{{ $media->human_size }}</div>
                            </div>
                            <div class="mb-2">
                                <strong>Type:</strong>
                                <div class="text-muted">{{ $media->mime_type }}</div>
                            </div>
                            <div class="mb-2">
                                <strong>Uploaded:</strong>
                                <div class="text-muted">{{ $media->created_at->format('M j, Y g:i A') }}</div>
                            </div>
                            @if($media->uploader)
                            <div class="mb-2">
                                <strong>Uploaded by:</strong>
                                <div class="text-muted">{{ $media->uploader->name }}</div>
                            </div>
                            @endif
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-header">
                            <h6 class="mb-0">Quick Actions</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="{{ $media->url }}" class="btn btn-outline-info btn-sm" target="_blank">
                                    <i class="fas fa-external-link-alt"></i> View Original
                                </a>
                                <a href="{{ $media->url }}" class="btn btn-outline-success btn-sm" download>
                                    <i class="fas fa-download"></i> Download
                                </a>
                                <button type="button" class="btn btn-outline-secondary btn-sm" data-url="{{ $media->url }}" onclick="copyToClipboard(this.dataset.url);">
                                    <i class="fas fa-copy"></i> Copy URL
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(function() {
            const btn = event.target.closest('button');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
            btn.classList.remove('btn-outline-secondary');
            btn.classList.add('btn-success');

            setTimeout(function() {
                btn.innerHTML = originalText;
                btn.classList.remove('btn-success');
                btn.classList.add('btn-outline-secondary');
            }, 2000);
        }).catch(function(err) {
            console.error('Failed to copy: ', err);
            alert('Failed to copy URL to clipboard');
        });
    }
</script>
@endsection
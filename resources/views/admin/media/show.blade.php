@extends('layouts.admin')

@section('title', $media->name)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>{{ $media->name }}</h1>
                <div>
                    <a href="{{ route('admin.media.edit', $media) }}" class="btn btn-primary me-2">
                        <i class="fas fa-edit"></i> Edit
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
                            <h5 class="mb-0">Media Preview</h5>
                        </div>
                        <div class="card-body text-center">
                            @if($media->isImage())
                            <img src="{{ $media->url }}" alt="{{ $media->alt_text }}"
                                class="img-fluid rounded" style="max-height: 500px;">
                            @elseif($media->isVideo())
                            <video controls class="w-100" style="max-height: 500px;">
                                <source src="{{ $media->url }}" type="{{ $media->mime_type }}">
                                Your browser does not support the video tag.
                            </video>
                            @elseif($media->isAudio())
                            <div class="audio-player">
                                <i class="fas fa-music fa-5x text-muted mb-3"></i>
                                <audio controls class="w-100">
                                    <source src="{{ $media->url }}" type="{{ $media->mime_type }}">
                                    Your browser does not support the audio tag.
                                </audio>
                            </div>
                            @else
                            <div class="file-preview">
                                <i class="fas fa-{{ $media->isDocument() ? 'file-alt' : 'file' }} fa-5x text-muted mb-3"></i>
                                <h5>{{ $media->file_name }}</h5>
                                <p class="text-muted">{{ strtoupper($media->extension) }} File</p>
                                <a href="{{ $media->url }}" class="btn btn-outline-primary" target="_blank">
                                    <i class="fas fa-download"></i> Download
                                </a>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">File Details</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <strong>File Name:</strong>
                                <div class="mt-1">{{ $media->file_name }}</div>
                            </div>

                            <div class="mb-3">
                                <strong>File Size:</strong>
                                <div class="mt-1">{{ $media->human_size }}</div>
                            </div>

                            <div class="mb-3">
                                <strong>MIME Type:</strong>
                                <div class="mt-1"><code>{{ $media->mime_type }}</code></div>
                            </div>

                            <div class="mb-3">
                                <strong>Uploaded:</strong>
                                <div class="mt-1">{{ $media->created_at->format('M j, Y g:i A') }}</div>
                            </div>

                            @if($media->uploader)
                            <div class="mb-3">
                                <strong>Uploaded by:</strong>
                                <div class="mt-1">{{ $media->uploader->name }}</div>
                            </div>
                            @endif

                            @if($media->alt_text)
                            <div class="mb-3">
                                <strong>Alt Text:</strong>
                                <div class="mt-1">{{ $media->alt_text }}</div>
                            </div>
                            @endif

                            @if($media->description)
                            <div class="mb-3">
                                <strong>Description:</strong>
                                <div class="mt-1">{{ $media->description }}</div>
                            </div>
                            @endif

                            <div class="mb-3">
                                <strong>URL:</strong>
                                <div class="mt-1">
                                    <input type="text" class="form-control form-control-sm"
                                        value="{{ $media->url }}" readonly onclick="this.select()">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-header">
                            <h6 class="mb-0">Actions</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="{{ route('admin.media.edit', $media) }}" class="btn btn-primary">
                                    <i class="fas fa-edit"></i> Edit Details
                                </a>

                                <a href="{{ $media->url }}" class="btn btn-outline-info" target="_blank">
                                    <i class="fas fa-external-link-alt"></i> View Original
                                </a>

                                <a href="{{ $media->url }}" class="btn btn-outline-success" download>
                                    <i class="fas fa-download"></i> Download
                                </a>

                                <button type="button" class="btn btn-outline-secondary" data-url="{{ $media->url }}" onclick="copyToClipboard(this.dataset.url);">
                                    <i class="fas fa-copy"></i> Copy URL
                                </button>

                                <form method="POST" action="{{ route('admin.media.destroy', $media) }}" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger w-100"
                                        onclick="return confirm('Are you sure you want to delete this file? This action cannot be undone.')">
                                        <i class="fas fa-trash"></i> Delete File
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    @if($media->isImage() && isset($media->metadata['thumbnails']))
                    <div class="card mt-3">
                        <div class="card-header">
                            <h6 class="mb-0">Thumbnails</h6>
                        </div>
                        <div class="card-body">
                            @foreach($media->metadata['thumbnails'] as $size => $path)
                            <div class="mb-2">
                                <strong>{{ ucfirst($size) }}:</strong>
                                <div class="mt-1">
                                    <img src="{{ Storage::disk($media->disk)->url($path) }}"
                                        alt="{{ $size }} thumbnail" class="img-thumbnail"
                                        style="max-width: 100px; max-height: 100px;">
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(function() {
            // Show success message
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
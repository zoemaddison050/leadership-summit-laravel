@extends('layouts.admin')

@section('title', 'Edit Page')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Edit Page: {{ $page->title }}</h1>
                <div>
                    <a href="{{ route('admin.pages.show', $page) }}" class="btn btn-outline-primary me-2">
                        <i class="fas fa-eye"></i> View
                    </a>
                    <a href="{{ route('admin.pages.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Pages
                    </a>
                </div>
            </div>

            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.pages.update', $page) }}">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('title') is-invalid @enderror"
                                        id="title" name="title" value="{{ old('title', $page->title) }}" required>
                                    @error('title')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="slug" class="form-label">Slug</label>
                                    <input type="text" class="form-control @error('slug') is-invalid @enderror"
                                        id="slug" name="slug" value="{{ old('slug', $page->slug) }}">
                                    <div class="form-text">Leave empty to auto-generate from title</div>
                                    @error('slug')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="content" class="form-label">Content <span class="text-danger">*</span></label>
                                    <textarea class="form-control @error('content') is-invalid @enderror"
                                        id="content" name="content" rows="15" required>{{ old('content', $page->content) }}</textarea>
                                    @error('content')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">Page Settings</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                            <select class="form-select @error('status') is-invalid @enderror"
                                                id="status" name="status" required>
                                                <option value="draft" {{ old('status', $page->status) === 'draft' ? 'selected' : '' }}>Draft</option>
                                                <option value="published" {{ old('status', $page->status) === 'published' ? 'selected' : '' }}>Published</option>
                                                <option value="archived" {{ old('status', $page->status) === 'archived' ? 'selected' : '' }}>Archived</option>
                                            </select>
                                            @error('status')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label for="meta_description" class="form-label">Meta Description</label>
                                            <textarea class="form-control @error('meta_description') is-invalid @enderror"
                                                id="meta_description" name="meta_description" rows="3"
                                                maxlength="255">{{ old('meta_description', $page->meta_description) }}</textarea>
                                            <div class="form-text">Used for SEO (max 255 characters)</div>
                                            @error('meta_description')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <small class="text-muted">
                                                <strong>Created:</strong> {{ $page->created_at->format('M j, Y g:i A') }}<br>
                                                <strong>Updated:</strong> {{ $page->updated_at->format('M j, Y g:i A') }}
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-grid gap-2 mt-3">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Update Page
                                    </button>
                                    @if($page->status === 'published')
                                    <a href="{{ route('pages.show', $page->slug) }}" class="btn btn-outline-info" target="_blank">
                                        <i class="fas fa-external-link-alt"></i> View Live
                                    </a>
                                    @endif
                                    <a href="{{ route('admin.pages.index') }}" class="btn btn-outline-secondary">
                                        Cancel
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const titleInput = document.getElementById('title');
        const slugInput = document.getElementById('slug');
        const originalSlug = slugInput.value;

        titleInput.addEventListener('input', function() {
            if (!slugInput.value || slugInput.value === originalSlug) {
                const slug = this.value
                    .toLowerCase()
                    .replace(/[^a-z0-9\s-]/g, '')
                    .replace(/\s+/g, '-')
                    .replace(/-+/g, '-')
                    .trim('-');
                slugInput.value = slug;
            }
        });
    });
</script>
@endsection
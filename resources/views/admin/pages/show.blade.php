@extends('layouts.admin')

@section('title', $page->title)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>{{ $page->title }}</h1>
                <div>
                    <a href="{{ route('admin.pages.edit', $page) }}" class="btn btn-primary me-2">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    @if($page->status === 'published')
                    <a href="{{ route('pages.show', $page->slug) }}" class="btn btn-outline-info me-2" target="_blank">
                        <i class="fas fa-external-link-alt"></i> View Live
                    </a>
                    @endif
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

            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Page Content</h5>
                        </div>
                        <div class="card-body">
                            <div class="content-preview">
                                {!! nl2br(e($page->content)) !!}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Page Details</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <strong>Status:</strong>
                                <span class="badge bg-{{ $page->status === 'published' ? 'success' : ($page->status === 'draft' ? 'warning' : 'secondary') }} ms-2">
                                    {{ ucfirst($page->status) }}
                                </span>
                            </div>

                            <div class="mb-3">
                                <strong>Slug:</strong>
                                <div class="mt-1">
                                    <code>{{ $page->slug }}</code>
                                </div>
                            </div>

                            @if($page->meta_description)
                            <div class="mb-3">
                                <strong>Meta Description:</strong>
                                <div class="mt-1 text-muted">
                                    {{ $page->meta_description }}
                                </div>
                            </div>
                            @endif

                            <div class="mb-3">
                                <strong>Created:</strong>
                                <div class="text-muted">{{ $page->created_at->format('M j, Y g:i A') }}</div>
                            </div>

                            <div class="mb-3">
                                <strong>Last Updated:</strong>
                                <div class="text-muted">{{ $page->updated_at->format('M j, Y g:i A') }}</div>
                            </div>

                            @if($page->status === 'published')
                            <div class="mb-3">
                                <strong>Public URL:</strong>
                                <div class="mt-1">
                                    <a href="{{ route('pages.show', $page->slug) }}" target="_blank" class="text-decoration-none">
                                        {{ route('pages.show', $page->slug) }}
                                        <i class="fas fa-external-link-alt ms-1"></i>
                                    </a>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-header">
                            <h6 class="mb-0">Actions</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="{{ route('admin.pages.edit', $page) }}" class="btn btn-primary">
                                    <i class="fas fa-edit"></i> Edit Page
                                </a>

                                @if($page->status === 'published')
                                <a href="{{ route('pages.show', $page->slug) }}" class="btn btn-outline-info" target="_blank">
                                    <i class="fas fa-external-link-alt"></i> View Live
                                </a>
                                @endif

                                <form method="POST" action="{{ route('admin.pages.destroy', $page) }}" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger w-100"
                                        onclick="return confirm('Are you sure you want to delete this page? This action cannot be undone.')">
                                        <i class="fas fa-trash"></i> Delete Page
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
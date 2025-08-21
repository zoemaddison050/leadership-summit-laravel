@extends('layouts.admin')

@section('title', 'Edit Event')

@section('breadcrumbs')
<li class="breadcrumb-item"><a href="{{ route('admin.events.index') }}">Events</a></li>
<li class="breadcrumb-item active" aria-current="page">Edit: {{ $event->title }}</li>
@endsection

@push('styles')
<style>
    .form-section {
        background: white;
        padding: 2rem;
        border-radius: 0.5rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        margin-bottom: 1.5rem;
    }

    .form-section h3 {
        color: var(--primary-color);
        border-bottom: 2px solid var(--primary-color);
        padding-bottom: 0.5rem;
        margin-bottom: 1.5rem;
    }

    .image-preview {
        max-width: 300px;
        max-height: 200px;
        border-radius: 0.5rem;
        margin-top: 1rem;
    }

    .current-image {
        max-width: 300px;
        max-height: 200px;
        border-radius: 0.5rem;
        border: 2px solid #e9ecef;
    }

    .datetime-inputs {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }

    @media (max-width: 768px) {
        .datetime-inputs {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<div class="page-header">
    <h1 class="page-title">Edit Event</h1>
    <div class="page-actions">
        <a href="{{ route('admin.events.show', $event) }}" class="btn btn-outline-info me-2">
            <i class="fas fa-eye me-2" aria-hidden="true"></i>View Event
        </a>
        <a href="{{ route('admin.events.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2" aria-hidden="true"></i>Back to Events
        </a>
    </div>
</div>

<form action="{{ route('admin.events.update', $event) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <!-- Basic Information -->
    <div class="form-section">
        <h3>Basic Information</h3>

        <div class="row">
            <div class="col-md-8">
                <div class="mb-3">
                    <label for="title" class="form-label">Event Title <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('title') is-invalid @enderror"
                        id="title" name="title" value="{{ old('title', $event->title) }}" required>
                    @error('title')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="col-md-4">
                <div class="mb-3">
                    <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                    <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                        <option value="">Select Status</option>
                        <option value="draft" {{ old('status', $event->status) == 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="published" {{ old('status', $event->status) == 'published' ? 'selected' : '' }}>Published</option>
                        <option value="featured" {{ old('status', $event->status) == 'featured' ? 'selected' : '' }}>Featured</option>
                        <option value="cancelled" {{ old('status', $event->status) == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                    @error('status')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <div class="mb-3">
            <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
            <textarea class="form-control @error('description') is-invalid @enderror"
                id="description" name="description" rows="6" required>{{ old('description', $event->description) }}</textarea>
            @error('description')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="location" class="form-label">Location</label>
            <input type="text" class="form-control @error('location') is-invalid @enderror"
                id="location" name="location" value="{{ old('location', $event->location) }}"
                placeholder="e.g., Conference Center, Online, TBD">
            @error('location')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <!-- Date & Time -->
    <div class="form-section">
        <h3>Date & Time</h3>

        <div class="datetime-inputs">
            <div class="mb-3">
                <label for="start_date" class="form-label">Start Date & Time <span class="text-danger">*</span></label>
                <input type="datetime-local" class="form-control @error('start_date') is-invalid @enderror"
                    id="start_date" name="start_date"
                    value="{{ old('start_date', $event->start_date->format('Y-m-d\TH:i')) }}" required>
                @error('start_date')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="end_date" class="form-label">End Date & Time</label>
                <input type="datetime-local" class="form-control @error('end_date') is-invalid @enderror"
                    id="end_date" name="end_date"
                    value="{{ old('end_date', $event->end_date ? $event->end_date->format('Y-m-d\TH:i') : '') }}">
                @error('end_date')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div class="form-text">Leave empty if it's a single-day event</div>
            </div>
        </div>
    </div>

    <!-- Featured Image -->
    <div class="form-section">
        <h3>Featured Image</h3>

        @if($event->featured_image)
        <div class="mb-3">
            <label class="form-label">Current Image</label>
            <div>
                <img src="{{ asset('storage/' . $event->featured_image) }}"
                    alt="{{ $event->title }}" class="current-image">
            </div>
        </div>
        @endif

        <div class="mb-3">
            <label for="featured_image" class="form-label">
                {{ $event->featured_image ? 'Replace Image' : 'Upload Image' }}
            </label>
            <input type="file" class="form-control @error('featured_image') is-invalid @enderror"
                id="featured_image" name="featured_image" accept="image/*">
            @error('featured_image')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <div class="form-text">Recommended size: 1200x600px. Max file size: 2MB. Formats: JPEG, PNG, JPG, GIF</div>
        </div>

        <div id="imagePreview" style="display: none;">
            <label class="form-label">New Image Preview</label>
            <div>
                <img id="previewImg" class="image-preview" alt="Image preview">
            </div>
        </div>
    </div>

    <!-- Event Statistics -->
    <div class="form-section">
        <h3>Event Statistics</h3>

        <div class="row">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">{{ $event->registrations->count() }}</h5>
                        <p class="card-text text-muted">Registrations</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">{{ $event->tickets->count() }}</h5>
                        <p class="card-text text-muted">Ticket Types</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">{{ $event->sessions->count() }}</h5>
                        <p class="card-text text-muted">Sessions</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">{{ $event->created_at->diffForHumans() }}</h5>
                        <p class="card-text text-muted">Created</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Form Actions -->
    <div class="form-section">
        <div class="d-flex justify-content-between">
            <div>
                <a href="{{ route('admin.events.index') }}" class="btn btn-outline-secondary me-2">
                    <i class="fas fa-times me-2" aria-hidden="true"></i>Cancel
                </a>
                <button type="button" class="btn btn-outline-danger" onclick="confirmDelete()">
                    <i class="fas fa-trash me-2" aria-hidden="true"></i>Delete Event
                </button>
            </div>
            <div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2" aria-hidden="true"></i>Update Event
                </button>
            </div>
        </div>
    </div>
</form>

<!-- Delete Form (hidden) -->
<form id="deleteForm" action="{{ route('admin.events.destroy', $event) }}" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Image preview functionality
        const imageInput = document.getElementById('featured_image');
        const imagePreview = document.getElementById('imagePreview');
        const previewImg = document.getElementById('previewImg');

        imageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    imagePreview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                imagePreview.style.display = 'none';
            }
        });

        // Delete confirmation
        window.confirmDelete = function() {
            if (confirm('Are you sure you want to delete this event? This action cannot be undone and will also delete all associated registrations and tickets.')) {
                document.getElementById('deleteForm').submit();
            }
        };
    });
</script>
@endpush
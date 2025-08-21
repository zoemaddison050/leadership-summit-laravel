@extends('layouts.admin')

@section('title', 'Add New Speaker')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Add New Speaker</h1>
                <a href="{{ route('admin.speakers.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Speakers
                </a>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Speaker Information</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('admin.speakers.store') }}" enctype="multipart/form-data">
                                @csrf

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                            <input type="text"
                                                class="form-control @error('name') is-invalid @enderror"
                                                id="name"
                                                name="name"
                                                value="{{ old('name') }}"
                                                required>
                                            @error('name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="position" class="form-label">Position/Title</label>
                                            <input type="text"
                                                class="form-control @error('position') is-invalid @enderror"
                                                id="position"
                                                name="position"
                                                value="{{ old('position') }}">
                                            @error('position')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="company" class="form-label">Company/Organization</label>
                                    <input type="text"
                                        class="form-control @error('company') is-invalid @enderror"
                                        id="company"
                                        name="company"
                                        value="{{ old('company') }}">
                                    @error('company')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="bio" class="form-label">Biography <span class="text-danger">*</span></label>
                                    <textarea class="form-control @error('bio') is-invalid @enderror"
                                        id="bio"
                                        name="bio"
                                        rows="6"
                                        required>{{ old('bio') }}</textarea>
                                    @error('bio')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">Provide a detailed biography of the speaker.</div>
                                </div>

                                <div class="mb-3">
                                    <label for="photo" class="form-label">Profile Photo</label>
                                    <input type="file"
                                        class="form-control @error('photo') is-invalid @enderror"
                                        id="photo"
                                        name="photo"
                                        accept="image/*">
                                    @error('photo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">Upload a professional headshot (JPEG, PNG, JPG, GIF - Max: 2MB)</div>
                                </div>

                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('admin.speakers.index') }}" class="btn btn-secondary">Cancel</a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Create Speaker
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Photo Preview</h6>
                        </div>
                        <div class="card-body text-center">
                            <div id="photo-preview" class="mb-3">
                                <div class="bg-light rounded d-flex align-items-center justify-content-center"
                                    style="width: 200px; height: 200px; margin: 0 auto;">
                                    <i class="fas fa-user fa-3x text-muted"></i>
                                </div>
                            </div>
                            <small class="text-muted">Upload a photo to see preview</small>
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-header">
                            <h6 class="mb-0">Tips</h6>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled small">
                                <li><i class="fas fa-check text-success"></i> Use high-quality professional photos</li>
                                <li><i class="fas fa-check text-success"></i> Square aspect ratio works best</li>
                                <li><i class="fas fa-check text-success"></i> Keep file size under 2MB</li>
                                <li><i class="fas fa-check text-success"></i> Write engaging biographies</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const photoInput = document.getElementById('photo');
        const photoPreview = document.getElementById('photo-preview');

        photoInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    photoPreview.innerHTML = `
                    <img src="${e.target.result}" 
                         alt="Preview" 
                         class="rounded" 
                         style="width: 200px; height: 200px; object-fit: cover;">
                `;
                };
                reader.readAsDataURL(file);
            } else {
                photoPreview.innerHTML = `
                <div class="bg-light rounded d-flex align-items-center justify-content-center" 
                     style="width: 200px; height: 200px; margin: 0 auto;">
                    <i class="fas fa-user fa-3x text-muted"></i>
                </div>
            `;
            }
        });
    });
</script>
@endsection
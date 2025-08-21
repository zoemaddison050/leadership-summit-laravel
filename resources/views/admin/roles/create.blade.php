@extends('layouts.admin')

@section('title', 'Create Role')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Create New Role</h1>
                <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Roles
                </a>
            </div>

            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.roles.store') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="name" class="form-label">Role Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name') }}" required placeholder="e.g., moderator, speaker">
                            @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Permissions</label>
                            <div class="row">
                                @foreach($availablePermissions as $permission => $label)
                                <div class="col-md-6 col-lg-4">
                                    <div class="form-check">
                                        <input type="checkbox" name="permissions[]" id="permission_{{ $permission }}"
                                            class="form-check-input" value="{{ $permission }}"
                                            {{ in_array($permission, old('permissions', [])) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="permission_{{ $permission }}">
                                            {{ $label }}
                                        </label>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Create Role
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
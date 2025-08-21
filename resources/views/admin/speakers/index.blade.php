@extends('layouts.admin')

@section('title', 'Speakers Management')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Speakers Management</h1>
                <a href="{{ route('admin.speakers.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add New Speaker
                </a>
            </div>

            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col">
                            <h5 class="mb-0">All Speakers ({{ $speakers->total() }})</h5>
                        </div>
                        <div class="col-auto">
                            <form method="POST" action="{{ route('admin.speakers.bulk-action') }}" id="bulk-action-form">
                                @csrf
                                <div class="input-group">
                                    <select name="action" class="form-select" required>
                                        <option value="">Bulk Actions</option>
                                        <option value="delete">Delete Selected</option>
                                    </select>
                                    <button type="submit" class="btn btn-outline-secondary" onclick="return confirm('Are you sure you want to perform this action?')">
                                        Apply
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if($speakers->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="50">
                                        <input type="checkbox" id="select-all" class="form-check-input">
                                    </th>
                                    <th width="80">Photo</th>
                                    <th>Name</th>
                                    <th>Position</th>
                                    <th>Company</th>
                                    <th>Sessions</th>
                                    <th>Created</th>
                                    <th width="120">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($speakers as $speaker)
                                <tr>
                                    <td>
                                        <input type="checkbox" name="speakers[]" value="{{ $speaker->id }}"
                                            class="form-check-input speaker-checkbox" form="bulk-action-form">
                                    </td>
                                    <td>
                                        @if($speaker->photo)
                                        <img src="{{ asset('storage/' . $speaker->photo) }}"
                                            alt="{{ $speaker->name }}"
                                            class="rounded-circle"
                                            width="50" height="50"
                                            style="object-fit: cover;">
                                        @else
                                        <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center"
                                            style="width: 50px; height: 50px;">
                                            <i class="fas fa-user text-white"></i>
                                        </div>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ $speaker->name }}</strong>
                                    </td>
                                    <td>{{ $speaker->position ?: '-' }}</td>
                                    <td>{{ $speaker->company ?: '-' }}</td>
                                    <td>
                                        <span class="badge bg-info">{{ $speaker->sessions_count }} sessions</span>
                                    </td>
                                    <td>{{ $speaker->created_at->format('M j, Y') }}</td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('admin.speakers.show', $speaker) }}"
                                                class="btn btn-sm btn-outline-info" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.speakers.edit', $speaker) }}"
                                                class="btn btn-sm btn-outline-primary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form method="POST" action="{{ route('admin.speakers.destroy', $speaker) }}"
                                                class="d-inline" onsubmit="return confirm('Are you sure you want to delete this speaker?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-5">
                        <i class="fas fa-microphone-alt fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No speakers found</h5>
                        <p class="text-muted">Get started by adding your first speaker.</p>
                        <a href="{{ route('admin.speakers.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add New Speaker
                        </a>
                    </div>
                    @endif
                </div>
                @if($speakers->hasPages())
                <div class="card-footer">
                    {{ $speakers->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle select all checkbox
        const selectAllCheckbox = document.getElementById('select-all');
        const speakerCheckboxes = document.querySelectorAll('.speaker-checkbox');

        selectAllCheckbox.addEventListener('change', function() {
            speakerCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });

        // Update select all checkbox when individual checkboxes change
        speakerCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const checkedCount = document.querySelectorAll('.speaker-checkbox:checked').length;
                selectAllCheckbox.checked = checkedCount === speakerCheckboxes.length;
                selectAllCheckbox.indeterminate = checkedCount > 0 && checkedCount < speakerCheckboxes.length;
            });
        });
    });
</script>
@endsection
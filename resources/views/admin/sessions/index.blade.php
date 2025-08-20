@extends('layouts.admin')

@section('title', 'Session Management')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Session Management</h3>
                    <a href="{{ route('admin.sessions.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Add New Session
                    </a>
                </div>

                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Coming Soon!</strong> Session management functionality will be implemented in a future update.
                        This will allow you to create and manage event sessions, workshops, and presentations.
                    </div>

                    <div class="text-center py-5">
                        <i class="fas fa-calendar-check fa-4x text-muted mb-3"></i>
                        <h4 class="text-muted">Session Management</h4>
                        <p class="text-muted">Create and manage event sessions and workshops</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
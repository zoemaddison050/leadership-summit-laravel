@extends('layouts.admin')

@section('title', 'Create Session')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Create New Session</h3>
                    <a href="{{ route('admin.sessions.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Back to Sessions
                    </a>
                </div>

                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Session creation functionality will be implemented in a future update.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
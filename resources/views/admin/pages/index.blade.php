@extends('layouts.admin')

@section('title', 'Page Management')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Page Management</h3>
                    <a href="{{ route('admin.pages.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Add New Page
                    </a>
                </div>

                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Coming Soon!</strong> Page management functionality will be implemented in a future update.
                        This will allow you to create and manage static pages like About, Contact, Terms of Service, etc.
                    </div>

                    <div class="text-center py-5">
                        <i class="fas fa-file-alt fa-4x text-muted mb-3"></i>
                        <h4 class="text-muted">Page Management</h4>
                        <p class="text-muted">Create and manage static pages for your website</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
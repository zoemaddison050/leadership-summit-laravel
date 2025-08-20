@extends('layouts.app')

@section('title', 'My Profile - Leadership Summit')
@section('meta_description', 'View and manage your profile information for the Leadership Summit.')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h2 class="mb-0">
                        <i class="fas fa-user me-2"></i>My Profile
                    </h2>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="text-muted mb-3">Profile Information</h5>
                            <div class="mb-3">
                                <strong>Name:</strong>
                                <p class="mb-1">{{ auth()->user()->name }}</p>
                            </div>
                            <div class="mb-3">
                                <strong>Email:</strong>
                                <p class="mb-1">{{ auth()->user()->email }}</p>
                            </div>
                            <div class="mb-3">
                                <strong>Role:</strong>
                                <p class="mb-1">{{ auth()->user()->role ? auth()->user()->role->name : 'No role assigned' }}</p>
                            </div>
                            <div class="mb-3">
                                <strong>Member Since:</strong>
                                <p class="mb-1">{{ auth()->user()->created_at->format('F j, Y') }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h5 class="text-muted mb-3">Quick Actions</h5>
                            <div class="d-grid gap-2">
                                <a href="{{ route('profile.edit') }}" class="btn btn-primary">
                                    <i class="fas fa-edit me-2"></i>Edit Profile
                                </a>
                                <a href="{{ route('registrations.index') }}" class="btn btn-outline-primary">
                                    <i class="fas fa-ticket-alt me-2"></i>My Registrations
                                </a>
                                <a href="{{ route('orders.index') }}" class="btn btn-outline-primary">
                                    <i class="fas fa-shopping-cart me-2"></i>My Orders
                                </a>
                                <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                                </a>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="row">
                        <div class="col-12">
                            <h5 class="text-muted mb-3">Recent Activity</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h6 class="card-title">Registrations</h6>
                                            <h3 class="text-primary">{{ auth()->user()->registrations->count() }}</h3>
                                            <small class="text-muted">Total registrations</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h6 class="card-title">Orders</h6>
                                            <h3 class="text-success">{{ auth()->user()->orders->count() }}</h3>
                                            <small class="text-muted">Total orders</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 
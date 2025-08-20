@extends('layouts.admin')

@section('title', 'Reports Dashboard')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Reports Dashboard</h1>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-0">{{ number_format($stats['total_registrations']) }}</h4>
                                    <p class="mb-0">Total Registrations</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-users fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-0">{{ number_format($stats['confirmed_registrations']) }}</h4>
                                    <p class="mb-0">Confirmed</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-check-circle fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-0">{{ number_format($stats['pending_registrations']) }}</h4>
                                    <p class="mb-0">Pending</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-clock fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-0">${{ number_format($stats['total_revenue'], 2) }}</h4>
                                    <p class="mb-0">Total Revenue</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-dollar-sign fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-8">
                    <!-- Recent Registrations -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Recent Registrations</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Attendee</th>
                                            <th>Event</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($stats['recent_registrations'] as $registration)
                                        <tr>
                                            <td>{{ $registration->attendee_name }}</td>
                                            <td>{{ $registration->event->title ?? 'N/A' }}</td>
                                            <td>
                                                <span class="badge bg-{{ $registration->registration_status === 'confirmed' ? 'success' : 'warning' }}">
                                                    {{ ucfirst($registration->registration_status) }}
                                                </span>
                                            </td>
                                            <td>{{ $registration->created_at->format('M d, Y') }}</td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="4" class="text-center">No recent registrations</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <!-- Quick Reports -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Quick Reports</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="{{ route('admin.reports.registrations') }}" class="btn btn-outline-primary">
                                    <i class="fas fa-users me-2"></i>Registration Report
                                </a>
                                <a href="{{ route('admin.reports.payments') }}" class="btn btn-outline-success">
                                    <i class="fas fa-credit-card me-2"></i>Payment Report
                                </a>
                                <a href="{{ route('admin.reports.events') }}" class="btn btn-outline-info">
                                    <i class="fas fa-calendar me-2"></i>Event Report
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- System Stats -->
                    <div class="card mt-3">
                        <div class="card-header">
                            <h5 class="mb-0">System Overview</h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-6">
                                    <h4 class="text-primary">{{ $stats['total_events'] }}</h4>
                                    <small>Total Events</small>
                                </div>
                                <div class="col-6">
                                    <h4 class="text-success">{{ $stats['active_events'] }}</h4>
                                    <small>Active Events</small>
                                </div>
                            </div>
                            <hr>
                            <div class="row text-center">
                                <div class="col-12">
                                    <h4 class="text-info">{{ $stats['total_users'] }}</h4>
                                    <small>Total Users</small>
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
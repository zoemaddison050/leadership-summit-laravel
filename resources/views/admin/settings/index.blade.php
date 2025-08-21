@extends('layouts.admin')

@section('title', 'Settings')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>System Settings</h1>
            </div>

            <form method="POST" action="{{ route('admin.settings.update') }}">
                @csrf
                @method('PATCH')

                <div class="row">
                    <div class="col-md-8">
                        <!-- General Settings -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">General Settings</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="site_name" class="form-label">Site Name</label>
                                            <input type="text" class="form-control" id="site_name" name="site_name"
                                                value="{{ $settings['site_name'] }}" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="contact_email" class="form-label">Contact Email</label>
                                            <input type="email" class="form-control" id="contact_email" name="contact_email"
                                                value="{{ $settings['contact_email'] }}" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="site_description" class="form-label">Site Description</label>
                                    <textarea class="form-control" id="site_description" name="site_description" rows="3" required>{{ $settings['site_description'] }}</textarea>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="contact_phone" class="form-label">Contact Phone</label>
                                            <input type="text" class="form-control" id="contact_phone" name="contact_phone"
                                                value="{{ $settings['contact_phone'] }}">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="address" class="form-label">Address</label>
                                            <input type="text" class="form-control" id="address" name="address"
                                                value="{{ $settings['address'] }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Registration Settings -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Registration Settings</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="max_registrations_per_event" class="form-label">Max Registrations per Event</label>
                                            <input type="number" class="form-control" id="max_registrations_per_event"
                                                name="max_registrations_per_event" value="{{ $settings['max_registrations_per_event'] }}" min="1">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="registration_deadline_days" class="form-label">Registration Deadline (days before event)</label>
                                            <input type="number" class="form-control" id="registration_deadline_days"
                                                name="registration_deadline_days" value="{{ $settings['registration_deadline_days'] }}" min="0">
                                        </div>
                                    </div>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="registration_enabled"
                                        name="registration_enabled" value="1" {{ $settings['registration_enabled'] ? 'checked' : '' }}>
                                    <label class="form-check-label" for="registration_enabled">
                                        Enable Registration
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Settings -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Payment Settings</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Payment Methods</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="crypto_payments"
                                            name="payment_methods[]" value="crypto"
                                            {{ in_array('crypto', $settings['payment_methods']) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="crypto_payments">
                                            Cryptocurrency Payments
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="stripe_payments"
                                            name="payment_methods[]" value="stripe"
                                            {{ in_array('stripe', $settings['payment_methods']) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="stripe_payments">
                                            Stripe Payments
                                        </label>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Supported Cryptocurrencies</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="btc"
                                            name="crypto_currencies[]" value="BTC"
                                            {{ in_array('BTC', $settings['crypto_currencies']) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="btc">Bitcoin (BTC)</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="eth"
                                            name="crypto_currencies[]" value="ETH"
                                            {{ in_array('ETH', $settings['crypto_currencies']) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="eth">Ethereum (ETH)</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="usdt"
                                            name="crypto_currencies[]" value="USDT"
                                            {{ in_array('USDT', $settings['crypto_currencies']) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="usdt">USDT (ERC-20)</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <!-- Notification Settings -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Notifications</h5>
                            </div>
                            <div class="card-body">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="email_notifications"
                                        name="email_notifications" value="1" {{ $settings['email_notifications'] ? 'checked' : '' }}>
                                    <label class="form-check-label" for="email_notifications">
                                        Email Notifications
                                    </label>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="sms_notifications"
                                        name="sms_notifications" value="1" {{ $settings['sms_notifications'] ? 'checked' : '' }}>
                                    <label class="form-check-label" for="sms_notifications">
                                        SMS Notifications
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- System Settings -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">System</h5>
                            </div>
                            <div class="card-body">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="maintenance_mode"
                                        name="maintenance_mode" value="1" {{ $settings['maintenance_mode'] ? 'checked' : '' }}>
                                    <label class="form-check-label" for="maintenance_mode">
                                        Maintenance Mode
                                    </label>
                                    <small class="form-text text-muted">Puts the site in maintenance mode for regular users</small>
                                </div>
                            </div>
                        </div>

                        <!-- Save Button -->
                        <div class="card">
                            <div class="card-body">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-save me-2"></i>Save Settings
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
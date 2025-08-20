@extends('layouts.app')

@section('title', 'Livewire Demo - No Page Reloads')

@section('content')
<div class="container py-5">
    <div class="row">
        <div class="col-12">
            <div class="text-center mb-5">
                <h1 class="display-4 text-primary">Livewire Demo</h1>
                <p class="lead">Interactive forms without page reloads using Laravel Livewire</p>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    All forms on this page submit without refreshing the page!
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Event Registration Component -->
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header bg-primary text-white">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-calendar-plus me-2"></i>
                        Event Registration
                    </h3>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-4">Register for events without page reload</p>
                    @php
                    // Create a sample event for demo
                    $sampleEvent = new class {
                    public $id = 1;
                    public $title = 'Leadership Summit 2025';
                    public $start_date;
                    public $location = 'Cypress Convention Center';

                    public function __construct() {
                    $this->start_date = \Carbon\Carbon::parse('2025-09-15 09:00:00');
                    }
                    };
                    @endphp
                    @livewire('event-registration', ['event' => $sampleEvent])
                </div>
            </div>
        </div>

        <!-- Contact Form Component -->
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header bg-success text-white">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-envelope me-2"></i>
                        Contact Form
                    </h3>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-4">Send messages without page reload</p>
                    @livewire('contact-form')
                </div>
            </div>
        </div>

        <!-- Newsletter Subscription Component -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-newspaper me-2"></i>
                        Newsletter Subscription
                    </h3>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-4">Subscribe to our newsletter for the latest updates and announcements.</p>
                    @livewire('newsletter-subscription')
                </div>
            </div>
        </div>

        <!-- Simple Counter Component -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-calculator me-2"></i>
                        Interactive Counter
                    </h3>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-4">A simple counter to demonstrate real-time updates</p>
                    @livewire('counter')
                </div>
            </div>
        </div>
    </div>

    <!-- Features Section -->
    <div class="row mt-5">
        <div class="col-12">
            <div class="card bg-light">
                <div class="card-body">
                    <h3 class="text-center mb-4">
                        <i class="fas fa-magic me-2 text-primary"></i>
                        Livewire Features Demonstrated
                    </h3>
                    <div class="row">
                        <div class="col-md-4 text-center mb-3">
                            <div class="feature-icon mb-3">
                                <i class="fas fa-sync-alt fa-2x text-primary"></i>
                            </div>
                            <h5>No Page Reloads</h5>
                            <p class="text-muted">Forms submit and update content without refreshing the page</p>
                        </div>
                        <div class="col-md-4 text-center mb-3">
                            <div class="feature-icon mb-3">
                                <i class="fas fa-check-circle fa-2x text-success"></i>
                            </div>
                            <h5>Real-time Validation</h5>
                            <p class="text-muted">Form validation happens instantly as you type</p>
                        </div>
                        <div class="col-md-4 text-center mb-3">
                            <div class="feature-icon mb-3">
                                <i class="fas fa-spinner fa-2x text-info"></i>
                            </div>
                            <h5>Loading States</h5>
                            <p class="text-muted">Visual feedback during form submission with loading indicators</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Code Examples -->
    <div class="row mt-5">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-code me-2"></i>
                        How It Works
                    </h3>
                </div>
                <div class="card-body">
                    <p>These components are built with Laravel Livewire. Here's how simple it is:</p>

                    <h5>1. Include Livewire in your layout:</h5>
                    <pre class="bg-light p-3 rounded"><code>&lt;!-- In your layout head --&gt;
@livewireStyles

&lt;!-- Before closing body tag --&gt;
@livewireScripts</code></pre>

                    <h5>2. Use components in your Blade templates:</h5>
                    <pre class="bg-light p-3 rounded"><code>&lt;!-- Simple component inclusion --&gt;
@livewire('contact-form')

&lt;!-- Component with parameters --&gt;
@livewire('event-registration', ['event' => $event])</code></pre>

                    <h5>3. Create interactive components:</h5>
                    <pre class="bg-light p-3 rounded"><code>// In your Livewire component class
public function submit()
{
    $this->validate();
    // Process form without page reload
    $this->showSuccessMessage = true;
}</code></pre>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .feature-icon {
        height: 80px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .card {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        border: 1px solid rgba(0, 0, 0, 0.125);
    }

    .card-header {
        border-bottom: 1px solid rgba(0, 0, 0, 0.125);
    }

    pre {
        font-size: 0.875rem;
    }

    code {
        color: #e83e8c;
    }
</style>
@endsection
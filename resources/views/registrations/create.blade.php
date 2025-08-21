@extends('layouts.app')

@section('title', 'Register for ' . $event->title)

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4>Register for {{ $event->title }}</h4>
                </div>
                <div class="card-body">
                    <!-- Event Information -->
                    <div class="event-info mb-4">
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Event Details</h5>
                                <p><strong>Date:</strong> {{ $event->start_date->format('M d, Y') }}</p>
                                <p><strong>Time:</strong> {{ $event->start_date->format('g:i A') }}</p>
                                @if($event->location)
                                <p><strong>Location:</strong> {{ $event->location }}</p>
                                @endif
                            </div>
                            <div class="col-md-6">
                                @if($event->featured_image)
                                <img src="{{ asset('storage/' . $event->featured_image) }}"
                                    alt="{{ $event->title }}"
                                    class="img-fluid rounded">
                                @endif
                            </div>
                        </div>
                        @if($event->description)
                        <div class="mt-3">
                            <h6>Description</h6>
                            <p>{{ Str::limit($event->description, 200) }}</p>
                        </div>
                        @endif
                    </div>

                    <!-- Registration Form -->
                    <form method="POST" action="{{ route('registrations.store', $event) }}" id="registration-form">
                        @csrf

                        <!-- Ticket Selection -->
                        <div class="mb-4">
                            <h5>Select Ticket Type</h5>
                            @foreach($tickets as $ticket)
                            <div class="card mb-2">
                                <div class="card-body">
                                    <div class="form-check">
                                        <input class="form-check-input"
                                            type="radio"
                                            name="ticket_id"
                                            id="ticket_{{ $ticket->id }}"
                                            value="{{ $ticket->id }}"
                                            data-price="{{ $ticket->price }}"
                                            {{ old('ticket_id') == $ticket->id ? 'checked' : '' }}
                                            required>
                                        <label class="form-check-label w-100" for="ticket_{{ $ticket->id }}">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6 class="mb-1">{{ $ticket->name }}</h6>
                                                    @if($ticket->description)
                                                    <p class="text-muted mb-1">{{ $ticket->description }}</p>
                                                    @endif
                                                    <small class="text-muted">{{ $ticket->available }} tickets available</small>
                                                </div>
                                                <div class="text-end">
                                                    <span class="h5 mb-0">
                                                        @if($ticket->price > 0)
                                                        ${{ number_format($ticket->price, 2) }}
                                                        @else
                                                        Free
                                                        @endif
                                                    </span>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                            @error('ticket_id')
                            <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Quantity Selection -->
                        <div class="mb-4">
                            <label for="quantity" class="form-label">Number of Tickets</label>
                            <select class="form-select" id="quantity" name="quantity" required>
                                @for($i = 1; $i <= 10; $i++)
                                    <option value="{{ $i }}" {{ old('quantity', 1) == $i ? 'selected' : '' }}>
                                    {{ $i }} {{ $i == 1 ? 'ticket' : 'tickets' }}
                                    </option>
                                    @endfor
                            </select>
                            @error('quantity')
                            <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Total Price Display -->
                        <div class="mb-4">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="h6 mb-0">Total:</span>
                                        <span class="h5 mb-0" id="total-price">$0.00</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Terms and Conditions -->
                        <div class="mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
                                <label class="form-check-label" for="terms">
                                    I agree to the <a href="#" target="_blank">Terms and Conditions</a> and
                                    <a href="#" target="_blank">Privacy Policy</a>
                                </label>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg" id="submit-btn">
                                <span id="submit-text">Register Now</span>
                                <span id="submit-spinner" class="spinner-border spinner-border-sm d-none" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ticketInputs = document.querySelectorAll('input[name="ticket_id"]');
        const quantitySelect = document.getElementById('quantity');
        const totalPriceElement = document.getElementById('total-price');
        const submitBtn = document.getElementById('submit-btn');
        const submitText = document.getElementById('submit-text');
        const submitSpinner = document.getElementById('submit-spinner');
        const form = document.getElementById('registration-form');

        function updateTotalPrice() {
            const selectedTicket = document.querySelector('input[name="ticket_id"]:checked');
            const quantity = parseInt(quantitySelect.value);

            if (selectedTicket) {
                const price = parseFloat(selectedTicket.dataset.price);
                const total = price * quantity;

                if (total > 0) {
                    totalPriceElement.textContent = '$' + total.toFixed(2);
                    submitText.textContent = 'Proceed to Payment';
                } else {
                    totalPriceElement.textContent = 'Free';
                    submitText.textContent = 'Register Now';
                }
            } else {
                totalPriceElement.textContent = '$0.00';
                submitText.textContent = 'Register Now';
            }
        }

        // Update price when ticket or quantity changes
        ticketInputs.forEach(input => {
            input.addEventListener('change', updateTotalPrice);
        });
        quantitySelect.addEventListener('change', updateTotalPrice);

        // Form submission handling
        form.addEventListener('submit', function() {
            submitBtn.disabled = true;
            submitText.classList.add('d-none');
            submitSpinner.classList.remove('d-none');
        });

        // Initial price calculation
        updateTotalPrice();
    });
</script>
@endsection
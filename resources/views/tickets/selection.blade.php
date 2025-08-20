@extends('layouts.app')

@section('title', 'Select Tickets - ' . $event->title)

@push('styles')
<style>
    .ticket-selection-container {
        min-height: 100vh;
        background: linear-gradient(135deg, #f8fafc 0%, #e3f2fd 100%);
        padding: 2rem 0;
    }

    .event-header {
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        color: white;
        padding: 3rem 0;
        margin-bottom: 3rem;
        border-radius: 0 0 2rem 2rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }

    .event-header h1 {
        font-weight: 700;
        font-size: 2.5rem;
        margin-bottom: 1rem;
    }

    .event-meta {
        display: flex;
        gap: 2rem;
        flex-wrap: wrap;
        opacity: 0.9;
    }

    .event-meta span {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 1rem;
    }

    .tickets-container {
        max-width: 1200px;
        margin: 0 auto;
    }

    .section-title {
        font-size: 2rem;
        font-weight: 700;
        color: var(--primary-color);
        margin-bottom: 2rem;
        text-align: center;
    }

    .ticket-card {
        background: white;
        border: 2px solid #e5e7eb;
        border-radius: 1.5rem;
        padding: 2.5rem;
        margin-bottom: 2rem;
        transition: all 0.3s ease;
        position: relative;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    }

    .ticket-card:hover {
        border-color: var(--primary-color);
        box-shadow: 0 12px 40px rgba(10, 36, 99, 0.15);
        transform: translateY(-4px);
    }

    .ticket-card.selected {
        border-color: var(--primary-color);
        background: linear-gradient(135deg, rgba(10, 36, 99, 0.03), rgba(59, 130, 246, 0.03));
        box-shadow: 0 12px 40px rgba(10, 36, 99, 0.2);
    }

    .ticket-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 1.5rem;
    }

    .ticket-info h3 {
        font-size: 1.75rem;
        font-weight: 700;
        color: var(--primary-color);
        margin-bottom: 0.5rem;
    }

    .ticket-description {
        color: #6b7280;
        font-size: 1rem;
        line-height: 1.6;
        margin-bottom: 1rem;
    }

    .ticket-price {
        font-size: 2.5rem;
        font-weight: 800;
        color: var(--primary-color);
        text-align: right;
    }

    .ticket-price.free {
        color: #10b981;
    }

    .availability-section {
        margin-bottom: 2rem;
    }

    .availability-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        border-radius: 2rem;
        font-size: 0.9rem;
        font-weight: 600;
    }

    .quantity-section {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1.5rem;
        padding-top: 1.5rem;
        border-top: 1px solid #e5e7eb;
    }

    .quantity-label {
        font-size: 1.1rem;
        font-weight: 600;
        color: #374151;
    }

    .quantity-controls {
        display: flex;
        align-items: center;
        background: #f9fafb;
        border: 2px solid #e5e7eb;
        border-radius: 1rem;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }

    .quantity-btn {
        background: white;
        border: none;
        padding: 1rem 1.25rem;
        cursor: pointer;
        transition: all 0.2s ease;
        font-size: 1.2rem;
        color: var(--primary-color);
        font-weight: 600;
        min-width: 50px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .quantity-btn:hover:not(:disabled) {
        background: var(--primary-color);
        color: white;
        transform: scale(1.05);
    }

    .quantity-btn:disabled {
        opacity: 0.4;
        cursor: not-allowed;
        background: #f3f4f6;
    }

    .quantity-input {
        border: none;
        background: white;
        text-align: center;
        width: 80px;
        padding: 1rem 0.5rem;
        font-weight: 700;
        font-size: 1.2rem;
        color: var(--primary-color);
        outline: none;
    }

    .ticket-total {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--primary-color);
        background: linear-gradient(135deg, rgba(10, 36, 99, 0.1), rgba(59, 130, 246, 0.1));
        padding: 0.75rem 1.5rem;
        border-radius: 1rem;
        text-align: center;
    }

    .order-summary {
        background: white;
        border-radius: 1.5rem;
        padding: 2.5rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        position: sticky;
        top: 2rem;
        border: 2px solid #e5e7eb;
    }

    .order-summary h3 {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--primary-color);
        margin-bottom: 1.5rem;
        text-align: center;
    }

    .summary-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 0;
        border-bottom: 1px solid #e5e7eb;
    }

    .summary-item:last-child {
        border-bottom: none;
        font-weight: 700;
        font-size: 1.4rem;
        color: var(--primary-color);
        background: linear-gradient(135deg, rgba(10, 36, 99, 0.05), rgba(59, 130, 246, 0.05));
        margin: 1rem -1rem 0 -1rem;
        padding: 1.5rem 1rem;
        border-radius: 1rem;
    }

    .continue-btn {
        width: 100%;
        padding: 1.25rem;
        font-size: 1.2rem;
        font-weight: 700;
        border-radius: 1rem;
        margin-top: 2rem;
        transition: all 0.3s ease;
        border: none;
    }

    .continue-btn:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(10, 36, 99, 0.3);
    }

    .continue-btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    .security-notice {
        text-align: center;
        margin-top: 1.5rem;
        color: #6b7280;
        font-size: 0.9rem;
    }

    .sold-out-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.95);
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 1.5rem;
        font-size: 1.5rem;
        font-weight: 700;
        color: #dc3545;
        backdrop-filter: blur(2px);
    }

    .no-tickets-container {
        text-align: center;
        padding: 4rem 2rem;
        background: white;
        border-radius: 1.5rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }

    .no-tickets-container i {
        color: #9ca3af;
        margin-bottom: 1.5rem;
    }

    .no-tickets-container h3 {
        color: var(--primary-color);
        margin-bottom: 1rem;
    }

    /* Mobile Styles */
    @media (max-width: 768px) {
        .ticket-selection-container {
            padding: 1rem 0;
        }

        .event-header {
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 1rem 1rem;
        }

        .event-header h1 {
            font-size: 2rem;
        }

        .event-meta {
            flex-direction: column;
            gap: 1rem;
        }

        .ticket-card {
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .ticket-header {
            flex-direction: column;
            gap: 1rem;
        }

        .ticket-price {
            font-size: 2rem;
            text-align: left;
        }

        .quantity-section {
            flex-direction: column;
            align-items: stretch;
            gap: 1rem;
        }

        .quantity-controls {
            justify-content: center;
        }

        .order-summary {
            position: static;
            margin-top: 2rem;
            padding: 2rem;
        }

        .section-title {
            font-size: 1.75rem;
        }
    }

    /* Tablet Styles */
    @media (min-width: 769px) and (max-width: 1024px) {
        .tickets-container {
            max-width: 900px;
        }

        .ticket-card {
            padding: 2rem;
        }

        .quantity-btn {
            padding: 0.875rem 1rem;
            min-width: 45px;
            height: 45px;
        }

        .quantity-input {
            width: 70px;
            padding: 0.875rem 0.5rem;
        }
    }

    /* Large Desktop Styles */
    @media (min-width: 1200px) {
        .tickets-container {
            max-width: 1400px;
        }

        .ticket-card {
            padding: 3rem;
        }

        .quantity-btn {
            padding: 1.25rem 1.5rem;
            min-width: 55px;
            height: 55px;
            font-size: 1.3rem;
        }

        .quantity-input {
            width: 90px;
            padding: 1.25rem 0.5rem;
            font-size: 1.3rem;
        }
    }
</style>
@endpush

@section('content')
<div class="ticket-selection-container">
    <!-- Event Header -->
    <div class="event-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-12">
                    <h1>{{ $event->title }}</h1>
                    <p class="mb-3">{{ Str::limit($event->description, 150) }}</p>
                    <div class="event-meta">
                        <span>
                            <i class="fas fa-calendar" aria-hidden="true"></i>
                            {{ $event->start_date->format('l, F j, Y') }}
                        </span>
                        <span>
                            <i class="fas fa-clock" aria-hidden="true"></i>
                            {{ $event->start_date->format('g:i A') }}
                        </span>
                        @if($event->location)
                        <span>
                            <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                            {{ $event->location }}
                        </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="tickets-container">
            @if($tickets->count() > 0)
            <h2 class="section-title">Select Your Tickets</h2>

            <div class="row">
                <div class="col-lg-8">
                    <form id="ticketForm" action="{{ route('registration.process', $event) }}" method="POST">
                        @csrf

                        @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                        @endif

                        @if (session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                        @endif

                        @foreach($tickets as $ticket)
                        <div class="ticket-card" data-ticket-id="{{ $ticket->id }}" data-price="{{ $ticket->price }}">
                            @if($ticket->quantity && $ticket->available <= 0)
                                <div class="sold-out-overlay">
                                <div>
                                    <i class="fas fa-times-circle me-2" aria-hidden="true"></i>
                                    SOLD OUT
                                </div>
                        </div>
                        @endif

                        <div class="ticket-header">
                            <div class="ticket-info">
                                <h3>{{ $ticket->name }}</h3>
                                @if($ticket->description)
                                <div class="ticket-description">{{ $ticket->description }}</div>
                                @endif
                            </div>
                            <div class="ticket-price {{ $ticket->price == 0 ? 'free' : '' }}">
                                @if($ticket->price > 0)
                                ${{ number_format($ticket->price, 2) }}
                                @else
                                Free
                                @endif
                            </div>
                        </div>

                        <div class="availability-section">
                            @if($ticket->quantity)
                            @if($ticket->available > 0)
                            @if($ticket->available <= 10)
                                <span class="availability-badge bg-warning text-dark">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                Only {{ $ticket->available }} left
                                </span>
                                @else
                                <span class="availability-badge bg-success text-white">
                                    <i class="fas fa-check-circle me-1"></i>
                                    {{ $ticket->available }} available
                                </span>
                                @endif
                                @else
                                <span class="availability-badge bg-danger text-white">
                                    <i class="fas fa-times-circle me-1"></i>
                                    Sold Out
                                </span>
                                @endif
                                @else
                                <span class="availability-badge bg-info text-white">
                                    <i class="fas fa-infinity me-1"></i>
                                    Unlimited
                                </span>
                                @endif
                        </div>

                        @if(!$ticket->quantity || $ticket->available > 0)
                        <div class="quantity-section">
                            <div class="quantity-label">Quantity:</div>
                            <div class="quantity-controls">
                                <button type="button" class="quantity-btn" onclick="changeQuantity({{ $ticket->id }}, -1);" aria-label="Decrease quantity">
                                    <i class="fas fa-minus" aria-hidden="true"></i>
                                </button>
                                <input type="number" class="quantity-input"
                                    id="quantity_{{ $ticket->id }}"
                                    name="tickets[{{ $ticket->id }}]"
                                    value="0" min="0"
                                    max="{{ $ticket->quantity ? $ticket->available : 10 }}"
                                    onchange="updateQuantity({{ $ticket->id }});"
                                    aria-label="Ticket quantity">
                                <button type="button" class="quantity-btn" onclick="changeQuantity({{ $ticket->id }}, 1);" aria-label="Increase quantity">
                                    <i class="fas fa-plus" aria-hidden="true"></i>
                                </button>
                            </div>
                            <div class="ticket-total" id="total_{{ $ticket->id }}" style="display: none;">
                                Total: $<span id="total_amount_{{ $ticket->id }}">0.00</span>
                            </div>
                        </div>
                        @endif
                </div>
                @endforeach
                </form>
            </div>

            <div class="col-lg-4">
                <div class="order-summary">
                    <h3>Order Summary</h3>

                    <div id="summaryItems">
                        <div class="text-muted text-center py-4">
                            <i class="fas fa-shopping-cart fa-2x mb-2 d-block"></i>
                            No tickets selected
                        </div>
                    </div>

                    <div class="summary-item" id="totalRow" style="display: none;">
                        <span>Total:</span>
                        <span id="grandTotal">$0.00</span>
                    </div>

                    <button type="submit" form="ticketForm" class="btn btn-primary continue-btn" id="continueBtn" disabled>
                        <i class="fas fa-arrow-right me-2" aria-hidden="true"></i>
                        Continue to Registration
                    </button>



                    <div class="security-notice">
                        <i class="fas fa-lock me-1" aria-hidden="true"></i>
                        Secure checkout powered by SSL
                    </div>
                </div>
            </div>
        </div>
        @else
        <!-- No Tickets Available -->
        <div class="no-tickets-container">
            <i class="fas fa-ticket-alt fa-4x" aria-hidden="true"></i>
            <h3>No Tickets Available</h3>
            <p class="text-muted mb-4">Ticket sales for this event are not currently available.</p>
            <a href="{{ route('events.show', $event) }}" class="btn btn-outline-primary btn-lg">
                <i class="fas fa-arrow-left me-2" aria-hidden="true"></i>Back to Event Details
            </a>
        </div>
        @endif
    </div>
</div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        let selectedTickets = {};

        // Add form submission debugging
        document.getElementById('ticketForm').addEventListener('submit', function(e) {
            console.log('Form submit event triggered');

            // Get all form data
            const formData = new FormData(this);
            console.log('Form data:');
            for (let [key, value] of formData.entries()) {
                console.log(key, value);
            }

            // Check if any tickets are selected
            let hasTickets = false;
            for (let [key, value] of formData.entries()) {
                if (key.startsWith('tickets[') && parseInt(value) > 0) {
                    hasTickets = true;
                    break;
                }
            }

            if (!hasTickets) {
                alert('Please select at least one ticket.');
                e.preventDefault();
                return false;
            }

            console.log('Form validation passed, submitting...');
        });

        window.changeQuantity = function(ticketId, change) {
            const input = document.getElementById(`quantity_${ticketId}`);
            const currentValue = parseInt(input.value) || 0;
            const newValue = Math.max(0, Math.min(currentValue + change, parseInt(input.max)));

            input.value = newValue;
            updateQuantity(ticketId);

            // Add visual feedback
            const button = event.target.closest('.quantity-btn');
            if (button && newValue !== currentValue) {
                button.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    button.style.transform = '';
                }, 150);
            }
        };

        window.updateQuantity = function(ticketId) {
            const input = document.getElementById(`quantity_${ticketId}`);
            const quantity = parseInt(input.value) || 0;
            const ticketCard = document.querySelector(`[data-ticket-id="${ticketId}"]`);
            const price = parseFloat(ticketCard.dataset.price);
            const totalElement = document.getElementById(`total_${ticketId}`);
            const totalAmountElement = document.getElementById(`total_amount_${ticketId}`);

            // Update quantity button states
            const minusBtn = ticketCard.querySelector('.quantity-btn:first-child');
            const plusBtn = ticketCard.querySelector('.quantity-btn:last-child');
            const maxQuantity = parseInt(input.max);

            minusBtn.disabled = quantity <= 0;
            plusBtn.disabled = quantity >= maxQuantity;

            // Update individual ticket total
            if (quantity > 0) {
                const total = price * quantity;
                totalAmountElement.textContent = total.toFixed(2);
                totalElement.style.display = 'block';
                ticketCard.classList.add('selected');
                selectedTickets[ticketId] = {
                    quantity: quantity,
                    price: price,
                    name: ticketCard.querySelector('h3').textContent
                };
            } else {
                totalElement.style.display = 'none';
                ticketCard.classList.remove('selected');
                delete selectedTickets[ticketId];
            }

            updateOrderSummary();
        };

        function updateOrderSummary() {
            const summaryItems = document.getElementById('summaryItems');
            const totalRow = document.getElementById('totalRow');
            const grandTotal = document.getElementById('grandTotal');
            const continueBtn = document.getElementById('continueBtn');

            // Clear existing items
            summaryItems.innerHTML = '';

            let total = 0;
            let hasItems = false;

            // Add selected tickets to summary
            for (const [ticketId, ticket] of Object.entries(selectedTickets)) {
                if (ticket.quantity > 0) {
                    hasItems = true;
                    const itemTotal = ticket.price * ticket.quantity;
                    total += itemTotal;

                    const summaryItem = document.createElement('div');
                    summaryItem.className = 'summary-item';
                    summaryItem.innerHTML = `
                        <div>
                            <div style="font-weight: 600;">${ticket.name}</div>
                            <small class="text-muted">Qty: ${ticket.quantity} × $${ticket.price.toFixed(2)}</small>
                        </div>
                        <span style="font-weight: 600;">$${itemTotal.toFixed(2)}</span>
                    `;
                    summaryItems.appendChild(summaryItem);
                }
            }

            if (!hasItems) {
                summaryItems.innerHTML = `
                    <div class="text-muted text-center py-4">
                        <i class="fas fa-shopping-cart fa-2x mb-2 d-block"></i>
                        No tickets selected
                    </div>
                `;
                totalRow.style.display = 'none';
                continueBtn.disabled = true;
                continueBtn.classList.add('disabled');
            } else {
                grandTotal.textContent = `$${total.toFixed(2)}`;
                totalRow.style.display = 'flex';
                continueBtn.disabled = false;
                continueBtn.classList.remove('disabled');
            }
        }

        // Initialize quantity controls
        document.querySelectorAll('.quantity-input').forEach(input => {
            input.addEventListener('change', function() {
                const ticketId = this.name.match(/\[(\d+)\]/)[1];
                updateQuantity(parseInt(ticketId));
            });

            // Prevent invalid input
            input.addEventListener('input', function() {
                const value = parseInt(this.value);
                const max = parseInt(this.max);
                const min = parseInt(this.min);

                if (value > max) this.value = max;
                if (value < min) this.value = min;
            });
        });

        // Initialize button states
        document.querySelectorAll('.ticket-card').forEach(card => {
            const ticketId = card.dataset.ticketId;
            updateQuantity(parseInt(ticketId));
        });

        // Add keyboard support for quantity buttons
        document.querySelectorAll('.quantity-btn').forEach(btn => {
            btn.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.click();
                }
            });
        });

        // Add continue button click handler
        document.getElementById('continueBtn').addEventListener('click', function(e) {
            console.log('Continue button clicked');
            console.log('Button disabled:', this.disabled);
            console.log('Selected tickets:', selectedTickets);

            // Don't prevent default if button is enabled
            if (!this.disabled) {
                // Add loading state
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
                this.disabled = true;

                // Re-enable after a delay if form doesn't submit
                setTimeout(() => {
                    if (this.disabled) {
                        this.innerHTML = originalText;
                        this.disabled = false;
                        console.log('Form submission timeout - re-enabling button');
                    }
                }, 8000);

                // Let the form submit naturally
                console.log('Allowing form submission...');
            } else {
                console.log('Button is disabled, preventing submission');
                e.preventDefault();
            }
        });
    });
</script>
@endpush
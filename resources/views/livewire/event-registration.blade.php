<div class="event-registration-form">
    <style>
        .event-registration-form {
            max-width: 600px;
        }

        .registration-form .form-label {
            font-weight: 600;
            color: #333;
        }

        .registration-form .form-control:focus {
            border-color: #0a2463;
            box-shadow: 0 0 0 0.2rem rgba(10, 36, 99, 0.25);
        }

        .event-summary {
            border-left: 4px solid #0a2463;
        }

        .btn-primary {
            background-color: #0a2463;
            border-color: #0a2463;
        }

        .btn-primary:hover {
            background-color: #083154;
            border-color: #083154;
        }

        [wire\:loading] {
            opacity: 0.7;
        }
    </style>

    @if($showSuccessMessage)
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <strong>Registration Successful!</strong>
        You have been successfully registered for {{ $event->title }}.
        A confirmation email will be sent to {{ $email }}.
        <button type="button" class="btn-close" wire:click="closeSuccessMessage" aria-label="Close"></button>
    </div>
    @endif

    @if($errors->has('general'))
    <div class="alert alert-danger" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        {{ $errors->first('general') }}
    </div>
    @endif

    <form wire:submit="register" class="registration-form">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="name" class="form-label">
                    Full Name <span class="text-danger">*</span>
                </label>
                <input
                    type="text"
                    class="form-control @error('name') is-invalid @enderror"
                    id="name"
                    wire:model="name"
                    placeholder="Enter your full name"
                    required>
                @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6 mb-3">
                <label for="email" class="form-label">
                    Email Address <span class="text-danger">*</span>
                </label>
                <input
                    type="email"
                    class="form-control @error('email') is-invalid @enderror"
                    id="email"
                    wire:model="email"
                    placeholder="Enter your email address"
                    required>
                @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="phone" class="form-label">Phone Number</label>
                <input
                    type="tel"
                    class="form-control @error('phone') is-invalid @enderror"
                    id="phone"
                    wire:model="phone"
                    placeholder="Enter your phone number">
                @error('phone')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6 mb-3">
                <label for="organization" class="form-label">Organization</label>
                <input
                    type="text"
                    class="form-control @error('organization') is-invalid @enderror"
                    id="organization"
                    wire:model="organization"
                    placeholder="Enter your organization">
                @error('organization')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="mb-3">
            <label for="dietary_requirements" class="form-label">Dietary Requirements</label>
            <textarea
                class="form-control @error('dietary_requirements') is-invalid @enderror"
                id="dietary_requirements"
                wire:model="dietary_requirements"
                rows="3"
                placeholder="Please specify any dietary requirements or allergies"></textarea>
            @error('dietary_requirements')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="d-grid">
            <button
                type="submit"
                class="btn btn-primary btn-lg"
                wire:loading.attr="disabled"
                wire:target="register">
                <span wire:loading.remove wire:target="register">
                    <i class="fas fa-user-plus me-2"></i>
                    Register for Event
                </span>
                <span wire:loading wire:target="register">
                    <i class="fas fa-spinner fa-spin me-2"></i>
                    Processing Registration...
                </span>
            </button>
        </div>
    </form>

    <!-- Event Details Summary -->
    <div class="event-summary mt-4 p-3 bg-light rounded">
        <h5 class="mb-2">
            <i class="fas fa-calendar-alt me-2 text-primary"></i>
            Event Details
        </h5>
        <div class="row">
            <div class="col-md-6">
                <p class="mb-1"><strong>Event:</strong> {{ $event->title }}</p>
                <p class="mb-1"><strong>Date:</strong> {{ $event->start_date->format('F j, Y') }}</p>
            </div>
            <div class="col-md-6">
                <p class="mb-1"><strong>Time:</strong> {{ $event->start_date->format('g:i A') }}</p>
                <p class="mb-1"><strong>Location:</strong> {{ $event->location ?? 'TBA' }}</p>
            </div>
        </div>
    </div>
</div>
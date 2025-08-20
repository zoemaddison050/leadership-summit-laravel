<div class="contact-form-wrapper">
    @if($showSuccessMessage)
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <strong>Message Sent!</strong>
        Thank you for contacting us. We'll get back to you within 24 hours.
        <button type="button" class="btn-close" wire:click="closeSuccessMessage" aria-label="Close"></button>
    </div>
    @endif

    @if($errors->has('general'))
    <div class="alert alert-danger" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        {{ $errors->first('general') }}
    </div>
    @endif

    <form wire:submit="submit" class="contact-form">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="contact-name" class="form-label">
                    Your Name <span class="text-danger">*</span>
                </label>
                <input
                    type="text"
                    class="form-control @error('name') is-invalid @enderror"
                    id="contact-name"
                    wire:model="name"
                    placeholder="Enter your full name"
                    required>
                @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6 mb-3">
                <label for="contact-email" class="form-label">
                    Email Address <span class="text-danger">*</span>
                </label>
                <input
                    type="email"
                    class="form-control @error('email') is-invalid @enderror"
                    id="contact-email"
                    wire:model="email"
                    placeholder="Enter your email address"
                    required>
                @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="mb-3">
            <label for="contact-subject" class="form-label">
                Subject <span class="text-danger">*</span>
            </label>
            <input
                type="text"
                class="form-control @error('subject') is-invalid @enderror"
                id="contact-subject"
                wire:model="subject"
                placeholder="What is this regarding?"
                required>
            @error('subject')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="contact-message" class="form-label">
                Message <span class="text-danger">*</span>
            </label>
            <textarea
                class="form-control @error('message') is-invalid @enderror"
                id="contact-message"
                wire:model="message"
                rows="5"
                placeholder="Please enter your message here..."
                required></textarea>
            <div class="form-text">
                <span wire:ignore>{{ strlen($message) }}</span>/2000 characters
            </div>
            @error('message')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="d-grid">
            <button
                type="submit"
                class="btn btn-primary btn-lg"
                wire:loading.attr="disabled"
                wire:target="submit">
                <span wire:loading.remove wire:target="submit">
                    <i class="fas fa-paper-plane me-2"></i>
                    Send Message
                </span>
                <span wire:loading wire:target="submit">
                    <i class="fas fa-spinner fa-spin me-2"></i>
                    Sending Message...
                </span>
            </button>
        </div>
    </form>
</div>

<style>
    .contact-form-wrapper {
        max-width: 600px;
    }

    .contact-form .form-label {
        font-weight: 600;
        color: #333;
    }

    .contact-form .form-control:focus {
        border-color: #0a2463;
        box-shadow: 0 0 0 0.2rem rgba(10, 36, 99, 0.25);
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
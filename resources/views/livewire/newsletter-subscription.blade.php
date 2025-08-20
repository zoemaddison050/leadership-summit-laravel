<div class="newsletter-subscription">
    <style>
        .newsletter-subscription .form-control:focus {
            border-color: #0a2463;
            box-shadow: 0 0 0 0.2rem rgba(10, 36, 99, 0.25);
        }

        .newsletter-subscription .btn-primary {
            background-color: #0a2463;
            border-color: #0a2463;
        }

        .newsletter-subscription .btn-primary:hover {
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
        <strong>Subscribed!</strong>
        Thank you for subscribing to our newsletter.
        <button type="button" class="btn-close" wire:click="closeSuccessMessage" aria-label="Close"></button>
    </div>
    @endif

    <form wire:submit="subscribe" class="newsletter-form">
        <div class="input-group">
            <input
                type="email"
                class="form-control @error('email') is-invalid @enderror"
                wire:model="email"
                placeholder="Enter your email address"
                required>
            <button
                type="submit"
                class="btn btn-primary"
                wire:loading.attr="disabled"
                wire:target="subscribe">
                <span wire:loading.remove wire:target="subscribe">
                    <i class="fas fa-envelope me-1"></i>
                    Subscribe
                </span>
                <span wire:loading wire:target="subscribe">
                    <i class="fas fa-spinner fa-spin me-1"></i>
                    Subscribing...
                </span>
            </button>
        </div>
        @error('email')
        <div class="text-danger mt-2">
            <small>{{ $message }}</small>
        </div>
        @enderror
    </form>
</div>

<style>
    .newsletter-subscription .form-control:focus {
        border-color: #0a2463;
        box-shadow: 0 0 0 0.2rem rgba(10, 36, 99, 0.25);
    }

    .newsletter-subscription .btn-primary {
        background-color: #0a2463;
        border-color: #0a2463;
    }

    .newsletter-subscription .btn-primary:hover {
        background-color: #083154;
        border-color: #083154;
    }

    [wire\:loading] {
        opacity: 0.7;
    }
</style>
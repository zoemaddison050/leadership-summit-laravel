@extends('layouts.admin')

@section('title', 'Create Ticket')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Create New Ticket</h1>
                <a href="{{ route('admin.tickets.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Tickets
                </a>
            </div>

            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.tickets.store') }}">
                        @csrf

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="event_id" class="form-label">Event <span class="text-danger">*</span></label>
                                    <select name="event_id" id="event_id" class="form-select @error('event_id') is-invalid @enderror" required>
                                        <option value="">Select an event</option>
                                        @foreach($events as $event)
                                        <option value="{{ $event->id }}" {{ old('event_id') == $event->id ? 'selected' : '' }}>
                                            {{ $event->title }}
                                        </option>
                                        @endforeach
                                    </select>
                                    @error('event_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Ticket Name <span class="text-danger">*</span></label>
                                    <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
                                        value="{{ old('name') }}" required placeholder="e.g., General Admission, VIP Pass">
                                    @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror"
                                rows="3" placeholder="Describe what this ticket includes...">{{ old('description') }}</textarea>
                            @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="price" class="form-label">Price ($) <span class="text-danger">*</span></label>
                                    <input type="number" name="price" id="price" class="form-control @error('price') is-invalid @enderror"
                                        value="{{ old('price') }}" step="0.01" min="0" required placeholder="0.00">
                                    @error('price')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="quantity" class="form-label">Available Quantity <span class="text-danger">*</span></label>
                                    <input type="number" name="quantity" id="quantity" class="form-control @error('quantity') is-invalid @enderror"
                                        value="{{ old('quantity') }}" min="1" required placeholder="100">
                                    @error('quantity')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="max_per_order" class="form-label">Max Per Order</label>
                                    <input type="number" name="max_per_order" id="max_per_order" class="form-control @error('max_per_order') is-invalid @enderror"
                                        value="{{ old('max_per_order') }}" min="1" placeholder="10">
                                    <small class="form-text text-muted">Leave empty for no limit</small>
                                    @error('max_per_order')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="sale_start" class="form-label">Sale Start Date</label>
                                    <input type="datetime-local" name="sale_start" id="sale_start" class="form-control @error('sale_start') is-invalid @enderror"
                                        value="{{ old('sale_start') }}">
                                    <small class="form-text text-muted">Leave empty to start selling immediately</small>
                                    @error('sale_start')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="sale_end" class="form-label">Sale End Date</label>
                                    <input type="datetime-local" name="sale_end" id="sale_end" class="form-control @error('sale_end') is-invalid @enderror"
                                        value="{{ old('sale_end') }}">
                                    <small class="form-text text-muted">Leave empty to sell until event starts</small>
                                    @error('sale_end')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="is_active" id="is_active" class="form-check-input" value="1"
                                    {{ old('is_active', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">
                                    Active (available for purchase)
                                </label>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('admin.tickets.index') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Create Ticket
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
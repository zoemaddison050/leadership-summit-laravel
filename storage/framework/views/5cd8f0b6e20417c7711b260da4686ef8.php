<?php $__env->startSection('title', 'Register for ' . $event->title); ?>

<?php $__env->startPush('styles'); ?>
<style>
    .registration-container {
        min-height: 100vh;
        background: linear-gradient(135deg, #f8fafc 0%, #e3f2fd 100%);
        padding: 2rem 0;
    }

    .registration-card {
        background: white;
        border-radius: 1.5rem;
        padding: 2.5rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        border: 2px solid #e5e7eb;
        max-width: 800px;
        margin: 0 auto;
    }

    .registration-header {
        text-align: center;
        margin-bottom: 2rem;
        padding-bottom: 1.5rem;
        border-bottom: 2px solid #e5e7eb;
    }

    .registration-header h2 {
        color: var(--primary-color);
        font-weight: 700;
        margin-bottom: 0.5rem;
    }

    .event-info {
        background: #f8fafc;
        border-radius: 1rem;
        padding: 1.5rem;
        margin-bottom: 2rem;
    }

    .event-meta {
        display: flex;
        gap: 2rem;
        flex-wrap: wrap;
        margin-top: 1rem;
    }

    .event-meta span {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: #6b7280;
    }

    .form-section {
        margin-bottom: 2rem;
    }

    .form-section h4 {
        color: var(--primary-color);
        font-weight: 600;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid #e5e7eb;
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-label {
        font-weight: 600;
        color: #374151;
        margin-bottom: 0.5rem;
        display: block;
    }

    .form-label.required::after {
        content: ' *';
        color: #dc3545;
    }

    .form-control {
        border-radius: 0.5rem;
        border: 2px solid #e5e7eb;
        padding: 0.875rem 1rem;
        font-size: 1rem;
        width: 100%;
        transition: all 0.2s ease;
    }

    .form-control:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.2rem rgba(10, 36, 99, 0.15);
        outline: none;
    }

    .form-control.is-invalid {
        border-color: #dc3545;
    }

    .form-control.is-valid {
        border-color: #28a745;
    }

    .event-datetime-readonly {
        background-color: #f8f9fa !important;
        color: #6c757d !important;
        cursor: not-allowed;
        opacity: 0.8;
    }

    .invalid-feedback {
        display: block;
        width: 100%;
        margin-top: 0.25rem;
        font-size: 0.875rem;
        color: #dc3545;
    }

    .ticket-selection {
        background: #f8fafc;
        border-radius: 1rem;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .ticket-option {
        border: 2px solid #e5e7eb;
        border-radius: 0.75rem;
        padding: 1.5rem;
        margin-bottom: 1rem;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .ticket-option:hover {
        border-color: var(--primary-color);
        transform: translateY(-2px);
    }

    .ticket-option.selected {
        border-color: var(--primary-color);
        background: linear-gradient(135deg, rgba(10, 36, 99, 0.05), rgba(59, 130, 246, 0.05));
    }

    .ticket-name {
        font-size: 1.25rem;
        font-weight: 600;
        color: var(--primary-color);
        margin-bottom: 0.5rem;
    }

    .ticket-price {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--primary-color);
        margin-bottom: 0.5rem;
    }

    .ticket-price.free {
        color: #10b981;
    }

    .quantity-controls {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-top: 1rem;
    }

    .quantity-btn {
        background: var(--primary-color);
        color: white;
        border: none;
        border-radius: 0.5rem;
        padding: 0.5rem 1rem;
        cursor: pointer;
        font-size: 1.2rem;
        min-width: 40px;
        height: 40px;
    }

    .quantity-btn:hover {
        background: #1e40af;
    }

    .quantity-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .quantity-input {
        width: 80px;
        text-align: center;
        font-weight: 600;
        font-size: 1.1rem;
    }

    .terms-section {
        background: #fef3c7;
        border-radius: 0.75rem;
        padding: 1.5rem;
        margin-bottom: 2rem;
    }

    .form-check {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
    }

    .form-check-input {
        margin-top: 0.25rem;
        width: 1.2rem;
        height: 1.2rem;
    }

    .submit-btn {
        width: 100%;
        padding: 1.25rem;
        font-size: 1.2rem;
        font-weight: 700;
        border-radius: 1rem;
        border: none;
        transition: all 0.3s ease;
    }

    .submit-btn:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(10, 36, 99, 0.3);
    }

    .submit-btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    .total-display {
        background: linear-gradient(135deg, rgba(10, 36, 99, 0.1), rgba(59, 130, 246, 0.1));
        border-radius: 1rem;
        padding: 1.5rem;
        text-align: center;
        margin-bottom: 2rem;
    }

    .total-amount {
        font-size: 2rem;
        font-weight: 800;
        color: var(--primary-color);
    }

    .total-amount.free {
        color: #10b981;
    }

    .modal-content {
        border-radius: 1rem;
        border: none;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    }

    .modal-header {
        border-bottom: 2px solid #e5e7eb;
        padding: 1.5rem;
    }

    .modal-body {
        padding: 1.5rem;
        line-height: 1.6;
    }

    .modal-body h6 {
        color: var(--primary-color);
        font-weight: 600;
        margin-top: 1.5rem;
        margin-bottom: 1rem;
    }

    .modal-body h6:first-child {
        margin-top: 0;
    }

    @media (max-width: 768px) {
        .registration-container {
            padding: 1rem;
        }

        .registration-card {
            padding: 1.5rem;
        }

        .event-meta {
            flex-direction: column;
            gap: 1rem;
        }

        .quantity-controls {
            justify-content: center;
        }
    }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="registration-container">
    <div class="container">
        <div class="registration-card">
            <div class="registration-header">
                <h2>Event Registration</h2>
                <p class="text-muted">Please fill out the form below to register for this event</p>
            </div>

            <!-- Event Information -->
            <div class="event-info">
                <h3><?php echo e($event->title); ?></h3>
                <p class="text-muted"><?php echo e(Str::limit($event->description, 200)); ?></p>
                <div class="event-meta">
                    <span>
                        <i class="fas fa-calendar"></i>
                        <?php echo e($event->start_date->format('l, F j, Y')); ?>

                    </span>
                    <span>
                        <i class="fas fa-clock"></i>
                        <?php echo e($event->start_date->format('g:i A')); ?>

                    </span>
                    <?php if($event->location): ?>
                    <span>
                        <i class="fas fa-map-marker-alt"></i>
                        <?php echo e($event->location); ?>

                    </span>
                    <?php endif; ?>
                </div>
            </div>

            <form method="POST" action="<?php echo e(route('events.register.process', $event)); ?>" id="registrationForm">
                <?php echo csrf_field(); ?>

                <!-- Personal Information -->
                <div class="form-section">
                    <h4>Personal Information</h4>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="attendee_name" class="form-label required">Full Name</label>
                                <input type="text" class="form-control <?php $__errorArgs = ['attendee_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                    id="attendee_name" name="attendee_name" value="<?php echo e(old('attendee_name')); ?>"
                                    placeholder="Enter your full name" required>
                                <?php $__errorArgs = ['attendee_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="attendee_email" class="form-label required">Email Address</label>
                                <input type="email" class="form-control <?php $__errorArgs = ['attendee_email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                    id="attendee_email" name="attendee_email" value="<?php echo e(old('attendee_email')); ?>"
                                    placeholder="Enter your email address" required>
                                <?php $__errorArgs = ['attendee_email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="attendee_phone" class="form-label required">Phone Number</label>
                                <input type="tel" class="form-control <?php $__errorArgs = ['attendee_phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                    id="attendee_phone" name="attendee_phone" value="<?php echo e(old('attendee_phone')); ?>"
                                    placeholder="Enter your phone number" required>
                                <?php $__errorArgs = ['attendee_phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="event_datetime" class="form-label">Event Date & Time</label>
                                <input type="text" class="form-control event-datetime-readonly"
                                    id="event_datetime" name="event_datetime"
                                    value="<?php echo e($event->start_date->format('l, F j, Y \a\t g:i A')); ?>"
                                    readonly>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Emergency Contact Section -->
                <div class="form-section">
                    <h4>Emergency Contact <span class="text-muted">(Optional)</span></h4>
                    <p class="text-muted mb-3">Please provide emergency contact information in case we need to reach someone during the event.</p>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="emergency_contact_name" class="form-label">Emergency Contact Name</label>
                                <input type="text" class="form-control <?php $__errorArgs = ['emergency_contact_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                    id="emergency_contact_name" name="emergency_contact_name" value="<?php echo e(old('emergency_contact_name')); ?>"
                                    placeholder="Enter emergency contact name">
                                <?php $__errorArgs = ['emergency_contact_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="emergency_contact_phone" class="form-label">Emergency Contact Phone</label>
                                <input type="tel" class="form-control <?php $__errorArgs = ['emergency_contact_phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                    id="emergency_contact_phone" name="emergency_contact_phone" value="<?php echo e(old('emergency_contact_phone')); ?>"
                                    placeholder="Enter emergency contact phone number">
                                <?php $__errorArgs = ['emergency_contact_phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ticket Selection -->
                <div class="form-section">
                    <h4><?php echo e($tickets->count() === 1 ? 'Selected Ticket' : 'Ticket Selection'); ?></h4>

                    <div class="ticket-selection">
                        <?php $__currentLoopData = $tickets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ticket): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="ticket-option" data-ticket-id="<?php echo e($ticket->id); ?>" data-price="<?php echo e($ticket->price); ?>">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="ticket-name"><?php echo e($ticket->name); ?></div>
                                    <?php if($ticket->description): ?>
                                    <div class="text-muted mb-2"><?php echo e($ticket->description); ?></div>
                                    <?php endif; ?>
                                    <div class="ticket-price <?php echo e($ticket->price == 0 ? 'free' : ''); ?>">
                                        <?php if($ticket->price > 0): ?>
                                        $<?php echo e(number_format($ticket->price, 2)); ?> per ticket
                                        <?php else: ?>
                                        Free
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-muted">
                                        <?php if($ticket->quantity): ?>
                                        <?php echo e($ticket->available); ?> of <?php echo e($ticket->quantity); ?> available
                                        <?php else: ?>
                                        Unlimited availability
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="quantity-controls">
                                    <button type="button" class="quantity-btn" onclick="changeQuantity(<?php echo e($ticket->id); ?>, -1)">-</button>
                                    <input type="number" class="quantity-input" id="quantity_<?php echo e($ticket->id); ?>"
                                        value="0" min="0" max="<?php echo e($ticket->quantity ? $ticket->available : 10); ?>"
                                        onchange="updateQuantity(<?php echo e($ticket->id); ?>)">
                                    <button type="button" class="quantity-btn" onclick="changeQuantity(<?php echo e($ticket->id); ?>, 1)">+</button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                        <!-- Hidden inputs for ticket selections -->
                        <div id="ticket-selections-container">
                            <?php $__currentLoopData = $tickets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ticket): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <input type="hidden" name="ticket_selections[<?php echo e($ticket->id); ?>]" id="ticket_selection_<?php echo e($ticket->id); ?>" value="0">
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>

                        <?php $__errorArgs = ['ticket_selections'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <div class="invalid-feedback d-block"><?php echo e($message); ?></div>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>

                <!-- Total Display -->
                <div class="total-display" id="totalDisplay" style="display: none;">
                    <div>Total Amount</div>
                    <div class="total-amount" id="totalAmount">$0.00</div>
                </div>

                <!-- Terms and Conditions -->
                <div class="terms-section">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input <?php $__errorArgs = ['terms_accepted'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                            id="terms_accepted" name="terms_accepted" value="1"
                            <?php echo e(old('terms_accepted') ? 'checked' : ''); ?> required>
                        <label class="form-check-label" for="terms_accepted">
                            <strong>I agree to the terms and conditions</strong><br>
                            <small class="text-muted">
                                By registering for this event, I acknowledge that I have read and agree to the
                                <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">terms and conditions</a>,
                                <a href="#" data-bs-toggle="modal" data-bs-target="#privacyModal">privacy policy</a>, and
                                <a href="#" data-bs-toggle="modal" data-bs-target="#cancellationModal">cancellation policy</a>.
                                I understand that my registration is subject to availability and payment confirmation.
                            </small>
                        </label>
                    </div>
                    <?php $__errorArgs = ['terms_accepted'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <div class="invalid-feedback d-block"><?php echo e($message); ?></div>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn btn-primary submit-btn" id="submitBtn" disabled>
                    <i class="fas fa-calendar-check me-2"></i>
                    Complete Registration
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Terms and Conditions Modal -->
<div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="termsModalLabel">Terms and Conditions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6>Event Registration Terms</h6>
                <p>By registering for this event, you agree to the following terms and conditions:</p>
                <ul>
                    <li>Registration is subject to availability and payment confirmation</li>
                    <li>All registrations are final and non-transferable</li>
                    <li>Event organizers reserve the right to modify event details or cancel the event</li>
                    <li>Participants must comply with all event rules and regulations</li>
                    <li>Event organizers are not liable for any personal injury or property damage</li>
                </ul>

                <h6>Payment Terms</h6>
                <ul>
                    <li>Payment must be completed within 15 minutes of registration</li>
                    <li>Incomplete payments will result in automatic cancellation</li>
                    <li>Refunds are subject to the cancellation policy</li>
                </ul>

                <h6>Participant Responsibilities</h6>
                <ul>
                    <li>Provide accurate and complete registration information</li>
                    <li>Arrive on time for the event</li>
                    <li>Follow all safety guidelines and instructions</li>
                    <li>Respect other participants and event staff</li>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Privacy Policy Modal -->
<div class="modal fade" id="privacyModal" tabindex="-1" aria-labelledby="privacyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="privacyModalLabel">Privacy Policy</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6>Information Collection</h6>
                <p>We collect the following information during registration:</p>
                <ul>
                    <li>Personal information (name, email, phone number)</li>
                    <li>Emergency contact information (optional)</li>
                    <li>Payment information (processed securely)</li>
                </ul>

                <h6>Information Use</h6>
                <p>Your information is used for:</p>
                <ul>
                    <li>Event registration and communication</li>
                    <li>Emergency contact purposes</li>
                    <li>Payment processing</li>
                    <li>Event updates and notifications</li>
                </ul>

                <h6>Information Protection</h6>
                <ul>
                    <li>All personal data is stored securely</li>
                    <li>Payment information is processed through secure channels</li>
                    <li>Information is not shared with third parties without consent</li>
                    <li>You may request deletion of your data after the event</li>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Cancellation Policy Modal -->
<div class="modal fade" id="cancellationModal" tabindex="-1" aria-labelledby="cancellationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cancellationModalLabel">Cancellation Policy</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6>Participant Cancellations</h6>
                <ul>
                    <li><strong>More than 7 days before event:</strong> Full refund minus processing fees</li>
                    <li><strong>3-7 days before event:</strong> 50% refund</li>
                    <li><strong>Less than 3 days before event:</strong> No refund</li>
                    <li><strong>Day of event:</strong> No refund</li>
                </ul>

                <h6>Event Organizer Cancellations</h6>
                <ul>
                    <li>Full refund if event is cancelled by organizers</li>
                    <li>Alternative event dates may be offered</li>
                    <li>Participants will be notified immediately of any changes</li>
                </ul>

                <h6>Refund Process</h6>
                <ul>
                    <li>Refund requests must be submitted in writing</li>
                    <li>Processing time: 5-10 business days</li>
                    <li>Refunds will be issued to the original payment method</li>
                </ul>

                <h6>Force Majeure</h6>
                <p>Events cancelled due to circumstances beyond our control (weather, natural disasters, government restrictions) may not be eligible for refunds, but alternative arrangements will be made when possible.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script src="<?php echo e(asset('js/registration-form.js')); ?>"></script>
<?php if(isset($selectedTicket)): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const preSelectedTicketId = {
            {
                $selectedTicket - > id
            }
        };
        const input = document.getElementById(`quantity_${preSelectedTicketId}`);
        if (input) {
            input.value = 1;
            updateQuantity(preSelectedTicketId);
        }
    });
</script>
<?php endif; ?>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/Apple/Desktop/dev_folder/Dev_project/test.kiro2/leadership-summit-laravel/resources/views/registrations/direct-form.blade.php ENDPATH**/ ?>
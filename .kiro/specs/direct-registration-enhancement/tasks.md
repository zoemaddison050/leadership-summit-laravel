# Implementation Plan

- [x] 1. Create database migrations for enhanced registration system

  - Create migration to add new fields to registrations table (registration_status, marked_at, payment_confirmed_at, declined_at, declined_reason)
  - Create migration for registration_locks table with email, phone, event_id, locked_at, expires_at fields
  - Add indexes for performance on email, phone, and event_id combinations
  - _Requirements: 1.1, 6.5, 8.1, 8.2_

- [x] 2. Create RegistrationLock model and implement locking mechanism

  - Create RegistrationLock model with fillable fields and datetime casting
  - Implement createLock static method with 30-minute expiration
  - Add isExpired method to check lock validity
  - Write unit tests for lock creation and expiration logic
  - _Requirements: 1.1, 8.3_

- [x] 3. Update Registration model with new fields and methods

  - Add new fillable fields for enhanced registration data
  - Add datetime casting for new timestamp fields
  - Implement status check methods (isConfirmed, isPending, isDeclined)
  - Add relationship methods and validation rules
  - _Requirements: 8.1, 8.2, 8.4_

- [x] 4. Create direct registration form view

  - Build comprehensive registration form with all required fields (full name, email, phone)
  - Add readonly event date/time field with greyed-out styling
  - Implement ticket selection interface with quantity controls and real-time pricing
  - Add optional emergency contact section with clear labeling
  - Include terms and conditions checkbox with modal link
  - _Requirements: 2.1, 2.2, 2.3, 3.1, 3.2, 4.1, 4.2, 5.1, 5.2, 6.1, 6.2_

- [ ] 5. Implement form validation with real-time feedback

  - Add client-side validation for email format with immediate feedback
  - Implement phone number format validation with international support
  - Create real-time total price calculation for ticket selections
  - Add terms acceptance validation before form submission
  - Write JavaScript for interactive form behavior
  - _Requirements: 2.4, 2.5, 3.4, 6.3_

- [x] 6. Update RegistrationController with direct registration methods

  - Implement showDirectForm method to display registration form with event data
  - Create processDirectRegistration method with comprehensive validation
  - Add checkDuplicateRegistration method to prevent duplicate submissions
  - Implement markRegistrationPending method for email/phone locking
  - Add session storage for registration data during payment process
  - _Requirements: 1.1, 1.2, 7.1, 8.3_

- [x] 7. Enhance PaymentController for new payment flow

  - Update showCryptoPayment method to display registration summary
  - Modify confirmPayment method to handle "I Have Paid" button with processing message
  - Implement 10-second countdown timer with automatic homepage redirect
  - Add processPaymentConfirmation method to create final registration record
  - Include session cleanup after successful registration creation
  - _Requirements: 7.2, 7.4_

- [ ] 8. Create processing message page with countdown

  - Build processing message view with "Processing payment..." text
  - Implement JavaScript countdown timer displaying 10, 9, 8... seconds
  - Add automatic redirect to homepage after countdown completion
  - Include optional "Go to Homepage Now" button for immediate navigation
  - Style processing page with appropriate loading indicators
  - _Requirements: 7.4_

- [ ] 9. Implement duplicate prevention system

  - Add duplicate checking logic in registration processing
  - Create registration locks when "I Have Paid" is clicked
  - Implement lock cleanup for expired registrations
  - Add user-friendly error messages for duplicate attempts
  - Write tests for concurrent registration scenarios
  - _Requirements: 8.3, 8.5_

- [x] 10. Create admin payment management interface

  - Build admin panel section for reviewing pending registrations
  - Implement payment review interface with approve/decline options
  - Add decline payment functionality with reason input
  - Create declineRegistrationPayment method to unlock email/phone for re-use
  - Include audit trail for all admin payment decisions
  - _Requirements: 8.4_

- [x] 11. Update routing to remove authentication requirements

  - Remove authentication middleware from registration routes
  - Update event show page to link directly to registration form
  - Redirect old authentication-based registration routes to new direct form
  - Add routes for admin payment management functions
  - Test all route accessibility without authentication
  - _Requirements: 1.1, 1.3_

- [x] 12. Implement email notifications for registration flow

  - Create email template for registration confirmation after payment
  - Add email notification when admin declines payment with reason
  - Implement email sending in processPaymentConfirmation method
  - Add email notification for successful registration completion
  - Test email delivery for all registration scenarios
  - _Requirements: 7.4_

- [x] 13. Add comprehensive error handling and user feedback

  - Implement graceful handling of expired registration locks
  - Add clear error messages for all validation failures
  - Create user-friendly messages for duplicate registration attempts
  - Handle payment page access without valid session data
  - Add logging for all registration and payment events
  - _Requirements: 1.3, 2.4, 2.5, 8.5_

- [ ] 14. Write comprehensive tests for registration system

  - Create unit tests for RegistrationLock model methods
  - Write integration tests for complete registration flow
  - Add tests for duplicate prevention across concurrent requests
  - Test admin payment decline and unlock functionality
  - Create tests for countdown timer and redirect behavior
  - _Requirements: 1.1, 7.1, 8.3_

- [ ] 15. Update event show page to use direct registration
  - Modify event show page to remove authentication-based registration buttons
  - Add direct "Register Now" button linking to new registration form
  - Update page styling to highlight streamlined registration process
  - Remove any references to account creation or login requirements
  - Test registration flow from event page to completion
  - _Requirements: 1.1, 1.2_

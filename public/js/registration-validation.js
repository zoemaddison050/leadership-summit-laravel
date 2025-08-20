// Registration Form Validation with Real-time Feedback and Enhanced Error Handling
let selectedTicket = null;
let selectedQuantity = 0;
let ticketSelections = {}; // Track multiple ticket selections
let totalAmount = 0;
let validationErrors = {}; // Track validation errors
let isFormSubmitting = false; // Prevent double submission

// Email validation regex (more comprehensive)
const emailRegex =
  /^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/;

// Phone validation regex (international format with better validation)
const phoneRegex = /^[\+]?[1-9][\d]{6,15}$/;

// Error messages for consistent user feedback
const errorMessages = {
  email: {
    required: "Email address is required.",
    invalid: "Please enter a valid email address (e.g., name@example.com).",
    format: "Email format is not valid. Please check for typos.",
  },
  phone: {
    required: "Phone number is required.",
    invalid:
      "Please enter a valid phone number (e.g., +1234567890 or 1234567890).",
    tooShort: "Phone number must be at least 7 digits long.",
    tooLong: "Phone number cannot be longer than 16 digits.",
  },
  name: {
    required: "Full name is required.",
    tooShort: "Please enter your full name (at least 2 characters).",
    invalid:
      "Please enter a valid name using only letters, spaces, hyphens, and apostrophes.",
  },
  terms: {
    required: "You must accept the terms and conditions to continue.",
  },
  tickets: {
    required: "Please select at least one ticket to continue.",
    unavailable:
      "Selected tickets are no longer available. Please refresh the page and try again.",
  },
  network: {
    offline:
      "You appear to be offline. Please check your internet connection and try again.",
    timeout: "The request timed out. Please try again.",
    server:
      "Server error occurred. Please try again or contact support if the problem persists.",
  },
};

// Initialize form validation
document.addEventListener("DOMContentLoaded", function () {
  initializeValidation();

  // Add event listeners to all required fields to update submit button
  const requiredFields = ["attendee_name", "attendee_email", "attendee_phone"];
  requiredFields.forEach((fieldId) => {
    const field = document.getElementById(fieldId);
    if (field) {
      field.addEventListener("input", updateSubmitButton);
      field.addEventListener("blur", updateSubmitButton);
    }
  });

  updateSubmitButton();
});

function initializeValidation() {
  // Email validation
  const emailInput = document.getElementById("attendee_email");
  if (emailInput) {
    emailInput.addEventListener("input", validateEmail);
    emailInput.addEventListener("blur", validateEmail);
  }

  // Phone validation
  const phoneInput = document.getElementById("attendee_phone");
  if (phoneInput) {
    phoneInput.addEventListener("input", validatePhone);
    phoneInput.addEventListener("blur", validatePhone);

    // Format phone number as user types
    phoneInput.addEventListener("input", function (e) {
      let value = e.target.value.replace(/\D/g, "");
      if (value.length >= 10) {
        value = value.replace(/(\d{3})(\d{3})(\d{4})/, "($1) $2-$3");
      } else if (value.length >= 6) {
        value = value.replace(/(\d{3})(\d{3})/, "($1) $2-");
      } else if (value.length >= 3) {
        value = value.replace(/(\d{3})/, "($1) ");
      }
      e.target.value = value;
    });
  }

  // Emergency contact phone validation
  const emergencyPhoneInput = document.getElementById(
    "emergency_contact_phone"
  );
  if (emergencyPhoneInput) {
    emergencyPhoneInput.addEventListener("input", validateEmergencyPhone);
    emergencyPhoneInput.addEventListener("blur", validateEmergencyPhone);

    // Format emergency phone number as user types
    emergencyPhoneInput.addEventListener("input", function (e) {
      let value = e.target.value.replace(/\D/g, "");
      if (value.length >= 10) {
        value = value.replace(/(\d{3})(\d{3})(\d{4})/, "($1) $2-$3");
      } else if (value.length >= 6) {
        value = value.replace(/(\d{3})(\d{3})/, "($1) $2-");
      } else if (value.length >= 3) {
        value = value.replace(/(\d{3})/, "($1) ");
      }
      e.target.value = value;
    });
  }

  // Terms checkbox validation
  const termsCheckbox = document.getElementById("terms_accepted");
  if (termsCheckbox) {
    termsCheckbox.addEventListener("change", validateTerms);
  }

  // Full name validation
  const fullNameInput = document.getElementById("attendee_name");
  if (fullNameInput) {
    fullNameInput.addEventListener("input", validateFullName);
    fullNameInput.addEventListener("blur", validateFullName);
  }
}

function validateEmail() {
  const emailInput = document.getElementById("attendee_email");
  if (!emailInput) return false;

  const email = emailInput.value.trim();

  clearValidationError(emailInput);
  delete validationErrors.email;

  if (email === "") {
    showValidationError(emailInput, errorMessages.email.required);
    validationErrors.email = errorMessages.email.required;
    return false;
  }

  // Check for common email format issues
  if (email.includes("..") || email.startsWith(".") || email.endsWith(".")) {
    showValidationError(emailInput, errorMessages.email.format);
    validationErrors.email = errorMessages.email.format;
    return false;
  }

  if (!emailRegex.test(email)) {
    showValidationError(emailInput, errorMessages.email.invalid);
    validationErrors.email = errorMessages.email.invalid;
    return false;
  }

  // Additional validation for common typos
  const commonDomains = [
    "gmail.com",
    "yahoo.com",
    "hotmail.com",
    "outlook.com",
    "aol.com",
  ];
  const emailParts = email.split("@");
  if (emailParts.length === 2) {
    const domain = emailParts[1].toLowerCase();
    const suggestions = [];

    // Check for common typos in popular domains
    if (domain.includes("gmai") && !domain.includes("gmail")) {
      suggestions.push("gmail.com");
    }
    if (domain.includes("yaho") && !domain.includes("yahoo")) {
      suggestions.push("yahoo.com");
    }

    if (suggestions.length > 0) {
      showValidationWarning(emailInput, `Did you mean ${suggestions[0]}?`);
    }
  }

  showValidationSuccess(emailInput);
  return true;
}

function validatePhone() {
  const phoneInput = document.getElementById("attendee_phone");
  if (!phoneInput) return false;

  const phone = phoneInput.value.trim();

  clearValidationError(phoneInput);
  delete validationErrors.phone;

  if (phone === "") {
    showValidationError(phoneInput, errorMessages.phone.required);
    validationErrors.phone = errorMessages.phone.required;
    return false;
  }

  // Remove spaces, dashes, and parentheses for validation
  const cleanPhone = phone.replace(/[\s\-\(\)]/g, "");

  // Check for obvious invalid patterns
  if (
    cleanPhone.includes("000000") ||
    cleanPhone === "1234567890" ||
    cleanPhone === "0000000000"
  ) {
    showValidationError(
      phoneInput,
      "Please enter a valid phone number, not a placeholder or test number."
    );
    validationErrors.phone = "Invalid phone number pattern";
    return false;
  }

  if (cleanPhone.length < 7) {
    showValidationError(phoneInput, errorMessages.phone.tooShort);
    validationErrors.phone = errorMessages.phone.tooShort;
    return false;
  }

  if (cleanPhone.length > 16) {
    showValidationError(phoneInput, errorMessages.phone.tooLong);
    validationErrors.phone = errorMessages.phone.tooLong;
    return false;
  }

  if (!phoneRegex.test(cleanPhone)) {
    showValidationError(phoneInput, errorMessages.phone.invalid);
    validationErrors.phone = errorMessages.phone.invalid;
    return false;
  }

  showValidationSuccess(phoneInput);
  return true;
}

function validateEmergencyPhone() {
  const phoneInput = document.getElementById("emergency_contact_phone");
  const phone = phoneInput.value.trim();

  clearValidationError(phoneInput);

  // Emergency phone is optional, so empty is valid
  if (phone === "") {
    return true;
  }

  // Remove spaces, dashes, and parentheses for validation
  const cleanPhone = phone.replace(/[\s\-\(\)]/g, "");

  if (!phoneRegex.test(cleanPhone)) {
    showValidationError(
      phoneInput,
      "Please enter a valid phone number (e.g., +1234567890 or 1234567890)."
    );
    return false;
  }

  if (cleanPhone.length < 7 || cleanPhone.length > 16) {
    showValidationError(
      phoneInput,
      "Phone number must be between 7 and 16 digits."
    );
    return false;
  }

  showValidationSuccess(phoneInput);
  return true;
}

function validateFullName() {
  const nameInput = document.getElementById("attendee_name");
  if (!nameInput) return false;

  const name = nameInput.value.trim();

  clearValidationError(nameInput);
  delete validationErrors.name;

  if (name === "") {
    showValidationError(nameInput, errorMessages.name.required);
    validationErrors.name = errorMessages.name.required;
    return false;
  }

  if (name.length < 2) {
    showValidationError(nameInput, errorMessages.name.tooShort);
    validationErrors.name = errorMessages.name.tooShort;
    return false;
  }

  // Check for valid name characters (letters, spaces, hyphens, apostrophes, periods)
  const nameRegex = /^[a-zA-Z\s\-'\.]+$/;
  if (!nameRegex.test(name)) {
    showValidationError(nameInput, errorMessages.name.invalid);
    validationErrors.name = errorMessages.name.invalid;
    return false;
  }

  // Check for suspicious patterns (all same character, keyboard mashing, etc.)
  if (
    /^(.)\1+$/.test(name.replace(/\s/g, "")) ||
    name.toLowerCase() === "test" ||
    name.toLowerCase() === "asdf"
  ) {
    showValidationError(nameInput, "Please enter your real full name.");
    validationErrors.name = "Invalid name pattern";
    return false;
  }

  // Suggest adding last name if only one word is entered
  if (!name.includes(" ") && name.length < 15) {
    showValidationWarning(
      nameInput,
      "Please include both first and last name if possible."
    );
  }

  showValidationSuccess(nameInput);
  return true;
}

function validateTerms() {
  const termsCheckbox = document.getElementById("terms_accepted");
  const isChecked = termsCheckbox.checked;

  clearValidationError(termsCheckbox);

  if (!isChecked) {
    showValidationError(
      termsCheckbox,
      "You must accept the terms and conditions to continue."
    );
    updateSubmitButton();
    return false;
  }

  showValidationSuccess(termsCheckbox);
  updateSubmitButton();
  return true;
}

function showValidationError(input, message) {
  if (!input) return;

  input.classList.add("is-invalid");
  input.classList.remove("is-valid");

  // Remove existing feedback messages
  const existingError = input.parentNode.querySelector(
    ".invalid-feedback.real-time"
  );
  const existingWarning = input.parentNode.querySelector(".warning-feedback");
  if (existingError) existingError.remove();
  if (existingWarning) existingWarning.remove();

  // Add new error message with accessibility support
  const errorDiv = document.createElement("div");
  errorDiv.className = "invalid-feedback real-time";
  errorDiv.textContent = message;
  errorDiv.setAttribute("role", "alert");
  errorDiv.setAttribute("aria-live", "polite");
  input.parentNode.appendChild(errorDiv);

  // Set aria-describedby for screen readers
  input.setAttribute("aria-describedby", input.id + "-error");
  errorDiv.id = input.id + "-error";

  updateSubmitButton();
}

function showValidationWarning(input, message) {
  if (!input) return;

  // Remove existing warning
  const existingWarning = input.parentNode.querySelector(".warning-feedback");
  if (existingWarning) existingWarning.remove();

  // Add warning message (doesn't affect validation state)
  const warningDiv = document.createElement("div");
  warningDiv.className = "warning-feedback";
  warningDiv.style.cssText =
    "display: block; width: 100%; margin-top: 0.25rem; font-size: 0.875rem; color: #f59e0b;";
  warningDiv.textContent = message;
  warningDiv.setAttribute("role", "status");
  warningDiv.setAttribute("aria-live", "polite");
  input.parentNode.appendChild(warningDiv);
}

function showValidationSuccess(input) {
  input.classList.remove("is-invalid");
  input.classList.add("is-valid");

  // Remove error message
  const existingError = input.parentNode.querySelector(
    ".invalid-feedback.real-time"
  );
  if (existingError) {
    existingError.remove();
  }

  updateSubmitButton();
}

function clearValidationError(input) {
  if (!input) return;

  input.classList.remove("is-invalid", "is-valid");

  // Remove all feedback messages
  const existingError = input.parentNode.querySelector(
    ".invalid-feedback.real-time"
  );
  const existingWarning = input.parentNode.querySelector(".warning-feedback");
  if (existingError) existingError.remove();
  if (existingWarning) existingWarning.remove();

  // Remove aria attributes
  input.removeAttribute("aria-describedby");
}

function changeQuantity(ticketId, change) {
  const input = document.getElementById(`quantity_${ticketId}`);
  const currentValue = parseInt(input.value) || 0;
  const newValue = Math.max(
    0,
    Math.min(currentValue + change, parseInt(input.max))
  );

  input.value = newValue;
  updateQuantity(ticketId);
}

function updateQuantity(ticketId) {
  const input = document.getElementById(`quantity_${ticketId}`);
  const quantity = parseInt(input.value) || 0;
  const ticketCard = document.querySelector(`[data-ticket-id="${ticketId}"]`);
  const price = parseFloat(ticketCard.dataset.price);

  // Update ticket selections object
  if (quantity > 0) {
    ticketSelections[ticketId] = {
      quantity: quantity,
      price: price,
    };
    ticketCard.classList.add("selected");
  } else {
    delete ticketSelections[ticketId];
    ticketCard.classList.remove("selected");
  }

  // Calculate total amount in real-time
  calculateTotalAmount();

  // Update hidden inputs for the primary selected ticket
  updateHiddenInputs();

  // Update submit button state
  updateSubmitButton();
}

function calculateTotalAmount() {
  totalAmount = 0;
  let hasSelections = false;

  // Calculate total from all selected tickets
  Object.keys(ticketSelections).forEach((ticketId) => {
    const selection = ticketSelections[ticketId];
    totalAmount += selection.price * selection.quantity;
    hasSelections = true;
  });

  // Update total display
  const totalDisplay = document.getElementById("totalDisplay");
  const totalAmountElement = document.getElementById("totalAmount");

  if (hasSelections && totalDisplay && totalAmountElement) {
    totalAmountElement.textContent =
      totalAmount > 0 ? `$${totalAmount.toFixed(2)}` : "Free";
    totalAmountElement.className =
      totalAmount > 0 ? "total-amount" : "total-amount free";
    totalDisplay.style.display = "block";
  } else if (totalDisplay) {
    totalDisplay.style.display = "none";
  }
}

function updateHiddenInputs() {
  // Update the hidden inputs for each ticket selection
  Object.keys(ticketSelections).forEach((ticketId) => {
    const hiddenInput = document.getElementById(`ticket_selection_${ticketId}`);
    if (hiddenInput) {
      hiddenInput.value = ticketSelections[ticketId].quantity;
    }
  });

  // Clear unselected tickets
  document
    .querySelectorAll('input[name^="ticket_selections["]')
    .forEach((input) => {
      const ticketId = input.name.match(/\[(\d+)\]/)[1];
      if (!ticketSelections[ticketId]) {
        input.value = 0;
      }
    });
}

function updateSubmitButton() {
  const submitBtn = document.getElementById("submitBtn");
  if (!submitBtn) return;

  const termsAccepted =
    document.getElementById("terms_accepted")?.checked || false;
  const hasTicketSelected = Object.keys(ticketSelections).length > 0;

  // Check if all required fields are valid
  const isEmailValid = validateEmailSilent();
  const isPhoneValid = validatePhoneSilent();
  const isNameValid = validateNameSilent();
  const isEmergencyPhoneValid = validateEmergencyPhoneSilent();

  const allValid =
    isEmailValid &&
    isPhoneValid &&
    isNameValid &&
    isEmergencyPhoneValid &&
    hasTicketSelected &&
    termsAccepted;

  submitBtn.disabled = !allValid;

  // Update button text based on state
  if (!hasTicketSelected) {
    submitBtn.innerHTML =
      '<i class="fas fa-calendar-check me-2"></i>Select Tickets to Continue';
  } else if (!termsAccepted) {
    submitBtn.innerHTML =
      '<i class="fas fa-calendar-check me-2"></i>Accept Terms to Continue';
  } else if (!allValid) {
    submitBtn.innerHTML =
      '<i class="fas fa-calendar-check me-2"></i>Complete Required Fields';
  } else {
    submitBtn.innerHTML =
      '<i class="fas fa-calendar-check me-2"></i>Complete Registration';
  }
}

// Silent validation functions (don't show UI feedback)
function validateEmailSilent() {
  const emailInput = document.getElementById("attendee_email");
  if (!emailInput) return false;
  const email = emailInput.value.trim();
  return email !== "" && emailRegex.test(email);
}

function validatePhoneSilent() {
  const phoneInput = document.getElementById("attendee_phone");
  if (!phoneInput) return false;
  const phone = phoneInput.value.trim();
  if (phone === "") return false;
  const cleanPhone = phone.replace(/[\s\-\(\)]/g, "");
  return (
    phoneRegex.test(cleanPhone) &&
    cleanPhone.length >= 7 &&
    cleanPhone.length <= 16
  );
}

function validateNameSilent() {
  const nameInput = document.getElementById("attendee_name");
  if (!nameInput) return false;
  const name = nameInput.value.trim();
  return name !== "" && name.length >= 2;
}

function validateEmergencyPhoneSilent() {
  const phoneInput = document.getElementById("emergency_contact_phone");
  if (!phoneInput) return true; // If field doesn't exist, consider it valid
  const phone = phoneInput.value.trim();
  if (phone === "") return true; // Optional field
  const cleanPhone = phone.replace(/[\s\-\(\)]/g, "");
  return (
    phoneRegex.test(cleanPhone) &&
    cleanPhone.length >= 7 &&
    cleanPhone.length <= 16
  );
}

// Initialize form submission validation
document.addEventListener("DOMContentLoaded", function () {
  const registrationForm = document.getElementById("registrationForm");
  const termsCheckbox = document.getElementById("terms_accepted");

  // Update submit button when terms checkbox changes
  if (termsCheckbox) {
    termsCheckbox.addEventListener("change", function () {
      validateTerms();
      updateSubmitButton();
    });
  }

  // Enhanced form validation on submit with comprehensive error handling
  if (registrationForm) {
    registrationForm.addEventListener("submit", function (e) {
      // Prevent double submission
      if (isFormSubmitting) {
        e.preventDefault();
        return;
      }

      let isValid = true;
      const errors = [];

      // Validate all fields and collect errors
      if (!validateFullName()) {
        isValid = false;
        errors.push("Full name is invalid");
      }
      if (!validateEmail()) {
        isValid = false;
        errors.push("Email address is invalid");
      }
      if (!validatePhone()) {
        isValid = false;
        errors.push("Phone number is invalid");
      }
      if (!validateEmergencyPhone()) {
        isValid = false;
        errors.push("Emergency contact phone is invalid");
      }
      if (!validateTerms()) {
        isValid = false;
        errors.push("Terms and conditions must be accepted");
      }

      // Check ticket selection
      if (Object.keys(ticketSelections).length === 0) {
        isValid = false;
        errors.push("At least one ticket must be selected");
        showGlobalError(errorMessages.tickets.required);
      }

      // Check for network connectivity
      if (!navigator.onLine) {
        e.preventDefault();
        isValid = false;
        showGlobalError(errorMessages.network.offline);
        return;
      }

      if (!isValid) {
        e.preventDefault();

        // Log validation errors for debugging
        console.warn("Form validation failed:", errors);

        // Show summary of errors
        showValidationSummary(errors);

        // Scroll to first error
        const firstError = document.querySelector(".is-invalid");
        if (firstError) {
          firstError.scrollIntoView({ behavior: "smooth", block: "center" });
          setTimeout(() => firstError.focus(), 300);
        }

        return;
      }

      // Set submitting state
      isFormSubmitting = true;
      const submitBtn = document.getElementById("submitBtn");
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML =
          '<i class="fas fa-spinner fa-spin me-2"></i>Processing Registration...';
      }

      // Add timeout protection
      const timeoutId = setTimeout(() => {
        if (isFormSubmitting) {
          showGlobalError(errorMessages.network.timeout);
          resetFormSubmission();
        }
      }, 30000); // 30 second timeout

      // Clear timeout if form submits successfully
      registrationForm.addEventListener("beforeunload", () => {
        clearTimeout(timeoutId);
      });
    });

    // Handle form submission errors
    window.addEventListener("error", function (e) {
      if (isFormSubmitting) {
        console.error("Form submission error:", e.error);
        showGlobalError(errorMessages.network.server);
        resetFormSubmission();
      }
    });

    // Handle network errors
    window.addEventListener("offline", function () {
      if (isFormSubmitting) {
        showGlobalError(errorMessages.network.offline);
        resetFormSubmission();
      }
    });
  }
});

// Utility functions for enhanced error handling

function showGlobalError(message) {
  // Remove existing global error
  const existingError = document.querySelector(".global-error-message");
  if (existingError) {
    existingError.remove();
  }

  // Create global error message
  const errorDiv = document.createElement("div");
  errorDiv.className = "alert alert-danger global-error-message";
  errorDiv.style.cssText =
    "position: fixed; top: 20px; left: 50%; transform: translateX(-50%); z-index: 9999; max-width: 90%; box-shadow: 0 4px 12px rgba(0,0,0,0.15);";
  errorDiv.innerHTML = `
    <div class="d-flex align-items-center">
      <i class="fas fa-exclamation-triangle me-2"></i>
      <span>${message}</span>
      <button type="button" class="btn-close ms-auto" onclick="this.parentElement.parentElement.remove()"></button>
    </div>
  `;
  errorDiv.setAttribute("role", "alert");
  errorDiv.setAttribute("aria-live", "assertive");

  document.body.appendChild(errorDiv);

  // Auto-remove after 10 seconds
  setTimeout(() => {
    if (errorDiv.parentNode) {
      errorDiv.remove();
    }
  }, 10000);
}

function showValidationSummary(errors) {
  if (errors.length === 0) return;

  // Remove existing summary
  const existingSummary = document.querySelector(".validation-summary");
  if (existingSummary) {
    existingSummary.remove();
  }

  // Create validation summary
  const summaryDiv = document.createElement("div");
  summaryDiv.className = "alert alert-warning validation-summary mb-4";
  summaryDiv.innerHTML = `
    <h6><i class="fas fa-exclamation-triangle me-2"></i>Please correct the following errors:</h6>
    <ul class="mb-0">
      ${errors.map((error) => `<li>${error}</li>`).join("")}
    </ul>
  `;
  summaryDiv.setAttribute("role", "alert");
  summaryDiv.setAttribute("aria-live", "polite");

  // Insert at top of form
  const form = document.getElementById("registrationForm");
  if (form) {
    form.insertBefore(summaryDiv, form.firstChild);
  }
}

function resetFormSubmission() {
  isFormSubmitting = false;
  const submitBtn = document.getElementById("submitBtn");
  if (submitBtn) {
    submitBtn.disabled = false;
    updateSubmitButton(); // Restore original button text
  }
}

function handleFormError(error, context = "") {
  console.error(`Form error ${context}:`, error);

  let errorMessage = errorMessages.network.server;

  // Customize error message based on error type
  if (error.name === "NetworkError" || error.message.includes("fetch")) {
    errorMessage = errorMessages.network.offline;
  } else if (error.message.includes("timeout")) {
    errorMessage = errorMessages.network.timeout;
  }

  showGlobalError(errorMessage);
  resetFormSubmission();
}

// Enhanced ticket validation with availability checking
function validateTicketAvailability() {
  const ticketOptions = document.querySelectorAll(".ticket-option");
  let hasUnavailableTickets = false;

  ticketOptions.forEach((option) => {
    const ticketId = option.dataset.ticketId;
    const quantityInput = document.getElementById(`quantity_${ticketId}`);

    if (quantityInput && parseInt(quantityInput.value) > 0) {
      const maxAvailable = parseInt(quantityInput.max);
      const requested = parseInt(quantityInput.value);

      if (requested > maxAvailable) {
        hasUnavailableTickets = true;
        showValidationError(
          quantityInput,
          `Only ${maxAvailable} tickets available`
        );
      }
    }
  });

  if (hasUnavailableTickets) {
    showGlobalError(errorMessages.tickets.unavailable);
    return false;
  }

  return true;
}

// Accessibility improvements
function announceToScreenReader(message) {
  const announcement = document.createElement("div");
  announcement.setAttribute("aria-live", "polite");
  announcement.setAttribute("aria-atomic", "true");
  announcement.className = "sr-only";
  announcement.textContent = message;

  document.body.appendChild(announcement);

  setTimeout(() => {
    document.body.removeChild(announcement);
  }, 1000);
}

// Enhanced error logging for debugging
function logValidationError(field, error, value = "") {
  const errorData = {
    timestamp: new Date().toISOString(),
    field: field,
    error: error,
    value: value ? value.substring(0, 50) : "", // Limit value length for privacy
    userAgent: navigator.userAgent,
    url: window.location.href,
  };

  console.warn("Validation error:", errorData);

  // Could send to analytics service here
  // analytics.track('validation_error', errorData);
}

// Form state management
function saveFormState() {
  const formData = {
    attendee_name: document.getElementById("attendee_name")?.value || "",
    attendee_email: document.getElementById("attendee_email")?.value || "",
    attendee_phone: document.getElementById("attendee_phone")?.value || "",
    emergency_contact_name:
      document.getElementById("emergency_contact_name")?.value || "",
    emergency_contact_phone:
      document.getElementById("emergency_contact_phone")?.value || "",
    ticketSelections: ticketSelections,
    timestamp: Date.now(),
  };

  try {
    sessionStorage.setItem("registrationFormState", JSON.stringify(formData));
  } catch (e) {
    console.warn("Could not save form state:", e);
  }
}

function restoreFormState() {
  try {
    const savedState = sessionStorage.getItem("registrationFormState");
    if (!savedState) return;

    const formData = JSON.parse(savedState);

    // Only restore if saved within last 30 minutes
    if (Date.now() - formData.timestamp > 30 * 60 * 1000) {
      sessionStorage.removeItem("registrationFormState");
      return;
    }

    // Restore form fields
    Object.keys(formData).forEach((key) => {
      if (key === "ticketSelections" || key === "timestamp") return;

      const input = document.getElementById(key);
      if (input && formData[key]) {
        input.value = formData[key];
      }
    });

    // Restore ticket selections
    if (formData.ticketSelections) {
      Object.keys(formData.ticketSelections).forEach((ticketId) => {
        const quantityInput = document.getElementById(`quantity_${ticketId}`);
        if (quantityInput) {
          quantityInput.value = formData.ticketSelections[ticketId].quantity;
          updateQuantity(ticketId);
        }
      });
    }

    announceToScreenReader("Form data restored from previous session");
  } catch (e) {
    console.warn("Could not restore form state:", e);
    sessionStorage.removeItem("registrationFormState");
  }
}

// Auto-save form state periodically
setInterval(() => {
  if (!isFormSubmitting && document.getElementById("registrationForm")) {
    saveFormState();
  }
}, 30000); // Save every 30 seconds

// Initialize form state restoration
document.addEventListener("DOMContentLoaded", function () {
  setTimeout(restoreFormState, 100); // Small delay to ensure form is ready
});

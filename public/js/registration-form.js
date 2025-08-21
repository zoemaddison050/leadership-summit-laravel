// Registration Form JavaScript - Simplified and Working Version
let selectedTicket = null;
let selectedQuantity = 0;
let ticketSelections = {};

// Initialize form when DOM is loaded
document.addEventListener("DOMContentLoaded", function () {
  initializeForm();
});

function initializeForm() {
  console.log("Initializing registration form...");

  // Add event listeners to form fields
  const requiredFields = ["attendee_name", "attendee_email", "attendee_phone"];
  requiredFields.forEach((fieldId) => {
    const field = document.getElementById(fieldId);
    if (field) {
      console.log(`Found field: ${fieldId}`);
      field.addEventListener("input", updateSubmitButton);
      field.addEventListener("blur", updateSubmitButton);
    } else {
      console.log(`Field not found: ${fieldId}`);
    }
  });

  // Add event listener to terms checkbox
  const termsCheckbox = document.getElementById("terms_accepted");
  if (termsCheckbox) {
    console.log("Found terms checkbox");
    termsCheckbox.addEventListener("change", updateSubmitButton);
  } else {
    console.log("Terms checkbox not found");
  }

  // Initial submit button update
  console.log("Running initial submit button update");
  updateSubmitButton();
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

  // Clear other selections (single ticket selection)
  document.querySelectorAll(".ticket-option").forEach((option) => {
    if (option.dataset.ticketId != ticketId) {
      option.classList.remove("selected");
      const otherInput = option.querySelector(".quantity-input");
      if (otherInput) {
        otherInput.value = 0;
        // Clear hidden input
        const hiddenInput = document.getElementById(
          `ticket_selection_${option.dataset.ticketId}`
        );
        if (hiddenInput) {
          hiddenInput.value = 0;
        }
      }
    }
  });

  // Update current selection
  if (quantity > 0) {
    ticketCard.classList.add("selected");
    selectedTicket = ticketId;
    selectedQuantity = quantity;

    // Update hidden input
    const hiddenInput = document.getElementById(`ticket_selection_${ticketId}`);
    if (hiddenInput) {
      hiddenInput.value = quantity;
    }

    // Update total display
    const total = price * quantity;
    const totalAmountElement = document.getElementById("totalAmount");
    const totalDisplay = document.getElementById("totalDisplay");

    if (totalAmountElement && totalDisplay) {
      totalAmountElement.textContent =
        price > 0 ? `$${total.toFixed(2)}` : "Free";
      totalAmountElement.className =
        price > 0 ? "total-amount" : "total-amount free";
      totalDisplay.style.display = "block";
    }
  } else {
    ticketCard.classList.remove("selected");
    if (selectedTicket == ticketId) {
      selectedTicket = null;
      selectedQuantity = 0;

      // Clear hidden input
      const hiddenInput = document.getElementById(
        `ticket_selection_${ticketId}`
      );
      if (hiddenInput) {
        hiddenInput.value = 0;
      }

      // Hide total display
      const totalDisplay = document.getElementById("totalDisplay");
      if (totalDisplay) {
        totalDisplay.style.display = "none";
      }
    }
  }

  updateSubmitButton();
}

function updateSubmitButton() {
  const submitBtn = document.getElementById("submitBtn");
  if (!submitBtn) {
    console.log("Submit button not found");
    return;
  }

  // Check required fields
  const attendeeName = document.getElementById("attendee_name");
  const attendeeEmail = document.getElementById("attendee_email");
  const attendeePhone = document.getElementById("attendee_phone");
  const termsAccepted = document.getElementById("terms_accepted");

  const isNameValid = attendeeName && attendeeName.value.trim().length >= 2;
  const isEmailValid =
    attendeeEmail && isValidEmail(attendeeEmail.value.trim());
  const isPhoneValid =
    attendeePhone && isValidPhone(attendeePhone.value.trim());
  const hasTicketSelected = selectedTicket !== null && selectedQuantity > 0;
  const areTermsAccepted = termsAccepted && termsAccepted.checked;

  // Debug logging
  console.log("Form validation status:", {
    isNameValid,
    isEmailValid,
    isPhoneValid,
    hasTicketSelected,
    areTermsAccepted,
    nameValue: attendeeName ? attendeeName.value : "not found",
    emailValue: attendeeEmail ? attendeeEmail.value : "not found",
    phoneValue: attendeePhone ? attendeePhone.value : "not found",
    selectedTicket,
    selectedQuantity,
  });

  const allValid =
    isNameValid &&
    isEmailValid &&
    isPhoneValid &&
    hasTicketSelected &&
    areTermsAccepted;

  submitBtn.disabled = !allValid;

  // Update button text based on state
  if (!hasTicketSelected) {
    submitBtn.innerHTML =
      '<i class="fas fa-calendar-check me-2"></i>Select Tickets to Continue';
  } else if (!areTermsAccepted) {
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

function isValidEmail(email) {
  const emailRegex =
    /^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/;
  return email.length > 0 && emailRegex.test(email);
}

function isValidPhone(phone) {
  if (phone.length === 0) return false;
  const cleanPhone = phone.replace(/[\s\-\(\)]/g, "");
  return (
    cleanPhone.length >= 7 &&
    cleanPhone.length <= 16 &&
    /^\+?[1-9]\d{6,15}$/.test(cleanPhone)
  );
}

// Form submission validation
document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("registrationForm");
  if (form) {
    form.addEventListener("submit", function (e) {
      if (!selectedTicket || selectedQuantity === 0) {
        e.preventDefault();
        alert("Please select a ticket type and quantity.");
        return false;
      }

      const termsCheckbox = document.getElementById("terms_accepted");
      if (!termsCheckbox || !termsCheckbox.checked) {
        e.preventDefault();
        alert("Please accept the terms and conditions.");
        return false;
      }

      // Validate required fields
      const attendeeName = document.getElementById("attendee_name");
      const attendeeEmail = document.getElementById("attendee_email");
      const attendeePhone = document.getElementById("attendee_phone");

      if (!attendeeName || attendeeName.value.trim().length < 2) {
        e.preventDefault();
        alert("Please enter your full name.");
        attendeeName.focus();
        return false;
      }

      if (!attendeeEmail || !isValidEmail(attendeeEmail.value.trim())) {
        e.preventDefault();
        alert("Please enter a valid email address.");
        attendeeEmail.focus();
        return false;
      }

      if (!attendeePhone || !isValidPhone(attendeePhone.value.trim())) {
        e.preventDefault();
        alert("Please enter a valid phone number.");
        attendeePhone.focus();
        return false;
      }

      // Show loading state
      const submitBtn = document.getElementById("submitBtn");
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML =
          '<i class="fas fa-spinner fa-spin me-2"></i>Processing Registration...';
      }

      return true;
    });
  }
});

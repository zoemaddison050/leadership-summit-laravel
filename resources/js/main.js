/**
 * Main JavaScript functionality for Leadership Summit
 */

// Smooth scrolling for anchor links
document.addEventListener("DOMContentLoaded", function () {
  // Smooth scrolling for all anchor links
  const anchorLinks = document.querySelectorAll('a[href^="#"]');
  anchorLinks.forEach((link) => {
    link.addEventListener("click", function (e) {
      const href = this.getAttribute("href");
      if (href === "#") return;

      const target = document.querySelector(href);
      if (target) {
        e.preventDefault();
        const headerHeight =
          document.querySelector(".site-header").offsetHeight;
        const targetPosition = target.offsetTop - headerHeight - 20;

        window.scrollTo({
          top: targetPosition,
          behavior: "smooth",
        });
      }
    });
  });

  // Sticky header functionality
  const header = document.querySelector(".site-header");
  let lastScrollTop = 0;

  window.addEventListener("scroll", function () {
    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;

    if (scrollTop > 100) {
      header.classList.add("sticky-header");
    } else {
      header.classList.remove("sticky-header");
    }

    // Hide header on scroll down, show on scroll up
    if (scrollTop > lastScrollTop && scrollTop > 200) {
      header.style.transform = "translateY(-100%)";
    } else {
      header.style.transform = "translateY(0)";
    }

    lastScrollTop = scrollTop;
  });

  // Mobile menu enhancements
  const navbarToggler = document.querySelector(".navbar-toggler");
  const navbarCollapse = document.querySelector(".navbar-collapse");

  if (navbarToggler && navbarCollapse) {
    // Close mobile menu when clicking on a link
    const navLinks = navbarCollapse.querySelectorAll(".nav-link");
    navLinks.forEach((link) => {
      link.addEventListener("click", function () {
        if (window.innerWidth < 992) {
          const bsCollapse = new bootstrap.Collapse(navbarCollapse, {
            toggle: false,
          });
          bsCollapse.hide();
        }
      });
    });

    // Close mobile menu when clicking outside
    document.addEventListener("click", function (e) {
      if (
        window.innerWidth < 992 &&
        !navbarToggler.contains(e.target) &&
        !navbarCollapse.contains(e.target) &&
        navbarCollapse.classList.contains("show")
      ) {
        const bsCollapse = new bootstrap.Collapse(navbarCollapse, {
          toggle: false,
        });
        bsCollapse.hide();
      }
    });
  }

  // Form validation enhancements
  const forms = document.querySelectorAll(".needs-validation");
  forms.forEach((form) => {
    form.addEventListener("submit", function (e) {
      if (!form.checkValidity()) {
        e.preventDefault();
        e.stopPropagation();

        // Focus on first invalid field
        const firstInvalid = form.querySelector(":invalid");
        if (firstInvalid) {
          firstInvalid.focus();
        }
      }
      form.classList.add("was-validated");
    });
  });

  // Lazy loading for images
  if ("IntersectionObserver" in window) {
    const imageObserver = new IntersectionObserver((entries, observer) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          const img = entry.target;
          img.src = img.dataset.src;
          img.classList.remove("lazy");
          imageObserver.unobserve(img);
        }
      });
    });

    const lazyImages = document.querySelectorAll("img[data-src]");
    lazyImages.forEach((img) => imageObserver.observe(img));
  }

  // Accessibility enhancements
  // Add focus indicators for keyboard navigation
  document.addEventListener("keydown", function (e) {
    if (e.key === "Tab") {
      document.body.classList.add("keyboard-navigation");
    }
  });

  document.addEventListener("mousedown", function () {
    document.body.classList.remove("keyboard-navigation");
  });

  // Newsletter form handling
  const newsletterForm = document.querySelector(".newsletter-form");
  if (newsletterForm) {
    newsletterForm.addEventListener("submit", function (e) {
      e.preventDefault();

      const email = this.querySelector('input[type="email"]').value;
      const button = this.querySelector('button[type="submit"]');
      const originalText = button.innerHTML;

      // Show loading state
      button.innerHTML =
        '<i class="fas fa-spinner fa-spin" aria-hidden="true"></i>';
      button.disabled = true;

      // Simulate API call (replace with actual implementation)
      setTimeout(() => {
        // Show success message
        const alert = document.createElement("div");
        alert.className = "alert alert-success mt-2";
        alert.innerHTML =
          '<i class="fas fa-check-circle me-2" aria-hidden="true"></i>Thank you for subscribing!';

        this.appendChild(alert);
        this.reset();

        // Reset button
        button.innerHTML = originalText;
        button.disabled = false;

        // Remove alert after 3 seconds
        setTimeout(() => {
          alert.remove();
        }, 3000);
      }, 1000);
    });
  }

  // Print functionality
  window.printPage = function () {
    window.print();
  };

  // Share functionality
  window.shareContent = function (title, url) {
    if (navigator.share) {
      navigator.share({
        title: title,
        url: url,
      });
    } else {
      // Fallback to copying URL to clipboard
      navigator.clipboard.writeText(url).then(() => {
        // Show success message
        const toast = document.createElement("div");
        toast.className = "toast-message";
        toast.textContent = "Link copied to clipboard!";
        document.body.appendChild(toast);

        setTimeout(() => {
          toast.remove();
        }, 3000);
      });
    }
  };
});

// Performance monitoring
window.addEventListener("load", function () {
  // Log page load time for performance monitoring
  const loadTime =
    performance.timing.loadEventEnd - performance.timing.navigationStart;
  console.log("Page load time:", loadTime + "ms");
});

// Error handling
window.addEventListener("error", function (e) {
  console.error("JavaScript error:", e.error);
  // You can send this to your error tracking service
});

// Service Worker registration (for future PWA features)
if ("serviceWorker" in navigator) {
  window.addEventListener("load", function () {
    // Uncomment when you have a service worker
    // navigator.serviceWorker.register('/sw.js');
  });
}

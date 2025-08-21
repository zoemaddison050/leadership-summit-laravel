@extends('layouts.app')

@section('title', 'Contact Us - Leadership Summit 2024')

@push('styles')
<style>
    /* Hero Section */
    .contact-hero {
        background: linear-gradient(135deg, var(--primary-color) 0%, #1e3a8a 100%);
        color: white;
        padding: 4rem 0;
        text-align: center;
    }

    .contact-hero h1 {
        font-size: 3rem;
        font-weight: 700;
        margin-bottom: 1rem;
    }

    .contact-hero p {
        font-size: 1.2rem;
        opacity: 0.9;
        max-width: 600px;
        margin: 0 auto;
    }

    /* Main Content */
    .contact-content {
        padding: 6rem 0;
        background: #f8fafc;
    }

    .contact-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 4rem;
        align-items: start;
    }

    /* Contact Info Cards */
    .contact-info {
        display: grid;
        gap: 2rem;
    }

    .contact-card {
        background: white;
        padding: 2rem;
        border-radius: 1rem;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .contact-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
    }

    .contact-card-header {
        display: flex;
        align-items: center;
        margin-bottom: 1rem;
    }

    .contact-icon {
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 1rem;
        flex-shrink: 0;
    }

    .contact-icon i {
        font-size: 1.2rem;
        color: white;
    }

    .contact-card h3 {
        font-size: 1.3rem;
        font-weight: 600;
        color: var(--primary-color);
        margin: 0;
    }

    .contact-card-content {
        color: var(--dark-gray);
        line-height: 1.6;
    }

    .contact-card-content a {
        color: var(--primary-color);
        text-decoration: none;
        font-weight: 500;
    }

    .contact-card-content a:hover {
        text-decoration: underline;
    }

    /* Contact Form */
    .contact-form {
        background: white;
        padding: 2.5rem;
        border-radius: 1rem;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    }

    .contact-form h2 {
        font-size: 2rem;
        font-weight: 700;
        color: var(--primary-color);
        margin-bottom: 1.5rem;
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-group label {
        display: block;
        font-weight: 600;
        color: var(--primary-color);
        margin-bottom: 0.5rem;
        font-size: 0.95rem;
    }

    .form-control {
        width: 100%;
        padding: 0.875rem 1rem;
        border: 2px solid #e2e8f0;
        border-radius: 0.5rem;
        font-size: 1rem;
        transition: border-color 0.3s ease, box-shadow 0.3s ease;
        background: #fafafa;
    }

    .form-control:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        background: white;
    }

    .form-control.textarea {
        resize: vertical;
        min-height: 120px;
    }

    .submit-btn {
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        color: white;
        border: none;
        padding: 1rem 2rem;
        border-radius: 0.5rem;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        width: 100%;
    }

    .submit-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(59, 130, 246, 0.3);
    }

    /* Map Section */
    .map-section {
        padding: 6rem 0;
        background: white;
    }

    .map-container {
        background: #f1f5f9;
        height: 400px;
        border-radius: 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px solid #e2e8f0;
    }

    .map-placeholder {
        text-align: center;
        color: var(--dark-gray);
    }

    .map-placeholder i {
        font-size: 3rem;
        color: var(--primary-color);
        margin-bottom: 1rem;
    }

    /* FAQ Section */
    .faq-section {
        padding: 6rem 0;
        background: #f8fafc;
    }

    .section-title {
        font-size: 2.5rem;
        font-weight: 700;
        text-align: center;
        margin-bottom: 1rem;
        color: var(--primary-color);
    }

    .section-subtitle {
        text-align: center;
        font-size: 1.1rem;
        color: var(--dark-gray);
        max-width: 600px;
        margin: 0 auto 3rem;
    }

    .faq-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 2rem;
    }

    .faq-item {
        background: white;
        padding: 2rem;
        border-radius: 1rem;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    }

    .faq-question {
        font-weight: 600;
        color: var(--primary-color);
        margin-bottom: 1rem;
        font-size: 1.1rem;
    }

    .faq-answer {
        color: var(--dark-gray);
        line-height: 1.6;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .contact-hero h1 {
            font-size: 2.5rem;
        }

        .contact-grid {
            grid-template-columns: 1fr;
            gap: 3rem;
        }

        .contact-content {
            padding: 4rem 0;
        }

        .map-section,
        .faq-section {
            padding: 4rem 0;
        }

        .section-title {
            font-size: 2rem;
        }
    }
</style>
@endpush

@section('content')
<!-- Hero Section -->
<section class="contact-hero">
    <div class="container">
        <h1>Get in Touch</h1>
        <p>We're here to help you with any questions about the Leadership Summit. Reach out to us and we'll get back to you as soon as possible.</p>
    </div>
</section>

<!-- Main Contact Content -->
<section class="contact-content">
    <div class="container">
        <div class="contact-grid">
            <!-- Contact Information -->
            <div class="contact-info">
                <div class="contact-card">
                    <div class="contact-card-header">
                        <div class="contact-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <h3>Visit Our Office</h3>
                    </div>
                    <div class="contact-card-content">
                        <p><strong>Leadership Summit Headquarters</strong></p>
                        <p>123 Leadership Avenue<br>
                            Downtown District<br>
                            Cypress, CA 90630</p>
                    </div>
                </div>

                <div class="contact-card">
                    <div class="contact-card-header">
                        <div class="contact-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <h3>Call Us</h3>
                    </div>
                    <div class="contact-card-content">
                        <p><strong>Main Office:</strong> <a href="tel:+15551234567">+1 (555) 123-4567</a></p>
                        <p><strong>Registration Support:</strong> <a href="tel:+15551234568">+1 (555) 123-4568</a></p>
                        <p><strong>Hours:</strong> Mon-Fri 9:00 AM - 6:00 PM PST</p>
                    </div>
                </div>

                <div class="contact-card">
                    <div class="contact-card-header">
                        <div class="contact-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <h3>Email Us</h3>
                    </div>
                    <div class="contact-card-content">
                        <p><strong>General Inquiries:</strong><br>
                            <a href="mailto:info@leadershipsummit2024.com">info@leadershipsummit2024.com</a>
                        </p>
                        <p><strong>Media & Press:</strong><br>
                            <a href="mailto:press@leadershipsummit2024.com">press@leadershipsummit2024.com</a>
                        </p>
                        <p><strong>Partnerships:</strong><br>
                            <a href="mailto:partners@leadershipsummit2024.com">partners@leadershipsummit2024.com</a>
                        </p>
                    </div>
                </div>

                <div class="contact-card">
                    <div class="contact-card-header">
                        <div class="contact-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h3>Business Hours</h3>
                    </div>
                    <div class="contact-card-content">
                        <p><strong>Monday - Friday:</strong> 9:00 AM - 6:00 PM</p>
                        <p><strong>Saturday:</strong> 10:00 AM - 4:00 PM</p>
                        <p><strong>Sunday:</strong> Closed</p>
                        <p><em>Emergency support available 24/7 during event dates</em></p>
                    </div>
                </div>
            </div>

            <!-- Contact Form -->
            <div class="contact-form">
                <h2>Send us a Message</h2>
                <form action="#" method="POST">
                    @csrf
                    <div class="form-group">
                        <label for="name">Full Name *</label>
                        <input type="text" id="name" name="name" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" class="form-control">
                    </div>

                    <div class="form-group">
                        <label for="subject">Subject *</label>
                        <select id="subject" name="subject" class="form-control" required>
                            <option value="">Select a topic...</option>
                            <option value="general">General Inquiry</option>
                            <option value="registration">Event Registration</option>
                            <option value="speaking">Speaking Opportunities</option>
                            <option value="sponsorship">Sponsorship & Partnerships</option>
                            <option value="media">Media & Press</option>
                            <option value="technical">Technical Support</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="message">Message *</label>
                        <textarea id="message" name="message" class="form-control textarea" rows="5" required placeholder="Tell us how we can help you..."></textarea>
                    </div>

                    <button type="submit" class="submit-btn">
                        <i class="fas fa-paper-plane me-2"></i>Send Message
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- Map Section -->
<section class="map-section">
    <div class="container">
        <h2 class="section-title">Find Us</h2>
        <p class="section-subtitle">Located in the heart of Cypress, our headquarters is easily accessible by car or public transportation.</p>

        <div class="map-container">
            <div class="map-placeholder">
                <i class="fas fa-map-marked-alt"></i>
                <h3>Interactive Map</h3>
                <p>Map integration coming soon</p>
            </div>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section class="faq-section">
    <div class="container">
        <h2 class="section-title">Frequently Asked Questions</h2>
        <p class="section-subtitle">Quick answers to common questions about contacting us and getting support</p>

        <div class="faq-grid">
            <div class="faq-item">
                <div class="faq-question">How quickly will you respond to my inquiry?</div>
                <div class="faq-answer">We typically respond to all inquiries within 24 hours during business days. For urgent matters during events, we provide 24/7 support.</div>
            </div>

            <div class="faq-item">
                <div class="faq-question">Can I visit your office in person?</div>
                <div class="faq-answer">Yes! We welcome visitors during business hours. We recommend calling ahead to ensure someone is available to meet with you.</div>
            </div>

            <div class="faq-item">
                <div class="faq-question">Do you offer phone support?</div>
                <div class="faq-answer">Absolutely. Our phone lines are open Monday through Friday, 9 AM to 6 PM PST. For immediate assistance, calling is often the fastest option.</div>
            </div>

            <div class="faq-item">
                <div class="faq-question">How can I become a speaker or sponsor?</div>
                <div class="faq-answer">For speaking opportunities, email us at speakers@leadershipsummit2024.com. For sponsorship inquiries, contact partners@leadershipsummit2024.com.</div>
            </div>

            <div class="faq-item">
                <div class="faq-question">Is there parking available at your office?</div>
                <div class="faq-answer">Yes, we have complimentary parking available for visitors. The parking entrance is located on the north side of our building.</div>
            </div>

            <div class="faq-item">
                <div class="faq-question">Can you help with technical issues during events?</div>
                <div class="faq-answer">Our technical support team is available 24/7 during event dates. Contact our main number and select option 3 for immediate technical assistance.</div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Form submission handling
        const contactForm = document.querySelector('.contact-form form');
        if (contactForm) {
            contactForm.addEventListener('submit', function(e) {
                e.preventDefault();

                // Get form data
                const formData = new FormData(this);
                const submitBtn = this.querySelector('.submit-btn');
                const originalText = submitBtn.innerHTML;

                // Show loading state
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Sending...';
                submitBtn.disabled = true;

                // Simulate form submission (replace with actual endpoint)
                setTimeout(() => {
                    alert('Thank you for your message! We\'ll get back to you soon.');
                    this.reset();
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }, 2000);
            });
        }

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    });
</script>
@endpush
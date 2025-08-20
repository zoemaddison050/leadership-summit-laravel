<footer id="colophon" class="site-footer" role="contentinfo">
    <div class="footer-main">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4 mb-md-0">
                    <div class="footer-logo">
                        <h2 class="site-title">
                            <a href="<?php echo e(url('/')); ?>" rel="home"><?php echo e(config('app.name', 'Leadership Summit')); ?></a>
                        </h2>
                    </div>
                    <div class="footer-description mt-3">
                        <p>Join us for the exclusive International Global Leadership Academy Summit in September 2025, Cypress. Connect with global leaders and visionaries to shape the future of leadership.</p>
                    </div>
                    <div class="footer-cta mt-4">
                        <?php
                        $defaultEvent = \App\Models\Event::getDefaultEvent();
                        ?>
                        <?php if($defaultEvent): ?>
                        <a href="<?php echo e(route('events.show', $defaultEvent->slug)); ?>" class="btn btn-primary">
                            <i class="fas fa-ticket-alt me-2" aria-hidden="true"></i>Register Now
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6 mb-4 mb-md-0">
                    <h3 class="footer-heading">Quick Links</h3>
                    <nav aria-label="Footer Navigation">
                        <ul class="footer-menu list-unstyled">
                            <li><a href="<?php echo e(url('/')); ?>">Home</a></li>
                            <li><a href="<?php echo e(url('/about')); ?>">About</a></li>
                            <li><a href="<?php echo e(url('/speakers')); ?>">Speakers</a></li>
                            <li><a href="<?php echo e(url('/events')); ?>">Events</a></li>
                            <li><a href="<?php echo e(url('/contact')); ?>">Contact</a></li>
                        </ul>
                    </nav>
                </div>
                <div class="col-lg-3 col-md-6 mb-4 mb-md-0">
                    <h3 class="footer-heading">Contact Us</h3>
                    <div class="contact-info">
                        <ul class="list-unstyled">
                            <li class="d-flex align-items-start mb-3">
                                <i class="fas fa-map-marker-alt me-3 mt-1" aria-hidden="true"></i>
                                <span>Cypress International Conference Center, Cypress</span>
                            </li>
                            <li class="d-flex align-items-center mb-3">
                                <i class="fas fa-envelope me-3" aria-hidden="true"></i>
                                <a href="mailto:info@leadershipacademy.org">info@leadershipacademy.org</a>
                            </li>
                            <li class="d-flex align-items-center">
                                <i class="fas fa-phone-alt me-3" aria-hidden="true"></i>
                                <a href="tel:+15551234567">+1 (555) 123-4567</a>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <h3 class="footer-heading">Follow Us</h3>
                    <div class="social-links" aria-label="Social Media Links">
                        <a href="#" target="_blank" aria-label="Facebook" class="social-icon">
                            <i class="fab fa-facebook-f" aria-hidden="true"></i>
                        </a>
                        <a href="#" target="_blank" aria-label="Twitter" class="social-icon">
                            <i class="fab fa-twitter" aria-hidden="true"></i>
                        </a>
                        <a href="#" target="_blank" aria-label="LinkedIn" class="social-icon">
                            <i class="fab fa-linkedin-in" aria-hidden="true"></i>
                        </a>
                        <a href="#" target="_blank" aria-label="Instagram" class="social-icon">
                            <i class="fab fa-instagram" aria-hidden="true"></i>
                        </a>
                        <a href="#" target="_blank" aria-label="YouTube" class="social-icon">
                            <i class="fab fa-youtube" aria-hidden="true"></i>
                        </a>
                    </div>
                    <div class="newsletter mt-4">
                        <h4 class="newsletter-heading" id="newsletter-heading">Subscribe to Updates</h4>
                        <form class="newsletter-form mt-3" aria-labelledby="newsletter-heading" action="<?php echo e(url('/newsletter/subscribe')); ?>" method="POST">
                            <?php echo csrf_field(); ?>
                            <div class="input-group">
                                <label for="newsletter-email" class="visually-hidden">Email address</label>
                                <input type="email" id="newsletter-email" name="email" class="form-control" placeholder="Your email" aria-label="Your email" required>
                                <button class="btn btn-primary" type="submit" aria-label="Subscribe">
                                    <i class="fas fa-paper-plane" aria-hidden="true"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="copyright">
                        <p>&copy; <?php echo e(date('Y')); ?> International Global Leadership Academy. All rights reserved.</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="footer-legal text-md-end">
                        <a href="<?php echo e(url('/privacy-policy')); ?>">Privacy Policy</a>
                        <span class="separator" aria-hidden="true">|</span>
                        <a href="<?php echo e(url('/terms-of-service')); ?>">Terms of Service</a>
                        <span class="separator" aria-hidden="true">|</span>
                        <a href="<?php echo e(url('/cookie-policy')); ?>">Cookie Policy</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer><?php /**PATH /Users/Apple/Desktop/dev_folder/Dev_project/test.kiro2/leadership-summit-laravel/resources/views/components/footer.blade.php ENDPATH**/ ?>
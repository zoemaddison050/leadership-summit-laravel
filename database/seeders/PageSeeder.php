<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $pages = [
            [
                'title' => 'About Us',
                'slug' => 'about-us',
                'content' => 'Welcome to the Leadership Summit! We are dedicated to bringing together thought leaders, innovators, and change-makers from around the world to share insights, build connections, and drive positive change in their organizations and communities.

Our mission is to provide a platform for learning, networking, and collaboration that empowers leaders to make a meaningful impact in their respective fields.

Join us for an unforgettable experience filled with inspiring keynote speeches, interactive workshops, and valuable networking opportunities.',
                'meta_description' => 'Learn about the Leadership Summit mission, vision, and commitment to empowering leaders worldwide.',
                'status' => 'published',
            ],
            [
                'title' => 'Contact Information',
                'slug' => 'contact',
                'content' => 'Get in touch with us for any questions or inquiries about the Leadership Summit.

Email: info@leadershipsummit.com
Phone: +1 (555) 123-4567
Address: 123 Leadership Ave, Summit City, SC 12345

Business Hours:
Monday - Friday: 9:00 AM - 6:00 PM
Saturday: 10:00 AM - 4:00 PM
Sunday: Closed

For media inquiries, please contact our press team at press@leadershipsummit.com

For sponsorship opportunities, reach out to sponsors@leadershipsummit.com',
                'meta_description' => 'Contact the Leadership Summit team for inquiries, support, and partnership opportunities.',
                'status' => 'published',
            ],
            [
                'title' => 'Privacy Policy',
                'slug' => 'privacy-policy',
                'content' => 'This Privacy Policy describes how the Leadership Summit collects, uses, and protects your personal information.

Information We Collect:
- Personal information you provide when registering for events
- Payment information for ticket purchases
- Communication preferences and feedback

How We Use Your Information:
- To process event registrations and payments
- To communicate important event updates
- To improve our services and user experience
- To comply with legal obligations

Data Protection:
We implement appropriate security measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction.

Contact Us:
If you have questions about this Privacy Policy, please contact us at privacy@leadershipsummit.com',
                'meta_description' => 'Learn how the Leadership Summit protects your privacy and handles your personal information.',
                'status' => 'published',
            ],
            [
                'title' => 'Terms of Service',
                'slug' => 'terms-of-service',
                'content' => 'By using the Leadership Summit website and services, you agree to these Terms of Service.

Event Registration:
- All registrations are subject to availability
- Cancellation policies apply as stated during registration
- Refunds are processed according to our refund policy

User Conduct:
- Users must provide accurate information during registration
- Respectful behavior is expected at all events
- Violation of conduct policies may result in removal from events

Intellectual Property:
- Event content and materials are protected by copyright
- Unauthorized recording or distribution is prohibited
- Speakers retain rights to their presentations

Limitation of Liability:
The Leadership Summit is not liable for any indirect, incidental, or consequential damages arising from the use of our services.

For questions about these terms, contact legal@leadershipsummit.com',
                'meta_description' => 'Read the Leadership Summit Terms of Service and understand your rights and responsibilities.',
                'status' => 'draft',
            ],
            [
                'title' => 'FAQ',
                'slug' => 'frequently-asked-questions',
                'content' => 'Frequently Asked Questions about the Leadership Summit

Q: How do I register for an event?
A: Visit our events page, select the event you want to attend, and follow the registration process.

Q: What payment methods do you accept?
A: We accept major credit cards and cryptocurrency payments for your convenience.

Q: Can I get a refund if I cannot attend?
A: Refund policies vary by event. Please check the specific event details for cancellation and refund information.

Q: Are meals included with registration?
A: Most events include lunch and refreshments. Specific details are provided in each event description.

Q: Is there parking available?
A: Yes, complimentary parking is available at most venues. Specific parking information is provided with your registration confirmation.

Q: Can I transfer my registration to someone else?
A: Yes, registration transfers are allowed up to 48 hours before the event. Contact our support team for assistance.

Q: Will I receive a certificate of attendance?
A: Yes, digital certificates are provided to all attendees who complete the full event program.

For additional questions, please contact our support team.',
                'meta_description' => 'Find answers to common questions about Leadership Summit events, registration, and policies.',
                'status' => 'published',
            ],
        ];

        foreach ($pages as $pageData) {
            Page::create($pageData);
        }
    }
}

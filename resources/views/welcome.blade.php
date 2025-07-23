<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Leadership Summit') }}</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <style>
        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f8fafc;
        }

        .hero {
            background-color: #1a202c;
            color: white;
            padding: 6rem 1.5rem;
            text-align: center;
        }

        .hero h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .hero p {
            font-size: 1.25rem;
            max-width: 800px;
            margin: 0 auto;
        }

        .cta-button {
            display: inline-block;
            background-color: #4f46e5;
            color: white;
            font-weight: bold;
            padding: 0.75rem 1.5rem;
            border-radius: 0.375rem;
            text-decoration: none;
            margin-top: 2rem;
            transition: background-color 0.2s;
        }

        .cta-button:hover {
            background-color: #4338ca;
        }

        .features {
            padding: 4rem 1.5rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .feature-card {
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 2rem;
        }

        .feature-card h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: #1a202c;
        }

        .feature-card p {
            color: #4a5568;
        }

        .upcoming-events {
            background-color: #f1f5f9;
            padding: 4rem 1.5rem;
        }

        .upcoming-events h2 {
            text-align: center;
            margin-bottom: 3rem;
            font-size: 2.25rem;
            color: #1a202c;
        }

        .events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .event-card {
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .event-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .event-card-content {
            padding: 1.5rem;
        }

        .event-card h3 {
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
            color: #1a202c;
        }

        .event-date {
            color: #4f46e5;
            font-weight: bold;
            margin-bottom: 1rem;
            display: block;
        }

        .event-card p {
            color: #4a5568;
            margin-bottom: 1.5rem;
        }

        .event-link {
            color: #4f46e5;
            font-weight: bold;
            text-decoration: none;
        }

        .event-link:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <header>
        <nav class="bg-white shadow">
            <div style="max-width: 1200px; margin: 0 auto; padding: 1rem 1.5rem; display: flex; justify-content: space-between; align-items: center;">
                <a href="/" style="font-size: 1.5rem; font-weight: bold; color: #1a202c; text-decoration: none;">
                    Leadership Summit
                </a>
                <div>
                    <a href="/events" style="margin-right: 1.5rem; color: #4a5568; text-decoration: none;">Events</a>
                    <a href="/speakers" style="margin-right: 1.5rem; color: #4a5568; text-decoration: none;">Speakers</a>
                    <a href="/about" style="margin-right: 1.5rem; color: #4a5568; text-decoration: none;">About</a>
                    <a href="/contact" style="color: #4a5568; text-decoration: none;">Contact</a>
                </div>
            </div>
        </nav>
    </header>

    <section class="hero">
        <h1>Leadership Summit 2025</h1>
        <p>Join industry leaders and innovators for a transformative experience focused on leadership, growth, and future trends.</p>
        <a href="/events" class="cta-button">Explore Events</a>
    </section>

    <section class="features">
        <h2 style="text-align: center; margin-bottom: 1rem; font-size: 2.25rem; color: #1a202c;">Why Attend Our Summit</h2>
        <p style="text-align: center; max-width: 800px; margin: 0 auto; color: #4a5568;">Our leadership summit brings together the brightest minds and most influential leaders to share insights, strategies, and visions for the future.</p>

        <div class="features-grid">
            <div class="feature-card">
                <h3>Expert Speakers</h3>
                <p>Learn from industry leaders and innovators who are shaping the future of business and technology.</p>
            </div>
            <div class="feature-card">
                <h3>Networking Opportunities</h3>
                <p>Connect with peers, mentors, and potential collaborators in a dynamic and engaging environment.</p>
            </div>
            <div class="feature-card">
                <h3>Interactive Workshops</h3>
                <p>Participate in hands-on sessions designed to develop practical skills and actionable strategies.</p>
            </div>
        </div>
    </section>

    <section class="upcoming-events">
        <h2>Upcoming Events</h2>
        <div class="events-grid">
            <div class="event-card">
                <img src="https://via.placeholder.com/600x400" alt="Leadership Conference">
                <div class="event-card-content">
                    <span class="event-date">August 15-17, 2025</span>
                    <h3>Annual Leadership Conference</h3>
                    <p>Our flagship event featuring keynote speeches, panel discussions, and networking opportunities.</p>
                    <a href="/events/annual-leadership-conference" class="event-link">Learn More →</a>
                </div>
            </div>
            <div class="event-card">
                <img src="https://via.placeholder.com/600x400" alt="Executive Workshop">
                <div class="event-card-content">
                    <span class="event-date">September 5, 2025</span>
                    <h3>Executive Leadership Workshop</h3>
                    <p>An intensive one-day workshop focused on developing executive leadership skills.</p>
                    <a href="/events/executive-leadership-workshop" class="event-link">Learn More →</a>
                </div>
            </div>
            <div class="event-card">
                <img src="https://via.placeholder.com/600x400" alt="Tech Leadership Summit">
                <div class="event-card-content">
                    <span class="event-date">October 10-12, 2025</span>
                    <h3>Tech Leadership Summit</h3>
                    <p>Exploring the intersection of technology and leadership in the digital age.</p>
                    <a href="/events/tech-leadership-summit" class="event-link">Learn More →</a>
                </div>
            </div>
        </div>
    </section>

    <footer style="background-color: #1a202c; color: white; padding: 3rem 1.5rem; text-align: center;">
        <div style="max-width: 1200px; margin: 0 auto;">
            <p>&copy; 2025 Leadership Summit. All rights reserved.</p>
            <div style="margin-top: 1rem;">
                <a href="/privacy" style="color: white; margin-right: 1rem; text-decoration: none;">Privacy Policy</a>
                <a href="/terms" style="color: white; margin-right: 1rem; text-decoration: none;">Terms of Service</a>
                <a href="/contact" style="color: white; text-decoration: none;">Contact Us</a>
            </div>
        </div>
    </footer>
</body>

</html>
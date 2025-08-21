@extends('layouts.app')

@section('title', 'Agenda - Leadership Academy Summit')

@section('content')
<!-- Hero Section -->
<div class="agenda-hero bg-gradient-primary text-white py-5 mb-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-10 mx-auto text-center">
                <h1 class="display-3 fw-bold mb-4">Summit Agenda</h1>
                <p class="lead fs-4 mb-4">Two days of transformative leadership insights, networking, and growth opportunities</p>
                <div class="agenda-stats d-flex justify-content-center gap-4 flex-wrap mt-4">
                    <div class="stat-item text-center">
                        <div class="stat-number display-6 fw-bold">2</div>
                        <div class="stat-label">Days</div>
                    </div>
                    <div class="stat-item text-center">
                        <div class="stat-number display-6 fw-bold">12+</div>
                        <div class="stat-label">Sessions</div>
                    </div>
                    <div class="stat-item text-center">
                        <div class="stat-number display-6 fw-bold">8</div>
                        <div class="stat-label">Expert Speakers</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container py-5">
    <div class="row">
        <div class="col-12">

            <!-- Day Navigation -->
            <div class="text-center mb-5">
                <div class="btn-group" role="group" aria-label="Day navigation">
                    <button type="button" class="btn btn-primary btn-lg active" onclick="showDay('day1', this)">
                        <i class="fas fa-calendar-day me-2"></i>Day 1 - September 15
                    </button>
                    <button type="button" class="btn btn-outline-primary btn-lg" onclick="showDay('day2', this)">
                        <i class="fas fa-calendar-day me-2"></i>Day 2 - September 16
                    </button>
                </div>
            </div>

            <!-- Day 1 Schedule -->
            <div id="day1" class="agenda-day">
                <div class="row mb-5">
                    <div class="col-12">
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-primary text-white">
                                <h2 class="h4 mb-0"><i class="fas fa-calendar-alt me-2"></i>Day 1 - September 15, 2025</h2>
                            </div>
                            <div class="card-body">
                                <div class="timeline">
                                    <div class="timeline-item">
                                        <div class="timeline-time">8:00 AM</div>
                                        <div class="timeline-content">
                                            <div class="d-flex align-items-center mb-2">
                                                <h5 class="mb-0 me-3">Registration & Welcome Coffee</h5>
                                                <span class="badge bg-success">Registration</span>
                                            </div>
                                            <p class="text-muted mb-0">Network with fellow attendees and enjoy refreshments</p>
                                        </div>
                                    </div>

                                    <div class="timeline-item">
                                        <div class="timeline-time">9:00 AM</div>
                                        <div class="timeline-content">
                                            <div class="d-flex align-items-center mb-2">
                                                <h5 class="mb-0 me-3">Opening Keynote: The Future of Leadership</h5>
                                                <span class="badge bg-danger">Keynote</span>
                                            </div>
                                            <p class="text-muted mb-1">Speaker: Dr. Sarah Johnson</p>
                                            <p class="mb-0">Exploring emerging trends and challenges in modern leadership</p>
                                        </div>
                                    </div>

                                    <div class="timeline-item">
                                        <div class="timeline-time">10:30 AM</div>
                                        <div class="timeline-content">
                                            <div class="d-flex align-items-center mb-2">
                                                <h5 class="mb-0 me-3">Coffee Break & Networking</h5>
                                                <span class="badge bg-warning text-dark">Break</span>
                                            </div>
                                            <p class="text-muted mb-0">15-minute refreshment break</p>
                                        </div>
                                    </div>

                                    <div class="timeline-item">
                                        <div class="timeline-time">10:45 AM</div>
                                        <div class="timeline-content">
                                            <div class="d-flex align-items-center mb-2">
                                                <h5 class="mb-0 me-3">Panel Discussion: Digital Transformation in Leadership</h5>
                                                <span class="badge bg-info">Panel</span>
                                            </div>
                                            <p class="text-muted mb-1">Moderator: Michael Chen</p>
                                            <p class="mb-0">How technology is reshaping leadership practices</p>
                                        </div>
                                    </div>

                                    <div class="timeline-item">
                                        <div class="timeline-time">12:00 PM</div>
                                        <div class="timeline-content">
                                            <div class="d-flex align-items-center mb-2">
                                                <h5 class="mb-0 me-3">Lunch & Networking</h5>
                                                <span class="badge bg-secondary">Lunch</span>
                                            </div>
                                            <p class="text-muted mb-0">Catered lunch with networking opportunities</p>
                                        </div>
                                    </div>

                                    <div class="timeline-item">
                                        <div class="timeline-time">1:30 PM</div>
                                        <div class="timeline-content">
                                            <div class="d-flex align-items-center mb-2">
                                                <h5 class="mb-0 me-3">Workshop: Building High-Performance Teams</h5>
                                                <span class="badge bg-primary">Workshop</span>
                                            </div>
                                            <p class="text-muted mb-1">Facilitator: Lisa Rodriguez</p>
                                            <p class="mb-0">Interactive session on team dynamics and performance optimization</p>
                                        </div>
                                    </div>

                                    <div class="timeline-item">
                                        <div class="timeline-time">3:00 PM</div>
                                        <div class="timeline-content">
                                            <div class="d-flex align-items-center mb-2">
                                                <h5 class="mb-0 me-3">Afternoon Break</h5>
                                                <span class="badge bg-warning text-dark">Break</span>
                                            </div>
                                            <p class="text-muted mb-0">15-minute refreshment break</p>
                                        </div>
                                    </div>

                                    <div class="timeline-item">
                                        <div class="timeline-time">3:15 PM</div>
                                        <div class="timeline-content">
                                            <div class="d-flex align-items-center mb-2">
                                                <h5 class="mb-0 me-3">Fireside Chat: Leadership in Crisis</h5>
                                                <span class="badge bg-dark">Fireside Chat</span>
                                            </div>
                                            <p class="text-muted mb-1">Guest: Former CEO James Wilson</p>
                                            <p class="mb-0">Lessons learned from leading through challenging times</p>
                                        </div>
                                    </div>

                                    <div class="timeline-item">
                                        <div class="timeline-time">4:30 PM</div>
                                        <div class="timeline-content">
                                            <div class="d-flex align-items-center mb-2">
                                                <h5 class="mb-0 me-3">Day 1 Wrap-up & Networking Reception</h5>
                                                <span class="badge bg-success">Networking</span>
                                            </div>
                                            <p class="text-muted mb-0">Cocktails and hors d'oeuvres</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Day 2 Schedule -->
            <div id="day2" class="agenda-day" style="display: none;">
                <div class="row mb-5">
                    <div class="col-12">
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-success text-white">
                                <h2 class="h4 mb-0"><i class="fas fa-calendar-alt me-2"></i>Day 2 - September 16, 2025</h2>
                            </div>
                            <div class="card-body">
                                <div class="timeline">
                                    <div class="timeline-item">
                                        <div class="timeline-time">8:30 AM</div>
                                        <div class="timeline-content">
                                            <div class="d-flex align-items-center mb-2">
                                                <h5 class="mb-0 me-3">Morning Coffee & Networking</h5>
                                                <span class="badge bg-success">Networking</span>
                                            </div>
                                            <p class="text-muted mb-0">Start your day with fellow leaders</p>
                                        </div>
                                    </div>

                                    <div class="timeline-item">
                                        <div class="timeline-time">9:00 AM</div>
                                        <div class="timeline-content">
                                            <div class="d-flex align-items-center mb-2">
                                                <h5 class="mb-0 me-3">Keynote: Sustainable Leadership for the Future</h5>
                                                <span class="badge bg-danger">Keynote</span>
                                            </div>
                                            <p class="text-muted mb-1">Speaker: Dr. Maria Santos</p>
                                            <p class="mb-0">Building organizations that thrive in the long term</p>
                                        </div>
                                    </div>

                                    <div class="timeline-item">
                                        <div class="timeline-time">10:30 AM</div>
                                        <div class="timeline-content">
                                            <div class="d-flex align-items-center mb-2">
                                                <h5 class="mb-0 me-3">Breakout Sessions (Choose One)</h5>
                                                <span class="badge bg-primary">Workshop</span>
                                            </div>
                                            <div class="row mt-3">
                                                <div class="col-md-6">
                                                    <div class="card border-primary">
                                                        <div class="card-body">
                                                            <h6 class="card-title">Track A: Innovation Leadership</h6>
                                                            <p class="card-text small">Fostering creativity and innovation in your organization</p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="card border-success">
                                                        <div class="card-body">
                                                            <h6 class="card-title">Track B: Emotional Intelligence</h6>
                                                            <p class="card-text small">Developing EQ for better leadership outcomes</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="timeline-item">
                                        <div class="timeline-time">12:00 PM</div>
                                        <div class="timeline-content">
                                            <div class="d-flex align-items-center mb-2">
                                                <h5 class="mb-0 me-3">Networking Lunch</h5>
                                                <span class="badge bg-secondary">Lunch</span>
                                            </div>
                                            <p class="text-muted mb-0">Continue conversations over lunch</p>
                                        </div>
                                    </div>

                                    <div class="timeline-item">
                                        <div class="timeline-time">1:30 PM</div>
                                        <div class="timeline-content">
                                            <div class="d-flex align-items-center mb-2">
                                                <h5 class="mb-0 me-3">Case Study: Successful Leadership Transformations</h5>
                                                <span class="badge bg-info">Case Study</span>
                                            </div>
                                            <p class="text-muted mb-1">Presenter: Alex Thompson</p>
                                            <p class="mb-0">Real-world examples of leadership transformation success stories</p>
                                        </div>
                                    </div>

                                    <div class="timeline-item">
                                        <div class="timeline-time">2:45 PM</div>
                                        <div class="timeline-content">
                                            <div class="d-flex align-items-center mb-2">
                                                <h5 class="mb-0 me-3">Interactive Workshop: Leadership Communication</h5>
                                                <span class="badge bg-primary">Workshop</span>
                                            </div>
                                            <p class="text-muted mb-1">Facilitator: Dr. Jennifer Lee</p>
                                            <p class="mb-0">Mastering the art of effective leadership communication</p>
                                        </div>
                                    </div>

                                    <div class="timeline-item">
                                        <div class="timeline-time">4:00 PM</div>
                                        <div class="timeline-content">
                                            <div class="d-flex align-items-center mb-2">
                                                <h5 class="mb-0 me-3">Closing Keynote: Your Leadership Journey Forward</h5>
                                                <span class="badge bg-danger">Keynote</span>
                                            </div>
                                            <p class="text-muted mb-1">Speaker: Robert Davis</p>
                                            <p class="mb-0">Taking action on insights gained during the summit</p>
                                        </div>
                                    </div>

                                    <div class="timeline-item">
                                        <div class="timeline-time">5:00 PM</div>
                                        <div class="timeline-content">
                                            <div class="d-flex align-items-center mb-2">
                                                <h5 class="mb-0 me-3">Summit Conclusion & Certificate Ceremony</h5>
                                                <span class="badge bg-warning text-dark">Ceremony</span>
                                            </div>
                                            <p class="text-muted mb-0">Closing remarks and networking farewell</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Call to Action -->
            <div class="row">
                <div class="col-12">
                    <div class="card bg-primary text-white text-center">
                        <div class="card-body py-5">
                            <h2 class="card-title display-5 mb-4">Ready to Transform Your Leadership?</h2>
                            <p class="card-text lead mb-4">Join us for this incredible journey of growth and discovery</p>
                            <a href="{{ route('tickets.selection', 'leadership-summit-2025') }}" class="btn btn-light btn-lg">
                                <i class="fas fa-ticket-alt me-2"></i>Register Now
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .bg-gradient-primary {
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    }

    .agenda-stats .stat-item {
        min-width: 100px;
    }

    .timeline {
        position: relative;
        padding-left: 30px;
    }

    .timeline::before {
        content: '';
        position: absolute;
        left: 15px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #007bff;
    }

    .timeline-item {
        position: relative;
        margin-bottom: 30px;
        padding-left: 40px;
    }

    .timeline-item::before {
        content: '';
        position: absolute;
        left: -8px;
        top: 5px;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        background: #007bff;
        border: 3px solid #fff;
        box-shadow: 0 0 0 3px #007bff;
    }

    .timeline-time {
        font-weight: bold;
        color: #007bff;
        font-size: 0.9rem;
        margin-bottom: 5px;
    }

    .timeline-content h5 {
        margin-bottom: 10px;
        color: #333;
    }

    .timeline-content p {
        margin-bottom: 5px;
    }

    .card {
        border: none;
        border-radius: 15px;
        overflow: hidden;
    }

    .card-header {
        border-radius: 15px 15px 0 0 !important;
    }

    .btn-group .btn {
        border-radius: 25px;
    }

    .btn-group .btn:first-child {
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
    }

    .btn-group .btn:last-child {
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
    }

    .agenda-day {
        animation: fadeIn 0.5s ease-in-out;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>

<script>
    function showDay(dayId, button) {
        // Hide all days
        document.querySelectorAll('.agenda-day').forEach(day => {
            day.style.display = 'none';
        });

        // Show selected day
        document.getElementById(dayId).style.display = 'block';

        // Update button styles
        document.querySelectorAll('.btn-group .btn').forEach(btn => {
            btn.classList.remove('btn-primary', 'active');
            btn.classList.add('btn-outline-primary');
        });

        // Highlight active button
        button.classList.remove('btn-outline-primary');
        button.classList.add('btn-primary', 'active');
    }
</script>
@endsection
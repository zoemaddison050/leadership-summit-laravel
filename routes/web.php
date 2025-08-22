<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('home');
})->name('home');

// Admin-only authentication routes (no public registration)
Route::get('login', [App\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])->name('login');
Route::post('login', [App\Http\Controllers\Auth\LoginController::class, 'login']);
Route::post('logout', [App\Http\Controllers\Auth\LoginController::class, 'logout'])->name('logout');

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('user.home');

Route::get('/dashboard', function () {
    $user = auth()->user();

    // Calculate dashboard statistics
    $dashboardStats = [
        'user_registrations' => $user->registrations()->count(),
        'user_orders' => $user->orders()->count(),
        'upcoming_events' => \App\Models\Event::where('start_date', '>=', now())->count(),
        'member_since_days' => $user->created_at->diffInDays(now()),
        'completion_rate' => 85, // Placeholder - could be calculated based on profile completeness
    ];

    // Get user's event registrations
    $userEvents = $user->registrations()->with(['event', 'ticket'])->latest()->take(5)->get();

    // Get upcoming events
    $upcomingEvents = \App\Models\Event::where('start_date', '>=', now())
        ->orderBy('start_date')
        ->take(6)
        ->get();

    // Get featured speakers
    $featuredSpeakers = \App\Models\Speaker::inRandomOrder()->take(4)->get();

    // Generate recent activity
    $recentActivity = [];

    // Add recent registrations as activity
    foreach ($user->registrations()->with('event')->latest()->take(3)->get() as $registration) {
        $recentActivity[] = [
            'title' => 'Registered for ' . $registration->event->title,
            'description' => 'You registered for this event',
            'date' => $registration->created_at,
            'icon' => 'fas fa-ticket-alt'
        ];
    }

    // Add recent orders as activity
    foreach ($user->orders()->latest()->take(2)->get() as $order) {
        $recentActivity[] = [
            'title' => 'Order #' . $order->order_number . ' completed',
            'description' => 'Your order has been processed',
            'date' => $order->created_at,
            'icon' => 'fas fa-shopping-cart'
        ];
    }

    // Sort activity by date
    usort($recentActivity, function ($a, $b) {
        return $b['date'] <=> $a['date'];
    });

    // Take only the 5 most recent activities
    $recentActivity = array_slice($recentActivity, 0, 5);

    // Define quick actions
    $quickActions = [
        [
            'title' => 'Browse Events',
            'description' => 'Discover upcoming leadership events',
            'url' => route('events.index'),
            'icon' => 'fas fa-calendar-alt'
        ],
        [
            'title' => 'My Registrations',
            'description' => 'View your event registrations',
            'url' => route('registrations.index'),
            'icon' => 'fas fa-ticket-alt'
        ],
        [
            'title' => 'My Orders',
            'description' => 'Check your purchase history',
            'url' => route('orders.index'),
            'icon' => 'fas fa-shopping-cart'
        ],
        [
            'title' => 'Edit Profile',
            'description' => 'Update your account information',
            'url' => route('profile.edit'),
            'icon' => 'fas fa-user-edit'
        ],
        [
            'title' => 'View Speakers',
            'description' => 'Meet our expert speakers',
            'url' => route('speakers.index'),
            'icon' => 'fas fa-users'
        ],
        [
            'title' => 'Contact Support',
            'description' => 'Get help and assistance',
            'url' => route('contact'),
            'icon' => 'fas fa-headset'
        ]
    ];

    return view('dashboard', compact('user', 'dashboardStats', 'quickActions', 'userEvents', 'upcomingEvents', 'featuredSpeakers', 'recentActivity'));
})->middleware(['auth'])->name('dashboard');

// Admin routes
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', function () {
        // Calculate real dashboard statistics
        $stats = [
            'total_events' => \App\Models\Event::count(),
            'total_registrations' => \App\Models\Registration::count(),
            'total_speakers' => \App\Models\Speaker::count(),
            'total_revenue' => \App\Models\Registration::where('payment_status', 'completed')->sum('total_amount'),
        ];

        // Calculate growth statistics
        $currentMonth = now()->startOfMonth();
        $lastMonth = now()->subMonth()->startOfMonth();
        $currentWeek = now()->startOfWeek();
        $lastWeek = now()->subWeek()->startOfWeek();

        $eventsThisMonth = \App\Models\Event::where('created_at', '>=', $currentMonth)->count();
        $eventsLastMonth = \App\Models\Event::whereBetween('created_at', [$lastMonth, $currentMonth])->count();

        $registrationsThisWeek = \App\Models\Registration::where('created_at', '>=', $currentWeek)->count();
        $registrationsLastWeek = \App\Models\Registration::whereBetween('created_at', [$lastWeek, $currentWeek])->count();

        $revenueThisMonth = \App\Models\Registration::where('payment_status', 'completed')
            ->where('created_at', '>=', $currentMonth)->sum('total_amount');
        $revenueLastMonth = \App\Models\Registration::where('payment_status', 'completed')
            ->whereBetween('created_at', [$lastMonth, $currentMonth])->sum('total_amount');

        $growth = [
            'events_this_month' => $eventsThisMonth,
            'events_growth' => $eventsLastMonth > 0 ? round((($eventsThisMonth - $eventsLastMonth) / $eventsLastMonth) * 100) : 0,
            'registrations_this_week' => $registrationsThisWeek,
            'registrations_growth' => $registrationsLastWeek > 0 ? round((($registrationsThisWeek - $registrationsLastWeek) / $registrationsLastWeek) * 100) : 0,
            'revenue_growth' => $revenueLastMonth > 0 ? round((($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth) * 100) : 0,
        ];

        // Get recent registrations
        $recentRegistrations = \App\Models\Registration::with('event')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Get upcoming events
        $upcomingEvents = \App\Models\Event::withCount('registrations')
            ->where('start_date', '>=', now())
            ->orderBy('start_date', 'asc')
            ->limit(3)
            ->get();

        return view('admin.dashboard', compact('stats', 'growth', 'recentRegistrations', 'upcomingEvents'));
    })->name('dashboard');

    // Event Management
    Route::resource('events', App\Http\Controllers\Admin\EventController::class);
    Route::post('/events/{event}/set-default', [App\Http\Controllers\Admin\EventController::class, 'setDefault'])->name('events.set-default');

    // Speaker Management
    Route::resource('speakers', App\Http\Controllers\Admin\SpeakerController::class);
    Route::post('/speakers/bulk-action', [App\Http\Controllers\Admin\SpeakerController::class, 'bulkAction'])->name('speakers.bulk-action');

    // Wallet Settings Management
    Route::resource('wallet-settings', App\Http\Controllers\Admin\WalletSettingController::class);
    Route::post('/wallet-settings/{walletSetting}/toggle', [App\Http\Controllers\Admin\WalletSettingController::class, 'toggleActive'])->name('wallet-settings.toggle');

    // Payment Management Routes
    Route::get('/payments/pending', [App\Http\Controllers\PaymentController::class, 'adminPendingPayments'])->name('payments.pending');
    Route::post('/payments/{registration}/approve', [App\Http\Controllers\PaymentController::class, 'approvePayment'])->name('payments.approve');
    Route::post('/payments/{registration}/decline', [App\Http\Controllers\PaymentController::class, 'declinePayment'])->name('payments.decline');

    // Registration Management
    Route::get('/registrations', [App\Http\Controllers\Admin\RegistrationController::class, 'index'])->name('registrations.index');
    Route::get('/registrations/{registration}', [App\Http\Controllers\Admin\RegistrationController::class, 'show'])->name('registrations.show');
    Route::patch('/registrations/{registration}/status', [App\Http\Controllers\Admin\RegistrationController::class, 'updateStatus'])->name('registrations.updateStatus');
    Route::delete('/registrations/{registration}', [App\Http\Controllers\Admin\RegistrationController::class, 'destroy'])->name('registrations.destroy');

    // Ticket Management
    Route::get('/tickets', [App\Http\Controllers\Admin\TicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/create', [App\Http\Controllers\Admin\TicketController::class, 'create'])->name('tickets.create');
    Route::post('/tickets', [App\Http\Controllers\Admin\TicketController::class, 'store'])->name('tickets.store');
    Route::get('/tickets/{ticket}', [App\Http\Controllers\Admin\TicketController::class, 'show'])->name('tickets.show');
    Route::get('/tickets/{ticket}/edit', [App\Http\Controllers\Admin\TicketController::class, 'edit'])->name('tickets.edit');
    Route::patch('/tickets/{ticket}', [App\Http\Controllers\Admin\TicketController::class, 'update'])->name('tickets.update');
    Route::delete('/tickets/{ticket}', [App\Http\Controllers\Admin\TicketController::class, 'destroy'])->name('tickets.destroy');

    // User Management
    Route::get('/users', [App\Http\Controllers\Admin\UserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [App\Http\Controllers\Admin\UserController::class, 'create'])->name('users.create');
    Route::post('/users', [App\Http\Controllers\Admin\UserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}', [App\Http\Controllers\Admin\UserController::class, 'show'])->name('users.show');
    Route::get('/users/{user}/edit', [App\Http\Controllers\Admin\UserController::class, 'edit'])->name('users.edit');
    Route::patch('/users/{user}', [App\Http\Controllers\Admin\UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [App\Http\Controllers\Admin\UserController::class, 'destroy'])->name('users.destroy');

    // Role Management
    Route::get('/roles', [App\Http\Controllers\Admin\RoleController::class, 'index'])->name('roles.index');
    Route::get('/roles/create', [App\Http\Controllers\Admin\RoleController::class, 'create'])->name('roles.create');
    Route::post('/roles', [App\Http\Controllers\Admin\RoleController::class, 'store'])->name('roles.store');
    Route::get('/roles/{role}', [App\Http\Controllers\Admin\RoleController::class, 'show'])->name('roles.show');
    Route::get('/roles/{role}/edit', [App\Http\Controllers\Admin\RoleController::class, 'edit'])->name('roles.edit');
    Route::patch('/roles/{role}', [App\Http\Controllers\Admin\RoleController::class, 'update'])->name('roles.update');
    Route::delete('/roles/{role}', [App\Http\Controllers\Admin\RoleController::class, 'destroy'])->name('roles.destroy');

    // Settings Management
    Route::get('/settings', [App\Http\Controllers\Admin\SettingsController::class, 'index'])->name('settings.index');
    Route::patch('/settings', [App\Http\Controllers\Admin\SettingsController::class, 'update'])->name('settings.update');

    // Reports
    Route::get('/reports', [App\Http\Controllers\Admin\ReportsController::class, 'index'])->name('reports.index');
    Route::get('/reports/registrations', [App\Http\Controllers\Admin\ReportsController::class, 'registrations'])->name('reports.registrations');
    Route::get('/reports/payments', [App\Http\Controllers\Admin\ReportsController::class, 'payments'])->name('reports.payments');
    Route::get('/reports/events', [App\Http\Controllers\Admin\ReportsController::class, 'events'])->name('reports.events');

    // Page Management
    Route::resource('pages', App\Http\Controllers\Admin\PageController::class);
    Route::post('/pages/bulk-action', [App\Http\Controllers\Admin\PageController::class, 'bulkAction'])->name('pages.bulk-action');

    // Media Management
    Route::resource('media', App\Http\Controllers\Admin\MediaController::class);
    Route::post('/media/bulk-action', [App\Http\Controllers\Admin\MediaController::class, 'bulkAction'])->name('media.bulk-action');
    Route::post('/media/upload', [App\Http\Controllers\Admin\MediaController::class, 'upload'])->name('media.upload');

    // Session Management
    Route::resource('sessions', App\Http\Controllers\Admin\SessionController::class);
    Route::post('/sessions/bulk-action', [App\Http\Controllers\Admin\SessionController::class, 'bulkAction'])->name('sessions.bulk-action');

    // UniPayment Settings Management - With CSRF Protection
    Route::get('/unipayment', [App\Http\Controllers\Admin\UniPaymentController::class, 'index'])->name('unipayment.index');
    Route::patch('/unipayment', [App\Http\Controllers\Admin\UniPaymentController::class, 'update'])
        ->middleware(['throttle:10,1'])
        ->name('unipayment.update');
    Route::post('/unipayment/test-connection', [App\Http\Controllers\Admin\UniPaymentController::class, 'testConnection'])
        ->middleware(['throttle:5,1'])
        ->name('unipayment.test-connection');
    Route::get('/unipayment/connection-status', [App\Http\Controllers\Admin\UniPaymentController::class, 'connectionStatus'])
        ->middleware(['throttle:20,1'])
        ->name('unipayment.connection-status');
    Route::get('/unipayment/transactions', [App\Http\Controllers\Admin\UniPaymentController::class, 'transactions'])->name('unipayment.transactions');

    // Webhook Management Routes
    Route::post('/unipayment/test-webhook', [App\Http\Controllers\Admin\UniPaymentController::class, 'testWebhook'])
        ->middleware(['throttle:5,1'])
        ->name('unipayment.test-webhook');
    Route::get('/unipayment/webhook-status', [App\Http\Controllers\Admin\UniPaymentController::class, 'webhookStatus'])
        ->middleware(['throttle:20,1'])
        ->name('unipayment.webhook-status');
    Route::get('/unipayment/generate-webhook-url', [App\Http\Controllers\Admin\UniPaymentController::class, 'generateWebhookUrl'])
        ->middleware(['throttle:10,1'])
        ->name('unipayment.generate-webhook-url');
    Route::post('/unipayment/validate-webhook-url', [App\Http\Controllers\Admin\UniPaymentController::class, 'validateWebhookUrl'])
        ->middleware(['throttle:10,1'])
        ->name('unipayment.validate-webhook-url');

    // Webhook Testing and Monitoring Routes
    Route::get('/unipayment/webhook-diagnostics', [App\Http\Controllers\Admin\UniPaymentController::class, 'webhookDiagnostics'])
        ->middleware(['throttle:5,1'])
        ->name('unipayment.webhook-diagnostics');
    Route::post('/unipayment/test-webhook-payload', [App\Http\Controllers\Admin\UniPaymentController::class, 'testWebhookPayload'])
        ->middleware(['throttle:5,1'])
        ->name('unipayment.test-webhook-payload');
    Route::post('/unipayment/test-webhook-accessibility', [App\Http\Controllers\Admin\UniPaymentController::class, 'testWebhookAccessibility'])
        ->middleware(['throttle:10,1'])
        ->name('unipayment.test-webhook-accessibility');
    Route::get('/unipayment/webhook-metrics', [App\Http\Controllers\Admin\UniPaymentController::class, 'webhookMetrics'])
        ->middleware(['throttle:20,1'])
        ->name('unipayment.webhook-metrics');
    Route::get('/unipayment/webhook-health', [App\Http\Controllers\Admin\UniPaymentController::class, 'webhookHealth'])
        ->middleware(['throttle:30,1'])
        ->name('unipayment.webhook-health');
    Route::get('/unipayment/webhook-trends', [App\Http\Controllers\Admin\UniPaymentController::class, 'webhookTrends'])
        ->middleware(['throttle:10,1'])
        ->name('unipayment.webhook-trends');
    Route::post('/unipayment/reset-webhook-counters', [App\Http\Controllers\Admin\UniPaymentController::class, 'resetWebhookCounters'])
        ->middleware(['throttle:2,1'])
        ->name('unipayment.reset-webhook-counters');
    Route::post('/unipayment/clear-webhook-test-cache', [App\Http\Controllers\Admin\UniPaymentController::class, 'clearWebhookTestCache'])
        ->middleware(['throttle:5,1'])
        ->name('unipayment.clear-webhook-test-cache');
});

// Speaker routes
Route::middleware(['auth', 'role:speaker'])->group(function () {
    Route::get('/speaker', function () {
        return 'Speaker Dashboard - Only speakers can see this';
    })->name('speaker.dashboard');

    Route::get('/speaker/sessions', function () {
        return 'Session Management - Only speakers can see this';
    })->name('speaker.sessions');
});

// Additional Admin Routes (merged into main admin group above)
// Note: These routes have been consolidated into the main admin route group

// These routes have been moved to the main admin route group above

// Public Events Routes
Route::get('events', [App\Http\Controllers\EventController::class, 'publicIndex'])->name('events.index');
Route::get('events/calendar', [App\Http\Controllers\EventController::class, 'calendar'])->name('events.calendar');
Route::get('events/{slug}', [App\Http\Controllers\EventController::class, 'publicShow'])->name('events.show');

// Direct Registration Routes (No Authentication Required) - With Error Handling and Session Management
Route::middleware(['registration.errors', 'payment.session_timeout'])->group(function () {
    Route::get('events/{event}/register', [App\Http\Controllers\RegistrationController::class, 'showDirectForm'])->name('events.register');
    Route::post('events/{event}/register', [App\Http\Controllers\RegistrationController::class, 'processDirectRegistration'])->name('events.register.process');

    // Direct Registration Payment Routes (No Authentication Required) - With Security Middleware and CSRF Protection
    Route::get('events/{event}/payment/selection', [App\Http\Controllers\PaymentController::class, 'showPaymentSelection'])->name('payment.selection');
    Route::post('events/{event}/payment/card', [App\Http\Controllers\PaymentController::class, 'processCardPayment'])
        ->middleware(['payment.security'])
        ->name('payment.card');
    Route::get('events/{event}/payment/crypto', [App\Http\Controllers\PaymentController::class, 'showCryptoPayment'])->name('payment.crypto');
    Route::post('events/{event}/payment/crypto/details', [App\Http\Controllers\PaymentController::class, 'getCryptoPaymentDetails'])
        ->middleware(['payment.security', 'payment.rate_limit:10,5'])
        ->name('payment.crypto.details');
    Route::post('events/{event}/payment/confirm', [App\Http\Controllers\PaymentController::class, 'confirmPayment'])
        ->middleware(['payment.security', 'payment.rate_limit:10,5'])
        ->name('payment.confirm');
    Route::post('events/{event}/payment/process', [App\Http\Controllers\PaymentController::class, 'processPaymentConfirmation'])
        ->middleware(['payment.security', 'payment.rate_limit:10,5'])
        ->name('payment.process');

    // UniPayment Callback and Webhook Routes (No Authentication Required) - With Rate Limiting and Security
    Route::get('payment/unipayment/callback', [App\Http\Controllers\PaymentController::class, 'handleUniPaymentCallback'])
        ->middleware(['payment.rate_limit:10,5'])
        ->name('payment.unipayment.callback');
    Route::post('payment/unipayment/webhook', [App\Http\Controllers\PaymentController::class, 'handleUniPaymentWebhook'])
        ->middleware(['webhook.auth', 'payment.rate_limit:20,1'])
        ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class])
        ->name('payment.unipayment.webhook');

    // Payment Method Switching and Recovery Routes (No Authentication Required) - With Security and Rate Limiting
    Route::post('events/{event}/payment/switch', [App\Http\Controllers\PaymentController::class, 'switchPaymentMethod'])
        ->middleware(['payment.security', 'payment.rate_limit:5,10'])
        ->name('payment.switch');

    // Temporary test route to set up registration data (REMOVE IN PRODUCTION)
    Route::get('test/setup-registration/{event}', function (App\Models\Event $event) {
        $ticket = $event->tickets()->first();
        if (!$ticket) {
            return 'No tickets found for this event';
        }

        $registrationData = [
            'event_id' => $event->id,
            'attendee_name' => 'michael jo',
            'attendee_email' => 'jomichael@gmail.com',
            'attendee_phone' => '7688798996',
            'emergency_contact_name' => 'Emergency Contact',
            'emergency_contact_phone' => '1234567890',
            'ticket_selections' => [
                [
                    'ticket_id' => $ticket->id,
                    'ticket_name' => $ticket->name,
                    'quantity' => 1,
                    'price' => $ticket->price,
                    'subtotal' => $ticket->price
                ]
            ],
            'total_amount' => $ticket->price,
            'terms_accepted_at' => now(),
            'created_at' => now(),
            'expires_at' => now()->addMinutes(30)
        ];

        session(['registration_data' => $registrationData]);

        return redirect()->route('payment.selection', $event)
            ->with('success', 'Test registration data set up successfully!');
    })->name('test.setup-registration');
    Route::get('events/{event}/payment/availability', [App\Http\Controllers\PaymentController::class, 'checkPaymentAvailability'])
        ->middleware(['payment.rate_limit:10,5'])
        ->name('payment.availability');
    Route::get('events/{event}/payment/failed', [App\Http\Controllers\PaymentController::class, 'handlePaymentFailure'])
        ->name('payment.failed');
    Route::post('events/{event}/payment/retry', [App\Http\Controllers\PaymentController::class, 'retryPayment'])
        ->middleware(['payment.security', 'payment.rate_limit:3,15'])
        ->name('payment.retry');
    Route::get('events/{event}/payment/crypto/failed', [App\Http\Controllers\PaymentController::class, 'handleCryptoPaymentFailure'])
        ->name('payment.crypto.failed');

    // Demo UniPayment Checkout Route (for testing with fake credentials)
    Route::get('demo/unipayment/checkout/{invoiceId}', [App\Http\Controllers\PaymentController::class, 'showDemoCheckout'])
        ->name('demo.unipayment.checkout');
    Route::post('demo/unipayment/checkout/{invoiceId}/complete', [App\Http\Controllers\PaymentController::class, 'completeDemoCheckout'])
        ->name('demo.unipayment.complete');

    // Payment processing status page
    Route::get('events/{event}/payment/processing', [App\Http\Controllers\PaymentController::class, 'showCardProcessing'])
        ->name('payment.card.processing');
});

Route::get('payment/processing', [App\Http\Controllers\PaymentController::class, 'showProcessing'])->name('payment.processing');
Route::get('registration/{registration}/success', [App\Http\Controllers\RegistrationController::class, 'success'])->name('registration.success');

// Ticket selection (no authentication required - redirects to direct registration)
Route::get('events/{event}/tickets', function ($event) {
    return redirect()->route('events.register', $event);
})->name('tickets.selection');

// Public Speakers Routes
Route::get('speakers', [App\Http\Controllers\SpeakerController::class, 'publicIndex'])->name('speakers.index');
Route::get('speakers/{speaker}', [App\Http\Controllers\SpeakerController::class, 'publicShow'])->name('speakers.show');

// Public Sessions Routes
Route::get('sessions', [App\Http\Controllers\SessionController::class, 'publicIndex'])->name('sessions.index');
Route::get('sessions/{session}', [App\Http\Controllers\SessionController::class, 'publicShow'])->name('sessions.show');

// Legacy Registration Routes - Redirect to Direct Registration
Route::get('events/{event}/register-auth', function ($event) {
    return redirect()->route('events.register', $event);
})->name('registrations.create');

Route::post('events/{event}/register-auth', function ($event) {
    return redirect()->route('events.register', $event);
})->name('registrations.store');

Route::post('events/{event}/registration/process', function ($event) {
    return redirect()->route('events.register', $event);
})->name('registration.process');

// Registration Management Routes (Authenticated users only)
Route::middleware(['auth'])->group(function () {
    Route::get('registrations', [App\Http\Controllers\RegistrationController::class, 'index'])->name('registrations.index');
    Route::get('registrations/{registration}', [App\Http\Controllers\RegistrationController::class, 'show'])->name('registrations.show');
    Route::get('registrations/{registration}/confirmation', [App\Http\Controllers\RegistrationController::class, 'confirmation'])->name('registrations.confirmation');

    Route::patch('registrations/{registration}/cancel', [App\Http\Controllers\RegistrationController::class, 'cancel'])->name('registrations.cancel');

    // Profile Routes
    Route::get('profile', [App\Http\Controllers\ProfileController::class, 'show'])->name('profile.show');
    Route::get('profile/edit', [App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile', [App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    Route::put('profile/password', [App\Http\Controllers\ProfileController::class, 'updatePassword'])->name('profile.password');

    // Crypto Payment Routes (Card payments removed) - For authenticated users only
    Route::post('events/{event}/payment/crypto/init', [App\Http\Controllers\PaymentController::class, 'initializeCrypto'])->name('payment.crypto.process');
    Route::get('payments/{payment}/crypto-status', [App\Http\Controllers\PaymentController::class, 'checkCryptoStatus'])->name('payments.crypto.status');
    Route::get('payments/{payment}/complete', [App\Http\Controllers\PaymentController::class, 'completeCryptoPayment'])->name('payments.complete');
    Route::post('payments/webhook', [App\Http\Controllers\PaymentController::class, 'webhook'])->name('payments.webhook');

    // Order Routes
    Route::get('orders', [App\Http\Controllers\OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}', [App\Http\Controllers\OrderController::class, 'show'])->name('orders.show');
    Route::patch('orders/{order}/cancel', [App\Http\Controllers\OrderController::class, 'cancel'])->name('orders.cancel');
    Route::get('orders/{order}/receipt', [App\Http\Controllers\OrderController::class, 'receipt'])->name('orders.receipt');
    Route::get('orders/{order}/status', [App\Http\Controllers\OrderController::class, 'status'])->name('orders.status');
});

// Permission-based routes
Route::middleware(['auth', 'permission:manage_events'])->group(function () {
    Route::get('/events/manage', function () {
        return 'Event Management - Only users with manage_events permission can see this';
    })->name('events.manage');
});

Route::middleware(['auth', 'permission:manage_users'])->group(function () {
    Route::get('/users/manage', function () {
        return 'User Management - Only users with manage_users permission can see this';
    })->name('users.manage');
});

// Store intended URL for authentication redirect
Route::post('/store-intended-url', function (Illuminate\Http\Request $request) {
    if ($request->has('intended_url')) {
        session(['intended_url' => $request->intended_url]);
    }
    return response()->json(['success' => true]);
})->name('store.intended.url');



// Static page routes
Route::get('/about', function () {
    return view('about');
})->name('about');

Route::get('/contact', function () {
    return view('contact');
})->name('contact');

Route::get('/agenda', function () {
    return view('agenda');
})->name('agenda');

// Public page routes (must be last to avoid conflicts)
Route::get('pages/{slug}', [App\Http\Controllers\PageController::class, 'showBySlug'])->name('pages.show');

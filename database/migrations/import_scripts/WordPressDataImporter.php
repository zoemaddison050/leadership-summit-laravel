<?php

namespace Database\ImportScripts;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Role;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\Speaker;
use App\Models\Session;
use App\Models\Registration;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Page;
use App\Models\Media;
use Carbon\Carbon;

/**
 * WordPress Data Importer for Leadership Summit Migration
 * 
 * This class imports data from WordPress JSON exports into Laravel database
 */
class WordPressDataImporter
{
    private $import_dir;
    private $imported_data = [];
    private $mapping = [];

    public function __construct($import_dir = null)
    {
        $this->import_dir = $import_dir ?: database_path('migrations/export_scripts/exported_data/');

        if (!is_dir($this->import_dir)) {
            throw new \Exception("Import directory not found: {$this->import_dir}");
        }
    }

    /**
     * Main import function
     */
    public function import_all_data()
    {
        echo "Starting WordPress data import...\n";

        try {
            DB::beginTransaction();

            $this->import_roles();
            $this->import_users();
            $this->import_events();
            $this->import_tickets();
            $this->import_speakers();
            $this->import_sessions();
            $this->import_session_speakers();
            $this->import_pages();
            $this->import_media();
            $this->import_orders();
            $this->import_payments();
            $this->import_registrations();

            DB::commit();
            echo "Import completed successfully!\n";
        } catch (\Exception $e) {
            DB::rollBack();
            echo "Import failed: " . $e->getMessage() . "\n";
            Log::error('WordPress import failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Import roles
     */
    public function import_roles()
    {
        echo "Importing roles...\n";

        $roles_data = $this->load_json_file('roles.json');
        $imported_count = 0;

        foreach ($roles_data as $role_data) {
            try {
                $role = Role::updateOrCreate(
                    ['name' => $role_data['name']],
                    [
                        'display_name' => $role_data['display_name'] ?? $role_data['name'],
                        'permissions' => json_encode($role_data['capabilities'] ?? []),
                        'created_at' => $this->parse_date($role_data['created_at']),
                        'updated_at' => $this->parse_date($role_data['updated_at'])
                    ]
                );

                $this->mapping['roles'][$role_data['name']] = $role->id;
                $imported_count++;
            } catch (\Exception $e) {
                echo "Failed to import role {$role_data['name']}: " . $e->getMessage() . "\n";
                Log::warning('Failed to import role', ['role' => $role_data['name'], 'error' => $e->getMessage()]);
            }
        }

        $this->imported_data['roles'] = $imported_count;
        echo "Imported {$imported_count} roles\n";
    }

    /**
     * Import users
     */
    public function import_users()
    {
        echo "Importing users...\n";

        $users_data = $this->load_json_file('users.json');
        $imported_count = 0;

        foreach ($users_data as $user_data) {
            try {
                // Get role ID from mapping
                $role_name = $user_data['role'] ?? 'subscriber';
                $role_id = $this->mapping['roles'][$role_name] ?? null;

                $user = User::updateOrCreate(
                    ['email' => $user_data['email']],
                    [
                        'name' => $user_data['name'],
                        'username' => $user_data['username'] ?? null,
                        'password' => $user_data['password'], // Keep existing hash
                        'role_id' => $role_id,
                        'first_name' => $user_data['first_name'] ?? null,
                        'last_name' => $user_data['last_name'] ?? null,
                        'description' => $user_data['description'] ?? null,
                        'website' => $user_data['website'] ?? null,
                        'status' => $user_data['status'] ?? 0,
                        'email_verified_at' => $this->parse_date($user_data['registered_date']),
                        'created_at' => $this->parse_date($user_data['created_at']),
                        'updated_at' => $this->parse_date($user_data['updated_at'])
                    ]
                );

                $this->mapping['users'][$user_data['id']] = $user->id;
                $imported_count++;
            } catch (\Exception $e) {
                echo "Failed to import user {$user_data['email']}: " . $e->getMessage() . "\n";
                Log::warning('Failed to import user', ['email' => $user_data['email'], 'error' => $e->getMessage()]);
            }
        }

        $this->imported_data['users'] = $imported_count;
        echo "Imported {$imported_count} users\n";
    }

    /**
     * Import events
     */
    public function import_events()
    {
        echo "Importing events...\n";

        $events_data = $this->load_json_file('events.json');
        $imported_count = 0;

        foreach ($events_data as $event_data) {
            try {
                $event = Event::updateOrCreate(
                    ['slug' => $event_data['slug']],
                    [
                        'title' => $event_data['title'],
                        'description' => $event_data['description'] ?? '',
                        'excerpt' => $event_data['excerpt'] ?? '',
                        'start_date' => $this->parse_date($event_data['start_date']),
                        'end_date' => $this->parse_date($event_data['end_date']),
                        'start_time' => $event_data['start_time'] ?? null,
                        'end_time' => $event_data['end_time'] ?? null,
                        'all_day' => $event_data['all_day'] === 'yes' || $event_data['all_day'] === true,
                        'location' => $this->format_location($event_data),
                        'venue_name' => $event_data['venue_name'] ?? null,
                        'venue_address' => $event_data['venue_address'] ?? null,
                        'venue_city' => $event_data['venue_city'] ?? null,
                        'venue_state' => $event_data['venue_state'] ?? null,
                        'venue_zip' => $event_data['venue_zip'] ?? null,
                        'venue_country' => $event_data['venue_country'] ?? null,
                        'cost' => $event_data['cost'] ?? null,
                        'cost_description' => $event_data['cost_description'] ?? null,
                        'website' => $event_data['website'] ?? null,
                        'featured_image' => $event_data['featured_image'] ?? null,
                        'status' => $this->map_post_status($event_data['status']),
                        'categories' => json_encode($event_data['categories'] ?? []),
                        'tags' => json_encode($event_data['tags'] ?? []),
                        'meta_data' => json_encode($event_data['meta_data'] ?? []),
                        'created_at' => $this->parse_date($event_data['created_at']),
                        'updated_at' => $this->parse_date($event_data['updated_at'])
                    ]
                );

                $this->mapping['events'][$event_data['id']] = $event->id;
                $imported_count++;
            } catch (\Exception $e) {
                echo "Failed to import event {$event_data['title']}: " . $e->getMessage() . "\n";
                Log::warning('Failed to import event', ['title' => $event_data['title'], 'error' => $e->getMessage()]);
            }
        }

        $this->imported_data['events'] = $imported_count;
        echo "Imported {$imported_count} events\n";
    }

    /**
     * Import tickets
     */
    public function import_tickets()
    {
        echo "Importing tickets...\n";

        $tickets_data = $this->load_json_file('tickets.json');
        $imported_count = 0;

        foreach ($tickets_data as $ticket_data) {
            try {
                // Get event ID from mapping
                $event_id = null;
                if (!empty($ticket_data['event_id'])) {
                    $event_id = $this->mapping['events'][$ticket_data['event_id']] ?? null;
                }

                $ticket = Ticket::updateOrCreate(
                    ['name' => $ticket_data['name'], 'event_id' => $event_id],
                    [
                        'event_id' => $event_id,
                        'description' => $ticket_data['description'] ?? '',
                        'short_description' => $ticket_data['short_description'] ?? '',
                        'price' => $ticket_data['price'] ?? 0,
                        'regular_price' => $ticket_data['regular_price'] ?? $ticket_data['price'] ?? 0,
                        'sale_price' => $ticket_data['sale_price'] ?? null,
                        'sku' => $ticket_data['sku'] ?? null,
                        'capacity' => $ticket_data['capacity'] ?? $ticket_data['stock_quantity'] ?? null,
                        'available' => $ticket_data['available'] ?? $ticket_data['stock_quantity'] ?? null,
                        'manage_stock' => $ticket_data['manage_stock'] ?? true,
                        'stock_status' => $ticket_data['stock_status'] ?? 'instock',
                        'start_sale_date' => $this->parse_date($ticket_data['start_sale_date']),
                        'end_sale_date' => $this->parse_date($ticket_data['end_sale_date']),
                        'type' => $ticket_data['type'] ?? 'simple',
                        'status' => $this->map_post_status($ticket_data['status']),
                        'featured_image' => $ticket_data['featured_image'] ?? null,
                        'categories' => json_encode($ticket_data['categories'] ?? []),
                        'tags' => json_encode($ticket_data['tags'] ?? []),
                        'meta_data' => json_encode($ticket_data['meta_data'] ?? []),
                        'created_at' => $this->parse_date($ticket_data['created_at']),
                        'updated_at' => $this->parse_date($ticket_data['updated_at'])
                    ]
                );

                $this->mapping['tickets'][$ticket_data['id']] = $ticket->id;
                $imported_count++;
            } catch (\Exception $e) {
                echo "Failed to import ticket {$ticket_data['name']}: " . $e->getMessage() . "\n";
                Log::warning('Failed to import ticket', ['name' => $ticket_data['name'], 'error' => $e->getMessage()]);
            }
        }

        $this->imported_data['tickets'] = $imported_count;
        echo "Imported {$imported_count} tickets\n";
    }

    /**
     * Import speakers
     */
    public function import_speakers()
    {
        echo "Importing speakers...\n";

        $speakers_data = $this->load_json_file('speakers.json');
        $imported_count = 0;

        foreach ($speakers_data as $speaker_data) {
            try {
                $speaker = Speaker::updateOrCreate(
                    ['name' => $speaker_data['name']],
                    [
                        'slug' => $speaker_data['slug'] ?? null,
                        'bio' => $speaker_data['bio'] ?? '',
                        'excerpt' => $speaker_data['excerpt'] ?? '',
                        'extended_bio' => $speaker_data['extended_bio'] ?? null,
                        'position' => $speaker_data['position'] ?? $speaker_data['job_title'] ?? null,
                        'company' => $speaker_data['company'] ?? $speaker_data['organization'] ?? null,
                        'credentials' => $speaker_data['credentials'] ?? null,
                        'featured' => $speaker_data['featured'] ?? false,
                        'photo' => $speaker_data['photo'] ?? null,
                        'linkedin_url' => $speaker_data['linkedin_url'] ?? null,
                        'twitter_url' => $speaker_data['twitter_url'] ?? null,
                        'website_url' => $speaker_data['website_url'] ?? null,
                        'status' => $this->map_post_status($speaker_data['status']),
                        'categories' => json_encode($speaker_data['categories'] ?? []),
                        'meta_data' => json_encode($speaker_data['meta_data'] ?? []),
                        'created_at' => $this->parse_date($speaker_data['created_at']),
                        'updated_at' => $this->parse_date($speaker_data['updated_at'])
                    ]
                );

                $this->mapping['speakers'][$speaker_data['id']] = $speaker->id;
                $imported_count++;
            } catch (\Exception $e) {
                echo "Failed to import speaker {$speaker_data['name']}: " . $e->getMessage() . "\n";
                Log::warning('Failed to import speaker', ['name' => $speaker_data['name'], 'error' => $e->getMessage()]);
            }
        }

        $this->imported_data['speakers'] = $imported_count;
        echo "Imported {$imported_count} speakers\n";
    }

    /**
     * Import sessions
     */
    public function import_sessions()
    {
        echo "Importing sessions...\n";

        $sessions_data = $this->load_json_file('sessions.json');
        $imported_count = 0;

        foreach ($sessions_data as $session_data) {
            try {
                // Get event ID from mapping
                $event_id = null;
                if (!empty($session_data['event_id'])) {
                    $event_id = $this->mapping['events'][$session_data['event_id']] ?? null;
                }

                $session = Session::updateOrCreate(
                    ['title' => $session_data['title'], 'event_id' => $event_id],
                    [
                        'event_id' => $event_id,
                        'slug' => $session_data['slug'] ?? null,
                        'description' => $session_data['description'] ?? '',
                        'excerpt' => $session_data['excerpt'] ?? '',
                        'start_time' => $this->parse_datetime($session_data['start_time'], $session_data['date']),
                        'end_time' => $this->parse_datetime($session_data['end_time'], $session_data['date']),
                        'date' => $this->parse_date($session_data['date']),
                        'location' => $session_data['location'] ?? null,
                        'room' => $session_data['room'] ?? null,
                        'capacity' => $session_data['capacity'] ?? null,
                        'session_type' => $session_data['session_type'] ?? null,
                        'level' => $session_data['level'] ?? null,
                        'materials_url' => $session_data['materials_url'] ?? null,
                        'recording_url' => $session_data['recording_url'] ?? null,
                        'featured_image' => $session_data['featured_image'] ?? null,
                        'status' => $this->map_post_status($session_data['status']),
                        'categories' => json_encode($session_data['categories'] ?? []),
                        'tracks' => json_encode($session_data['tracks'] ?? []),
                        'tags' => json_encode($session_data['tags'] ?? []),
                        'meta_data' => json_encode($session_data['meta_data'] ?? []),
                        'created_at' => $this->parse_date($session_data['created_at']),
                        'updated_at' => $this->parse_date($session_data['updated_at'])
                    ]
                );

                $this->mapping['sessions'][$session_data['id']] = $session->id;
                $imported_count++;
            } catch (\Exception $e) {
                echo "Failed to import session {$session_data['title']}: " . $e->getMessage() . "\n";
                Log::warning('Failed to import session', ['title' => $session_data['title'], 'error' => $e->getMessage()]);
            }
        }

        $this->imported_data['sessions'] = $imported_count;
        echo "Imported {$imported_count} sessions\n";
    }

    /**
     * Import session-speaker relationships
     */
    public function import_session_speakers()
    {
        echo "Importing session-speaker relationships...\n";

        $session_speakers_data = $this->load_json_file('session_speakers.json');
        $imported_count = 0;

        foreach ($session_speakers_data as $relationship) {
            try {
                $session_id = $this->mapping['sessions'][$relationship['session_id']] ?? null;
                $speaker_id = $this->mapping['speakers'][$relationship['speaker_id']] ?? null;

                if ($session_id && $speaker_id) {
                    DB::table('session_speakers')->updateOrInsert(
                        [
                            'session_id' => $session_id,
                            'speaker_id' => $speaker_id
                        ],
                        [
                            'created_at' => $this->parse_date($relationship['created_at']),
                            'updated_at' => $this->parse_date($relationship['updated_at'])
                        ]
                    );
                    $imported_count++;
                }
            } catch (\Exception $e) {
                echo "Failed to import session-speaker relationship: " . $e->getMessage() . "\n";
                Log::warning('Failed to import session-speaker relationship', ['error' => $e->getMessage()]);
            }
        }

        $this->imported_data['session_speakers'] = $imported_count;
        echo "Imported {$imported_count} session-speaker relationships\n";
    }

    /**
     * Import pages
     */
    public function import_pages()
    {
        echo "Importing pages...\n";

        $pages_data = $this->load_json_file('pages.json');
        $imported_count = 0;

        foreach ($pages_data as $page_data) {
            try {
                $page = Page::updateOrCreate(
                    ['slug' => $page_data['slug']],
                    [
                        'title' => $page_data['title'],
                        'content' => $page_data['content'] ?? '',
                        'excerpt' => $page_data['excerpt'] ?? '',
                        'parent_id' => $page_data['parent_id'] ?? null,
                        'menu_order' => $page_data['menu_order'] ?? 0,
                        'template' => $page_data['template'] ?? null,
                        'featured_image' => $page_data['featured_image'] ?? null,
                        'meta_title' => $page_data['meta_title'] ?? null,
                        'meta_description' => $page_data['meta_description'] ?? null,
                        'status' => $this->map_post_status($page_data['status']),
                        'meta_data' => json_encode($page_data['meta_data'] ?? []),
                        'created_at' => $this->parse_date($page_data['created_at']),
                        'updated_at' => $this->parse_date($page_data['updated_at'])
                    ]
                );

                $this->mapping['pages'][$page_data['id']] = $page->id;
                $imported_count++;
            } catch (\Exception $e) {
                echo "Failed to import page {$page_data['title']}: " . $e->getMessage() . "\n";
                Log::warning('Failed to import page', ['title' => $page_data['title'], 'error' => $e->getMessage()]);
            }
        }

        $this->imported_data['pages'] = $imported_count;
        echo "Imported {$imported_count} pages\n";
    }

    /**
     * Import media
     */
    public function import_media()
    {
        echo "Importing media...\n";

        $media_data = $this->load_json_file('media.json');
        $imported_count = 0;

        foreach ($media_data as $media_item) {
            try {
                $media = Media::updateOrCreate(
                    ['filename' => $media_item['filename']],
                    [
                        'title' => $media_item['title'],
                        'file_path' => $media_item['file_path'] ?? null,
                        'file_url' => $media_item['file_url'] ?? null,
                        'mime_type' => $media_item['mime_type'] ?? null,
                        'file_size' => $media_item['file_size'] ?? null,
                        'alt_text' => $media_item['alt_text'] ?? null,
                        'caption' => $media_item['caption'] ?? null,
                        'description' => $media_item['description'] ?? null,
                        'parent_id' => $media_item['parent_id'] ?? null,
                        'metadata' => json_encode($media_item['metadata'] ?? []),
                        'created_at' => $this->parse_date($media_item['created_at']),
                        'updated_at' => $this->parse_date($media_item['updated_at'])
                    ]
                );

                $this->mapping['media'][$media_item['id']] = $media->id;
                $imported_count++;
            } catch (\Exception $e) {
                echo "Failed to import media {$media_item['filename']}: " . $e->getMessage() . "\n";
                Log::warning('Failed to import media', ['filename' => $media_item['filename'], 'error' => $e->getMessage()]);
            }
        }

        $this->imported_data['media'] = $imported_count;
        echo "Imported {$imported_count} media files\n";
    }

    /**
     * Import orders
     */
    public function import_orders()
    {
        echo "Importing orders...\n";

        $orders_data = $this->load_json_file('orders.json');
        $imported_count = 0;

        foreach ($orders_data as $order_data) {
            try {
                // Get user ID from mapping
                $user_id = null;
                if (!empty($order_data['user_id'])) {
                    $user_id = $this->mapping['users'][$order_data['user_id']] ?? null;
                }

                $order = Order::updateOrCreate(
                    ['id' => $order_data['id']],
                    [
                        'user_id' => $user_id,
                        'status' => $order_data['status'],
                        'currency' => $order_data['currency'] ?? 'USD',
                        'total' => $order_data['total'] ?? 0,
                        'subtotal' => $order_data['subtotal'] ?? 0,
                        'tax_total' => $order_data['tax_total'] ?? 0,
                        'shipping_total' => $order_data['shipping_total'] ?? 0,
                        'discount_total' => $order_data['discount_total'] ?? 0,
                        'payment_method' => $order_data['payment_method'] ?? null,
                        'payment_method_title' => $order_data['payment_method_title'] ?? null,
                        'transaction_id' => $order_data['transaction_id'] ?? null,
                        'billing_first_name' => $order_data['billing_first_name'] ?? null,
                        'billing_last_name' => $order_data['billing_last_name'] ?? null,
                        'billing_email' => $order_data['billing_email'] ?? null,
                        'billing_phone' => $order_data['billing_phone'] ?? null,
                        'billing_address_1' => $order_data['billing_address_1'] ?? null,
                        'billing_address_2' => $order_data['billing_address_2'] ?? null,
                        'billing_city' => $order_data['billing_city'] ?? null,
                        'billing_state' => $order_data['billing_state'] ?? null,
                        'billing_postcode' => $order_data['billing_postcode'] ?? null,
                        'billing_country' => $order_data['billing_country'] ?? null,
                        'order_notes' => $order_data['order_notes'] ?? null,
                        'created_at' => $this->parse_date($order_data['created_at']),
                        'updated_at' => $this->parse_date($order_data['updated_at'])
                    ]
                );

                $this->mapping['orders'][$order_data['id']] = $order->id;
                $imported_count++;
            } catch (\Exception $e) {
                echo "Failed to import order {$order_data['id']}: " . $e->getMessage() . "\n";
                Log::warning('Failed to import order', ['id' => $order_data['id'], 'error' => $e->getMessage()]);
            }
        }

        $this->imported_data['orders'] = $imported_count;
        echo "Imported {$imported_count} orders\n";
    }

    /**
     * Import payments
     */
    public function import_payments()
    {
        echo "Importing payments...\n";

        $payments_data = $this->load_json_file('payments.json');
        $imported_count = 0;

        foreach ($payments_data as $payment_data) {
            try {
                // Get order ID from mapping
                $order_id = $this->mapping['orders'][$payment_data['order_id']] ?? null;

                $payment = Payment::updateOrCreate(
                    ['id' => $payment_data['id']],
                    [
                        'order_id' => $order_id,
                        'amount' => $payment_data['amount'] ?? 0,
                        'currency' => $payment_data['currency'] ?? 'USD',
                        'payment_method' => $payment_data['payment_method'] ?? null,
                        'payment_method_title' => $payment_data['payment_method_title'] ?? null,
                        'transaction_id' => $payment_data['transaction_id'] ?? null,
                        'status' => $payment_data['status'] ?? 'pending',
                        'gateway_response' => $payment_data['gateway_response'] ?? null,
                        'created_at' => $this->parse_date($payment_data['created_at']),
                        'updated_at' => $this->parse_date($payment_data['updated_at'])
                    ]
                );

                $this->mapping['payments'][$payment_data['id']] = $payment->id;
                $imported_count++;
            } catch (\Exception $e) {
                echo "Failed to import payment {$payment_data['id']}: " . $e->getMessage() . "\n";
                Log::warning('Failed to import payment', ['id' => $payment_data['id'], 'error' => $e->getMessage()]);
            }
        }

        $this->imported_data['payments'] = $imported_count;
        echo "Imported {$imported_count} payments\n";
    }

    /**
     * Import registrations
     */
    public function import_registrations()
    {
        echo "Importing registrations...\n";

        $registrations_data = $this->load_json_file('registrations.json');
        $imported_count = 0;

        foreach ($registrations_data as $registration_data) {
            try {
                // Get mapped IDs
                $user_id = null;
                if (!empty($registration_data['user_id'])) {
                    $user_id = $this->mapping['users'][$registration_data['user_id']] ?? null;
                }

                $event_id = null;
                if (!empty($registration_data['event_id'])) {
                    $event_id = $this->mapping['events'][$registration_data['event_id']] ?? null;
                }

                $ticket_id = null;
                if (!empty($registration_data['ticket_id'])) {
                    $ticket_id = $this->mapping['tickets'][$registration_data['ticket_id']] ?? null;
                }

                $order_id = null;
                if (!empty($registration_data['order_id'])) {
                    $order_id = $this->mapping['orders'][$registration_data['order_id']] ?? null;
                }

                $registration = Registration::create([
                    'user_id' => $user_id,
                    'event_id' => $event_id,
                    'ticket_id' => $ticket_id,
                    'order_id' => $order_id,
                    'quantity' => $registration_data['quantity'] ?? 1,
                    'status' => $registration_data['status'] ?? 'pending',
                    'payment_status' => $registration_data['payment_status'] ?? 'pending',
                    'first_name' => $registration_data['first_name'] ?? null,
                    'last_name' => $registration_data['last_name'] ?? null,
                    'email' => $registration_data['email'] ?? null,
                    'organization' => $registration_data['organization'] ?? null,
                    'created_at' => $this->parse_date($registration_data['created_at']),
                    'updated_at' => $this->parse_date($registration_data['updated_at'])
                ]);

                $imported_count++;
            } catch (\Exception $e) {
                echo "Failed to import registration: " . $e->getMessage() . "\n";
                Log::warning('Failed to import registration', ['error' => $e->getMessage()]);
            }
        }

        $this->imported_data['registrations'] = $imported_count;
        echo "Imported {$imported_count} registrations\n";
    }

    /**
     * Helper methods
     */

    private function load_json_file($filename)
    {
        $file_path = $this->import_dir . $filename;

        if (!file_exists($file_path)) {
            echo "Warning: File not found: {$filename}\n";
            return [];
        }

        $content = file_get_contents($file_path);
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Failed to parse JSON file {$filename}: " . json_last_error_msg());
        }

        return $data;
    }

    private function parse_date($date_string)
    {
        if (empty($date_string) || $date_string === '0000-00-00 00:00:00') {
            return null;
        }

        try {
            return Carbon::parse($date_string);
        } catch (\Exception $e) {
            return null;
        }
    }

    private function parse_datetime($time_string, $date_string = null)
    {
        if (empty($time_string)) {
            return null;
        }

        try {
            if ($date_string) {
                return Carbon::parse($date_string . ' ' . $time_string);
            }
            return Carbon::parse($time_string);
        } catch (\Exception $e) {
            return null;
        }
    }

    private function map_post_status($wp_status)
    {
        $status_map = [
            'publish' => 'published',
            'draft' => 'draft',
            'private' => 'private',
            'pending' => 'pending',
            'trash' => 'deleted'
        ];

        return $status_map[$wp_status] ?? 'draft';
    }

    private function format_location($event_data)
    {
        $location_parts = array_filter([
            $event_data['venue_name'] ?? null,
            $event_data['venue_address'] ?? null,
            $event_data['venue_city'] ?? null,
            $event_data['venue_state'] ?? null,
            $event_data['venue_zip'] ?? null,
            $event_data['venue_country'] ?? null
        ]);

        return !empty($location_parts) ? implode(', ', $location_parts) : null;
    }

    /**
     * Get import summary
     */
    public function get_import_summary()
    {
        return $this->imported_data;
    }

    /**
     * Get ID mappings
     */
    public function get_mappings()
    {
        return $this->mapping;
    }
}

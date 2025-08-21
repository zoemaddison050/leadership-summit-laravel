<?php

/**
 * WordPress Data Export Script for Leadership Summit Migration
 * 
 * This script exports data from WordPress to JSON files for Laravel import
 * Run this script in WordPress environment to export all necessary data
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    // If not in WordPress environment, define basic constants for standalone execution
    define('ABSPATH', dirname(__FILE__) . '/');

    // Include WordPress configuration if available
    if (file_exists('../../../wp-config.php')) {
        require_once('../../../wp-config.php');
    } else {
        die('WordPress configuration not found. Please run this script from WordPress environment.');
    }
}

class WordPressDataExporter
{
    private $export_dir;
    private $exported_data = [];

    public function __construct()
    {
        $this->export_dir = dirname(__FILE__) . '/exported_data/';

        // Create export directory if it doesn't exist
        if (!file_exists($this->export_dir)) {
            wp_mkdir_p($this->export_dir);
        }
    }

    /**
     * Main export function
     */
    public function export_all_data()
    {
        echo "Starting WordPress data export...\n";

        $this->export_users_and_roles();
        $this->export_events_and_tickets();
        $this->export_speakers();
        $this->export_sessions();
        $this->export_pages_and_content();
        $this->export_media();
        $this->export_woocommerce_data();

        echo "Export completed successfully!\n";
        echo "Exported files saved to: " . $this->export_dir . "\n";
    }

    /**
     * Export users and roles
     */
    public function export_users_and_roles()
    {
        echo "Exporting users and roles...\n";

        // Export roles
        global $wp_roles;
        $roles_data = [];

        foreach ($wp_roles->roles as $role_key => $role_data) {
            $roles_data[] = [
                'name' => $role_key,
                'display_name' => $role_data['name'],
                'capabilities' => $role_data['capabilities'],
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ];
        }

        $this->save_to_file('roles.json', $roles_data);

        // Export users
        $users = get_users([
            'number' => -1,
            'fields' => 'all'
        ]);

        $users_data = [];
        foreach ($users as $user) {
            $user_meta = get_user_meta($user->ID);

            $users_data[] = [
                'id' => $user->ID,
                'name' => $user->display_name,
                'email' => $user->user_email,
                'username' => $user->user_login,
                'password' => $user->user_pass, // Will need to be handled carefully
                'role' => !empty($user->roles) ? $user->roles[0] : 'subscriber',
                'first_name' => get_user_meta($user->ID, 'first_name', true),
                'last_name' => get_user_meta($user->ID, 'last_name', true),
                'description' => get_user_meta($user->ID, 'description', true),
                'website' => $user->user_url,
                'registered_date' => $user->user_registered,
                'status' => $user->user_status,
                'meta_data' => $this->serialize_meta_data($user_meta),
                'created_at' => $user->user_registered,
                'updated_at' => current_time('mysql')
            ];
        }

        $this->save_to_file('users.json', $users_data);
        echo "Exported " . count($users_data) . " users and " . count($roles_data) . " roles\n";
    }

    /**
     * Export events and tickets (The Events Calendar + WooCommerce)
     */
    public function export_events_and_tickets()
    {
        echo "Exporting events and tickets...\n";

        // Export events from The Events Calendar
        $events = get_posts([
            'post_type' => 'tribe_events',
            'posts_per_page' => -1,
            'post_status' => ['publish', 'draft', 'private']
        ]);

        $events_data = [];
        foreach ($events as $event) {
            $event_meta = get_post_meta($event->ID);

            $events_data[] = [
                'id' => $event->ID,
                'title' => $event->post_title,
                'slug' => $event->post_name,
                'description' => $event->post_content,
                'excerpt' => $event->post_excerpt,
                'start_date' => get_post_meta($event->ID, '_EventStartDate', true),
                'end_date' => get_post_meta($event->ID, '_EventEndDate', true),
                'start_time' => get_post_meta($event->ID, '_EventStartTime', true),
                'end_time' => get_post_meta($event->ID, '_EventEndTime', true),
                'all_day' => get_post_meta($event->ID, '_EventAllDay', true),
                'location' => get_post_meta($event->ID, '_EventVenueID', true),
                'venue_name' => get_post_meta($event->ID, '_EventVenue', true),
                'venue_address' => get_post_meta($event->ID, '_EventAddress', true),
                'venue_city' => get_post_meta($event->ID, '_EventCity', true),
                'venue_state' => get_post_meta($event->ID, '_EventState', true),
                'venue_zip' => get_post_meta($event->ID, '_EventZip', true),
                'venue_country' => get_post_meta($event->ID, '_EventCountry', true),
                'organizer' => get_post_meta($event->ID, '_EventOrganizerID', true),
                'cost' => get_post_meta($event->ID, '_EventCost', true),
                'cost_description' => get_post_meta($event->ID, '_EventCostDescription', true),
                'website' => get_post_meta($event->ID, '_EventURL', true),
                'featured_image' => get_the_post_thumbnail_url($event->ID, 'full'),
                'status' => $event->post_status,
                'categories' => wp_get_post_terms($event->ID, 'tribe_events_cat', ['fields' => 'names']),
                'tags' => wp_get_post_terms($event->ID, 'post_tag', ['fields' => 'names']),
                'meta_data' => $this->serialize_meta_data($event_meta),
                'created_at' => $event->post_date,
                'updated_at' => $event->post_modified
            ];
        }

        $this->save_to_file('events.json', $events_data);

        // Export tickets (WooCommerce products related to events)
        $tickets = get_posts([
            'post_type' => 'product',
            'posts_per_page' => -1,
            'post_status' => ['publish', 'draft', 'private']
        ]);

        $tickets_data = [];
        foreach ($tickets as $ticket) {
            $product = wc_get_product($ticket->ID);
            if (!$product) continue;

            $ticket_meta = get_post_meta($ticket->ID);

            $tickets_data[] = [
                'id' => $ticket->ID,
                'event_id' => get_post_meta($ticket->ID, '_tribe_wooticket_for_event', true),
                'name' => $ticket->post_title,
                'description' => $ticket->post_content,
                'short_description' => $ticket->post_excerpt,
                'price' => $product->get_price(),
                'regular_price' => $product->get_regular_price(),
                'sale_price' => $product->get_sale_price(),
                'sku' => $product->get_sku(),
                'stock_quantity' => $product->get_stock_quantity(),
                'manage_stock' => $product->get_manage_stock(),
                'stock_status' => $product->get_stock_status(),
                'capacity' => get_post_meta($ticket->ID, '_capacity', true),
                'available' => get_post_meta($ticket->ID, '_stock', true),
                'start_sale_date' => get_post_meta($ticket->ID, '_ticket_start_date', true),
                'end_sale_date' => get_post_meta($ticket->ID, '_ticket_end_date', true),
                'type' => $product->get_type(),
                'status' => $ticket->post_status,
                'featured_image' => get_the_post_thumbnail_url($ticket->ID, 'full'),
                'categories' => wp_get_post_terms($ticket->ID, 'product_cat', ['fields' => 'names']),
                'tags' => wp_get_post_terms($ticket->ID, 'product_tag', ['fields' => 'names']),
                'meta_data' => $this->serialize_meta_data($ticket_meta),
                'created_at' => $ticket->post_date,
                'updated_at' => $ticket->post_modified
            ];
        }

        $this->save_to_file('tickets.json', $tickets_data);
        echo "Exported " . count($events_data) . " events and " . count($tickets_data) . " tickets\n";
    }

    /**
     * Export speakers
     */
    public function export_speakers()
    {
        echo "Exporting speakers...\n";

        $speakers = get_posts([
            'post_type' => 'speaker',
            'posts_per_page' => -1,
            'post_status' => ['publish', 'draft', 'private']
        ]);

        $speakers_data = [];
        foreach ($speakers as $speaker) {
            $speaker_meta = get_post_meta($speaker->ID);

            $speakers_data[] = [
                'id' => $speaker->ID,
                'name' => $speaker->post_title,
                'slug' => $speaker->post_name,
                'bio' => $speaker->post_content,
                'excerpt' => $speaker->post_excerpt,
                'extended_bio' => get_post_meta($speaker->ID, '_speaker_bio', true),
                'job_title' => get_post_meta($speaker->ID, '_speaker_job_title', true),
                'position' => get_post_meta($speaker->ID, '_speaker_job_title', true), // Alias
                'organization' => get_post_meta($speaker->ID, '_speaker_organization', true),
                'company' => get_post_meta($speaker->ID, '_speaker_organization', true), // Alias
                'credentials' => get_post_meta($speaker->ID, '_speaker_credentials', true),
                'featured' => get_post_meta($speaker->ID, '_speaker_featured', true) === '1',
                'photo' => get_the_post_thumbnail_url($speaker->ID, 'full'),
                'linkedin_url' => get_post_meta($speaker->ID, '_speaker_linkedin', true),
                'twitter_url' => get_post_meta($speaker->ID, '_speaker_twitter', true),
                'website_url' => get_post_meta($speaker->ID, '_speaker_website', true),
                'status' => $speaker->post_status,
                'categories' => wp_get_post_terms($speaker->ID, 'speaker_category', ['fields' => 'names']),
                'meta_data' => $this->serialize_meta_data($speaker_meta),
                'created_at' => $speaker->post_date,
                'updated_at' => $speaker->post_modified
            ];
        }

        $this->save_to_file('speakers.json', $speakers_data);
        echo "Exported " . count($speakers_data) . " speakers\n";
    }

    /**
     * Export sessions
     */
    public function export_sessions()
    {
        echo "Exporting sessions...\n";

        $sessions = get_posts([
            'post_type' => 'session',
            'posts_per_page' => -1,
            'post_status' => ['publish', 'draft', 'private']
        ]);

        $sessions_data = [];
        foreach ($sessions as $session) {
            $session_meta = get_post_meta($session->ID);

            // Get session speakers
            $session_speakers = get_post_meta($session->ID, '_session_speakers', true);
            if (is_string($session_speakers)) {
                $session_speakers = maybe_unserialize($session_speakers);
            }

            $sessions_data[] = [
                'id' => $session->ID,
                'event_id' => get_post_meta($session->ID, '_session_event', true),
                'title' => $session->post_title,
                'slug' => $session->post_name,
                'description' => $session->post_content,
                'excerpt' => $session->post_excerpt,
                'start_time' => get_post_meta($session->ID, '_session_start_time', true),
                'end_time' => get_post_meta($session->ID, '_session_end_time', true),
                'date' => get_post_meta($session->ID, '_session_date', true),
                'location' => get_post_meta($session->ID, '_session_location', true),
                'room' => get_post_meta($session->ID, '_session_room', true),
                'capacity' => get_post_meta($session->ID, '_session_capacity', true),
                'speakers' => is_array($session_speakers) ? $session_speakers : [],
                'session_type' => get_post_meta($session->ID, '_session_type', true),
                'level' => get_post_meta($session->ID, '_session_level', true),
                'materials_url' => get_post_meta($session->ID, '_session_materials', true),
                'recording_url' => get_post_meta($session->ID, '_session_recording', true),
                'featured_image' => get_the_post_thumbnail_url($session->ID, 'full'),
                'status' => $session->post_status,
                'categories' => wp_get_post_terms($session->ID, 'session_category', ['fields' => 'names']),
                'tracks' => wp_get_post_terms($session->ID, 'session_track', ['fields' => 'names']),
                'tags' => wp_get_post_terms($session->ID, 'post_tag', ['fields' => 'names']),
                'meta_data' => $this->serialize_meta_data($session_meta),
                'created_at' => $session->post_date,
                'updated_at' => $session->post_modified
            ];
        }

        $this->save_to_file('sessions.json', $sessions_data);

        // Export session-speaker relationships
        $session_speakers_data = [];
        foreach ($sessions_data as $session) {
            if (!empty($session['speakers']) && is_array($session['speakers'])) {
                foreach ($session['speakers'] as $speaker_id) {
                    $session_speakers_data[] = [
                        'session_id' => $session['id'],
                        'speaker_id' => $speaker_id,
                        'created_at' => current_time('mysql'),
                        'updated_at' => current_time('mysql')
                    ];
                }
            }
        }

        $this->save_to_file('session_speakers.json', $session_speakers_data);
        echo "Exported " . count($sessions_data) . " sessions and " . count($session_speakers_data) . " session-speaker relationships\n";
    }

    /**
     * Export pages and content
     */
    public function export_pages_and_content()
    {
        echo "Exporting pages and content...\n";

        // Export pages
        $pages = get_posts([
            'post_type' => 'page',
            'posts_per_page' => -1,
            'post_status' => ['publish', 'draft', 'private']
        ]);

        $pages_data = [];
        foreach ($pages as $page) {
            $page_meta = get_post_meta($page->ID);

            $pages_data[] = [
                'id' => $page->ID,
                'title' => $page->post_title,
                'slug' => $page->post_name,
                'content' => $page->post_content,
                'excerpt' => $page->post_excerpt,
                'parent_id' => $page->post_parent,
                'menu_order' => $page->menu_order,
                'template' => get_page_template_slug($page->ID),
                'featured_image' => get_the_post_thumbnail_url($page->ID, 'full'),
                'meta_title' => get_post_meta($page->ID, '_yoast_wpseo_title', true),
                'meta_description' => get_post_meta($page->ID, '_yoast_wpseo_metadesc', true),
                'status' => $page->post_status,
                'meta_data' => $this->serialize_meta_data($page_meta),
                'created_at' => $page->post_date,
                'updated_at' => $page->post_modified
            ];
        }

        $this->save_to_file('pages.json', $pages_data);

        // Export posts/blog content
        $posts = get_posts([
            'post_type' => 'post',
            'posts_per_page' => -1,
            'post_status' => ['publish', 'draft', 'private']
        ]);

        $posts_data = [];
        foreach ($posts as $post) {
            $post_meta = get_post_meta($post->ID);

            $posts_data[] = [
                'id' => $post->ID,
                'title' => $post->post_title,
                'slug' => $post->post_name,
                'content' => $post->post_content,
                'excerpt' => $post->post_excerpt,
                'author_id' => $post->post_author,
                'featured_image' => get_the_post_thumbnail_url($post->ID, 'full'),
                'meta_title' => get_post_meta($post->ID, '_yoast_wpseo_title', true),
                'meta_description' => get_post_meta($post->ID, '_yoast_wpseo_metadesc', true),
                'status' => $post->post_status,
                'categories' => wp_get_post_terms($post->ID, 'category', ['fields' => 'names']),
                'tags' => wp_get_post_terms($post->ID, 'post_tag', ['fields' => 'names']),
                'meta_data' => $this->serialize_meta_data($post_meta),
                'created_at' => $post->post_date,
                'updated_at' => $post->post_modified
            ];
        }

        $this->save_to_file('posts.json', $posts_data);
        echo "Exported " . count($pages_data) . " pages and " . count($posts_data) . " posts\n";
    }

    /**
     * Export media files
     */
    public function export_media()
    {
        echo "Exporting media files...\n";

        $media = get_posts([
            'post_type' => 'attachment',
            'posts_per_page' => -1,
            'post_status' => 'inherit'
        ]);

        $media_data = [];
        foreach ($media as $attachment) {
            $attachment_meta = get_post_meta($attachment->ID);
            $file_path = get_attached_file($attachment->ID);
            $file_url = wp_get_attachment_url($attachment->ID);

            $media_data[] = [
                'id' => $attachment->ID,
                'title' => $attachment->post_title,
                'filename' => basename($file_path),
                'file_path' => $file_path,
                'file_url' => $file_url,
                'mime_type' => $attachment->post_mime_type,
                'file_size' => filesize($file_path),
                'alt_text' => get_post_meta($attachment->ID, '_wp_attachment_image_alt', true),
                'caption' => $attachment->post_excerpt,
                'description' => $attachment->post_content,
                'parent_id' => $attachment->post_parent,
                'metadata' => wp_get_attachment_metadata($attachment->ID),
                'created_at' => $attachment->post_date,
                'updated_at' => $attachment->post_modified
            ];
        }

        $this->save_to_file('media.json', $media_data);
        echo "Exported " . count($media_data) . " media files\n";
    }

    /**
     * Export WooCommerce data (orders, payments, registrations)
     */
    public function export_woocommerce_data()
    {
        if (!class_exists('WooCommerce')) {
            echo "WooCommerce not found, skipping e-commerce data export\n";
            return;
        }

        echo "Exporting WooCommerce data...\n";

        // Export orders
        $orders = wc_get_orders([
            'limit' => -1,
            'status' => 'any'
        ]);

        $orders_data = [];
        $registrations_data = [];
        $payments_data = [];

        foreach ($orders as $order) {
            $order_data = [
                'id' => $order->get_id(),
                'user_id' => $order->get_user_id(),
                'status' => $order->get_status(),
                'currency' => $order->get_currency(),
                'total' => $order->get_total(),
                'subtotal' => $order->get_subtotal(),
                'tax_total' => $order->get_total_tax(),
                'shipping_total' => $order->get_shipping_total(),
                'discount_total' => $order->get_discount_total(),
                'payment_method' => $order->get_payment_method(),
                'payment_method_title' => $order->get_payment_method_title(),
                'transaction_id' => $order->get_transaction_id(),
                'billing_first_name' => $order->get_billing_first_name(),
                'billing_last_name' => $order->get_billing_last_name(),
                'billing_email' => $order->get_billing_email(),
                'billing_phone' => $order->get_billing_phone(),
                'billing_address_1' => $order->get_billing_address_1(),
                'billing_address_2' => $order->get_billing_address_2(),
                'billing_city' => $order->get_billing_city(),
                'billing_state' => $order->get_billing_state(),
                'billing_postcode' => $order->get_billing_postcode(),
                'billing_country' => $order->get_billing_country(),
                'order_notes' => $order->get_customer_note(),
                'created_at' => $order->get_date_created()->date('Y-m-d H:i:s'),
                'updated_at' => $order->get_date_modified()->date('Y-m-d H:i:s')
            ];

            $orders_data[] = $order_data;

            // Create payment record
            $payments_data[] = [
                'id' => $order->get_id(), // Using order ID as payment ID
                'order_id' => $order->get_id(),
                'amount' => $order->get_total(),
                'currency' => $order->get_currency(),
                'payment_method' => $order->get_payment_method(),
                'payment_method_title' => $order->get_payment_method_title(),
                'transaction_id' => $order->get_transaction_id(),
                'status' => $this->map_order_status_to_payment_status($order->get_status()),
                'gateway_response' => '',
                'created_at' => $order->get_date_created()->date('Y-m-d H:i:s'),
                'updated_at' => $order->get_date_modified()->date('Y-m-d H:i:s')
            ];

            // Create registration records for each item
            foreach ($order->get_items() as $item) {
                $product_id = $item->get_product_id();
                $event_id = get_post_meta($product_id, '_tribe_wooticket_for_event', true);

                if ($event_id) {
                    $registrations_data[] = [
                        'id' => $order->get_id() . '_' . $item->get_id(),
                        'user_id' => $order->get_user_id(),
                        'event_id' => $event_id,
                        'ticket_id' => $product_id,
                        'order_id' => $order->get_id(),
                        'quantity' => $item->get_quantity(),
                        'status' => $this->map_order_status_to_registration_status($order->get_status()),
                        'payment_status' => $this->map_order_status_to_payment_status($order->get_status()),
                        'first_name' => get_post_meta($order->get_id(), '_event_registration_first_name', true) ?: $order->get_billing_first_name(),
                        'last_name' => get_post_meta($order->get_id(), '_event_registration_last_name', true) ?: $order->get_billing_last_name(),
                        'email' => get_post_meta($order->get_id(), '_event_registration_email', true) ?: $order->get_billing_email(),
                        'organization' => get_post_meta($order->get_id(), '_event_registration_organization', true),
                        'created_at' => $order->get_date_created()->date('Y-m-d H:i:s'),
                        'updated_at' => $order->get_date_modified()->date('Y-m-d H:i:s')
                    ];
                }
            }
        }

        $this->save_to_file('orders.json', $orders_data);
        $this->save_to_file('payments.json', $payments_data);
        $this->save_to_file('registrations.json', $registrations_data);

        echo "Exported " . count($orders_data) . " orders, " . count($payments_data) . " payments, and " . count($registrations_data) . " registrations\n";
    }

    /**
     * Helper function to serialize meta data
     */
    private function serialize_meta_data($meta_data)
    {
        $serialized = [];
        foreach ($meta_data as $key => $value) {
            if (is_array($value) && count($value) === 1) {
                $value = $value[0];
            }
            $serialized[$key] = maybe_unserialize($value);
        }
        return $serialized;
    }

    /**
     * Map WooCommerce order status to payment status
     */
    private function map_order_status_to_payment_status($order_status)
    {
        $status_map = [
            'completed' => 'completed',
            'processing' => 'completed',
            'on-hold' => 'pending',
            'pending' => 'pending',
            'cancelled' => 'cancelled',
            'refunded' => 'refunded',
            'failed' => 'failed'
        ];

        return $status_map[$order_status] ?? 'pending';
    }

    /**
     * Map WooCommerce order status to registration status
     */
    private function map_order_status_to_registration_status($order_status)
    {
        $status_map = [
            'completed' => 'confirmed',
            'processing' => 'confirmed',
            'on-hold' => 'pending',
            'pending' => 'pending',
            'cancelled' => 'cancelled',
            'refunded' => 'cancelled',
            'failed' => 'cancelled'
        ];

        return $status_map[$order_status] ?? 'pending';
    }

    /**
     * Save data to JSON file
     */
    private function save_to_file($filename, $data)
    {
        $file_path = $this->export_dir . $filename;
        $json_data = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        if (file_put_contents($file_path, $json_data) === false) {
            throw new Exception("Failed to write to file: $file_path");
        }

        $this->exported_data[$filename] = count($data);
    }

    /**
     * Get export summary
     */
    public function get_export_summary()
    {
        return $this->exported_data;
    }
}

// Execute export if run directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    try {
        $exporter = new WordPressDataExporter();
        $exporter->export_all_data();

        echo "\nExport Summary:\n";
        foreach ($exporter->get_export_summary() as $file => $count) {
            echo "- $file: $count records\n";
        }
    } catch (Exception $e) {
        echo "Export failed: " . $e->getMessage() . "\n";
        exit(1);
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $events = [
            [
                'title' => 'Leadership Summit 2025',
                'slug' => 'leadership-summit-2025',
                'description' => 'Join us for the premier leadership conference of the year, featuring industry experts and thought leaders.',
                'start_date' => '2025-09-15 09:00:00',
                'end_date' => '2025-09-17 17:00:00',
                'location' => 'Convention Center, Downtown',
                'featured_image' => 'leadership-summit-2025.jpg',
                'status' => 'published',
            ],
            [
                'title' => 'Tech Leadership Workshop',
                'slug' => 'tech-leadership-workshop',
                'description' => 'A hands-on workshop focused on leadership in the technology sector.',
                'start_date' => '2025-08-20 10:00:00',
                'end_date' => '2025-08-20 16:00:00',
                'location' => 'Tech Hub, Innovation District',
                'featured_image' => 'tech-workshop.jpg',
                'status' => 'published',
            ],
            [
                'title' => 'Women in Leadership Conference',
                'slug' => 'women-in-leadership-conference',
                'description' => 'Empowering women leaders across all industries.',
                'start_date' => '2025-10-05 09:00:00',
                'end_date' => '2025-10-05 18:00:00',
                'location' => 'Grand Hotel, Business District',
                'featured_image' => 'women-leadership.jpg',
                'status' => 'draft',
            ]
        ];

        foreach ($events as $eventData) {
            $event = \App\Models\Event::create($eventData);

            // Create tickets for each event
            $tickets = [
                [
                    'event_id' => $event->id,
                    'name' => 'Early Bird',
                    'description' => 'Early bird pricing for early registrants',
                    'price' => 199.00,
                    'quantity' => 100,
                    'available' => 100,
                ],
                [
                    'event_id' => $event->id,
                    'name' => 'Regular',
                    'description' => 'Regular admission ticket',
                    'price' => 299.00,
                    'quantity' => 200,
                    'available' => 200,
                ],
                [
                    'event_id' => $event->id,
                    'name' => 'VIP',
                    'description' => 'VIP access with premium benefits',
                    'price' => 499.00,
                    'quantity' => 50,
                    'available' => 50,
                ]
            ];

            foreach ($tickets as $ticketData) {
                \App\Models\Ticket::create($ticketData);
            }
        }
    }
}

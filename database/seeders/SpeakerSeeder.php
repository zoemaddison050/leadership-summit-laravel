<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SpeakerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $speakers = [
            [
                'name' => 'Dr. Sarah Johnson',
                'bio' => 'Dr. Sarah Johnson is a renowned leadership expert with over 20 years of experience in organizational development. She has authored several bestselling books on leadership and transformation.',
                'photo' => 'sarah-johnson.jpg',
                'position' => 'CEO',
                'company' => 'Leadership Dynamics Inc.',
            ],
            [
                'name' => 'Michael Chen',
                'bio' => 'Michael Chen is a technology executive and thought leader who has led digital transformations at Fortune 500 companies. He specializes in agile leadership and innovation management.',
                'photo' => 'michael-chen.jpg',
                'position' => 'CTO',
                'company' => 'TechForward Solutions',
            ],
            [
                'name' => 'Dr. Amanda Rodriguez',
                'bio' => 'Dr. Amanda Rodriguez is a leadership coach and consultant who focuses on developing inclusive leadership practices. She has worked with organizations worldwide to build diverse and effective teams.',
                'photo' => 'amanda-rodriguez.jpg',
                'position' => 'Principal Consultant',
                'company' => 'Inclusive Leadership Partners',
            ],
            [
                'name' => 'James Thompson',
                'bio' => 'James Thompson is a former military officer turned business leader. He brings unique insights on leadership under pressure and building high-performance teams.',
                'photo' => 'james-thompson.jpg',
                'position' => 'Managing Director',
                'company' => 'Strategic Leadership Group',
            ],
            [
                'name' => 'Lisa Park',
                'bio' => 'Lisa Park is an entrepreneur and startup advisor who has founded three successful companies. She specializes in leadership in fast-growing organizations and scaling teams.',
                'photo' => 'lisa-park.jpg',
                'position' => 'Founder & CEO',
                'company' => 'Innovation Ventures',
            ]
        ];

        foreach ($speakers as $speakerData) {
            $speaker = \App\Models\Speaker::create($speakerData);
        }

        // Create sample sessions and assign speakers
        $events = \App\Models\Event::all();

        foreach ($events as $event) {
            $sessions = [
                [
                    'event_id' => $event->id,
                    'title' => 'Keynote: The Future of Leadership',
                    'description' => 'Opening keynote exploring emerging trends in leadership and organizational development.',
                    'start_time' => $event->start_date,
                    'end_time' => $event->start_date->addHour(),
                    'location' => 'Main Auditorium',
                ],
                [
                    'event_id' => $event->id,
                    'title' => 'Building High-Performance Teams',
                    'description' => 'Interactive workshop on creating and managing high-performance teams in modern organizations.',
                    'start_time' => $event->start_date->addHours(2),
                    'end_time' => $event->start_date->addHours(3),
                    'location' => 'Workshop Room A',
                ],
                [
                    'event_id' => $event->id,
                    'title' => 'Leadership in Digital Transformation',
                    'description' => 'Panel discussion on leading organizations through digital transformation initiatives.',
                    'start_time' => $event->start_date->addHours(4),
                    'end_time' => $event->start_date->addHours(5),
                    'location' => 'Conference Room B',
                ]
            ];

            foreach ($sessions as $sessionData) {
                $session = \App\Models\Session::create($sessionData);

                // Assign random speakers to sessions
                $randomSpeakers = \App\Models\Speaker::inRandomOrder()->take(rand(1, 2))->get();
                $session->speakers()->attach($randomSpeakers);
            }
        }
    }
}

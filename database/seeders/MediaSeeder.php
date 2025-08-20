<?php

namespace Database\Seeders;

use App\Models\Media;
use App\Models\User;
use Illuminate\Database\Seeder;

class MediaSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $adminUser = User::where('email', 'admin@example.com')->first();

        if (!$adminUser) {
            return; // Skip if admin user doesn't exist
        }

        $mediaFiles = [
            [
                'name' => 'Leadership Summit Logo',
                'file_name' => 'leadership-summit-logo.png',
                'mime_type' => 'image/png',
                'path' => 'media/leadership-summit-logo.png',
                'size' => 45678,
                'disk' => 'public',
                'alt_text' => 'Leadership Summit official logo',
                'description' => 'The official logo of the Leadership Summit conference',
                'uploaded_by' => $adminUser->id,
            ],
            [
                'name' => 'Conference Hall Photo',
                'file_name' => 'conference-hall.jpg',
                'mime_type' => 'image/jpeg',
                'path' => 'media/conference-hall.jpg',
                'size' => 234567,
                'disk' => 'public',
                'alt_text' => 'Large conference hall with stage and seating',
                'description' => 'Main conference hall where keynote presentations take place',
                'uploaded_by' => $adminUser->id,
            ],
            [
                'name' => 'Speaker Guidelines',
                'file_name' => 'speaker-guidelines.pdf',
                'mime_type' => 'application/pdf',
                'path' => 'media/speaker-guidelines.pdf',
                'size' => 123456,
                'disk' => 'public',
                'alt_text' => null,
                'description' => 'Guidelines and requirements for conference speakers',
                'uploaded_by' => $adminUser->id,
            ],
            [
                'name' => 'Welcome Message Audio',
                'file_name' => 'welcome-message.mp3',
                'mime_type' => 'audio/mpeg',
                'path' => 'media/welcome-message.mp3',
                'size' => 567890,
                'disk' => 'public',
                'alt_text' => null,
                'description' => 'Welcome message from the conference organizers',
                'uploaded_by' => $adminUser->id,
            ],
            [
                'name' => 'Event Schedule Template',
                'file_name' => 'event-schedule-template.xlsx',
                'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'path' => 'media/event-schedule-template.xlsx',
                'size' => 89012,
                'disk' => 'public',
                'alt_text' => null,
                'description' => 'Template for creating event schedules',
                'uploaded_by' => $adminUser->id,
            ],
        ];

        foreach ($mediaFiles as $mediaData) {
            Media::create($mediaData);
        }
    }
}

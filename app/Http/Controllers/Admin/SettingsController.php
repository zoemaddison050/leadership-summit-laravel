<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = [
            'site_name' => config('app.name', 'Leadership Summit'),
            'site_description' => 'Join us for the exclusive International Global Leadership Academy Summit',
            'contact_email' => 'info@leadershipsummit.com',
            'contact_phone' => '+1 (555) 123-4567',
            'address' => 'Cypress International Conference Center, Cypress',
            'registration_enabled' => true,
            'payment_methods' => ['crypto', 'stripe'],
            'crypto_currencies' => ['BTC', 'ETH', 'USDT'],
            'max_registrations_per_event' => 500,
            'registration_deadline_days' => 7,
            'email_notifications' => true,
            'sms_notifications' => false,
            'maintenance_mode' => false
        ];

        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'site_name' => 'required|string|max:255',
            'site_description' => 'required|string|max:500',
            'contact_email' => 'required|email',
            'contact_phone' => 'required|string|max:20',
            'address' => 'required|string|max:255',
            'registration_enabled' => 'boolean',
            'payment_methods' => 'array',
            'crypto_currencies' => 'array',
            'max_registrations_per_event' => 'required|integer|min:1',
            'registration_deadline_days' => 'required|integer|min:0',
            'email_notifications' => 'boolean',
            'sms_notifications' => 'boolean',
            'maintenance_mode' => 'boolean'
        ]);

        // In a real application, you would save these to a settings table or config files
        // For now, we'll just cache them
        $settings = $request->except('_token', '_method');
        Cache::put('app_settings', $settings, now()->addDays(30));

        return redirect()->back()->with('success', 'Settings updated successfully.');
    }
}

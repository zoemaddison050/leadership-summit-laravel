<?php

use Illuminate\Support\Facades\Route;

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
    return view('welcome');
});

// Authentication Routes
Route::middleware(['auth'])->group(function () {
    // Dashboard
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});

// Event Routes
Route::prefix('events')->group(function () {
    Route::get('/', 'App\Http\Controllers\EventController@index')->name('events.index');
    Route::get('/{event}', 'App\Http\Controllers\EventController@show')->name('events.show');
});

// Speaker Routes
Route::prefix('speakers')->group(function () {
    Route::get('/', 'App\Http\Controllers\SpeakerController@index')->name('speakers.index');
    Route::get('/{speaker}', 'App\Http\Controllers\SpeakerController@show')->name('speakers.show');
});

// Registration Routes
Route::prefix('registration')->group(function () {
    Route::get('/{event}', 'App\Http\Controllers\RegistrationController@create')->name('registration.create');
    Route::post('/{event}', 'App\Http\Controllers\RegistrationController@store')->name('registration.store');
});

// Admin Routes
Route::prefix('admin')->middleware(['auth', 'admin'])->group(function () {
    // Admin Dashboard
    Route::get('/', 'App\Http\Controllers\Admin\DashboardController@index')->name('admin.dashboard');

    // Event Management
    Route::resource('events', 'App\Http\Controllers\Admin\EventController');

    // Speaker Management
    Route::resource('speakers', 'App\Http\Controllers\Admin\SpeakerController');

    // Session Management
    Route::resource('sessions', 'App\Http\Controllers\Admin\SessionController');

    // User Management
    Route::resource('users', 'App\Http\Controllers\Admin\UserController');
});

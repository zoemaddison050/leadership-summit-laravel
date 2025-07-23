<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Event API Routes
Route::prefix('events')->group(function () {
    Route::get('/', 'App\Http\Controllers\Api\EventController@index');
    Route::get('/{event}', 'App\Http\Controllers\Api\EventController@show');
});

// Speaker API Routes
Route::prefix('speakers')->group(function () {
    Route::get('/', 'App\Http\Controllers\Api\SpeakerController@index');
    Route::get('/{speaker}', 'App\Http\Controllers\Api\SpeakerController@show');
});

// Registration API Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/register/{event}', 'App\Http\Controllers\Api\RegistrationController@register');
    Route::get('/registrations', 'App\Http\Controllers\Api\RegistrationController@userRegistrations');
});

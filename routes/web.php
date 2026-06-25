<?php

use App\Http\Controllers\WeatherController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json(['status' => 'ok']);
});

Route::get('/weather/{city}', [WeatherController::class, 'show'])
    ->where('city', '[a-zA-Z\s\-]+'); // Only allows letters, spaces, and hyphens;

Route::get('/weather/{city}/cached', [WeatherController::class, 'cached'])
    ->where('city', '[a-zA-Z\s\-]+'); // Only allows letters, spaces, and hyphens;

// Route::fallback(function () {
//     return response()->json(['message' => 'Not Found.'], 404);
// });
<?php

use App\Http\Controllers\WeatherController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json(['status' => 'ok']);
});

Route::get('/weather/{city}', [WeatherController::class, 'show'])
    ->where('city', '[a-zA-Z\s\-]+');

Route::get('/weather/{city}/cached', [WeatherController::class, 'cached'])
    ->where('city', '[a-zA-Z\s\-]+');
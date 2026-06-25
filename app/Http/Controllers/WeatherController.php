<?php

namespace App\Http\Controllers;

use App\Exceptions\WeatherApiException;
use App\Services\OpenWeatherMapService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class WeatherController extends Controller
{
    public function __construct(
        private readonly OpenWeatherMapService $weather,
    ) {}

    public function show(string $city): JsonResponse
    {
        try { 
            $validator = Validator::make(['city' => $city], [
                'city' => 'required|string|min:2|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'The provided city name is invalid.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $cleanCity = urldecode($city);

            return $this->weatherResponse(fn () => $this->weather->currentWeather($cleanCity));
        } catch (Throwable $exception) {
            return response()->json([
                'message' => 'An unexpected error occurred.',
            ], 500);
        }
    }

    public function cached(string $city): JsonResponse
    {
        try {
            $validator = Validator::make(['city' => $city], [
                'city' => 'required|string|min:2|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'The provided city name is invalid.',
                    'errors' => $validator->errors()
                ], 422);
            }
            $cleanCity = urldecode($city);

            return $this->weatherResponse(fn () => $this->weather->cachedCurrentWeather($cleanCity));
            
        } catch (Throwable $exception) {
            return response()->json([
                'message' => 'An unexpected error occurred.',
            ], 500);
        }
    }

    private function weatherResponse(callable $callback): JsonResponse
    {
        try {
            return response()->json($callback());
        } catch (WeatherApiException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], $exception->statusCode);
        } catch (Throwable $exception) {
            return response()->json([
                'message' => 'An unexpected error occurred.',
            ], 500);
        }
    }
}

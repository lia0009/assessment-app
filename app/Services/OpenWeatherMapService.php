<?php

namespace App\Services;

use App\Exceptions\WeatherApiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class OpenWeatherMapService
{
    private const CACHE_TTL_MINUTES = 10;

    public function currentWeather(string $city): array
    {
        return [
            ...$this->fetchWeather($city),
            'source' => 'external',
        ];
    }

    public function cachedCurrentWeather(string $city): array
    {
        $cacheKey = $this->cacheKey($city);

        if (Cache::has($cacheKey)) {
            return [
                ...Cache::get($cacheKey),
                'source' => 'cache',
            ];
        }

        $weather = $this->fetchWeather($city);

        Cache::put($cacheKey, $weather, now()->addMinutes(self::CACHE_TTL_MINUTES));

        return [
            ...$weather,
            'source' => 'external',
        ];
    }

    private function fetchWeather(string $city): array
    {
        $apiKey = config('services.openweathermap.key');

        if (blank($apiKey)) {
            throw new WeatherApiException('OpenWeatherMap API key is not configured.', 500);
        }

        try {
            $response = Http::acceptJson()
                ->timeout(10)
                ->get(config('services.openweathermap.url'), [
                    'q' => $city,
                    'appid' => $apiKey,
                    'units' => config('services.openweathermap.units', 'metric'),
                ]);
        } catch (ConnectionException) {
            throw new WeatherApiException('Unable to connect to the weather provider.');
        }

        if ($response->failed()) {
            throw new WeatherApiException(
                $this->errorMessageForStatus($response->status()),
                $response->status() === 404 ? 404 : 502,
            );
        }

        $payload = $response->json();

        if (! is_array($payload) || ! isset($payload['name'], $payload['main']['temp'], $payload['weather'][0]['description'])) {
            throw new WeatherApiException('Weather provider returned an unexpected response.');
        }

        return [
            'city' => $payload['name'],
            'temperature' => $payload['main']['temp'],
            'weather_description' => $payload['weather'][0]['description'],
            'timestamp' => $this->timestampFromPayload($payload),
        ];
    }

    private function timestampFromPayload(array $payload): string
    {
        if (isset($payload['dt']) && is_numeric($payload['dt'])) {
            return Carbon::createFromTimestamp((int) $payload['dt'])->toIso8601String();
        }

        return now()->toIso8601String();
    }

    private function errorMessageForStatus(int $status): string
    {
        return match ($status) {
            401 => 'Weather provider rejected the configured API key.',
            404 => 'City not found.',
            default => 'Weather provider request failed.',
        };
    }

    private function cacheKey(string $city): string
    {
        return 'weather.'.Str::slug(Str::lower($city));
    }
}

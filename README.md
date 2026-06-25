# Weather Assessment App

Laravel API that exposes current weather data from OpenWeatherMap with an optional 10-minute cache.

## Requirements

- PHP 8.2+
- Composer
- OpenWeatherMap API key

## Setup

Install dependencies:

```bash
composer install
```

Create your environment file and app key:

```bash
cp .env.example .env
php artisan key:generate
```

Set your OpenWeatherMap key in `.env`:

```env
OPENWEATHERMAP_API_KEY=your-openweathermap-api-key
OPENWEATHERMAP_UNITS=metric
```

Run the app:

```bash
php artisan serve
```

## Endpoints

Fetch live weather data:

```http
GET /weather/{city}
```

Fetch weather data with a 10-minute cache:

```http
GET /weather/{city}/cached
```

Successful responses include:

```json
{
  "city": "Manila",
  "temperature": 30.5,
  "weather_description": "scattered clouds",
  "timestamp": "2026-06-24T10:40:00+00:00",
  "source": "external"
}
```

The cached endpoint returns `"source": "external"` on the first request and `"source": "cache"` when the same city is served from cache.

## Tests

Run the automated tests:

```bash
php artisan test
```

The feature tests fake OpenWeatherMap responses, verify the response shape, check the cache source behavior, and cover a not-found error from the external provider.

## Approach

The controller handles HTTP responses, while `App\Services\OpenWeatherMapService` owns the OpenWeatherMap integration, response mapping, and cache logic. External-service failures are converted into clear JSON error messages with appropriate HTTP status codes.

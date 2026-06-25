<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WeatherApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.openweathermap.key' => 'test-api-key',
            'services.openweathermap.url' => 'https://api.openweathermap.org/data/2.5/weather',
            'services.openweathermap.units' => 'metric',
        ]);

        Cache::flush();
    }

    public function test_weather_endpoint_returns_external_weather_data(): void
    {
        Http::fake([
            '*' => Http::response($this->weatherPayload(), 200),
        ]);

        $response = $this->getJson('/weather/Manila');

        $response
            ->assertOk()
            ->assertJsonPath('city', 'Manila')
            ->assertJsonPath('temperature', 30.5)
            ->assertJsonPath('weather_description', 'scattered clouds')
            ->assertJsonPath('timestamp', '2026-06-24T10:40:00+00:00')
            ->assertJsonPath('source', 'external');

        Http::assertSent(fn ($request) => $request['q'] === 'Manila'
            && $request['appid'] === 'test-api-key'
            && $request['units'] === 'metric');
    }

    public function test_cached_weather_endpoint_serves_repeated_request_from_cache(): void
    {
        Http::fake([
            '*' => Http::response($this->weatherPayload(), 200),
        ]);

        $this->getJson('/weather/Manila/cached')
            ->assertOk()
            ->assertJsonPath('source', 'external');

        $this->getJson('/weather/Manila/cached')
            ->assertOk()
            ->assertJsonPath('source', 'cache')
            ->assertJsonPath('city', 'Manila');

        Http::assertSentCount(1);
    }

    public function test_cached_weather_endpoint_reuses_cache_for_equivalent_city_names(): void
    {
        Http::fake([
            '*' => Http::response($this->weatherPayload('New York'), 200),
        ]);

        $this->getJson('/weather/New%20York/cached')
            ->assertOk()
            ->assertJsonPath('source', 'external')
            ->assertJsonPath('city', 'New York');

        $this->getJson('/weather/new-york/cached')
            ->assertOk()
            ->assertJsonPath('source', 'cache')
            ->assertJsonPath('city', 'New York');

        Http::assertSentCount(1);
    }

    public function test_cached_weather_endpoint_does_not_cache_failed_provider_response(): void
    {
        Http::fakeSequence()
            ->push(['message' => 'city not found'], 404)
            ->push($this->weatherPayload(), 200);

        $this->getJson('/weather/UnknownCity/cached')
            ->assertNotFound()
            ->assertJsonPath('message', 'City not found.');

        $this->getJson('/weather/UnknownCity/cached')
            ->assertOk()
            ->assertJsonPath('source', 'external')
            ->assertJsonPath('city', 'Manila');

        Http::assertSentCount(2);
    }

    public function test_cached_weather_endpoint_returns_404_for_invalid_city_name(): void
    {
        $this->getJson('/weather/M@n1l$!/cached')
            ->assertStatus(404)
            ->assertJsonPath('message', 'Not Found.');
    }

    public function test_weather_endpoint_returns_clear_error_when_city_is_not_found(): void
    {
        Http::fake([
            '*' => Http::response(['message' => 'city not found'], 404),
        ]);

        $this->getJson('/weather/UnknownCity')
            ->assertNotFound()
            ->assertJsonPath('message', 'City not found.');
    }

    public function test_non_existent_route_returns_404(): void
    {
        $this->getJson('/non-existent-route')
            ->assertNotFound()
            ->assertJsonPath('message', 'Not Found.');
    }

    public function test_weather_endpoint_returns_404_for_invalid_city_name(): void
    {
        $this->getJson('/weather/1')
            ->assertStatus(404)
            ->assertJsonPath('message', 'Not Found.');
    }

    public function test_weather_endpoint_returns_404_for_invalid_city_name_with_special_chara(): void
    {
        $this->getJson('/weather/M@n1l$!')
            ->assertStatus(404)
            ->assertJsonPath('message', 'Not Found.');
    }

    public function test_weather_endpoint_returns_404_for_slash_in_city_name(): void
    {
        $this->getJson('/weather/New/York')
            ->assertStatus(404)
            ->assertJsonPath('message', 'Not Found.');
    }

    private function weatherPayload(string $city = 'Manila'): array
    {
        return [
            'name' => $city,
            'dt' => 1782297600,
            'main' => [
                'temp' => 30.5,
            ],
            'weather' => [
                [
                    'description' => 'scattered clouds',
                ],
            ],
        ];
    }
}

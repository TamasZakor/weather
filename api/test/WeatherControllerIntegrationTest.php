<?php

declare(strict_types=1);

namespace ADS\TakeHome\Tests;

use ADS\TakeHome\Controllers\WeatherController;
use ADS\TakeHome\Framework\HttpRequest;
use ADS\TakeHome\Framework\JsonHttpResponse;
use ADS\TakeHome\Models\City;
use ADS\TakeHome\Models\DailyWeatherRecord;
use ADS\TakeHome\Repositories\WeatherRepository;
use ADS\TakeHome\Services\WeatherService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Kontroller és szerviz réteg integrációs tesztjei (HTTP kérések és válaszok).
 */
class WeatherControllerIntegrationTest extends TestCase
{
    #[Test]
    public function getMetaReturns200AndMetadataStructureWithCacheHeaders(): void
    {
        $mockRepo = $this->createMock(WeatherRepository::class);
        $mockRepo->method("getAllCities")->willReturn([
            new City(1, "Budapest", "Közép-Magyarország"),
        ]);
        $mockRepo->method("getRegions")->willReturn([
            "Dunántúl", "Közép-Magyarország", "Észak és Alföld",
        ]);
        $mockRepo->method("getDateRange")->willReturn([
            "minDate" => "1901-01-01",
            "maxDate" => "2023-12-31",
        ]);

        $service = new WeatherService($mockRepo);
        $controller = new WeatherController($service);

        $request = new HttpRequest(query: []);
        $response = $controller->getMeta($request);

        $this->assertInstanceOf(JsonHttpResponse::class, $response);
        $this->assertSame(200, $response->getStatus());

        $headers = $response->getHeaders();
        $this->assertArrayHasKey("Cache-Control", $headers);
        $this->assertStringContainsString("max-age=86400", $headers["Cache-Control"]);

        $data = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey("cities", $data);
        $this->assertArrayHasKey("regions", $data);
        $this->assertArrayHasKey("dateRange", $data);
        $this->assertSame("Budapest", $data["cities"][0]["name"]);
    }

    #[Test]
    public function getCityWeatherReturns200OnValidRequest(): void
    {
        $mockRepo = $this->createMock(WeatherRepository::class);
        $mockRepo->method("getCityWeatherData")->willReturn([
            new DailyWeatherRecord("1901-01-01", -5.0, 1.0, -2.0, 0.0),
        ]);

        $service = new WeatherService($mockRepo);
        $controller = new WeatherController($service);

        $request = new HttpRequest(query: [
            "cityId" => "1",
            "startDate" => "1901-01-01",
            "endDate" => "1901-01-05",
        ]);

        $response = $controller->getCityWeather($request);

        $this->assertSame(200, $response->getStatus());
        $records = json_decode((string) $response->getBody(), true);
        $this->assertIsArray($records);
        $this->assertCount(1, $records);
        $this->assertSame("1901-01-01", $records[0]["date"]);
    }

    #[Test]
    public function getCityWeatherReturns400OnInvalidOrMissingCityId(): void
    {
        $mockRepo = $this->createMock(WeatherRepository::class);
        $service = new WeatherService($mockRepo);
        $controller = new WeatherController($service);

        // Hiányzó cityId
        $request = new HttpRequest(query: []);
        $response = $controller->getCityWeather($request);

        $this->assertSame(400, $response->getStatus());
        $body = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey("error", $body);
        $this->assertStringContainsString("Város", $body["error"]);
    }

    #[Test]
    public function getRegionWeatherReturns400OnInvalidRegion(): void
    {
        $mockRepo = $this->createMock(WeatherRepository::class);
        $service = new WeatherService($mockRepo);
        $controller = new WeatherController($service);

        $request = new HttpRequest(query: [
            "region" => "IsmeretlenRegion",
            "startDate" => "1901-01-01",
            "endDate" => "1901-01-05",
        ]);

        $response = $controller->getRegionWeather($request);

        $this->assertSame(400, $response->getStatus());
        $body = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey("error", $body);
        $this->assertStringContainsString("Régió", $body["error"]);
    }

    #[Test]
    public function getRegionWeatherReturns200WithAggregatedRecords(): void
    {
        $mockRepo = $this->createMock(WeatherRepository::class);
        $mockRepo->method("getRegionWeatherData")->willReturn([
            new DailyWeatherRecord("1920-05-01", -1.0, 8.5, 3.75, 2.3),
            new DailyWeatherRecord("1920-05-02", 0.0, 10.0, 5.0, 0.0),
        ]);

        $service = new WeatherService($mockRepo);
        $controller = new WeatherController($service);

        $request = new HttpRequest(query: [
            "region" => "Dunántúl",
            "startDate" => "1920-05-01",
            "endDate" => "1920-05-02",
        ]);

        $response = $controller->getRegionWeather($request);

        $this->assertSame(200, $response->getStatus());
        $records = json_decode((string) $response->getBody(), true);
        $this->assertIsArray($records);
        $this->assertCount(2, $records);
        $this->assertSame("1920-05-01", $records[0]["date"]);
        $this->assertSame(3.75, $records[0]["avgTemp"]);
    }

    #[Test]
    public function getCityWeatherReturns200WithCacheControlHeader(): void
    {
        $mockRepo = $this->createMock(WeatherRepository::class);
        $mockRepo->method("getCityWeatherData")->willReturn([
            new DailyWeatherRecord("1901-01-01", -3.0, 2.0, -0.5, 1.1),
        ]);

        $service = new WeatherService($mockRepo);
        $controller = new WeatherController($service);

        $request = new HttpRequest(query: [
            "cityId" => "2",
            "startDate" => "1901-01-01",
            "endDate" => "1901-01-01",
        ]);

        $response = $controller->getCityWeather($request);

        $this->assertSame(200, $response->getStatus());
        $headers = $response->getHeaders();
        $this->assertArrayHasKey("Cache-Control", $headers);
        $this->assertStringContainsString("immutable", $headers["Cache-Control"]);
    }

    #[Test]
    public function getCityWeatherReturns400WhenCityIdIsZero(): void
    {
        $mockRepo = $this->createMock(WeatherRepository::class);
        $service = new WeatherService($mockRepo);
        $controller = new WeatherController($service);

        $request = new HttpRequest(query: ["cityId" => "0"]);
        $response = $controller->getCityWeather($request);

        $this->assertSame(400, $response->getStatus());
        $body = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey("error", $body);
    }

    #[Test]
    public function getCityWeatherReturns200WithEmptyArrayWhenNoDataFound(): void
    {
        $mockRepo = $this->createMock(WeatherRepository::class);
        $mockRepo->method("getCityWeatherData")->willReturn([]);

        $service = new WeatherService($mockRepo);
        $controller = new WeatherController($service);

        $request = new HttpRequest(query: [
            "cityId" => "1",
            "startDate" => "1901-01-01",
            "endDate" => "1901-01-05",
        ]);

        $response = $controller->getCityWeather($request);

        $this->assertSame(200, $response->getStatus());
        $records = json_decode((string) $response->getBody(), true);
        $this->assertIsArray($records);
        $this->assertCount(0, $records);
    }
}

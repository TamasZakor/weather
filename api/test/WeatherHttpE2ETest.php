<?php

declare(strict_types=1);

namespace ADS\TakeHome\Tests;

use ADS\TakeHome\Tests\Support\CurlHttpClient;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Teljes körű HTTP End-to-End tesztek.
 * A teljes stack-et teszteli élő webszerveren (HTTP -> Router -> Controller -> Service -> Repository -> MySQL -> Response).
 */
class WeatherHttpE2ETest extends TestCase
{
    private CurlHttpClient $client;

    protected function setUp(): void
    {
        $this->client = new CurlHttpClient("http://localhost");
    }

    #[Test]
    public function rootStatusEndpointReturns200AndApiInfo(): void
    {
        $res = $this->client->get("/");

        $this->assertSame(200, $res["status"]);
        $data = json_decode($res["body"], true);
        $this->assertIsArray($data);
        $this->assertSame("online", $data["status"]);
        $this->assertArrayHasKey("endpoints", $data);
    }

    #[Test]
    public function metaEndpointReturns200WithHeadersAndPayload(): void
    {
        $res = $this->client->get("/api/meta");

        $this->assertSame(200, $res["status"]);

        // Biztonsági fejlécek ellenőrzése
        $this->assertArrayHasKey("X-Content-Type-Options", $res["headers"]);
        $this->assertSame("nosniff", $res["headers"]["X-Content-Type-Options"]);
        $this->assertArrayHasKey("X-Frame-Options", $res["headers"]);
        $this->assertSame("SAMEORIGIN", $res["headers"]["X-Frame-Options"]);

        // Cache és Content-Type fejlécek ellenőrzése
        $this->assertArrayHasKey("Cache-Control", $res["headers"]);
        $this->assertStringContainsString("max-age=86400", $res["headers"]["Cache-Control"]);
        $this->assertArrayHasKey("Content-Type", $res["headers"]);
        $this->assertStringContainsString("application/json", $res["headers"]["Content-Type"]);

        $data = json_decode($res["body"], true);
        $this->assertArrayHasKey("cities", $data);
        $this->assertArrayHasKey("regions", $data);
        $this->assertArrayHasKey("dateRange", $data);
        $this->assertNotEmpty($data["cities"]);
        $this->assertCount(3, $data["regions"]);
        $this->assertSame("1901-01-01", $data["dateRange"]["minDate"]);
    }

    #[Test]
    public function cityWeatherEndpointReturnsDailyData(): void
    {
        $res = $this->client->get("/api/weather/city", [
            "cityId" => "1",
            "startDate" => "1901-01-01",
            "endDate" => "1901-01-03",
        ]);

        $this->assertSame(200, $res["status"]);
        $this->assertArrayHasKey("Cache-Control", $res["headers"]);

        $records = json_decode($res["body"], true);
        $this->assertIsArray($records);
        $this->assertCount(3, $records);

        $first = $records[0];
        $this->assertSame("1901-01-01", $first["date"]);
        $this->assertArrayHasKey("minTemp", $first);
        $this->assertArrayHasKey("maxTemp", $first);
        $this->assertArrayHasKey("avgTemp", $first);
        $this->assertArrayHasKey("precipitation", $first);
    }

    #[Test]
    public function cityWeatherMissingCityIdReturns400(): void
    {
        $res = $this->client->get("/api/weather/city");

        $this->assertSame(400, $res["status"]);
        $data = json_decode($res["body"], true);
        $this->assertArrayHasKey("error", $data);
        $this->assertStringContainsString("Város", $data["error"]);
    }

    #[Test]
    public function regionWeatherEndpointReturnsAggregatedData(): void
    {
        $res = $this->client->get("/api/weather/region", [
            "region" => "Dunántúl",
            "startDate" => "1901-01-01",
            "endDate" => "1901-01-02",
        ]);

        $this->assertSame(200, $res["status"]);
        $records = json_decode($res["body"], true);
        $this->assertIsArray($records);
        $this->assertCount(2, $records);
        $this->assertSame("1901-01-01", $records[0]["date"]);
    }

    #[Test]
    public function regionWeatherInvalidRegionReturns400(): void
    {
        $res = $this->client->get("/api/weather/region", [
            "region" => "ErvenytelenRegio",
        ]);

        $this->assertSame(400, $res["status"]);
        $data = json_decode($res["body"], true);
        $this->assertArrayHasKey("error", $data);
        $this->assertStringContainsString("Régió", $data["error"]);
    }

    #[Test]
    public function notFoundEndpointReturns404(): void
    {
        $res = $this->client->get("/nem-letezo-utvonal");

        $this->assertSame(404, $res["status"]);
        $data = json_decode($res["body"], true);
        $this->assertArrayHasKey("error", $data);
    }

    #[Test]
    public function openApiAndDocsEndpointsReturnSuccess(): void
    {
        $openApi = $this->client->get("/openapi.json");
        $this->assertSame(200, $openApi["status"]);
        $this->assertStringContainsString("openapi", $openApi["body"]);

        $docs = $this->client->get("/docs");
        $this->assertSame(200, $docs["status"]);
        $this->assertStringContainsString("SwaggerUIBundle", $docs["body"]);
    }
}

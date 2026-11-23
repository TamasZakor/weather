<?php

declare(strict_types=1);

namespace ADS\TakeHome\Tests;

use ADS\TakeHome\Framework\HttpRequest;
use ADS\TakeHome\Framework\JsonHttpResponse;
use ADS\TakeHome\Framework\Router;
use ADS\TakeHome\Requests\WeatherQuery;
use ADS\TakeHome\Tests\Support\CurlHttpClient;
use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Speciális és kevésbé ismert határesetek tesztelése:
 *  - HTTP 405 Method Not Allowed
 *  - Szökőnap (Február 29) és naptári évforduló határesetek
 *  - URL kódolás és ékezetes karakterek kezelése
 *  - Biztonsági hibaszivárgás védelem (Information Disclosure)
 *  - Paraméter-injekció kísérletek lekezelése
 */
class WeatherAdvancedHttpAndEdgeCasesTest extends TestCase
{
    private CurlHttpClient $client;

    protected function setUp(): void
    {
        $this->client = new CurlHttpClient("http://localhost");
    }

    #[Test]
    public function postMethodOnGetEndpointsReturns405MethodNotAllowed(): void
    {
        $endpoints = ["/api/meta", "/api/weather/city", "/api/weather/region"];

        foreach ($endpoints as $endpoint) {
            $res = $this->client->request("POST", $endpoint);
            $this->assertSame(405, $res["status"], "POST metódusra 405 kell érkezzen a {$endpoint} végponton.");

            $data = json_decode($res["body"], true);
            $this->assertIsArray($data);
            $this->assertArrayHasKey("error", $data);
            $this->assertStringContainsString("POST nem engedélyezett", $data["error"]);
        }
    }

    #[Test]
    public function deleteAndPutMethodsReturn405MethodNotAllowed(): void
    {
        $resDelete = $this->client->request("DELETE", "/api/weather/city");
        $this->assertSame(405, $resDelete["status"]);

        $resPut = $this->client->request("PUT", "/api/meta");
        $this->assertSame(405, $resPut["status"]);
    }

    #[Test]
    public function leapYearFebruary29IsAcceptedAndRetrievedCorrectly(): void
    {
        // 1904 szökőév, február 29. létező nap a naptárban
        $query = WeatherQuery::forCity([
            "cityId" => "1",
            "startDate" => "1904-02-28",
            "endDate" => "1904-03-01",
        ]);

        $this->assertSame(19040228, $query->startDate);
        $this->assertSame(19040301, $query->endDate);

        // Élő HTTP kérésben is ellenőrzöm, hogy a február 29. szerepel a válaszban
        $res = $this->client->get("/api/weather/city", [
            "cityId" => "1",
            "startDate" => "1904-02-28",
            "endDate" => "1904-03-01",
        ]);

        $this->assertSame(200, $res["status"]);
        $records = json_decode($res["body"], true);
        $this->assertIsArray($records);
        $this->assertCount(3, $records);

        $dates = array_column($records, "date");
        $this->assertContains("1904-02-29", $dates, "A szökőnapi rekordnak léteznie kell.");
    }

    #[Test]
    public function newYearBoundaryCrossingWorksSeamlessly(): void
    {
        // Évforduló átlépés: 1950. dec. 30. - 1951. jan. 2.
        $res = $this->client->get("/api/weather/city", [
            "cityId" => "2",
            "startDate" => "1950-12-30",
            "endDate" => "1951-01-02",
        ]);

        $this->assertSame(200, $res["status"]);
        $records = json_decode($res["body"], true);
        $this->assertCount(4, $records);
        $this->assertSame("1950-12-30", $records[0]["date"]);
        $this->assertSame("1951-01-02", $records[3]["date"]);
    }

    #[Test]
    public function urlEncodedRegionNameIsHandledCorrectly(): void
    {
        // Kifejezetten nyers URL-kódolással küldjük a magyar ékezetes régiónevet
        $encodedRegion = rawurlencode("Észak és Alföld");
        $res = $this->client->get("/api/weather/region?region=" . $encodedRegion . "&startDate=1901-01-01&endDate=1901-01-02");

        $this->assertSame(200, $res["status"]);
        $records = json_decode($res["body"], true);
        $this->assertIsArray($records);
        $this->assertCount(2, $records);
    }

    #[Test]
    public function sqlInjectionAttemptsAreSafelyRejected(): void
    {
        // Injekciós kísérlet a cityId-ban
        $res = $this->client->get("/api/weather/city", [
            "cityId" => "1' OR '1'='1",
        ]);

        $this->assertSame(400, $res["status"]);
        $data = json_decode($res["body"], true);
        $this->assertArrayHasKey("error", $data);
        $this->assertStringContainsString("Város", $data["error"]);
    }

    #[Test]
    public function routerDoesNotLeakSensitiveExceptionDetailsInProdMode(): void
    {
        // Szigorú regressziós egységteszt a Router hibaszivárgás-védelmére
        $router = new Router();
        $router->addRoute("GET", "/simulate-fatal-error", static function (HttpRequest $req): JsonHttpResponse {
            throw new Exception("Szigorúan titkos adatbázis jelszó és belső hiba.");
        });

        // Szimuláljuk a környezetet prod módban
        $previousEnv = getenv("APP_ENV");
        putenv("APP_ENV=prod");

        $_SERVER["REQUEST_METHOD"] = "GET";
        $_SERVER["REQUEST_URI"] = "/simulate-fatal-error";

        ob_start();
        $router->handleRequest();
        $output = (string) ob_get_clean();

        putenv($previousEnv !== false ? "APP_ENV={$previousEnv}" : "APP_ENV");

        $this->assertNotEmpty($output);
        $data = json_decode($output, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey("error", $data);
        $this->assertSame("Belső szerverhiba történt.", $data["error"]);

        // Nem szivároghat ki sem a belső kivétel üzenet, sem a stack trace!
        $this->assertArrayNotHasKey("message", $data);
        $this->assertArrayNotHasKey("stackTrace", $data);
        $this->assertStringNotContainsString("Szigorúan titkos adatbázis", $output);
    }
}

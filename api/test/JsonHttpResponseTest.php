<?php

declare(strict_types=1);

namespace ADS\TakeHome\Tests;

use ADS\TakeHome\Framework\HttpResponseInterface;
use ADS\TakeHome\Framework\JsonHttpResponse;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * A JsonHttpResponse válaszobjektum viselkedésének teljes lefedése.
 *
 * Lefedi:
 *  - getStatus() minden tipikus HTTP kódra
 *  - getHeaders() egyedi és üres fejlécek
 *  - getBody() JSON szöveg kódolás (array, object, scalar, null)
 *  - JSON_UNESCAPED_UNICODE (ékezetes karakterek nem escape-elve)
 *  - JSON_UNESCAPED_SLASHES (perjel nem escape-elve)
 *  - json_encode() hiba esetén hibaüzenet visszaadása
 *  - Implementálja-e a HttpResponseInterface-t
 */
class JsonHttpResponseTest extends TestCase
{
    #[Test]
    public function implementsHttpResponseInterface(): void
    {
        $response = new JsonHttpResponse([]);

        $this->assertInstanceOf(HttpResponseInterface::class, $response);
    }

    #[Test]
    public function getStatusReturnsConstructorValue(): void
    {
        $this->assertSame(200, (new JsonHttpResponse([], 200))->getStatus());
        $this->assertSame(400, (new JsonHttpResponse([], 400))->getStatus());
        $this->assertSame(404, (new JsonHttpResponse([], 404))->getStatus());
        $this->assertSame(500, (new JsonHttpResponse([], 500))->getStatus());
    }

    #[Test]
    public function getStatusDefaultsTo200(): void
    {
        $response = new JsonHttpResponse(["ok" => true]);

        $this->assertSame(200, $response->getStatus());
    }

    #[Test]
    public function getHeadersReturnsEmptyArrayByDefault(): void
    {
        $response = new JsonHttpResponse(["data" => 1]);

        $this->assertSame([], $response->getHeaders());
    }

    #[Test]
    public function getHeadersReturnsConstructorHeaders(): void
    {
        $headers = [
            "Cache-Control" => "public, max-age=86400, immutable",
            "X-Content-Type-Options" => "nosniff",
            "X-Frame-Options" => "SAMEORIGIN",
        ];

        $response = new JsonHttpResponse([], 200, $headers);

        $this->assertSame($headers, $response->getHeaders());
        $this->assertSame("public, max-age=86400, immutable", $response->getHeaders()["Cache-Control"]);
        $this->assertSame("nosniff", $response->getHeaders()["X-Content-Type-Options"]);
        $this->assertSame("SAMEORIGIN", $response->getHeaders()["X-Frame-Options"]);
    }

    #[Test]
    public function getBodyEncodesArrayAsJson(): void
    {
        $response = new JsonHttpResponse(["foo" => "bar", "num" => 42]);

        $this->assertSame('{"foo":"bar","num":42}', $response->getBody());
    }

    #[Test]
    public function getBodyEncodesNestedStructure(): void
    {
        $data = [
            "cities" => [
                ["id" => 1, "name" => "Budapest"],
            ],
            "dateRange" => ["minDate" => "1901-01-01", "maxDate" => "2023-12-31"],
        ];

        $body = (new JsonHttpResponse($data))->getBody();
        $this->assertIsString($body);
        $decoded = json_decode($body, true);

        $this->assertSame("Budapest", $decoded["cities"][0]["name"]);
        $this->assertSame("1901-01-01", $decoded["dateRange"]["minDate"]);
    }

    #[Test]
    public function getBodyDoesNotEscapeUnicodeCharacters(): void
    {
        // JSON_UNESCAPED_UNICODE: ékezetes karakterek ne legyenek \uXXXX alakban
        $response = new JsonHttpResponse(["region" => "Közép-Magyarország"]);

        $body = $response->getBody();
        $this->assertIsString($body);

        $this->assertStringContainsString("Közép-Magyarország", $body);
        $this->assertStringNotContainsString("\\u", $body);
    }

    #[Test]
    public function getBodyDoesNotEscapeForwardSlashes(): void
    {
        // JSON_UNESCAPED_SLASHES: a perjel ne legyen \/ alakban kódolva
        $response = new JsonHttpResponse(["url" => "https://example.com/api/v1"]);

        $body = $response->getBody();
        $this->assertIsString($body);

        $this->assertStringContainsString("https://example.com/api/v1", $body);
        $this->assertStringNotContainsString("\\/", $body);
    }

    #[Test]
    public function getBodyEncodesNullBodyAsJsonNull(): void
    {
        $response = new JsonHttpResponse(null);

        $this->assertSame("null", $response->getBody());
    }

    #[Test]
    public function getBodyEncodesBooleanValues(): void
    {
        $this->assertSame("true", (new JsonHttpResponse(true))->getBody());
        $this->assertSame("false", (new JsonHttpResponse(false))->getBody());
    }

    #[Test]
    public function getBodyEncodesEmptyArrayAsJsonEmptyArray(): void
    {
        $response = new JsonHttpResponse([]);

        $this->assertSame("[]", $response->getBody());
    }

    #[Test]
    public function getBodyReturnsErrorMessageOnJsonEncodeFailure(): void
    {
        // INF és NAN értékek JSON_THROW_ON_ERROR nélkül json_encode() false-t adnak vissza
        $response = new JsonHttpResponse(INF);

        $body = $response->getBody();

        // A hibás esetben hibaüzenetet kell visszaadni, nem üres stringet / null-t
        $this->assertIsString($body);
        $this->assertStringContainsString("json_encode() hiba", $body);
    }
}

<?php

declare(strict_types=1);

namespace ADS\TakeHome\Framework;

use Closure;
use Exception;

/**
 * Egyszerű HTTP router.
 */
class Router
{
    /**
     * @var array<string, array<string, Closure(HttpRequest):HttpResponseInterface>>
     */
    private array $routeGroups = [];

    /**
     * Regisztrál egy új végpontot.
     *
     * Az egyszerűség kedvéért most path paramétereket nem kezelünk, csak
     * pontos egyezőséget figyelünk.
     *
     * @param string $method HTTP metódus, pl. "GET", "POST"
     * @param string $path A végpont elérési útja, az URL path része, pl. "/hello"
     * @param Closure(HttpRequest):HttpResponseInterface $handler
     */
    public function addRoute(string $method, string $path, Closure $handler): void
    {
        $routeGroup = $this->routeGroups[$path] ?? [];
        $routeGroup[$method] = $handler;
        $this->routeGroups[$path] = $routeGroup;
    }

    /**
     * Elvégzi a HTTP kérés feldolgozását és a válasz visszaküldését.
     */
    public function handleRequest(): void
    {
        $method = $_SERVER["REQUEST_METHOD"];
        $route = $_SERVER["REQUEST_URI"];

        // A REQUEST_URI-ban a query string is benne van, köszi PHP.
        [$path] = explode("?", $route);
        $path = rawurldecode($path);

        // Ha fordított proxy mögül érkezik /api/v1 prefixszel
        if (str_starts_with($path, "/api/v1")) {
            $path = substr($path, strlen("/api/v1"));
            if ($path === "" || !str_starts_with($path, "/")) {
                $path = "/" . $path;
            }
        }

        $routeGroup = $this->routeGroups[$path] ?? null;
        if ($routeGroup === null) {
            $res = new JsonHttpResponse(["error" => "nem található"], 404);
            $this->sendResponse($res);
            return;
        }
        $handler = $routeGroup[$method] ?? null;
        if ($handler === null) {
            $res = new JsonHttpResponse(["error" => "{$method} nem engedélyezett"], 405);
            $this->sendResponse($res);
            return;
        }
        $request = HttpRequest::fromGlobals();
        try {
            $res = $handler($request);
        } catch (Exception $e) {
            // Szerveroldali hibanaplóba írás a konzol/naplófájl számára
            error_log("Szerveroldali hiba: " . $e->getMessage() . "\n" . $e->getTraceAsString());

            $isDev = (getenv("APP_ENV") ?: "prod") === "dev";
            $errorPayload = ["error" => "Belső szerverhiba történt."];

            // Csak explicit dev módban szivárogtatunk ki részletes kivétel-információkat
            if ($isDev) {
                $errorPayload["message"] = $e->getMessage();
                $errorPayload["stackTrace"] = $e->getTraceAsString();
            }

            $res = new JsonHttpResponse($errorPayload, 500);
        }
        $this->sendResponse($res);
    }

    private function sendResponse(HttpResponseInterface $response): void
    {
        http_response_code($response->getStatus());

        // Alapvető biztonsági fejlécek
        header("X-Content-Type-Options: nosniff");
        header("X-Frame-Options: SAMEORIGIN");

        // Alapértelmezett Content-Type JSON válaszokhoz
        if ($response instanceof JsonHttpResponse) {
            header("Content-Type: application/json; charset=utf-8");
        }

        // Egyedi response fejlécek kiküldése (pl. Cache-Control)
        foreach ($response->getHeaders() as $name => $value) {
            header("{$name}: {$value}");
        }

        $body = $response->getBody();
        if ($body !== null) {
            echo $body;
        }
    }
}

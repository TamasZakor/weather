<?php

namespace ADS\TakeHome;

include __DIR__ . "/../vendor/autoload.php";

use ADS\TakeHome\Controllers\ExampleController;
use ADS\TakeHome\Controllers\WeatherController;
use ADS\TakeHome\Framework\HtmlHttpResponse;
use ADS\TakeHome\Framework\HttpRequest;
use ADS\TakeHome\Framework\HttpResponseInterface;
use ADS\TakeHome\Framework\JsonHttpResponse;
use ADS\TakeHome\Framework\MySQL;
use ADS\TakeHome\Framework\Router;
use ADS\TakeHome\Models\AbstractModel;
use ADS\TakeHome\Repositories\WeatherRepository;
use ADS\TakeHome\Services\WeatherService;

$router = new Router();

$mysql = new MySQL("db", "weather", "root", "root");
AbstractModel::setDatabase($mysql);

$helloController = new ExampleController(greeting: "Hello", db: $mysql);

$weatherRepository = new WeatherRepository();
$weatherService = new WeatherService($weatherRepository);
$weatherController = new WeatherController($weatherService);

// Alap API üdvözlő / status végpont
$router->addRoute("GET", "/", static fn (HttpRequest $request): JsonHttpResponse => new JsonHttpResponse([
    "name" => "Adiumsoft Weather Service API",
    "version" => "1.0.0",
    "status" => "online",
    "documentation" => "/docs",
    "openapi" => "/openapi.json",
    "endpoints" => [
        "GET /api/meta" => "Városok és régiók metaadatai",
        "GET /api/weather/city?cityId={id}&startDate={YYYY-MM-DD}&endDate={YYYY-MM-DD}" => "Város időjárási adatai",
        "GET /api/weather/region?region={name}&startDate={YYYY-MM-DD}&endDate={YYYY-MM-DD}" => "Régió aggregált adatai",
    ],
]));

// OpenAPI specifikáció végpont
$router->addRoute("GET", "/openapi.json", static function (HttpRequest $request): HttpResponseInterface {
    $file = __DIR__ . "/openapi.json";
    if (!is_file($file)) {
        return new JsonHttpResponse(["error" => "OpenAPI specifikáció nem található."], 404);
    }
    $content = json_decode((string) file_get_contents($file), true);
    return new JsonHttpResponse($content, 200, [
        "Cache-Control" => "public, max-age=3600",
    ]);
});

// Swagger UI interaktív dokumentációs oldal
$router->addRoute("GET", "/docs", static function (HttpRequest $request): HttpResponseInterface {
    $templatePath = __DIR__ . "/Resources/swagger.html";
    if (!is_file($templatePath)) {
        return new JsonHttpResponse(["error" => "Swagger sablon nem található."], 404);
    }

    $html = (string) file_get_contents($templatePath);
    return new HtmlHttpResponse($html);
});

$router->addRoute("GET", "/hello", $helloController->sayHello(...));
$router->addRoute("GET", "/budapest", $helloController->getBudapestMaxTempCount(...));

// Időjárás végpontok
$router->addRoute("GET", "/api/meta", $weatherController->getMeta(...));
$router->addRoute("GET", "/api/weather/city", $weatherController->getCityWeather(...));
$router->addRoute("GET", "/api/weather/region", $weatherController->getRegionWeather(...));

$router->handleRequest();

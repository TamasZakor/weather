<?php

declare(strict_types=1);

namespace ADS\TakeHome\Controllers;

use ADS\TakeHome\Framework\HttpRequest;
use ADS\TakeHome\Framework\HttpResponseInterface;
use ADS\TakeHome\Framework\JsonHttpResponse;
use ADS\TakeHome\Requests\WeatherQuery;
use ADS\TakeHome\Services\WeatherService;
use InvalidArgumentException;

/**
 * Időjárási HTTP kéréseket kiszolgáló vékony (thin) kontroller.
 */
class WeatherController
{
    public function __construct(
        private readonly WeatherService $weatherService,
    ) {
    }

    /**
     * Cache-Control konstans statikus adatokhoz (1 nap = 86400 másodperc).
     */
    private const array STATIC_CACHE_HEADERS = [
        "Cache-Control" => "public, max-age=86400, immutable",
    ];

    /**
     * Városok és régiók metaadatai a kliens dropdown menüihez.
     */
    public function getMeta(HttpRequest $request): HttpResponseInterface
    {
        return new JsonHttpResponse(
            $this->weatherService->getMetadata(),
            200,
            self::STATIC_CACHE_HEADERS
        );
    }

    /**
     * Város időjárási adatainak végpontja.
     */
    public function getCityWeather(HttpRequest $request): HttpResponseInterface
    {
        try {
            $query = WeatherQuery::forCity($request->query);
            $records = $this->weatherService->getCityWeather($query);
            return new JsonHttpResponse($records, 200, self::STATIC_CACHE_HEADERS);
        } catch (InvalidArgumentException $e) {
            return new JsonHttpResponse(["error" => $e->getMessage()], 400);
        }
    }

    /**
     * Régió átlagolt adatainak végpontja.
     */
    public function getRegionWeather(HttpRequest $request): HttpResponseInterface
    {
        try {
            $query = WeatherQuery::forRegion($request->query);
            $records = $this->weatherService->getRegionWeather($query);
            return new JsonHttpResponse($records, 200, self::STATIC_CACHE_HEADERS);
        } catch (InvalidArgumentException $e) {
            return new JsonHttpResponse(["error" => $e->getMessage()], 400);
        }
    }
}

<?php

declare(strict_types=1);

namespace ADS\TakeHome\Services;

use ADS\TakeHome\Models\City;
use ADS\TakeHome\Models\DailyWeatherRecord;
use ADS\TakeHome\Repositories\WeatherRepository;
use ADS\TakeHome\Requests\WeatherQuery;
use InvalidArgumentException;

/**
 * Üzleti logika réteg az időjárási adatok kezelésére.
 */
class WeatherService
{
    public function __construct(
        private readonly WeatherRepository $repository,
    ) {
    }

    /**
     * @return array{cities: list<City>, regions: list<string>, dateRange: array{minDate: string, maxDate: string}}
     */
    public function getMetadata(): array
    {
        return [
            "cities" => $this->repository->getAllCities(),
            "regions" => $this->repository->getRegions(),
            "dateRange" => $this->repository->getDateRange(),
        ];
    }

    /**
     * @return list<DailyWeatherRecord>
     */
    public function getCityWeather(WeatherQuery $query): array
    {
        if ($query->cityId === null) {
            throw new InvalidArgumentException("A 'cityId' nem lehet null.");
        }

        return $this->repository->getCityWeatherData(
            $query->cityId,
            $query->startDate,
            $query->endDate
        );
    }

    /**
     * @return list<DailyWeatherRecord>
     */
    public function getRegionWeather(WeatherQuery $query): array
    {
        if ($query->region === null) {
            throw new InvalidArgumentException("A 'region' nem lehet null.");
        }

        return $this->repository->getRegionWeatherData(
            $query->region->value,
            $query->startDate,
            $query->endDate
        );
    }
}

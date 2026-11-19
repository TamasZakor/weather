<?php

declare(strict_types=1);

namespace ADS\TakeHome\Tests;

use ADS\TakeHome\Framework\MySQL;
use ADS\TakeHome\Models\AbstractModel;
use ADS\TakeHome\Models\City;
use ADS\TakeHome\Models\DailyWeatherRecord;
use ADS\TakeHome\Repositories\WeatherRepository;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Valós adatbázis integrációs tesztek (takehome-db konténer ellen).
 * A WeatherRepository és a mögöttes SQL lekérdezések valós MySQL-en futnak.
 */
class WeatherRepositoryDatabaseTest extends TestCase
{
    private WeatherRepository $repository;

    protected function setUp(): void
    {
        $mysql = new MySQL("db", "weather", "root", "root");
        AbstractModel::setDatabase($mysql);
        $this->repository = new WeatherRepository();
    }

    #[Test]
    public function getAllCitiesReturnsRealCitiesList(): void
    {
        $cities = $this->repository->getAllCities();

        $this->assertNotEmpty($cities);
        $this->assertContainsOnlyInstancesOf(City::class, $cities);

        $budapestFound = false;
        foreach ($cities as $city) {
            $this->assertGreaterThan(0, $city->id);
            $this->assertNotEmpty($city->name);
            $this->assertNotEmpty($city->region);
            if ($city->name === 'Budapest') {
                $budapestFound = true;
                $this->assertSame('Közép-Magyarország', $city->region);
            }
        }

        $this->assertTrue($budapestFound, 'Budapestnek szerepelnie kell az adatbázisban.');
    }

    #[Test]
    public function getRegionsReturnsExpectedHungarianRegions(): void
    {
        $regions = $this->repository->getRegions();

        $this->assertCount(3, $regions);
        $this->assertContains('Dunántúl', $regions);
        $this->assertContains('Közép-Magyarország', $regions);
        $this->assertContains('Észak és Alföld', $regions);
    }

    #[Test]
    public function getDateRangeReturnsValidMinAndMaxDates(): void
    {
        $range = $this->repository->getDateRange();

        $this->assertArrayHasKey('minDate', $range);
        $this->assertArrayHasKey('maxDate', $range);

        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $range['minDate']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $range['maxDate']);

        $this->assertLessThanOrEqual($range['maxDate'], $range['minDate']);
        // 1901-01-01 és legfeljebb aktuális év
        $this->assertSame('1901-01-01', $range['minDate']);
    }

    #[Test]
    public function getCityWeatherDataReturnsPopulatedDailyRecords(): void
    {
        // Budapest (id: 1), 1901-01-01 és 1901-01-05 között
        $records = $this->repository->getCityWeatherData(1, 19010101, 19010105);

        $this->assertNotEmpty($records);
        $this->assertContainsOnlyInstancesOf(DailyWeatherRecord::class, $records);

        $first = $records[0];
        $this->assertSame('1901-01-01', $first->date);
        $this->assertIsFloat($first->minTemp);
        $this->assertIsFloat($first->maxTemp);
        $this->assertIsFloat($first->avgTemp);
        $this->assertIsFloat($first->precipitation);
        // Min nem lehet nagyobb a Max-nál
        $this->assertLessThanOrEqual($first->maxTemp, $first->minTemp);
    }

    #[Test]
    public function getRegionWeatherDataReturnsAggregatedRecords(): void
    {
        // Közép-Magyarország régió, 1901-01-01 és 1901-01-03 között
        $records = $this->repository->getRegionWeatherData('Közép-Magyarország', 19010101, 19010103);

        $this->assertNotEmpty($records);
        $this->assertContainsOnlyInstancesOf(DailyWeatherRecord::class, $records);

        foreach ($records as $record) {
            $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $record->date);
            $this->assertIsFloat($record->minTemp);
            $this->assertIsFloat($record->maxTemp);
            $this->assertIsFloat($record->avgTemp);
            $this->assertIsFloat($record->precipitation);
        }
    }

    #[Test]
    public function getCityWeatherDataReturnsEmptyArrayForFutureDates(): void
    {
        // 2099-es év nem szerepel a historikus adatokban
        $records = $this->repository->getCityWeatherData(1, 20990101, 20990110);

        $this->assertIsArray($records);
        $this->assertEmpty($records);
    }
}

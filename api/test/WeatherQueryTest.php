<?php

declare(strict_types=1);

namespace ADS\TakeHome\Tests;

use ADS\TakeHome\Enums\Region;
use ADS\TakeHome\Requests\WeatherQuery;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * A WeatherQuery form / query request osztály validációinak teljes lefedése.
 */
class WeatherQueryTest extends TestCase
{
    #[Test]
    public function forCitySucceedsWithValidParameters(): void
    {
        $query = WeatherQuery::forCity([
            "cityId" => "1",
            "startDate" => "1901-01-01",
            "endDate" => "1901-01-10",
        ]);

        $this->assertSame(1, $query->cityId);
        $this->assertNull($query->region);
        $this->assertSame(19010101, $query->startDate);
        $this->assertSame(19010110, $query->endDate);
    }

    #[Test]
    public function forCityAppliesDefaultDatesWhenDatesOmitted(): void
    {
        $query = WeatherQuery::forCity([
            "cityId" => 5,
        ]);

        $this->assertSame(5, $query->cityId);
        $this->assertSame(19010101, $query->startDate);
        $this->assertSame(19011231, $query->endDate);
    }

    #[Test]
    public function forCityCalculatesOneYearLaterWhenOnlyStartDateGiven(): void
    {
        $query = WeatherQuery::forCity([
            "cityId" => 2,
            "startDate" => "1950-06-01",
        ]);

        $this->assertSame(19500601, $query->startDate);
        // +1 év -1 nap: 1951-05-31
        $this->assertSame(19510531, $query->endDate);
    }

    #[Test]
    public function forCityThrowsExceptionWhenCityIdMissing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("A 'Város' kitöltése kötelező.");

        WeatherQuery::forCity([]);
    }

    #[Test]
    public function forCityThrowsExceptionWhenCityIdIsNotPositiveNumber(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("A 'Város' mező kitöltése kötelező és érvényesnek kell lennie.");

        WeatherQuery::forCity(["cityId" => "-1"]);
    }

    #[Test]
    public function forCityThrowsExceptionWhenDateFormatIsInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("YYYY-MM-DD");

        WeatherQuery::forCity([
            "cityId" => 1,
            "startDate" => "invalid-date",
            "endDate" => "1901-01-10",
        ]);
    }

    #[Test]
    public function forCityThrowsExceptionWhenStartDateLaterThanEndDate(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("nem lehet későbbi");

        WeatherQuery::forCity([
            "cityId" => 1,
            "startDate" => "1901-01-20",
            "endDate" => "1901-01-10",
        ]);
    }

    #[Test]
    public function forRegionSucceedsWithValidParameters(): void
    {
        $query = WeatherQuery::forRegion([
            "region" => "Dunántúl",
            "startDate" => "1920-05-01",
            "endDate" => "1920-05-31",
        ]);

        $this->assertNull($query->cityId);
        $this->assertSame(Region::DUNANTUL, $query->region);
        $this->assertSame(19200501, $query->startDate);
        $this->assertSame(19200531, $query->endDate);
    }

    #[Test]
    public function forRegionAppliesDefaultDatesWhenDatesOmitted(): void
    {
        $query = WeatherQuery::forRegion([
            "region" => "Közép-Magyarország",
        ]);

        $this->assertSame(Region::KOZEP_MAGYARORSZAG, $query->region);
        $this->assertSame(19010101, $query->startDate);
        $this->assertSame(19011231, $query->endDate);
    }

    #[Test]
    public function forRegionThrowsExceptionWhenRegionMissing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("A 'Régió' kitöltése kötelező.");

        WeatherQuery::forRegion([]);
    }

    #[Test]
    public function forRegionThrowsExceptionWhenRegionValueInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("A 'Régió' mező érvénytelen vagy hiányzik.");

        WeatherQuery::forRegion(["region" => "NemLetezoRegio"]);
    }

    #[Test]
    public function forRegionCalculatesOneYearLaterWhenOnlyStartDateGiven(): void
    {
        $query = WeatherQuery::forRegion([
            "region" => "Észak és Alföld",
            "startDate" => "1930-03-15",
        ]);

        $this->assertSame(Region::ESZAK_ES_ALFOLD, $query->region);
        $this->assertSame(19300315, $query->startDate);
        // +1 év -1 nap: 1931-03-14
        $this->assertSame(19310314, $query->endDate);
    }

    #[Test]
    public function forCityThrowsExceptionWhenCityIdIsZero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("A 'Város' mező kitöltése kötelező és érvényesnek kell lennie.");

        WeatherQuery::forCity(["cityId" => "0"]);
    }

    #[Test]
    public function forCityThrowsExceptionWhenCityIdIsNonNumeric(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("A 'Város' mező kitöltése kötelező és érvényesnek kell lennie.");

        WeatherQuery::forCity(["cityId" => "abc"]);
    }

    #[Test]
    public function forCitySucceedsWhenStartDateEqualsEndDate(): void
    {
        // Határeset: azonos start és end dátum érvényes kell legyen (nem > hanem >=)
        $query = WeatherQuery::forCity([
            "cityId" => "3",
            "startDate" => "1950-07-04",
            "endDate" => "1950-07-04",
        ]);

        $this->assertSame(19500704, $query->startDate);
        $this->assertSame(19500704, $query->endDate);
    }

    #[Test]
    public function forCityThrowsExceptionWhenEndDateFormatIsInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("YYYY-MM-DD");

        WeatherQuery::forCity([
            "cityId" => 1,
            "startDate" => "1901-01-01",
            "endDate" => "nemDatum",
        ]);
    }
}

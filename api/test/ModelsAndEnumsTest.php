<?php

declare(strict_types=1);

namespace ADS\TakeHome\Tests;

use ADS\TakeHome\Enums\Region;
use ADS\TakeHome\Models\City;
use ADS\TakeHome\Models\DailyWeatherRecord;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Entitások, DTO-k és Enumok viselkedésének tesztelése.
 */
class ModelsAndEnumsTest extends TestCase
{
    #[Test]
    public function dailyWeatherRecordSerializesCorrectly(): void
    {
        $record = new DailyWeatherRecord(
            date: "1901-01-01",
            minTemp: -5.4,
            maxTemp: 2.1,
            avgTemp: -1.65,
            precipitation: 0.0,
        );

        $json = $record->jsonSerialize();

        $this->assertSame("1901-01-01", $json["date"]);
        $this->assertSame(-5.4, $json["minTemp"]);
        $this->assertSame(2.1, $json["maxTemp"]);
        $this->assertSame(-1.65, $json["avgTemp"]);
        $this->assertSame(0.0, $json["precipitation"]);
    }

    #[Test]
    public function dailyWeatherRecordAttributeLabelsMatchExpectedHungarianText(): void
    {
        $this->assertSame("Dátum", DailyWeatherRecord::getAttributeLabel("date"));
        $this->assertSame("Min. hőmérséklet", DailyWeatherRecord::getAttributeLabel("minTemp"));
        $this->assertSame("Max. hőmérséklet", DailyWeatherRecord::getAttributeLabel("maxTemp"));
        $this->assertSame("Átlag hőmérséklet", DailyWeatherRecord::getAttributeLabel("avgTemp"));
        $this->assertSame("Csapadék", DailyWeatherRecord::getAttributeLabel("precipitation"));
        $this->assertSame("unknownKey", DailyWeatherRecord::getAttributeLabel("unknownKey"));
    }

    #[Test]
    public function citySerializesCorrectly(): void
    {
        $city = new City(1, "Budapest", "Közép-Magyarország");
        $json = $city->jsonSerialize();

        $this->assertSame(1, $json["id"]);
        $this->assertSame("Budapest", $json["name"]);
        $this->assertSame("Közép-Magyarország", $json["region"]);
    }

    #[Test]
    public function regionEnumValuesContainsAllSupportedHungarianRegions(): void
    {
        $values = Region::values();

        $this->assertCount(3, $values);
        $this->assertContains("Dunántúl", $values);
        $this->assertContains("Közép-Magyarország", $values);
        $this->assertContains("Észak és Alföld", $values);
    }
}

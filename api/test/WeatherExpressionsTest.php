<?php

declare(strict_types=1);

namespace ADS\TakeHome\Tests;

use ADS\TakeHome\Components\WeatherExpressions;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * A WeatherExpressions SQL helper tesztjei.
 */
class WeatherExpressionsTest extends TestCase
{
    #[Test]
    public function minTempExpressionIsCorrect(): void
    {
        $this->assertSame("tn.min_temp AS minTemp", WeatherExpressions::minTemp());
    }

    #[Test]
    public function maxTempExpressionIsCorrect(): void
    {
        $this->assertSame("tx.max_temp AS maxTemp", WeatherExpressions::maxTemp());
    }

    #[Test]
    public function avgTempExpressionCalculatesAverageOfMinAndMax(): void
    {
        $this->assertSame("ROUND((tn.min_temp + tx.max_temp) / 2, 2) AS avgTemp", WeatherExpressions::avgTemp());
    }

    #[Test]
    public function precipitationExpressionIsCorrect(): void
    {
        $this->assertSame("r.precipitation AS precipitation", WeatherExpressions::precipitation());
    }

    #[Test]
    public function avgAvgTempExpressionAggregatesDailyAverages(): void
    {
        $this->assertSame("ROUND(AVG((tn.min_temp + tx.max_temp) / 2), 2) AS avgTemp", WeatherExpressions::avgAvgTemp());
    }

    #[Test]
    public function citySelectColumnsContainsAllRequiredExpressions(): void
    {
        $columns = WeatherExpressions::citySelectColumns();

        $this->assertCount(5, $columns);
        $this->assertSame("tx.date", $columns[0]);
        $this->assertSame("tn.min_temp AS minTemp", $columns[1]);
        $this->assertSame("tx.max_temp AS maxTemp", $columns[2]);
        $this->assertSame("ROUND((tn.min_temp + tx.max_temp) / 2, 2) AS avgTemp", $columns[3]);
        $this->assertSame("r.precipitation AS precipitation", $columns[4]);
    }

    #[Test]
    public function regionSelectColumnsContainsAggregatedExpressions(): void
    {
        $columns = WeatherExpressions::regionSelectColumns();

        $this->assertCount(5, $columns);
        $this->assertSame("tx.date", $columns[0]);
        $this->assertStringContainsString("AVG(tn.min_temp)", $columns[1]);
        $this->assertStringContainsString("AVG(tx.max_temp)", $columns[2]);
        $this->assertStringContainsString("ROUND(AVG((tn.min_temp + tx.max_temp) / 2), 2)", $columns[3]);
        $this->assertStringContainsString("AVG(r.precipitation)", $columns[4]);
    }

    #[Test]
    public function avgMinTempExpressionAggregatesAcrossCities(): void
    {
        $this->assertSame("ROUND(AVG(tn.min_temp), 2) AS minTemp", WeatherExpressions::avgMinTemp());
    }

    #[Test]
    public function avgMaxTempExpressionAggregatesAcrossCities(): void
    {
        $this->assertSame("ROUND(AVG(tx.max_temp), 2) AS maxTemp", WeatherExpressions::avgMaxTemp());
    }

    #[Test]
    public function avgPrecipitationExpressionAggregatesAcrossCities(): void
    {
        $this->assertSame("ROUND(AVG(r.precipitation), 2) AS precipitation", WeatherExpressions::avgPrecipitation());
    }
}

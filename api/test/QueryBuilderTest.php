<?php

declare(strict_types=1);

namespace ADS\TakeHome\Tests;

use ADS\TakeHome\Components\QueryBuilder;
use ADS\TakeHome\Framework\MySQL;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * A Fluent QueryBuilder SQL összeállító képességeinek tesztjei.
 */
class QueryBuilderTest extends TestCase
{
    #[Test]
    public function simpleSelectGeneratesValidSql(): void
    {
        $mockDb = $this->createMock(MySQL::class);
        $qb = new QueryBuilder($mockDb);

        $sql = $qb->from("weather.cities", "c")
            ->select(["id", "name"])
            ->orderBy("name ASC")
            ->toSql();

        $this->assertSame("SELECT id, name FROM weather.cities AS c ORDER BY name ASC", $sql);
    }

    #[Test]
    public function complexQueryWithWhereAndJoinGeneratesValidSql(): void
    {
        $mockDb = $this->createMock(MySQL::class);
        $qb = new QueryBuilder($mockDb);

        $sql = $qb->from("weather.maximum_temperatures", "tx")
            ->innerJoin("weather.minimum_temperatures", "tn", "tx.city_id = tn.city_id AND tx.date = tn.date")
            ->select(["tx.date", "tx.max_temp", "tn.min_temp"])
            ->where("tx.city_id = :cityId", [":cityId" => 1])
            ->where("tx.date >= :startDate", [":startDate" => 19010101])
            ->groupBy("tx.date")
            ->orderBy("tx.date ASC")
            ->toSql();

        $expected = "SELECT tx.date, tx.max_temp, tn.min_temp FROM weather.maximum_temperatures AS tx "
            . "INNER JOIN weather.minimum_temperatures AS tn ON tx.city_id = tn.city_id AND tx.date = tn.date "
            . "WHERE tx.city_id = :cityId AND tx.date >= :startDate "
            . "GROUP BY tx.date "
            . "ORDER BY tx.date ASC";

        $this->assertSame($expected, $sql);
        $this->assertSame([":cityId" => 1, ":startDate" => 19010101], $qb->getParams());
    }
}

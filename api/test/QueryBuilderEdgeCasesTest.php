<?php

declare(strict_types=1);

namespace ADS\TakeHome\Tests;

use ADS\TakeHome\Components\QueryBuilder;
use ADS\TakeHome\Framework\MySQL;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * A QueryBuilder lefedetlen ágainak és edge case-einek tesztjei.
 *
 * Lefedi:
 *  - SELECT * (üres selects)
 *  - select() string ág (nem array)
 *  - LEFT JOIN
 *  - Több orderBy / groupBy egyszerre (comma-join)
 *  - Csak WHERE (FROM + WHERE, JOIN nélkül)
 *  - getParams() üres params esetén
 */
class QueryBuilderEdgeCasesTest extends TestCase
{
    #[Test]
    public function toSqlReturnsSelectStarWhenNoColumnsSpecified(): void
    {
        $mockDb = $this->createMock(MySQL::class);
        $qb = new QueryBuilder($mockDb);

        $sql = $qb->from("weather.cities")->toSql();

        $this->assertSame("SELECT * FROM weather.cities", $sql);
    }

    #[Test]
    public function selectWithStringAddsColumnCorrectly(): void
    {
        $mockDb = $this->createMock(MySQL::class);
        $qb = new QueryBuilder($mockDb);

        // A select() elfogad egyszeres string-et is (nem csak tömböt)
        $sql = $qb->from("weather.cities", "c")
            ->select("id")
            ->select("name")
            ->toSql();

        $this->assertSame("SELECT id, name FROM weather.cities AS c", $sql);
    }

    #[Test]
    public function selectMixedStringAndArrayMergesCorrectly(): void
    {
        $mockDb = $this->createMock(MySQL::class);
        $qb = new QueryBuilder($mockDb);

        $sql = $qb->from("weather.cities", "c")
            ->select("id")
            ->select(["name", "region"])
            ->toSql();

        $this->assertSame("SELECT id, name, region FROM weather.cities AS c", $sql);
    }

    #[Test]
    public function leftJoinGeneratesCorrectSqlClause(): void
    {
        $mockDb = $this->createMock(MySQL::class);
        $qb = new QueryBuilder($mockDb);

        $sql = $qb->from("weather.maximum_temperatures", "tx")
            ->select(["tx.date", "r.precipitation"])
            ->leftJoin("weather.precipitation", "r", "tx.city_id = r.city_id AND tx.date = r.date")
            ->toSql();

        $this->assertSame(
            "SELECT tx.date, r.precipitation FROM weather.maximum_temperatures AS tx"
            . " LEFT JOIN weather.precipitation AS r ON tx.city_id = r.city_id AND tx.date = r.date",
            $sql
        );
    }

    #[Test]
    public function multipleOrderBysAreJoinedWithComma(): void
    {
        $mockDb = $this->createMock(MySQL::class);
        $qb = new QueryBuilder($mockDb);

        $sql = $qb->from("weather.maximum_temperatures", "tx")
            ->select("tx.date")
            ->orderBy("tx.date ASC")
            ->orderBy("tx.city_id DESC")
            ->toSql();

        $this->assertSame(
            "SELECT tx.date FROM weather.maximum_temperatures AS tx ORDER BY tx.date ASC, tx.city_id DESC",
            $sql
        );
    }

    #[Test]
    public function multipleGroupBysAreJoinedWithComma(): void
    {
        $mockDb = $this->createMock(MySQL::class);
        $qb = new QueryBuilder($mockDb);

        $sql = $qb->from("weather.maximum_temperatures", "tx")
            ->select(["tx.city_id", "tx.date", "MAX(tx.max_temp)"])
            ->groupBy("tx.city_id")
            ->groupBy("tx.date")
            ->toSql();

        $this->assertSame(
            "SELECT tx.city_id, tx.date, MAX(tx.max_temp) FROM weather.maximum_temperatures AS tx"
            . " GROUP BY tx.city_id, tx.date",
            $sql
        );
    }

    #[Test]
    public function multipleWhereConditionsAreJoinedWithAnd(): void
    {
        $mockDb = $this->createMock(MySQL::class);
        $qb = new QueryBuilder($mockDb);

        $sql = $qb->from("weather.maximum_temperatures", "tx")
            ->select("tx.date")
            ->where("tx.city_id = :cityId", [":cityId" => 5])
            ->where("tx.date >= :startDate", [":startDate" => 19010101])
            ->where("tx.date <= :endDate", [":endDate" => 19011231])
            ->toSql();

        $this->assertSame(
            "SELECT tx.date FROM weather.maximum_temperatures AS tx"
            . " WHERE tx.city_id = :cityId AND tx.date >= :startDate AND tx.date <= :endDate",
            $sql
        );
        $this->assertSame(
            [":cityId" => 5, ":startDate" => 19010101, ":endDate" => 19011231],
            $qb->getParams()
        );
    }

    #[Test]
    public function getParamsReturnsEmptyArrayWhenNoWhereGiven(): void
    {
        $mockDb = $this->createMock(MySQL::class);
        $qb = new QueryBuilder($mockDb);

        $qb->from("weather.cities")->select("id")->toSql();

        $this->assertSame([], $qb->getParams());
    }

    #[Test]
    public function fromWithoutAliasOmitsAsKeyword(): void
    {
        $mockDb = $this->createMock(MySQL::class);
        $qb = new QueryBuilder($mockDb);

        $sql = $qb->from("weather.cities")->select(["id", "name"])->toSql();

        // Alias nélkül nincs "AS" a FROM klauzulában
        $this->assertStringNotContainsString(" AS ", $sql);
        $this->assertSame("SELECT id, name FROM weather.cities", $sql);
    }

    #[Test]
    public function innerJoinAndLeftJoinCanBeCombined(): void
    {
        $mockDb = $this->createMock(MySQL::class);
        $qb = new QueryBuilder($mockDb);

        $sql = $qb->from("weather.maximum_temperatures", "tx")
            ->select(["tx.date", "tn.min_temp", "r.precipitation"])
            ->innerJoin("weather.minimum_temperatures", "tn", "tx.city_id = tn.city_id AND tx.date = tn.date")
            ->leftJoin("weather.precipitation", "r", "tx.city_id = r.city_id AND tx.date = r.date")
            ->toSql();

        $this->assertStringContainsString("INNER JOIN weather.minimum_temperatures AS tn", $sql);
        $this->assertStringContainsString("LEFT JOIN weather.precipitation AS r", $sql);
    }
}

<?php

declare(strict_types=1);

namespace ADS\TakeHome\Repositories;

use ADS\TakeHome\Components\WeatherExpressions;
use ADS\TakeHome\Enums\Region;
use ADS\TakeHome\Models\City;
use ADS\TakeHome\Models\DailyWeatherRecord;
use ADS\TakeHome\Models\MinimumTemperature;
use ADS\TakeHome\Models\Precipitation;
use ADS\TakeHome\Models\Weather;

/**
 * Időjárási és város adatok lekérdezése az adatbázisból modell szintű lekérdezésekkel.
 */
class WeatherRepository
{
    /**
     * @return list<City>
     */
    public function getAllCities(): array
    {
        $rows = City::query()
            ->select(["id", "name", "region"])
            ->orderBy("name ASC")
            ->fetchAll();

        $cities = [];
        foreach ($rows as $row) {
            $cities[] = new City(
                id: (int) $row["id"],
                name: (string) $row["name"],
                region: (string) $row["region"],
            );
        }

        return $cities;
    }

    /**
     * @return list<string>
     */
    public function getRegions(): array
    {
        return Region::values();
    }

    /**
     * Visszaadja az adatbázisban található legkorábbi és legkésőbbi dátumot YYYY-MM-DD formátumban.
     *
     * @return array{minDate: string, maxDate: string}
     */
    public function getDateRange(): array
    {
        $rows = Weather::query("tx")
            ->select([
                "MIN(tx.date) AS minDate",
                "MAX(tx.date) AS maxDate",
            ])
            ->fetchAll();

        $minRaw = isset($rows[0]["minDate"]) ? (string) $rows[0]["minDate"] : "19010101";
        $maxRaw = isset($rows[0]["maxDate"]) ? (string) $rows[0]["maxDate"] : "20231231";

        return [
            "minDate" => sprintf("%s-%s-%s", substr($minRaw, 0, 4), substr($minRaw, 4, 2), substr($minRaw, 6, 2)),
            "maxDate" => sprintf("%s-%s-%s", substr($maxRaw, 0, 4), substr($maxRaw, 4, 2), substr($maxRaw, 6, 2)),
        ];
    }

    /**
     * Adott város időjárási adatainak lekérdezése a megadott időszakra.
     *
     * @return list<DailyWeatherRecord>
     */
    public function getCityWeatherData(int $cityId, int $startDate, int $endDate): array
    {
        $rows = Weather::queryWeatherData()
            ->where("tx.city_id = :cityId", [":cityId" => $cityId])
            ->where("tx.date >= :startDate", [":startDate" => $startDate])
            ->where("tx.date <= :endDate", [":endDate" => $endDate])
            ->orderBy("tx.date ASC")
            ->fetchAll();

        return $this->mapRowsToRecords($rows);
    }

    /**
     * Adott régió városainak átlagolt időjárási adatai modell-összekapcsolással.
     *
     * @return list<DailyWeatherRecord>
     */
    public function getRegionWeatherData(string $region, int $startDate, int $endDate): array
    {
        // c.id = tx.city_id (a City.id-vel párosítja a Weather.city_id-t a 'c' aliasú szülőtáblán)
        $rows = City::query("c")
            ->joinModel(Weather::class, "tx", ["city_id" => "id"], parentAlias: "c")
            ->joinModel(MinimumTemperature::class, "tn", ["city_id", "date"], parentAlias: "tx")
            ->joinModel(Precipitation::class, "r", ["city_id", "date"], parentAlias: "tx")
            ->select(WeatherExpressions::regionSelectColumns())
            ->where("c.region = :region", [":region" => $region])
            ->where("tx.date >= :startDate", [":startDate" => $startDate])
            ->where("tx.date <= :endDate", [":endDate" => $endDate])
            ->groupBy("tx.date")
            ->orderBy("tx.date ASC")
            ->fetchAll();

        return $this->mapRowsToRecords($rows);
    }

    /**
     * @param list<array<string, scalar|null>> $rows
     * @return list<DailyWeatherRecord>
     */
    private function mapRowsToRecords(array $rows): array
    {
        $records = [];
        foreach ($rows as $row) {
            $rawDate = (string) $row["date"];
            $formattedDate = sprintf(
                "%s-%s-%s",
                substr($rawDate, 0, 4),
                substr($rawDate, 4, 2),
                substr($rawDate, 6, 2)
            );

            $records[] = new DailyWeatherRecord(
                date: $formattedDate,
                minTemp: (float) $row["minTemp"],
                maxTemp: (float) $row["maxTemp"],
                avgTemp: (float) $row["avgTemp"],
                precipitation: (float) $row["precipitation"],
            );
        }

        return $records;
    }
}

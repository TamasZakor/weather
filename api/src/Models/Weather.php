<?php

declare(strict_types=1);

namespace ADS\TakeHome\Models;

use ADS\TakeHome\Components\QueryBuilder;
use ADS\TakeHome\Components\WeatherExpressions;

/**
 * Meteorológiai adatok modellje (Maximum hőmérséklet tábla alapokon).
 */
class Weather extends AbstractModel
{
    public static function tableName(): string
    {
        return "weather.maximum_temperatures";
    }

    /**
     * Összekapcsolja az összes kapcsolódó időjárási táblát típusbiztos, OOP módon.
     * A SELECT kifejezések a WeatherExpressions helper osztályból származnak –
     * a számítási logika (kerekítés, képlet) ott módosítható.
     */
    public static function queryWeatherData(): QueryBuilder
    {
        return self::query("tx")
            ->joinModel(MinimumTemperature::class, "tn", ["city_id", "date"], parentAlias: "tx")
            ->joinModel(Precipitation::class, "r", ["city_id", "date"], parentAlias: "tx")
            ->select(WeatherExpressions::citySelectColumns());
    }
}

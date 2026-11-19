<?php

declare(strict_types=1);

namespace ADS\TakeHome\Components;

/**
 * Központi SQL kifejezés-gyár az időjárási számításokhoz.
 *
 * Minden mezőre vonatkozó SQL fragment egyetlen helyen van definiálva,
 * így ha a számítási logika változik (pl. kerekítési pontosság, képlet),
 * elegendő itt módosítani – a Weather::queryWeatherData() és a
 * WeatherRepository::getRegionWeatherData() automatikusan az új értéket használja.
 *
 * Alias konvenciók (a hívó oldalon kell biztosítani):
 *   tx  = weather.maximum_temperatures
 *   tn  = weather.minimum_temperatures
 *   r   = weather.precipitation
 */
class WeatherExpressions
{
    /**
     * Napi minimumhőmérséklet – egyedi város lekérdezéshez.
     * Kimenet: `tn.min_temp AS minTemp`
     */
    public static function minTemp(): string
    {
        return "tn.min_temp AS minTemp";
    }

    /**
     * Napi maximumhőmérséklet – egyedi város lekérdezéshez.
     * Kimenet: `tx.max_temp AS maxTemp`
     */
    public static function maxTemp(): string
    {
        return "tx.max_temp AS maxTemp";
    }

    /**
     * Napi átlaghőmérséklet = (min + max) / 2, 2 tizedesjegyre kerekítve – egyedi város.
     * Kimenet: `ROUND((tn.min_temp + tx.max_temp) / 2, 2) AS avgTemp`
     */
    public static function avgTemp(): string
    {
        return "ROUND((tn.min_temp + tx.max_temp) / 2, 2) AS avgTemp";
    }

    /**
     * Napi csapadék – egyedi város lekérdezéshez.
     * Kimenet: `r.precipitation AS precipitation`
     */
    public static function precipitation(): string
    {
        return "r.precipitation AS precipitation";
    }

    /**
     * Régió-szintű átlagolt minimumhőmérséklet (AVG a városok között).
     * Kimenet: `ROUND(AVG(tn.min_temp), 2) AS minTemp`
     */
    public static function avgMinTemp(): string
    {
        return "ROUND(AVG(tn.min_temp), 2) AS minTemp";
    }

    /**
     * Régió-szintű átlagolt maximumhőmérséklet (AVG a városok között).
     * Kimenet: `ROUND(AVG(tx.max_temp), 2) AS maxTemp`
     */
    public static function avgMaxTemp(): string
    {
        return "ROUND(AVG(tx.max_temp), 2) AS maxTemp";
    }

    /**
     * Régió-szintű átlagolt átlaghőmérséklet = AVG((min + max) / 2), 2 tizedesjegyre kerekítve.
     * Kimenet: `ROUND(AVG((tn.min_temp + tx.max_temp) / 2), 2) AS avgTemp`
     */
    public static function avgAvgTemp(): string
    {
        return "ROUND(AVG((tn.min_temp + tx.max_temp) / 2), 2) AS avgTemp";
    }

    /**
     * Régió-szintű átlagolt csapadék (AVG a városok között).
     * Kimenet: `ROUND(AVG(r.precipitation), 2) AS precipitation`
     */
    public static function avgPrecipitation(): string
    {
        return "ROUND(AVG(r.precipitation), 2) AS precipitation";
    }

    /**
     * Teljes SELECT-lista egyedi város lekérdezéshez.
     *
     * @return list<string>
     */
    public static function citySelectColumns(): array
    {
        return [
            "tx.date",
            self::minTemp(),
            self::maxTemp(),
            self::avgTemp(),
            self::precipitation(),
        ];
    }

    /**
     * Teljes SELECT-lista régió (aggregált) lekérdezéshez.
     *
     * @return list<string>
     */
    public static function regionSelectColumns(): array
    {
        return [
            "tx.date",
            self::avgMinTemp(),
            self::avgMaxTemp(),
            self::avgAvgTemp(),
            self::avgPrecipitation(),
        ];
    }
}

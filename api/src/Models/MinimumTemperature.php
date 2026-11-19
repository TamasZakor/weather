<?php

declare(strict_types=1);

namespace ADS\TakeHome\Models;

/**
 * Minimum hőmérsékletek táblamodellje.
 */
class MinimumTemperature extends AbstractModel
{
    public static function tableName(): string
    {
        return "weather.minimum_temperatures";
    }
}

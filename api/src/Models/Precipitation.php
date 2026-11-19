<?php

declare(strict_types=1);

namespace ADS\TakeHome\Models;

/**
 * Csapadék adatok táblamodellje.
 */
class Precipitation extends AbstractModel
{
    public static function tableName(): string
    {
        return "weather.precipitation";
    }
}

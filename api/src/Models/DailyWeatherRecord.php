<?php

declare(strict_types=1);

namespace ADS\TakeHome\Models;

use JsonSerializable;

/**
 * Napi időjárási adatmodell és attribútum leíró.
 */
class DailyWeatherRecord implements JsonSerializable
{
    /**
     * Attribútum címkék (Attribute Labels) felhasználóbarát megjelenítéshez és validációhoz.
     */
    public const array ATTRIBUTE_LABELS = [
        "cityId"        => "Város",
        "region"        => "Régió",
        "startDate"     => "Kezdő dátum",
        "endDate"       => "Záró dátum",
        "date"          => "Dátum",
        "minTemp"       => "Min. hőmérséklet",
        "maxTemp"       => "Max. hőmérséklet",
        "avgTemp"       => "Átlag hőmérséklet",
        "precipitation" => "Csapadék",
    ];

    public function __construct(
        public readonly string $date,
        public readonly float $minTemp,
        public readonly float $maxTemp,
        public readonly float $avgTemp,
        public readonly float $precipitation,
    ) {
    }

    /**
     * @return array{date: string, minTemp: float, maxTemp: float, avgTemp: float, precipitation: float}
     */
    public function jsonSerialize(): array
    {
        return [
            "date" => $this->date,
            "minTemp" => $this->minTemp,
            "maxTemp" => $this->maxTemp,
            "avgTemp" => $this->avgTemp,
            "precipitation" => $this->precipitation,
        ];
    }

    /**
     * Visszaadja a mező emberi nevét.
     */
    public static function getAttributeLabel(string $attribute): string
    {
        return self::ATTRIBUTE_LABELS[$attribute] ?? $attribute;
    }
}

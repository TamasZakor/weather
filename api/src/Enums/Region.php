<?php

declare(strict_types=1);

namespace ADS\TakeHome\Enums;

/**
 * Támogatott meteorológiai régiók felsorolása (Backed Enum).
 */
enum Region: string
{
    case DUNANTUL = "Dunántúl";
    case KOZEP_MAGYARORSZAG = "Közép-Magyarország";
    case ESZAK_ES_ALFOLD = "Észak és Alföld";

    /**
     * Visszaadja a régiók string értékeinek listáját.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), "value");
    }
}

<?php

declare(strict_types=1);

namespace ADS\TakeHome\Requests;

use ADS\TakeHome\Enums\Region;
use InvalidArgumentException;

/**
 * Időjárás lekérdezési paramétereket validáló és reprezentáló Request / Form Model.
 */
class WeatherQuery
{
    /**
     * Mezők emberi elnevezése (Attribute Labels) a felhasználóbarát hibaüzenetekhez.
     */
    public const array ATTRIBUTE_LABELS = [
        "cityId"    => "Város",
        "region"    => "Régió",
        "startDate" => "Kezdő dátum",
        "endDate"   => "Záró dátum",
    ];

    /**
     * Alapértelmezett kezdő dátum, ha a kérésben nincs megadva (archívum kezdete).
     */
    public const string DEFAULT_START_DATE = "1901-01-01";

    /**
     * Alapértelmezett záró dátum: 1 évnyi intervallum a kezdő dátumhoz képest.
     */
    public const string DEFAULT_END_DATE = "1901-12-31";

    public function __construct(
        public readonly int|null $cityId,
        public readonly Region|null $region,
        public readonly int $startDate,
        public readonly int $endDate,
    ) {
    }

    /**
     * Város alapú lekérdezés paramétereinek validálása és példányosítása.
     * Ha a dátumok nincsenek megadva, alapértelmezetten 1 évnyi intervallumot kér le.
     *
     * @param array<string, mixed> $params
     * @throws InvalidArgumentException
     */
    public static function forCity(array $params): self
    {
        self::checkRequiredParams($params, ["cityId"]);

        $cityIdRaw = $params["cityId"];
        $cityLabel = self::ATTRIBUTE_LABELS["cityId"];

        if (!is_numeric($cityIdRaw) || (int) $cityIdRaw <= 0) {
            throw new InvalidArgumentException("A '{$cityLabel}' mező kitöltése kötelező és érvényesnek kell lennie.");
        }

        $startDateStr = isset($params["startDate"]) && trim((string) $params["startDate"]) !== ""
            ? (string) $params["startDate"]
            : self::DEFAULT_START_DATE;

        $endDateStr = isset($params["endDate"]) && trim((string) $params["endDate"]) !== ""
            ? (string) $params["endDate"]
            : (isset($params["startDate"]) ? self::calculateOneYearLater($startDateStr) : self::DEFAULT_END_DATE);

        [$startInt, $endInt] = self::parseDates($startDateStr, $endDateStr);

        return new self(
            cityId: (int) $cityIdRaw,
            region: null,
            startDate: $startInt,
            endDate: $endInt,
        );
    }

    /**
     * Régió alapú lekérdezés paramétereinek validálása és példányosítása.
     * Ha a dátumok nincsenek megadva, alapértelmezetten 1 évnyi intervallumot kér le.
     *
     * @param array<string, mixed> $params
     * @throws InvalidArgumentException
     */
    public static function forRegion(array $params): self
    {
        self::checkRequiredParams($params, ["region"]);

        $regionRaw = $params["region"];
        $regionLabel = self::ATTRIBUTE_LABELS["region"];

        $regionEnum = is_string($regionRaw) ? Region::tryFrom($regionRaw) : null;
        if ($regionEnum === null) {
            throw new InvalidArgumentException("A '{$regionLabel}' mező érvénytelen vagy hiányzik. Kérjük válasszon egy érvényes régiót.");
        }

        $startDateStr = isset($params["startDate"]) && trim((string) $params["startDate"]) !== ""
            ? (string) $params["startDate"]
            : self::DEFAULT_START_DATE;

        $endDateStr = isset($params["endDate"]) && trim((string) $params["endDate"]) !== ""
            ? (string) $params["endDate"]
            : (isset($params["startDate"]) ? self::calculateOneYearLater($startDateStr) : self::DEFAULT_END_DATE);

        [$startInt, $endInt] = self::parseDates($startDateStr, $endDateStr);

        return new self(
            cityId: null,
            region: $regionEnum,
            startDate: $startInt,
            endDate: $endInt,
        );
    }

    /**
     * Kiszámítja a kezdő dátumhoz képest az 1 évvel későbbi záró dátumot (alapértelmezett védelmi vonal).
     */
    private static function calculateOneYearLater(string $startDateStr): string
    {
        $timestamp = strtotime($startDateStr . " +1 year -1 day");
        if ($timestamp === false) {
            return self::DEFAULT_END_DATE;
        }
        return date("Y-m-d", $timestamp);
    }

    /**
     * Ellenőrzi, hogy a megadott kötelező mezők léteznek-e.
     *
     * @param array<string, mixed> $params
     * @param list<string> $requiredKeys
     * @throws InvalidArgumentException
     */
    private static function checkRequiredParams(array $params, array $requiredKeys): void
    {
        foreach ($requiredKeys as $key) {
            if (!isset($params[$key]) || trim((string) $params[$key]) === "") {
                $label = self::ATTRIBUTE_LABELS[$key] ?? $key;
                throw new InvalidArgumentException("A '{$label}' kitöltése kötelező.");
            }
        }
    }

    /**
     * Szigorú dátum formátum (YYYY-MM-DD) és tartomány ellenőrzés.
     *
     * @return array{0: int, 1: int}
     * @throws InvalidArgumentException
     */
    private static function parseDates(string $startDateStr, string $endDateStr): array
    {
        $startLabel = self::ATTRIBUTE_LABELS["startDate"];
        $endLabel = self::ATTRIBUTE_LABELS["endDate"];

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDateStr)) {
            throw new InvalidArgumentException("A '{$startLabel}' kitöltése kötelező és ÉÉÉÉ-HH-NN (YYYY-MM-DD) formátumú kell legyen.");
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDateStr)) {
            throw new InvalidArgumentException("A '{$endLabel}' kitöltése kötelező és ÉÉÉÉ-HH-NN (YYYY-MM-DD) formátumú kell legyen.");
        }

        $startInt = (int) str_replace("-", "", $startDateStr);
        $endInt = (int) str_replace("-", "", $endDateStr);

        if ($startInt > $endInt) {
            throw new InvalidArgumentException("A '{$startLabel}' nem lehet későbbi a '{$endLabel}' értékénél.");
        }

        return [$startInt, $endInt];
    }
}

<?php

declare(strict_types=1);

namespace ADS\TakeHome\Models;

use JsonSerializable;

/**
 * Város entitás modell.
 */
class City extends AbstractModel implements JsonSerializable
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $region,
    ) {
    }

    public static function tableName(): string
    {
        return "weather.cities";
    }

    /**
     * @return array{id: int, name: string, region: string}
     */
    public function jsonSerialize(): array
    {
        return [
            "id" => $this->id,
            "name" => $this->name,
            "region" => $this->region,
        ];
    }
}

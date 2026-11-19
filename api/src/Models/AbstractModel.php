<?php

declare(strict_types=1);

namespace ADS\TakeHome\Models;

use ADS\TakeHome\Components\QueryBuilder;
use ADS\TakeHome\Framework\MySQL;
use RuntimeException;

/**
 * Alapmodell (ActiveRecord / Eloquent minta szerint), amely biztosítja
 * a táblaszintű Fluent QueryBuilder hozzáférést.
 */
abstract class AbstractModel
{
    private static MySQL|null $db = null;

    /**
     * Beállítja az adatbáziskapcsolatot az összes modell számára.
     */
    public static function setDatabase(MySQL $db): void
    {
        self::$db = $db;
    }

    /**
     * Visszaadja az adatbáziskapcsolatot.
     */
    public static function getDatabase(): MySQL
    {
        if (self::$db === null) {
            throw new RuntimeException("Az adatbázis nincs inicializálva a Model rétegben. Hívja meg az AbstractModel::setDatabase() metódust.");
        }
        return self::$db;
    }

    /**
     * Elindít egy új Fluent QueryBuilder lekérdezést az adott modell táblájára.
     */
    public static function query(?string $alias = null): QueryBuilder
    {
        return (new QueryBuilder(self::getDatabase()))->from(static::tableName(), $alias);
    }

    /**
     * Az adott modellhez tartozó adatbázistábla neve.
     */
    abstract public static function tableName(): string;
}

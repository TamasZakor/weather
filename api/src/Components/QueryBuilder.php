<?php

declare(strict_types=1);

namespace ADS\TakeHome\Components;

use ADS\TakeHome\Framework\MySQL;
use ADS\TakeHome\Models\AbstractModel;

/**
 * Könnyűsúlyú Fluent SQL Query Builder.
 * Támogatja a SELECT, FROM, JOIN, WHERE, GROUP BY és ORDER BY műveleteket nevesített paraméterezéssel.
 */
class QueryBuilder
{
    /** @var list<string> */
    private array $selects = [];

    private string $from = "";

    /** @var list<string> */
    private array $joins = [];

    /** @var list<string> */
    private array $wheres = [];

    /** @var list<string> */
    private array $groupBys = [];

    /** @var list<string> */
    private array $orderBys = [];

    /** @var array<string, scalar|null> */
    private array $params = [];

    public function __construct(
        private readonly MySQL $db,
    ) {
    }

    /**
     * @param string|list<string> $columns
     */
    public function select(string|array $columns): self
    {
        if (is_array($columns)) {
            $this->selects = array_merge($this->selects, $columns);
        } else {
            $this->selects[] = $columns;
        }
        return $this;
    }

    public function from(string $table, ?string $alias = null): self
    {
        $this->from = $alias !== null ? "{$table} AS {$alias}" : $table;
        return $this;
    }

    public function innerJoin(string $table, string $alias, string $condition): self
    {
        $this->joins[] = "INNER JOIN {$table} AS {$alias} ON {$condition}";
        return $this;
    }

    /**
     * Összekapcsol egy másik AbstractModel osztály táblájával típusbiztos, OOP módon.
     *
     * @param class-string<AbstractModel> $modelClass A csatolandó modell osztálya
     * @param string $alias A csatolt tábla aliasa (pl. 'tn')
     * @param array<int|string, string>|string $onColumns A közös kulcsoszlopok: ['col1', 'col2'] ha a két táblában azonos a nevük, vagy ['targetCol' => 'parentCol']
     * @param string|null $parentAlias A szülő tábla aliasa, amihez kapcsolunk
     */
    public function joinModel(
        string $modelClass,
        string $alias,
        array|string $onColumns,
        ?string $parentAlias = null
    ): self {
        $tableName = $modelClass::tableName();
        $columns = is_array($onColumns) ? $onColumns : [$onColumns];

        $conditions = [];
        foreach ($columns as $targetCol => $parentCol) {
            // Ha számmal indexelt: azonos az oszlopnév mindkét táblában
            if (is_int($targetCol)) {
                $targetCol = $parentCol;
            }

            $left = $parentAlias !== null ? "{$parentAlias}.{$parentCol}" : $parentCol;
            $right = "{$alias}.{$targetCol}";
            $conditions[] = "{$left} = {$right}";
        }

        $conditionStr = implode(" AND ", $conditions);
        return $this->innerJoin($tableName, $alias, $conditionStr);
    }

    public function leftJoin(string $table, string $alias, string $condition): self
    {
        $this->joins[] = "LEFT JOIN {$table} AS {$alias} ON {$condition}";
        return $this;
    }

    /**
     * @param array<string, scalar|null> $params
     */
    public function where(string $condition, array $params = []): self
    {
        $this->wheres[] = $condition;
        $this->params = array_merge($this->params, $params);
        return $this;
    }

    public function groupBy(string $groupBy): self
    {
        $this->groupBys[] = $groupBy;
        return $this;
    }

    public function orderBy(string $orderBy): self
    {
        $this->orderBys[] = $orderBy;
        return $this;
    }

    public function toSql(): string
    {
        $selectClause = empty($this->selects) ? "*" : implode(", ", $this->selects);
        $sql = "SELECT {$selectClause} FROM {$this->from}";

        if (!empty($this->joins)) {
            $sql .= " " . implode(" ", $this->joins);
        }

        if (!empty($this->wheres)) {
            $sql .= " WHERE " . implode(" AND ", $this->wheres);
        }

        if (!empty($this->groupBys)) {
            $sql .= " GROUP BY " . implode(", ", $this->groupBys);
        }

        if (!empty($this->orderBys)) {
            $sql .= " ORDER BY " . implode(", ", $this->orderBys);
        }

        return $sql;
    }

    /**
     * @return array<string, scalar|null>
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Futtatja a lekérdezést a MySQL példányon keresztül.
     *
     * @return list<array<string, scalar|null>>
     */
    public function fetchAll(): array
    {
        return $this->db->queryAssoc($this->toSql(), $this->params);
    }
}

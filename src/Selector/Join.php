<?php

namespace Selector;

use Exception;

/**
 * Helper for SQL JOIN generation.
 */
final class Join {
    private string $leftTableAlias;
    private string $rightTableName;
    private string $rightTableAlias;
    /**
     * @var array<string, string>
     */
    private array $joiners = [];
    private string $typeJoin = 'left';
    private string $onBoth = '';
    private string $onRight = '';

    /**
     * @param array<string, string> $joiners
     */
    public function __construct(string $leftTableAlias, string $rightTableName, string $rightTableAlias, array $joiners) {
        $this->leftTableAlias = $leftTableAlias;
        $this->rightTableName = $rightTableName;
        $this->rightTableAlias = $rightTableAlias;
        $this->joiners = $this->normalizeJoiners($joiners);
        $this->setOnFromJoiners($joiners);
    }

    public function setTypeJoin(string $typeJoin): void {
        $typeJoin = strtolower($typeJoin);
        if (!in_array($typeJoin, ['left', 'inner', 'right'], true)) {
            throw new Exception(self::class . ": unsupported join type '$typeJoin'");
        }

        $this->typeJoin = $typeJoin;
    }

    /**
     * @param array<string, string> $joiners
     */
    public function checkJoinersEquals(array $joiners): bool {
        $left = $this->normalizeJoiners($joiners);
        $right = $this->normalizeJoiners($this->joiners);

        return $left === $right;
    }

    public function getRightTableName(): string {
        return $this->rightTableName;
    }

    public function getRightTableAlias(): string {
        return $this->rightTableAlias;
    }

    /**
     * @return array<string, string>
     */
    public function getJoiners(): array {
        return $this->joiners;
    }

    public function genSQLJoin(): string {
        $on = [];
        if ($this->onBoth) {
            $on[] = $this->onBoth;
        }
        if ($this->onRight) {
            $on[] = $this->onRight;
        }

        if (!$on) {
            throw new Exception(self::class . ': join conditions are empty');
        }

        $type = strtoupper($this->typeJoin);
        $table = $this->quoteTableName($this->rightTableName);
        $alias = $this->quoteIdentifier($this->rightTableAlias);

        return "$type JOIN $table $alias ON (" . implode(' AND ', $on) . ')';
    }

    /**
     * @param array<string, string> $joiners
     */
    private function setOnFromJoiners(array $joiners): void {
        if (!$joiners) {
            throw new Exception(self::class . ': joiners are required');
        }

        $onBoth = [];
        $onRight = [];

        foreach ($joiners as $leftFieldJoinName => $rightFieldJoinName) {
            if ($leftFieldJoinName === '' || $rightFieldJoinName === '') {
                continue;
            }

            if ($leftFieldJoinName[0] === '(' || $leftFieldJoinName[0] === '%') {
                $onRight[] = $this->genOnRight($rightFieldJoinName, $leftFieldJoinName);
                continue;
            }

            $onBoth[] = $this->genOnBoth($rightFieldJoinName, $leftFieldJoinName);
        }

        $this->onBoth = implode(' AND ', $onBoth);
        $this->onRight = implode(' AND ', $onRight);

        if (!$this->onBoth && !$this->onRight) {
            throw new Exception(self::class . ': this field is not available for JOIN');
        }
    }

    private function genOnBoth(string $rightFieldJoinName, string $leftFieldJoinName): string {
        $left = $this->quoteQualified($this->leftTableAlias, $leftFieldJoinName);
        $right = $this->quoteQualified($this->rightTableAlias, $rightFieldJoinName);

        return "$right = $left";
    }

    private function genOnRight(string $rightFieldJoinName, string $valuesSQL): string {
        $right = $this->quoteQualified($this->rightTableAlias, $rightFieldJoinName);

        return "$right IN($valuesSQL)";
    }

    private function quoteTableName(string $tableName): string {
        return '`' . str_replace(['`', '.'], ['', '`.`'], $tableName) . '`';
    }

    private function quoteIdentifier(string $name): string {
        return '`' . str_replace('`', '', $name) . '`';
    }

    private function quoteQualified(string $tableAlias, string $fieldName): string {
        $tableAliasPrepared = str_replace('`', '', $tableAlias);
        $fieldPrepared = str_replace('`', '', $fieldName);

        if (str_contains($fieldPrepared, '.')) {
            return '`' . str_replace('.', '`.`', $fieldPrepared) . '`';
        }

        return "`$tableAliasPrepared`.`$fieldPrepared`";
    }

    /**
     * @param array<string, string> $joiners
     * @return array<string, string>
     */
    private function normalizeJoiners(array $joiners): array {
        ksort($joiners);
        return $joiners;
    }
}

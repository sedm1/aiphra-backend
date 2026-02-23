<?php

namespace DB\PDO;

abstract class AbstractDB {
    public string $query = '';

    public function prepare_table_name(string $table_name): string {
        return str_replace(['`', '.'], ['', '`.`'], $table_name);
    }

    public static function prepare_column_name(string $column_name): string {
        return str_replace(['`', '.'], ['', '`.`'], $column_name);
    }

    abstract public static function prepareSql(string $sql): string;

    abstract public function prepare_val(mixed $val, bool $sanitize = false): string;

    final protected function dbhInit(): mixed {
        $dbh = $this->dbhInitConnect();
        $this->dbhInitVariables($dbh);

        return $dbh;
    }

    abstract protected function dbhInitConnect();

    protected function dbhInitVariables($dbh): void {
    }

    public function query(string $query): static {
        $this->query = $query;
        return $this;
    }

    public function appendQuery(string $query): static {
        $this->query .= $query;
        return $this;
    }

    abstract public function with(string $sql): static;

    abstract public function sel(string|array $fieldsNames = '*'): static;

    abstract public function from(self|string $dbhOrTableName = '', string $alias = ''): static;

    abstract public function join(self|string $dbhOrTableName = '', string $typeJoin = '', string $alias = ''): static;

    abstract public function using(string $fieldsNames = ''): static;

    abstract public function on(string $where = ''): static;

    abstract public function w(string|array $where = ''): static;

    abstract public function g(string $groupBy = ''): static;

    abstract public function h(string $having = ''): static;

    abstract public function o(string $order = ''): static;

    abstract public function l(int|string $limit = ''): static;

    abstract public function fetchColumn(int $columnNumber = 0): mixed;

    abstract public function fetch(?int $fetchStyle = null): mixed;

    abstract public function fetchAll(?int $fetchStyle = null): array;

    abstract public function insert(string $tableName, string|array $fieldsNames = ''): static;

    abstract public function set(string|array $data): static;

    abstract public function setValues(array $values, bool $sanitize = false): static;

    abstract public function del(string $tableName, string $alias = ''): static;

    abstract public function exec(string $query = ''): null|false|int;

    abstract public function selFoundRows(): ?int;

    abstract public function id(): false|string;

    abstract public function connection_id(): false|string;

    abstract public function getMeta(): array;
}

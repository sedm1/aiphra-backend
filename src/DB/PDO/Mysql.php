<?php

namespace DB\PDO;

use DateTime;
use JetBrains\PhpStorm\ArrayShape;
use PDO;
use PDOStatement;

class Mysql extends AbstractDB {
    protected array $config;
    public PDO $dbh;
    protected ?PDOStatement $pdoStatement = null;
    public string $query = '';

    public function __construct(&$dbh, &$config) {
        $this->config = &$config;
        if (!$dbh) {
            $dbh = $this->dbhInit();
        }
        $this->dbh = &$dbh;
    }

    protected function dbhInitConnect(): PDO {
        $host = $this->config['host'] ?? '127.0.0.1';
        $port = $this->config['port'] ?? 3306;
        $db = $this->config['dbname'] ?? '';
        $type = $this->config['type'] ?? 'mysql';
        $charset = $this->config['charset'] ?? 'utf8mb4';

        $dsn = $type . ':host=' . $host . ';port=' . $port . ';dbname=' . $db . ';charset=' . $charset;

        return new PDO($dsn, $this->config['user'] ?? '', $this->config['pass'] ?? '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    protected function dbhInitVariables($dbh): void {
        $dbh->query('SET NAMES utf8mb4');
    }

    public static function prepareSql(string $sql): string {
        return $sql;
    }

    public function prepare_val(mixed $val, bool $sanitize = false): string {
        if ($val instanceof DateTime) {
            return 'FROM_UNIXTIME(' . $val->getTimestamp() . ')';
        }
        if (is_null($val)) return 'null';
        if (is_bool($val)) return $val ? '1' : '0';
        if (is_int($val) || is_float($val)) return (string)$val;

        return $this->dbh->quote((string)$val);
    }

    public function with(string $sql): static {
        $this->query .= 'WITH ' . $sql . ' ';
        return $this;
    }

    public function sel(string|array $fieldsNames = '*'): static {
        $this->query = 'SELECT ';

        if ($fieldsNames === '*') {
            $this->query .= '* ';
        } elseif (is_array($fieldsNames)) {
            $fieldStr = implode('`, `', $fieldsNames);
            $fieldStr = '`' . str_replace('.', '`.`', $fieldStr) . '` ';
            $this->query .= $fieldStr;
        } else {
            $this->query .= $fieldsNames . ' ';
        }

        return $this;
    }

    public function from(AbstractDB|string $dbhOrTableName = '', string $alias = ''): static {
        if ($dbhOrTableName instanceof AbstractDB) {
            $queryFrom = '(' . $dbhOrTableName->query . ')';
        } else {
            $queryFrom = $this->prepare_table_name($dbhOrTableName);
            $queryFrom = '`' . $queryFrom . '`';
        }
        $this->query .= 'FROM ' . $queryFrom . ' ';
        if ($alias) $this->query .= '`' . $alias . '` ';

        return $this;
    }

    public function join(AbstractDB|string $dbhOrTableName = '', string $typeJoin = '', string $alias = ''): static {
        if ($dbhOrTableName instanceof AbstractDB) {
            $queryFrom = '(' . $dbhOrTableName->query . ')';
        } else {
            $queryFrom = $this->prepare_table_name($dbhOrTableName);
            $queryFrom = '`' . $queryFrom . '`';
        }

        switch ($typeJoin) {
            case 'l':
            case 'left':
                $this->query .= 'LEFT ';
                break;
            case 'r':
            case 'right':
                $this->query .= 'RIGHT ';
                break;
            default:
                $this->query .= 'INNER ';
        }

        $this->query .= 'JOIN ' . $queryFrom . ' ';
        if ($alias) $this->query .= '`' . $alias . '` ';

        return $this;
    }

    public function using(string $fieldsNames = ''): static {
        if ($fieldsNames) $this->query .= 'USING (' . $fieldsNames . ') ';
        return $this;
    }

    public function on(string $where = ''): static {
        if ($where) $this->query .= 'ON (' . $where . ') ';
        return $this;
    }

    public function where(string|array $where = ''): static {
        if ($where) {
            if (is_array($where)) {
                $parts = [];
                foreach ($where as $name => $val) {
                    if (is_array($val)) {
                        if (!$val) {
                            $parts[] = '0';
                        } else {
                            $valuesPrepared = array_map(fn($v) => $this->prepare_val($v), $val);
                            $parts[] = '`' . static::prepare_column_name($name) . '` IN(' . implode(',', $valuesPrepared) . ')';
                        }
                    } else {
                        $parts[] = '`' . static::prepare_column_name($name) . '` = ' . $this->prepare_val($val);
                    }
                }
                $where = implode(' AND ', $parts);
            }

            $this->query .= 'WHERE (' . $where . ') ';
        }

        return $this;
    }

    public function w(string|array $where = ''): static {
        return $this->where($where);
    }

    public function group(string $groupBy = ''): static {
        if ($groupBy) $this->query .= 'GROUP BY ' . $groupBy . ' ';
        return $this;
    }

    public function g(string $groupBy = ''): static {
        return $this->group($groupBy);
    }

    public function having(string $having = ''): static {
        if ($having) $this->query .= 'HAVING (' . $having . ') ';
        return $this;
    }

    public function h(string $having = ''): static {
        return $this->having($having);
    }

    public function order(string $order = ''): static {
        if ($order) $this->query .= 'ORDER BY ' . $order . ' ';
        return $this;
    }

    public function o(string $order = ''): static {
        return $this->order($order);
    }

    public function limit(int|string $limit = ''): static {
        if ($limit !== '') $this->query .= 'LIMIT ' . $limit . ' ';
        return $this;
    }

    public function l(int|string $limit = ''): static {
        return $this->limit($limit);
    }

    public function handler(): ?PDOStatement {
        return $this->dbh->query($this->query);
    }

    public function fetchColumn(int $columnNumber = 0): mixed {
        $this->pdoStatement = $this->dbh->query($this->query);
        if ($this->pdoStatement) return $this->pdoStatement->fetchColumn($columnNumber);
        return null;
    }

    public function fetch(?int $fetchStyle = null): mixed {
        if (!$fetchStyle) $fetchStyle = PDO::FETCH_ASSOC;

        $this->pdoStatement = $this->dbh->query($this->query);
        if ($this->pdoStatement) return $this->pdoStatement->fetch($fetchStyle);
        return null;
    }

    public function fetchAll(?int $fetchStyle = null): array {
        if (!$fetchStyle) $fetchStyle = PDO::FETCH_ASSOC;

        $this->pdoStatement = $this->dbh->query($this->query);
        if ($this->pdoStatement) return $this->pdoStatement->fetchAll($fetchStyle);
        return [];
    }

    public function insert(string $tableName, string|array $fieldsNames = ''): static {
        $tableName = $this->prepare_table_name($tableName);

        $this->query = 'INSERT INTO `' . $tableName . '` ';

        if ($fieldsNames) {
            if (is_array($fieldsNames)) {
                $fieldStr = '`' . implode('`, `', $fieldsNames) . '`';
            } else {
                $fieldStr = $fieldsNames;
            }

            $this->query .= '(' . $fieldStr . ') ';
        }

        return $this;
    }

    public function insertValues(string $tableName, array $sets): static {
        $this->query = '';

        if (!$sets) return $this;

        if (!isset($sets[0])) $sets = array_values($sets);

        $fieldNames = array_keys($sets[0]);

        $this->insert($tableName, $fieldNames)->appendQuery(' VALUES ');

        foreach ($sets as $i => $set) {
            if ($i) $this->appendQuery(',');

            $this->setValues($set);
        }

        return $this;
    }

    public function update(string $tableName, string $alias = ''): static {
        $tableName = $this->prepare_table_name($tableName);

        $this->query = 'UPDATE `' . $tableName . '` ';
        if ($alias) $this->query .= '`' . $alias . '` ';

        return $this;
    }

    public function replace(string $tableName): static {
        $tableName = $this->prepare_table_name($tableName);

        $this->query = 'REPLACE INTO `' . $tableName . '` ';

        return $this;
    }

    public function set(string|array $data): static {
        $this->query .= 'SET ';

        if (is_array($data)) {
            $dataStr = '';
            foreach ($data as $name => $val) {
                $preparedColumnName = static::prepare_column_name($name);
                $preparedVal = $this->prepare_val($val);
                $dataStr .= '`' . $preparedColumnName . '` = ' . $preparedVal . ',';
            }
            $dataStr = substr($dataStr, 0, strlen($dataStr) - 1);
        } else {
            $dataStr = $data;
        }

        $this->query .= $dataStr . ' ';

        return $this;
    }

    public function setValues(array $values, bool $sanitize = false): static {
        foreach ($values as &$_value) {
            $_value = $this->prepare_val($_value);
        }
        $this->query .= '(' . implode(',', $values) . ')';

        return $this;
    }

    public function del(string $tableName, string $alias = ''): static {
        $tableName = $this->prepare_table_name($tableName);
        $this->query = 'DELETE `' . ($alias ?: $tableName) . '` FROM `' . $tableName . '` ';
        if ($alias) $this->query .= '`' . $alias . '` ';

        return $this;
    }

    public function exec(string $query = ''): null|false|int {
        if ($query) $this->query = $query;

        return $this->dbh->exec($this->query);
    }

    public function beginTransaction(): bool {
        return $this->dbh->beginTransaction();
    }

    public function commit(): bool {
        return $this->dbh->commit();
    }

    public function selFoundRows(): ?int {
        $query = $this->query;
        $res = $this->query('SELECT FOUND_ROWS()')->fetchColumn();
        $this->query = $query;

        return $res;
    }

    public function id(): false|string {
        return $this->dbh->lastInsertId();
    }

    public function connection_id(): false|string {
        return $this->sel('CONNECTION_ID()')->fetchColumn();
    }

    public function getMeta(): array {
        if (!isset($this->pdoStatement)) return [];

        $meta = [];
        for ($i = 0; $i < $this->pdoStatement->columnCount(); $i++) {
            $meta[] = $this->pdoStatement->getColumnMeta($i);
        }

        return $meta;
    }
}

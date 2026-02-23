<?php
declare(strict_types=1);

namespace Aiphra\Backend\Database;

use PDO;
use PDOException;
use PDOStatement;

final class DB
{
    private PDO $pdo;

    /**
     * @param array{
     *   host: string,
     *   port?: int,
     *   dbname: string,
     *   user: string,
     *   pass: string,
     *   charset?: string
     * } $config
     */
    public function __construct(array $config)
    {
        $host = $config['host'];
        $port = $config['port'] ?? 3306;
        $db = $config['dbname'];
        $charset = $config['charset'] ?? 'utf8mb4';

        $dsn = "mysql:host={$host};port={$port};dbname={$db};charset={$charset}";

        $this->pdo = new PDO($dsn, $config['user'], $config['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    public static function fromEnv(): self
    {
        return new self([
            'host' => (string) getenv('DB_HOST'),
            'port' => (int) (getenv('DB_PORT') ?: 3306),
            'dbname' => (string) getenv('DB_NAME'),
            'user' => (string) getenv('DB_USER'),
            'pass' => (string) getenv('DB_PASS'),
            'charset' => (string) (getenv('DB_CHARSET') ?: 'utf8mb4'),
        ]);
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    public function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt;
    }

    public function fetch(string $sql, array $params = []): ?array
    {
        $row = $this->query($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    public function insert(string $table, array $data): string
    {
        $this->assertIdent($table);
        if (!$data) {
            throw new PDOException('Insert data is empty');
        }

        $cols = array_keys($data);
        foreach ($cols as $col) {
            $this->assertIdent($col);
        }

        $placeholders = [];
        $params = [];
        foreach ($data as $col => $value) {
            $ph = ':' . $col;
            $placeholders[] = $ph;
            $params[$ph] = $value;
        }

        $sql = 'INSERT INTO `' . $table . '` (`' . implode('`,`', $cols) . '`) VALUES (' . implode(',', $placeholders) . ')';
        $this->query($sql, $params);

        return $this->pdo->lastInsertId();
    }

    public function update(string $table, array $data, array $where): int
    {
        $this->assertIdent($table);
        if (!$data) {
            throw new PDOException('Update data is empty');
        }

        $params = [];
        $setSql = $this->buildSet($data, $params);
        $whereSql = $this->buildWhere($where, $params);

        $sql = 'UPDATE `' . $table . '` SET ' . $setSql . $whereSql;
        return $this->query($sql, $params)->rowCount();
    }

    public function delete(string $table, array $where): int
    {
        $this->assertIdent($table);
        $params = [];
        $whereSql = $this->buildWhere($where, $params);
        $sql = 'DELETE FROM `' . $table . '`' . $whereSql;

        return $this->query($sql, $params)->rowCount();
    }

    public function select(
        string $table,
        array $where = [],
        array $fields = ['*'],
        string $orderBy = '',
        ?int $limit = null,
        ?int $offset = null
    ): array {
        $this->assertIdent($table);
        foreach ($fields as $field) {
            if ($field !== '*') {
                $this->assertIdent($field);
            }
        }

        $params = [];
        $fieldSql = ($fields === ['*']) ? '*' : '`' . implode('`,`', $fields) . '`';
        $sql = 'SELECT ' . $fieldSql . ' FROM `' . $table . '`';
        $sql .= $this->buildWhere($where, $params);
        if ($orderBy !== '') {
            $this->assertOrderBy($orderBy);
            $sql .= ' ORDER BY ' . $orderBy;
        }
        if ($limit !== null) {
            $sql .= ' LIMIT ' . (int) $limit;
            if ($offset !== null) {
                $sql .= ' OFFSET ' . (int) $offset;
            }
        }

        return $this->fetchAll($sql, $params);
    }

    private function buildSet(array $data, array &$params): string
    {
        $parts = [];
        foreach ($data as $col => $value) {
            $this->assertIdent($col);
            $ph = ':set_' . $col;
            $parts[] = '`' . $col . '` = ' . $ph;
            $params[$ph] = $value;
        }
        return implode(', ', $parts);
    }

    private function buildWhere(array $where, array &$params): string
    {
        if (!$where) {
            return '';
        }

        $parts = [];
        $i = 0;
        foreach ($where as $col => $value) {
            $this->assertIdent($col);
            if (is_array($value)) {
                if (!$value) {
                    $parts[] = '1=0';
                    continue;
                }
                $in = [];
                foreach ($value as $j => $v) {
                    $ph = ':w' . $i . '_' . $j;
                    $in[] = $ph;
                    $params[$ph] = $v;
                }
                $parts[] = '`' . $col . '` IN (' . implode(',', $in) . ')';
            } elseif ($value === null) {
                $parts[] = '`' . $col . '` IS NULL';
            } else {
                $ph = ':w' . $i;
                $parts[] = '`' . $col . '` = ' . $ph;
                $params[$ph] = $value;
            }
            $i++;
        }

        return ' WHERE ' . implode(' AND ', $parts);
    }

    private function assertIdent(string $name): void
    {
        if (!preg_match('/^[a-zA-Z0-9_\\.]+$/', $name)) {
            throw new PDOException('Invalid identifier: ' . $name);
        }
    }

    private function assertOrderBy(string $orderBy): void
    {
        if (!preg_match('/^[a-zA-Z0-9_\\.\\s,`]+$/', $orderBy)) {
            throw new PDOException('Invalid ORDER BY: ' . $orderBy);
        }
    }
}

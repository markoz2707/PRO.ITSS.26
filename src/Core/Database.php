<?php

namespace ITSS\Core;

use PDO;
use PDOException;

class Database
{
    private static ?Database $instance = null;
    private PDO $connection;
    private array $config;

    private function __construct(array $config)
    {
        $this->config = $config;
        $this->connect();
    }

    public static function getInstance(array $config = null): Database
    {
        if (self::$instance === null) {
            if ($config === null) {
                throw new \RuntimeException('Database configuration required for first initialization');
            }
            self::$instance = new self($config);
        }
        return self::$instance;
    }

    private function connect(): void
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $this->config['host'],
            $this->config['port'],
            $this->config['name'],
            $this->config['charset']
        );

        try {
            $this->connection = new PDO(
                $dsn,
                $this->config['user'],
                $this->config['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->config['charset']}"
                ]
            );
        } catch (PDOException $e) {
            throw new \RuntimeException('Database connection failed: ' . $e->getMessage());
        }
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }

    public function query(string $sql, array $params = []): \PDOStatement
    {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            Logger::error('Database query error: ' . $e->getMessage(), [
                'sql' => $sql,
                'params' => $params
            ]);
            throw $e;
        }
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    public function fetchOne(string $sql, array $params = []): ?array
    {
        $result = $this->query($sql, $params)->fetch();
        return $result ?: null;
    }

    public function insert(string $table, array $data): int
    {
        $columns = array_keys($data);
        $placeholders = array_map(fn($col) => ":$col", $columns);

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $this->query($sql, $data);
        return (int) $this->connection->lastInsertId();
    }

    public function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $setParts = array_map(fn($col) => "$col = :$col", array_keys($data));

        $sql = sprintf(
            'UPDATE %s SET %s WHERE %s',
            $table,
            implode(', ', $setParts),
            $where
        );

        $params = array_merge($data, $whereParams);
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    public function delete(string $table, string $where, array $whereParams = []): int
    {
        $sql = sprintf('DELETE FROM %s WHERE %s', $table, $where);
        $stmt = $this->query($sql, $whereParams);
        return $stmt->rowCount();
    }

    public function beginTransaction(): bool
    {
        return $this->connection->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->connection->commit();
    }

    public function rollback(): bool
    {
        return $this->connection->rollBack();
    }
}

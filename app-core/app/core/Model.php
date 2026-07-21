<?php

namespace App\Core;

abstract class Model
{
    protected string $table = '';
    protected string $primaryKey = 'id';

    protected function db(): \PDO
    {
        return Database::pdo();
    }

    public function all(string $orderBy = null): array
    {
        $sql = "SELECT * FROM {$this->table}" . ($orderBy ? " ORDER BY {$orderBy}" : '');
        return $this->db()->query($sql)->fetchAll();
    }

    public function find($id): ?array
    {
        $stmt = $this->db()->prepare("SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findBy(string $col, $val): ?array
    {
        $stmt = $this->db()->prepare("SELECT * FROM {$this->table} WHERE {$col} = ? LIMIT 1");
        $stmt->execute([$val]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function where(string $col, $val): array
    {
        $stmt = $this->db()->prepare("SELECT * FROM {$this->table} WHERE {$col} = ?");
        $stmt->execute([$val]);
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $cols = array_keys($data);
        $ph   = array_map(fn($c) => ":{$c}", $cols);
        $sql  = "INSERT INTO {$this->table} (" . implode(',', $cols) . ") VALUES (" . implode(',', $ph) . ")";
        $stmt = $this->db()->prepare($sql);
        $stmt->execute($data);
        return (int)$this->db()->lastInsertId();
    }

    public function update($id, array $data): bool
    {
        $sets = implode(',', array_map(fn($c) => "{$c} = :{$c}", array_keys($data)));
        $data[$this->primaryKey] = $id;
        $sql  = "UPDATE {$this->table} SET {$sets} WHERE {$this->primaryKey} = :{$this->primaryKey}";
        return $this->db()->prepare($sql)->execute($data);
    }

    public function delete($id): bool
    {
        $stmt = $this->db()->prepare("DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?");
        return $stmt->execute([$id]);
    }

    public function count(string $where = '', array $params = []): int
    {
        $sql  = "SELECT COUNT(*) FROM {$this->table}" . ($where ? " WHERE {$where}" : '');
        $stmt = $this->db()->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }
}

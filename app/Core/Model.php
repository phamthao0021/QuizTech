<?php
declare(strict_types=1);

namespace App\Core;

use App\Config\Database;

abstract class Model
{
    protected static \PDO $db;
    protected string $table;
    protected array $fillable = [];

    public function __construct()
    {
        self::$db = Database::getConnection();
    }

    public static function getDb(): \PDO
    {
        return self::$db;
    }

    public function findAll(array $conditions = [], string $order = ''): array
    {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];

        if (!empty($conditions)) {
            $where = [];
            foreach ($conditions as $key => $value) {
                $where[] = "$key = ?";
                $params[] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        if ($order) {
            $sql .= " ORDER BY $order";
        }

        $stmt = self::$db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = self::$db->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int
    {
        $fields = array_intersect_key($data, array_flip($this->fillable));
        $columns = implode(', ', array_keys($fields));
        $placeholders = implode(', ', array_fill(0, count($fields), '?'));
        
        $stmt = self::$db->prepare("INSERT INTO {$this->table} ($columns) VALUES ($placeholders)");
        $stmt->execute(array_values($fields));
        return (int)self::$db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $fields = array_intersect_key($data, array_flip($this->fillable));
        $set = implode(' = ?, ', array_keys($fields)) . ' = ?';
        
        $stmt = self::$db->prepare("UPDATE {$this->table} SET $set WHERE id = ?");
        return $stmt->execute(array_merge(array_values($fields), [$id]));
    }

    public function delete(int $id): bool
    {
        $stmt = self::$db->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function count(array $conditions = []): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table}";
        $params = [];

        if (!empty($conditions)) {
            $where = [];
            foreach ($conditions as $key => $value) {
                $where[] = "$key = ?";
                $params[] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $stmt = self::$db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function query(string $sql, array $params = []): array
    {
        $stmt = self::$db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function queryOne(string $sql, array $params = []): ?array
    {
        $stmt = self::$db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch() ?: null;
    }
}
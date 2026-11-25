<?php
namespace App\Core;

use App\Core\Database;
use PDO;

abstract class Model {
    protected string $table;
    protected string $primaryKey = 'id';

    public function find($id): ?array {
        $stmt = Database::getConnection()->prepare("SELECT * FROM `{$this->table}` WHERE `{$this->primaryKey}` = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function all(string $orderBy = 'id DESC'): array {
        $stmt = Database::getConnection()->query("SELECT * FROM `{$this->table}` ORDER BY {$orderBy}");
        return $stmt->fetchAll();
    }

    public function create(array $data): int {
        $keys = array_keys($data);
        $fields = implode('`, `', $keys);
        $placeholders = implode(', ', array_fill(0, count($keys), '?'));

        $sql = "INSERT INTO `{$this->table}` (`{$fields}`) VALUES ({$placeholders})";
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute(array_values($data));

        return (int) Database::getConnection()->lastInsertId();
    }

    public function update($id, array $data): bool {
        $fields = [];
        foreach (array_keys($data) as $field) {
            $fields[] = "`{$field}` = ?";
        }
        $setClause = implode(', ', $fields);

        $sql = "UPDATE `{$this->table}` SET {$setClause} WHERE `{$this->primaryKey}` = ?";
        $params = array_values($data);
        $params[] = $id;

        $stmt = Database::getConnection()->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete($id): bool {
        $stmt = Database::getConnection()->prepare("DELETE FROM `{$this->table}` WHERE `{$this->primaryKey}` = ?");
        return $stmt->execute([$id]);
    }
}

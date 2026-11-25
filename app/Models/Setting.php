<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class Setting extends Model {
    protected string $table = 'settings';

    public static function get(string $key, $default = null) {
        try {
            $stmt = Database::getConnection()->prepare("SELECT `value` FROM settings WHERE `key` = ?");
            $stmt->execute([$key]);
            $val = $stmt->fetchColumn();
            return $val !== false ? $val : $default;
        } catch (\Exception $e) {
            return $default;
        }
    }

    public static function set(string $key, string $value, string $group = 'general', ?string $description = null): void {
        $stmt = Database::getConnection()->prepare("INSERT INTO settings (`key`, `value`, `group`, `description`) 
            VALUES (?, ?, ?, ?) 
            ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), `updated_at` = CURRENT_TIMESTAMP");
        $stmt->execute([$key, $value, $group, $description]);
    }

    public static function getAllGrouped(): array {
        $stmt = Database::getConnection()->query("SELECT * FROM settings ORDER BY `group`, `key`");
        $rows = $stmt->fetchAll();
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['group']][$row['key']] = $row['value'];
        }
        return $grouped;
    }
}

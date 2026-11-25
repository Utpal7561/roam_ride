<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class Notification extends Model {
    protected string $table = 'notifications';

    public static function createForUser(int $userId, string $title, string $message, string $type = 'system', ?string $link = null): int {
        $stmt = Database::getConnection()->prepare("INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $title, $message, $type, $link]);
        return (int) Database::getConnection()->lastInsertId();
    }

    public function getUserNotifications(int $userId, int $limit = 20): array {
        $stmt = Database::getConnection()->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT ?");
        $stmt->bindValue(1, $userId, \PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function markAsRead(int $id, int $userId): bool {
        $stmt = Database::getConnection()->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        return $stmt->execute([$id, $userId]);
    }
}

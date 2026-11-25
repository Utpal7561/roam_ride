<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class User extends Model {
    protected string $table = 'users';

    public function findByPhone(string $phone): ?array {
        $stmt = Database::getConnection()->prepare("SELECT * FROM users WHERE phone = ?");
        $stmt->execute([$phone]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByEmail(string $email): ?array {
        $stmt = Database::getConnection()->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByLoginIdentifier(string $identifier): ?array {
        $stmt = Database::getConnection()->prepare("SELECT * FROM users WHERE phone = ? OR email = ?");
        $stmt->execute([$identifier, $identifier]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}

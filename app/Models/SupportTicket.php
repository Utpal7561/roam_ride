<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class SupportTicket extends Model {
    protected string $table = 'support_tickets';

    public function getFiltered(array $filters = []): array {
        $sql = "SELECT t.*, u.name as assigned_name, b.booking_code 
                FROM support_tickets t
                LEFT JOIN users u ON t.assigned_to_user_id = u.id
                LEFT JOIN bookings b ON t.booking_id = b.id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND t.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['category'])) {
            $sql .= " AND t.category = ?";
            $params[] = $filters['category'];
        }

        if (!empty($filters['user_id'])) {
            $sql .= " AND t.user_id = ?";
            $params[] = $filters['user_id'];
        }

        $sql .= " ORDER BY t.id DESC";
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}

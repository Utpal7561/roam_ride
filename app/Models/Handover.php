<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class Handover extends Model {
    protected string $table = 'vehicle_handover';

    public function getForBooking(int $bookingId): array {
        $sql = "SELECT h.*, s.name as staff_name, c.name as customer_name, v.name as vehicle_name, v.reg_number as vehicle_reg 
                FROM vehicle_handover h
                LEFT JOIN users s ON h.staff_id = s.id
                LEFT JOIN users c ON h.customer_id = c.id
                LEFT JOIN vehicles v ON h.vehicle_id = v.id
                WHERE h.booking_id = ?
                ORDER BY h.id ASC";
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute([$bookingId]);
        return $stmt->fetchAll();
    }
}

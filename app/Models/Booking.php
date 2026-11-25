<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class Booking extends Model {
    protected string $table = 'bookings';

    public function findWithDetails($id): ?array {
        $sql = "SELECT b.*, 
                    v.name as vehicle_name, v.brand as vehicle_brand, v.model as vehicle_model, v.reg_number as vehicle_reg, v.primary_image as vehicle_image,
                    av.name as assigned_vehicle_name, av.reg_number as assigned_vehicle_reg,
                    pl.name as pickup_location_name, pl.address as pickup_location_address, pl.phone as pickup_location_phone,
                    rl.name as return_location_name, rl.address as return_location_address,
                    u.name as user_name, u.phone as user_phone, u.email as user_email
                FROM bookings b
                LEFT JOIN vehicles v ON b.vehicle_id = v.id
                LEFT JOIN vehicles av ON b.assigned_vehicle_id = av.id
                LEFT JOIN locations pl ON b.pickup_location_id = pl.id
                LEFT JOIN locations rl ON b.return_location_id = rl.id
                LEFT JOIN users u ON b.customer_id = u.id
                WHERE b.id = ? OR b.booking_code = ?";
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute([$id, $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getCustomerBookings(int $customerId): array {
        $sql = "SELECT b.*, 
                    v.name as vehicle_name, v.brand as vehicle_brand, v.primary_image as vehicle_image,
                    pl.name as pickup_location_name, rl.name as return_location_name
                FROM bookings b
                LEFT JOIN vehicles v ON b.vehicle_id = v.id
                LEFT JOIN locations pl ON b.pickup_location_id = pl.id
                LEFT JOIN locations rl ON b.return_location_id = rl.id
                WHERE b.customer_id = ?
                ORDER BY b.id DESC";
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute([$customerId]);
        return $stmt->fetchAll();
    }

    public function getFilteredBookings(array $filters = []): array {
        $sql = "SELECT b.*, 
                    v.name as vehicle_name, v.brand as vehicle_brand,
                    av.name as assigned_vehicle_name, av.reg_number as assigned_vehicle_reg,
                    pl.name as pickup_location_name,
                    u.name as user_name, u.phone as user_phone
                FROM bookings b
                LEFT JOIN vehicles v ON b.vehicle_id = v.id
                LEFT JOIN vehicles av ON b.assigned_vehicle_id = av.id
                LEFT JOIN locations pl ON b.pickup_location_id = pl.id
                LEFT JOIN users u ON b.customer_id = u.id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['booking_status'])) {
            $sql .= " AND b.booking_status = ?";
            $params[] = $filters['booking_status'];
        }

        if (!empty($filters['payment_status'])) {
            $sql .= " AND b.payment_status = ?";
            $params[] = $filters['payment_status'];
        }

        if (!empty($filters['vehicle_id'])) {
            $sql .= " AND (b.vehicle_id = ? OR b.assigned_vehicle_id = ?)";
            $params[] = $filters['vehicle_id'];
            $params[] = $filters['vehicle_id'];
        }

        if (!empty($filters['location_id'])) {
            $sql .= " AND b.pickup_location_id = ?";
            $params[] = $filters['location_id'];
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(b.pickup_datetime) >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(b.pickup_datetime) <= ?";
            $params[] = $filters['date_to'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (b.booking_code LIKE ? OR b.customer_name LIKE ? OR b.customer_phone LIKE ?)";
            $term = '%' . $filters['search'] . '%';
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        $sql .= " ORDER BY b.id DESC";

        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function addStatusHistory(int $bookingId, string $status, ?string $notes = null, ?int $userId = null): void {
        $stmt = Database::getConnection()->prepare("INSERT INTO booking_status_history (booking_id, status, notes, changed_by_user_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$bookingId, $status, $notes, $userId]);
    }

    public function getStatusHistory(int $bookingId): array {
        $sql = "SELECT h.*, u.name as changed_by_name, u.role as changed_by_role 
                FROM booking_status_history h
                LEFT JOIN users u ON h.changed_by_user_id = u.id
                WHERE h.booking_id = ?
                ORDER BY h.id ASC";
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute([$bookingId]);
        return $stmt->fetchAll();
    }
}

<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class Vehicle extends Model {
    protected string $table = 'vehicles';

    public function getFiltered(array $filters = []): array {
        $sql = "SELECT v.*, c.name as category_name, c.slug as category_slug, l.name as location_name, l.city as location_city 
                FROM vehicles v
                LEFT JOIN vehicle_categories c ON v.category_id = c.id
                LEFT JOIN locations l ON v.pickup_location_id = l.id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['category_id'])) {
            $sql .= " AND v.category_id = ?";
            $params[] = $filters['category_id'];
        }

        if (!empty($filters['category_slug'])) {
            $sql .= " AND c.slug = ?";
            $params[] = $filters['category_slug'];
        }

        if (!empty($filters['availability_status'])) {
            $sql .= " AND v.availability_status = ?";
            $params[] = $filters['availability_status'];
        }

        if (!empty($filters['fuel_type'])) {
            $sql .= " AND v.fuel_type = ?";
            $params[] = $filters['fuel_type'];
        }

        if (!empty($filters['transmission'])) {
            $sql .= " AND v.transmission = ?";
            $params[] = $filters['transmission'];
        }

        if (!empty($filters['min_price'])) {
            $sql .= " AND v.daily_rate >= ?";
            $params[] = $filters['min_price'];
        }

        if (!empty($filters['max_price'])) {
            $sql .= " AND v.daily_rate <= ?";
            $params[] = $filters['max_price'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (v.name LIKE ? OR v.brand LIKE ? OR v.model LIKE ?)";
            $term = '%' . $filters['search'] . '%';
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        if (!empty($filters['featured'])) {
            $sql .= " AND v.is_featured = 1";
        }

        // Sorting
        $sort = $filters['sort'] ?? 'popular';
        switch ($sort) {
            case 'price_low':
                $sql .= " ORDER BY v.daily_rate ASC";
                break;
            case 'price_high':
                $sql .= " ORDER BY v.daily_rate DESC";
                break;
            case 'name_asc':
                $sql .= " ORDER BY v.name ASC";
                break;
            case 'newest':
                $sql .= " ORDER BY v.year DESC, v.id DESC";
                break;
            case 'popular':
            default:
                $sql .= " ORDER BY v.is_featured DESC, v.id DESC";
                break;
        }

        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findWithDetails($id): ?array {
        $sql = "SELECT v.*, c.name as category_name, c.slug as category_slug, l.name as location_name, l.address as location_address, l.city as location_city, l.phone as location_phone 
                FROM vehicles v
                LEFT JOIN vehicle_categories c ON v.category_id = c.id
                LEFT JOIN locations l ON v.pickup_location_id = l.id
                WHERE v.id = ? OR v.slug = ?";
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute([$id, $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Check if a specific vehicle is available for a date/time range.
     * Prevents overlapping bookings or maintenance blocks.
     */
    public function isAvailableForPeriod(int $vehicleId, string $startDateTime, string $endDateTime, ?int $excludeBookingId = null): bool {
        $pdo = Database::getConnection();

        // 1. Check vehicle operational availability status
        $vStmt = $pdo->prepare("SELECT availability_status FROM vehicles WHERE id = ?");
        $vStmt->execute([$vehicleId]);
        $vStatus = $vStmt->fetchColumn();

        if (in_array($vStatus, ['maintenance', 'out_of_service'])) {
            return false;
        }

        // 2. Check overlapping bookings (excluding cancelled or rejected)
        $sql = "SELECT COUNT(*) FROM bookings 
                WHERE (vehicle_id = ? OR assigned_vehicle_id = ?)
                AND booking_status NOT IN ('cancelled', 'rejected', 'completed')
                AND NOT (return_datetime <= ? OR pickup_datetime >= ?)";
        
        $params = [$vehicleId, $vehicleId, $startDateTime, $endDateTime];
        if ($excludeBookingId !== null) {
            $sql .= " AND id != ?";
            $params[] = $excludeBookingId;
        }

        $bStmt = $pdo->prepare($sql);
        $bStmt->execute($params);

        return (int)$bStmt->fetchColumn() === 0;
    }
}

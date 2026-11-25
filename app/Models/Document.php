<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class Document extends Model {
    protected string $table = 'documents';

    public function getCustomerDocuments(int $customerId, ?int $bookingId = null): array {
        $sql = "SELECT d.*, b.booking_code, v.name as vehicle_name, v.reg_number as vehicle_reg 
                FROM documents d
                LEFT JOIN bookings b ON d.booking_id = b.id
                LEFT JOIN vehicles v ON d.vehicle_id = v.id
                WHERE d.user_id = ? AND d.is_customer_visible = 1";
        $params = [$customerId];

        if ($bookingId !== null) {
            $sql .= " AND d.booking_id = ?";
            $params[] = $bookingId;
        }

        $sql .= " ORDER BY d.id DESC";
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getBookingDocuments(int $bookingId): array {
        $sql = "SELECT d.*, u.name as user_name, u.role as user_role, verifier.name as verified_by_name 
                FROM documents d
                LEFT JOIN users u ON d.user_id = u.id
                LEFT JOIN users verifier ON d.verified_by_user_id = verifier.id
                WHERE d.booking_id = ?
                ORDER BY d.id ASC";
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute([$bookingId]);
        return $stmt->fetchAll();
    }

    public function getPendingVerification(): array {
        $sql = "SELECT d.*, u.name as customer_name, u.phone as customer_phone, b.booking_code 
                FROM documents d
                LEFT JOIN users u ON d.user_id = u.id
                LEFT JOIN bookings b ON d.booking_id = b.id
                WHERE d.doc_type IN ('driving_license_front', 'driving_license_back') 
                AND d.verification_status IN ('uploaded', 'under_review')
                ORDER BY d.id DESC";
        $stmt = Database::getConnection()->query($sql);
        return $stmt->fetchAll();
    }
}

<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class Payment extends Model {
    protected string $table = 'payments';

    public function getBookingPayments(int $bookingId): array {
        $sql = "SELECT p.*, u.name as created_by_name 
                FROM payments p
                LEFT JOIN users u ON p.created_by_user_id = u.id
                WHERE p.booking_id = ?
                ORDER BY p.id ASC";
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute([$bookingId]);
        return $stmt->fetchAll();
    }

    public function getRecentPayments(int $limit = 50): array {
        $sql = "SELECT p.*, b.booking_code, u.name as customer_name, u.phone as customer_phone, staff.name as recorded_by 
                FROM payments p
                LEFT JOIN bookings b ON p.booking_id = b.id
                LEFT JOIN users u ON p.customer_id = u.id
                LEFT JOIN users staff ON p.created_by_user_id = staff.id
                ORDER BY p.id DESC
                LIMIT ?";
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getPaymentSummary(): array {
        $pdo = Database::getConnection();
        
        $totalCollected = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payment_status = 'successful' AND payment_type != 'refund'")->fetchColumn();
        $totalRefunded = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payment_type = 'refund' OR payment_status = 'refunded'")->fetchColumn();
        $outstanding = $pdo->query("SELECT COALESCE(SUM(remaining_balance), 0) FROM bookings WHERE booking_status NOT IN ('cancelled', 'rejected')")->fetchColumn();
        $pendingAdvance = $pdo->query("SELECT COALESCE(SUM(advance_required - amount_paid), 0) FROM bookings WHERE payment_status = 'pending' AND booking_status NOT IN ('cancelled', 'rejected')")->fetchColumn();

        return [
            'total_collected' => (float) $totalCollected,
            'total_refunded' => (float) $totalRefunded,
            'net_revenue' => (float) ($totalCollected - $totalRefunded),
            'outstanding_balance' => (float) $outstanding,
            'pending_advance' => (float) $pendingAdvance,
        ];
    }
}

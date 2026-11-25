<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class Promotion extends Model {
    protected string $table = 'promo_codes';

    public function findByCode(string $code): ?array {
        $stmt = Database::getConnection()->prepare("SELECT * FROM promo_codes WHERE code = ?");
        $stmt->execute([strtoupper(trim($code))]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function validateAndCalculate(string $code, float $bookingAmount, int $customerId): array {
        $promo = $this->findByCode($code);
        if (!$promo) {
            return ['valid' => false, 'message' => 'Invalid promo code.'];
        }

        if (!$promo['is_active']) {
            return ['valid' => false, 'message' => 'This promo code is currently inactive.'];
        }

        $today = date('Y-m-d');
        if ($today < $promo['start_date'] || $today > $promo['expiry_date']) {
            return ['valid' => false, 'message' => 'This promo code has expired.'];
        }

        if ($promo['current_uses'] >= $promo['max_uses']) {
            return ['valid' => false, 'message' => 'Promo code usage limit has been reached.'];
        }

        if ($bookingAmount < (float)$promo['min_booking_amount']) {
            return ['valid' => false, 'message' => "Minimum booking amount of ₹{$promo['min_booking_amount']} required for this coupon."];
        }

        // Check per-customer usage limit
        $stmt = Database::getConnection()->prepare("SELECT COUNT(*) FROM bookings WHERE customer_id = ? AND promo_code = ? AND booking_status != 'cancelled'");
        $stmt->execute([$customerId, $promo['code']]);
        $usedCount = (int)$stmt->fetchColumn();

        if ($usedCount >= $promo['per_customer_limit']) {
            return ['valid' => false, 'message' => 'You have already used this promo code the maximum allowed times.'];
        }

        // Calculate discount
        $discount = 0.00;
        if ($promo['discount_type'] === 'percentage') {
            $discount = round(($bookingAmount * (float)$promo['discount_value']) / 100, 2);
            if ($promo['max_discount_amount'] && $discount > (float)$promo['max_discount_amount']) {
                $discount = (float)$promo['max_discount_amount'];
            }
        } else {
            $discount = min($bookingAmount, (float)$promo['discount_value']);
        }

        return [
            'valid' => true,
            'promo' => $promo,
            'discount' => $discount,
            'message' => 'Promo code applied successfully!'
        ];
    }
}

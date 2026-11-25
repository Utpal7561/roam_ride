<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Database;
use App\Models\Booking;
use App\Models\Vehicle;
use App\Models\User;
use App\Models\Promotion;
use App\Models\Setting;
use App\Models\Notification;
use App\Helpers\Security;
use App\Middleware\RateLimitMiddleware;
use DateTime;
use Exception;

class BookingController extends Controller {
    private Booking $bookingModel;
    private Vehicle $vehicleModel;
    private User $userModel;
    private Promotion $promoModel;

    public function __construct() {
        $this->bookingModel = new Booking();
        $this->vehicleModel = new Vehicle();
        $this->userModel = new User();
        $this->promoModel = new Promotion();
    }

    public function create(Request $request): void {
        RateLimitMiddleware::handle($request, 'booking');

        $data = $this->validate($request->all(), [
            'vehicle_id' => 'required|numeric',
            'pickup_datetime' => 'required|date',
            'return_datetime' => 'required|date',
            'pickup_location_id' => 'required|numeric',
            'customer_name' => 'required|min:2|max:100',
            'customer_phone' => 'required|phone',
            'payment_option' => 'required|in:advance,pay_later,full,offline,whatsapp',
        ]);

        $vehicleId = (int)$data['vehicle_id'];
        $vehicle = $this->vehicleModel->find($vehicleId);

        if (!$vehicle) {
            $this->error('Selected vehicle does not exist.');
        }

        // Validate dates
        $pickupDt = new DateTime($data['pickup_datetime']);
        $returnDt = new DateTime($data['return_datetime']);
        $now = new DateTime();

        if ($pickupDt < $now) {
            $this->error('Pickup date and time cannot be in the past.');
        }

        if ($returnDt <= $pickupDt) {
            $this->error('Return date and time must be after the pickup date and time.');
        }

        // Calculate hours and days
        $interval = $pickupDt->diff($returnDt);
        $totalHours = ($interval->days * 24) + $interval->h + ($interval->i > 0 ? 1 : 0);
        if ($totalHours <= 0) $totalHours = 1;
        $totalDays = max(1, (int) ceil($totalHours / 24));

        // Verify vehicle is not already booked or in maintenance
        if (!$this->vehicleModel->isAvailableForPeriod($vehicleId, $data['pickup_datetime'], $data['return_datetime'])) {
            $this->error('Sorry, this vehicle is unavailable for the selected date and time window. Please choose another vehicle or timeframe.');
        }

        // Identify or auto-create customer account if not logged in
        $currentUser = $request->getUser();
        $customerId = null;

        if ($currentUser) {
            $customerId = $currentUser['id'];
        } else {
            // Find existing user by phone
            $existingUser = $this->userModel->findByPhone(trim($data['customer_phone']));
            if ($existingUser) {
                $customerId = $existingUser['id'];
            } else {
                // Create user automatically
                $custEmail = !empty($data['customer_email']) ? strtolower(trim($data['customer_email'])) : null;
                $defaultPassword = password_hash(substr(preg_replace('/\D/', '', $data['customer_phone']), -6) ?: 'ride123', PASSWORD_BCRYPT);
                
                $customerId = $this->userModel->create([
                    'name' => trim($data['customer_name']),
                    'phone' => trim($data['customer_phone']),
                    'email' => $custEmail,
                    'password_hash' => $defaultPassword,
                    'role' => 'customer',
                    'status' => 'active'
                ]);
            }
        }

        // Server-Side Financial Calculations
        $dailyRate = (float)$vehicle['daily_rate'];
        $hourlyRate = (float)$vehicle['hourly_rate'];
        $deposit = (float)$vehicle['deposit_amount'];

        // If rental is less than 24 hours and hourly rate exists, use hourly
        if ($totalHours < 24 && $hourlyRate > 0) {
            $rentalCharge = round($totalHours * $hourlyRate, 2);
        } else {
            $rentalCharge = round($totalDays * $dailyRate, 2);
        }

        // Check promo discount
        $discountAmount = 0.00;
        $promoCode = null;
        if (!empty($data['promo_code'])) {
            $promoResult = $this->promoModel->validateAndCalculate($data['promo_code'], $rentalCharge, $customerId);
            if ($promoResult['valid']) {
                $discountAmount = $promoResult['discount'];
                $promoCode = $promoResult['promo']['code'];
            }
        }

        $taxPct = (float) Setting::get('tax_percentage', 5);
        $taxableAmount = max(0, $rentalCharge - $discountAmount);
        $taxAmount = round(($taxableAmount * $taxPct) / 100, 2);
        $totalAmount = round($taxableAmount + $taxAmount + $deposit, 2);

        // Advance calculation
        $advancePct = (float) Setting::get('advance_percentage', 25);
        $minAdvance = (float) Setting::get('min_advance_amount', 300);
        $advanceRequired = max($minAdvance, round(($totalAmount * $advancePct) / 100, 2));
        if ($advanceRequired > $totalAmount) {
            $advanceRequired = $totalAmount;
        }

        // Payment Option Handling
        $rawOption = $data['payment_option'] ?? 'advance';
        $paymentOption = in_array($rawOption, ['advance', 'pay_later', 'full']) ? $rawOption : 'advance';
        $paymentDeadline = null;
        $initialStatus = 'pending_approval';

        if ($paymentOption === 'pay_later') {
            $payLaterEnabled = (int) Setting::get('pay_later_enabled', 1);
            if (!$payLaterEnabled) {
                $this->error('Pay Later option is currently disabled by administrator.');
            }
            $deadlineHours = (int) Setting::get('pay_later_deadline_hours', 4);
            $deadline = clone $pickupDt;
            $deadline->modify("-{$deadlineHours} hours");
            if ($deadline < $now) {
                $deadline = clone $now;
                $deadline->modify("+2 hours");
            }
            $paymentDeadline = $deadline->format('Y-m-d H:i:s');
        }

        $bookingCode = Security::generateBookingCode();
        $returnLocationId = !empty($data['return_location_id']) ? (int)$data['return_location_id'] : (int)$data['pickup_location_id'];

        // Database Transaction
        Database::beginTransaction();
        try {
            $bookingId = $this->bookingModel->create([
                'booking_code' => $bookingCode,
                'customer_id' => $customerId,
                'vehicle_id' => $vehicleId,
                'assigned_vehicle_id' => $vehicleId, // default assigned
                'pickup_location_id' => (int)$data['pickup_location_id'],
                'return_location_id' => $returnLocationId,
                'pickup_datetime' => $data['pickup_datetime'],
                'return_datetime' => $data['return_datetime'],
                'total_days' => $totalDays,
                'total_hours' => $totalHours,
                'daily_rate' => $dailyRate,
                'hourly_rate' => $hourlyRate,
                'rental_charge' => $rentalCharge,
                'deposit_amount' => $deposit,
                'additional_charges' => 0.00,
                'discount_amount' => $discountAmount,
                'promo_code' => $promoCode,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'advance_required' => $advanceRequired,
                'amount_paid' => 0.00,
                'remaining_balance' => $totalAmount,
                'payment_option' => $paymentOption,
                'payment_status' => 'pending',
                'booking_status' => $initialStatus,
                'document_verification_status' => 'not_uploaded',
                'payment_deadline' => $paymentDeadline,
                'customer_name' => trim($data['customer_name']),
                'customer_phone' => trim($data['customer_phone']),
                'customer_email' => !empty($data['customer_email']) ? strtolower(trim($data['customer_email'])) : null,
                'customer_notes' => $data['customer_notes'] ?? null,
            ]);

            // Status history
            $this->bookingModel->addStatusHistory($bookingId, $initialStatus, 'Booking created by customer via website', $customerId);

            // Increment promo usage if applied
            if ($promoCode) {
                Database::getConnection()->exec("UPDATE promo_codes SET current_uses = current_uses + 1 WHERE code = " . Database::getConnection()->quote($promoCode));
            }

            // Create notification for customer
            Notification::createForUser(
                $customerId,
                "Booking Placed: {$bookingCode}",
                "Your booking for {$vehicle['name']} has been submitted successfully. Please complete advance payment and upload license documents.",
                'booking',
                "/account/bookings/{$bookingId}"
            );

            // Audit log
            $this->logAudit($customerId, 'customer', 'CREATE_BOOKING', 'bookings', (string)$bookingId, null, json_encode(['code' => $bookingCode, 'amount' => $totalAmount]));

            Database::commit();

            $createdBooking = $this->bookingModel->findWithDetails($bookingId);

            $this->success([
                'booking' => $createdBooking,
                'booking_id' => $bookingId,
                'booking_code' => $bookingCode,
                'total_amount' => $totalAmount,
                'advance_required' => $advanceRequired,
                'payment_option' => $paymentOption,
                'payment_deadline' => $paymentDeadline,
            ], 'Booking successfully reserved! Please proceed with payment or document upload.', 201);

        } catch (Exception $e) {
            Database::rollBack();
            error_log("Booking creation error: " . $e->getMessage());
            $this->error('Failed to create booking: ' . $e->getMessage(), null, 500);
        }
    }

    public function index(Request $request): void {
        $user = $request->getUser();
        if (!$user) {
            $this->error('Unauthorized', null, 401);
        }

        $bookings = $this->bookingModel->getCustomerBookings($user['id']);
        $this->success($bookings);
    }

    public function show(Request $request, array $params): void {
        $id = $params['id'];
        $booking = $this->bookingModel->findWithDetails($id);

        if (!$booking) {
            $this->error('Booking not found', null, 404);
        }

        $user = $request->getUser();
        $isAdmin = $user && in_array($user['role'], ['super_admin', 'admin', 'booking_manager', 'payment_manager', 'vehicle_manager', 'staff']);

        if (!$isAdmin && (!$user || (int)$booking['customer_id'] !== (int)$user['id'])) {
            $this->error('Access denied to this booking.', null, 403);
        }

        // Attach payments and documents
        $pdo = Database::getConnection();
        $stmtP = $pdo->prepare("SELECT * FROM payments WHERE booking_id = ? ORDER BY id ASC");
        $stmtP->execute([$booking['id']]);
        $booking['payments'] = $stmtP->fetchAll();

        // Customer visible documents
        $docSql = "SELECT * FROM documents WHERE booking_id = ?";
        if (!$isAdmin) {
            $docSql .= " AND is_customer_visible = 1";
        }
        $docSql .= " ORDER BY id DESC";
        $stmtD = $pdo->prepare($docSql);
        $stmtD->execute([$booking['id']]);
        $booking['documents'] = $stmtD->fetchAll();

        // Timeline / History
        $booking['timeline'] = $this->bookingModel->getStatusHistory($booking['id']);

        $this->success($booking);
    }

    public function applyPromo(Request $request): void {
        $data = $this->validate($request->all(), [
            'code' => 'required',
            'amount' => 'required|numeric'
        ]);

        $user = $request->getUser();
        $userId = $user ? $user['id'] : 0;

        $res = $this->promoModel->validateAndCalculate($data['code'], (float)$data['amount'], $userId);
        if (!$res['valid']) {
            $this->error($res['message']);
        }

        $this->success([
            'code' => $res['promo']['code'],
            'discount' => $res['discount'],
            'description' => $res['promo']['description']
        ], $res['message']);
    }

    public function cancel(Request $request, array $params): void {
        $id = (int)$params['id'];
        $booking = $this->bookingModel->find($id);

        if (!$booking) {
            $this->error('Booking not found', null, 404);
        }

        $user = $request->getUser();
        $isAdmin = $user && in_array($user['role'], ['super_admin', 'admin', 'booking_manager']);

        if (!$isAdmin && (!$user || (int)$booking['customer_id'] !== (int)$user['id'])) {
            $this->error('Unauthorized', null, 403);
        }

        if (in_array($booking['booking_status'], ['completed', 'cancelled', 'rejected', 'rental_active'])) {
            $this->error("Cannot cancel booking currently in status '{$booking['booking_status']}'.");
        }

        // Check cancellation deadline
        $pickupDt = new DateTime($booking['pickup_datetime']);
        $now = new DateTime();
        $diffHours = ($pickupDt->getTimestamp() - $now->getTimestamp()) / 3600;

        $cancellationPolicyHours = (int) Setting::get('cancellation_hours', 6);
        $reason = $request->get('reason', 'Customer requested cancellation');

        Database::beginTransaction();
        try {
            $this->bookingModel->update($id, [
                'booking_status' => 'cancelled',
                'customer_notes' => trim(($booking['customer_notes'] ?? '') . " [Cancellation Reason: {$reason}]")
            ]);

            $this->bookingModel->addStatusHistory($id, 'cancelled', "Booking cancelled. {$reason}", $user['id']);

            // If money was paid, mark refund pending
            if ((float)$booking['amount_paid'] > 0) {
                Database::getConnection()->prepare("INSERT INTO refunds (booking_id, customer_id, refund_amount, reason, status) VALUES (?, ?, ?, ?, 'pending')")
                    ->execute([$id, $booking['customer_id'], $booking['amount_paid'], "Cancellation of booking {$booking['booking_code']}"]);
                
                $this->bookingModel->update($id, ['payment_status' => 'refund_pending']);
            }

            // Restore vehicle status to available if it was assigned
            if ($booking['assigned_vehicle_id']) {
                Database::getConnection()->exec("UPDATE vehicles SET availability_status = 'available' WHERE id = {$booking['assigned_vehicle_id']}");
            }

            Notification::createForUser(
                $booking['customer_id'],
                "Booking Cancelled: {$booking['booking_code']}",
                "Your booking has been cancelled successfully. If an advance was paid, a refund request has been initiated.",
                'booking',
                "/account/bookings/{$id}"
            );

            $this->logAudit($user['id'], $user['role'], 'CANCEL_BOOKING', 'bookings', (string)$id, $booking['booking_status'], 'cancelled');

            Database::commit();
            $this->success(null, 'Booking cancelled successfully.');

        } catch (Exception $e) {
            Database::rollBack();
            $this->error('Failed to cancel booking: ' . $e->getMessage(), null, 500);
        }
    }
}

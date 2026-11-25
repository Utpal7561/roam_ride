<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Database;
use App\Models\Payment;
use App\Models\Booking;
use App\Models\Notification;
use App\Helpers\Security;
use Exception;

class PaymentController extends Controller {
    private Payment $paymentModel;
    private Booking $bookingModel;

    public function __construct() {
        $this->paymentModel = new Payment();
        $this->bookingModel = new Booking();
    }

    /**
     * Customer online payment process (Simulated payment gateway / UPI intent)
     */
    public function process(Request $request): void {
        $user = $request->getUser();
        if (!$user || !in_array($user['role'] ?? '', ['admin', 'super_admin', 'staff', 'manager'])) {
            $this->error('Online self-payment is disabled. All vehicle rental payments are collected offline and recorded by staff from the Admin Operations Center.', null, 403);
        }

        $data = $this->validate($request->all(), [
            'booking_id' => 'required|numeric',
            'amount' => 'required|numeric',
            'payment_method' => 'required|in:upi,card,online_gateway,bank_transfer',
            'payment_type' => 'in:booking_advance,partial_payment,full_payment,security_deposit',
        ]);

        $bookingId = (int)$data['booking_id'];
        $booking = $this->bookingModel->find($bookingId);

        if (!$booking) {
            $this->error('Booking not found', null, 404);
        }

        $user = $request->getUser();
        if ($user && (int)$booking['customer_id'] !== (int)$user['id']) {
            $this->error('Unauthorized', null, 403);
        }

        $amount = (float)$data['amount'];
        if ($amount <= 0) {
            $this->error('Payment amount must be greater than zero.');
        }

        $remaining = (float)$booking['remaining_balance'];
        if ($amount > $remaining + 1.0) { // allowance for rounding
            $this->error("Payment amount (₹{$amount}) cannot exceed the remaining balance of ₹{$remaining}.");
        }

        $paymentCode = Security::generatePaymentCode();
        $txnRef = 'TXN_' . strtoupper($data['payment_method']) . '_' . time() . '_' . rand(1000, 9999);
        $paymentType = $data['payment_type'] ?? ($amount >= $remaining ? 'full_payment' : 'booking_advance');

        Database::beginTransaction();
        try {
            $paymentId = $this->paymentModel->create([
                'payment_code' => $paymentCode,
                'booking_id' => $bookingId,
                'customer_id' => $booking['customer_id'],
                'amount' => $amount,
                'payment_type' => $paymentType,
                'payment_method' => $data['payment_method'],
                'transaction_reference' => $txnRef,
                'payment_date' => date('Y-m-d H:i:s'),
                'payment_status' => 'successful',
                'notes' => 'Customer online transaction processed via ' . strtoupper($data['payment_method']),
                'created_by_user_id' => $user ? $user['id'] : null,
            ]);

            // Re-calculate booking total payments & remaining balance
            $newPaid = round((float)$booking['amount_paid'] + $amount, 2);
            $newBalance = max(0, round((float)$booking['total_amount'] - $newPaid, 2));

            $newPaymentStatus = ($newBalance <= 0) ? 'paid' : 'partial';
            $newBookingStatus = $booking['booking_status'];

            // Advance transition logic
            if ($booking['booking_status'] === 'pending_booking' || $booking['booking_status'] === 'pending_payment') {
                if ($newPaid >= (float)$booking['advance_required']) {
                    $newBookingStatus = ($booking['document_verification_status'] === 'approved') 
                        ? 'booking_confirmed' 
                        : 'pending_document_verification';
                } else {
                    $newBookingStatus = 'payment_partially_completed';
                }
            }

            $this->bookingModel->update($bookingId, [
                'amount_paid' => $newPaid,
                'remaining_balance' => $newBalance,
                'payment_status' => $newPaymentStatus,
                'booking_status' => $newBookingStatus,
            ]);

            $this->bookingModel->addStatusHistory(
                $bookingId,
                $newBookingStatus,
                "Payment of ₹{$amount} received via " . strtoupper($data['payment_method']) . " (Txn: {$txnRef}). New balance: ₹{$newBalance}",
                $user ? $user['id'] : null
            );

            Notification::createForUser(
                $booking['customer_id'],
                "Payment Received: ₹{$amount}",
                "Your payment of ₹{$amount} for booking {$booking['booking_code']} was successful. Remaining balance: ₹{$newBalance}.",
                'payment',
                "/account/bookings/{$bookingId}"
            );

            $this->logAudit($user ? $user['id'] : null, 'customer', 'PROCESS_PAYMENT', 'payments', (string)$paymentId, null, json_encode(['amount' => $amount, 'txn' => $txnRef]));

            Database::commit();

            $this->success([
                'payment_id' => $paymentId,
                'payment_code' => $paymentCode,
                'transaction_reference' => $txnRef,
                'amount_paid' => $amount,
                'total_paid' => $newPaid,
                'remaining_balance' => $newBalance,
                'payment_status' => $newPaymentStatus,
                'booking_status' => $newBookingStatus,
            ], 'Payment processed successfully!');

        } catch (Exception $e) {
            Database::rollBack();
            $this->error('Payment processing failed: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Admin records manual payment (Cash, Counter UPI, Card, Bank Transfer)
     * Maintains immutable transaction history.
     */
    public function recordManual(Request $request): void {
        $data = $this->validate($request->all(), [
            'booking_id' => 'required|numeric',
            'amount' => 'required|numeric',
            'payment_method' => 'required|in:cash,upi,card,bank_transfer,other',
            'payment_type' => 'required|in:booking_advance,partial_payment,full_payment,security_deposit,additional_charges,late_fee',
            'notes' => 'nullable|max:255'
        ]);

        $bookingId = (int)$data['booking_id'];
        $booking = $this->bookingModel->find($bookingId);

        if (!$booking) {
            $this->error('Booking not found', null, 404);
        }

        $admin = $request->getUser();
        $amount = (float)$data['amount'];
        if ($amount <= 0) {
            $this->error('Payment amount must be greater than zero.');
        }

        $paymentCode = Security::generatePaymentCode();
        $txnRef = $request->get('transaction_reference') ?: ('MANUAL_' . strtoupper($data['payment_method']) . '_' . time());

        Database::beginTransaction();
        try {
            $paymentId = $this->paymentModel->create([
                'payment_code' => $paymentCode,
                'booking_id' => $bookingId,
                'customer_id' => $booking['customer_id'],
                'amount' => $amount,
                'payment_type' => $data['payment_type'],
                'payment_method' => $data['payment_method'],
                'transaction_reference' => $txnRef,
                'payment_date' => $request->get('payment_date') ?: date('Y-m-d H:i:s'),
                'payment_status' => 'successful',
                'notes' => $data['notes'] ?? 'Manual counter payment recorded by staff: ' . $admin['name'],
                'created_by_user_id' => $admin['id'],
            ]);

            $newPaid = round((float)$booking['amount_paid'] + $amount, 2);
            $newBalance = max(0, round((float)$booking['total_amount'] - $newPaid, 2));
            $newPaymentStatus = ($newBalance <= 0) ? 'paid' : 'partial';

            $newBookingStatus = $booking['booking_status'];
            if ($booking['booking_status'] === 'pending_booking' || $booking['booking_status'] === 'pending_payment') {
                if ($newPaid >= (float)$booking['advance_required']) {
                    $newBookingStatus = ($booking['document_verification_status'] === 'approved') 
                        ? 'booking_confirmed' 
                        : 'pending_document_verification';
                }
            }

            $this->bookingModel->update($bookingId, [
                'amount_paid' => $newPaid,
                'remaining_balance' => $newBalance,
                'payment_status' => $newPaymentStatus,
                'booking_status' => $newBookingStatus,
            ]);

            $this->bookingModel->addStatusHistory(
                $bookingId,
                $newBookingStatus,
                "Staff {$admin['name']} recorded manual payment of ₹{$amount} via " . strtoupper($data['payment_method']) . ". Remaining: ₹{$newBalance}",
                $admin['id']
            );

            Notification::createForUser(
                $booking['customer_id'],
                "Payment Confirmed: ₹{$amount}",
                "A payment of ₹{$amount} has been recorded for booking {$booking['booking_code']}. Outstanding balance: ₹{$newBalance}.",
                'payment',
                "/account/bookings/{$bookingId}"
            );

            $this->logAudit($admin['id'], $admin['role'], 'RECORD_MANUAL_PAYMENT', 'payments', (string)$paymentId, null, json_encode(['amount' => $amount, 'method' => $data['payment_method']]));

            Database::commit();

            $this->success([
                'payment_id' => $paymentId,
                'payment_code' => $paymentCode,
                'amount' => $amount,
                'new_total_paid' => $newPaid,
                'new_balance' => $newBalance,
                'payment_status' => $newPaymentStatus,
                'booking_status' => $newBookingStatus,
            ], 'Payment record logged successfully in immutable transaction ledger.');

        } catch (Exception $e) {
            Database::rollBack();
            $this->error('Failed to log payment: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Admin records refund
     */
    public function recordRefund(Request $request): void {
        $data = $this->validate($request->all(), [
            'booking_id' => 'required|numeric',
            'amount' => 'required|numeric',
            'reason' => 'required|min:3',
            'payment_method' => 'in:upi,bank_transfer,cash,other'
        ]);

        $bookingId = (int)$data['booking_id'];
        $booking = $this->bookingModel->find($bookingId);

        if (!$booking) {
            $this->error('Booking not found', null, 404);
        }

        $admin = $request->getUser();
        $refundAmount = (float)$data['amount'];
        $paid = (float)$booking['amount_paid'];

        if ($refundAmount <= 0) {
            $this->error('Refund amount must be greater than zero.');
        }

        if ($refundAmount > $paid) {
            $this->error("Refund amount (₹{$refundAmount}) cannot exceed total paid amount (₹{$paid}).");
        }

        $paymentCode = Security::generatePaymentCode();
        $txnRef = 'REFUND_' . time() . '_' . rand(100, 999);

        Database::beginTransaction();
        try {
            // Immutable ledger entry for refund
            $paymentId = $this->paymentModel->create([
                'payment_code' => $paymentCode,
                'booking_id' => $bookingId,
                'customer_id' => $booking['customer_id'],
                'amount' => $refundAmount,
                'payment_type' => 'refund',
                'payment_method' => $data['payment_method'] ?? 'bank_transfer',
                'transaction_reference' => $txnRef,
                'payment_date' => date('Y-m-d H:i:s'),
                'payment_status' => 'successful',
                'notes' => 'Refund processed: ' . $data['reason'],
                'created_by_user_id' => $admin['id'],
            ]);

            // Refund record entry
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("INSERT INTO refunds (booking_id, customer_id, refund_amount, reason, status, approved_by_user_id, transaction_ref, processed_at) VALUES (?, ?, ?, ?, 'processed', ?, ?, ?)");
            $stmt->execute([
                $bookingId,
                $booking['customer_id'],
                $refundAmount,
                $data['reason'],
                $admin['id'],
                $txnRef,
                date('Y-m-d H:i:s')
            ]);

            $newPaid = max(0, round($paid - $refundAmount, 2));
            $this->bookingModel->update($bookingId, [
                'amount_paid' => $newPaid,
                'payment_status' => 'refunded'
            ]);

            $this->bookingModel->addStatusHistory(
                $bookingId,
                'refunded',
                "Staff {$admin['name']} processed refund of ₹{$refundAmount} (Reason: {$data['reason']}).",
                $admin['id']
            );

            Notification::createForUser(
                $booking['customer_id'],
                "Refund Processed: ₹{$refundAmount}",
                "A refund of ₹{$refundAmount} has been processed for booking {$booking['booking_code']} (Ref: {$txnRef}).",
                'payment',
                "/account/bookings/{$bookingId}"
            );

            $this->logAudit($admin['id'], $admin['role'], 'PROCESS_REFUND', 'refunds', (string)$paymentId, null, json_encode(['refund_amount' => $refundAmount]));

            Database::commit();

            $this->success([
                'refund_id' => $paymentId,
                'transaction_reference' => $txnRef,
                'refund_amount' => $refundAmount,
                'updated_paid' => $newPaid
            ], 'Refund processed successfully and recorded in ledger.');

        } catch (Exception $e) {
            Database::rollBack();
            $this->error('Failed to process refund: ' . $e->getMessage(), null, 500);
        }
    }

    public function summary(): void {
        $summary = $this->paymentModel->getPaymentSummary();
        $this->success($summary);
    }
}

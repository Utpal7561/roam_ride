<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Database;
use App\Models\Booking;
use App\Models\Vehicle;
use App\Models\User;
use App\Models\Payment;
use App\Models\Document;
use App\Models\Handover;
use App\Models\Setting;
use App\Models\Notification;
use Exception;

class AdminController extends Controller {
    private Booking $bookingModel;
    private Vehicle $vehicleModel;
    private User $userModel;
    private Payment $paymentModel;

    public function __construct() {
        $this->bookingModel = new Booking();
        $this->vehicleModel = new Vehicle();
        $this->userModel = new User();
        $this->paymentModel = new Payment();
    }

    /**
     * Dashboard live metrics & charts
     */
    public function dashboard(Request $request): void {
        $pdo = Database::getConnection();

        // 1. Vehicle counts
        $vehicleCounts = $pdo->query("SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN availability_status = 'available' THEN 1 ELSE 0 END) as available,
            SUM(CASE WHEN availability_status = 'reserved' THEN 1 ELSE 0 END) as reserved,
            SUM(CASE WHEN availability_status = 'booked' OR availability_status = 'assigned' THEN 1 ELSE 0 END) as booked,
            SUM(CASE WHEN availability_status = 'rental_active' THEN 1 ELSE 0 END) as rented,
            SUM(CASE WHEN availability_status = 'maintenance' THEN 1 ELSE 0 END) as maintenance,
            SUM(CASE WHEN availability_status = 'out_of_service' THEN 1 ELSE 0 END) as out_of_service
        FROM vehicles")->fetch();

        // 2. Booking counts
        $bookingCounts = $pdo->query("SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN booking_status = 'pending_booking' OR booking_status = 'pending_payment' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN DATE(pickup_datetime) = CURDATE() THEN 1 ELSE 0 END) as today_pickups,
            SUM(CASE WHEN booking_status = 'rental_active' THEN 1 ELSE 0 END) as active_rentals,
            SUM(CASE WHEN booking_status = 'completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN booking_status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
        FROM bookings")->fetch();

        // 3. Financial counts
        $financials = $this->paymentModel->getPaymentSummary();

        // 4. Monthly booking & revenue trends (Last 6 months)
        $trends = $pdo->query("SELECT 
            DATE_FORMAT(payment_date, '%b %Y') as month,
            DATE_FORMAT(payment_date, '%Y-%m') as sort_key,
            COALESCE(SUM(amount), 0) as revenue,
            COUNT(DISTINCT booking_id) as booking_count
        FROM payments
        WHERE payment_status = 'successful' AND payment_type != 'refund'
        GROUP BY sort_key, month
        ORDER BY sort_key DESC
        LIMIT 6")->fetchAll();

        // 5. Recent bookings
        $recentBookings = $this->bookingModel->getFilteredBookings();
        $recentBookings = array_slice($recentBookings, 0, 8);

        // 6. Pending documents count
        $pendingDocs = (int)$pdo->query("SELECT COUNT(*) FROM documents WHERE doc_type IN ('driving_license_front', 'driving_license_back') AND verification_status IN ('uploaded', 'under_review')")->fetchColumn();

        $this->success([
            'vehicles' => $vehicleCounts,
            'bookings' => $bookingCounts,
            'financials' => $financials,
            'monthly_trends' => array_reverse($trends),
            'recent_bookings' => $recentBookings,
            'pending_docs_count' => $pendingDocs,
        ]);
    }

    /**
     * Bookings list with search & filters
     */
    public function bookings(Request $request): void {
        $filters = [
            'booking_status' => $request->getQuery('status'),
            'payment_status' => $request->getQuery('payment_status'),
            'vehicle_id' => $request->getQuery('vehicle_id'),
            'location_id' => $request->getQuery('location_id'),
            'date_from' => $request->getQuery('date_from'),
            'date_to' => $request->getQuery('date_to'),
            'search' => $request->getQuery('search'),
        ];

        $bookings = $this->bookingModel->getFilteredBookings($filters);
        $this->success($bookings);
    }

    /**
     * Update booking status & logical workflow
     */
    public function updateBookingStatus(Request $request, array $params): void {
        $id = (int)$params['id'];
        $booking = $this->bookingModel->find($id);

        if (!$booking) {
            $this->error('Booking not found', null, 404);
        }

        $data = $this->validate($request->all(), [
            'status' => 'required|in:pending_approval,pending_booking,pending_payment,payment_partially_completed,payment_completed,pending_document_verification,documents_approved,booking_confirmed,vehicle_assigned,ready_for_pickup,rental_active,returned,completed,cancelled,rejected',
        ]);

        $admin = $request->getUser();
        $newStatus = $data['status'];
        $notes = $request->get('notes') ?? ("Status updated to " . ucwords(str_replace('_', ' ', $newStatus)));

        // Business Rule check: Cannot confirm booking if documents required are rejected
        if ($newStatus === 'booking_confirmed') {
            if ($booking['document_verification_status'] === 'rejected') {
                $this->error('Cannot confirm booking: Customer documents have been rejected. Please verify documents first or request re-upload.');
            }
        }

        // Active rental sync
        $pdo = Database::getConnection();
        Database::beginTransaction();
        try {
            $this->bookingModel->update($id, [
                'booking_status' => $newStatus,
                'admin_notes' => trim(($booking['admin_notes'] ?? '') . "\n[" . date('Y-m-d H:i') . "] {$notes}")
            ]);

            $this->bookingModel->addStatusHistory($id, $newStatus, $notes, $admin['id']);

            // Update assigned vehicle operational status based on booking status
            $targetVehicleId = $booking['assigned_vehicle_id'] ?: $booking['vehicle_id'];
            if ($targetVehicleId) {
                if ($newStatus === 'rental_active') {
                    $pdo->exec("UPDATE vehicles SET availability_status = 'rental_active' WHERE id = {$targetVehicleId}");
                    $this->bookingModel->update($id, ['actual_pickup_datetime' => date('Y-m-d H:i:s')]);
                } elseif ($newStatus === 'completed' || $newStatus === 'cancelled' || $newStatus === 'rejected') {
                    $pdo->exec("UPDATE vehicles SET availability_status = 'available' WHERE id = {$targetVehicleId}");
                    if ($newStatus === 'completed') {
                        $this->bookingModel->update($id, ['actual_return_datetime' => date('Y-m-d H:i:s')]);
                    }
                } elseif ($newStatus === 'ready_for_pickup') {
                    $pdo->exec("UPDATE vehicles SET availability_status = 'reserved' WHERE id = {$targetVehicleId}");
                }
            }

            Notification::createForUser(
                $booking['customer_id'],
                "Booking Update: {$booking['booking_code']}",
                "Your booking status has been updated to: " . ucwords(str_replace('_', ' ', $newStatus)) . ". {$notes}",
                'booking',
                "/account/bookings/{$id}"
            );

            $this->logAudit($admin['id'], $admin['role'], 'UPDATE_BOOKING_STATUS', 'bookings', (string)$id, $booking['booking_status'], $newStatus);

            Database::commit();
            $this->success(null, "Booking status updated to " . ucwords(str_replace('_', ' ', $newStatus)));

        } catch (Exception $e) {
            Database::rollBack();
            $this->error('Failed to update status: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Assign or change physical vehicle for a booking
     */
    public function assignVehicle(Request $request, array $params): void {
        $bookingId = (int)$params['id'];
        $booking = $this->bookingModel->find($bookingId);

        if (!$booking) {
            $this->error('Booking not found', null, 404);
        }

        $data = $this->validate($request->all(), [
            'vehicle_id' => 'required|numeric'
        ]);

        $admin = $request->getUser();
        $newVehicleId = (int)$data['vehicle_id'];
        $newVehicle = $this->vehicleModel->find($newVehicleId);

        if (!$newVehicle) {
            $this->error('Target vehicle does not exist.');
        }

        // Verify vehicle is not already booked during the timeframe (excluding this booking)
        if (!$this->vehicleModel->isAvailableForPeriod($newVehicleId, $booking['pickup_datetime'], $booking['return_datetime'], $bookingId)) {
            $this->error("Vehicle {$newVehicle['name']} ({$newVehicle['reg_number']}) is not available during {$booking['pickup_datetime']} to {$booking['return_datetime']}.");
        }

        $oldAssigned = $booking['assigned_vehicle_id'];

        Database::beginTransaction();
        try {
            // Free old vehicle if different
            if ($oldAssigned && $oldAssigned != $newVehicleId) {
                Database::getConnection()->exec("UPDATE vehicles SET availability_status = 'available' WHERE id = {$oldAssigned}");
            }

            // Assign new
            $this->bookingModel->update($bookingId, [
                'assigned_vehicle_id' => $newVehicleId,
                'booking_status' => ($booking['booking_status'] === 'booking_confirmed') ? 'vehicle_assigned' : $booking['booking_status']
            ]);

            Database::getConnection()->exec("UPDATE vehicles SET availability_status = 'assigned' WHERE id = {$newVehicleId}");

            $this->bookingModel->addStatusHistory(
                $bookingId,
                'vehicle_assigned',
                "Vehicle assigned: {$newVehicle['name']} [{$newVehicle['reg_number']}] by {$admin['name']}",
                $admin['id']
            );

            Notification::createForUser(
                $booking['customer_id'],
                "Vehicle Assigned: {$booking['booking_code']}",
                "Your ride has been assigned: {$newVehicle['name']} ({$newVehicle['reg_number']}).",
                'booking',
                "/account/bookings/{$bookingId}"
            );

            $this->logAudit($admin['id'], $admin['role'], 'ASSIGN_VEHICLE', 'bookings', (string)$bookingId, (string)$oldAssigned, (string)$newVehicleId);

            Database::commit();

            $this->success([
                'assigned_vehicle' => $newVehicle
            ], "Vehicle {$newVehicle['name']} successfully assigned to booking {$booking['booking_code']}.");

        } catch (Exception $e) {
            Database::rollBack();
            $this->error('Failed to assign vehicle: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Update security deposit for a booking and adjust financials
     */
    public function updateBookingDeposit(Request $request, array $params): void {
        $bookingId = (int)$params['id'];
        $booking = $this->bookingModel->find($bookingId);

        if (!$booking) {
            $this->error('Booking not found', null, 404);
        }

        $data = $this->validate($request->all(), [
            'deposit_amount' => 'required|numeric'
        ]);

        $admin = $request->getUser();
        $newDeposit = round((float)$data['deposit_amount'], 2);
        if ($newDeposit < 0) {
            $this->error('Security deposit cannot be negative.');
        }

        $oldDeposit = (float)$booking['deposit_amount'];
        $diff = $newDeposit - $oldDeposit;

        $newTotal = round((float)$booking['total_amount'] + $diff, 2);
        $amountPaid = (float)$booking['amount_paid'];
        $newRemaining = round(max(0, $newTotal - $amountPaid), 2);

        Database::beginTransaction();
        try {
            $this->bookingModel->update($bookingId, [
                'deposit_amount' => $newDeposit,
                'total_amount' => $newTotal,
                'remaining_balance' => $newRemaining,
            ]);

            $note = "Security deposit updated from ₹" . number_format($oldDeposit, 2) . " to ₹" . number_format($newDeposit, 2) . " by {$admin['name']}";
            $this->bookingModel->addStatusHistory($bookingId, $booking['booking_status'], $note, $admin['id']);

            $this->logAudit($admin['id'], $admin['role'], 'UPDATE_DEPOSIT', 'bookings', (string)$bookingId, (string)$oldDeposit, (string)$newDeposit);

            Database::commit();

            $updatedBooking = $this->bookingModel->findWithDetails($bookingId);
            $this->success($updatedBooking, "Security deposit updated to ₹" . number_format($newDeposit, 2) . " successfully.");
        } catch (Exception $e) {
            Database::rollBack();
            $this->error('Failed to update security deposit: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Admin creates manual / walk-in / counter booking directly
     */
    public function createManualBooking(Request $request): void {
        $admin = $request->getUser();
        $data = $this->validate($request->all(), [
            'vehicle_id' => 'required|numeric',
            'customer_name' => 'required|min:2|max:100',
            'customer_phone' => 'required|min:8|max:20',
            'pickup_datetime' => 'required',
            'return_datetime' => 'required',
        ]);

        $vehicleId = (int)$data['vehicle_id'];
        $vehicle = $this->vehicleModel->find($vehicleId);
        if (!$vehicle) {
            $this->error('Selected vehicle does not exist.');
        }

        // Check availability for dates
        if (!$this->vehicleModel->isAvailableForPeriod($vehicleId, $data['pickup_datetime'], $data['return_datetime'])) {
            $this->error("Vehicle {$vehicle['name']} ({$vehicle['reg_number']}) is not available for the selected dates/times.");
        }

        // Find or create customer
        $userModel = new \App\Models\User();
        $existing = $userModel->findByPhone(trim($data['customer_phone']));
        if ($existing) {
            $customerId = $existing['id'];
        } else {
            $rawDigits = preg_replace('/\D/', '', $data['customer_phone']);
            $defaultPassword = password_hash(substr($rawDigits, -6) ?: 'ride123', PASSWORD_BCRYPT);
            $customerId = $userModel->create([
                'name' => trim($data['customer_name']),
                'phone' => trim($data['customer_phone']),
                'email' => !empty($data['customer_email']) ? strtolower(trim($data['customer_email'])) : null,
                'password_hash' => $defaultPassword,
                'role' => 'customer',
                'status' => 'active'
            ]);
        }

        // Calculate duration
        $pickupDt = new \DateTime($data['pickup_datetime']);
        $returnDt = new \DateTime($data['return_datetime']);
        $diffHours = max(1, (int)ceil(($returnDt->getTimestamp() - $pickupDt->getTimestamp()) / 3600));
        $totalDays = max(1, (int)ceil($diffHours / 24));

        $dailyRate = (isset($data['daily_rate']) && $data['daily_rate'] !== '') ? (float)$data['daily_rate'] : (float)$vehicle['daily_rate'];
        $rentalCharge = (isset($data['rental_charge']) && $data['rental_charge'] !== '') ? (float)$data['rental_charge'] : round($totalDays * $dailyRate, 2);
        $deposit = (isset($data['deposit_amount']) && $data['deposit_amount'] !== '') ? (float)$data['deposit_amount'] : (float)($vehicle['deposit_amount'] ?? 1000);
        $discount = isset($data['discount_amount']) ? (float)$data['discount_amount'] : 0.00;
        $taxPct = (float)\App\Models\Setting::get('tax_percentage', 5);
        $taxable = max(0, $rentalCharge - $discount);
        $tax = round(($taxable * $taxPct) / 100, 2);
        $totalAmount = round($taxable + $tax + $deposit, 2);

        $initialStatus = $data['booking_status'] ?? 'booking_confirmed';
        $bookingCode = \App\Helpers\Security::generateBookingCode();
        $pickupLoc = (int)($data['pickup_location_id'] ?? $vehicle['pickup_location_id'] ?? 1);
        $returnLoc = (int)($data['return_location_id'] ?? $pickupLoc);

        // Immediate payment if provided at counter
        $paidNow = isset($data['amount_paid_now']) ? round((float)$data['amount_paid_now'], 2) : 0.00;
        $remaining = round(max(0, $totalAmount - $paidNow), 2);
        $paymentStatus = ($paidNow >= $totalAmount) ? 'paid' : ($paidNow > 0 ? 'partially_paid' : 'pending');

        Database::beginTransaction();
        try {
            $bookingId = $this->bookingModel->create([
                'booking_code' => $bookingCode,
                'customer_id' => $customerId,
                'vehicle_id' => $vehicleId,
                'assigned_vehicle_id' => $vehicleId,
                'pickup_location_id' => $pickupLoc,
                'return_location_id' => $returnLoc,
                'pickup_datetime' => $data['pickup_datetime'],
                'return_datetime' => $data['return_datetime'],
                'total_days' => $totalDays,
                'total_hours' => $diffHours,
                'daily_rate' => $dailyRate,
                'hourly_rate' => (float)$vehicle['hourly_rate'],
                'rental_charge' => $rentalCharge,
                'deposit_amount' => $deposit,
                'additional_charges' => 0.00,
                'discount_amount' => $discount,
                'tax_amount' => $tax,
                'total_amount' => $totalAmount,
                'advance_required' => min($totalAmount, round($totalAmount * 0.25, 2)),
                'amount_paid' => $paidNow,
                'remaining_balance' => $remaining,
                'payment_option' => 'counter',
                'payment_status' => $paymentStatus,
                'booking_status' => $initialStatus,
                'document_verification_status' => 'approved',
                'customer_name' => trim($data['customer_name']),
                'customer_phone' => trim($data['customer_phone']),
                'customer_email' => !empty($data['customer_email']) ? strtolower(trim($data['customer_email'])) : null,
                'customer_notes' => $data['customer_notes'] ?? null,
                'admin_notes' => "Manual booking created by staff: {$admin['name']}"
            ]);

            // If immediate counter payment was recorded
            if ($paidNow > 0) {
                $payModel = new \App\Models\Payment();
                $payModel->create([
                    'booking_id' => $bookingId,
                    'customer_id' => $customerId,
                    'payment_code' => 'PAY-' . strtoupper(bin2hex(random_bytes(4))),
                    'transaction_reference' => $data['payment_reference'] ?? ('COUNTER-' . time()),
                    'amount' => $paidNow,
                    'payment_method' => $data['payment_method'] ?? 'cash',
                    'payment_type' => $data['payment_type'] ?? 'booking_advance',
                    'payment_status' => 'successful',
                    'notes' => 'Recorded during manual booking creation by ' . $admin['name'],
                    'created_by_user_id' => $admin['id'],
                    'payment_date' => date('Y-m-d H:i:s'),
                ]);
            }

            // Update physical vehicle status
            if ($initialStatus === 'rental_active') {
                Database::getConnection()->exec("UPDATE vehicles SET availability_status = 'rental_active' WHERE id = {$vehicleId}");
            } elseif ($initialStatus === 'booking_confirmed' || $initialStatus === 'ready_for_pickup') {
                Database::getConnection()->exec("UPDATE vehicles SET availability_status = 'reserved' WHERE id = {$vehicleId}");
            }

            $this->bookingModel->addStatusHistory(
                $bookingId,
                $initialStatus,
                "Manual booking created by staff {$admin['name']}. Paid: ₹{$paidNow}, Remaining: ₹{$remaining}",
                $admin['id']
            );

            $this->logAudit($admin['id'], $admin['role'], 'CREATE_MANUAL_BOOKING', 'bookings', (string)$bookingId, null, json_encode(['code' => $bookingCode, 'total' => $totalAmount]));

            Database::commit();

            $fullBooking = $this->bookingModel->findWithDetails($bookingId);
            $this->success($fullBooking, "Manual booking {$bookingCode} created successfully!", 201);
        } catch (\Exception $e) {
            Database::rollBack();
            $this->error('Failed to create manual booking: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Vehicles CRUD
     */
    public function addVehicle(Request $request): void {
        $data = $this->validate($request->all(), [
            'name' => 'required|min:2|max:150',
            'brand' => 'required|max:80',
            'model' => 'required|max:80',
            'category_id' => 'required|numeric',
            'reg_number' => 'required|unique:vehicles,reg_number',
            'daily_rate' => 'required|numeric',
            'pickup_location_id' => 'required|numeric',
        ]);

        $admin = $request->getUser();
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $data['name']))) . '-' . rand(100, 999);

        $primaryImage = 'assets/images/vehicles/activa6g.jpg'; // default placeholder
        $file = $request->getFile('primary_image');
        if ($file) {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $fileName = 'veh_' . time() . '_' . rand(100, 999) . '.' . $ext;
            $dest = dirname(__DIR__, 2) . '/public/uploads/vehicles/' . $fileName;
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $primaryImage = 'uploads/vehicles/' . $fileName;
            }
        }

        $vehicleId = $this->vehicleModel->create([
            'name' => trim($data['name']),
            'slug' => $slug,
            'brand' => trim($data['brand']),
            'model' => trim($data['model']),
            'category_id' => (int)$data['category_id'],
            'reg_number' => strtoupper(trim($data['reg_number'])),
            'daily_rate' => (float)$data['daily_rate'],
            'hourly_rate' => (float)($data['hourly_rate'] ?? round($data['daily_rate'] / 10, 2)),
            'deposit_amount' => (float)($data['deposit_amount'] ?? 1000.00),
            'fuel_type' => $data['fuel_type'] ?? 'Petrol',
            'transmission' => $data['transmission'] ?? 'Automatic',
            'engine_capacity' => $data['engine_capacity'] ?? '110cc',
            'color' => $data['color'] ?? 'Black & Yellow',
            'year' => (int)($data['year'] ?? date('Y')),
            'mileage' => $data['mileage'] ?? '45 km/l',
            'passenger_capacity' => (int)($data['passenger_capacity'] ?? 2),
            'helmet_included' => (int)($data['helmet_included'] ?? 2),
            'accessories' => $data['accessories'] ?? 'Mobile Mount, Helmet (x2)',
            'pickup_location_id' => (int)$data['pickup_location_id'],
            'availability_status' => $data['availability_status'] ?? 'available',
            'condition_status' => $data['condition_status'] ?? 'excellent',
            'primary_image' => $primaryImage,
            'is_featured' => !empty($data['is_featured']) ? 1 : 0,
            'rental_rules' => $data['rental_rules'] ?? 'Valid driving license required.',
            'cancellation_policy' => $data['cancellation_policy'] ?? 'Free cancellation up to 6 hours prior.',
            'notes' => $data['notes'] ?? null,
        ]);

        $this->logAudit($admin['id'], $admin['role'], 'ADD_VEHICLE', 'vehicles', (string)$vehicleId, null, $data['name']);

        $this->success(['vehicle_id' => $vehicleId], 'Vehicle added to fleet successfully.', 201);
    }

    public function editVehicle(Request $request, array $params): void {
        $id = (int)$params['id'];
        $vehicle = $this->vehicleModel->find($id);

        if (!$vehicle) {
            $this->error('Vehicle not found', null, 404);
        }

        $data = $this->validate($request->all(), [
            'name' => 'required|min:2|max:150',
            'reg_number' => "required|unique:vehicles,reg_number,{$id}",
            'daily_rate' => 'required|numeric',
        ]);

        $admin = $request->getUser();
        $updateData = [
            'name' => trim($data['name']),
            'brand' => trim($data['brand'] ?? $vehicle['brand']),
            'model' => trim($data['model'] ?? $vehicle['model']),
            'category_id' => (int)($data['category_id'] ?? $vehicle['category_id']),
            'reg_number' => strtoupper(trim($data['reg_number'])),
            'daily_rate' => (float)$data['daily_rate'],
            'hourly_rate' => (float)($data['hourly_rate'] ?? $vehicle['hourly_rate']),
            'deposit_amount' => (float)($data['deposit_amount'] ?? $vehicle['deposit_amount']),
            'fuel_type' => $data['fuel_type'] ?? $vehicle['fuel_type'],
            'transmission' => $data['transmission'] ?? $vehicle['transmission'],
            'engine_capacity' => $data['engine_capacity'] ?? $vehicle['engine_capacity'],
            'color' => $data['color'] ?? $vehicle['color'],
            'mileage' => $data['mileage'] ?? $vehicle['mileage'],
            'pickup_location_id' => (int)($data['pickup_location_id'] ?? $vehicle['pickup_location_id']),
            'availability_status' => $data['availability_status'] ?? $vehicle['availability_status'],
            'condition_status' => $data['condition_status'] ?? $vehicle['condition_status'],
            'is_featured' => isset($data['is_featured']) ? (int)$data['is_featured'] : $vehicle['is_featured'],
            'notes' => $data['notes'] ?? $vehicle['notes'],
        ];

        $file = $request->getFile('primary_image');
        if ($file) {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $fileName = 'veh_' . time() . '_' . rand(100, 999) . '.' . $ext;
            $dest = dirname(__DIR__, 2) . '/public/uploads/vehicles/' . $fileName;
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $updateData['primary_image'] = 'uploads/vehicles/' . $fileName;
            }
        }

        $this->vehicleModel->update($id, $updateData);
        $this->logAudit($admin['id'], $admin['role'], 'UPDATE_VEHICLE', 'vehicles', (string)$id, json_encode($vehicle), json_encode($updateData));

        $this->success(null, 'Vehicle details updated successfully.');
    }

    public function deleteVehicle(Request $request, array $params): void {
        $id = (int)$params['id'];
        $vehicle = $this->vehicleModel->find($id);

        if (!$vehicle) {
            $this->error('Vehicle not found', null, 404);
        }

        $admin = $request->getUser();

        // Check if vehicle is attached to active bookings
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE (vehicle_id = ? OR assigned_vehicle_id = ?) AND booking_status IN ('booking_confirmed', 'vehicle_assigned', 'rental_active')");
        $stmt->execute([$id, $id]);
        if ($stmt->fetchColumn() > 0) {
            $this->error('Cannot delete this vehicle because it is currently assigned to active reservations.');
        }

        $this->vehicleModel->update($id, ['availability_status' => 'out_of_service']);
        $this->logAudit($admin['id'], $admin['role'], 'DECOMMISSION_VEHICLE', 'vehicles', (string)$id, null, 'Marked out of service');

        $this->success(null, 'Vehicle marked as Out of Service.');
    }

    /**
     * Handover & Return Checklists (Section 16)
     */
    public function recordHandover(Request $request): void {
        $data = $this->validate($request->all(), [
            'booking_id' => 'required|numeric',
            'handover_type' => 'required|in:pickup,return',
            'odometer_reading' => 'required|numeric',
            'fuel_level' => 'required',
        ]);

        $bookingId = (int)$data['booking_id'];
        $booking = $this->bookingModel->find($bookingId);

        if (!$booking) {
            $this->error('Booking not found', null, 404);
        }

        $admin = $request->getUser();
        $type = $data['handover_type'];
        $vehicleId = $booking['assigned_vehicle_id'] ?: $booking['vehicle_id'];

        $cleaningCharges = (float)($data['cleaning_charges'] ?? 0.00);
        $fuelCharges = (float)($data['fuel_charges'] ?? 0.00);
        $lateFee = (float)($data['late_fee_charges'] ?? 0.00);
        $totalExtra = round($cleaningCharges + $fuelCharges + $lateFee, 2);

        Database::beginTransaction();
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("INSERT INTO vehicle_handover (
                booking_id, vehicle_id, staff_id, customer_id, handover_type, handover_datetime,
                odometer_reading, fuel_level, condition_notes, existing_scratches, accessories_supplied, helmet_count,
                customer_signature_confirmed, staff_signature_confirmed, new_damages,
                cleaning_charges, fuel_charges, late_fee_charges, total_extra_charges
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 1, ?, ?, ?, ?, ?)");

            $stmt->execute([
                $bookingId,
                $vehicleId,
                $admin['id'],
                $booking['customer_id'],
                $type,
                date('Y-m-d H:i:s'),
                (int)$data['odometer_reading'],
                $data['fuel_level'],
                $data['condition_notes'] ?? null,
                $data['existing_scratches'] ?? null,
                $data['accessories_supplied'] ?? 'Mobile Mount, Helmet(s)',
                (int)($data['helmet_count'] ?? 1),
                $data['new_damages'] ?? null,
                $cleaningCharges,
                $fuelCharges,
                $lateFee,
                $totalExtra
            ]);

            if ($type === 'pickup') {
                $this->bookingModel->update($bookingId, [
                    'booking_status' => 'rental_active',
                    'actual_pickup_datetime' => date('Y-m-d H:i:s')
                ]);
                $pdo->exec("UPDATE vehicles SET availability_status = 'rental_active' WHERE id = {$vehicleId}");
                $this->bookingModel->addStatusHistory($bookingId, 'rental_active', "Vehicle handed over to customer by {$admin['name']}. Odometer: {$data['odometer_reading']}, Fuel: {$data['fuel_level']}", $admin['id']);
            } else {
                // Return
                $newStatus = 'completed';
                $updateFields = [
                    'booking_status' => $newStatus,
                    'actual_return_datetime' => date('Y-m-d H:i:s')
                ];

                if ($totalExtra > 0) {
                    $newTotal = round((float)$booking['total_amount'] + $totalExtra, 2);
                    $newBal = round((float)$booking['remaining_balance'] + $totalExtra, 2);
                    $updateFields['total_amount'] = $newTotal;
                    $updateFields['additional_charges'] = round((float)$booking['additional_charges'] + $totalExtra, 2);
                    $updateFields['remaining_balance'] = $newBal;
                    $updateFields['payment_status'] = ($newBal > 0) ? 'partial' : 'paid';

                    // Add immutable ledger entry for extra charges
                    $this->paymentModel->create([
                        'payment_code' => Security::generatePaymentCode(),
                        'booking_id' => $bookingId,
                        'customer_id' => $booking['customer_id'],
                        'amount' => $totalExtra,
                        'payment_type' => 'additional_charges',
                        'payment_method' => 'other',
                        'payment_date' => date('Y-m-d H:i:s'),
                        'payment_status' => 'pending',
                        'notes' => "Return inspection extra charges (Cleaning: ₹{$cleaningCharges}, Fuel: ₹{$fuelCharges}, Late Fee: ₹{$lateFee})",
                        'created_by_user_id' => $admin['id']
                    ]);
                }

                $this->bookingModel->update($bookingId, $updateFields);
                $pdo->exec("UPDATE vehicles SET availability_status = 'available' WHERE id = {$vehicleId}");
                $this->bookingModel->addStatusHistory($bookingId, 'completed', "Vehicle return completed by {$admin['name']}. Extra charges: ₹{$totalExtra}. Ride marked completed.", $admin['id']);
            }

            $this->logAudit($admin['id'], $admin['role'], strtoupper($type) . '_HANDOVER', 'vehicle_handover', (string)$bookingId);

            Database::commit();
            $this->success(null, ucfirst($type) . ' inspection record logged successfully.');

        } catch (Exception $e) {
            Database::rollBack();
            $this->error('Failed to log handover: ' . $e->getMessage(), null, 500);
        }
    }

    public function customers(): void {
        $pdo = Database::getConnection();
        $sql = "SELECT u.id, u.name, u.phone, u.email, u.status, u.created_at,
                    COUNT(b.id) as total_bookings,
                    COALESCE(SUM(b.total_amount), 0) as total_spent,
                    COALESCE(SUM(b.remaining_balance), 0) as outstanding_balance
                FROM users u
                LEFT JOIN bookings b ON u.id = b.customer_id
                WHERE u.role = 'customer'
                GROUP BY u.id
                ORDER BY u.id DESC";
        $customers = $pdo->query($sql)->fetchAll();
        $this->success($customers);
    }

    public function auditLogs(): void {
        $pdo = Database::getConnection();
        $logs = $pdo->query("SELECT a.*, u.name as user_name FROM audit_logs a LEFT JOIN users u ON a.user_id = u.id ORDER BY a.id DESC LIMIT 100")->fetchAll();
        $this->success($logs);
    }
}

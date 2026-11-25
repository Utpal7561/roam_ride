<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use App\Models\Document;
use App\Models\Booking;
use App\Helpers\Security;
use App\Middleware\RateLimitMiddleware;
use Exception;

class DocumentController extends Controller {
    private Document $docModel;
    private Booking $bookingModel;

    public function __construct() {
        $this->docModel = new Document();
        $this->bookingModel = new Booking();
    }

    /**
     * Customer uploads driving license front / back or other ID
     */
    public function upload(Request $request): void {
        RateLimitMiddleware::handle($request, 'upload');

        $user = $request->getUser();
        if (!$user) {
            $this->error('Authentication required', null, 401);
        }

        $bookingId = $request->get('booking_id');
        $docType = $request->get('doc_type', 'driving_license_front');
        $title = $request->get('title');

        if (!in_array($docType, ['driving_license_front', 'driving_license_back', 'other'])) {
            $this->error('Invalid document type.');
        }

        $file = $request->getFile('file');
        if (!$file) {
            $this->error('No document file was uploaded.');
        }

        $appConfig = require dirname(__DIR__, 2) . '/config/app.php';
        $maxSize = $appConfig['upload']['max_size'];
        $allowedMimes = $appConfig['upload']['allowed_mimes'];

        $validation = Security::validateUploadedFile($file, $allowedMimes, $maxSize);
        if (!$validation['valid']) {
            $this->error($validation['error']);
        }

        $mime = $validation['mime'];
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $safeFileName = 'doc_' . $user['id'] . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $targetDir = $appConfig['paths']['private_docs'];

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $targetPath = $targetDir . '/' . $safeFileName;
        $relativePath = 'storage/private_documents/' . $safeFileName;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            $this->error('Failed to save uploaded file securely.');
        }

        if (!$title) {
            $title = ($docType === 'driving_license_front') ? 'Driving License (Front)' : (($docType === 'driving_license_back') ? 'Driving License (Back)' : 'Customer Document');
        }

        $vehicleId = null;
        if ($bookingId) {
            $booking = $this->bookingModel->find($bookingId);
            if ($booking) {
                $vehicleId = $booking['vehicle_id'];
            }
        }

        $docId = $this->docModel->create([
            'user_id' => $user['id'],
            'booking_id' => $bookingId ?: null,
            'vehicle_id' => $vehicleId,
            'title' => $title,
            'doc_type' => $docType,
            'file_path' => $relativePath,
            'file_name' => $file['name'],
            'file_size' => $file['size'],
            'mime_type' => $mime,
            'verification_status' => 'uploaded',
            'is_customer_visible' => 1,
            'is_confidential' => 0,
        ]);

        // Update booking document verification status
        if ($bookingId) {
            $this->bookingModel->update($bookingId, [
                'document_verification_status' => 'uploaded'
            ]);
            $this->bookingModel->addStatusHistory($bookingId, 'pending_document_verification', "Document uploaded: {$title}", $user['id']);
        }

        $this->logAudit($user['id'], $user['role'], 'UPLOAD_DOCUMENT', 'documents', (string)$docId, null, $safeFileName);

        $this->success([
            'document_id' => $docId,
            'title' => $title,
            'doc_type' => $docType,
            'verification_status' => 'uploaded',
            'file_name' => $file['name'],
            'created_at' => date('Y-m-d H:i:s')
        ], 'Document uploaded successfully! Our team will review and verify it shortly.', 201);
    }

    /**
     * Customer "My Rental Documents"
     */
    public function index(Request $request): void {
        $user = $request->getUser();
        if (!$user) {
            $this->error('Authentication required', null, 401);
        }

        $bookingId = $request->getQuery('booking_id');
        $docs = $this->docModel->getCustomerDocuments($user['id'], $bookingId ? (int)$bookingId : null);

        $this->success($docs);
    }

    /**
     * Authenticated secure document view & download
     */
    public function download(Request $request, array $params): void {
        $id = (int)$params['id'];
        $doc = $this->docModel->find($id);

        if (!$doc) {
            Response::error('Document not found', null, 404);
        }

        $user = $request->getUser();
        $isAdmin = $user && in_array($user['role'], ['super_admin', 'admin', 'booking_manager', 'payment_manager', 'vehicle_manager', 'staff']);

        if (!$isAdmin && (!$user || (int)$doc['user_id'] !== (int)$user['id'])) {
            Response::error('Unauthorized access to this document', null, 403);
        }

        // Customer cannot view if marked not customer visible
        if (!$isAdmin && !$doc['is_customer_visible']) {
            Response::error('This document is not accessible yet.', null, 403);
        }

        $fullPath = dirname(__DIR__, 2) . '/' . ltrim($doc['file_path'], '/');
        if (!file_exists($fullPath)) {
            Response::error('Document file not found on disk.', null, 404);
        }

        Response::download($fullPath, $doc['file_name'], $doc['mime_type']);
    }

    /**
     * Admin verify document (Approve or Reject with reason)
     */
    public function verify(Request $request, array $params): void {
        $id = (int)$params['id'];
        $doc = $this->docModel->find($id);

        if (!$doc) {
            $this->error('Document not found', null, 404);
        }

        $data = $this->validate($request->all(), [
            'status' => 'required|in:approved,rejected,under_review',
        ]);

        $admin = $request->getUser();
        $status = $data['status'];
        $reason = $request->get('rejection_reason');

        $this->docModel->update($id, [
            'verification_status' => $status,
            'rejection_reason' => ($status === 'rejected') ? $reason : null,
            'verified_by_user_id' => $admin['id'],
            'verified_at' => date('Y-m-d H:i:s')
        ]);

        // If this doc belongs to a booking, check all documents for the booking
        if ($doc['booking_id']) {
            $bookingId = (int)$doc['booking_id'];
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("SELECT verification_status FROM documents WHERE booking_id = ? AND doc_type IN ('driving_license_front', 'driving_license_back')");
            $stmt->execute([$bookingId]);
            $statuses = $stmt->fetchAll(\PDO::FETCH_COLUMN);

            $overallStatus = 'uploaded';
            if (in_array('rejected', $statuses)) {
                $overallStatus = 'rejected';
            } elseif (!empty($statuses) && count(array_filter($statuses, fn($s) => $s === 'approved')) === count($statuses)) {
                $overallStatus = 'approved';
            }

            $this->bookingModel->update($bookingId, [
                'document_verification_status' => $overallStatus
            ]);

            $this->bookingModel->addStatusHistory($bookingId, $overallStatus === 'approved' ? 'documents_approved' : 'document_reviewed', "Document '{$doc['title']}' was marked as {$status} by staff.", $admin['id']);
        }

        $this->logAudit($admin['id'], $admin['role'], 'VERIFY_DOCUMENT', 'documents', (string)$id, $doc['verification_status'], $status);

        $this->success(null, "Document verification status updated to {$status}.");
    }

    /**
     * Admin attaches confirmed rental documents to a booking
     */
    public function attachRentalDocument(Request $request, array $params): void {
        $bookingId = (int)$params['id'];
        $booking = $this->bookingModel->findWithDetails($bookingId) ?: $this->bookingModel->find($bookingId);

        if (!$booking) {
            $this->error('Booking not found', null, 404);
        }

        $admin = $request->getUser();
        $docType = $request->get('doc_type', 'rental_agreement');
        $title = $request->get('title');
        $isCustomerVisible = (int)$request->get('is_customer_visible', 1);

        $file = $request->getFile('file');
        $appConfig = require dirname(__DIR__, 2) . '/config/app.php';

        if ($file) {
            $validation = Security::validateUploadedFile($file, $appConfig['upload']['allowed_mimes'], $appConfig['upload']['max_size']);
            if (!$validation['valid']) {
                $this->error($validation['error']);
            }
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $safeName = 'rental_' . $bookingId . '_' . time() . '.' . $ext;
            $targetPath = $appConfig['paths']['private_docs'] . '/' . $safeName;
            move_uploaded_file($file['tmp_name'], $targetPath);
            $relPath = 'storage/private_documents/' . $safeName;
            $origName = $file['name'];
            $mime = $validation['mime'];
            $size = $file['size'];
        } else {
            // Generate official 14-clause Roam & Ride rental agreement document
            $origName = "rental_agreement_{$booking['booking_code']}.html";
            $safeName = 'agreement_' . $bookingId . '_' . time() . '.html';
            $targetPath = $appConfig['paths']['private_docs'] . '/' . $safeName;
            
            $agreementContent = $this->generateRentalAgreementHtml($booking);
            file_put_contents($targetPath, $agreementContent);
            $relPath = 'storage/private_documents/' . $safeName;
            $mime = 'text/html';
            $size = strlen($agreementContent);
        }

        if (!$title) {
            $title = ucwords(str_replace('_', ' ', $docType)) . " ({$booking['booking_code']})";
        }

        $docId = $this->docModel->create([
            'user_id' => $booking['customer_id'],
            'booking_id' => $bookingId,
            'vehicle_id' => $booking['assigned_vehicle_id'] ?: $booking['vehicle_id'],
            'title' => $title,
            'doc_type' => $docType,
            'file_path' => $relPath,
            'file_name' => $origName,
            'file_size' => $size,
            'mime_type' => $mime,
            'verification_status' => 'approved',
            'is_customer_visible' => $isCustomerVisible,
            'is_confidential' => 0,
            'verified_by_user_id' => $admin['id'],
            'verified_at' => date('Y-m-d H:i:s')
        ]);

        $this->logAudit($admin['id'], $admin['role'], 'ATTACH_RENTAL_DOC', 'documents', (string)$docId, null, $title);

        $this->success([
            'document_id' => $docId,
            'title' => $title,
            'file_name' => $origName,
            'is_customer_visible' => (bool)$isCustomerVisible
        ], 'Official Rental Agreement attached and now accessible to customer.');
    }

    /**
     * Generate HTML document containing the 14 official Roam & Ride rental terms
     */
    private function generateRentalAgreementHtml(array $b): string {
        $bookingCode = htmlspecialchars($b['booking_code'] ?? 'RR-' . $b['id']);
        $custName = htmlspecialchars($b['customer_name'] ?? $b['user_name'] ?? 'Valued Customer');
        $custPhone = htmlspecialchars($b['customer_phone'] ?? $b['user_phone'] ?? 'N/A');
        $custEmail = htmlspecialchars($b['customer_email'] ?? $b['user_email'] ?? 'N/A');
        $vehicleName = htmlspecialchars($b['vehicle_name'] ?? 'Two-Wheeler');
        $vehicleBrand = htmlspecialchars($b['vehicle_brand'] ?? '');
        $vehicleReg = htmlspecialchars($b['vehicle_reg'] ?? $b['assigned_vehicle_reg'] ?? 'Assigned at Handover');
        $pickupDt = htmlspecialchars($b['pickup_datetime'] ?? 'N/A');
        $returnDt = htmlspecialchars($b['return_datetime'] ?? 'N/A');
        $totalDays = htmlspecialchars((string)($b['total_days'] ?? 1));
        $totalHours = htmlspecialchars((string)($b['total_hours'] ?? 24));
        $rentalCharge = htmlspecialchars((string)($b['rental_charge'] ?? $b['total_amount']));
        $deposit = htmlspecialchars((string)($b['deposit_amount'] ?? 1000));
        $totalAmount = htmlspecialchars((string)($b['total_amount'] ?? 0));
        $amountPaid = htmlspecialchars((string)($b['amount_paid'] ?? 0));
        $balance = htmlspecialchars((string)($b['remaining_balance'] ?? 0));
        $today = date('d M Y, h:i A');

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Rental Agreement - {$bookingCode} - ROAM &amp; RIDE</title>
  <style>
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; margin: 0; padding: 24px; background: #0c0c0e; color: #e4e4e7; line-height: 1.5; font-size: 13px; }
    .container { max-width: 840px; margin: 0 auto; background: #141417; border: 1px solid #27272a; border-radius: 12px; padding: 32px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
    .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #facc15; padding-bottom: 16px; margin-bottom: 24px; }
    .brand-title { font-size: 26px; font-weight: 900; color: #facc15; letter-spacing: 1px; margin: 0; }
    .brand-tagline { font-size: 11px; font-weight: 800; color: #ffffff; letter-spacing: 2px; }
    .badge-doc { background: #facc15; color: #000; font-weight: 900; padding: 4px 10px; border-radius: 4px; font-size: 11px; text-transform: uppercase; }
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px; }
    .box { background: #1c1c21; border: 1px solid #2e2e38; border-radius: 8px; padding: 14px; }
    .box-title { font-size: 11px; font-weight: 800; color: #facc15; text-transform: uppercase; margin-bottom: 8px; letter-spacing: 0.5px; }
    table { width: 100%; border-collapse: collapse; margin-top: 4px; font-size: 12px; }
    td { padding: 4px 0; }
    td.label { color: #a1a1aa; width: 40%; font-weight: 500; }
    td.val { color: #ffffff; font-weight: 700; }
    .financials { background: #1c1c21; border: 1px solid #facc15; border-radius: 8px; padding: 14px; margin-bottom: 24px; }
    .terms-section { margin-top: 24px; border-top: 1px solid #27272a; padding-top: 20px; }
    .terms-title { font-size: 14px; font-weight: 900; color: #facc15; margin-bottom: 4px; letter-spacing: 1px; }
    .terms-subtitle { font-size: 10px; font-weight: 700; color: #ef4444; margin-bottom: 16px; text-transform: uppercase; }
    .term-item { margin-bottom: 12px; font-size: 11.5px; text-align: justify; color: #d4d4d8; }
    .term-item strong { color: #ffffff; }
    .ack-box { background: #1e1e24; border: 1px dashed #facc15; border-radius: 8px; padding: 16px; margin-top: 24px; }
    .signature-row { display: flex; justify-content: space-between; margin-top: 32px; padding-top: 16px; border-top: 1px solid #3f3f46; }
    .contact-bar { margin-top: 24px; background: #000; border: 1px solid #3f3f46; border-radius: 6px; padding: 12px; text-align: center; font-size: 11px; color: #a1a1aa; }
    .contact-bar a { color: #facc15; text-decoration: none; font-weight: bold; }
    .no-print { margin-bottom: 16px; text-align: right; }
    .btn-print { background: #facc15; color: #000; border: none; font-weight: 800; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 12px; }
    @media print {
      body { background: #fff; color: #000; font-size: 11px; padding: 0; }
      .container { border: none; box-shadow: none; padding: 0; background: #fff; }
      .box, .financials, .ack-box { background: #f9f9f9; border-color: #ccc; }
      .brand-title, .box-title, .terms-title, .badge-doc { color: #000 !important; }
      .brand-tagline, td.val, .term-item strong { color: #000 !important; }
      .term-item, td.label, .contact-bar { color: #333 !important; }
      .no-print { display: none; }
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="no-print">
      <button class="btn-print" onclick="window.print()">🖨️ Print Agreement / Save as PDF</button>
    </div>

    <!-- Header -->
    <div class="header">
      <div>
        <h1 class="brand-title">ROAM &amp; RIDE</h1>
        <div class="brand-tagline">PICK . RIDE . EXPLORE.</div>
      </div>
      <div style="text-align: right;">
        <span class="badge-doc">OFFICIAL RENTAL AGREEMENT</span>
        <div style="margin-top: 6px; font-size: 11px; color: #a1a1aa;">Generated: {$today}</div>
      </div>
    </div>

    <!-- Grid: Renter Details & Fleet Details -->
    <div class="grid-2">
      <div class="box">
        <div class="box-title">👤 Renter &amp; Booking Information</div>
        <table>
          <tr><td class="label">Booking Code:</td><td class="val font-mono">{$bookingCode}</td></tr>
          <tr><td class="label">Renter Name:</td><td class="val">{$custName}</td></tr>
          <tr><td class="label">Contact Phone:</td><td class="val">{$custPhone}</td></tr>
          <tr><td class="label">Email Address:</td><td class="val">{$custEmail}</td></tr>
          <tr><td class="label">Pickup Location:</td><td class="val">Kamarkuchi Hub (Sonapur)</td></tr>
        </table>
      </div>

      <div class="box">
        <div class="box-title">🏍️ Vehicle &amp; Schedule Details</div>
        <table>
          <tr><td class="label">Vehicle:</td><td class="val">{$vehicleName} ({$vehicleBrand})</td></tr>
          <tr><td class="label">Registration #:</td><td class="val">{$vehicleReg}</td></tr>
          <tr><td class="label">Pickup Schedule:</td><td class="val">{$pickupDt}</td></tr>
          <tr><td class="label">Return Schedule:</td><td class="val">{$returnDt}</td></tr>
          <tr><td class="label">Duration:</td><td class="val">{$totalDays} Day(s) ({$totalHours} Hours)</td></tr>
        </table>
      </div>
    </div>

    <!-- Financial Statement Box -->
    <div class="financials">
      <div class="box-title">💰 Rental Financial Ledger</div>
      <div style="display: flex; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-top: 8px;">
        <div><span style="color:#a1a1aa;">Rental Charges:</span> <strong style="color:#fff;">₹{$rentalCharge}</strong></div>
        <div><span style="color:#a1a1aa;">Security Deposit:</span> <strong style="color:#facc15;">₹{$deposit} (Refundable)</strong></div>
        <div><span style="color:#a1a1aa;">Total Booking Value:</span> <strong style="color:#fff;">₹{$totalAmount}</strong></div>
        <div><span style="color:#a1a1aa;">Amount Paid:</span> <strong style="color:#10b981;">₹{$amountPaid}</strong></div>
        <div><span style="color:#a1a1aa;">Remaining Balance:</span> <strong style="color:#facc15;">₹{$balance}</strong></div>
      </div>
    </div>

    <!-- 14 IMPORTANT RENTAL TERMS & CONDITIONS -->
    <div class="terms-section">
      <div class="terms-title">IMPORTANT RENTAL TERMS &amp; CONDITIONS</div>
      <div class="terms-subtitle">ROAM &amp; RIDE • PICK . RIDE . EXPLORE. | PLEASE READ BEFORE RIDING</div>

      <div class="term-item">
        <strong>1. RENTAL PERIOD.</strong> One rental day is 24 consecutive hours from the pickup time recorded on the front of this receipt. The scooter must be returned by the agreed date, time and location. Any extension requires prior approval from Roam&amp;Ride and may attract additional charges.
      </div>

      <div class="term-item">
        <strong>2. RETURN GRACE PERIOD &amp; LATE FEE.</strong> A 30-minute grace period applies after the scheduled return time. After this period, the late-return charge agreed at booking applies. The applicable rate must be written on the front of the receipt or in the signed rental agreement. Keeping the scooter beyond the agreed time does not automatically extend the booking.
      </div>

      <div class="term-item">
        <strong>3. RENTAL PAYMENT &amp; SECURITY DEPOSIT.</strong> Rental charges are payable as agreed at booking. A refundable security deposit of ₹1,000 is collected separately from the rental charge. After the scooter and accessories are returned and inspected, documented outstanding amounts may be deducted and explained to the renter. The remaining undisputed deposit balance will be refunded within the timeline communicated at booking.
      </div>

      <div class="term-item">
        <strong>4. FUEL POLICY.</strong> The fuel level will be recorded at handover. The scooter must be returned with the same fuel level. Any shortfall may be charged at the reasonable, documented cost of restoring the recorded level.
      </div>

      <div class="term-item">
        <strong>5. LICENCE &amp; AUTHORISED RIDER.</strong> The renter must provide a genuine, current and valid driving licence authorising them to ride the relevant vehicle class and must meet all legal eligibility requirements. Only the named renter, or another rider expressly approved in writing by Roam&amp;Ride and legally qualified, may ride the scooter. The scooter must not be lent, sub-rented or transferred to another person.
      </div>

      <div class="term-item">
        <strong>6. SAFE &amp; LAWFUL USE.</strong> The renter must obey all traffic laws, speed limits, parking rules and road-safety requirements and use legally required helmets and safety equipment. Riding under the influence of alcohol or drugs, racing, stunts, overloading, carrying more passengers than legally permitted, using the scooter for unlawful activities or unauthorised commercial use is prohibited.
      </div>

      <div class="term-item">
        <strong>7. TRAFFIC FINES &amp; PENALTIES.</strong> The renter is responsible for traffic challans, parking fines, tolls and penalties arising from their use or possession of the scooter during the rental period, including notices received after return that relate to that period. Roam&amp;Ride may share relevant booking details with authorities when legally required.
      </div>

      <div class="term-item">
        <strong>8. ACCIDENT, DAMAGE, BREAKDOWN OR THEFT.</strong> The renter must promptly inform Roam&amp;Ride of any accident, damage, breakdown, fire, theft or attempted theft. Contact police or emergency services wherever required, obtain an incident reference where applicable, and cooperate with lawful and insurance procedures. Do not abandon the scooter or arrange repairs without approval except where immediate action is necessary for safety. Responsibility for loss, damage, insurance excess or uninsured amounts will be assessed based on the facts, applicable law and relevant insurance terms; liability is not imposed where prohibited by law.
      </div>

      <div class="term-item">
        <strong>9. VEHICLE CARE &amp; CONDITION.</strong> The renter must inspect the scooter before accepting it and ensure existing scratches, dents, defects, fuel level, odometer reading and supplied accessories are recorded. Use reasonable care, park securely and report faults promptly. Stop riding if the scooter appears unsafe. No modification, tampering or unauthorised repair is permitted.
      </div>

      <div class="term-item">
        <strong>10. RETURN &amp; ACCESSORIES.</strong> Return the scooter to the agreed location by the agreed time, with the same recorded fuel level and all supplied keys, helmets, documents and accessories. Roam&amp;Ride and the renter should jointly inspect the scooter at return and record any new damage, missing items, fuel shortfall or other agreed charges. The renter should keep a copy or photograph of the completed return record.
      </div>

      <div class="term-item">
        <strong>11. OPERATING AREA &amp; TRACKING.</strong> Use the scooter only within the area agreed at booking. Inter-state travel or travel outside the approved area requires prior written permission and compliance with applicable legal requirements. If a GPS/tracking device is installed, location information may be used for fleet security, safety, recovery and lawful operational purposes, subject to applicable privacy law.
      </div>

      <div class="term-item">
        <strong>12. CANCELLATION, EARLY RETURN &amp; NON-RETURN.</strong> Any cancellation, rescheduling or early-return refund terms must be communicated and agreed at booking. Failure to return the scooter, unauthorised retention, concealment or transfer may be reported to the appropriate authorities and addressed through lawful procedures.
      </div>

      <div class="term-item">
        <strong>13. PERSONAL BELONGINGS &amp; CUSTOMER INFORMATION.</strong> The renter is responsible for personal belongings left in or on the scooter, except where liability is imposed by law. Customer identity, licence and contact information will be used for rental verification, administration, safety, insurance, legal compliance and recovery purposes, subject to applicable privacy requirements.
      </div>

      <div class="term-item">
        <strong>14. AGREEMENT &amp; DISPUTES.</strong> The front of this receipt, these terms and conditions, and any signed rental agreement and vehicle inspection report form the booking record. Any handwritten change must be acknowledged by both parties. The parties will first attempt to resolve concerns in good faith. Applicable Indian law and the courts having competent jurisdiction will apply. Nothing here excludes rights or liabilities that cannot legally be excluded.
      </div>
    </div>

    <!-- Renter Acknowledgement -->
    <div class="ack-box">
      <div style="font-weight: 800; color: #facc15; margin-bottom: 6px; font-size: 12px;">RENTER ACKNOWLEDGEMENT</div>
      <p style="margin: 0; font-size: 11.5px; color: #e4e4e7;">
        By accepting possession or booking through our platform, I confirm that I have read and understood these 14 terms and conditions and agree to comply with them, together with the booking details recorded on this document and the signed rental handover.
      </p>

      <div class="signature-row">
        <div>
          <div style="font-size: 11px; color: #a1a1aa;">Renter Digital Signature:</div>
          <div style="font-size: 13px; font-weight: bold; color: #facc15; margin-top: 4px;">{$custName}</div>
          <div style="font-size: 10px; color: #71717a;">Verified via Mobile: {$custPhone}</div>
        </div>
        <div style="text-align: right;">
          <div style="font-size: 11px; color: #a1a1aa;">Authorized Roam &amp; Ride Hub:</div>
          <div style="font-size: 13px; font-weight: bold; color: #fff; margin-top: 4px;">Kamarkuchi Point, Sonapur</div>
          <div style="font-size: 10px; color: #10b981;">✓ Digitally Certified Agreement</div>
        </div>
      </div>
    </div>

    <!-- Contact & Map Footer -->
    <div class="contact-bar">
      <strong>Roam&amp;Ride WhatsApp:</strong> <a href="https://wa.me/919395485880" target="_blank">9395485880</a> &nbsp;|&nbsp; 
      <strong>Pickup Point:</strong> Kamarkuchi, Sonapur &nbsp;|&nbsp; 
      <strong>Map:</strong> <a href="https://maps.app.goo.gl/m8W3cwX7uiGEQok28?g_st=ic" target="_blank">https://maps.app.goo.gl/m8W3cwX7uiGEQok28?g_st=ic</a>
    </div>
  </div>
</body>
</html>
HTML;
    }
}

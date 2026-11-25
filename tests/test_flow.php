<?php
/**
 * Automated Verification Test for Rome & Ride
 */

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('STORAGE_PATH', ROOT_PATH . '/storage');
define('PUBLIC_PATH', ROOT_PATH . '/public');

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = APP_PATH . '/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) require_once $file;
});

require_once CONFIG_PATH . '/app.php';
require_once CONFIG_PATH . '/database.php';

use App\Core\Database;
use App\Core\JWT;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Document;
use App\Services\RateLimitService;

echo "=== ROME & RIDE AUTOMATED VERIFICATION ===\n\n";

$testsPassed = 0;
$totalTests = 0;

function assertTest($description, $condition) {
    global $testsPassed, $totalTests;
    $totalTests++;
    if ($condition) {
        echo " [PASS] {$description}\n";
        $testsPassed++;
    } else {
        echo " [FAIL] {$description}\n";
    }
}

// 1. Database Connectivity
$pdo = Database::getConnection();
assertTest("Database connected to MySQL", $pdo instanceof PDO);

// 2. Vehicles Count and Availability
$vModel = new Vehicle();
$vehicles = $vModel->all();
assertTest("Fleet has at least 10 vehicles seeded", count($vehicles) >= 10);

$activa = $vModel->find(1);
assertTest("Vehicle Activa 6G exists with daily rate ₹499", (float)$activa['daily_rate'] === 499.00);

// 3. User Authentication & Password Hashing
$uModel = new User();
$adminUser = $uModel->findByEmail('admin@romeandride.com');
assertTest("Admin account exists", !empty($adminUser));
assertTest("Admin password_verify('admin123') matches", password_verify('admin123', $adminUser['password_hash']));

$customer = $uModel->findByEmail('rahul@gmail.com');
assertTest("Customer account exists", !empty($customer));
assertTest("Customer password_verify('customer123') matches", password_verify('customer123', $customer['password_hash']));

// 4. JWT Generation and Decoding
$token = JWT::encode(['sub' => $customer['id'], 'role' => 'customer']);
$decoded = JWT::decode($token);
assertTest("JWT token successfully generated and decoded", $decoded && $decoded['sub'] == $customer['id']);

// 5. Booking Logic and Overlapping Date Prevention
$isAvail = $vModel->isAvailableForPeriod(1, '2026-12-01 10:00:00', '2026-12-03 10:00:00');
assertTest("Vehicle 1 is available for future dates 2026-12-01", $isAvail === true);

// 6. Token Bucket Rate Limiting Test
$bucketKey = "test_rate_" . time();
$res1 = RateLimitService::checkAndConsume($bucketKey, 5.0, 0.1, 1.0);
assertTest("Token bucket initial request allowed", $res1['allowed'] === true);

// 7. Payment Summary & Immutable Ledger
$pModel = new Payment();
$summary = $pModel->getPaymentSummary();
assertTest("Financial ledger calculated collected revenue", $summary['total_collected'] > 0);

// 8. Vehicle Document Delivery (Section 8)
$dModel = new Document();
$rahulDocs = $dModel->getCustomerDocuments($customer['id']);
assertTest("Rahul has customer-visible rental documents delivered", count($rahulDocs) >= 1);

// 9. Document File Check
$firstDoc = $rahulDocs[0];
$fullDocPath = ROOT_PATH . '/' . $firstDoc['file_path'];
assertTest("Document file exists in private storage ({$firstDoc['file_name']})", file_exists($fullDocPath));

// 10. Booking Status Workflow
$bModel = new Booking();
$sampleBooking = $bModel->find(1);
assertTest("Sample booking RNR-2026-1001 exists with total ₹{$sampleBooking['total_amount']}", !empty($sampleBooking));

echo "\n----------------------------------------\n";
echo "SUMMARY: {$testsPassed} / {$totalTests} TESTS PASSED (" . round(($testsPassed/$totalTests)*100) . "%)\n";
echo "----------------------------------------\n";

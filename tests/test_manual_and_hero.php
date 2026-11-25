<?php
/**
 * Test Suite: Manual Booking Creation & Homepage Hero Picture Customization
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
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Booking;
use App\Models\Setting;

echo "========================================================\n";
echo "ROME & RIDE — MANUAL BOOKING & HERO PICTURE TEST SUITE\n";
echo "========================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertTest($condition, $message) {
    global $passCount, $failCount;
    if ($condition) {
        echo " [PASS] $message\n";
        $passCount++;
    } else {
        echo " [FAIL] $message\n";
        $failCount++;
    }
}

$phpBin = 'C:\\wamp64\\bin\\php\\php8.2.29\\php.exe';
$pdo = Database::getConnection();

// --- TEST 1: Manual Booking via Controller ---
echo "--- TEST 1: Manual Booking Creation (Counter / Walk-in) ---\n";

$workerManual = tempnam(sys_get_temp_dir(), 'mb_');
$workerCode = <<<'PHP'
<?php
define('ROOT_PATH', 'c:/wamp64/www/roam_ride');
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

$uModel = new \App\Models\User();
$admin = $uModel->findByEmail('admin@romeandride.com');

$adminCtrl = new \App\Controllers\AdminController();
$request = new \App\Core\Request();
$request->setUser($admin);

$randDay = rand(20, 200);
$pickupDt = date('Y-m-d H:i:s', strtotime("+{$randDay} days 10:00:00"));
$returnDt = date('Y-m-d H:i:s', strtotime("+{$randDay} days 18:00:00"));

$manualPayload = [
    'customer_name' => 'Walkin Guest Test',
    'customer_phone' => '9998887766',
    'customer_email' => 'walkin@example.com',
    'vehicle_id' => 1,
    'pickup_datetime' => $pickupDt,
    'return_datetime' => $returnDt,
    'pickup_location_id' => 1,
    'return_location_id' => 1,
    'daily_rate' => 600,
    'rental_charge' => 600,
    'deposit_amount' => 1500, // Custom deposit handled & set by admin
    'discount_amount' => 50,
    'amount_paid_now' => 2077.50, // (550) + 5% GST (27.50) + deposit (1500) = 2077.50
    'payment_method' => 'cash',
    'payment_reference' => 'COUNTER-CASH-REC-001',
    'booking_status' => 'booking_confirmed',
    'internal_notes' => 'Counter walk-in test with security deposit ₹1500'
];

$refProp = new ReflectionProperty($request, 'bodyParams');
$refProp->setAccessible(true);
$refProp->setValue($request, $manualPayload);

$adminCtrl->createManualBooking($request);
PHP;

file_put_contents($workerManual, $workerCode);
$outManual = shell_exec("\"$phpBin\" \"$workerManual\" 2>&1");
unlink($workerManual);

// Extract JSON
$jsonStart = strpos($outManual, '{"success"');
$jsonStr = $jsonStart !== false ? substr($outManual, $jsonStart) : $outManual;
$resManual = json_decode($jsonStr, true);

assertTest(!empty($resManual) && ($resManual['success'] ?? false) === true, "Manual Booking API created booking successfully: " . ($resManual['message'] ?? $outManual));
$bookingId = $resManual['data']['id'] ?? null;
$bookingCode = $resManual['data']['booking_code'] ?? null;
assertTest(!empty($bookingId) && !empty($bookingCode), "Received Booking ID #$bookingId with code $bookingCode");

if ($bookingId) {
    $bRow = $pdo->query("SELECT * FROM bookings WHERE id = $bookingId")->fetch();
    assertTest($bRow['customer_name'] === 'Walkin Guest Test', "Customer name recorded: {$bRow['customer_name']}");
    assertTest((float)$bRow['deposit_amount'] === 1500.0, "Security deposit accurately recorded as ₹{$bRow['deposit_amount']}");
    assertTest((float)$bRow['amount_paid'] === 2077.50, "Full counter payment recorded: ₹{$bRow['amount_paid']}");
    assertTest($bRow['payment_status'] === 'paid', "Payment status marked as paid");
    assertTest($bRow['booking_status'] === 'booking_confirmed', "Booking status is booking_confirmed");

    // Check payment ledger
    $pRow = $pdo->query("SELECT * FROM payments WHERE booking_id = $bookingId ORDER BY id DESC LIMIT 1")->fetch();
    assertTest(!empty($pRow) && $pRow['payment_method'] === 'cash', "Cash payment ledger entry recorded for ₹{$pRow['amount']}");
}

// --- TEST 2: Homepage Hero Picture & Content Manager ---
echo "\n--- TEST 2: Homepage Hero Picture & Content Manager ---\n";

$workerHero = tempnam(sys_get_temp_dir(), 'hero_');
$workerHeroCode = <<<'PHP'
<?php
define('ROOT_PATH', 'c:/wamp64/www/roam_ride');
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

$uModel = new \App\Models\User();
$admin = $uModel->findByEmail('admin@romeandride.com');

$settingCtrl = new \App\Controllers\SettingController();
$request = new \App\Core\Request();
$request->setUser($admin);

$heroPayload = [
    'hero_image' => 'assets/images/vehicles/revolt_rv400.jpg',
    'hero_badge' => 'TOP ELECTRIC RIDE',
    'hero_title' => 'Revolt RV400 Electric AI Motorcycle',
    'hero_subtitle' => 'From ₹799 / day (₹79/hr)',
    'hero_tagline_left' => 'Kamarkuchi Hub (Sonapur)',
    'hero_tagline_right' => 'Instant Handover & Full Charge'
];

$refProp = new ReflectionProperty($request, 'bodyParams');
$refProp->setAccessible(true);
$refProp->setValue($request, $heroPayload);

$settingCtrl->updateHero($request);
PHP;

file_put_contents($workerHero, $workerHeroCode);
$outHero = shell_exec("\"$phpBin\" \"$workerHero\" 2>&1");
unlink($workerHero);

$jsonStartH = strpos($outHero, '{"success"');
$jsonStrH = $jsonStartH !== false ? substr($outHero, $jsonStartH) : $outHero;
$resHero = json_decode($jsonStrH, true);

assertTest(!empty($resHero) && ($resHero['success'] ?? false) === true, "Hero update API returned success: " . ($resHero['message'] ?? $outHero));
assertTest(($resHero['data']['hero_title'] ?? '') === 'Revolt RV400 Electric AI Motorcycle', "Hero title updated to Revolt RV400");
assertTest(($resHero['data']['hero_image'] ?? '') === 'assets/images/vehicles/revolt_rv400.jpg', "Hero picture updated to revolt_rv400.jpg");

// Verify Setting::get reflects the update
assertTest(Setting::get('hero_title') === 'Revolt RV400 Electric AI Motorcycle', "Setting::get('hero_title') matches database");
assertTest(Setting::get('hero_image') === 'assets/images/vehicles/revolt_rv400.jpg', "Setting::get('hero_image') matches database");

// Test restoring default hero picture and settings
$workerReset = tempnam(sys_get_temp_dir(), 'rst_');
$workerResetCode = <<<'PHP'
<?php
define('ROOT_PATH', 'c:/wamp64/www/roam_ride');
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

$uModel = new \App\Models\User();
$admin = $uModel->findByEmail('admin@romeandride.com');

$settingCtrl = new \App\Controllers\SettingController();
$request = new \App\Core\Request();
$request->setUser($admin);

$resetPayload = [
    'hero_image' => 'assets/images/vehicles/activa6g.jpg',
    'hero_badge' => 'BESTSELLER',
    'hero_title' => 'Honda Activa 6G Sport',
    'hero_subtitle' => 'From ₹499 / day (₹49/hr)',
    'hero_tagline_left' => 'Station Hub Exit',
    'hero_tagline_right' => 'Instant 5-Min Handover'
];

$refProp = new ReflectionProperty($request, 'bodyParams');
$refProp->setAccessible(true);
$refProp->setValue($request, $resetPayload);

$settingCtrl->updateHero($request);
PHP;

file_put_contents($workerReset, $workerResetCode);
$outReset = shell_exec("\"$phpBin\" \"$workerReset\" 2>&1");
unlink($workerReset);

assertTest(Setting::get('hero_title') === 'Honda Activa 6G Sport', "Hero title restored to Honda Activa 6G Sport");
assertTest(Setting::get('hero_image') === 'assets/images/vehicles/activa6g.jpg', "Hero picture restored to activa6g.jpg");

// Audit Log Checks
$auditLogs = $pdo->query("SELECT * FROM audit_logs WHERE action IN ('CREATE_MANUAL_BOOKING', 'UPDATE_HERO') ORDER BY id DESC LIMIT 5")->fetchAll();
assertTest(count($auditLogs) >= 2, "Audit log trail properly recorded administrative actions");

echo "\n========================================================\n";
echo "SUMMARY: Passed: $passCount | Failed: $failCount\n";
echo "========================================================\n";

if ($failCount > 0) {
    exit(1);
}

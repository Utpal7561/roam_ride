<?php
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('CONFIG_PATH', ROOT_PATH . '/config');

spl_autoload_register(function ($class) {
    $file = APP_PATH . '/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (file_exists($file)) require_once $file;
});
require_once CONFIG_PATH . '/app.php';
require_once CONFIG_PATH . '/database.php';

$pdo = \App\Core\Database::getConnection();

// Delete payment #13
$pdo->exec("DELETE FROM payments WHERE id = 13");

// Revert booking #16
$pdo->exec("UPDATE bookings SET 
    amount_paid = 0.00, 
    remaining_balance = total_amount, 
    payment_status = 'pending',
    booking_status = 'pending_approval'
WHERE id = 16");

echo "Successfully reverted booking RNR-2026-8A7CE to unpaid (offline payment pending admin verification).\n";
$b = $pdo->query("SELECT id, booking_code, total_amount, amount_paid, remaining_balance, payment_status, booking_status FROM bookings WHERE id = 16")->fetch(PDO::FETCH_ASSOC);
print_r($b);

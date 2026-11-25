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
$b = $pdo->query("SELECT * FROM bookings WHERE booking_code = 'RNR-2026-8A7CE'")->fetch(PDO::FETCH_ASSOC);
echo "BOOKING ROW:\n";
print_r($b);

if ($b) {
    $payments = $pdo->query("SELECT * FROM payments WHERE booking_id = {$b['id']}")->fetchAll(PDO::FETCH_ASSOC);
    echo "\nPAYMENTS ROWS:\n";
    print_r($payments);
}

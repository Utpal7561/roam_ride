<?php
require_once __DIR__ . '/../app/Core/Database.php';

try {
    $pdo = App\Core\Database::getConnection();
    $check = $pdo->query("SELECT id FROM locations WHERE name LIKE '%Kamarkuchi%' OR name LIKE '%Sonapur%'")->fetch();
    if (!$check) {
        $stmt = $pdo->prepare("INSERT INTO locations (name, slug, address, city, phone, is_pickup, is_return) VALUES (?, ?, ?, ?, ?, 1, 1)");
        $stmt->execute(['Kamarkuchi Hub (Sonapur)', 'kamarkuchi-sonapur-hub', 'Kamarkuchi, Sonapur, NH-37 (https://maps.app.goo.gl/m8W3cwX7uiGEQok28?g_st=ic)', 'Kamarkuchi, Sonapur', '+91 9395485880']);
        echo "Inserted Kamarkuchi location ID: " . $pdo->lastInsertId() . PHP_EOL;
    } else {
        echo "Kamarkuchi already exists: ID " . $check['id'] . PHP_EOL;
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}

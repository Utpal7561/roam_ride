<?php
require_once __DIR__ . '/../app/Core/Database.php';

try {
    $pdo = App\Core\Database::getConnection();
    // Modify booking_status to VARCHAR(50) or add pending_approval to ENUM
    $pdo->exec("ALTER TABLE `bookings` MODIFY COLUMN `booking_status` VARCHAR(50) NOT NULL DEFAULT 'pending_approval'");
    echo "Successfully updated bookings.booking_status column definition to VARCHAR(50) DEFAULT 'pending_approval'\n";

    // Also update any previous 'pending_booking' to 'pending_approval'
    $affected = $pdo->exec("UPDATE `bookings` SET `booking_status` = 'pending_approval' WHERE `booking_status` = 'pending_booking'");
    echo "Updated {$affected} existing bookings from 'pending_booking' to 'pending_approval'\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

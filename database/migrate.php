<?php
/**
 * Database Migration & Seeder Script
 * Rome & Ride — Pick. Ride. Explore.
 */

require_once __DIR__ . '/../config/database.php';

echo "=== Rome & Ride Database Setup ===\n";

try {
    $dbConfig = require __DIR__ . '/../config/database.php';
    
    // Connect without database first to ensure database exists
    $dsnWithoutDb = sprintf(
        "mysql:host=%s;port=%s;charset=utf8mb4",
        $dbConfig['host'],
        $dbConfig['port']
    );
    $pdo = new PDO($dsnWithoutDb, $dbConfig['username'], $dbConfig['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $dbName = $dbConfig['database'];
    echo "Creating database if not exists: {$dbName}...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    $pdo->exec("USE `{$dbName}`;");

    echo "Executing schema.sql...\n";
    $schemaSql = file_get_contents(__DIR__ . '/schema.sql');
    $pdo->exec($schemaSql);
    echo "Schema created successfully.\n";

    echo "Seeding initial data...\n";

    // 1. Roles
    $roles = [
        ['super_admin', 'Super Admin', 'Full unrestricted access to all modules and configurations'],
        ['admin', 'Admin', 'General administrative management of fleet, bookings, and operations'],
        ['booking_manager', 'Booking Manager', 'Manage customer bookings, documents, and scheduling'],
        ['payment_manager', 'Payment Manager', 'Financial management, transaction verification, and refunds'],
        ['vehicle_manager', 'Vehicle Manager', 'Vehicle inventory, maintenance, availability, and assignments'],
        ['staff', 'Staff', 'Operational counter duties, vehicle handover and returns'],
        ['customer', 'Customer', 'Registered rental customer']
    ];
    $stmt = $pdo->prepare("INSERT INTO roles (slug, name, description) VALUES (?, ?, ?)");
    foreach ($roles as $r) {
        $stmt->execute($r);
    }

    // 2. Locations
    $locations = [
        ['Central Station Hub', 'central-station-hub', 'Platform 1 Exit, Railway Station Road, Paltan Bazaar', 'Guwahati', '+91 98765 00001', 1, 1],
        ['Airport Express Hub', 'airport-express-hub', 'Terminal 1 Arrival Gate, LGBI Airport', 'Guwahati', '+91 98765 00002', 1, 1],
        ['City Center Hub (GS Road)', 'city-center-hub', 'Near City Centre Mall, Christian Basti, GS Road', 'Guwahati', '+91 98765 00003', 1, 1],
        ['University & Tech Hub', 'university-tech-hub', 'Jalukbari Point, Near Gauhati University', 'Guwahati', '+91 98765 00004', 1, 1],
    ];
    $stmt = $pdo->prepare("INSERT INTO locations (name, slug, address, city, phone, is_pickup, is_return) VALUES (?, ?, ?, ?, ?, ?, ?)");
    foreach ($locations as $l) {
        $stmt->execute($l);
    }

    // 3. Vehicle Categories
    $categories = [
        ['Scooters', 'scooters', 'bi-scooter', 'Easy, fuel-efficient daily city runabouts perfect for beating traffic.', 1],
        ['Electric Scooters', 'electric-scooters', 'bi-lightning-charge', 'Modern EV scooters with instant torque, digital displays, and zero tailpipe emissions.', 2],
        ['Premium Scooters', 'premium-scooters', 'bi-speedometer2', 'High-performance maxi-scooters with ABS, sporty ergonomics, and superior comfort.', 3],
        ['Bikes & Cruisers', 'bikes-cruisers', 'bi-bicycle', 'Powerful motorcycles ideal for highway touring, scenic river routes, and weekend thrill.', 4],
    ];
    $stmt = $pdo->prepare("INSERT INTO vehicle_categories (name, slug, icon, description, sort_order) VALUES (?, ?, ?, ?, ?)");
    foreach ($categories as $c) {
        $stmt->execute($c);
    }

    // 4. Default Users
    $passwordAdmin = password_hash('admin123', PASSWORD_BCRYPT);
    $passwordMgr = password_hash('manager123', PASSWORD_BCRYPT);
    $passwordCust = password_hash('customer123', PASSWORD_BCRYPT);

    $users = [
        ['Admin Chief', '+919876543210', 'admin@romeandride.com', $passwordAdmin, 'super_admin', 'active', 'Central Admin Office'],
        ['Vikram Sharma (Booking Lead)', '+919876543211', 'booking@romeandride.com', $passwordMgr, 'booking_manager', 'active', 'Central Station Hub'],
        ['Ananya Sen (Finance Desk)', '+919876543212', 'payment@romeandride.com', $passwordMgr, 'payment_manager', 'active', 'HQ Finance Dept'],
        ['Deepak Kalita (Fleet Head)', '+919876543213', 'fleet@romeandride.com', $passwordMgr, 'vehicle_manager', 'active', 'Airport Hub Workshop'],
        ['Rohit Baruah (Counter Staff)', '+919876543214', 'staff@romeandride.com', $passwordMgr, 'staff', 'active', 'City Center Hub'],
        ['Rahul Verma (Rider)', '+919876543220', 'rahul@gmail.com', $passwordCust, 'customer', 'active', 'Zoo Road, Guwahati'],
        ['Priya Bora (Rider)', '+919876543221', 'priya@gmail.com', $passwordCust, 'customer', 'active', 'Six Mile, Khanapara'],
        ['Amitav Goswami (Rider)', '+919876543222', 'amitav@gmail.com', $passwordCust, 'customer', 'active', 'Uzan Bazaar, Guwahati'],
    ];
    $stmt = $pdo->prepare("INSERT INTO users (name, phone, email, password_hash, role, status, address) VALUES (?, ?, ?, ?, ?, ?, ?)");
    foreach ($users as $u) {
        $stmt->execute($u);
    }

    // 5. Vehicles (10 High Quality Sample Vehicles)
    $vehicles = [
        [
            'Honda Activa 6G Sport', 'honda-activa-6g-sport', 'Honda', 'Activa 6G', 1, 'AS-01-BK-4521',
            499.00, 49.00, 1000.00, 'Petrol', 'Automatic', '109.5cc', 'Matte Axis Grey & Yellow', 2024, '48 km/l', 2, 2,
            'Mobile Mount, Helmet (x2), Tool Kit', 1, 'available', 'excellent',
            '2027-05-15', '2039-04-10', '2026-10-01', '2027-01-15',
            'assets/images/vehicles/activa6g.jpg', 1,
            'Valid 2-wheeler license required. Fuel at same level upon return. No stunt riding.',
            'Free cancellation up to 6 hours before pickup. 15% cancellation fee afterwards.'
        ],
        [
            'Vespa SXL 150 Racing Sixties', 'vespa-sxl-150-racing-sixties', 'Vespa', 'SXL 150', 3, 'AS-01-EL-9082',
            899.00, 89.00, 2000.00, 'Petrol', 'Automatic', '149.5cc', 'Innocenza White & Racing Red', 2024, '38 km/l', 2, 2,
            'Premium Chrome Mirrors, Retro Helmet, USB Port', 3, 'available', 'excellent',
            '2027-06-20', '2039-05-18', '2026-09-12', '2026-12-25',
            'assets/images/vehicles/vespa150.jpg', 1,
            'Valid driving license required. Helmets must be worn. Premium fuel recommended.',
            'Free cancellation up to 12 hours prior to scheduled start.'
        ],
        [
            'Ather 450X Gen 3 High-Performance', 'ather-450x-gen-3', 'Ather', '450X', 2, 'AS-01-EV-3310',
            699.00, 69.00, 1500.00, 'Electric', 'Automatic', '3.7 kWh (Warp Mode)', 'Space Grey & Neon Yellow', 2024, '105 km TrueRange', 2, 2,
            'Fast Charger, Touchscreen Navigation, Smart Lock', 1, 'available', 'excellent',
            '2027-08-10', '2039-07-22', '2026-10-20', '2027-02-10',
            'assets/images/vehicles/ather450x.jpg', 1,
            'Do not discharge battery below 15%. Portable home charger included.',
            'Cancel up to 6 hours before pickup for 100% refund.'
        ],
        [
            'Ola S1 Pro Gen 2 Super-EV', 'ola-s1-pro-gen-2', 'Ola Electric', 'S1 Pro Gen 2', 2, 'AS-01-EV-5541',
            649.00, 65.00, 1500.00, 'Electric', 'Automatic', '4 kWh Battery', 'Midnight Black', 2024, '130 km HyperMode', 2, 2,
            'Hypercharger Access, Music Speakers, Digital Display', 2, 'available', 'excellent',
            '2027-09-01', '2039-08-15', '2026-10-15', '2027-01-30',
            'assets/images/vehicles/olas1pro.jpg', 1,
            'Helmet mandatory. Digital key pin provided on vehicle handover.',
            'Flexible cancellation up to 6 hours before rental slot.'
        ],
        [
            'Yamaha Aerox 155 VVA Maxi-Scooter', 'yamaha-aerox-155-vva', 'Yamaha', 'Aerox 155', 3, 'AS-01-MK-7712',
            999.00, 99.00, 2500.00, 'Petrol', 'Automatic', '155cc Liquid-Cooled', 'Racing Blue & Black', 2024, '40 km/l', 2, 2,
            'ABS Braking, Traction Control, 24.5L Underseat Boot', 3, 'available', 'excellent',
            '2027-07-14', '2039-06-25', '2026-10-05', '2027-01-18',
            'assets/images/vehicles/aerox155.jpg', 1,
            'Experience required with high displacement maxi-scooters. Strict highway safety protocol.',
            'Free cancellation up to 12 hours before booking time.'
        ],
        [
            'TVS Ntorq 125 Race XP Edition', 'tvs-ntorq-125-race-xp', 'TVS', 'Ntorq 125', 1, 'AS-01-TR-6234',
            549.00, 55.00, 1000.00, 'Petrol', 'Automatic', '124.8cc 3-Valve', 'Race Red & Matte Black', 2024, '44 km/l', 2, 2,
            'Bluetooth Connected Cluster, Turn-by-Turn GPS, Helmet', 1, 'available', 'excellent',
            '2027-04-18', '2039-03-30', '2026-09-28', '2027-01-05',
            'assets/images/vehicles/ntorq125.jpg', 1,
            'Daily commuter standard terms. Zero tolerance for reckless riding.',
            'Instant refund on cancellations made at least 4 hours ahead.'
        ],
        [
            'Royal Enfield Hunter 350 Dapper', 'royal-enfield-hunter-350', 'Royal Enfield', 'Hunter 350', 4, 'AS-01-RN-1980',
            1299.00, 129.00, 3000.00, 'Petrol', 'Manual', '349cc J-Series', 'Dapper Ash & Yellow Accent', 2024, '36 km/l', 2, 2,
            'Dual Channel ABS, Touring Seat, Crash Guard, Phone Mount', 1, 'available', 'excellent',
            '2027-11-05', '2039-10-12', '2026-10-18', '2027-02-15',
            'assets/images/vehicles/hunter350.jpg', 1,
            'Motorcycle with Gear (MCWG) endorsement on Driving License mandatory.',
            'Cancellations prior to 24 hours receive 100% refund. Within 24 hours: 10% fee.'
        ],
        [
            'Aprilia SR 160 Carbon ABS', 'aprilia-sr-160-carbon', 'Aprilia', 'SR 160', 3, 'AS-01-AP-8845',
            849.00, 85.00, 2000.00, 'Petrol', 'Automatic', '160cc 3V Tech', 'Carbon Black & Crimson Red', 2024, '35 km/l', 2, 2,
            '14-inch Alloy Wheels, Front Disc ABS, USB Charger', 4, 'available', 'excellent',
            '2027-06-30', '2039-05-22', '2026-09-15', '2026-12-30',
            'assets/images/vehicles/apriliasr160.jpg', 1,
            'Sport scooter guidelines apply. Helmet included for driver and pillion.',
            'Free cancellation up to 6 hours before reservation start.'
        ],
        [
            'Suzuki Burgman Street 125 Ride Connect', 'suzuki-burgman-street-125', 'Suzuki', 'Burgman 125', 1, 'AS-01-SZ-3419',
            599.00, 59.00, 1000.00, 'Petrol', 'Automatic', '124cc SEP Engine', 'Matte Bordeaux Red', 2024, '46 km/l', 2, 2,
            'Windshield, Extra Long Footboard, Dual Luggage Hooks', 2, 'available', 'excellent',
            '2027-05-28', '2039-04-18', '2026-10-10', '2027-01-20',
            'assets/images/vehicles/burgman125.jpg', 0,
            'Spacious comfort scooter. Ideal for city sight-seeing and luggage travel.',
            'Cancel up to 6 hours before pickup without charge.'
        ],
        [
            'Revolt RV400 Electric AI Motorcycle', 'revolt-rv400-ai-electric', 'Revolt', 'RV400', 2, 'AS-01-EV-8822',
            799.00, 79.00, 2000.00, 'Electric', 'Manual', '3.24 kWh Lithium Ion', 'Cosmic Black & Rebel Red', 2024, '150 km Eco / 85 km Sport', 2, 2,
            'Exhaust Sound Simulator, Fast Charger, Geofencing', 1, 'available', 'excellent',
            '2027-10-12', '2039-09-08', '2026-10-12', '2027-02-01',
            'assets/images/vehicles/revoltrv400.jpg', 1,
            'Electric motorcycle handling experience recommended. Charger provided.',
            'Full refund on cancellation up to 6 hours in advance.'
        ],
    ];

    $vStmt = $pdo->prepare("INSERT INTO vehicles (
        name, slug, brand, model, category_id, reg_number,
        daily_rate, hourly_rate, deposit_amount, fuel_type, transmission, engine_capacity, color, year, mileage, passenger_capacity, helmet_included,
        accessories, pickup_location_id, availability_status, condition_status,
        insurance_expiry, reg_expiry, last_service_date, next_service_date,
        primary_image, is_featured, rental_rules, cancellation_policy
    ) VALUES (
        ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?,
        ?, ?, ?, ?,
        ?, ?, ?, ?
    )");

    foreach ($vehicles as $v) {
        $vStmt->execute($v);
    }

    // 6. Promo Codes
    $promos = [
        ['ROMERIDE100', 'Flat ₹100 discount on your city ride', 'fixed', 100.00, 499.00, 100.00, 500, 12, 1, '2026-01-01', '2027-12-31', 1],
        ['CONCERT20', '20% Off for concert & stadium event rentals', 'percentage', 20.00, 800.00, 400.00, 200, 28, 2, '2026-01-01', '2027-12-31', 1],
        ['WEEKEND15', '15% Off on Friday to Sunday rides', 'percentage', 15.00, 600.00, 300.00, 300, 45, 2, '2026-01-01', '2027-12-31', 1],
        ['EXPLORE50', 'Flat ₹50 quick instant discount', 'fixed', 50.00, 300.00, 50.00, 1000, 85, 3, '2026-01-01', '2027-12-31', 1],
    ];
    $stmt = $pdo->prepare("INSERT INTO promo_codes (code, description, discount_type, discount_value, min_booking_amount, max_discount_amount, max_uses, current_uses, per_customer_limit, start_date, expiry_date, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($promos as $p) {
        $stmt->execute($p);
    }

    // 7. Settings
    $settings = [
        ['site_name', 'Rome & Ride', 'general', 'Platform Name'],
        ['site_tagline', 'Pick. Ride. Explore.', 'general', 'Brand Tagline'],
        ['site_phone', '+91 98765 43210', 'general', 'Customer Service Phone'],
        ['site_email', 'support@romeandride.com', 'general', 'Customer Service Email'],
        ['site_address', 'Central Hub, Station Road, Guwahati, Assam 781001', 'general', 'HQ Address'],
        ['currency_symbol', '₹', 'financial', 'Display Currency Symbol'],
        ['currency_code', 'INR', 'financial', 'Currency Code'],
        ['advance_percentage', '25', 'booking', 'Default Advance Payment Percentage (%)'],
        ['min_advance_amount', '300', 'booking', 'Minimum advance booking amount'],
        ['pay_later_enabled', '1', 'booking', 'Allow customers to reserve with Pay Later option'],
        ['pay_later_deadline_hours', '4', 'booking', 'Hours before pickup to settle Pay Later balance'],
        ['default_security_deposit', '1000', 'booking', 'Standard refundable security deposit'],
        ['cancellation_hours', '6', 'booking', 'Free cancellation cut-off before pickup in hours'],
        ['tax_percentage', '5', 'financial', 'GST / Local Tax Percentage'],
        ['document_upload_required', '1', 'booking', 'Driving license mandatory before ride handover'],
        ['emergency_hotline', '+91 98765 99999', 'general', '24/7 Roadside Assistance Hotline'],
    ];
    $stmt = $pdo->prepare("INSERT INTO settings (`key`, `value`, `group`, `description`) VALUES (?, ?, ?, ?)");
    foreach ($settings as $s) {
        $stmt->execute($s);
    }

    // 8. Sample Bookings
    $bookings = [
        [
            'RNR-2026-1001', 6, 1, 1, 1, 1,
            '2026-11-20 09:00:00', '2026-11-22 18:00:00',
            2, 48, 499.00, 0.00, 998.00, 1000.00, 0.00, 100.00, 'ROMERIDE100', 44.90, 1942.90,
            500.00, 500.00, 1442.90, 'advance', 'partial',
            'booking_confirmed', 'approved', '2026-11-20 07:00:00',
            'Rahul Verma', '+919876543220', 'rahul@gmail.com', 'Planning a 2-day visit to river ghats and temples.', 'Customer verified. Scooters pre-inspected.'
        ],
        [
            'RNR-2026-1002', 7, 3, 3, 2, 2,
            '2026-11-21 10:00:00', '2026-11-21 20:00:00',
            1, 10, 699.00, 69.00, 699.00, 1500.00, 0.00, 0.00, NULL, 34.95, 2233.95,
            600.00, 0.00, 2233.95, 'pay_later', 'pending',
            'pending_payment', 'uploaded', '2026-11-21 06:00:00',
            'Priya Bora', '+919876543221', 'priya@gmail.com', 'Need smart EV scooter for music concert in city.', 'Pay later reservation under review.'
        ],
        [
            'RNR-2026-1003', 8, 7, 7, 1, 1,
            '2026-11-18 08:00:00', '2026-11-19 20:00:00',
            2, 36, 1299.00, 129.00, 2598.00, 3000.00, 0.00, 389.70, 'WEEKEND15', 110.42, 5318.72,
            1500.00, 5318.72, 0.00, 'full', 'paid',
            'completed', 'approved', NULL,
            'Amitav Goswami', '+919876543222', 'amitav@gmail.com', 'Shillong highway scenic ride.', 'Returned smoothly. Deposit refunded.'
        ]
    ];

    $bStmt = $pdo->prepare("INSERT INTO bookings (
        booking_code, customer_id, vehicle_id, assigned_vehicle_id, pickup_location_id, return_location_id,
        pickup_datetime, return_datetime,
        total_days, total_hours, daily_rate, hourly_rate, rental_charge, deposit_amount, additional_charges, discount_amount, promo_code, tax_amount, total_amount,
        advance_required, amount_paid, remaining_balance, payment_option, payment_status,
        booking_status, document_verification_status, payment_deadline,
        customer_name, customer_phone, customer_email, customer_notes, admin_notes
    ) VALUES (
        ?, ?, ?, ?, ?, ?,
        ?, ?,
        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?,
        ?, ?, ?,
        ?, ?, ?, ?, ?
    )");
    foreach ($bookings as $b) {
        $bStmt->execute($b);
    }

    // 9. Sample Payments
    $payments = [
        ['PAY-2026-8801', 1, 6, 500.00, 'booking_advance', 'upi', 'UPI/20261119/90823412', '2026-11-19 14:30:00', 'successful', 'Booking advance paid online via UPI QR', 1],
        ['PAY-2026-8802', 3, 8, 1500.00, 'booking_advance', 'card', 'TXN_CARD_772199', '2026-11-17 18:00:00', 'successful', 'Advance paid via Card', 1],
        ['PAY-2026-8803', 3, 8, 3818.72, 'full_payment', 'cash', 'CASH_REC_00192', '2026-11-18 08:15:00', 'successful', 'Counter settlement before ride start', 5],
    ];
    $stmt = $pdo->prepare("INSERT INTO payments (payment_code, booking_id, customer_id, amount, payment_type, payment_method, transaction_reference, payment_date, payment_status, notes, created_by_user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($payments as $p) {
        $stmt->execute($p);
    }

    // 10. Sample Documents (Driving License + Rental Agreement)
    $documents = [
        [6, 1, 1, 'Driving License - Front', 'driving_license_front', 'storage/private_documents/license_rahul_front.pdf', 'license_rahul_front.pdf', 345000, 'application/pdf', 'approved', 1, 0, NULL, 1, '2026-11-19 15:00:00', '2034-08-12'],
        [6, 1, 1, 'Driving License - Back', 'driving_license_back', 'storage/private_documents/license_rahul_back.pdf', 'license_rahul_back.pdf', 312000, 'application/pdf', 'approved', 1, 0, NULL, 1, '2026-11-19 15:00:00', '2034-08-12'],
        [6, 1, 1, 'Vehicle Rental Agreement (RNR-2026-1001)', 'rental_agreement', 'storage/private_documents/agreement_1001.pdf', 'agreement_1001.pdf', 154000, 'application/pdf', 'approved', 1, 0, NULL, 1, '2026-11-19 15:10:00', NULL],
        [6, 1, 1, 'Vehicle Registration Certificate (RC)', 'registration_certificate', 'storage/private_documents/rc_as01bk4521.pdf', 'rc_as01bk4521.pdf', 210000, 'application/pdf', 'approved', 1, 0, NULL, 1, '2026-11-19 15:10:00', '2039-04-10'],
        [6, 1, 1, 'Comprehensive Insurance Certificate', 'insurance', 'storage/private_documents/insurance_as01bk4521.pdf', 'insurance_as01bk4521.pdf', 198000, 'application/pdf', 'approved', 1, 0, NULL, 1, '2026-11-19 15:10:00', '2027-05-15'],
        [6, 1, 1, 'Pickup & Ride Instructions', 'pickup_instructions', 'storage/private_documents/pickup_guidelines.pdf', 'pickup_guidelines.pdf', 95000, 'application/pdf', 'approved', 1, 0, NULL, 1, '2026-11-19 15:10:00', NULL],
        [7, 2, 3, 'Driving License - Front', 'driving_license_front', 'storage/private_documents/license_priya_front.jpg', 'license_priya_front.jpg', 420000, 'image/jpeg', 'under_review', 1, 0, NULL, NULL, NULL, '2032-02-15'],
    ];
    $stmt = $pdo->prepare("INSERT INTO documents (user_id, booking_id, vehicle_id, title, doc_type, file_path, file_name, file_size, mime_type, verification_status, is_customer_visible, is_confidential, rejection_reason, verified_by_user_id, verified_at, expiry_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($documents as $d) {
        $stmt->execute($d);
    }

    // 11. Vehicle Handover records for completed trip 1003
    $pdo->exec("INSERT INTO vehicle_handover (
        booking_id, vehicle_id, staff_id, customer_id, handover_type, handover_datetime,
        odometer_reading, fuel_level, condition_notes, existing_scratches, accessories_supplied, helmet_count,
        customer_signature_confirmed, staff_signature_confirmed
    ) VALUES (
        3, 7, 5, 8, 'pickup', '2026-11-18 08:30:00',
        4250, 'Full (100%)', 'Bike handed over in clean condition, tires at 32 psi.', 'Minor brush mark on right exhaust shield.', 'Phone mount, Touring kit, 2 Helmets', 2,
        1, 1
    )");
    $pdo->exec("INSERT INTO vehicle_handover (
        booking_id, vehicle_id, staff_id, customer_id, handover_type, handover_datetime,
        odometer_reading, fuel_level, condition_notes, existing_scratches, accessories_supplied, helmet_count,
        customer_signature_confirmed, staff_signature_confirmed, total_extra_charges
    ) VALUES (
        3, 7, 5, 8, 'return', '2026-11-19 19:45:00',
        4580, 'Full (100%)', 'Returned on time, no new damages, pristine condition.', 'Minor brush mark on right exhaust shield.', 'Phone mount returned, 2 Helmets returned intact', 2,
        1, 1, 0.00
    )");

    // 12. Reviews
    $reviews = [
        [1, 6, 'Rahul Verma', 5, 'Rented the Activa 6G for 2 days. The pickup process was super quick, scooter was spotless and ran like a breeze! Highly recommend Rome & Ride!', 1, 'approved'],
        [3, 7, 'Priya Bora', 5, 'The Ather 450X was fantastic! Warp mode is pure joy in city traffic. Battery range was more than enough for my concert trip.', 1, 'approved'],
        [7, 8, 'Amitav Goswami', 5, 'Took the Royal Enfield Hunter 350 to the highway. Super smooth engine, clean handover docs available right in my account. 10/10 service!', 1, 'approved'],
        [2, 6, 'Devraj Choudhury', 5, 'Vespa 150 is pure style. Got compliments everywhere I parked. Customer support was very helpful when I had to extend by 2 hours.', 1, 'approved'],
    ];
    $stmt = $pdo->prepare("INSERT INTO reviews (vehicle_id, user_id, customer_name, rating, comment, is_featured, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    foreach ($reviews as $rev) {
        $stmt->execute($rev);
    }

    // 13. Audit Log
    $pdo->exec("INSERT INTO audit_logs (user_id, user_role, action, entity_type, entity_id, new_values, ip_address, user_agent) VALUES (
        1, 'super_admin', 'SYSTEM_INITIALIZATION', 'system', '1', 'Initial database setup and fleet seeding completed', '127.0.0.1', 'Rome&Ride Seeder/1.0'
    )");

    echo "Seeding completed successfully!\n";
    echo "=====================================\n";
    echo "Default Admin: admin@romeandride.com | Phone: +919876543210 | Pass: admin123\n";
    echo "Default Customer: rahul@gmail.com   | Phone: +919876543220 | Pass: customer123\n";
    echo "=====================================\n";

} catch (Exception $e) {
    echo "ERROR during migration: " . $e->getMessage() . "\n";
    exit(1);
}

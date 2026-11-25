<?php
/**
 * Generate Assets & Seed Documents
 * Rome & Ride
 */

$vehicles = [
    ["activa6g.jpg", "HONDA ACTIVA 6G", "#FFD700", "SPORT COMMUTER"],
    ["vespa150.jpg", "VESPA SXL 150", "#EF4444", "RACING SIXTIES"],
    ["ather450x.jpg", "ATHER 450X GEN 3", "#22C55E", "WARP EV SCOOTER"],
    ["olas1pro.jpg", "OLA S1 PRO GEN 2", "#3B82F6", "HYPER SMART EV"],
    ["aerox155.jpg", "YAMAHA AEROX 155", "#6366F1", "MAXI VVA RACER"],
    ["ntorq125.jpg", "TVS NTORQ 125", "#F97316", "RACE XP EDITION"],
    ["hunter350.jpg", "RE HUNTER 350", "#EAB308", "STREET ROADSTER"],
    ["apriliasr160.jpg", "APRILIA SR 160", "#DC2626", "CARBON RACER"],
    ["burgman125.jpg", "SUZUKI BURGMAN", "#06B6D4", "MAXI COMMUTER"],
    ["revoltrv400.jpg", "REVOLT RV400", "#A855F7", "AI ELECTRIC BIKE"]
];

$dir = __DIR__ . "/../public/assets/images/vehicles";
if (!is_dir($dir)) mkdir($dir, 0777, true);

foreach ($vehicles as $v) {
    list($file, $name, $accent, $sub) = $v;
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 500" width="800" height="500">
      <defs>
        <linearGradient id="bg_' . md5($file) . '" x1="0%" y1="0%" x2="100%" y2="100%">
          <stop offset="0%" stop-color="#18181b"/>
          <stop offset="100%" stop-color="#09090b"/>
        </linearGradient>
        <linearGradient id="glow_' . md5($file) . '" x1="0%" y1="0%" x2="100%" y2="0%">
          <stop offset="0%" stop-color="' . $accent . '" stop-opacity="0.9"/>
          <stop offset="100%" stop-color="#FFE500" stop-opacity="0.4"/>
        </linearGradient>
      </defs>
      <rect width="800" height="500" fill="url(#bg_' . md5($file) . ')"/>
      <circle cx="400" cy="230" r="180" fill="' . $accent . '" opacity="0.08"/>
      <path d="M50 380 L750 380" stroke="#27272a" stroke-width="2" stroke-dasharray="10 10"/>
      
      <g transform="translate(180, 110)">
        <circle cx="80" cy="220" r="50" fill="#1c1917" stroke="' . $accent . '" stroke-width="6"/>
        <circle cx="80" cy="220" r="22" fill="#27272a" stroke="#71717a" stroke-width="3"/>
        <circle cx="360" cy="220" r="50" fill="#1c1917" stroke="' . $accent . '" stroke-width="6"/>
        <circle cx="360" cy="220" r="22" fill="#27272a" stroke="#71717a" stroke-width="3"/>

        <path d="M90 205 L150 145 L220 175 L290 135 L350 105 L370 65 L340 65 L320 95 L250 125 L160 125 L110 185 Z" fill="url(#glow_' . md5($file) . ')"/>
        <path d="M150 145 L220 175 L280 175 L320 125 L260 125 Z" fill="#27272a" stroke="' . $accent . '" stroke-width="2"/>
        <line x1="340" y1="65" x2="315" y2="45" stroke="#ffffff" stroke-width="6" stroke-linecap="round"/>
        <circle cx="350" cy="70" r="9" fill="#FFE500"/>
        <path d="M160 125 Q220 105 260 123 L250 135 L170 135 Z" fill="#09090b" stroke="#71717a" stroke-width="2"/>
      </g>

      <rect x="40" y="40" width="160" height="32" rx="6" fill="#27272a" stroke="' . $accent . '" stroke-width="1.5"/>
      <text x="120" y="61" fill="' . $accent . '" font-family="Segoe UI, sans-serif" font-weight="bold" font-size="13" text-anchor="middle">ROME &amp; RIDE</text>

      <text x="400" y="415" fill="#FFFFFF" font-family="Segoe UI, sans-serif" font-weight="900" font-size="28" text-anchor="middle" letter-spacing="1">' . htmlspecialchars($name) . '</text>
      <text x="400" y="445" fill="' . $accent . '" font-family="Segoe UI, sans-serif" font-weight="bold" font-size="14" text-anchor="middle" letter-spacing="3">' . htmlspecialchars($sub) . '</text>
    </svg>';
    
    file_put_contents("$dir/$file", $svg);
    $svgFile = str_replace(".jpg", ".svg", $file);
    file_put_contents("$dir/$svgFile", $svg);
}

// Generate sample private documents
$docDir = __DIR__ . "/../storage/private_documents";
if (!is_dir($docDir)) mkdir($docDir, 0777, true);

$sampleDocs = [
    'license_rahul_front.pdf' => "%PDF-1.4 Sample Driving License Front - Rahul Verma - DL-0120220098231 - Class: MCWG / LMV - Valid: 2034-08-12",
    'license_rahul_back.pdf' => "%PDF-1.4 Sample Driving License Back - Rahul Verma - Address: Zoo Road, Guwahati - Emergency Contact: 9876543220",
    'agreement_1001.pdf' => "%PDF-1.4 ROME & RIDE OFFICIAL VEHICLE RENTAL AGREEMENT\nBooking Code: RNR-2026-1001\nRider: Rahul Verma\nVehicle: Honda Activa 6G (AS-01-BK-4521)\nDuration: 2026-11-20 to 2026-11-22\nSecurity Deposit: INR 1,000 (Refundable)\nTerms: Rider acknowledges road safety adherence, helmet usage, and fuel return at parity.\nStatus: Confirmed and Signed.",
    'rc_as01bk4521.pdf' => "%PDF-1.4 GOVERNMENT OF ASSAM - MOTOR VEHICLES DEPT\nRegistration Certificate: AS-01-BK-4521\nVehicle: Honda Activa 6G Sport\nOwner: Rome & Ride Fleet Operations Ltd\nEngine: 109.5cc | Fuel: Petrol\nFitness Valid Upto: 2039-04-10",
    'insurance_as01bk4521.pdf' => "%PDF-1.4 TATA AIG GENERAL INSURANCE - COMMERCIAL TWO-WHEELER COMPREHENSIVE POLICY\nPolicy No: RNR-POL-2026-78219\nVehicle Reg: AS-01-BK-4521\nSum Insured: INR 85,000\nThird Party Liability: Comprehensive Active\nValid Through: 2027-05-15",
    'pickup_guidelines.pdf' => "%PDF-1.4 ROME & RIDE - RIDER PICKUP & SAFETY INSTRUCTIONS\n1. Bring original Govt Photo ID & Driving License to Central Station Hub.\n2. Collect vehicle keys and 2 sanitized helmets.\n3. Verify digital odometer and fuel level with staff before departure.\n4. Emergency Roadside Assistance: +91 98765 99999\n5. Return vehicle with clean condition and equal fuel level.",
    'license_priya_front.jpg' => "Sample Image Data - Driving License Front - Priya Bora"
];

foreach ($sampleDocs as $fName => $content) {
    file_put_contents("$docDir/$fName", $content);
}

echo "Assets & sample documents created.\n";

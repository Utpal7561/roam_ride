<?php
/**
 * Generate Real Valid Binary JPEG Images using GD
 * Rome & Ride
 */

$vehicles = [
    ["activa6g.jpg", "HONDA ACTIVA 6G", [255, 215, 0], "SPORT COMMUTER"],
    ["vespa150.jpg", "VESPA SXL 150", [239, 68, 68], "RACING SIXTIES"],
    ["ather450x.jpg", "ATHER 450X GEN 3", [34, 197, 94], "WARP EV SCOOTER"],
    ["olas1pro.jpg", "OLA S1 PRO GEN 2", [59, 130, 246], "HYPER SMART EV"],
    ["aerox155.jpg", "YAMAHA AEROX 155", [99, 102, 241], "MAXI VVA RACER"],
    ["ntorq125.jpg", "TVS NTORQ 125", [249, 115, 22], "RACE XP EDITION"],
    ["hunter350.jpg", "RE HUNTER 350", "[234, 179, 8]", "STREET ROADSTER"],
    ["apriliasr160.jpg", "APRILIA SR 160", [220, 38, 38], "CARBON RACER"],
    ["burgman125.jpg", "SUZUKI BURGMAN 125", [6, 182, 212], "MAXI COMMUTER"],
    ["revoltrv400.jpg", "REVOLT RV400", [168, 85, 247], "AI ELECTRIC BIKE"]
];

$dir = __DIR__ . "/../public/assets/images/vehicles";
if (!is_dir($dir)) mkdir($dir, 0777, true);

foreach ($vehicles as $v) {
    list($file, $name, $rgb, $sub) = $v;
    if (is_string($rgb)) {
        $rgb = [234, 179, 8];
    }

    $width = 800;
    $height = 500;
    $im = imagecreatetruecolor($width, $height);

    // Dark sleek background
    $bg = imagecolorallocate($im, 18, 18, 21);
    imagefilledrectangle($im, 0, 0, $width, $height, $bg);

    // Grid lines / floor
    $gridColor = imagecolorallocate($im, 39, 39, 42);
    for ($y = 280; $y <= 400; $y += 30) {
        imageline($im, 50, $y, 750, $y, $gridColor);
    }

    // Vehicle accent color
    $accent = imagecolorallocate($im, $rgb[0], $rgb[1], $rgb[2]);
    $yellow = imagecolorallocate($im, 255, 229, 0);
    $white = imagecolorallocate($im, 255, 255, 255);
    $gray = imagecolorallocate($im, 161, 161, 170);
    $darkGray = imagecolorallocate($im, 34, 34, 38);

    // Glowing circle in background
    imagefilledellipse($im, 400, 220, 260, 260, $darkGray);
    imageellipse($im, 400, 220, 270, 270, $accent);

    // Wheels (Front & Rear)
    // Rear wheel
    imagefilledellipse($im, 260, 300, 90, 90, imagecolorallocate($im, 10, 10, 12));
    imageellipse($im, 260, 300, 94, 94, $accent);
    imagefilledellipse($im, 260, 300, 34, 34, $darkGray);

    // Front wheel
    imagefilledellipse($im, 540, 300, 90, 90, imagecolorallocate($im, 10, 10, 12));
    imageellipse($im, 540, 300, 94, 94, $accent);
    imagefilledellipse($im, 540, 300, 34, 34, $darkGray);

    // Body frame lines
    imagesetthickness($im, 6);
    imageline($im, 260, 280, 340, 220, $accent);
    imageline($im, 340, 220, 440, 230, $white);
    imageline($im, 440, 230, 500, 170, $accent);
    imageline($im, 500, 170, 480, 130, $white); // Handlebar
    imageline($im, 480, 130, 460, 120, $yellow);
    imageline($im, 500, 170, 540, 300, $accent); // Front fork

    // Headlight glow
    imagefilledellipse($im, 505, 170, 16, 16, $yellow);

    // Seat
    imagesetthickness($im, 8);
    imageline($im, 320, 200, 410, 210, $darkGray);

    // Reset thickness
    imagesetthickness($im, 1);

    // Brand tag top left
    imagefilledrectangle($im, 40, 40, 200, 75, $darkGray);
    imagerectangle($im, 40, 40, 200, 75, $accent);
    imagestring($im, 4, 60, 50, "ROME & RIDE", $yellow);

    // Text labels at bottom
    // Name
    $font = 5; // Built-in font 5 is largest standard font
    $textLen = strlen($name) * imagefontwidth($font);
    $x = (int)(($width - $textLen) / 2);
    imagestring($im, $font, $x, 390, $name, $white);

    // Subtitle
    $subLen = strlen($sub) * imagefontwidth(3);
    $subX = (int)(($width - $subLen) / 2);
    imagestring($im, 3, $subX, 420, $sub, $accent);

    // Category / Tag
    $tag = "PICK . RIDE . EXPLORE";
    $tagLen = strlen($tag) * imagefontwidth(2);
    $tagX = (int)(($width - $tagLen) / 2);
    imagestring($im, 2, $tagX, 445, $tag, $gray);

    // Save as true binary JPEG file
    imagejpeg($im, "$dir/$file", 92);
    imagedestroy($im);
}

echo "All 10 real JPEG binary images generated successfully!\n";

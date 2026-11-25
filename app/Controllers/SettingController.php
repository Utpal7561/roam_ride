<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Database;
use App\Models\Setting;
use App\Models\Promotion;

class SettingController extends Controller {
    public function publicSettings(): void {
        $settings = [
            'site_name' => Setting::get('site_name', 'Rome & Ride'),
            'site_tagline' => Setting::get('site_tagline', 'PICK. RIDE. EXPLORE.'),
            'site_phone' => Setting::get('site_phone', '+91 9395485880'),
            'site_email' => Setting::get('site_email', 'support@romeandride.com'),
            'site_address' => Setting::get('site_address', 'Kamarkuchi, Sonapur, NH 37'),
            'currency_symbol' => Setting::get('currency_symbol', '₹'),
            'advance_percentage' => (int) Setting::get('advance_percentage', 25),
            'pay_later_enabled' => (bool) Setting::get('pay_later_enabled', 1),
            'emergency_hotline' => Setting::get('emergency_hotline', '+91 9395485880'),
            'tax_percentage' => (float) Setting::get('tax_percentage', 5),
            'hero_image' => Setting::get('hero_image', 'assets/images/vehicles/activa6g.jpg'),
            'hero_badge' => Setting::get('hero_badge', 'BESTSELLER'),
            'hero_title' => Setting::get('hero_title', 'Honda Activa 6G Sport'),
            'hero_subtitle' => Setting::get('hero_subtitle', 'From ₹499 / day (₹49/hr)'),
            'hero_tagline_left' => Setting::get('hero_tagline_left', 'Kamarkuchi Hub (Sonapur)'),
            'hero_tagline_right' => Setting::get('hero_tagline_right', 'Instant 5-Min Handover'),
        ];
        $this->success($settings);
    }

    public function allSettings(): void {
        $settings = Setting::getAllGrouped();
        $this->success($settings);
    }

    public function updateHero(Request $request): void {
        $admin = $request->getUser();
        $file = $request->getFile('hero_image_file');
        
        $imagePath = null;
        if ($file && !empty($file['tmp_name'])) {
            $validation = \App\Helpers\Security::validateUploadedFile($file, ['image/jpeg', 'image/png', 'image/webp'], 5 * 1024 * 1024);
            if (!$validation['valid']) {
                $this->error($validation['error']);
            }
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $fileName = 'hero_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
            $destDir = dirname(__DIR__, 2) . '/public/uploads/hero';
            if (!is_dir($destDir)) {
                mkdir($destDir, 0755, true);
            }
            $dest = $destDir . '/' . $fileName;
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $imagePath = 'uploads/hero/' . $fileName;
                Setting::set('hero_image', $imagePath, 'hero', 'Homepage Hero Image');
            }
        } elseif ($request->get('hero_image')) {
            $imagePath = trim($request->get('hero_image'));
            Setting::set('hero_image', $imagePath, 'hero', 'Homepage Hero Image');
        }

        if ($request->get('hero_badge') !== null) {
            Setting::set('hero_badge', trim($request->get('hero_badge')), 'hero');
        }
        if ($request->get('hero_title') !== null) {
            Setting::set('hero_title', trim($request->get('hero_title')), 'hero');
        }
        if ($request->get('hero_subtitle') !== null) {
            Setting::set('hero_subtitle', trim($request->get('hero_subtitle')), 'hero');
        }
        if ($request->get('hero_tagline_left') !== null) {
            Setting::set('hero_tagline_left', trim($request->get('hero_tagline_left')), 'hero');
        }
        if ($request->get('hero_tagline_right') !== null) {
            Setting::set('hero_tagline_right', trim($request->get('hero_tagline_right')), 'hero');
        }

        $this->logAudit($admin['id'], $admin['role'], 'UPDATE_HERO', 'settings', 'hero', null, $imagePath);

        $this->success([
            'hero_image' => Setting::get('hero_image', 'assets/images/vehicles/activa6g.jpg'),
            'hero_badge' => Setting::get('hero_badge', 'BESTSELLER'),
            'hero_title' => Setting::get('hero_title', 'Honda Activa 6G Sport'),
            'hero_subtitle' => Setting::get('hero_subtitle', 'From ₹499 / day (₹49/hr)'),
            'hero_tagline_left' => Setting::get('hero_tagline_left', 'Kamarkuchi Hub (Sonapur)'),
            'hero_tagline_right' => Setting::get('hero_tagline_right', 'Instant 5-Min Handover'),
        ], 'Homepage picture and hero content updated successfully!');
    }

    public function updateSettings(Request $request): void {
        $admin = $request->getUser();
        $data = $request->all();

        foreach ($data as $key => $val) {
            Setting::set($key, (string)$val);
        }

        $this->logAudit($admin['id'], $admin['role'], 'UPDATE_SETTINGS', 'settings', 'all');
        $this->success(null, 'System settings saved successfully.');
    }

    public function promotions(): void {
        $pdo = Database::getConnection();
        $promos = $pdo->query("SELECT * FROM promo_codes ORDER BY id DESC")->fetchAll();
        $this->success($promos);
    }

    public function addPromotion(Request $request): void {
        $data = $this->validate($request->all(), [
            'code' => 'required|unique:promo_codes,code',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric',
            'start_date' => 'required|date',
            'expiry_date' => 'required|date',
        ]);

        $admin = $request->getUser();
        $promoModel = new Promotion();

        $promoId = $promoModel->create([
            'code' => strtoupper(trim($data['code'])),
            'description' => $data['description'] ?? null,
            'discount_type' => $data['discount_type'],
            'discount_value' => (float)$data['discount_value'],
            'min_booking_amount' => (float)($data['min_booking_amount'] ?? 0.00),
            'max_discount_amount' => !empty($data['max_discount_amount']) ? (float)$data['max_discount_amount'] : null,
            'max_uses' => (int)($data['max_uses'] ?? 100),
            'current_uses' => 0,
            'per_customer_limit' => (int)($data['per_customer_limit'] ?? 1),
            'start_date' => $data['start_date'],
            'expiry_date' => $data['expiry_date'],
            'is_active' => 1,
        ]);

        $this->logAudit($admin['id'], $admin['role'], 'CREATE_PROMO', 'promo_codes', (string)$promoId, null, $data['code']);
        $this->success(['promo_id' => $promoId], 'Promo discount code created successfully.', 201);
    }
}

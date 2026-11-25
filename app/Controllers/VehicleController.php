<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Database;
use App\Models\Vehicle;

class VehicleController extends Controller {
    private Vehicle $vehicleModel;

    public function __construct() {
        $this->vehicleModel = new Vehicle();
    }

    public function index(Request $request): void {
        $filters = [
            'category_id' => $request->getQuery('category_id'),
            'category_slug' => $request->getQuery('category'),
            'availability_status' => $request->getQuery('availability', 'available'),
            'fuel_type' => $request->getQuery('fuel_type'),
            'transmission' => $request->getQuery('transmission'),
            'min_price' => $request->getQuery('min_price'),
            'max_price' => $request->getQuery('max_price'),
            'search' => $request->getQuery('search'),
            'featured' => $request->getQuery('featured'),
            'sort' => $request->getQuery('sort', 'popular')
        ];

        // Public vehicle list excludes sensitive reg_number unless specifically allowed
        $vehicles = $this->vehicleModel->getFiltered($filters);
        
        // Strip or mask registration number for public view if needed
        $user = $request->getUser();
        $isAdmin = $user && in_array($user['role'], ['super_admin', 'admin', 'vehicle_manager', 'booking_manager', 'staff']);

        if (!$isAdmin) {
            foreach ($vehicles as &$v) {
                // Mask reg number like AS-01-**-4521
                if (!empty($v['reg_number'])) {
                    $parts = explode('-', $v['reg_number']);
                    if (count($parts) >= 4) {
                        $v['reg_number_masked'] = $parts[0] . '-' . $parts[1] . '-**-' . $parts[3];
                    } else {
                        $v['reg_number_masked'] = substr($v['reg_number'], 0, 5) . '****';
                    }
                }
            }
        }

        $this->success($vehicles);
    }

    public function show(Request $request, array $params): void {
        $id = $params['id'];
        $vehicle = $this->vehicleModel->findWithDetails($id);

        if (!$vehicle) {
            $this->error('Vehicle not found', null, 404);
        }

        // Fetch additional gallery images
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM vehicle_images WHERE vehicle_id = ? ORDER BY sort_order ASC");
        $stmt->execute([$vehicle['id']]);
        $images = $stmt->fetchAll();

        // Fetch approved reviews for this vehicle
        $rStmt = $pdo->prepare("SELECT * FROM reviews WHERE vehicle_id = ? AND status = 'approved' ORDER BY id DESC");
        $rStmt->execute([$vehicle['id']]);
        $reviews = $rStmt->fetchAll();

        $vehicle['gallery'] = $images;
        $vehicle['reviews'] = $reviews;

        $this->success($vehicle);
    }

    public function checkAvailability(Request $request, array $params): void {
        $id = (int)$params['id'];
        $start = $request->getQuery('pickup_datetime');
        $end = $request->getQuery('return_datetime');

        if (!$start || !$end) {
            $this->error('Pickup and return datetime are required.');
        }

        $isAvailable = $this->vehicleModel->isAvailableForPeriod($id, $start, $end);
        $this->success([
            'vehicle_id' => $id,
            'is_available' => $isAvailable,
            'pickup_datetime' => $start,
            'return_datetime' => $end,
        ]);
    }

    public function categories(): void {
        $pdo = Database::getConnection();
        $categories = $pdo->query("SELECT c.*, (SELECT COUNT(*) FROM vehicles WHERE category_id = c.id AND availability_status = 'available') as vehicle_count 
                                  FROM vehicle_categories c 
                                  WHERE status = 'active' 
                                  ORDER BY sort_order ASC")->fetchAll();
        $this->success($categories);
    }

    public function locations(): void {
        $pdo = Database::getConnection();
        $locations = $pdo->query("SELECT * FROM locations WHERE status = 'active' ORDER BY id ASC")->fetchAll();
        $this->success($locations);
    }
}

<?php
/**
 * API Routes Definition
 * Rome & Ride REST API v1
 */

use App\Core\Router;
use App\Core\Request;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Controllers\AuthController;
use App\Controllers\VehicleController;
use App\Controllers\BookingController;
use App\Controllers\PaymentController;
use App\Controllers\DocumentController;
use App\Controllers\AdminController;
use App\Controllers\ReportController;
use App\Controllers\SupportController;
use App\Controllers\SettingController;

/** @var Router $router */

// Middleware helpers
$authRequired = fn(Request $r) => AuthMiddleware::handle($r, true);
$authOptional = fn(Request $r) => AuthMiddleware::handle($r, false);
$adminOnly = function(Request $r) {
    if (!AuthMiddleware::handle($r, true)) return false;
    return RoleMiddleware::handle($r, ['super_admin', 'admin']);
};
$staffOrAdmin = function(Request $r) {
    if (!AuthMiddleware::handle($r, true)) return false;
    return RoleMiddleware::handle($r, ['super_admin', 'admin', 'booking_manager', 'payment_manager', 'vehicle_manager', 'staff']);
};

// --- AUTHENTICATION ---
$router->post('/api/v1/auth/register', [AuthController::class, 'register']);
$router->post('/api/v1/auth/login', [AuthController::class, 'login']);
$router->post('/api/v1/auth/logout', [AuthController::class, 'logout']);
$router->get('/api/v1/auth/me', [AuthController::class, 'me'], [$authRequired]);
$router->put('/api/v1/auth/profile', [AuthController::class, 'updateProfile'], [$authRequired]);

// --- VEHICLES (PUBLIC) ---
$router->get('/api/v1/vehicles', [VehicleController::class, 'index'], [$authOptional]);
$router->get('/api/v1/vehicles/{id}', [VehicleController::class, 'show'], [$authOptional]);
$router->get('/api/v1/vehicles/{id}/availability', [VehicleController::class, 'checkAvailability']);
$router->get('/api/v1/categories', [VehicleController::class, 'categories']);
$router->get('/api/v1/locations', [VehicleController::class, 'locations']);

// --- BOOKINGS ---
$router->post('/api/v1/bookings', [BookingController::class, 'create'], [$authOptional]);
$router->get('/api/v1/bookings', [BookingController::class, 'index'], [$authRequired]);
$router->get('/api/v1/bookings/{id}', [BookingController::class, 'show'], [$authOptional]);
$router->post('/api/v1/bookings/{id}/cancel', [BookingController::class, 'cancel'], [$authRequired]);
$router->post('/api/v1/bookings/apply-promo', [BookingController::class, 'applyPromo'], [$authOptional]);

// --- PAYMENTS ---
$router->post('/api/v1/payments/process', [PaymentController::class, 'process'], [$authOptional]);
$router->post('/api/v1/admin/payments/manual', [PaymentController::class, 'recordManual'], [$staffOrAdmin]);
$router->post('/api/v1/admin/payments/refund', [PaymentController::class, 'recordRefund'], [$staffOrAdmin]);
$router->get('/api/v1/admin/payments/summary', [PaymentController::class, 'summary'], [$staffOrAdmin]);

// --- DOCUMENTS ---
$router->post('/api/v1/documents/upload', [DocumentController::class, 'upload'], [$authRequired]);
$router->get('/api/v1/documents', [DocumentController::class, 'index'], [$authRequired]);
$router->get('/api/v1/documents/{id}/download', [DocumentController::class, 'download'], [$authOptional]);
$router->post('/api/v1/admin/documents/{id}/verify', [DocumentController::class, 'verify'], [$staffOrAdmin]);
$router->post('/api/v1/admin/bookings/{id}/documents', [DocumentController::class, 'attachRentalDocument'], [$staffOrAdmin]);

// --- ADMIN MANAGEMENT ---
$router->get('/api/v1/admin/dashboard', [AdminController::class, 'dashboard'], [$staffOrAdmin]);
$router->get('/api/v1/admin/bookings', [AdminController::class, 'bookings'], [$staffOrAdmin]);
$router->post('/api/v1/admin/bookings/manual', [AdminController::class, 'createManualBooking'], [$staffOrAdmin]);
$router->put('/api/v1/admin/bookings/{id}/status', [AdminController::class, 'updateBookingStatus'], [$staffOrAdmin]);
$router->put('/api/v1/admin/bookings/{id}/deposit', [AdminController::class, 'updateBookingDeposit'], [$staffOrAdmin]);
$router->post('/api/v1/admin/bookings/{id}/deposit', [AdminController::class, 'updateBookingDeposit'], [$staffOrAdmin]);
$router->post('/api/v1/admin/bookings/{id}/assign-vehicle', [AdminController::class, 'assignVehicle'], [$staffOrAdmin]);

$router->post('/api/v1/admin/vehicles', [AdminController::class, 'addVehicle'], [$staffOrAdmin]);
$router->put('/api/v1/admin/vehicles/{id}', [AdminController::class, 'editVehicle'], [$staffOrAdmin]);
$router->post('/api/v1/admin/vehicles/{id}', [AdminController::class, 'editVehicle'], [$staffOrAdmin]);
$router->delete('/api/v1/admin/vehicles/{id}', [AdminController::class, 'deleteVehicle'], [$staffOrAdmin]);

$router->post('/api/v1/admin/handover', [AdminController::class, 'recordHandover'], [$staffOrAdmin]);
$router->get('/api/v1/admin/customers', [AdminController::class, 'customers'], [$staffOrAdmin]);
$router->get('/api/v1/admin/audit-logs', [AdminController::class, 'auditLogs'], [$adminOnly]);

// --- REPORTS ---
$router->get('/api/v1/admin/reports/bookings', [ReportController::class, 'bookings'], [$staffOrAdmin]);
$router->get('/api/v1/admin/reports/revenue', [ReportController::class, 'revenue'], [$staffOrAdmin]);
$router->get('/api/v1/admin/reports/vehicles', [ReportController::class, 'vehicles'], [$staffOrAdmin]);

// --- SUPPORT & SETTINGS ---
$router->post('/api/v1/support/tickets', [SupportController::class, 'create'], [$authOptional]);
$router->get('/api/v1/support/tickets', [SupportController::class, 'index'], [$authOptional]);
$router->put('/api/v1/admin/support/tickets/{id}/reply', [SupportController::class, 'reply'], [$staffOrAdmin]);

$router->get('/api/v1/settings', [SettingController::class, 'publicSettings']);
$router->post('/api/v1/admin/settings/hero', [SettingController::class, 'updateHero'], [$staffOrAdmin]);
$router->put('/api/v1/admin/settings/hero', [SettingController::class, 'updateHero'], [$staffOrAdmin]);
$router->get('/api/v1/admin/settings', [SettingController::class, 'allSettings'], [$adminOnly]);
$router->post('/api/v1/admin/settings', [SettingController::class, 'updateSettings'], [$adminOnly]);
$router->get('/api/v1/admin/promotions', [SettingController::class, 'promotions'], [$staffOrAdmin]);
$router->post('/api/v1/admin/promotions', [SettingController::class, 'addPromotion'], [$staffOrAdmin]);

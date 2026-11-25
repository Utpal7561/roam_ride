<?php
/**
 * Web Routes Definition
 * Rome & Ride
 */

use App\Core\Router;
use App\Core\Request;
use App\Core\Response;

/** @var Router $router */

// Public / Customer pages
$webHandler = function(Request $request, array $params = []) {
    require_once dirname(__DIR__) . '/public/views/index.php';
    exit;
};

$adminHandler = function(Request $request, array $params = []) {
    require_once dirname(__DIR__) . '/public/views/admin.php';
    exit;
};

$router->get('/', $webHandler);
$router->get('/vehicles', $webHandler);
$router->get('/vehicles/{slug}', $webHandler);
$router->get('/booking', $webHandler);
$router->get('/booking/confirmation', $webHandler);
$router->get('/account', $webHandler);
$router->get('/account/bookings', $webHandler);
$router->get('/account/documents', $webHandler);
$router->get('/account/profile', $webHandler);
$router->get('/about', $webHandler);
$router->get('/contact', $webHandler);
$router->get('/faq', $webHandler);
$router->get('/terms', $webHandler);
$router->get('/cancellation-policy', $webHandler);

// Admin Dashboard Routes
$router->get('/admin', $adminHandler);
$router->get('/admin/dashboard', $adminHandler);
$router->get('/admin/bookings', $adminHandler);
$router->get('/admin/bookings/{id}', $adminHandler);
$router->get('/admin/vehicles', $adminHandler);
$router->get('/admin/payments', $adminHandler);
$router->get('/admin/documents', $adminHandler);
$router->get('/admin/handover', $adminHandler);
$router->get('/admin/reports', $adminHandler);
$router->get('/admin/promotions', $adminHandler);
$router->get('/admin/support', $adminHandler);
$router->get('/admin/settings', $adminHandler);
$router->get('/admin/audit-logs', $adminHandler);
$router->get('/admin/customers', $adminHandler);

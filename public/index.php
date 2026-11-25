<?php
/**
 * Rome & Ride — Pick. Ride. Explore.
 * Front Controller & Application Bootstrapper
 */

// Error reporting configuration
error_reporting(E_ALL);
ini_set('display_errors', '0');

// Define root path constants
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('STORAGE_PATH', ROOT_PATH . '/storage');
define('PUBLIC_PATH', __DIR__);

// Auto-loader for App namespace
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = APP_PATH . '/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

// Load config & helper functions
require_once CONFIG_PATH . '/app.php';
require_once CONFIG_PATH . '/database.php';
require_once APP_PATH . '/Helpers/Security.php';

// Initialize request
$request = new \App\Core\Request();

// Check if route override was passed via query parameter (e.g., ?route=/api/v1/vehicles)
$routeOverride = $request->getQuery('route');
if ($routeOverride) {
    $request->setUri($routeOverride);
}

// Global Security Headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Initialize router
$router = new \App\Core\Router($request);

// Register routes
require_once ROOT_PATH . '/routes/api.php';
require_once ROOT_PATH . '/routes/web.php';

// Resolve route
$router->resolve();

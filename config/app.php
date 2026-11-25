<?php
/**
 * Application Configuration
 * Rome & Ride
 */

// Simple robust .env loader
if (!function_exists('env')) {
    function env($key, $default = null) {
        static $envCache = null;
        if ($envCache === null) {
            $envCache = [];
            $envPath = dirname(__DIR__) . '/.env';
            if (file_exists($envPath)) {
                $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if ($line === '' || str_starts_with($line, '#')) {
                        continue;
                    }
                    if (str_contains($line, '=')) {
                        list($name, $val) = explode('=', $line, 2);
                        $name = trim($name);
                        $val = trim($val);
                        // Strip quotes
                        if ((str_starts_with($val, '"') && str_ends_with($val, '"')) ||
                            (str_starts_with($val, "'") && str_ends_with($val, "'"))) {
                            $val = substr($val, 1, -1);
                        }
                        $envCache[$name] = $val;
                    }
                }
            }
        }

        if (isset($envCache[$key])) {
            $val = $envCache[$key];
            if ($val === 'true' || $val === '(true)') return true;
            if ($val === 'false' || $val === '(false)') return false;
            if ($val === 'null' || $val === '(null)') return null;
            return $val;
        }

        if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
            $val = $_ENV[$key];
            if ($val === 'true' || $val === '(true)') return true;
            if ($val === 'false' || $val === '(false)') return false;
            if ($val === 'null' || $val === '(null)') return null;
            return $val;
        }

        if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
            $val = $_SERVER[$key];
            if ($val === 'true' || $val === '(true)') return true;
            if ($val === 'false' || $val === '(false)') return false;
            if ($val === 'null' || $val === '(null)') return null;
            return $val;
        }

        $sysEnv = getenv($key);
        if ($sysEnv !== false && $sysEnv !== '') {
            if ($sysEnv === 'true' || $sysEnv === '(true)') return true;
            if ($sysEnv === 'false' || $sysEnv === '(false)') return false;
            if ($sysEnv === 'null' || $sysEnv === '(null)') return null;
            return $sysEnv;
        }

        return $default;
    }
}

return [
    'name' => env('APP_NAME', 'Rome & Ride'),
    'tagline' => env('APP_TAGLINE', 'PICK. RIDE. EXPLORE.'),
    'env' => env('APP_ENV', 'development'),
    'debug' => env('APP_DEBUG', true),
    'url' => env('APP_URL', 'http://localhost/roam_ride'),
    'timezone' => 'Asia/Kolkata',
    
    // JWT Configuration
    'jwt' => [
        'secret' => env('JWT_SECRET', 'rome_and_ride_default_jwt_secret_key_change_in_production_1234567890'),
        'access_ttl' => (int) env('JWT_ACCESS_TTL', 3600), // 1 hour
        'refresh_ttl' => (int) env('JWT_REFRESH_TTL', 604800), // 7 days
    ],
    
    // Rate Limiting
    'rate_limit' => [
        'enabled' => env('RATE_LIMIT_ENABLED', true),
        'storage' => env('RATE_LIMIT_STORAGE', 'database'),
        'limits' => [
            'login' => ['capacity' => 5, 'refill_rate' => 0.083], // 5 per min
            'register' => ['capacity' => 3, 'refill_rate' => 0.05], // 3 per min
            'booking' => ['capacity' => 10, 'refill_rate' => 0.166], // 10 per min
            'upload' => ['capacity' => 5, 'refill_rate' => 0.083], // 5 per min
            'api' => ['capacity' => 60, 'refill_rate' => 1.0], // 60 per min
        ]
    ],
    
    // Uploads
    'upload' => [
        'max_size' => (int) env('UPLOAD_MAX_SIZE', 5242880), // 5MB
        'allowed_mimes' => [
            'image/jpeg', 'image/png', 'image/webp', 'application/pdf'
        ],
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'webp', 'pdf'],
    ],

    // Paths
    'paths' => [
        'root' => dirname(__DIR__),
        'public' => dirname(__DIR__) . '/public',
        'storage' => dirname(__DIR__) . '/storage',
        'private_docs' => dirname(__DIR__) . '/storage/private_documents',
        'logs' => dirname(__DIR__) . '/storage/logs',
    ]
];

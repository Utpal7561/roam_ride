<?php
namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Services\RateLimitService;

class RateLimitMiddleware {
    public static function handle(Request $request, string $actionType = 'api'): bool {
        $appConfig = require dirname(__DIR__, 2) . '/config/app.php';
        $limits = $appConfig['rate_limit']['limits'][$actionType] ?? ['capacity' => 60, 'refill_rate' => 1.0];

        $capacity = (float) $limits['capacity'];
        $refillRate = (float) $limits['refill_rate'];

        $ip = $request->getIp();
        $user = $request->getUser();
        $key = $user ? "rate:{$actionType}:user:{$user['id']}" : "rate:{$actionType}:ip:{$ip}";

        $result = RateLimitService::checkAndConsume($key, $capacity, $refillRate);

        header("X-RateLimit-Limit: {$capacity}");
        header("X-RateLimit-Remaining: " . floor($result['remaining']));

        if (!$result['allowed']) {
            header("Retry-After: {$result['retry_after']}");
            Response::json([
                'success' => false,
                'message' => 'Too many requests. Please slow down.',
                'errors' => ['rate_limit' => "Rate limit exceeded. Try again in {$result['retry_after']} seconds."]
            ], 429);
            return false;
        }

        return true;
    }
}

<?php
namespace App\Services;

use App\Core\Database;
use PDO;

class RateLimitService {
    /**
     * Check and consume tokens for a given bucket key.
     *
     * @param string $key e.g. "login:127.0.0.1" or "booking:user:5"
     * @param float $capacity Maximum token capacity
     * @param float $refillRate Tokens added per second (e.g. 5/60 = 0.0833)
     * @param float $cost Cost of this request (default 1.0)
     * @return array ['allowed' => bool, 'remaining' => float, 'retry_after' => int]
     */
    public static function checkAndConsume(string $key, float $capacity, float $refillRate, float $cost = 1.0): array {
        $appConfig = require dirname(__DIR__, 2) . '/config/app.php';
        if (empty($appConfig['rate_limit']['enabled'])) {
            return ['allowed' => true, 'remaining' => $capacity, 'retry_after' => 0];
        }

        try {
            $pdo = Database::getConnection();
            $now = time();

            // Fetch current bucket
            $stmt = $pdo->prepare("SELECT tokens, last_refill_time FROM rate_limits WHERE `key` = ? FOR UPDATE");
            $stmt->execute([$key]);
            $bucket = $stmt->fetch();

            if (!$bucket) {
                // Initialize bucket with capacity minus cost
                $remaining = max(0, $capacity - $cost);
                $insert = $pdo->prepare("INSERT INTO rate_limits (`key`, `tokens`, `last_refill_time`) VALUES (?, ?, ?)");
                $insert->execute([$key, $remaining, $now]);
                
                return [
                    'allowed' => true,
                    'remaining' => $remaining,
                    'retry_after' => 0
                ];
            }

            $lastRefill = (int) $bucket['last_refill_time'];
            $currentTokens = (float) $bucket['tokens'];

            // Refill tokens based on elapsed time
            $elapsed = max(0, $now - $lastRefill);
            $tokensToAdd = $elapsed * $refillRate;
            $newTokens = min($capacity, $currentTokens + $tokensToAdd);

            if ($newTokens >= $cost) {
                $remaining = $newTokens - $cost;
                $update = $pdo->prepare("UPDATE rate_limits SET tokens = ?, last_refill_time = ? WHERE `key` = ?");
                $update->execute([$remaining, $now, $key]);

                return [
                    'allowed' => true,
                    'remaining' => $remaining,
                    'retry_after' => 0
                ];
            } else {
                // Calculate seconds until enough tokens
                $needed = $cost - $newTokens;
                $retryAfter = (int) ceil($needed / max(0.001, $refillRate));

                return [
                    'allowed' => false,
                    'remaining' => $newTokens,
                    'retry_after' => max(1, $retryAfter)
                ];
            }
        } catch (\Exception $e) {
            // Fail open on rate limiting storage errors to avoid blocking legitimate requests
            error_log("Rate limiting error: " . $e->getMessage());
            return ['allowed' => true, 'remaining' => $capacity, 'retry_after' => 0];
        }
    }
}

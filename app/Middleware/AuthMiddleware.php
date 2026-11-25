<?php
namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\JWT;
use App\Core\Database;
use PDO;

class AuthMiddleware {
    public static function handle(Request $request, bool $required = true): bool {
        $token = $request->getBearerToken();

        if (!$token) {
            if ($required) {
                Response::json([
                    'success' => false,
                    'message' => 'Authentication required. No token provided.',
                    'errors' => ['auth' => 'Unauthorized']
                ], 401);
                return false;
            }
            return true;
        }

        $payload = JWT::decode($token);
        if (!$payload || !isset($payload['sub'])) {
            if ($required) {
                Response::json([
                    'success' => false,
                    'message' => 'Invalid or expired token. Please log in again.',
                    'errors' => ['auth' => 'Token invalid or expired']
                ], 401);
                return false;
            }
            return true;
        }

        // Fetch user from DB to ensure user is active and has up-to-date role
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("SELECT id, name, phone, email, role, status FROM users WHERE id = ?");
            $stmt->execute([$payload['sub']]);
            $user = $stmt->fetch();

            if (!$user || $user['status'] !== 'active') {
                if ($required) {
                    Response::json([
                        'success' => false,
                        'message' => 'User account is inactive or not found.',
                        'errors' => ['auth' => 'Account inactive']
                    ], 401);
                    return false;
                }
                return true;
            }

            $request->setUser($user);
            return true;

        } catch (\Exception $e) {
            if ($required) {
                Response::error('Authentication verification failed', null, 500);
                return false;
            }
            return true;
        }
    }
}

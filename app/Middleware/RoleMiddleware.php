<?php
namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

class RoleMiddleware {
    public static function handle(Request $request, array $allowedRoles): bool {
        $user = $request->getUser();

        if (!$user) {
            Response::json([
                'success' => false,
                'message' => 'Unauthorized access',
                'errors' => ['role' => 'Not authenticated']
            ], 401);
            return false;
        }

        // Super Admin always has full access
        if ($user['role'] === 'super_admin') {
            return true;
        }

        if (!in_array($user['role'], $allowedRoles)) {
            Response::json([
                'success' => false,
                'message' => 'Forbidden: You do not have permission to perform this action.',
                'errors' => ['role' => 'Access denied for role: ' . $user['role']]
            ], 403);
            return false;
        }

        return true;
    }
}

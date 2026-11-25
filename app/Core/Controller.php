<?php
namespace App\Core;

use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Core\Database;

class Controller {
    protected function json($data, int $status = 200): void {
        Response::json($data, $status);
    }

    protected function success($data = null, string $message = 'Success', int $status = 200): void {
        Response::success($data, $message, $status);
    }

    protected function error(string $message = 'Error', $errors = null, int $status = 400): void {
        Response::error($message, $errors, $status);
    }

    protected function validate(array $data, array $rules): array {
        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            Response::json([
                'success' => false,
                'message' => $validator->firstError() ?? 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
            exit;
        }
        return $data;
    }

    protected function logAudit(?int $userId, ?string $userRole, string $action, string $entityType, ?string $entityId, ?string $oldValues = null, ?string $newValues = null): void {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, user_role, action, entity_type, entity_id, old_values, new_values, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $userId,
                $userRole,
                $action,
                $entityType,
                $entityId,
                $oldValues,
                $newValues,
                $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
            ]);
        } catch (\Exception $e) {
            error_log("Audit log failed: " . $e->getMessage());
        }
    }
}

<?php
namespace App\Core;

class Response {
    public static function json($data, int $statusCode = 200, array $headers = []): void {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-XSS-Protection: 1; mode=block');

        foreach ($headers as $header => $value) {
            header("$header: $value");
        }

        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function success($data = null, string $message = 'Success', int $statusCode = 200): void {
        self::json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'errors' => null
        ], $statusCode);
    }

    public static function error(string $message = 'Error', $errors = null, int $statusCode = 400): void {
        self::json([
            'success' => false,
            'message' => $message,
            'data' => null,
            'errors' => $errors
        ], $statusCode);
    }

    public static function download(string $filePath, string $downloadName, string $mimeType = 'application/octet-stream'): void {
        if (!file_exists($filePath)) {
            self::error('File not found', null, 404);
        }

        header('Content-Description: File Transfer');
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: inline; filename="' . basename($downloadName) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));
        
        readfile($filePath);
        exit;
    }

    public static function redirect(string $url): void {
        header("Location: $url");
        exit;
    }
}

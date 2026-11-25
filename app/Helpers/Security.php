<?php
namespace App\Helpers;

class Security {
    public static function sanitize(string $input): string {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    public static function cleanInput(array $data): array {
        $clean = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $clean[$key] = self::cleanInput($value);
            } elseif (is_string($value)) {
                $clean[$key] = trim($value);
            } else {
                $clean[$key] = $value;
            }
        }
        return $clean;
    }

    public static function generateBookingCode(): string {
        return 'RNR-' . date('Y') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
    }

    public static function generatePaymentCode(): string {
        return 'PAY-' . date('Y') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
    }

    public static function generateTicketCode(): string {
        return 'TCK-' . date('Y') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
    }

    public static function validateUploadedFile(array $file, array $allowedMimes, int $maxBytes): array {
        if (!isset($file['error']) || is_array($file['error'])) {
            return ['valid' => false, 'error' => 'Invalid file upload parameter.'];
        }

        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                return ['valid' => false, 'error' => 'No file was uploaded.'];
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return ['valid' => false, 'error' => 'File size exceeds maximum allowed limit.'];
            default:
                return ['valid' => false, 'error' => 'Unknown upload error occurred.'];
        }

        if ($file['size'] > $maxBytes) {
            $mb = round($maxBytes / 1048576, 1);
            return ['valid' => false, 'error' => "File is too large. Max size allowed is {$mb}MB."];
        }

        // Verify MIME type using finfo
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $realMime = $finfo->file($file['tmp_name']);

        if (!in_array($realMime, $allowedMimes)) {
            return ['valid' => false, 'error' => "Invalid file format ({$realMime}). Allowed types: JPG, PNG, WEBP, PDF."];
        }

        return ['valid' => true, 'mime' => $realMime];
    }
}

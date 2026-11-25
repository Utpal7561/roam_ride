<?php
namespace App\Core;

class Request {
    private string $method;
    private string $uri;
    private array $headers;
    private array $queryParams;
    private array $bodyParams;
    private array $files;
    private ?array $user = null;

    public function __construct() {
        $this->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        
        // Handle method override
        if ($this->method === 'POST' && isset($_POST['_method'])) {
            $this->method = strtoupper($_POST['_method']);
        }

        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        
        // Strip query string
        if (str_contains($uri, '?')) {
            $uri = explode('?', $uri, 2)[0];
        }

        // Clean URI relative to base folder if running in subfolder like /roam_ride or /roam_ride/public
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $baseDir = str_replace('\\', '/', dirname($scriptName));

        if ($baseDir !== '/' && $baseDir !== '.' && $baseDir !== '' && str_starts_with($uri, $baseDir)) {
            $uri = substr($uri, strlen($baseDir));
        } else {
            $parentBase = str_replace('\\', '/', dirname($baseDir));
            if ($parentBase !== '/' && $parentBase !== '.' && $parentBase !== '' && str_starts_with($uri, $parentBase)) {
                $uri = substr($uri, strlen($parentBase));
            }
        }

        // Also handle if root folder name itself is at the start of $uri (e.g. /roam_ride or /roam_ride/)
        if (defined('ROOT_PATH')) {
            $folderName = basename(ROOT_PATH);
            if ($folderName !== '' && preg_match('#^/' . preg_quote($folderName, '#') . '(/|$)#i', $uri)) {
                $uri = preg_replace('#^/' . preg_quote($folderName, '#') . '#i', '', $uri);
            }
        }

        $this->uri = '/' . ltrim($uri, '/');
        if ($this->uri === '') {
            $this->uri = '/';
        }

        $this->headers = function_exists('getallheaders') ? getallheaders() : [];
        $this->queryParams = $_GET;
        $this->files = $_FILES;

        // Parse JSON body or form body
        $contentType = $this->getHeader('Content-Type') ?? '';
        if (str_contains($contentType, 'application/json')) {
            $rawInput = file_get_contents('php://input');
            $this->bodyParams = json_decode($rawInput, true) ?? [];
        } else {
            $this->bodyParams = $_POST;
        }
    }

    public function getMethod(): string {
        return $this->method;
    }

    public function getUri(): string {
        return $this->uri;
    }

    public function setUri(string $uri): void {
        $this->uri = $uri;
    }

    public function getHeader(string $name): ?string {
        foreach ($this->headers as $key => $value) {
            if (strcasecmp($key, $name) === 0) {
                return $value;
            }
        }
        $serverKey = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $_SERVER[$serverKey] ?? null;
    }

    public function getBearerToken(): ?string {
        $authHeader = $this->getHeader('Authorization');
        if ($authHeader && preg_match('/Bearer\s(\S+)/i', $authHeader, $matches)) {
            return $matches[1];
        }
        // Also support cookie or query param token for downloads/media
        return $_COOKIE['rnr_token'] ?? $this->getQuery('token') ?? null;
    }

    public function all(): array {
        return array_merge($this->queryParams, $this->bodyParams);
    }

    public function get(string $key, $default = null) {
        return $this->bodyParams[$key] ?? $this->queryParams[$key] ?? $default;
    }

    public function getQuery(string $key, $default = null) {
        return $this->queryParams[$key] ?? $default;
    }

    public function getFile(string $key): ?array {
        return $this->files[$key] ?? null;
    }

    public function setUser(array $user): void {
        $this->user = $user;
    }

    public function getUser(): ?array {
        return $this->user;
    }

    public function getIp(): string {
        return $_SERVER['HTTP_CF_CONNECTING_IP'] 
            ?? $_SERVER['HTTP_X_FORWARDED_FOR'] 
            ?? $_SERVER['REMOTE_ADDR'] 
            ?? '127.0.0.1';
    }

    public function getUserAgent(): string {
        return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    }
}
